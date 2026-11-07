<?php

namespace Goodport\QueryDuplicates\Tests\Feature\Admin;

use Goodport\QueryDuplicates\Tests\TestCase;

final class DisabledTrackingTest extends TestCase
{
    protected bool $packageEnabled = false;

    /**
     * The admin page stays available when tracking is disabled.
     */
    public function test_page_is_available_when_tracking_is_disabled(): void
    {
        $this->allowQueryDuplicates();
        $this->insertDuplicate(['route' => 'posts.index']);

        $this->get(route('query-duplicates.index'))
            ->assertOk()
            ->assertSee('posts.index');
    }
}
