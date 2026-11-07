<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class EmptyExclusionTest extends TestCase
{
    protected ?array $exclude = [
        'routes' => [],
        'queries' => [],
    ];

    /**
     * An empty exclusion list keeps every duplicate query in the report.
     */
    public function test_empty_exclude_reports_all_queries(): void
    {
        $this->get(route('testing.duplicates.excluded-substring'))->assertOk();

        $this->assertDuplicateCount(2);
    }
}
