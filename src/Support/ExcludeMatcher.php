<?php

namespace Goodport\QueryDuplicates\Support;

use Goodport\QueryDuplicates\Config\ExclusionRule;
use Illuminate\Support\Str;

final class ExcludeMatcher
{
    /**
     * @param  list<ExclusionRule>  $rules
     */
    public function __construct(
        private readonly array $rules,
    ) {}

    /**
     * Checks whether the query should be excluded for the current route.
     *
     * A route-only rule excludes every query on matching routes. A query-only
     * rule excludes the query on every route. A rule with both keys excludes
     * only matching queries on matching routes.
     *
     * @param  array<int, mixed>  $bindings
     */
    public function matches(?string $routeName, string $sql, array $bindings = []): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->route !== null
                && ($routeName === null || ! Str::is($rule->route, $routeName))) {
                continue;
            }

            if ($rule->sql === null) {
                return true;
            }

            if (str_contains($sql, $rule->sql) && $this->bindingsMatch($rule->bindings, $bindings)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tells whether a route-only rule excludes the whole route.
     */
    public function excludesRoute(?string $routeName): bool
    {
        if ($routeName === null) {
            return false;
        }

        foreach ($this->rules as $rule) {
            if ($rule->route !== null
                && $rule->sql === null
                && Str::is($rule->route, $routeName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks only the binding positions listed in the rule.
     *
     * @param  array<int, mixed>  $expected
     * @param  array<int, mixed>  $bindings
     */
    private function bindingsMatch(array $expected, array $bindings): bool
    {
        foreach ($expected as $position => $value) {
            if (! array_key_exists($position, $bindings) || $bindings[$position] !== $value) {
                return false;
            }
        }

        return true;
    }
}
