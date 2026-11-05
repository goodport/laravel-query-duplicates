<?php

namespace Goodport\QueryDuplicates\Support;

final class ExcludeMatcher
{
    private readonly array $exclude;

    /**
     * @param  list<string>  $exclude  Substrings that exclude a query from collection.
     */
    public function __construct(array $exclude)
    {
        $this->exclude = $exclude;
    }

    /**
     * Checks whether the SQL should be excluded from collection.
     */
    public function matches(string $sql): bool
    {
        foreach ($this->exclude as $fragment) {
            if (str_contains($sql, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
