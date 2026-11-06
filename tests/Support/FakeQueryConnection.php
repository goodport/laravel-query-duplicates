<?php

namespace Goodport\QueryDuplicates\Tests\Support;

use Closure;
use Illuminate\Database\Connection;
use stdClass;

/**
 * A connection without a real database: runs the callback and dispatches QueryExecuted.
 *
 * Used by package tests that verify the collection lifecycle rather than SQL.
 */
final class FakeQueryConnection extends Connection
{
    /**
     * @param  (Closure(string, array<int, mixed>): array<int, mixed>)|null  $selectRows
     */
    public function __construct(
        string $name,
        private readonly ?Closure $selectRows = null,
    ) {
        parent::__construct(new stdClass, $name, '', ['name' => $name]);
    }

    /**
     * @param  string  $query
     * @param  array<int, mixed>  $bindings
     * @param  bool  $useReadPdo
     * @return array<int, mixed>
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run(
            $query,
            $bindings,
            fn (): array => $this->selectRows === null ? [] : ($this->selectRows)($query, $bindings),
        );
    }

    /**
     * Returns a fixed query time so feature tests do not depend on machine speed.
     */
    protected function getElapsedTime($start)
    {
        return 0.1;
    }
}
