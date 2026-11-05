<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Aggregator\QueryGroup;
use Goodport\QueryDuplicates\Tests\TestCase;

final class StrictModeTest extends TestCase
{
    protected ?bool $strict = true;

    /**
     * Strict mode reports the same SQL regardless of bindings.
     */
    public function test_different_bindings_are_reported(): void
    {
        $this->get(route('testing.duplicates.different-bindings'))->assertOk();

        $records = $this->loggedRecords();

        $this->assertCount(1, $records);
        $this->assertSame(3, $records[0]['context']['query_count']);
        $this->assertSame(QueryGroup::TYPE__DUPLICATE, $records[0]['context']['type']);
    }
}
