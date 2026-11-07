<?php

namespace Goodport\QueryDuplicates\Services;

use Goodport\QueryDuplicates\Models\QueryDuplicate;
use Goodport\QueryDuplicates\Storage\SqliteStorage;

final class QueryDuplicateDeleter
{
    public function __construct(
        private readonly SqliteStorage $storage,
    ) {}

    /**
     * Deletes one stored report by its identifier.
     *
     * A missing identifier is a no-op: the report may have been deleted in
     * another tab, and the page should still redirect without an error.
     */
    public function delete(int $id): void
    {
        $this->storage->ensure();

        QueryDuplicate::query()->whereKey($id)->delete();
    }
}
