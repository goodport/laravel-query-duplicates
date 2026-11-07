<?php

namespace Goodport\QueryDuplicates\Services;

use Goodport\QueryDuplicates\Models\QueryDuplicate;

final class QueryDuplicateClearer
{
    /**
     * Deletes every stored report.
     */
    public function clear(): void
    {
        QueryDuplicate::query()->delete();
    }
}
