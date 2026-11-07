# AGENTS.md

## About the Package

`goodport/laravel-query-duplicates` is a development and testing tool for Laravel: it detects duplicate SQL queries and N+1 queries within HTTP requests, stores reports in the `query_duplicates` table, and shows them on a small built-in page. It is installed in `require-dev` and is not intended for production monitoring.

## Stack

- PHP 8.3+
- Laravel 12
- Testing: Orchestra Testbench, PHPUnit 11
- Formatting: Laravel Pint

## Structure

- `src/QueryDuplicatesServiceProvider.php` — config, views, translations, migrations, admin routes, listener and middleware registration.
- `src/Http/TrackDuplicateQueries.php` — per-HTTP-request collection lifecycle, admin-prefix skip, and recorder call in `terminate()`.
- `src/Http/QueryDuplicateController.php` — admin page and clear action.
- `src/Http/Requests/QueryDuplicateIndexRequest.php` — query filter validation.
- `src/Listener/RecordQuery.php` — subscribes to `QueryExecuted`.
- `src/Aggregator/DuplicateQueryAggregator.php` — query grouping and reporting rules.
- `src/Aggregator/QueryGroup.php` — group data and the `duplicate` and `n+1` types.
- `src/Config/AdminRouteDefaults.php` — safe admin route defaults and normalization.
- `src/Config/BooleanValue.php` — strict boolean config parsing shared by settings and the admin route.
- `src/Config/DuplicateQuerySettings.php` — tracking config parsing and validation.
- `src/Models/QueryDuplicate.php` — report model and the package connection override.
- `src/Recording/ReportKey.php` — path and SQL normalization and the SHA-256 report identity.
- `src/Recording/QueryDuplicateRecorder.php` — batched `upsert` + `increment`, database failure isolation.
- `src/Recording/QueryRequestContext.php` — method, path, and route of one request.
- `src/Repository/QueryDuplicateRepository.php` — read-only search, type filter, whitelisted sorting, and pagination.
- `src/Services/QueryDuplicateClearer.php` — deletes all reports.
- `src/Support/` — exclusions, N+1 detection, and missing-table detection.
- `database/migrations/` — the `query_duplicates` table.
- `routes/web.php` — admin routes.
- `resources/views/query-duplicates/index.blade.php` — self-contained admin page.
- `lang/en/` and `lang/ru/` — page translations.
- `config/query-duplicates.php` — publishable config.
- `tests/` — Unit, Feature, and Support helpers.

## Key Behavior

- Only SQL executed inside an HTTP request is tracked; console, queue, scheduler, and Octane are out of scope.
- N+1 takes priority over duplicate.
- The trigger threshold is `threshold`.
- The query string and bindings are never stored; SQL is stored exactly as `QueryExecuted` returned it.
- One report identity is one row. The identity is built from method, route (or normalized path), normalized SQL, and type, then hashed with SHA-256.
- Repeated reports update `path`, `route`, `sql`, `query_count`, `time_ms`, `type`, and `updated_at`, and increase `occurrences`. `created_at` is the first occurrence and `updated_at` is the last one.
- Storage is batched: one `upsert` and one `increment` per request, regardless of the number of groups.
- Database errors never break the HTTP response and are reported once per process.
- The admin page is registered by default but returns 403 until the application defines the `view-query-duplicates` gate or replaces the middleware.
- The page is available when tracking is disabled because it only reads stored reports.
- Requests under `admin_route.prefix` are not tracked.
- Memory is bounded by the number of unique SQL statements and a limit on binding sets.

## Commands

- `composer install`
- `composer test` or `vendor/bin/phpunit`
- `vendor/bin/pint --dirty`

## Tests

- Use Orchestra Testbench.
- The package table uses a separate SQLite `:memory:` connection configured through `query-duplicates.connection`; the real package migration runs in `setUp`.
- The `fake` connection remains for tracked SQL and HTTP lifecycle tests.
- No real database or network access is required.
- Run only the tests related to your change.

## Rules

- Do not add dependencies on application-specific models, controllers, routes, or databases.
- Change the public API and config in a backward-compatible way.
- This file describes the package structure; update it when the architecture changes.
