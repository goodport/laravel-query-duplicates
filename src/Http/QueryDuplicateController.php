<?php

namespace Goodport\QueryDuplicates\Http;

use Goodport\QueryDuplicates\Http\Requests\QueryDuplicateIndexRequest;
use Goodport\QueryDuplicates\Repository\QueryDuplicateRepository;
use Goodport\QueryDuplicates\Services\QueryDuplicateClearer;
use Goodport\QueryDuplicates\Support\MissingTableDetector;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class QueryDuplicateController
{
    public function __construct(
        private readonly QueryDuplicateRepository $repository,
        private readonly QueryDuplicateClearer $clearer,
        private readonly MissingTableDetector $missingTable,
    ) {}

    /**
     * Shows the filtered report table or the migration hint.
     */
    public function index(QueryDuplicateIndexRequest $request): View
    {
        $filters = $this->repository->normalizeFilters($request->validated());

        try {
            $duplicates = $this->repository->getPage($filters);
        } catch (QueryException $exception) {
            if (! $this->missingTable->matches($exception)) {
                throw $exception;
            }

            $duplicates = null;
        }

        return view('query-duplicates::query-duplicates.index', [
            'duplicates' => $duplicates,
            'filters' => $filters,
        ]);
    }

    /**
     * Deletes every report and redirects back to the table.
     *
     * When the table is missing, the redirect has no success message: the index
     * page renders the setup block instead, so the user is not told that
     * reports were deleted when nothing existed.
     */
    public function clear(): RedirectResponse
    {
        try {
            $this->clearer->clear();
        } catch (QueryException $exception) {
            if (! $this->missingTable->matches($exception)) {
                throw $exception;
            }

            return redirect()->route('query-duplicates.index');
        }

        return redirect()
            ->route('query-duplicates.index')
            ->with('status', trans('query-duplicates::query-duplicates.cleared'));
    }
}
