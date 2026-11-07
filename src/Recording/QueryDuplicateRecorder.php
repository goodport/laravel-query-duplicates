<?php

namespace Goodport\QueryDuplicates\Recording;

use Goodport\QueryDuplicates\Aggregator\QueryGroup;
use Goodport\QueryDuplicates\Models\QueryDuplicate;
use Goodport\QueryDuplicates\Storage\SqliteStorage;
use Throwable;

final class QueryDuplicateRecorder
{
    private bool $errorReported = false;

    public function __construct(
        private readonly SqliteStorage $storage,
    ) {}

    /**
     * Stores the reported groups of one HTTP request in a single batch.
     *
     * Storage failures never break the application response. The exception is
     * reported once per process so an unavailable SQLite storage does not flood
     * the log.
     *
     * @param  list<QueryGroup>  $groups
     */
    public function record(QueryRequestContext $context, array $groups): void
    {
        $rows = $this->buildRows($context, $groups);

        if ($rows === []) {
            return;
        }

        try {
            $this->storage->ensure();
            $this->write($rows);
        } catch (Throwable $exception) {
            $this->reportError($exception);
        }
    }

    /**
     * Inserts or updates report rows and counts one occurrence per request.
     *
     * Both statements share a transaction on the package connection, so a
     * failure cannot leave a freshly inserted row with occurrences=0.
     *
     * @param  array<string, array<string, mixed>>  $rows  Rows keyed by report key.
     */
    private function write(array $rows): void
    {
        QueryDuplicate::query()->getConnection()->transaction(function () use ($rows): void {
            QueryDuplicate::query()->upsert(
                array_values($rows),
                ['report_key'],
                ['path', 'route', 'sql', 'query_count', 'time_ms', 'type', 'updated_at'],
            );

            QueryDuplicate::query()
                ->whereIn('report_key', array_keys($rows))
                ->increment('occurrences');
        });
    }

    /**
     * Builds one row per report identity.
     *
     * Groups that normalize to the same identity are merged because a single
     * upsert statement cannot contain the same unique key twice. Execution
     * counts and times are summed; the latest values win for the other fields.
     *
     * @param  list<QueryGroup>  $groups
     * @return array<string, array<string, mixed>>
     */
    private function buildRows(QueryRequestContext $context, array $groups): array
    {
        $reportedAt = now();
        $rows = [];

        foreach ($groups as $group) {
            $reportKey = ReportKey::for($context, $group);

            if (isset($rows[$reportKey])) {
                $rows[$reportKey]['query_count'] += $group->executions;
                $rows[$reportKey]['time_ms'] += $group->totalTimeMs;
                $rows[$reportKey]['method'] = $context->method;
                $rows[$reportKey]['path'] = $context->path;
                $rows[$reportKey]['route'] = $context->route;
                $rows[$reportKey]['sql'] = $group->sql;
                $rows[$reportKey]['type'] = $group->type;

                continue;
            }

            $rows[$reportKey] = [
                'report_key' => $reportKey,
                'method' => $context->method,
                'path' => $context->path,
                'route' => $context->route,
                'sql' => $group->sql,
                'query_count' => $group->executions,
                'time_ms' => $group->totalTimeMs,
                'type' => $group->type,
                'occurrences' => 0,
                'created_at' => $reportedAt,
                'updated_at' => $reportedAt,
            ];
        }

        foreach ($rows as $reportKey => $row) {
            $rows[$reportKey]['time_ms'] = round($row['time_ms'], 2);
        }

        return $rows;
    }

    /**
     * Sends the first database failure of the process to the application handler.
     */
    private function reportError(Throwable $exception): void
    {
        if ($this->errorReported) {
            return;
        }

        $this->errorReported = true;

        report($exception);
    }
}
