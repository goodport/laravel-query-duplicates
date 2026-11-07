<?php

namespace Goodport\QueryDuplicates\Config;

use InvalidArgumentException;

final readonly class MethodGroupSettings
{
    public const DEFAULT_GET_ENABLED = true;

    public const DEFAULT_GET_THRESHOLD = 2;

    public const DEFAULT_OTHER_ENABLED = true;

    public const DEFAULT_OTHER_THRESHOLD = 5;

    public const MINIMUM_THRESHOLD = 2;

    public function __construct(
        public bool $enabled,
        public int $threshold,
    ) {}

    /**
     * Returns the default settings of the GET group.
     */
    public static function getDefaults(): self
    {
        return new self(
            enabled: self::DEFAULT_GET_ENABLED,
            threshold: self::DEFAULT_GET_THRESHOLD,
        );
    }

    /**
     * Returns the default settings of the non-GET group.
     */
    public static function otherDefaults(): self
    {
        return new self(
            enabled: self::DEFAULT_OTHER_ENABLED,
            threshold: self::DEFAULT_OTHER_THRESHOLD,
        );
    }

    /**
     * Parses the [methods] config section into the GET and non-GET groups.
     *
     * A missing or empty section uses the bundled defaults. A missing group
     * uses its own defaults. Unknown groups and unknown group keys are rejected.
     *
     * @return array{get: self, other: self}
     *
     * @throws InvalidArgumentException If the section or a group value is invalid.
     */
    public static function fromConfig(mixed $methods): array
    {
        if ($methods === null || $methods === []) {
            return self::defaultGroups();
        }

        if (! is_array($methods) || array_is_list($methods)) {
            throw new InvalidArgumentException(
                'The [query-duplicates.methods] config value must be an array with [get] and [other] groups.',
            );
        }

        self::assertKnownGroupNames($methods);

        return [
            'get' => self::group(
                value: $methods['get'] ?? null,
                provided: array_key_exists('get', $methods),
                defaults: self::getDefaults(),
                name: 'get',
            ),
            'other' => self::group(
                value: $methods['other'] ?? null,
                provided: array_key_exists('other', $methods),
                defaults: self::otherDefaults(),
                name: 'other',
            ),
        ];
    }

    /**
     * Returns both groups with their default settings.
     *
     * @return array{get: self, other: self}
     */
    private static function defaultGroups(): array
    {
        return [
            'get' => self::getDefaults(),
            'other' => self::otherDefaults(),
        ];
    }

    /**
     * Parses one method group and fills missing values from its defaults.
     *
     * @throws InvalidArgumentException If the group value is invalid.
     */
    private static function group(mixed $value, bool $provided, self $defaults, string $name): self
    {
        if (! $provided || $value === []) {
            return $defaults;
        }

        if (! is_array($value) || array_is_list($value)) {
            throw new InvalidArgumentException(
                "The [query-duplicates.methods.{$name}] config value must be an array.",
            );
        }

        self::assertKnownGroupKeys($value, $name);

        return new self(
            enabled: array_key_exists('enabled', $value)
                ? self::boolean($value['enabled'], $name)
                : $defaults->enabled,
            threshold: array_key_exists('threshold', $value)
                ? self::threshold($value['threshold'], $name)
                : $defaults->threshold,
        );
    }

    /**
     * Rejects unknown group names to make configuration typos visible.
     *
     * @param  array<array-key, mixed>  $methods
     */
    private static function assertKnownGroupNames(array $methods): void
    {
        $unknownGroups = array_diff(array_keys($methods), ['get', 'other']);

        if ($unknownGroups !== []) {
            throw new InvalidArgumentException(
                'The [query-duplicates.methods] config may contain only [get] and [other] groups.',
            );
        }
    }

    /**
     * Rejects unknown group keys to make configuration typos visible.
     *
     * @param  array<array-key, mixed>  $group
     */
    private static function assertKnownGroupKeys(array $group, string $name): void
    {
        $unknownKeys = array_diff(array_keys($group), ['enabled', 'threshold']);

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException(
                "The [query-duplicates.methods.{$name}] config may contain only [enabled] and [threshold] keys.",
            );
        }
    }

    /**
     * Reads a boolean value without implicit casting.
     */
    private static function boolean(mixed $value, string $group): bool
    {
        $parsed = BooleanValue::tryParse($value);

        if ($parsed === null) {
            throw new InvalidArgumentException(
                "The [query-duplicates.methods.{$group}.enabled] config value must be a boolean.",
            );
        }

        return $parsed;
    }

    /**
     * Reads an integer value within the allowed range.
     */
    private static function threshold(mixed $value, string $group): int
    {
        $parsed = is_int($value)
            ? $value
            : (is_string($value) ? filter_var($value, FILTER_VALIDATE_INT) : false);

        if ($parsed === false || $parsed < self::MINIMUM_THRESHOLD) {
            throw new InvalidArgumentException(
                "The [query-duplicates.methods.{$group}.threshold] config value must be an integer greater than or equal to ".self::MINIMUM_THRESHOLD.'.',
            );
        }

        return $parsed;
    }
}
