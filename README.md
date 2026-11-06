# Laravel Query Duplicates

Find duplicated SQL queries and N+1 queries inside Laravel HTTP requests.

The package reports two kinds of problems:

- **Duplicate** — the same SQL statement executed several times.
  - By default, the bindings must match as well.
  - In strict mode, any repeated SQL statement is reported, even with different bindings.
- **N+1** — a lazy Eloquent relation loaded several times from the same place in the code.

The package observes only SQL executed while an HTTP request is being handled.
Data preparation in tests (factories, seeders, `actingAs`) and assertions after the
request (`assertDatabaseHas` and similar) are outside the tracked area.

## Requirements

- PHP 8.3+
- Laravel 12 (`laravel/framework`)

The package is a development and testing tool. Install it in `require-dev`; it is
not intended for production monitoring.

## Installation

```bash
composer require --dev goodport/laravel-query-duplicates
```

The service provider is discovered automatically. The package is enabled by default
and writes reports to the application's default log channel.

Publish the configuration file only when you need to change the defaults:

```bash
php artisan vendor:publish --tag=query-duplicates-config
```

## Configuration

```php
// config/query-duplicates.php
return [
    'enabled' => true,
    'strict' => false,
    'threshold' => 2,
    'log_channel' => null,
    'exclude' => [],
];
```

| Key | Default | Description |
| --- | --- | --- |
| `enabled` | `true` | Registers the query listener and the global middleware. |
| `strict` | `false` | Reports the same SQL regardless of bindings. |
| `threshold` | `2` | Minimum executions before a query is reported. Used for duplicates and N+1. |
| `log_channel` | `null` | Log channel name; `null` means the default channel. |
| `exclude` | `[]` | SQL substrings and rules with optional bindings excluded from tracking. |

A dedicated log channel keeps reports separate from the regular application log:

```php
// config/logging.php
'query-duplicates' => [
    'driver' => 'single',
    'path' => storage_path('logs/query-duplicates.log'),
    'level' => 'debug',
],
```

```php
// config/query-duplicates.php
'log_channel' => 'query-duplicates',
```

When the package is enabled, a channel that is not declared in `config/logging.php`
fails during provider boot with a clear exception instead of silently writing to
the emergency logger.

### Reporting rules

- A query is considered only when it ran at least `threshold` times.
- **N+1**: the query comes from a lazy Eloquent relation, and at least `threshold`
  executions share the same call site.
- **Duplicate**: the same SQL ran at least `threshold` times. In non-strict mode,
  the bindings must also match. In strict mode, bindings are ignored.
- N+1 has priority: a relation query is reported as `n+1`, not as a duplicate.

A single execution is never reported.

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
An empty `bindings` array excludes by SQL only. Several entries are combined
with OR, and string entries keep their previous behavior.

Invalid rules, such as an empty `sql`, a non-array `bindings` value, or a
negative or non-integer binding position, stop configuration parsing with an
`InvalidArgumentException`.

The configuration uses only arrays and scalar values, with no closures or
callables, so it is compatible with `php artisan config:cache`. Application
specific exclusions belong in the publishing application's configuration, not
in the package.

## Log output

One entry is written per reported query group in `terminate()`. In a normal HTTP
lifecycle this happens after the response has been sent; in tests `terminate()` is
called before `call()` returns.

```json
{
    "message": "Duplicate query: 3 executions, 2.96 ms.",
    "context": {
        "method": "GET",
        "path": "users/42/edit",
        "route": "users.edit",
        "sql": "select exists(select * from `posts` where `user_id` = ?) as `exists`",
        "query_count": 3,
        "time_ms": 2.96,
        "type": "duplicate"
    }
}
```

- `query_count` — how many times the SQL ran during the request.
- `time_ms` — total time of all executions.
- `type` — `duplicate` or `n+1`.

The request path is logged without the query string, so signed URLs and tokens in
query parameters do not reach the log.

## Performance

- Disabled: neither the listener nor the middleware is registered.
- Enabled outside an HTTP request (console, queue, test preparation): one flag
  check per query.
- Enabled inside an HTTP request: one `md5` of the SQL and one binding
  fingerprint per execution. The call stack is inspected once for every new query
  and on each execution of a relation query until it is confirmed as N+1.

Memory is bounded by the number of unique SQL statements and by a fixed limit of
tracked binding sets per group.

## Limitations

- Only HTTP requests are tracked. Console commands, queue workers, the scheduler
  and Octane are out of scope.
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
