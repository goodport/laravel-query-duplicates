<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class RouteExclusionTest extends TestCase
{
    protected ?array $exclude = [
        ['route' => 'testing.duplicates.identical'],
    ];

    /**
     * A request with an excluded route name is not reported.
     */
    public function test_excluded_route_is_not_reported(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();
        $this->assertDuplicateCount(0);

        $this->get(route('testing.duplicates.secondary'))->assertOk();
        $this->assertDuplicateCount(1);
    }
}
