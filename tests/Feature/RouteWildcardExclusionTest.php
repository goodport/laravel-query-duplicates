<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class RouteWildcardExclusionTest extends TestCase
{
    protected ?array $exclude = [
        'routes' => ['testing.duplicates.id*'],
        'queries' => [],
    ];

    /**
     * A wildcard route exclusion skips matching requests and keeps other routes.
     */
    public function test_wildcard_route_exclusion_skips_only_matching_routes(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();
        $this->assertDuplicateCount(0);

        $this->get(route('testing.duplicates.secondary'))->assertOk();
        $this->assertDuplicateCount(1);
    }
}
