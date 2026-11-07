<?php

namespace Goodport\QueryDuplicates\Tests\Feature\Admin;

use Goodport\QueryDuplicates\Storage\SqliteStorage;
use Goodport\QueryDuplicates\Storage\StorageUnavailableException;
use Goodport\QueryDuplicates\Tests\TestCase;

final class StorageUnavailableTest extends TestCase
{
    /**
     * An unavailable SQLite storage shows the setup message, path, and reason.
     */
    public function test_page_shows_setup_message_when_storage_is_unavailable(): void
    {
        $this->allowQueryDuplicates();

        $storagePath = (string) config('query-duplicates.storage_path');
        $failure = new StorageUnavailableException($storagePath, 'simulated storage failure');

        $this->app->instance(SqliteStorage::class, new class($failure) extends SqliteStorage
        {
            public function __construct(
                private readonly StorageUnavailableException $failure,
            ) {}

            public function ensure(): void
            {
                throw $this->failure;
            }
        });

        $this->get(route('query-duplicates.index'))
            ->assertOk()
            ->assertSee(trans('query-duplicates::query-duplicates.setup_title'))
            ->assertSee(trans('query-duplicates::query-duplicates.storage_unavailable', [
                'path' => $storagePath,
            ]))
            ->assertSee(trans('query-duplicates::query-duplicates.storage_reason', [
                'reason' => $failure->getMessage(),
            ]))
            ->assertDontSee(trans('query-duplicates::query-duplicates.empty'));
    }
}
