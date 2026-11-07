<?php

namespace Goodport\QueryDuplicates\Config;

use InvalidArgumentException;

final readonly class DuplicateQuerySettings
{
    public function __construct(
        public bool $enabled,
        public bool $strict,
        public MethodGroupSettings $getGroup,
        public MethodGroupSettings $otherGroup,
        public ExclusionSettings $exclusions,
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
        ['get' => $getGroup, 'other' => $otherGroup] = MethodGroupSettings::fromConfig(
            $config['methods'] ?? null,
        );

        return new self(
            enabled: $enabled,
            strict: self::boolean($config, 'strict'),
            getGroup: $getGroup,
            otherGroup: $otherGroup,
            exclusions: ExclusionSettings::fromConfig($config['exclude'] ?? null),
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
}
