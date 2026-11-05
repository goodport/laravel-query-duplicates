<?php

namespace Goodport\QueryDuplicates\Http;

use Closure;
use Goodport\QueryDuplicates\Aggregator\DuplicateQueryAggregator;
use Goodport\QueryDuplicates\Logging\DuplicateQueryWriter;
use Goodport\QueryDuplicates\Logging\QueryRequestContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TrackDuplicateQueries
{
    public function __construct(
        private readonly DuplicateQueryAggregator $aggregator,
        private readonly DuplicateQueryWriter $writer,
    ) {}

    /**
     * Starts collection for the duration of the HTTP request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->aggregator->start();

        try {
            return $next($request);
        } finally {
            $this->aggregator->stop();
        }
    }

    /**
     * Writes the report and resets the collection area.
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            $requestContext = new QueryRequestContext(
                method: $request->getMethod(),
                path: $request->path(),
                route: $request->route()?->getName(),
            );

            foreach ($this->aggregator->report() as $group) {
                $this->writer->write($requestContext, $group);
            }
        } finally {
            $this->aggregator->reset();
        }
    }
}
