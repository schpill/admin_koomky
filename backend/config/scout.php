<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    */

    'driver' => env('SCOUT_DRIVER', 'meilisearch'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    */

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    */

    'queue' => env('SCOUT_QUEUE', true),

    /*
    |--------------------------------------------------------------------------
    | Database Transactions
    |--------------------------------------------------------------------------
    */

    'after_commit' => false,

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    */

    'chunk' => [
        'searchable' => 500,
        'import' => 500,
        'flush' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Engine Configuration
    |--------------------------------------------------------------------------
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            'users' => [
                'filterableAttributes' => ['name', 'email'],
                'sortableAttributes' => ['name', 'created_at'],
                'searchableAttributes' => ['name', 'email'],
                'displayedAttributes' => ['name', 'email'],
            ],
            'clients' => [
                'filterableAttributes' => ['tags', 'archived_at', 'user_id'],
                'sortableAttributes' => ['company_name', 'created_at'],
                'searchableAttributes' => ['company_name', 'first_name', 'last_name', 'email', 'reference', 'notes'],
                'displayedAttributes' => ['company_name', 'first_name', 'last_name', 'email', 'reference'],
            ],
        ],
    ],

];
