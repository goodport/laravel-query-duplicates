<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class MethodTrackingTest extends TestCase
{
    /**
     * @var array{get?: array{enabled?: bool, threshold?: int}, other?: array{enabled?: bool, threshold?: int}}|null
     */
    protected ?array $methods = [
        'other' => ['enabled' => false],
    ];

    /**
     * Трекаются только запросы из включённой группы HTTP-методов.
     */
    public function test_only_enabled_method_groups_are_tracked(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();
        $this->assertDuplicateCount(1);

        $this->post(route('testing.duplicates.posted'))->assertOk();
        $this->assertDuplicateCount(1);
    }
}
