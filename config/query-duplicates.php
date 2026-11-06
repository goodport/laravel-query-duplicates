<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable duplicate query tracking
    |--------------------------------------------------------------------------
    |
    | The package is enabled by default. Only SQL queries executed inside an
    | HTTP request are tracked.
    |
    */

    'enabled' => true,

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
    | Log channel
    |--------------------------------------------------------------------------
    |
    | Any channel defined in config/logging.php. When null, the application's
    | default channel is used.
    |
    */

    'log_channel' => null,

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
