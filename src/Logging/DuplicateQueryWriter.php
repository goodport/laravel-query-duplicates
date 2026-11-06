<?php

namespace Goodport\QueryDuplicates\Logging;

use Goodport\QueryDuplicates\Aggregator\QueryGroup;
use Psr\Log\LoggerInterface;

final class DuplicateQueryWriter
{
    private const LEVEL = 'warning';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ReportDeduplicator $deduplicator,
    ) {}

    /**
     * Writes a single report entry for a duplicate or N+1 query group.
     */
    public function write(QueryRequestContext $requestContext, QueryGroup $group): void
    {
        if (! $this->deduplicator->shouldWrite($requestContext, $group)) {
            return;
        }

        $timeMs = round($group->totalTimeMs, 2);
        $type = $group->type;

        $message = sprintf(
            '%s query: %d executions, %.2f ms.',
            $type === QueryGroup::TYPE__N_PLUS_ONE ? 'N+1' : 'Duplicate',
            $group->executions,
            $timeMs,
        );

        $this->logger->log(self::LEVEL, $message, [
            ...$this->baseContext($requestContext),
            'sql' => $group->sql,
            'query_count' => $group->executions,
            'time_ms' => $timeMs,
            'type' => $type,
        ]);
    }

    /**
     * Builds the common request context shared by all report entries.
     *
     * @return array{method: string, path: string, route: string|null}
     */
    private function baseContext(QueryRequestContext $requestContext): array
    {
        return [
            'method' => $requestContext->method,
            'path' => $requestContext->path,
            'route' => $requestContext->route,
        ];
    }
}
