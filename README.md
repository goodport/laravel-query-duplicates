# Laravel Query Duplicates

Find duplicated SQL queries and N+1 queries inside Laravel HTTP requests and inspect
them on a small built-in page.

The package reports two kinds of problems:

- **Duplicate** — the same SQL statement executed several times.
  - By default, the bindings must match as well.
  - In strict mode, any repeated SQL statement is reported, even with different bindings.
- **N+1** — a lazy Eloquent relation loaded several times from the same place in the code.

The package observes only SQL executed while an HTTP request is being handled.
Data preparation in tests (factories, seeders, `actingAs`) and assertions after the
request (`assertDatabaseHas` and similar) are outside the tracked area.

## Designed for test runs

The package is built for the test workflow: run your HTTP or feature tests, then open
the admin page and review the duplicate and N+1 queries those requests produced.

Reports are stored in the package-owned SQLite file and are kept after the test run.
The same file is used by the application, its tests, and the admin page, so reports
created during the test suite appear on the page without any export or migration step.

Typical workflow:

1. Run your application HTTP or feature tests.
2. Open `/query-duplicates` (or your configured `admin_route.prefix`).
3. Review, filter, and sort the reported queries; copy a report as JSON when needed.
4. Fix the code, rerun the tests, and delete the rows that are already resolved.

Only SQL executed while handling an HTTP request is observed. Pure unit tests, console
commands, factories, seeders, and post-request assertions do not produce reports.
This is not production monitoring: the package is intended for development and test
runs.

The default thresholds are tuned for test runs: GET requests are reported from 2
executions, and other HTTP methods from 5. Adjust the thresholds or disable a method
group in the configuration when a test suite produces too many or too few reports.

## Requirements

- PHP 8.3+
- Laravel 12 (`laravel/framework`)
- `pdo_sqlite` PHP extension

The package is a development and testing tool. Install it in `require-dev`; it is
not intended for production monitoring.

## Installation

```bash
composer require --dev goodport/laravel-query-duplicates
```

The service provider is discovered automatically. No migration is required:
`php artisan migrate` is not needed, and the package never writes reports to the
application database.

Publish the configuration or the views only when you need to change them:

```bash
php artisan vendor:publish --tag=query-duplicates-config
php artisan vendor:publish --tag=query-duplicates-views
```

The package no longer writes to the log and no longer uses a database connection
configured by the application. The previous `log_channel` and `connection` settings
were removed. This is a major change.

## SQLite storage

The package owns a dedicated SQLite file and registers its own connection named
`query-duplicates`. The application default connection and the application test
database are never used.

Default location:

```
storage/framework/query-duplicates.sqlite
```

The file and the `query_duplicates` table are created automatically on first use.
Local application requests, application tests, and the admin page all read and write
the same file, so reports produced by tests are visible in the local admin page.

The connection uses `busy_timeout = 5000` and `journal_mode = WAL` to reduce
`database is locked` failures during concurrent tests, and disables foreign key
constraints.

Delete the SQLite file to reset all reports. There is no migration to roll back.

## Configuration

```php
// config/query-duplicates.php
return [
    'enabled' => true,
    'methods' => [
        'get' => [
            'enabled' => true,
            'threshold' => 2,
        ],
        'other' => [
            'enabled' => true,
            'threshold' => 5,
        ],
    ],
    'strict' => false,
    'storage_path' => null,
    'admin_route' => [
        'enabled' => true,
        'prefix' => 'query-duplicates',
        'middleware' => ['web', 'can:view-query-duplicates'],
    ],
    'exclude' => [],
];
```

| Key | Default | Description |
| --- | --- | --- |
| `enabled` | `true` | Registers the query listener and the global middleware. |
| `methods.get.enabled` | `true` | Tracks GET requests. |
| `methods.get.threshold` | `2` | Minimum executions before a GET query is reported. Used for duplicates and N+1. |
| `methods.other.enabled` | `true` | Tracks every non-GET method: POST, PUT, PATCH, DELETE, HEAD, OPTIONS, and custom methods. |
| `methods.other.threshold` | `5` | Minimum executions before a non-GET query is reported. Used for duplicates and N+1. |
| `strict` | `false` | Reports the same SQL regardless of bindings. |
| `storage_path` | `null` | Path to the package SQLite file. `null` uses `storage/framework/query-duplicates.sqlite`. `:memory:` is used by the package tests. |
| `admin_route.enabled` | `true` | Registers the admin routes. The page is available even when tracking is disabled. |
| `admin_route.prefix` | `query-duplicates` | Route prefix. Requests under this prefix are never tracked. |
| `admin_route.middleware` | `['web', 'can:view-query-duplicates']` | Middleware for the admin routes. |
| `exclude` | `[]` | SQL substrings and rules with optional bindings excluded from tracking. |

