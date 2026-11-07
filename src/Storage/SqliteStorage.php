<?php

namespace Goodport\QueryDuplicates\Storage;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Owns the package SQLite connection, file, and report table.
 *
 * The package never uses the application default connection: local application
 * requests, application tests, and the admin page all share this file.
 */
class SqliteStorage
{
    public const CONNECTION = 'query-duplicates';

    public const MEMORY_PATH = ':memory:';

    private const TABLE = 'query_duplicates';

    /**
     * Returns the SQLite path from the configuration or the package default.
     *
     * @throws InvalidArgumentException When the configured value is not a non-empty string.
     */
    public function path(): string
    {
        $configuredPath = config('query-duplicates.storage_path');

        if ($configuredPath === null) {
            return storage_path('framework/query-duplicates.sqlite');
        }

        if (! is_string($configuredPath)) {
            throw new InvalidArgumentException(
                'The [query-duplicates.storage_path] config value must be null or a non-empty string.',
            );
        }

        $configuredPath = trim($configuredPath);

        if ($configuredPath === '') {
            throw new InvalidArgumentException(
                'The [query-duplicates.storage_path] config value must be null or a non-empty string.',
            );
        }

        return $configuredPath;
    }

    /**
     * Creates the SQLite directory and the report table when they are missing.
     *
     * Concurrent processes may both try to create the table. The losing process
     * re-checks the table and continues, because the schema is already ready.
     *
     * @throws StorageUnavailableException When the directory, file, or table cannot be used.
     */
    public function ensure(): void
    {
        $storagePath = $this->path();

        $this->ensureDirectory($storagePath);

        $schema = DB::connection(self::CONNECTION)->getSchemaBuilder();

        try {
            if ($schema->hasTable(self::TABLE)) {
                return;
            }

            $this->createTable();
        } catch (QueryException $exception) {
            if ($this->tableExists($schema)) {
                return;
            }

            throw new StorageUnavailableException(
                $storagePath,
                'the query_duplicates table could not be created',
                $exception,
            );
        }
    }

    /**
     * Creates a missing storage directory and verifies write access.
     */
    private function ensureDirectory(string $storagePath): void
    {
        if ($storagePath === self::MEMORY_PATH) {
            return;
        }

        $directory = dirname($storagePath);

        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new StorageUnavailableException(
                $storagePath,
                "the directory [{$directory}] could not be created",
            );
        }

        if (! is_writable($directory)) {
            throw new StorageUnavailableException(
                $storagePath,
                "the directory [{$directory}] is not writable",
            );
        }

        if (! file_exists($storagePath) && ! @touch($storagePath)) {
            throw new StorageUnavailableException(
                $storagePath,
                'the SQLite file could not be created',
            );
        }

        if (! is_writable($storagePath)) {
            throw new StorageUnavailableException(
                $storagePath,
                'the SQLite file is not writable',
            );
        }
    }

    /**
     * Creates the report table with its indexes.
     */
    private function createTable(): void
    {
        DB::connection(self::CONNECTION)->getSchemaBuilder()->create(
            self::TABLE,
            static function (Blueprint $table): void {
                $table->id();
                $table->char('report_key', 64)->unique();
                $table->string('method', 10);
                $table->text('path');
                $table->string('route', 255)->nullable();
                $table->text('sql');
                $table->unsignedInteger('query_count');
                $table->decimal('time_ms', 10, 2);
                $table->string('type', 10);
                $table->unsignedInteger('occurrences')->default(0);
                $table->timestamps();

                $table->index('updated_at');
                $table->index('query_count');
                $table->index('time_ms');
                $table->index('occurrences');
            },
        );
    }

    /**
     * Re-checks the table after a failed creation attempt.
     */
    private function tableExists(SchemaBuilder $schema): bool
    {
        try {
            return $schema->hasTable(self::TABLE);
        } catch (QueryException) {
            return false;
        }
    }
}
