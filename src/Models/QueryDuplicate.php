<?php

namespace Goodport\QueryDuplicates\Models;

use Illuminate\Database\Eloquent\Model;

final class QueryDuplicate extends Model
{
    protected $table = 'query_duplicates';

    /**
     * Returns the configured package connection instead of the application default.
     */
    public function getConnectionName(): ?string
    {
        $connection = config('query-duplicates.connection');

        return is_string($connection) && $connection !== ''
            ? $connection
            : parent::getConnectionName();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'query_count' => 'integer',
            'time_ms' => 'float',
            'occurrences' => 'integer',
        ];
    }

    /**
     * Builds the JSON context copied from the report row.
     *
     * @return array{
     *     method: string,
     *     path: string,
     *     route: string|null,
     *     sql: string,
     *     query_count: int,
     *     time_ms: float,
     *     type: string
     * }
     */
    public function context(): array
    {
        return [
            'method' => $this->method,
            'path' => $this->path,
            'route' => $this->route,
            'sql' => $this->sql,
            'query_count' => (int) $this->query_count,
            'time_ms' => (float) $this->time_ms,
            'type' => $this->type,
        ];
    }
}
