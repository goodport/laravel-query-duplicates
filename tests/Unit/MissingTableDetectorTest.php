<?php

namespace Goodport\QueryDuplicates\Tests\Unit;

use Goodport\QueryDuplicates\Support\MissingTableDetector;
use Illuminate\Database\QueryException;
use PDOException;
use PHPUnit\Framework\TestCase;

final class MissingTableDetectorTest extends TestCase
{
    /**
     * A missing table on SQLite is detected.
     */
    public function test_sqlite_missing_table_is_detected(): void
    {
        $detector = new MissingTableDetector;

        $this->assertTrue($detector->matches($this->exception(
            sql: 'select * from "query_duplicates"',
            errorInfo: ['HY000', 1, 'no such table: query_duplicates'],
        )));
    }

    /**
     * A missing table on MySQL and PostgreSQL is detected by SQLSTATE.
     */
    public function test_sqlstate_missing_table_is_detected(): void
    {
        $detector = new MissingTableDetector;

        $this->assertTrue($detector->matches($this->exception(
            sql: 'select * from `query_duplicates`',
            errorInfo: ['42S02', 1146, "Table 'app.query_duplicates' doesn't exist"],
        )));

        $this->assertTrue($detector->matches($this->exception(
            sql: 'select * from "query_duplicates"',
            errorInfo: ['42P01', 7, 'relation "query_duplicates" does not exist'],
        )));
    }

    /**
     * Another database error is not masked as a missing table.
     */
    public function test_other_database_error_is_not_masked(): void
    {
        $detector = new MissingTableDetector;

        $this->assertFalse($detector->matches($this->exception(
            sql: 'select * from "query_duplicates"',
            errorInfo: ['HY000', 1, 'no such column: missing'],
        )));
    }

    /**
     * A missing table error for another query is not masked.
     */
    public function test_missing_table_error_for_another_query_is_not_masked(): void
    {
        $detector = new MissingTableDetector;

        $this->assertFalse($detector->matches($this->exception(
            sql: 'select * from "example_posts"',
            errorInfo: ['42S02', 1146, "Table 'app.example_posts' doesn't exist"],
        )));
    }

    /**
     * @param  array{0: string, 1: int, 2: string}  $errorInfo
     */
    private function exception(string $sql, array $errorInfo): QueryException
    {
        $previous = new PDOException($errorInfo[2]);
        $previous->errorInfo = $errorInfo;

        return new QueryException('testing', $sql, [], $previous);
    }
}
