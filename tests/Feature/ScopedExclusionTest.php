<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class ScopedExclusionTest extends TestCase
{
    protected ?array $exclude = [
        [
            'route' => 'testing.duplicates.excluded-substring',
            'query' => 'as sessions',
        ],
    ];

    /**
     * A query exclusion with a route applies only on that route.
     */
    public function test_query_exclusion_is_limited_to_the_configured_route(): void
    {
        $this->get(route('testing.duplicates.excluded-substring'))->assertOk();

        $this->assertSame('select 2 as kept', $this->singleDuplicate()->sql);

        $this->get(route('testing.duplicates.sessions'))->assertOk();

        $this->assertDuplicateCount(3);
        $this->assertSame(
            0,
            $this->duplicateRows()
                ->where('route', 'testing.duplicates.excluded-substring')
                ->where('sql', 'select 1 as sessions')
                ->count(),
        );
        $this->assertSame(
            1,
            $this->duplicateRows()
                ->where('route', 'testing.duplicates.sessions')
                ->where('sql', 'select 1 as sessions')
                ->count(),
        );
    }
}
