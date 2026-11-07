<?php

namespace Goodport\QueryDuplicates\Config;

final class AdminRouteDefaults
{
    public const ENABLED = true;

    public const PREFIX = 'query-duplicates';

    /**
     * @var list<string>
     */
    public const MIDDLEWARE = ['web', 'can:view-query-duplicates'];

    /**
     * Returns the complete default admin route configuration.
     *
     * @return array{enabled: bool, prefix: string, middleware: list<string>}
     */
    public static function all(): array
    {
        return [
            'enabled' => self::ENABLED,
            'prefix' => self::PREFIX,
            'middleware' => self::MIDDLEWARE,
        ];
    }

    /**
     * Normalizes a partial or invalid admin route configuration.
     *
     * Missing and invalid values fall back to the safe defaults, so a partial
     * application override never loses the gate middleware.
     *
     * @return array{enabled: bool, prefix: string, middleware: list<string>}
     */
    public static function normalize(mixed $config): array
    {
        if (! is_array($config)) {
            return self::all();
        }

        return [
            'enabled' => self::normalizeEnabled($config['enabled'] ?? self::ENABLED),
            'prefix' => self::normalizePrefix($config['prefix'] ?? self::PREFIX),
            'middleware' => self::normalizeMiddleware($config['middleware'] ?? self::MIDDLEWARE),
        ];
    }

    /**
     * Parses the enabled flag, including string values from cached configs.
     */
    private static function normalizeEnabled(mixed $value): bool
    {
        return BooleanValue::tryParse($value) ?? self::ENABLED;
    }

    /**
     * Returns a non-empty route prefix or the default one.
     */
    private static function normalizePrefix(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return self::PREFIX;
        }

        return $value;
    }

    /**
     * Returns a non-empty list of string middleware or the default one.
     *
     * @return list<string>
     */
    private static function normalizeMiddleware(mixed $value): array
    {
        if (! is_array($value)) {
            return self::MIDDLEWARE;
        }

        $middleware = [];

        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                $middleware[] = trim($item);
            }
        }

        return $middleware === [] ? self::MIDDLEWARE : $middleware;
    }
}
