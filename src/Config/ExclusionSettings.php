<?php

namespace Goodport\QueryDuplicates\Config;

use InvalidArgumentException;

final readonly class ExclusionSettings
{
    /**
     * @param  list<string>  $routes
     * @param  list<string|array{sql: string, bindings: array<int, mixed>}>  $queries
     */
    public function __construct(
        public array $routes,
        public array $queries,
    ) {}

    /**
     * Parses the [exclude] config section into route and query exclusions.
     *
     * A missing or empty section means no exclusions. The previous flat list of
     * query rules is not supported and must be moved under [queries].
     *
     * @throws InvalidArgumentException If the section or an entry is invalid.
     */
    public static function fromConfig(mixed $exclude): self
    {
        if ($exclude === null || $exclude === []) {
            return new self([], []);
        }

        if (! is_array($exclude) || array_is_list($exclude)) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude] config value must be an array with [routes] and [queries] keys.',
            );
        }

        $unknownKeys = array_diff(array_keys($exclude), ['routes', 'queries']);

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude] config may contain only [routes] and [queries] keys.',
            );
        }

        return new self(
            routes: self::routes($exclude['routes'] ?? []),
            queries: self::queries($exclude['queries'] ?? []),
        );
    }

    /**
     * Reads route name patterns. A pattern may contain [*].
     *
     * @return list<string>
     */
    private static function routes(mixed $routes): array
    {
        if (! is_array($routes) || ! array_is_list($routes)) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude.routes] config value must be a list of route names.',
            );
        }

        $validated = [];

        foreach ($routes as $route) {
            if (! is_string($route) || trim($route) === '') {
                throw new InvalidArgumentException(
                    'Each [query-duplicates.exclude.routes] entry must be a non-empty string.',
                );
            }

            $validated[] = trim($route);
        }

        return array_values(array_unique($validated));
    }

    /**
     * Reads query exclusion rules: SQL substrings and rules with optional bindings.
     *
     * @return list<string|array{sql: string, bindings: array<int, mixed>}>
     */
    private static function queries(mixed $queries): array
    {
        if (! is_array($queries) || ! array_is_list($queries)) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude.queries] config value must be a list of query exclusion rules.',
            );
        }

        $validated = [];

        foreach ($queries as $entry) {
            $validated[] = is_array($entry)
                ? self::queryRule($entry)
                : self::querySubstring($entry);
        }

        return $validated;
    }

    /**
     * Validates a SQL substring query exclusion.
     */
    private static function querySubstring(mixed $entry): string
    {
        if (! is_string($entry) || trim($entry) === '') {
            throw new InvalidArgumentException(
                'Each [query-duplicates.exclude.queries] entry must be a non-empty string or a rule array.',
            );
        }

        return $entry;
    }

    /**
     * Validates a query exclusion rule with a SQL substring and optional bindings.
     *
     * @param  array<array-key, mixed>  $rule
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    private static function queryRule(array $rule): array
    {
        self::assertKnownRuleKeys($rule);

        $sql = $rule['sql'] ?? null;

        if (! is_string($sql) || trim($sql) === '') {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude.queries] rule [sql] must be a non-empty string.',
            );
        }

        $bindings = $rule['bindings'] ?? [];

        if (! is_array($bindings)) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude.queries] rule [bindings] must be an array.',
            );
        }

        foreach (array_keys($bindings) as $position) {
            if (! is_int($position) || $position < 0) {
                throw new InvalidArgumentException(
                    'The [query-duplicates.exclude.queries] rule [bindings] keys must be non-negative integers.',
                );
            }
        }

        return [
            'sql' => $sql,
            'bindings' => $bindings,
        ];
    }

    /**
     * Rejects unknown rule keys to make configuration typos visible.
     *
     * @param  array<array-key, mixed>  $rule
     */
    private static function assertKnownRuleKeys(array $rule): void
    {
        $unknownKeys = array_diff(array_keys($rule), ['sql', 'bindings']);

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude.queries] rule may contain only [sql] and [bindings] keys.',
            );
        }
    }
}
