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

        $context = $this->singleReportContext();

        $this->assertSame('select 2 as kept', $context['sql']);
    }

    /**
     * A rule exclusion skips only queries with matching SQL and bindings.
     */
    public function test_binding_exclusion_skips_only_matching_variant(): void
    {
        $sql = 'select * from `example_events` where `category` = ?';

        $this->get(route('testing.duplicates.excluded-bindings'))->assertOk();

        $contexts = $this->reportContexts();

        $this->assertCount(2, $contexts);
        $this->assertEqualsCanonicalizing(
            [$sql, 'select 2 as kept'],
            array_column($contexts, 'sql'),
        );

        foreach ($contexts as $context) {
            if ($context['sql'] === $sql) {
                $this->assertSame(2, $context['query_count']);

                return;
            }
        }

        $this->fail('The query with other bindings was not reported.');
    }
}
