<?php

namespace Goodport\QueryDuplicates\Listener;

use Goodport\QueryDuplicates\Aggregator\DuplicateQueryAggregator;
use Illuminate\Database\Events\QueryExecuted;

final class RecordQuery
{
    public function __construct(
        private readonly DuplicateQueryAggregator $aggregator,
    ) {}

    /**
     * Passes the executed query to the aggregator.
     */
    public function __invoke(QueryExecuted $query): void
    {
        $this->aggregator->record($query);
    }
}
