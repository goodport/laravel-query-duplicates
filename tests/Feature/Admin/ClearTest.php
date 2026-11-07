<?php

namespace Goodport\QueryDuplicates\Tests\Feature\Admin;

use Goodport\QueryDuplicates\Storage\SqliteStorage;
use Goodport\QueryDuplicates\Storage\StorageUnavailableException;
use Goodport\QueryDuplicates\Tests\TestCase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class ClearTest extends TestCase
{
    /**
     * Clearing deletes all reports and redirects back to the table.
     */
    public function test_clear_deletes_all_reports_and_redirects(): void
    {
        $this->allowQueryDuplicates();
        $this->insertDuplicate();
        $this->insertDuplicate();

        $this->assertDuplicateCount(2);

        $this->followingRedirects()
            ->post(route('query-duplicates.clear'))
            ->assertOk()
            ->assertSee(trans('query-duplicates::query-duplicates.cleared'));

        $this->assertDuplicateCount(0);
    }

    /**
     * Clearing without available storage redirects to the setup hint without a success message.
     */
    public function test_clear_without_available_storage_redirects_without_success_message(): void
    {
        $this->allowQueryDuplicates();
        $this->makeStorageUnavailable();

        $this->post(route('query-duplicates.clear'))
            ->assertRedirect(route('query-duplicates.index'))
            ->assertSessionMissing('status');

        $this->get(route('query-duplicates.index'))
            ->assertOk()
            ->assertSee(trans('query-duplicates::query-duplicates.setup_title'))
            ->assertDontSee(trans('query-duplicates::query-duplicates.cleared'));
    }

    /**
     * Другая ошибка базы данных при очистке не маскируется.
     */
    public function test_clear_rethrows_other_database_errors(): void
    {
        $this->allowQueryDuplicates();
        $this->insertDuplicate();

        DB::connection(SqliteStorage::CONNECTION)->statement(
            'CREATE TRIGGER prevent_query_duplicate_clear BEFORE DELETE ON query_duplicates '.
            "BEGIN SELECT RAISE(ABORT, 'clear blocked'); END",
        );

        $this->expectException(QueryException::class);

        $this->withoutExceptionHandling()->post(route('query-duplicates.clear'));
    }

    /**
     * Replaces the package storage with a double that cannot be opened.
     */
    private function makeStorageUnavailable(): void
    {
        $this->app->instance(SqliteStorage::class, new class extends SqliteStorage
        {
            public function ensure(): void
            {
                throw new StorageUnavailableException(
                    $this->path(),
                    'simulated storage failure',
                );
            }
        });
    }
}
