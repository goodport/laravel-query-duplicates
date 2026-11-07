<?php

namespace Goodport\QueryDuplicates\Tests\Feature\Admin;

use Goodport\QueryDuplicates\Aggregator\QueryGroup;
use Goodport\QueryDuplicates\Repository\QueryDuplicateRepository;
use Goodport\QueryDuplicates\Tests\TestCase;

final class IndexTest extends TestCase
{
    /**
     * Without the gate, the page is forbidden.
     */
    public function test_page_is_forbidden_without_gate(): void
    {
        $this->get(route('query-duplicates.index'))->assertForbidden();
    }

    /**
     * With the gate, the page lists stored reports.
     */
    public function test_page_lists_reports_with_gate(): void
    {
        $this->allowQueryDuplicates();
        $this->insertDuplicate(['route' => 'posts.index']);

        $this->get(route('query-duplicates.index'))
            ->assertOk()
            ->assertSee('posts.index');
    }

    /**
     * The page shows the active SQLite storage path.
     */
    public function test_page_shows_active_storage_path(): void
    {
        $this->allowQueryDuplicates();

        $this->get(route('query-duplicates.index'))
            ->assertOk()
            ->assertSee(trans('query-duplicates::query-duplicates.storage_path', [
                'path' => (string) config('query-duplicates.storage_path'),
            ]));
    }

    /**
     * Search matches route, path and SQL case-insensitively.
     */
    public function test_search_matches_route_path_and_sql(): void
    {
        $this->allowQueryDuplicates();

        $this->insertDuplicate([
            'route' => 'posts.index',
            'path' => 'posts',
            'sql' => 'select * from `example_posts`',
        ]);
        $this->insertDuplicate([
            'route' => 'users.index',
            'path' => 'users/42',
            'sql' => 'select * from `example_users`',
        ]);

        $this->get(route('query-duplicates.index', ['q' => 'POSTS.INDEX']))
            ->assertOk()
            ->assertSee('posts.index')
            ->assertDontSee('users.index');

        $this->get(route('query-duplicates.index', ['q' => 'USERS/42']))
            ->assertOk()
            ->assertSee('users.index')
            ->assertDontSee('posts.index');

        $this->get(route('query-duplicates.index', ['q' => 'EXAMPLE_POSTS']))
            ->assertOk()
            ->assertSee('posts.index')
            ->assertDontSee('users.index');
    }

    /**
     * The type filter keeps only the selected report type.
     */
    public function test_type_filter_keeps_only_selected_type(): void
    {
        $this->allowQueryDuplicates();

        $this->insertDuplicate([
            'route' => 'posts.index',
            'type' => QueryGroup::TYPE__DUPLICATE,
        ]);
        $this->insertDuplicate([
            'route' => 'users.index',
            'type' => QueryGroup::TYPE__N_PLUS_ONE,
        ]);

        $this->get(route('query-duplicates.index', ['type' => QueryGroup::TYPE__N_PLUS_ONE]))
            ->assertOk()
            ->assertSee('users.index')
            ->assertDontSee('posts.index');
    }

    /**
     * The method filter keeps only the selected HTTP method.
     */
    public function test_method_filter_keeps_only_selected_method(): void
    {
        $this->allowQueryDuplicates();

        $this->insertDuplicate([
            'route' => 'posts.store',
            'method' => 'POST',
        ]);
        $this->insertDuplicate([
            'route' => 'posts.index',
            'method' => 'GET',
        ]);

        $this->get(route('query-duplicates.index', ['method' => 'POST']))
            ->assertOk()
            ->assertSee('posts.store')
            ->assertDontSee('posts.index');
    }

