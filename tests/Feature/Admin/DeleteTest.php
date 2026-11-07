<?php

namespace Goodport\QueryDuplicates\Tests\Feature\Admin;

use Goodport\QueryDuplicates\Storage\SqliteStorage;
use Goodport\QueryDuplicates\Storage\StorageUnavailableException;
use Goodport\QueryDuplicates\Tests\TestCase;

final class DeleteTest extends TestCase
{
    /**
     * Удаляется только выбранная запись, остальные остаются.
     */
    public function test_delete_removes_only_the_selected_report(): void
    {
        $this->allowQueryDuplicates();
        $deleted = $this->insertDuplicate();
        $kept = $this->insertDuplicate();

        $this->delete(route('query-duplicates.destroy', ['queryDuplicate' => $deleted->id]))
            ->assertRedirect(route('query-duplicates.index'))
            ->assertSessionHas('status', trans('query-duplicates::query-duplicates.deleted'));

        $this->assertDuplicateCount(1);

        $remaining = $this->singleDuplicate();

        $this->assertSame($kept->id, $remaining->id);
    }

    /**
     * Редирект после удаления сохраняет фильтры, сортировку и страницу.
     */
    public function test_delete_redirect_preserves_filters_and_page(): void
    {
        $this->allowQueryDuplicates();
        $report = $this->insertDuplicate();

        $filters = [
            'q' => 'posts',
            'type' => 'duplicate',
            'method' => 'GET',
            'sort' => 'time_ms',
            'direction' => 'asc',
            'page' => '2',
        ];

        $this->delete(route('query-duplicates.destroy', ['queryDuplicate' => $report->id] + $filters))
            ->assertRedirect(route('query-duplicates.index', $filters))
            ->assertSessionHas('status', trans('query-duplicates::query-duplicates.deleted'));

        $this->assertDuplicateCount(0);
    }

    /**
     * Без Gate админка возвращает 403 и не удаляет запись.
     */
    public function test_delete_requires_the_gate(): void
    {
        $report = $this->insertDuplicate();

        $this->delete(route('query-duplicates.destroy', ['queryDuplicate' => $report->id]))
            ->assertForbidden();

        $this->assertDuplicateCount(1);
    }

    /**
     * При недоступном SQLite редирект не показывает ложное сообщение об успехе.
     */
    public function test_delete_without_available_storage_redirects_without_success_message(): void
    {
        $this->allowQueryDuplicates();
        $report = $this->insertDuplicate();
        $this->makeStorageUnavailable();

        $this->delete(route('query-duplicates.destroy', ['queryDuplicate' => $report->id]))
            ->assertRedirect(route('query-duplicates.index'))
            ->assertSessionMissing('status');

        $this->assertDuplicateCount(1);
    }

    /**
     * В каждой строке таблицы есть кнопка удаления, сохраняющая фильтры.
     */
    public function test_table_row_contains_delete_button_that_keeps_filters(): void
    {
        $this->allowQueryDuplicates();
        $report = $this->insertDuplicate();

        $filters = [
            'q' => 'posts',
            'sort' => 'time_ms',
            'direction' => 'asc',
        ];

        $this->get(route('query-duplicates.index', $filters))
            ->assertOk()
            ->assertSee(route('query-duplicates.destroy', ['queryDuplicate' => $report->id] + $filters))
            ->assertSee(trans('query-duplicates::query-duplicates.delete'));
    }

    /**
     * Подменяет хранилище пакета на недоступное.
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
