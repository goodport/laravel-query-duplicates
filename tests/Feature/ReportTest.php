<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Aggregator\QueryGroup;
use Goodport\QueryDuplicates\Tests\TestCase;

final class ReportTest extends TestCase
{
    /**
     * Only queries inside the HTTP request are logged; preparation and assertions are not.
     */
    public function test_only_queries_inside_http_request_are_logged(): void
    {
        $connection = $this->app->make('db')->connection();

        $connection->select('select ? as id', [99]);
        $connection->select('select ? as id', [99]);

        $this->get(route('testing.duplicates.identical'))->assertOk();

        $connection->select('select ? as id', [7]);
        $connection->select('select ? as id', [7]);

        $this->assertSame(3, $this->singleReportContext()['query_count']);
    }

    /**
     * Different bindings are not a duplicate while strict mode is off.
     */
    public function test_different_bindings_are_not_logged_by_default(): void
    {
        $this->get(route('testing.duplicates.different-bindings'))->assertOk();

        $this->assertReportCount(0);
    }

    /**
     * The log entry has the documented fields.
     */
    public function test_log_entry_contains_only_documented_fields(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();

        $context = $this->singleReportContext();

        $this->assertSame(
            ['method', 'path', 'route', 'sql', 'query_count', 'time_ms', 'type'],
            array_keys($context),
        );
        $this->assertSame('GET', $context['method']);
        $this->assertSame('duplicates/identical', $context['path']);
        $this->assertSame('testing.duplicates.identical', $context['route']);
        $this->assertSame('select ? as id', $context['sql']);
        $this->assertSame(3, $context['query_count']);
        $this->assertSame(QueryGroup::TYPE__DUPLICATE, $context['type']);
    }

    /**
     * The message names the report type and execution count.
     */
    public function test_message_contains_type_and_count(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();

        $message = $this->singleReportRecord()['message'];

        $this->assertStringContainsString('Duplicate query: 3 executions', $message);
    }

    /**
     * Queries on the second SQL connection are included in the report.
     */
    public function test_queries_on_secondary_connection_are_logged(): void
    {
        $this->get(route('testing.duplicates.secondary'))->assertOk();

        $this->assertSame(2, $this->singleReportContext()['query_count']);
    }

    /**
     * The path context has no query string or request parameters.
     */
    public function test_path_has_no_query_string(): void
    {
        $this->get(route('testing.duplicates.identical').'?token=secret-token')->assertOk();

        $this->assertSame('duplicates/identical', $this->singleReportContext()['path']);
        $this->assertStringNotContainsString('secret-token', $this->rawLogContents());
    }

    /**
     * A 404 does not prevent the report from being written in terminate.
     */
    public function test_not_found_does_not_break_reporting(): void
    {
        $this->get(route('testing.duplicates.identical').'/missing-page')->assertNotFound();

        $this->assertNull($this->singleReportContext()['route']);
    }
}
