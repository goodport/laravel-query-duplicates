<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Storage\SqliteStorage;
use Goodport\QueryDuplicates\Tests\TestCase;

final class StorageMemoryTest extends TestCase
{
    /**
     * Surrounding spaces do not turn the memory path into a real file path.
     */
    public function test_memory_path_with_surrounding_spaces_is_normalized(): void
    {
        config(['query-duplicates.storage_path' => ' :memory: ']);

        $this->assertSame(
            SqliteStorage::MEMORY_PATH,
            (new SqliteStorage)->path(),
        );
    }

    /**
     * The memory path does not create a directory or a file.
     */
    public function test_memory_path_does_not_create_storage_file(): void
    {
        $defaultPath = storage_path('framework/query-duplicates.sqlite');

        $this->assertFileDoesNotExist($defaultPath);

        config(['query-duplicates.storage_path' => ' :memory: ']);

        (new SqliteStorage)->ensure();

        $this->assertFileDoesNotExist($defaultPath);
        $this->assertFileDoesNotExist(SqliteStorage::MEMORY_PATH);
    }
}
