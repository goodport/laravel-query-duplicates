<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class DisabledGetGroupTest extends TestCase
{
    /**
     * @var array{get?: array{enabled?: bool, threshold?: int}, other?: array{enabled?: bool, threshold?: int}}|null
     */
    protected ?array $methods = [
        'get' => ['enabled' => false],
    ];

    /**
     * Отключённая GET-группа не трекает GET, но не мешает трекингу other.
     */
    public function test_disabled_get_group_still_tracks_other_methods(): void
    {
        $this->get(route('testing.duplicates.threshold', ['times' => 5]))->assertOk();
        $this->assertDuplicateCount(0);

        $this->post(route('testing.duplicates.threshold', ['times' => 5]))->assertOk();

        $this->assertSame('POST', $this->singleDuplicate()->method);
    }
}
