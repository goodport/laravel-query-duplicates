<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class EmptyMethodFilterTest extends TestCase
{
    /**
     * Пустой список методов включает трекинг для всех методов.
     */
    public function test_empty_method_list_tracks_post_requests(): void
    {
        $this->post(route('testing.duplicates.posted'))->assertOk();

        $this->assertDuplicateCount(1);
    }
}
