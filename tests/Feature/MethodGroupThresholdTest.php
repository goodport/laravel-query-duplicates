<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class MethodGroupThresholdTest extends TestCase
{
    /**
     * @var array{get?: array{enabled?: bool, threshold?: int}, other?: array{enabled?: bool, threshold?: int}}|null
     */
    protected ?array $methods = [
        'get' => ['threshold' => 3],
        'other' => ['threshold' => 5],
    ];

    /**
     * GET requests use their own threshold.
     */
    public function test_get_group_uses_its_own_threshold(): void
    {
        $this->get(route('testing.duplicates.threshold', ['times' => 2]))->assertOk();
        $this->assertDuplicateCount(0);

        $this->get(route('testing.duplicates.threshold', ['times' => 3]))->assertOk();
        $this->assertDuplicateCount(1);
    }

    /**
     * Non-GET requests use their own threshold and keep their actual method.
     */
    public function test_other_group_uses_its_own_threshold(): void
    {
        $this->post(route('testing.duplicates.threshold', ['times' => 4]))->assertOk();
        $this->assertDuplicateCount(0);

        $this->post(route('testing.duplicates.threshold', ['times' => 5]))->assertOk();

        $this->assertSame('POST', $this->singleDuplicate()->method);
    }
}
