# AGENTS.md

## About the Package

`goodport/laravel-query-duplicates` is a development and testing tool for Laravel: it detects duplicate SQL queries and N+1 queries within HTTP requests, stores reports in a package-owned SQLite file, and shows them on a small built-in page. It is installed in `require-dev` and is not intended for production monitoring.

## Stack

- PHP 8.3+
- Laravel 12
- Testing: Orchestra Testbench, PHPUnit 11
- Formatting: Laravel Pint

## Structure

- `src/QueryDuplicatesServiceProvider.php` — config, SQLite connection, views, translations, admin routes, listener and middleware registration.
- `src/Storage/SqliteStorage.php` — owns the `query-duplicates` connection, file path, directory creation, and the automatic `query_duplicates` table.
- `src/Storage/StorageUnavailableException.php` — unavailable SQLite directory, file, or table.
- `src/Http/TrackDuplicateQueries.php` — per-HTTP-request collection lifecycle, admin-prefix skip, and recorder call in `terminate()`.
- `src/Http/QueryDuplicateController.php` — admin page, clear, and single-report delete actions.
- `src/Http/Requests/QueryDuplicateIndexRequest.php` — query filter validation.
- `src/Listener/RecordQuery.php` — subscribes to `QueryExecuted`.
- `src/Aggregator/DuplicateQueryAggregator.php` — query grouping and reporting rules.
- `src/Aggregator/QueryGroup.php` — group data and the `duplicate` and `n+1` types.
- `src/Config/AdminRouteDefaults.php` — safe admin route defaults and normalization.
- `src/Config/BooleanValue.php` — strict boolean config parsing shared by settings and the admin route.
- `src/Config/DuplicateQuerySettings.php` — tracking config parsing and validation.
- `src/Models/QueryDuplicate.php` — report model fixed to the `query-duplicates` connection.
- `src/Recording/ReportKey.php` — path and SQL normalization and the SHA-256 report identity.
- `src/Recording/QueryDuplicateRecorder.php` — batched `upsert` + `increment`, database failure isolation.
- `src/Recording/QueryRequestContext.php` — method, path, and route of one request.
- `src/Repository/QueryDuplicateRepository.php` — read-only search, type filter, whitelisted sorting, and pagination.
- `src/Services/QueryDuplicateClearer.php` — deletes all reports.
- `src/Services/QueryDuplicateDeleter.php` — deletes one report by id.
- `src/Support/` — exclusions and N+1 detection.
- `routes/web.php` — admin routes.
- `resources/views/query-duplicates/index.blade.php` — self-contained admin page.
- `lang/en/` and `lang/ru/` — page translations.
- `config/query-duplicates.php` — publishable config.
- `tests/` — Unit, Feature, and Support helpers.

## Key Behavior

- Reports live in the package-owned SQLite file `storage/framework/query-duplicates.sqlite` unless `storage_path` overrides it. The application default connection and the application test database are never used.
- `SqliteStorage::ensure()` creates the directory and table automatically; no migration is required. Concurrent table creation is tolerated by re-checking `hasTable`.
- Local application requests, application tests, and the admin page share the same SQLite file. Deleting the file resets all reports.
- Only SQL executed inside an HTTP request is tracked; console, queue, scheduler, and Octane are out of scope.
- N+1 takes priority over duplicate.
- The trigger threshold is `threshold`.
- The query string and bindings are never stored; SQL is stored exactly as `QueryExecuted` returned it.
- One report identity is one row. The identity is built from method, route (or normalized path), normalized SQL, and type, then hashed with SHA-256.
- Repeated reports update `path`, `route`, `sql`, `query_count`, `time_ms`, `type`, and `updated_at`, and increase `occurrences`. `created_at` is the first occurrence and `updated_at` is the last one.
- Storage is batched: one `upsert` and one `increment` per request, regardless of the number of groups.
- Storage errors never break the HTTP response and are reported once per process.
- The admin page shows a setup message with the SQLite path when the storage is unavailable; other errors are not masked.
- A single report can be deleted from its row; the redirect keeps the current search, type, sort, direction, and page.
- The admin page sorts by `query_count` descending by default; `method` is also sortable.
- The admin page is registered by default but returns 403 until the application defines the `view-query-duplicates` gate or replaces the middleware.
- The page is available when tracking is disabled because it only reads stored reports and shows the active SQLite path.
- Requests under `admin_route.prefix` are not tracked.
- Memory is bounded by the number of unique SQL statements and a limit on binding sets.

## Commands

- `composer install`
- `composer test` or `vendor/bin/phpunit`
- `vendor/bin/pint --dirty`

## Tests

- Use Orchestra Testbench.
- Package tests set `query-duplicates.storage_path` to `:memory:` and call `SqliteStorage::ensure()` in `setUp`. There are no migration helpers.
- The `fake` connection remains for tracked SQL and HTTP lifecycle tests.
- No real database or network access is required.
- Run only the tests related to your change.

## Rules

- Do not add dependencies on application-specific models, controllers, routes, or databases.
- Change the public API and config in a backward-compatible way.
- This file describes the package structure; update it when the architecture changes.
