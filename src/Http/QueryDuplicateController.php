<?php

namespace Goodport\QueryDuplicates\Http;

use Goodport\QueryDuplicates\Http\Requests\QueryDuplicateIndexRequest;
use Goodport\QueryDuplicates\Repository\QueryDuplicateRepository;
use Goodport\QueryDuplicates\Services\QueryDuplicateClearer;
use Goodport\QueryDuplicates\Storage\SqliteStorage;
use Goodport\QueryDuplicates\Storage\StorageUnavailableException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class QueryDuplicateController
{
    public function __construct(
        private readonly QueryDuplicateRepository $repository,
        private readonly QueryDuplicateClearer $clearer,
        private readonly SqliteStorage $storage,
    ) {}

    /**
     * Shows the filtered report table or the storage setup message.
     */
    public function index(QueryDuplicateIndexRequest $request): View
    {
        $filters = $this->repository->normalizeFilters($request->validated());

        try {
            $duplicates = $this->repository->getPage($filters);
            $storagePath = $this->storage->path();
        } catch (StorageUnavailableException $exception) {
            return view('query-duplicates::query-duplicates.index', [
                'duplicates' => null,
                'filters' => $filters,
                'storagePath' => $exception->storagePath,
                'storageError' => $exception->getMessage(),
            ]);
        }

        return view('query-duplicates::query-duplicates.index', [
            'duplicates' => $duplicates,
            'filters' => $filters,
            'storagePath' => $storagePath,
            'storageError' => null,
        ]);
    }

    /**
     * Deletes every report and redirects back to the table.
     *
     * When the SQLite storage is unavailable, the redirect has no success
     * message: the index page renders the setup block instead, so the user is
     * not told that reports were deleted when nothing could be cleared.
     */
    public function clear(): RedirectResponse
    {
        try {
            $this->clearer->clear();
        } catch (StorageUnavailableException) {
            return redirect()->route('query-duplicates.index');
        }

        return redirect()
            ->route('query-duplicates.index')
            ->with('status', trans('query-duplicates::query-duplicates.cleared'));
    }
}
