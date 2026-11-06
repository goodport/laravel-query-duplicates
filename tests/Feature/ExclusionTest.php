<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class ExclusionTest extends TestCase
{
    protected ?array $exclude = [
        'as sessions',
        [
            'sql' => 'select * from `example_events` where `category` = ?',
            'bindings' => [0 => 'security'],
        ],
    ];

    /**
     * Substring exclusion removes matching queries from the report.
     */
    public function test_substring_exclusion_skips_matching_queries(): void
    {
        $this->get(route('testing.duplicates.excluded-substring'))->assertOk();

        $records = $this->loggedRecords();

        $this->assertCount(1, $records);
        $this->assertSame('select 2 as kept', $records[0]['context']['sql']);
    }

    /**
     * A rule exclusion skips only queries with matching SQL and bindings.
     */
    public function test_binding_exclusion_skips_only_matching_variant(): void
    {
        $sql = 'select * from `example_events` where `category` = ?';

        $this->get(route('testing.duplicates.excluded-bindings'))->assertOk();

        $records = $this->loggedRecords();

        $this->assertCount(2, $records);
        $this->assertEqualsCanonicalizing(
            [$sql, 'select 2 as kept'],
            array_column(array_column($records, 'context'), 'sql'),
        );

        foreach ($records as $record) {
            if ($record['context']['sql'] === $sql) {
                $this->assertSame(2, $record['context']['query_count']);

                return;
            }
        }

        $this->fail('The query with other bindings was not reported.');
    }
}
