<?php

namespace Goodport\QueryDuplicates\Repository;

use Goodport\QueryDuplicates\Models\QueryDuplicate;
use Goodport\QueryDuplicates\Storage\SqliteStorage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class QueryDuplicateRepository
{
    public const PER_PAGE = 30;

    public function __construct(
        private readonly SqliteStorage $storage,
    ) {}

    /**
     * Columns accepted by the sort parameter.
     *
     * @var list<string>
     */
    public const SORTABLE = ['method', 'updated_at', 'query_count', 'time_ms', 'occurrences'];

    /**
     * Directions accepted by the sort parameter.
     *
     * @var list<string>
     */
    public const DIRECTIONS = ['asc', 'desc'];

    public const DEFAULT_SORT = 'query_count';

    public const DEFAULT_DIRECTION = 'desc';

    /**
     * Fills the sort parameters with normalized defaults.
     *
     * The same array is passed to the query and to the view, so the highlighted
     * column always matches the applied ordering.
     *
     * @param  array{q?: string|null, type?: string|null, sort?: string|null, direction?: string|null}  $filters
     * @return array{q?: string|null, type?: string|null, sort: string, direction: string}
     */
    public function normalizeFilters(array $filters): array
    {
        $filters['sort'] = $this->sortColumn($filters['sort'] ?? null);
        $filters['direction'] = $this->sortDirection($filters['direction'] ?? null);

        return $filters;
    }

    /**
     * Returns the filtered and sorted report page.
     *
     * @param  array{q?: string|null, type?: string|null, sort?: string|null, direction?: string|null}  $filters
     */
    public function getPage(array $filters): LengthAwarePaginator
    {
        $this->storage->ensure();

        $query = QueryDuplicate::query();

        $this->applySearch($query, $filters['q'] ?? null);
        $this->applyType($query, $filters['type'] ?? null);

        return $query
            ->orderBy(
                $this->sortColumn($filters['sort'] ?? null),
                $this->sortDirection($filters['direction'] ?? null),
            )
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Searches the route, path and SQL case-insensitively.
     */
    private function applySearch(Builder $query, ?string $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $pattern = '%'.$search.'%';

        $query->where(function (Builder $query) use ($pattern): void {
            $query
                ->whereLike('route', $pattern, caseSensitive: false)
                ->orWhereLike('path', $pattern, caseSensitive: false)
                ->orWhereLike('sql', $pattern, caseSensitive: false);
        });
    }

    /**
     * Applies the report type filter.
     */
    private function applyType(Builder $query, ?string $type): void
    {
        if ($type === null || $type === '') {
            return;
        }

        $query->where('type', $type);
    }

    /**
     * Returns a whitelisted sort column or the default one.
     */
    private function sortColumn(?string $sort): string
    {
        return in_array($sort, self::SORTABLE, true) ? $sort : self::DEFAULT_SORT;
    }

    /**
     * Returns the requested direction or the default one.
     */
    private function sortDirection(?string $direction): string
    {
        return in_array($direction, self::DIRECTIONS, true) ? $direction : self::DEFAULT_DIRECTION;
    }
}
