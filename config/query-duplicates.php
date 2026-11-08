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
    | HTTP method groups
    |--------------------------------------------------------------------------
    |
    | Tracking is configured separately for GET requests and for every other
    | HTTP method: POST, PUT, PATCH, DELETE, HEAD, OPTIONS, and any custom
    | method. Each group has two settings:
    |
    | enabled   - turns tracking on or off for the whole group. A disabled group
    |             stops creating new reports. Existing reports and the admin
    |             page stay available.
    | threshold - minimum number of executions before a query is reported. It
    |             applies to duplicates and N+1 queries and must be an integer
    |             of at least 2.
    |
    | Reports are still split by the actual HTTP method, so GET and POST
    | requests create separate report rows. The group threshold is selected for
    | each HTTP request before collection starts.
    |
    | A missing group uses its own defaults. A missing or empty [methods]
    | section uses the defaults below. Unknown group names and unknown group
    | settings are rejected.
    |
    */

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
    | Each entry is an exclusion rule with an optional [route] and [query].
    |
    | Route only: excludes every query on matching routes.
    | Query only: excludes matching queries on every route.
    | Both: excludes matching queries only on matching routes.
    |
    | Route names support [*] wildcards. Unnamed routes cannot match a route
    | exclusion. A query is either a SQL substring or a rule array with [sql]
    | and optional [bindings]. SQL is matched against the statement with
    | placeholders. Only the binding positions listed in the rule are compared,
    | using strict equality. At least one of [route] or [query] is required.
    |
    | The configuration contains only arrays and scalar values, so it can be
    | cached with config:cache.
    |
    | Example:
    |
    | 'exclude' => [
    |     [
    |         'route' => 'admin.*',
    |     ],
    |     [
    |         'query' => 'select * from `sessions`',
    |     ],
    |     [
    |         'route' => 'posts.*',
    |         'query' => [
    |             'sql' => 'select * from `example_events` where `category` = ?',
    |             'bindings' => [
    |                 0 => 'security',
    |             ],
    |         ],
    |     ],
    | ],
    |
    */

    'exclude' => [],

];
