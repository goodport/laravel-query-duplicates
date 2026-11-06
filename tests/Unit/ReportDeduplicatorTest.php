<?php

namespace Goodport\QueryDuplicates\Tests\Unit;

use Goodport\QueryDuplicates\Aggregator\QueryGroup;
use Goodport\QueryDuplicates\Logging\QueryRequestContext;
use Goodport\QueryDuplicates\Logging\ReportDeduplicator;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;

final class ReportDeduplicatorTest extends TestCase
{
    /**
     * An identical report is considered written only once.
     */
    public function test_identical_report_is_written_once(): void
    {
        $deduplicator = $this->makeDeduplicator();
        $context = $this->requestContext();
        $group = $this->group('select ? as id');

        $this->assertTrue($deduplicator->shouldWrite($context, $group));
        $this->assertFalse($deduplicator->shouldWrite($context, $group));
    }

    /**
     * Reports with different SQL are written separately.
     */
    public function test_report_with_different_sql_is_written(): void
    {
        $deduplicator = $this->makeDeduplicator();
        $context = $this->requestContext();

        $this->assertTrue($deduplicator->shouldWrite($context, $this->group('select ? as id')));
        $this->assertTrue($deduplicator->shouldWrite($context, $this->group('select ? as name')));
    }

    /**
     * Reports with different types are written separately.
     */
    public function test_report_with_different_type_is_written(): void
    {
        $deduplicator = $this->makeDeduplicator();
        $context = $this->requestContext();

        $duplicate = $this->group('select ? as id');
        $duplicate->markAs(QueryGroup::TYPE__DUPLICATE);

        $nPlusOne = $this->group('select ? as id');
        $nPlusOne->markAs(QueryGroup::TYPE__N_PLUS_ONE);

        $this->assertTrue($deduplicator->shouldWrite($context, $duplicate));
        $this->assertTrue($deduplicator->shouldWrite($context, $nPlusOne));
    }

    /**
     * Creates a deduplicator backed by an isolated in-memory cache.
     */
    private function makeDeduplicator(): ReportDeduplicator
    {
        return new ReportDeduplicator(new Repository(new ArrayStore));
    }

    /**
     * Creates a request context for a fictional endpoint.
     */
    private function requestContext(): QueryRequestContext
    {
        return new QueryRequestContext(
            method: 'GET',
            path: 'users/42/edit',
            route: 'users.edit',
        );
    }

    /**
     * Creates a report group with fictional SQL.
     */
    private function group(string $sql): QueryGroup
    {
        return new QueryGroup($sql);
    }
}
