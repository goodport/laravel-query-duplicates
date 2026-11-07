<?php

namespace Goodport\QueryDuplicates\Storage;

use RuntimeException;
use Throwable;

final class StorageUnavailableException extends RuntimeException
{
    /**
     * @param  string  $storagePath  SQLite path that cannot be used.
     * @param  string  $reason  Short technical reason for the failure.
     */
    public function __construct(
        public readonly string $storagePath,
        string $reason,
        ?Throwable $previous = null,
    ) {
        $details = $previous === null
            ? $reason
            : $reason.': '.rtrim($previous->getMessage(), '.');

        parent::__construct(
            "The query duplicates SQLite storage [{$storagePath}] is unavailable: {$details}.",
            0,
            $previous,
        );
    }
}
