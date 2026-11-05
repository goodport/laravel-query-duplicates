<?php

namespace Goodport\QueryDuplicates\Tests\Unit;

use Goodport\QueryDuplicates\Support\ExcludeMatcher;
use PHPUnit\Framework\TestCase;

final class ExcludeMatcherTest extends TestCase
{
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
}
