<?php

namespace Goodport\QueryDuplicates\Config;

final class BooleanValue
{
    /**
     * Parses a boolean from a configuration value without implicit casting.
     *
     * Returns null when the value is not a recognized boolean, so callers can
     * apply their own default or validation error.
     */
    public static function tryParse(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) && ($value === 0 || $value === 1)) {
            return $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if ($normalized === '1' || $normalized === 'true') {
                return true;
            }

            if ($normalized === '0' || $normalized === 'false') {
                return false;
            }
        }

        return null;
    }
}
