<?php

namespace Goodport\QueryDuplicates\Tests\Support;

use Illuminate\Contracts\Foundation\Application;

trait DefinesQueryDuplicateDatabase
{
    protected const QUERY_DUPLICATE_CONNECTION = 'query-duplicates';

    /**
     * Registers an isolated in-memory SQLite connection for the package table.
     */
    protected function defineQueryDuplicateDatabase(Application $app): void
    {
        $app['config']->set('database.connections.'.self::QUERY_DUPLICATE_CONNECTION, [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        $app['config']->set('query-duplicates.connection', self::QUERY_DUPLICATE_CONNECTION);
    }

    /**
     * Creates the package table through its real migration.
     */
    protected function migrateQueryDuplicateTable(): void
    {
        $migration = require dirname(__DIR__, 2).'/database/migrations/2026_11_07_000000_create_query_duplicates_table.php';

        $migration->up();
    }
}
