<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class ExclusionTest extends TestCase
{
    protected ?array $exclude = ['as sessions'];

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
}