`methods` is an associative array. The allowed groups are `get` and `other`; either
group may be omitted, and a missing group receives its own defaults. Unknown groups
and unknown group settings stop configuration parsing with an
`InvalidArgumentException`. A missing or empty `methods` section uses the defaults
above.

> **Breaking change.** The previous global `threshold` setting and the flat method
> list such as `methods => ['GET']` are no longer supported. Update a published
> `config/query-duplicates.php` to the grouped `methods` structure shown above before
> upgrading. The empty `methods => []` form still works and means both default groups.

### Reporting rules

- The threshold is selected per HTTP request before collection starts: GET requests
  use `methods.get.threshold`, every other method uses `methods.other.threshold`.
- A disabled group does not start collection. Its existing reports and the admin page
  stay available.
- A query is considered only when it ran at least the active group threshold times.
- **N+1**: the query comes from a lazy Eloquent relation, and at least the threshold
  number of executions share the same call site.
- **Duplicate**: the same SQL ran at least the threshold number of times. In
  non-strict mode, the bindings must also match. In strict mode, bindings are ignored.
- N+1 has priority: a relation query is reported as `n+1`, not as a duplicate.
- A single execution is never reported.
- Reports are still split by the actual HTTP method, so a GET and a POST request never
  share a report row.

### Report identity

One report is stored as one row in `query_duplicates`. The row is identified by a
SHA-256 key built from:

- the HTTP method;
- the route name, or the normalized path when the route has no name;
- the normalized SQL;
- the report type.

Numeric path segments become `{id}`, UUID segments become `{uuid}`, and string and
numeric SQL literals become `?`. SQL case is preserved. Repeated requests for
different records therefore update the same row and increase `occurrences` instead
of creating near-duplicates.

Unnamed routes with slugs produce a separate row for each distinct path. This is an
accepted v1 limitation.

### Exclusions

Each `exclude` entry is either a SQL substring or a rule array. Substrings are
matched against the normalized SQL returned by Laravel, so `?` placeholders are
used and bindings do not affect matching:

```php
'exclude' => [
    'select * from `sessions`',
],
```

A rule array has a required `sql` substring and optional `bindings`:

```php
'exclude' => [
    [
        'sql' => 'select * from `example_events` where `category` = ? and `owner_type` = ?',
        'bindings' => [
            0 => 'security',
            1 => 'App\\Example\\Widget',
        ],
    ],
],
```

The query is excluded only when the SQL contains `sql` and every listed binding
position matches the query bindings strictly (`===`). Positions that are not
listed in the rule, including dynamic identifiers and types, are not checked.
A position that is listed in the rule but missing from the query does not match.
An empty `bindings` array excludes by SQL only. Several entries are combined with
OR, and string entries keep their previous behavior.

Invalid rules, such as an empty `sql`, a non-array `bindings` value, or a negative
or non-integer binding position, stop configuration parsing with an
`InvalidArgumentException`.

The published configuration is a literal array of scalars and arrays, with no
class references, closures or callables. It keeps working after
`composer install --no-dev` when the package is not present in `vendor`, and it is
compatible with `php artisan config:cache`. Application-specific
exclusions belong in the publishing application's configuration, not in the package.

## Admin page

The package registers three routes:

| Method | Path | Route name |
| --- | --- | --- |
| `GET` | `{prefix}` | `query-duplicates.index` |
| `POST` | `{prefix}/clear` | `query-duplicates.clear` |
| `DELETE` | `{prefix}/{queryDuplicate}` | `query-duplicates.destroy` |

The package does not define the `view-query-duplicates` gate. With the default
middleware the page returns 403 until the application defines the gate:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('view-query-duplicates', function ($user): bool {
        return $user->isAdmin();
    });
}
```

Access can also be restricted with application middleware instead of a gate:

```php
'admin_route' => [
    'enabled' => true,
    'prefix' => 'query-duplicates',
    'middleware' => ['web', 'isAdmin'],
],
```

The page supports:

- search by `route`, `path`, and `sql`, case-insensitive;
- filtering by `type` (`duplicate` or `n+1`) and HTTP method (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`);
- sorting by `method`, `updated_at`, `query_count`, `time_ms`, and `occurrences`;
- the default order is `query_count` descending;
- pagination with 30 rows per page;
- clearing every report with a POST request, CSRF protection, and confirmation;
- deleting a single report from its row with a DELETE request while keeping the current search, type, sort, direction, and page;
- copying a row as JSON for sharing with a developer or agent.

