<?php

namespace Goodport\QueryDuplicates\Tests\Feature\Admin;

use Goodport\QueryDuplicates\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

final class MissingTableTest extends TestCase
{
    /**
     * A missing table shows the migration hint instead of an error page.
     */
    public function test_missing_table_shows_migration_hint(): void
    {
        $this->allowQueryDuplicates();

        Schema::connection(config('query-duplicates.connection'))->drop('query_duplicates');

        $this->get(route('query-duplicates.index'))
            ->assertOk()
            ->assertSee(trans('query-duplicates::query-duplicates.missing_table'));
    }
}
