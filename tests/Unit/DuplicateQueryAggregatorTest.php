<?php

namespace Goodport\QueryDuplicates\Tests\Unit;

use Goodport\QueryDuplicates\Aggregator\DuplicateQueryAggregator;
use Goodport\QueryDuplicates\Aggregator\QueryGroup;
use Goodport\QueryDuplicates\Config\DuplicateQuerySettings;
use Goodport\QueryDuplicates\Config\ExclusionSettings;
use Goodport\QueryDuplicates\Config\MethodGroupSettings;
use Goodport\QueryDuplicates\Support\ExcludeMatcher;
use Goodport\QueryDuplicates\Support\RelationQueryDetector;
use Goodport\QueryDuplicates\Tests\Support\FakeQueryConnection;
use Illuminate\Database\Events\QueryExecuted;
use PHPUnit\Framework\TestCase;

final class DuplicateQueryAggregatorTest extends TestCase
{
    private const USER_RELATION_CALLSITE = 'app/Http/Resources/UserResource.php:42';

    private const ORDER_RELATION_CALLSITE = 'app/Http/Resources/OrderResource.php:17';

    private FakeQueryConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = new FakeQueryConnection('testing');
    }

    /**
     * The same SQL with the same bindings is a duplicate.
     */
    public function test_identical_repeats_are_duplicates(): void
    {
        $aggregator = $this->makeAggregator();
        $aggregator->start(2);

        $this->recordRepeated($aggregator, 'select ? as id', [7], 3);

        $groups = $aggregator->report();

        $this->assertCount(1, $groups);
        $this->assertSame(3, $groups[0]->executions);
        $this->assertSame(QueryGroup::TYPE__DUPLICATE, $groups[0]->type);
    }

    /**
     * The same SQL with different bindings is not a duplicate while strict mode is off.
     */
    public function test_different_bindings_are_not_duplicates_by_default(): void
    {
        $aggregator = $this->makeAggregator();
        $aggregator->start(2);

        $this->recordDistinct($aggregator, 'select ? as id', [1, 2, 3]);

        $this->assertSame([], $aggregator->report());
    }

    /**
     * Strict mode reports the same SQL regardless of bindings.
     */
    public function test_strict_mode_reports_different_bindings(): void
    {
        $aggregator = $this->makeAggregator(strict: true);
        $aggregator->start(2);

        $this->recordDistinct($aggregator, 'select ? as id', [1, 2, 3]);

        $groups = $aggregator->report();

        $this->assertCount(1, $groups);
        $this->assertSame(QueryGroup::TYPE__DUPLICATE, $groups[0]->type);
    }

    /**
     * A single execution is never reported.
     */
    public function test_single_execution_is_never_reported(): void
    {
        $aggregator = $this->makeAggregator();
        $aggregator->start(2);

        $aggregator->record($this->query('select 1 as single'));

        $this->assertSame([], $aggregator->report());
    }

    /**
     * A duplicate below the active threshold is not reported.
     */
    public function test_duplicate_below_threshold_is_not_reported(): void
    {
        $aggregator = $this->makeAggregator();
        $aggregator->start(3);

        $this->recordRepeated($aggregator, 'select ? as id', [7], 2);

        $this->assertSame([], $aggregator->report());
    }

    /**
     * A duplicate exactly at the active threshold is reported.
     */
    public function test_duplicate_at_threshold_is_reported(): void
    {
        $aggregator = $this->makeAggregator();
        $aggregator->start(3);

        $this->recordRepeated($aggregator, 'select ? as id', [7], 3);

        $groups = $aggregator->report();

        $this->assertCount(1, $groups);
        $this->assertSame(QueryGroup::TYPE__DUPLICATE, $groups[0]->type);
    }

    /**
     * A relation query repeated from the same place is an N+1.
     */
    public function test_relation_repeated_from_one_callsite_is_n_plus_one(): void
    {
        $aggregator = $this->makeAggregator(relationCallsites: [self::USER_RELATION_CALLSITE]);
        $aggregator->start(3);

        $this->recordDistinct($aggregator, 'select * from `posts` where `user_id` = ?', [1, 2, 3]);

        $groups = $aggregator->report();

        $this->assertCount(1, $groups);
        $this->assertSame(QueryGroup::TYPE__N_PLUS_ONE, $groups[0]->type);
    }

    /**
     * A relation query with identical bindings is still an N+1, not a duplicate.
     */
    public function test_relation_with_identical_bindings_is_n_plus_one(): void
    {
        $aggregator = $this->makeAggregator(relationCallsites: [self::USER_RELATION_CALLSITE]);
        $aggregator->start(2);

        $this->recordRepeated($aggregator, 'select * from `posts`', [], 2);

        $groups = $aggregator->report();

        $this->assertCount(1, $groups);
        $this->assertSame(QueryGroup::TYPE__N_PLUS_ONE, $groups[0]->type);
    }

    /**
     * Relation queries from different places are not an N+1.
     */
    public function test_relation_from_different_callsites_is_not_n_plus_one(): void
    {
        $aggregator = $this->makeAggregator(relationCallsites: [
            self::USER_RELATION_CALLSITE,
            self::ORDER_RELATION_CALLSITE,
        ]);
        $aggregator->start(2);

        $this->recordDistinct($aggregator, 'select * from `posts` where `user_id` = ?', [1, 2]);

        $this->assertSame([], $aggregator->report());
    }

    /**
     * A relation below the active threshold is not reported.
     */
    public function test_relation_below_threshold_is_not_reported(): void
    {
        $aggregator = $this->makeAggregator(relationCallsites: [self::USER_RELATION_CALLSITE]);
        $aggregator->start(3);

        $this->recordDistinct($aggregator, 'select * from `posts` where `user_id` = ?', [1, 2]);

        $this->assertSame([], $aggregator->report());
    }

    /**
     * The report is sorted by total time and then by execution count.
     */
    public function test_report_is_sorted_by_total_time_then_executions(): void
    {
        $aggregator = $this->makeAggregator();
        $aggregator->start(2);

        $this->recordRepeated($aggregator, 'select 1 as slow', [], 2, 3.0);
        $this->recordRepeated($aggregator, 'select 1 as fast-many', [], 4, 1.0);
        $this->recordRepeated($aggregator, 'select 1 as fast-few', [], 2, 2.0);

        $groups = $aggregator->report();

        $this->assertSame('select 1 as slow', $groups[0]->sql);
        $this->assertSame('select 1 as fast-many', $groups[1]->sql);
        $this->assertSame('select 1 as fast-few', $groups[2]->sql);
    }

    /**
     * Excluded queries are ignored before a group is created.
     */
    public function test_excluded_queries_are_ignored(): void
    {
        $aggregator = $this->makeAggregator(
            excludeMatcher: new ExcludeMatcher(['as sessions']),
        );
        $aggregator->start(2);

        foreach (['select 1 as sessions', 'select 2 as kept'] as $sql) {
            $this->recordRepeated($aggregator, $sql, [], 2);
        }

        $groups = $aggregator->report();

        $this->assertCount(1, $groups);
        $this->assertSame('select 2 as kept', $groups[0]->sql);
    }

    /**
     * start() begins a new area and does not mix data with the previous one.
     */
    public function test_start_resets_previous_area(): void
    {
        $aggregator = $this->makeAggregator();
        $aggregator->start(2);
        $this->recordRepeated($aggregator, 'select 1 as previous', [], 2);

        $aggregator->start(2);
        $this->recordRepeated($aggregator, 'select 2 as current', [], 2);

        $groups = $aggregator->report();

        $this->assertCount(1, $groups);
        $this->assertSame('select 2 as current', $groups[0]->sql);
    }

    /**
     * Queries outside the active area are ignored.
     */
    public function test_inactive_aggregator_ignores_queries(): void
    {
        $aggregator = $this->makeAggregator();

        $this->recordRepeated($aggregator, 'select 1 as ignored', [], 2);

        $this->assertSame([], $aggregator->report());
    }

    /**
     * @param  list<string>  $relationCallsites
     */
    private function makeAggregator(
        ?ExcludeMatcher $excludeMatcher = null,
        bool $strict = false,
        array $relationCallsites = [],
    ): DuplicateQueryAggregator {
        return new DuplicateQueryAggregator(
            settings: new DuplicateQuerySettings(
                enabled: true,
                strict: $strict,
                getGroup: MethodGroupSettings::getDefaults(),
                otherGroup: MethodGroupSettings::otherDefaults(),
                exclusions: new ExclusionSettings([], []),
            ),
            excludeMatcher: $excludeMatcher ?? new ExcludeMatcher([]),
            relationDetector: $this->makeDetector($relationCallsites),
        );
    }

    /**
     * @param  list<string>  $callsites
     */
    private function makeDetector(array $callsites): RelationQueryDetector
    {
        return new class($callsites) implements RelationQueryDetector
        {
            /**
             * @param  list<string>  $callsites
             */
            public function __construct(
                private array $callsites,
            ) {}

            public function detect(): ?string
            {
                if ($this->callsites === []) {
                    return null;
                }

                return count($this->callsites) === 1
                    ? $this->callsites[0]
                    : array_shift($this->callsites);
            }
        };
    }

    /**
     * Records the same query and bindings several times.
     *
     * @param  array<int, mixed>  $bindings
     */
    private function recordRepeated(
        DuplicateQueryAggregator $aggregator,
        string $sql,
        array $bindings,
        int $times,
        float $time = 1.0,
    ): void {
        for ($execution = 0; $execution < $times; $execution++) {
            $aggregator->record($this->query($sql, $bindings, time: $time));
        }
    }

    /**
     * Records the same SQL with a different binding for each value.
     *
     * @param  array<int, mixed>  $values
     */
    private function recordDistinct(
        DuplicateQueryAggregator $aggregator,
        string $sql,
        array $values,
    ): void {
        foreach ($values as $value) {
            $aggregator->record($this->query($sql, [$value]));
        }
    }

    /**
     * @param  array<int, mixed>  $bindings
     */
    private function query(
        string $sql,
        array $bindings = [],
        float $time = 1.0,
    ): QueryExecuted {
        return new QueryExecuted($sql, $bindings, $time, $this->connection);
    }
}
