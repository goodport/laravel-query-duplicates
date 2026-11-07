<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class ExclusionTest extends TestCase
{
    protected ?array $exclude = [
        'routes' => [],
        'queries' => [
            'as sessions',
            [
                'sql' => 'select * from `example_events` where `category` = ?',
                'bindings' => [0 => 'security'],
            ],
        ],
    ];

    /**
     * Substring exclusion removes matching queries from the report.
     */
    public function test_substring_exclusion_skips_matching_queries(): void
    {
        $this->get(route('testing.duplicates.excluded-substring'))->assertOk();

        $this->assertSame('select 2 as kept', $this->singleDuplicate()->sql);
    }

    /**
     * A rule exclusion skips only queries with matching SQL and bindings.
     */
    public function test_binding_exclusion_skips_only_matching_variant(): void
    {
        $sql = 'select * from `example_events` where `category` = ?';

        $this->get(route('testing.duplicates.excluded-bindings'))->assertOk();

        $rows = $this->duplicateRows();

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(
            [$sql, 'select 2 as kept'],
            $rows->pluck('sql')->all(),
        );

        $reportedRow = $rows->firstWhere('sql', $sql);

        $this->assertNotNull($reportedRow);
        $this->assertSame(2, $reportedRow->query_count);
    }
}
