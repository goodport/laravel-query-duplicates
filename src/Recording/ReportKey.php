<?php

namespace Goodport\QueryDuplicates\Recording;

use Goodport\QueryDuplicates\Aggregator\QueryGroup;

final class ReportKey
{
    /**
     * Builds the stable identity of a report without exposing raw SQL or URLs.
     *
     * Route parameters and SQL literals are normalized so requests for different
     * records produce the same key. The SQL case is preserved.
     */
    public static function for(QueryRequestContext $context, QueryGroup $group): string
    {
        $identity = [
            $context->method,
            $context->route ?? self::normalizePath($context->path),
            self::normalizeSql($group->sql),
            $group->type,
        ];

        return hash('sha256', serialize($identity));
    }

    /**
     * Replaces UUID and numeric path segments with placeholders.
     */
    public static function normalizePath(string $path): string
    {
        $path = (string) preg_replace(
            '/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/i',
            '{uuid}',
            $path,
        );

        return (string) preg_replace('/\b\d+\b/', '{id}', $path);
    }

    /**
     * Replaces string and numeric SQL literals with placeholders.
     */
    public static function normalizeSql(string $sql): string
    {
        $sql = (string) preg_replace("/'(?:[^'\\\\]|\\\\.|'')*'/", '?', $sql);

        return (string) preg_replace('/\b\d+(?:\.\d+)?\b/', '?', $sql);
    }
}
