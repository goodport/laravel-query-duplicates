<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable duplicate query tracking
    |--------------------------------------------------------------------------
    |
    | The package is enabled by default. Only SQL queries executed inside an
    | HTTP request are tracked. The admin page stays available when tracking is
    | disabled, because it reads already stored reports.
    |
    */

    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | HTTP methods
    |--------------------------------------------------------------------------
    |
    | Only requests with these methods are tracked. An empty list tracks every
    | method. Values are case-insensitive and normalized to uppercase.
    | Supported values: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS.
    |
    | Example:
    |
    | 'methods' => ['GET'],
    |
    */

    'methods' => [],

    /*
    |--------------------------------------------------------------------------
    | Strict duplicate mode
    |--------------------------------------------------------------------------
    |
    | When strict mode is disabled, a duplicate is a query with the same SQL
    | and the same bindings. When enabled, bindings are ignored and any
    | repeated SQL statement is reported.
    |
    */

    'strict' => false,

    /*
    |--------------------------------------------------------------------------
    | Minimum executions
    |--------------------------------------------------------------------------
    |
    | A query is reported when it runs at least this many times. The same
    | threshold is used for duplicates and N+1 queries.
    |
    */

    'threshold' => 2,

    /*
    |--------------------------------------------------------------------------
    | Reports storage
    |--------------------------------------------------------------------------
    |
    | The package owns a dedicated SQLite file for query_duplicates reports.
    | Null uses storage/framework/query-duplicates.sqlite. A string overrides
    | the location. The special value ":memory:" is used by the package tests.
    |
    | The file and table are created automatically; php artisan migrate is not
    | required. Local application requests, application tests, and the admin
    | page share this file. Delete the file to reset all reports.
    |
    */

    'storage_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Admin page
    |--------------------------------------------------------------------------
    |
    | enabled registers the admin routes. The package does not define the
    | [view-query-duplicates] gate, so the page returns 403 until the
    | application defines it or replaces the middleware.
    |
    | prefix is the route prefix and is also skipped during tracking.
    |
    */

    'admin_route' => [
        'enabled' => true,
        'prefix' => 'query-duplicates',
        'middleware' => ['web', 'can:view-query-duplicates'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclusions
    |--------------------------------------------------------------------------
    |
    | Queries excluded from tracking. An entry is either a SQL substring or a
    | rule array with [sql] and optional [bindings]. SQL is matched against the
    | statement with placeholders. Only the binding positions listed in the
    | rule are compared, using strict equality.
    |
    | The configuration contains only arrays and scalar values, so it can be
    | cached with config:cache.
    |
    | Example:
    |
    | 'exclude' => [
    |     'select * from `sessions`',
    |     [
    |         'sql' => 'select * from `example_events` where `category` = ?',
    |         'bindings' => [
    |             0 => 'security',
    |         ],
    |     ],
    | ],
    |
    */

    'exclude' => [],

];
