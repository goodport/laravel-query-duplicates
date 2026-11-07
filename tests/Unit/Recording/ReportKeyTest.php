<?php

namespace Goodport\QueryDuplicates\Tests\Unit\Recording;

use Goodport\QueryDuplicates\Aggregator\QueryGroup;
use Goodport\QueryDuplicates\Recording\QueryRequestContext;
use Goodport\QueryDuplicates\Recording\ReportKey;
use PHPUnit\Framework\TestCase;

final class ReportKeyTest extends TestCase
{
    /**
     * Different numeric path segments and SQL literals produce the same key.
     */
    public function test_different_ids_produce_same_key(): void
    {
        $first = $this->key(
            path: 'users/27',
            route: null,
            sql: "select * from `example_users` where `id` = 27 and `email` = 'first@example.com'",
        );
        $second = $this->key(
            path: 'users/9',
            route: null,
            sql: "select * from `example_users` where `id` = 9 and `email` = 'second@example.com'",
        );

        $this->assertSame($first, $second);
    }

    /**
     * Different SQL statements produce different keys.
     */
    public function test_different_sql_produces_different_key(): void
    {
        $this->assertNotSame(
            $this->key(sql: 'select * from `example_posts` where `id` = 27'),
            $this->key(sql: 'select * from `example_pages` where `id` = 27'),
        );
    }

    /**
     * Different route names produce different keys.
     */
    public function test_different_route_produces_different_key(): void
    {
        $this->assertNotSame(
            $this->key(route: 'posts.index'),
            $this->key(route: 'pages.index'),
        );
    }

    /**
     * Different report types produce different keys.
     */
    public function test_different_type_produces_different_key(): void
    {
        $this->assertNotSame(
            $this->key(type: QueryGroup::TYPE__DUPLICATE),
            $this->key(type: QueryGroup::TYPE__N_PLUS_ONE),
        );
    }

    /**
     * Numeric and UUID path segments are replaced with placeholders.
     */
    public function test_path_is_normalized(): void
    {
        $this->assertSame(
            'users/{id}/files/{uuid}',
            ReportKey::normalizePath('users/42/files/9b6c6c1e-1a2b-4c3d-8e4f-5a6b7c8d9e0f'),
        );
    }

    /**
     * String and numeric SQL literals are replaced and the SQL case is kept.
     */
    public function test_sql_is_normalized(): void
    {
        $this->assertSame(
            'SELECT * FROM `example_users` WHERE `id` = ? AND `email` = ? AND `score` > ?',
            ReportKey::normalizeSql(
                "SELECT * FROM `example_users` WHERE `id` = 42 AND `email` = 'first@example.com' AND `score` > 3.5",
            ),
        );
    }

    /**
     * Builds a report key for the given identity parts.
     */
    private function key(
        string $path = 'posts/42',
        ?string $route = 'posts.show',
        string $sql = 'select * from `example_posts` where `id` = 42',
        string $type = QueryGroup::TYPE__DUPLICATE,
    ): string {
        $group = new QueryGroup($sql);
        $group->markAs($type);

        return ReportKey::for(
            new QueryRequestContext('GET', $path, $route),
            $group,
        );
    }
}
