<?php

namespace Goodport\QueryDuplicates\Tests\Unit;

use Goodport\QueryDuplicates\Support\RouteExcludeMatcher;
use PHPUnit\Framework\TestCase;

final class RouteExcludeMatcherTest extends TestCase
{
    /**
     * An exact route name matches.
     */
    public function test_exact_route_name_matches(): void
    {
        $matcher = new RouteExcludeMatcher(['health']);

        $this->assertTrue($matcher->matches('health'));
    }

    /**
     * A wildcard pattern matches nested route names.
     */
    public function test_wildcard_pattern_matches(): void
    {
        $matcher = new RouteExcludeMatcher(['admin.*']);

        $this->assertTrue($matcher->matches('admin.users.index'));
        $this->assertFalse($matcher->matches('users.index'));
    }

    /**
     * Unnamed and unknown routes do not match.
     */
    public function test_unnamed_and_unknown_routes_do_not_match(): void
    {
        $matcher = new RouteExcludeMatcher(['admin.*']);

        $this->assertFalse($matcher->matches(null));
        $this->assertFalse($matcher->matches('health'));
    }
}