The copied JSON has the same keys as the previous log context:

```json
{
    "method": "GET",
    "path": "users/42/edit",
    "route": "users.edit",
    "sql": "select exists(select * from `posts` where `user_id` = ?) as `exists`",
    "query_count": 3,
    "time_ms": 2.96,
    "type": "duplicate"
}
```

The page is self-contained: Blade with inline CSS and JavaScript, no CDN and no
dependency on the application's CSS framework. Requests to the admin prefix are
not tracked, so the page never reports its own queries.

The inline styles and script may need a Content-Security-Policy exception. A strict
policy usually has to allow `style-src 'unsafe-inline'` and
`script-src 'unsafe-inline'` (or a matching nonce) for this page to render and for
the copy button to work.

> **SQL may contain literal values.** The `sql` column stores the statement exactly
> as `QueryExecuted` returned it, with placeholders and **without** substituting
> bindings. If the application inlines values into the SQL string itself (raw
> expressions, hand-built statements with literals), those values are written to
> `query_duplicates` and included in the copied JSON. Treat the table and the
> copied JSON as application data, not as sanitized diagnostics.

## Table

The `query_duplicates` table has these columns:

| Column | Type | Description |
| --- | --- | --- |
| `id` | primary key | Row identifier. |
| `report_key` | `char(64)` unique | SHA-256 identity of the report. |
| `method` | `string(10)` | HTTP method. |
| `path` | `text` | Last seen path without the query string. |
| `route` | `string(255)` nullable | Route name, or `null`. |
| `sql` | `text` | Exact SQL returned by `QueryExecuted`, without substituted bindings. |
| `query_count` | unsigned integer | Executions in the last reported request. |
| `time_ms` | `decimal(10,2)` | Total time of the last reported request. |
| `type` | `string(10)` | `duplicate` or `n+1`. |
| `occurrences` | unsigned integer | Number of times the report was seen. |
| `created_at` | timestamp | First occurrence. |
| `updated_at` | timestamp | Last occurrence. |

Indexes cover `report_key` (unique), `updated_at`, `query_count`, `time_ms`, and
`occurrences`.

### Storage path

The file is created automatically. Override its location when needed:

```php
'storage_path' => storage_path('framework/query-duplicates.sqlite'),
```

The parent directory is created when it is missing. The package registers the
`query-duplicates` SQLite connection itself; there is no connection selector in the
configuration, the admin page, or the database config of the application.

### Storage failures

A failing recorder never breaks the HTTP response. The first storage error is sent
to the application exception handler once per process, so an unavailable file does
not flood the log. The admin page catches
`Goodport\QueryDuplicates\Storage\StorageUnavailableException` separately and shows
a setup message with the active SQLite path and a request to check write permissions
for `storage/framework`. Other errors are not masked.

The admin page always displays the active SQLite path. When the file is deleted, the
next request creates it again and starts a fresh report history.

## Performance

- Disabled: neither the listener nor the middleware is registered.
- Enabled outside an HTTP request (console, queue, test preparation): one flag
  check per query.
- Enabled inside an HTTP request: one `md5` of the SQL and one binding
  fingerprint per execution. The call stack is inspected once for every new query
  and on each execution of a relation query until it is confirmed as N+1.
- When a report is stored: one batched `upsert` and one `increment` per request,
  regardless of the number of reported groups.

Memory is bounded by the number of unique SQL statements and by a fixed limit of
tracked binding sets per group.

## Limitations

- Only HTTP requests are tracked. Console commands, queue workers, the scheduler
  and Octane are out of scope.
- There is no automatic retention or scheduled cleanup. Reports stay until they are
  cleared on the admin page or the SQLite file is deleted.
- N+1 detection covers lazy Eloquent relations. Manual repeated queries are
  reported as duplicates.
- `WithoutMiddleware` removes the package middleware, so tracking does not run for
  such routes.
- When an exception escapes the HTTP kernel (for example with
  `withoutExceptionHandling()`), `terminate()` is not called and no report is
  written. `handle()` still stops collection in `finally`; the next request's
  `start()` resets the previous area, so data never leaks into the next request.
- Nested HTTP requests through a repeated `Kernel::handle()` are not supported.

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint
```

## License

MIT.
