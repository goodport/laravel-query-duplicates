<?php

namespace Goodport\QueryDuplicates\Logging;

use Goodport\QueryDuplicates\Aggregator\QueryGroup;
use Illuminate\Contracts\Cache\Repository;

final class ReportDeduplicator
{
    /**
     * How long an identical report is considered already written.
     */
    private const TTL_SECONDS = 3600;

    private const KEY_PREFIX = 'query-duplicates:report:';

    public function __construct(
        private readonly Repository $cache,
    ) {}

    /**
     * Marks the report as written and tells whether it should be logged now.
     */
    public function shouldWrite(QueryRequestContext $requestContext, QueryGroup $group): bool
    {
        return $this->cache->add($this->key($requestContext, $group), true, self::TTL_SECONDS);
    }

    /**
     * Builds a cache key from the report identity without exposing raw SQL.
     *
     * Route parameters and SQL literals are normalized so repeated requests for
     * different records are treated as the same report.
     */
    private function key(QueryRequestContext $requestContext, QueryGroup $group): string
    {
        $identity = [
            $requestContext->method,
            $requestContext->route ?? $this->normalizePath($requestContext->path),
            $this->normalizeSql($group->sql),
            $group->type,
        ];

        return self::KEY_PREFIX.hash('sha256', serialize($identity));
    }

    /**
     * Replaces numeric path segments with a placeholder.
     */
    private function normalizePath(string $path): string
    {
        return (string) preg_replace('/\b\d+\b/', '{id}', $path);
    }

    /**
     * Replaces SQL value literals with placeholders to compare query shapes.
     */
    private function normalizeSql(string $sql): string
    {
        $sql = (string) preg_replace("/'(?:[^'\\\\]|\\\\.|'')*'/", '?', $sql);

        return (string) preg_replace('/\b\d+(?:\.\d+)?\b/', '?', $sql);
    }
}