    /**
     * Every documented sort column orders the rows.
     */
    public function test_sorting_by_documented_columns(): void
    {
        $this->allowQueryDuplicates();
        $this->insertSortingRows();

        $this->get(route('query-duplicates.index', ['sort' => 'query_count', 'direction' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['fast.index', 'middle.index', 'slow.index']);

        $this->get(route('query-duplicates.index', ['sort' => 'method', 'direction' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['fast.index', 'slow.index', 'middle.index']);

        $this->get(route('query-duplicates.index', ['sort' => 'method', 'direction' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['middle.index', 'slow.index', 'fast.index']);

        $this->get(route('query-duplicates.index', ['sort' => 'time_ms', 'direction' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['slow.index', 'middle.index', 'fast.index']);

        $this->get(route('query-duplicates.index'))
            ->assertOk()
            ->assertSeeInOrder(['slow.index', 'middle.index', 'fast.index']);
    }

    /**
     * An unknown sort column falls back to the default order without an error.
     */
    public function test_unknown_sort_column_is_ignored(): void
    {
        $this->allowQueryDuplicates();
        $this->insertSortingRows();

        $this->get(route('query-duplicates.index', ['sort' => 'unexpected_column']))
            ->assertOk()
            ->assertSeeInOrder(['slow.index', 'middle.index', 'fast.index']);
    }

    /**
     * The copy context is fully escaped and contains the documented keys.
     */
    public function test_copy_context_is_escaped_and_has_documented_keys(): void
    {
        $this->allowQueryDuplicates();
        $row = $this->insertDuplicate();

        $response = $this->get(route('query-duplicates.index'))->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringContainsString('&quot;method&quot;', $html);
        $this->assertSame(1, preg_match('/data-context="([^"]*)"/', $html, $matches));

        $context = json_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), true);

        $this->assertSame($row->context(), $context);
    }

    /**
     * The page shows the configured number of reports and the rest on the next page.
     */
    public function test_pagination_shows_configured_rows_per_page(): void
    {
        $this->allowQueryDuplicates();

        for ($index = 0; $index <= QueryDuplicateRepository::PER_PAGE; $index++) {
            $this->insertDuplicate([
                'route' => 'page.row.'.$index,
                'query_count' => $index,
                'updated_at' => now()->addSeconds($index),
            ]);
        }

        $lastRow = 'page.row.'.QueryDuplicateRepository::PER_PAGE;

        $this->get(route('query-duplicates.index'))
            ->assertOk()
            ->assertSee($lastRow)
            ->assertDontSee('page.row.0');

        $this->get(route('query-duplicates.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('page.row.0')
            ->assertDontSee($lastRow);
    }

    /**
     * Dangerous report values stay escaped and the copy context is still valid JSON.
     */
    public function test_dangerous_values_are_escaped_and_context_stays_valid_json(): void
    {
        $this->allowQueryDuplicates();

        $payload = "<script>alert('x')</script>";
        $row = $this->insertDuplicate([
            'route' => $payload,
            'path' => "posts/\"'&<",
            'sql' => "select '<b>&' as \"value\"",
        ]);

        $response = $this->get(route('query-duplicates.index'))->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringNotContainsString($payload, $html);
        $this->assertStringNotContainsString('<b>&', $html);
        $this->assertSame(1, preg_match('/data-context="([^"]*)"/', $html, $matches));

        $decodedContext = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);

        $this->assertSame(
            $row->context(),
            json_decode($decodedContext, true, 512, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * Inserts rows with distinct sort values.
     */
    private function insertSortingRows(): void
    {
        $this->insertDuplicate([
            'route' => 'slow.index',
            'method' => 'POST',
            'query_count' => 5,
            'time_ms' => 9.5,
            'updated_at' => now()->subMinutes(2),
        ]);
        $this->insertDuplicate([
            'route' => 'fast.index',
            'method' => 'GET',
            'query_count' => 2,
            'time_ms' => 1.0,
            'updated_at' => now()->subMinute(),
        ]);
        $this->insertDuplicate([
            'route' => 'middle.index',
            'method' => 'PUT',
            'query_count' => 3,
            'time_ms' => 4.0,
            'updated_at' => now(),
        ]);
    }
}
