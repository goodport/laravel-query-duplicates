<?php

namespace Goodport\QueryDuplicates\Tests\Support;

use Illuminate\Database\Connection;
use stdClass;

/**
 * A connection without a real database: runs the callback and dispatches QueryExecuted.
 *
 * Used by package tests that verify the collection lifecycle rather than SQL.
 */
final class FakeQueryConnection extends Connection
{
    public function __construct(string $name)
    {
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
        return $this->run($query, $bindings, static fn (): array => []);
    }

    /**
     * Returns a fixed query time so feature tests do not depend on machine speed.
     */
    protected function getElapsedTime($start)
    {
        return 0.1;
    }
}
