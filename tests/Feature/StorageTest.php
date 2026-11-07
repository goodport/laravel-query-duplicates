<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Storage\SqliteStorage;
use Goodport\QueryDuplicates\Storage\StorageUnavailableException;
use Goodport\QueryDuplicates\Tests\TestCase;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;

final class StorageTest extends TestCase
{
    private string $storageDirectory;

    protected function setUp(): void
    {
        $this->storageDirectory = sys_get_temp_dir().'/query-duplicates-'.bin2hex(random_bytes(6));

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem)->deleteDirectory($this->storageDirectory);
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set(
            'query-duplicates.storage_path',
            $this->storageDirectory.'/query-duplicates.sqlite',
        );
    }

    /**
     * The SQLite file and the report table are created automatically.
     */
    public function test_file_and_table_are_created_automatically(): void
    {
        $this->assertFileExists($this->storageDirectory.'/query-duplicates.sqlite');

        $this->assertTrue(
            DB::connection(SqliteStorage::CONNECTION)
                ->getSchemaBuilder()
                ->hasTable('query_duplicates'),
        );
    }

    /**
     * The registered connection uses WAL and the configured busy timeout.
     */
    public function test_connection_uses_wal_and_busy_timeout(): void
    {
        $connection = DB::connection(SqliteStorage::CONNECTION);

        $journalMode = $connection->selectOne('PRAGMA journal_mode');
        $busyTimeout = $connection->selectOne('PRAGMA busy_timeout');

        $this->assertSame('wal', strtolower((string) $journalMode->journal_mode));
        $this->assertSame(5000, (int) $busyTimeout->timeout);
    }

    /**
     * The package falls back to storage/framework/query-duplicates.sqlite.
     */
    public function test_default_path_is_used_when_storage_path_is_null(): void
    {
        config(['query-duplicates.storage_path' => null]);

        $this->assertSame(
            storage_path('framework/query-duplicates.sqlite'),
            (new SqliteStorage)->path(),
        );
    }

    /**
     * A repeated ensure does not fail when the table already exists.
     */
    public function test_ensure_is_idempotent(): void
    {
        $storage = new SqliteStorage;

        $storage->ensure();
        $storage->ensure();

        $this->assertDuplicateCount(0);
    }

    /**
     * An unusable directory raises the storage exception with the active path.
     */
    public function test_unavailable_directory_raises_storage_exception(): void
    {
        $blockerPath = $this->storageDirectory.'/blocker';
        (new Filesystem)->put($blockerPath, 'not a directory');

        $storagePath = $blockerPath.'/reports.sqlite';
        config(['query-duplicates.storage_path' => $storagePath]);

        $this->expectException(StorageUnavailableException::class);
        $this->expectExceptionMessage($storagePath);

        (new SqliteStorage)->ensure();
    }
}
