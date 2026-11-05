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
    | SQL substrings excluded from tracking. Matching is done against the SQL
    | with placeholders, so bindings do not affect it.
    |
    | Example:
    |
    | 'exclude' => [
    |     'select * from `sessions`',
    | ],
    |
    */

    'exclude' => [],

];
