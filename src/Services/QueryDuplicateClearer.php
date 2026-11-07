<?php

namespace Goodport\QueryDuplicates\Services;

use Goodport\QueryDuplicates\Models\QueryDuplicate;
use Goodport\QueryDuplicates\Storage\SqliteStorage;

final class QueryDuplicateClearer
{
    public function __construct(
        private readonly SqliteStorage $storage,
    ) {}

    /**
     * Deletes every stored report.
     */
    public function clear(): void
    {
        $this->storage->ensure();

        QueryDuplicate::query()->delete();
    }
}
