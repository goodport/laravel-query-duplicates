<?php

namespace Goodport\QueryDuplicates\Aggregator;

use Goodport\QueryDuplicates\Config\DuplicateQuerySettings;
use Goodport\QueryDuplicates\Support\ExcludeMatcher;
use Goodport\QueryDuplicates\Support\RelationQueryDetector;
use Illuminate\Database\Events\QueryExecuted;

final class DuplicateQueryAggregator
{
    /**
     * @var array<string, QueryGroup> Groups of the current collection area.
     */
    private array $groups = [];

    private bool $active = false;

    public function __construct(
        private readonly DuplicateQuerySettings $settings,
        private readonly ExcludeMatcher $excludeMatcher,
        private readonly RelationQueryDetector $relationDetector,
    ) {}

    /**
     * Starts a new collection area and resets the previous one.
     */
    public function start(): void
    {
        $this->reset();
        $this->active = true;
    }

    /**
     * Stops collection while keeping the collected data.
     */
    public function stop(): void
    {
        $this->active = false;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Resets the collection area completely.
     */
    public function reset(): void
    {
        $this->active = false;
        $this->groups = [];
    }

    /**
     * Records an executed SQL query when collection is active.
     */
    public function record(QueryExecuted $query): void
    {
        if (! $this->active) {
            return;
        }

        if ($this->excludeMatcher->matches($query->sql, $query->bindings)) {
            return;
        }

        $group = $this->groupFor($query);

        $group->addExecution(
            timeMs: (float) $query->time,
            bindingFingerprint: md5(serialize($query->bindings)),
        );

        $this->detectRelationExecution($group);
    }

    /**
     * Returns the reported groups sorted by query time.
     *
     * @return list<QueryGroup>
     */
    public function report(): array
    {
        $groups = [];

        foreach ($this->groups as $group) {
            $type = $this->typeFor($group);

            if ($type === null) {
                continue;
            }

            $group->markAs($type);
            $groups[] = $group;
        }

        usort(
            $groups,
            static function (QueryGroup $left, QueryGroup $right): int {
                $timeOrder = $right->totalTimeMs <=> $left->totalTimeMs;

                return $timeOrder !== 0
                    ? $timeOrder
                    : $right->executions <=> $left->executions;
            },
        );

        return $groups;
    }

    /**
     * Returns the existing group or creates a new one.
     */
    private function groupFor(QueryExecuted $query): QueryGroup
    {
        $groupKey = md5($query->sql).'@'.$query->connectionName;

        return $this->groups[$groupKey] ??= new QueryGroup(
            sql: $query->sql,
        );
    }

    /**
     * Marks a repeated query as a relation N+1 when one call site repeats.
     */
    private function detectRelationExecution(QueryGroup $group): void
    {
        if ($group->isRelationConfirmed() || $group->relationQuery === false) {
            return;
        }

        $callsite = $this->relationDetector->detect();

        if ($group->relationQuery === null) {
            $group->relationQuery = $callsite !== null;
        }

        if ($callsite === null) {
            return;
        }

        $group->recordRelationExecution($callsite, $this->settings->threshold);
    }

    /**
     * Returns the report type for a group or null when it is not reported.
     */
    private function typeFor(QueryGroup $group): ?string
    {
        if ($group->executions < $this->settings->threshold) {
            return null;
        }

        if ($group->isRelationConfirmed()) {
            return QueryGroup::TYPE__N_PLUS_ONE;
        }

        if ($this->settings->strict || $group->maxIdenticalRepeats >= $this->settings->threshold) {
            return QueryGroup::TYPE__DUPLICATE;
        }

        return null;
    }
}
