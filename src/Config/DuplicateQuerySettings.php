<?php

namespace Goodport\QueryDuplicates\Config;

use Goodport\QueryDuplicates\Support\HttpMethods;
use InvalidArgumentException;

final readonly class DuplicateQuerySettings
{
    /**
     * @param  list<string|array{sql: string, bindings: array<int, mixed>}>  $exclude
     * @param  list<string>  $methods
     */
    public function __construct(
        public bool $enabled,
        public bool $strict,
        public int $threshold,
        public array $exclude,
        public array $methods = [],
    ) {}

    /**
     * Creates settings from the configuration array and validates their values.
     *
     * When the package is disabled, the bundled defaults are returned without
     * validating the rest of the user configuration.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws InvalidArgumentException If a configuration value is invalid.
     */
    public static function fromConfig(array $config): self
    {
        if (! self::enabledFromConfig($config['enabled'] ?? null)) {
            return self::parse(self::defaultConfig(), false);
        }

        return self::parse($config, true);
    }

    /**
     * Parses the configuration with an explicit enabled flag.
     *
     * @param  array<string, mixed>  $config
     */
    private static function parse(array $config, bool $enabled): self
    {
        return new self(
            enabled: $enabled,
            strict: self::boolean($config, 'strict'),
            threshold: self::integer($config, 'threshold', 2),
            exclude: self::exclusions($config),
            methods: HttpMethods::normalizeList($config['methods'] ?? []),
        );
    }

    /**
     * Parses the package enabled flag from the raw configuration value.
     *
     * @throws InvalidArgumentException If the value is not boolean.
     */
    public static function enabledFromConfig(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        $parsed = BooleanValue::tryParse($value);

        if ($parsed === null) {
            throw new InvalidArgumentException(
                'The [query-duplicates.enabled] config value must be a boolean.',
            );
        }

        return $parsed;
    }

    /**
     * Returns the bundled default configuration.
     *
     * @return array<string, mixed>
     */
    private static function defaultConfig(): array
    {
        /** @var array<string, mixed> $defaults */
        $defaults = require dirname(__DIR__, 2).'/config/query-duplicates.php';

        return $defaults;
    }

    /**
     * Reads an integer value within the allowed range.
     *
     * @param  array<string, mixed>  $config
     */
    private static function integer(array $config, string $key, int $minimum): int
    {
        $value = $config[$key] ?? null;
        $parsed = is_int($value)
            ? $value
            : (is_string($value) ? filter_var($value, FILTER_VALIDATE_INT) : false);

        if ($parsed === false || $parsed < $minimum) {
            throw new InvalidArgumentException(
                "The [query-duplicates.{$key}] config value must be an integer greater than or equal to {$minimum}.",
            );
        }

        return $parsed;
    }

    /**
     * Reads a boolean value without implicit casting.
     *
     * @param  array<string, mixed>  $config
     */
    private static function boolean(array $config, string $key): bool
    {
        $parsed = BooleanValue::tryParse($config[$key] ?? null);

        if ($parsed === null) {
            throw new InvalidArgumentException(
                "The [query-duplicates.{$key}] config value must be a boolean.",
            );
        }

        return $parsed;
    }

    /**
     * Reads exclusion rules: SQL substrings and rules with optional bindings.
     *
     * @param  array<string, mixed>  $config
     * @return list<string|array{sql: string, bindings: array<int, mixed>}>
     */
    private static function exclusions(array $config): array
    {
        $value = $config['exclude'] ?? null;

        if (! is_array($value) || ! array_is_list($value)) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude] config value must be a list of exclusion rules.',
            );
        }

        $validated = [];

        foreach ($value as $entry) {
            $validated[] = is_array($entry)
                ? self::exclusionRule($entry)
                : self::exclusionSubstring($entry);
        }

        return $validated;
    }

    /**
     * Validates a SQL substring exclusion rule.
     */
    private static function exclusionSubstring(mixed $entry): string
    {
        if (! is_string($entry) || trim($entry) === '') {
            throw new InvalidArgumentException(
                'Each [query-duplicates.exclude] entry must be a non-empty string or a rule array.',
            );
        }

        return $entry;
    }

    /**
     * Validates an exclusion rule with a SQL substring and optional bindings.
     *
     * @param  array<array-key, mixed>  $rule
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    private static function exclusionRule(array $rule): array
    {
        self::assertKnownRuleKeys($rule);

        $sql = $rule['sql'] ?? null;

        if (! is_string($sql) || trim($sql) === '') {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude] rule [sql] must be a non-empty string.',
            );
        }

        $bindings = $rule['bindings'] ?? [];

        if (! is_array($bindings)) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude] rule [bindings] must be an array.',
            );
        }

        foreach (array_keys($bindings) as $position) {
            if (! is_int($position) || $position < 0) {
                throw new InvalidArgumentException(
                    'The [query-duplicates.exclude] rule [bindings] keys must be non-negative integers.',
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
                'The [query-duplicates.exclude] rule may contain only [sql] and [bindings] keys.',
            );
        }
    }
}
