# AGENTS.md

## About the Package

`goodport/laravel-query-duplicates` is a development and testing tool for Laravel: it detects duplicate SQL queries and N+1 queries within HTTP requests. It is installed in `require-dev` and is not intended for production monitoring.

## Stack

- PHP 8.3+
- Laravel 12
- `psr/log` ^3.0
- Testing: Orchestra Testbench, PHPUnit 11
- Formatting: Laravel Pint

## Structure

- `src/QueryDuplicatesServiceProvider.php` — registers the listener and middleware.
- `src/Http/TrackDuplicateQueries.php` — per-HTTP-request collection lifecycle.
- `src/Listener/RecordQuery.php` — subscribes to `QueryExecuted`.
- `src/Aggregator/DuplicateQueryAggregator.php` — query grouping and reporting rules.
- `src/Aggregator/QueryGroup.php` — group data and the `duplicate` and `n+1` types.
- `src/Config/DuplicateQuerySettings.php` — config parsing and validation.
- `src/Logging/` — report writing and channel selection.
- `src/Support/` — SQL exceptions and N+1 detection.
- `config/query-duplicates.php` — publishable config.
- `tests/` — Unit, Feature, Integration, and Support helpers.

## Key Behavior

- Only SQL executed inside an HTTP request is tracked; console, queue, scheduler, and Octane are out of scope.
- N+1 takes priority over duplicate.
- The trigger threshold is `threshold`.
- The query string is never included in the report.
- Memory is bounded by the number of unique SQL statements and a limit on binding sets.

## Commands

- `composer install`
- `composer test` or `vendor/bin/phpunit`
- `vendor/bin/pint --dirty`

## Tests

- Use Orchestra Testbench, a fake connection, and file-based log channels.
- No real database or network access is required.
- Run only the tests related to your change.

## Rules

- Do not add dependencies on application-specific models, controllers, routes, or databases.
- Change the public API and config in a backward-compatible way.
- This file describes the package structure; update it when the architecture changes.
