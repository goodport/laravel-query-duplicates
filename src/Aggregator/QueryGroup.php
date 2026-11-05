<?php

namespace Goodport\QueryDuplicates\Aggregator;

final class QueryGroup
{
    public const TYPE__N_PLUS_ONE = 'n+1';

    public const TYPE__DUPLICATE = 'duplicate';

    /**
     * Binding sets tracked per group. New sets beyond the limit are ignored so a
     * large loop cannot grow the map without bound.
     */
    private const MAX_BINDING_SETS = 50;

    public int $executions = 0;

    public float $totalTimeMs = 0.0;

    public int $maxIdenticalRepeats = 0;

    public ?string $type = null;

    /**
     * null while unknown, true for a relation query, false for any other query.
     */
    public ?bool $relationQuery = null;

    /**
     * @var array<string, int> Binding set fingerprint → execution count.
     */
    private array $bindingSetCounts = [];

    /**
     * @var array<string, int> Relation call site → execution count.
     */
    private array $relationCallsiteCounts = [];

    private bool $relationConfirmed = false;

    public function __construct(
        public readonly string $sql,
    ) {}

    /**
     * Records the next execution of the group.
     */
    public function addExecution(float $timeMs, string $bindingFingerprint): void
    {
        $this->executions++;
        $this->totalTimeMs += $timeMs;

        if (array_key_exists($bindingFingerprint, $this->bindingSetCounts)) {
            $this->bindingSetCounts[$bindingFingerprint]++;
            $this->maxIdenticalRepeats = max(
                $this->maxIdenticalRepeats,
                $this->bindingSetCounts[$bindingFingerprint],
            );

            return;
        }

        if (count($this->bindingSetCounts) < self::MAX_BINDING_SETS) {
            $this->bindingSetCounts[$bindingFingerprint] = 1;
            $this->maxIdenticalRepeats = max($this->maxIdenticalRepeats, 1);
        }
    }

    /**
     * Records a lazy relation execution and confirms N+1 once one call site repeats.
     */
    public function recordRelationExecution(string $callsite, int $threshold): void
    {
        if ($this->relationConfirmed) {
            return;
        }

        $count = ($this->relationCallsiteCounts[$callsite] ?? 0) + 1;
        $this->relationCallsiteCounts[$callsite] = $count;

        if ($count >= $threshold) {
            $this->relationConfirmed = true;
        }
    }

    /**
     * Whether a relation was loaded repeatedly from the same place.
     */
    public function isRelationConfirmed(): bool
    {
        return $this->relationConfirmed;
    }

    /**
     * Assigns the report type. A group is reported only once.
     */
    public function markAs(string $type): void
    {
        $this->type = $type;
    }
}
