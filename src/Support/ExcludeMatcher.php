<?php

namespace Goodport\QueryDuplicates\Support;

final class ExcludeMatcher
{
    /**
     * @param  list<string|array{sql: string, bindings: array<int, mixed>}>  $exclude
     */
    public function __construct(
        private readonly array $exclude,
    ) {}

    /**
     * Checks whether the query should be excluded from collection.
     *
     * @param  array<int, mixed>  $bindings
     */
    public function matches(string $sql, array $bindings = []): bool
    {
        foreach ($this->exclude as $rule) {
            if (is_string($rule)) {
                if (str_contains($sql, $rule)) {
                    return true;
                }

                continue;
            }

            if (str_contains($sql, $rule['sql']) && $this->bindingsMatch($rule['bindings'], $bindings)) {
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
