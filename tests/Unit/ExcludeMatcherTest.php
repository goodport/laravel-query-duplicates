<?php

namespace Goodport\QueryDuplicates\Tests\Unit;

use Goodport\QueryDuplicates\Config\ExclusionRule;
use Goodport\QueryDuplicates\Support\ExcludeMatcher;
use PHPUnit\Framework\TestCase;

final class ExcludeMatcherTest extends TestCase
{
    private const EVENT_QUERY = 'select * from `example_events` where `category` = ? and `owner_type` = ?';

    /**
     * A query-only substring excludes the query on every route.
     */
    public function test_query_only_rule_excludes_query_everywhere(): void
    {
        $matcher = new ExcludeMatcher([
            new ExclusionRule(route: null, sql: 'from `sessions`', bindings: []),
        ]);

        $this->assertTrue($matcher->matches(null, 'select * from `sessions` where user_id = ?'));
        $this->assertTrue($matcher->matches('users.index', 'select * from `sessions` where user_id = ?'));
        $this->assertFalse($matcher->matches('users.index', 'select * from `users` where id = ?'));
    }

    /**
     * A route-only rule excludes every query on matching routes.
     */
    public function test_route_only_rule_excludes_all_queries_on_matching_routes(): void
    {
        $matcher = new ExcludeMatcher([
            new ExclusionRule(route: 'admin.*', sql: null, bindings: []),
        ]);

        $this->assertTrue($matcher->matches('admin.users.index', 'select 1'));
        $this->assertTrue($matcher->matches('admin.users.index', 'select 2'));
        $this->assertFalse($matcher->matches('users.index', 'select 1'));
        $this->assertFalse($matcher->matches(null, 'select 1'));
    }

    /**
     * A rule with route and query applies only when both match.
     */
    public function test_route_and_query_rule_is_scoped_to_matching_route(): void
    {
        $matcher = new ExcludeMatcher([
            new ExclusionRule(
                route: 'posts.*',
                sql: 'from `example_events`',
                bindings: [0 => 'security'],
            ),
        ]);

        $bindings = ['security', 'App\\Example\\Widget'];

        $this->assertTrue($matcher->matches('posts.index', self::EVENT_QUERY, $bindings));
        $this->assertFalse($matcher->matches('users.index', self::EVENT_QUERY, $bindings));
        $this->assertFalse($matcher->matches('posts.index', self::EVENT_QUERY, ['other', 'App\\Example\\Widget']));
        $this->assertFalse($matcher->matches('posts.index', 'select * from `users`', []));
    }

    /**
     * A query-only rule with bindings excludes matching queries everywhere.
     */
    public function test_query_only_rule_with_bindings_matches_everywhere(): void
    {
        $matcher = new ExcludeMatcher([
            new ExclusionRule(route: null, sql: 'from `example_events`', bindings: [0 => 'security']),
        ]);

        $this->assertTrue($matcher->matches(null, self::EVENT_QUERY, ['security']));
        $this->assertTrue($matcher->matches('users.index', self::EVENT_QUERY, ['security']));
        $this->assertFalse($matcher->matches('users.index', self::EVENT_QUERY, ['other']));
    }

    /**
     * A route-only rule can exclude the whole route.
     */
    public function test_route_only_rule_excludes_whole_route(): void
    {
        $matcher = new ExcludeMatcher([
            new ExclusionRule(route: 'admin.*', sql: null, bindings: []),
        ]);

        $this->assertTrue($matcher->excludesRoute('admin.users.index'));
        $this->assertFalse($matcher->excludesRoute('users.index'));
        $this->assertFalse($matcher->excludesRoute(null));
    }

    /**
     * A scoped or query-only rule does not exclude the whole route.
     */
    public function test_scoped_and_query_only_rules_do_not_exclude_whole_route(): void
    {
        $matcher = new ExcludeMatcher([
            new ExclusionRule(route: 'posts.*', sql: 'from `example_events`', bindings: []),
            new ExclusionRule(route: null, sql: 'from `sessions`', bindings: []),
        ]);

        $this->assertFalse($matcher->excludesRoute('posts.index'));
        $this->assertFalse($matcher->excludesRoute('users.index'));
    }

    /**
     * An empty rule list excludes nothing.
     */
    public function test_empty_rule_list_excludes_nothing(): void
    {
        $matcher = new ExcludeMatcher([]);

        $this->assertFalse($matcher->matches(null, 'select 1'));
    }

    /**
     * A query rule with empty bindings matches by SQL only.
     */
    public function test_query_rule_with_empty_bindings_matches_by_sql_only(): void
    {
        $matcher = new ExcludeMatcher([
            new ExclusionRule(
                route: null,
                sql: 'select * from `example_events`',
                bindings: [],
            ),
        ]);

        $this->assertTrue($matcher->matches(
            null,
            'select * from `example_events` where `category` = ?',
            ['security'],
        ));
    }

    /**
     * Several rules are combined with OR.
     */
    public function test_rules_are_combined_with_or(): void
    {
        $matcher = new ExcludeMatcher([
            new ExclusionRule(route: 'admin.*', sql: null, bindings: []),
            new ExclusionRule(route: null, sql: 'from `sessions`', bindings: []),
            new ExclusionRule(route: 'posts.*', sql: 'from `example_events`', bindings: [0 => 'security']),
        ]);

        $this->assertTrue($matcher->matches('admin.users.index', 'select 1'));
        $this->assertTrue($matcher->matches('users.index', 'select * from `sessions`'));
        $this->assertTrue($matcher->matches('posts.index', self::EVENT_QUERY, ['security']));
        $this->assertFalse($matcher->matches('users.index', 'select * from `users`'));
    }

    /**
     * Binding values are compared strictly.
     */
    public function test_binding_values_are_compared_strictly(): void
    {
        $matcher = new ExcludeMatcher([
            new ExclusionRule(route: null, sql: 'from `example_events`', bindings: [0 => 1]),
        ]);

        $this->assertTrue($matcher->matches(null, 'select * from `example_events` where `id` = ?', [1]));
        $this->assertFalse($matcher->matches(null, 'select * from `example_events` where `id` = ?', ['1']));
    }

    /**
     * A missing binding position does not match.
     */
    public function test_missing_binding_position_does_not_match(): void
    {
        $matcher = new ExcludeMatcher([
            new ExclusionRule(
                route: null,
                sql: self::EVENT_QUERY,
                bindings: [0 => 'security', 1 => 'App\\Example\\Widget'],
            ),
        ]);

        $this->assertFalse($matcher->matches(null, self::EVENT_QUERY, ['security']));
    }

    /**
     * Bindings outside the rule are not compared.
     */
    public function test_unlisted_bindings_are_ignored(): void
    {
        $matcher = new ExcludeMatcher([
            new ExclusionRule(route: null, sql: 'from `example_events`', bindings: [0 => 'security']),
        ]);

        $this->assertTrue($matcher->matches(
            null,
            'select * from `example_events` where `category` = ?',
            ['security', 42],
        ));
    }
}
