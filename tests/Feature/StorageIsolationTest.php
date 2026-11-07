<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Models\QueryDuplicate;
use Goodport\QueryDuplicates\Storage\SqliteStorage;
use Goodport\QueryDuplicates\Tests\TestCase;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Schema;

final class StorageIsolationTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.connections.application', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        $app['config']->set('database.default', 'application');
    }

    /**
     * Reports are written only to the package SQLite connection.
     */
    public function test_reports_are_written_only_to_package_storage(): void
    {
        $model = new QueryDuplicate;

        $this->assertSame(SqliteStorage::CONNECTION, $model->getConnectionName());
        $this->assertNotSame(
            $this->app['db']->getDefaultConnection(),
            $model->getConnectionName(),
        );

        $this->get(route('testing.duplicates.identical'))->assertOk();

        $this->assertDuplicateCount(1);
        $this->assertFalse(
            Schema::connection('application')->hasTable('query_duplicates'),
        );
    }
}
