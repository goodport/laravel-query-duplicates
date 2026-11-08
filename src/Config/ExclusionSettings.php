<?php

namespace Goodport\QueryDuplicates\Config;

use InvalidArgumentException;

final readonly class ExclusionSettings
{
    /**
     * @param  list<ExclusionRule>  $rules
     */
    public function __construct(
        public array $rules,
    ) {}

    /**
     * Parses the [exclude] config section into a list of exclusion rules.
     *
     * A missing or empty section means no exclusions. The previous split
     * [routes] and [queries] structure is not supported.
     *
     * @throws InvalidArgumentException If the section or a rule is invalid.
     */
    public static function fromConfig(mixed $exclude): self
    {
        if ($exclude === null || $exclude === []) {
            return new self([]);
        }

        if (! is_array($exclude) || ! array_is_list($exclude)) {
            throw new InvalidArgumentException(
                'The [query-duplicates.exclude] config value must be a list of exclusion rules.',
            );
        }

        $rules = [];

        foreach ($exclude as $rule) {
            $rules[] = ExclusionRule::fromConfig($rule);
        }

        return new self($rules);
    }
}
