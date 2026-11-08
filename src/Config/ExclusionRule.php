<?php

namespace Goodport\QueryDuplicates\Config;

use InvalidArgumentException;

final readonly class ExclusionRule
{
    /**
     * @param  array<int, mixed>  $bindings
     */
    public function __construct(
        public ?string $route,
        public ?string $sql,
        public array $bindings,
    ) {}

    /**
     * Parses one exclusion rule.
     *
     * A rule may contain a route, a query, or both. A route-only rule excludes
     * every query on matching routes; a query-only rule excludes the query on
     * every route; both together scope the query to matching routes.
     *
     * @throws InvalidArgumentException If the rule is invalid.
     */
    public static function fromConfig(mixed $rule): self
    {
        if (! is_array($rule)) {
            throw new InvalidArgumentException(
                'Each [query-duplicates.exclude] entry must be a rule array.',
            );
        }

        if ($rule === []) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude] rule must contain [route] or [query].',
            );
        }

        if (array_is_list($rule)) {
            throw new InvalidArgumentException(
                'Each [query-duplicates.exclude] entry must be a rule array.',
            );
        }

        $unknownKeys = array_diff(array_keys($rule), ['route', 'query']);

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude] rule may contain only [route] and [query] keys.',
            );
        }

        $route = self::route($rule['route'] ?? null);
        $hasQuery = array_key_exists('query', $rule);

        if ($route === null && ! $hasQuery) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude] rule must contain [route] or [query].',
            );
        }

        if (! $hasQuery) {
            return new self(route: $route, sql: null, bindings: []);
        }

        return self::withQuery($rule['query'], $route);
    }

    /**
     * Parses the query part of a rule: a SQL substring or a rule with bindings.
     *
     * @throws InvalidArgumentException If the query is invalid.
     */
    private static function withQuery(mixed $query, ?string $route): self
    {
        if (is_string($query)) {
            if (trim($query) === '') {
                throw new InvalidArgumentException(
                    'The [query-duplicates.exclude] rule [query] must be a non-empty string or a rule array.',
                );
            }

            return new self(route: $route, sql: $query, bindings: []);
        }

        if (! is_array($query) || array_is_list($query)) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude] rule [query] must be a non-empty string or a rule array.',
            );
        }

        $unknownKeys = array_diff(array_keys($query), ['sql', 'bindings']);

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude] rule [query] may contain only [sql] and [bindings] keys.',
            );
        }

        $sql = $query['sql'] ?? null;

        if (! is_string($sql) || trim($sql) === '') {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude] rule [query.sql] must be a non-empty string.',
            );
        }

        $bindings = $query['bindings'] ?? [];

        if (! is_array($bindings)) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude] rule [query.bindings] must be an array.',
            );
        }

        foreach (array_keys($bindings) as $position) {
            if (! is_int($position) || $position < 0) {
                throw new InvalidArgumentException(
                    'The [query-duplicates.exclude] rule [query.bindings] keys must be non-negative integers.',
                );
            }
        }

        return new self(route: $route, sql: $sql, bindings: $bindings);
    }

    /**
     * Reads an optional route name pattern.
     *
     * @throws InvalidArgumentException If the route is not a non-empty string.
     */
    private static function route(mixed $route): ?string
    {
        if ($route === null) {
            return null;
        }

        if (! is_string($route) || trim($route) === '') {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude] rule [route] must be a non-empty string or null.',
            );
        }

        return trim($route);
    }
}
