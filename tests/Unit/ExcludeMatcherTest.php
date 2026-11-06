<?php

namespace Goodport\QueryDuplicates\Tests\Unit;

use Goodport\QueryDuplicates\Support\ExcludeMatcher;
use PHPUnit\Framework\TestCase;

final class ExcludeMatcherTest extends TestCase
{
    private const EVENT_QUERY = 'select * from `example_events` where `category` = ? and `owner_type` = ?';

    /**
     * A substring excludes matching SQL.
     */
    public function test_substring_excludes_matching_query(): void
    {
        $matcher = new ExcludeMatcher(['from `sessions`']);

        $this->assertTrue($matcher->matches('select * from `sessions` where user_id = ?'));
        $this->assertFalse($matcher->matches('select * from `users` where id = ?'));
    }

    /**
     * An empty list excludes nothing.
     */
    public function test_empty_list_does_not_exclude_anything(): void
    {
        $matcher = new ExcludeMatcher([]);

        $this->assertFalse($matcher->matches('select 1'));
    }

    /**
     * A rule matches the SQL and every listed binding.
     */
    public function test_rule_matches_sql_and_listed_bindings(): void
    {
        $matcher = $this->matcher();

        $this->assertTrue($matcher->matches(
            self::EVENT_QUERY,
            ['security', 'App\\Example\\Widget'],
        ));
    }

    /**
     * A different value at a listed position does not match.
     */
    public function test_rule_does_not_match_different_binding_value(): void
    {
        $matcher = $this->matcher();

        $this->assertFalse($matcher->matches(
            self::EVENT_QUERY,
            ['other', 'App\\Example\\Widget'],
        ));
    }

    /**
     * A missing binding position does not match.
     */
    public function test_rule_does_not_match_missing_binding_position(): void
    {
        $matcher = $this->matcher();

        $this->assertFalse($matcher->matches(
            self::EVENT_QUERY,
            ['security'],
        ));
    }

    /**
     * Bindings outside the rule are not compared.
     */
    public function test_rule_ignores_unlisted_bindings(): void
    {
        $matcher = $this->matcher();

        $this->assertTrue($matcher->matches(
            self::EVENT_QUERY,
            ['security', 'App\\Example\\Widget', 42],
        ));
    }

    /**
     * An empty bindings map excludes only by SQL.
     */
    public function test_rule_with_empty_bindings_matches_only_by_sql(): void
    {
        $matcher = new ExcludeMatcher([
            [
                'sql' => 'select * from `example_events`',
                'bindings' => [],
            ],
        ]);

        $this->assertTrue($matcher->matches(
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
            ['sql' => 'from `sessions`', 'bindings' => []],
            ['sql' => 'from `example_events`', 'bindings' => [0 => 'security']],
        ]);

        $this->assertTrue($matcher->matches('select * from `sessions`'));
        $this->assertTrue($matcher->matches('select * from `example_events` where `category` = ?', ['security']));
        $this->assertFalse($matcher->matches('select * from `example_events` where `category` = ?', ['other']));
    }

    /**
     * Binding values are compared strictly.
     */
    public function test_binding_values_are_compared_strictly(): void
    {
        $matcher = new ExcludeMatcher([
            ['sql' => 'from `example_events`', 'bindings' => [0 => 1]],
        ]);

        $this->assertTrue($matcher->matches('select * from `example_events` where `id` = ?', [1]));
        $this->assertFalse($matcher->matches('select * from `example_events` where `id` = ?', ['1']));
    }

    /**
     * Returns a matcher with a partial bindings rule.
     */
    private function matcher(): ExcludeMatcher
    {
        return new ExcludeMatcher([
            [
                'sql' => self::EVENT_QUERY,
                'bindings' => [
                    0 => 'security',
                    1 => 'App\\Example\\Widget',
                ],
            ],
        ]);
    }
}
