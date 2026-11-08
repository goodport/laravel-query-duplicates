<?php

namespace Goodport\QueryDuplicates\Listener;

use Goodport\QueryDuplicates\Aggregator\DuplicateQueryAggregator;
use Goodport\QueryDuplicates\Support\ExcludeMatcher;
use Illuminate\Routing\Events\RouteMatched;

final class SetMatchedRoute
{
    public function __construct(
        private readonly DuplicateQueryAggregator $aggregator,
        private readonly ExcludeMatcher $excludeMatcher,
    ) {}

    /**
     * Gives the aggregator the route name and stops route-only exclusions early.
     */
    public function __invoke(RouteMatched $event): void
    {
        $routeName = $event->route->getName();

        $this->aggregator->setRouteName($routeName);

        if ($this->excludeMatcher->excludesRoute($routeName)) {
            $this->aggregator->reset();
        }
    }
}
