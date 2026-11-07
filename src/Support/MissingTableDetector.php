<?php

namespace Goodport\QueryDuplicates\Support;

use Illuminate\Database\QueryException;

final class MissingTableDetector
{
    private const TABLE = 'query_duplicates';

    /**
     * SQLSTATE codes for a missing table in MySQL and PostgreSQL.
     *
     * @var list<string>
     */
    private const SQLSTATES = ['42S02', '42P01'];

    /**
     * Tells whether the exception is caused by the missing package table.
     *
     * Other database failures, including errors on the same connection, are not
     * masked: the failed SQL and the driver error must both point to the table.
     */
    public function matches(QueryException $exception): bool
    {
        if (! str_contains($exception->getSql(), self::TABLE)) {
            return false;
        }

        $errorInfo = $exception->errorInfo;

        if (in_array($errorInfo[0] ?? null, self::SQLSTATES, true)) {
            return true;
        }

        return ($errorInfo[1] ?? null) === 1
            && str_contains($exception->getMessage(), 'no such table');
    }
}
