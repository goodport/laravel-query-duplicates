<?php

namespace Goodport\QueryDuplicates\Support;

use InvalidArgumentException;

final class HttpMethods
{
    /**
     * HTTP methods supported by the package.
     *
     * @var list<string>
     */
    public const ALL = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    /**
     * Normalizes a configured list of HTTP methods.
     *
     * Values are trimmed, uppercased, validated and deduplicated. An empty list
     * means that every method is tracked.
     *
     * @return list<string>
     *
     * @throws InvalidArgumentException When the value is not a list of supported methods.
     */
    public static function normalizeList(mixed $methods): array
    {
        if (! is_array($methods) || ! array_is_list($methods)) {
            throw new InvalidArgumentException(
                'The [query-duplicates.methods] config value must be a list of supported HTTP methods.',
            );
        }

        $normalized = [];

        foreach ($methods as $method) {
            if (! is_string($method) || trim($method) === '') {
                throw new InvalidArgumentException(
                    'The [query-duplicates.methods] config value must be a list of supported HTTP methods.',
                );
            }

            $method = strtoupper(trim($method));

            if (! in_array($method, self::ALL, true)) {
                throw new InvalidArgumentException(
                    'The [query-duplicates.methods] config value contains an unsupported HTTP method.',
                );
            }

            $normalized[] = $method;
        }

        return array_values(array_unique($normalized));
    }
}
