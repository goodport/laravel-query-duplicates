<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class RecordingTest extends TestCase
{
    /**
     * A repeated report updates the same row and keeps the first created time.
     */
    public function test_repeated_report_updates_the_same_row(): void
    {
        $this->freezeSecond();

        $this->get(route('testing.duplicates.identical'))->assertOk();

        $row = $this->singleDuplicate();
        $createdAt = $row->created_at;
        $firstUpdatedAt = $row->updated_at;

        $this->assertSame(1, $row->occurrences);

        $this->travel(1)->minutes();

        $this->get(route('testing.duplicates.identical'))->assertOk();

        $row->refresh();

        $this->assertDuplicateCount(1);
        $this->assertSame(2, $row->occurrences);
        $this->assertTrue($row->created_at->equalTo($createdAt));
        $this->assertTrue($row->updated_at->greaterThan($firstUpdatedAt));
    }

    /**
     * A batch of several groups creates a row for each report identity.
     */
    public function test_batch_creates_a_row_per_report(): void
    {
        $this->get(route('testing.duplicates.excluded-substring'))->assertOk();

        $rows = $this->duplicateRows();

        $this->assertCount(2, $rows);
        $this->assertSame([1, 1], $rows->pluck('occurrences')->all());
    }

    /**
     * Different report identities create different rows.
     */
    public function test_different_report_keys_create_different_rows(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();
        $this->get(route('testing.duplicates.secondary'))->assertOk();

        $this->assertDuplicateCount(2);
        $this->assertEqualsCanonicalizing(
            ['testing.duplicates.identical', 'testing.duplicates.secondary'],
            $this->duplicateRows()->pluck('route')->all(),
        );
    }

    /**
     * Groups with the same report key are merged and their totals summed.
     */
    public function test_same_report_key_is_merged_within_one_batch(): void
    {
        $this->get(route('testing.duplicates.multiple-connections'))->assertOk();

        $row = $this->singleDuplicate();

        $this->assertSame(4, $row->query_count);
        $this->assertEqualsWithDelta(0.4, $row->time_ms, 0.001);
        $this->assertSame(1, $row->occurrences);
    }

    /**
     * The last request updates the execution count and the total time.
     */
    public function test_query_count_and_time_are_updated_by_the_last_request(): void
    {
        $this->get(route('testing.duplicates.repeat', ['times' => 2]))->assertOk();
        $this->get(route('testing.duplicates.repeat', ['times' => 5]))->assertOk();

        $row = $this->singleDuplicate();

        $this->assertSame(5, $row->query_count);
        $this->assertEqualsWithDelta(0.5, $row->time_ms, 0.001);
        $this->assertSame(2, $row->occurrences);
    }
}
