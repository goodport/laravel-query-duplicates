<?php

namespace Goodport\QueryDuplicates\Support;

use Illuminate\Support\Str;

final class RouteExcludeMatcher
{
    /**
     * @param  list<string>  $patterns
     */
    public function __construct(
        private readonly array $patterns,
    ) {}

    /**
     * Tells whether the route name matches an excluded pattern.
     *
     * Patterns support [*] wildcards, for example [admin.*]. Unnamed routes
     * cannot match a route exclusion.
     */
    public function matches(?string $routeName): bool
    {
        if ($routeName === null) {
            return false;
        }

        foreach ($this->patterns as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }
}
