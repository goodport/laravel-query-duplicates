<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Aggregator\QueryGroup;
use Goodport\QueryDuplicates\Tests\TestCase;

final class ReportTest extends TestCase
{
    /**
     * Only queries inside the HTTP request are stored; preparation and assertions are not.
     */
    public function test_only_queries_inside_http_request_are_stored(): void
    {
        $connection = $this->app->make('db')->connection();

        $connection->select('select ? as id', [99]);
        $connection->select('select ? as id', [99]);

        $this->get(route('testing.duplicates.identical'))->assertOk();

        $connection->select('select ? as id', [7]);
        $connection->select('select ? as id', [7]);

        $this->assertSame(3, $this->singleDuplicate()->query_count);
    }

    /**
     * Different bindings are not a duplicate while strict mode is off.
     */
    public function test_different_bindings_are_not_stored_by_default(): void
    {
        $this->get(route('testing.duplicates.different-bindings'))->assertOk();

        $this->assertDuplicateCount(0);
    }

    /**
     * The stored row has the documented fields.
     */
    public function test_row_contains_only_documented_fields(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();

        $row = $this->singleDuplicate();

        $this->assertSame('GET', $row->method);
        $this->assertSame('duplicates/identical', $row->path);
        $this->assertSame('testing.duplicates.identical', $row->route);
        $this->assertSame('select ? as id', $row->sql);
        $this->assertSame(3, $row->query_count);
        $this->assertEqualsWithDelta(0.3, $row->time_ms, 0.001);
        $this->assertSame(QueryGroup::TYPE__DUPLICATE, $row->type);
        $this->assertSame(1, $row->occurrences);
    }

    /**
     * Queries on the second SQL connection are included in the report.
     */
    public function test_queries_on_secondary_connection_are_stored(): void
    {
        $this->get(route('testing.duplicates.secondary'))->assertOk();

        $this->assertSame(2, $this->singleDuplicate()->query_count);
    }

    /**
     * The same SQL from GET and POST requests creates separate reports.
     */
    public function test_same_sql_with_different_methods_creates_separate_reports(): void
    {
        $this->get(route('testing.duplicates.threshold', ['times' => 2]))->assertOk();
        $this->post(route('testing.duplicates.threshold', ['times' => 5]))->assertOk();

        $rows = $this->duplicateRows();

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(['GET', 'POST'], $rows->pluck('method')->all());
        $this->assertSame(
            ['select ? as threshold'],
            $rows->pluck('sql')->unique()->values()->all(),
        );
    }

    /**
     * The stored path has no query string or request parameters.
     */
    public function test_path_has_no_query_string(): void
    {
        $this->get(route('testing.duplicates.identical').'?token=secret-token')->assertOk();

        $row = $this->singleDuplicate();

        $this->assertSame('duplicates/identical', $row->path);
        $this->assertStringNotContainsString('secret-token', (string) json_encode($row->getAttributes()));
    }

    /**
     * A 404 does not prevent the report from being stored in terminate.
     */
    public function test_not_found_does_not_break_reporting(): void
    {
        $this->get(route('testing.duplicates.identical').'/missing-page')->assertNotFound();

        $this->assertNull($this->singleDuplicate()->route);
    }
}
