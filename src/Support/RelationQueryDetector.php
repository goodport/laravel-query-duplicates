<?php

namespace Goodport\QueryDuplicates\Support;

interface RelationQueryDetector
{
    /**
     * Returns the application call site of a lazy relation query, or null when
     * the current query is not executed by an Eloquent relation.
     */
    public function detect(): ?string;
}
