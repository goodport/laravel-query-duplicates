<?php

namespace Goodport\QueryDuplicates\Http;

use Closure;
use Goodport\QueryDuplicates\Aggregator\DuplicateQueryAggregator;
use Goodport\QueryDuplicates\Config\AdminRouteDefaults;
use Goodport\QueryDuplicates\Config\DuplicateQuerySettings;
use Goodport\QueryDuplicates\Config\MethodGroupSettings;
use Goodport\QueryDuplicates\Recording\QueryDuplicateRecorder;
use Goodport\QueryDuplicates\Recording\QueryRequestContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TrackDuplicateQueries
{
    public function __construct(
        private readonly DuplicateQueryAggregator $aggregator,
        private readonly QueryDuplicateRecorder $recorder,
        private readonly DuplicateQuerySettings $settings,
    ) {}

    /**
     * Starts collection for the duration of the HTTP request.
     *
     * Requests handled by the package admin page are skipped so the page does not
     * report its own search, pagination, and clear queries. Requests whose method
     * group is disabled are skipped as well.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $group = $this->trackedGroup($request);

        if ($group === null) {
            return $next($request);
        }

        $this->aggregator->start($group->threshold);

        try {
            return $next($request);
        } finally {
            $this->aggregator->stop();
        }
    }

    /**
     * Stores the report and resets the collection area.
     */
    public function terminate(Request $request, Response $response): void
    {
        if ($this->trackedGroup($request) === null) {
            return;
        }

        try {
            $requestContext = new QueryRequestContext(
                method: $request->getMethod(),
                path: $request->path(),
                route: $request->route()?->getName(),
            );

            $this->recorder->record($requestContext, $this->aggregator->report());
        } finally {
            $this->aggregator->reset();
        }
    }

    /**
     * Returns the enabled method group for a tracked request.
     *
     * Returns null for the package admin page and for disabled groups.
     */
    private function trackedGroup(Request $request): ?MethodGroupSettings
    {
        if ($this->isAdminRequest($request)) {
            return null;
        }

        $group = $request->isMethod(Request::METHOD_GET)
            ? $this->settings->getGroup
            : $this->settings->otherGroup;

        return $group->enabled ? $group : null;
    }

    /**
     * Tells whether the request targets the package admin prefix.
     */
    private function isAdminRequest(Request $request): bool
    {
        $adminRoute = AdminRouteDefaults::normalize(config('query-duplicates.admin_route'));
        $prefix = trim($adminRoute['prefix'], '/');

        return $prefix !== '' && $request->is($prefix, $prefix.'/*');
    }
}
