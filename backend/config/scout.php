<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search connection that gets used while
    | using Laravel Scout. This connection is used when syncing and querying
    | for search records. You should specify the name of the connection.
    |
    | Supported: "algolia", "meilisearch", "database", "collection", "null"
    |
    */

    'driver' => env('SCOUT_DRIVER', 'meilisearch'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Here you may specify a prefix that will be applied to all search index
    | names used by Scout. This prefix may be useful if you have multiple
    | "tenants" or applications sharing the same search infrastructure.
    |
    */

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that sync your data
    | with your search indexes are performed in sync with the model saving
    | or queued for execution later. This is recommended for performance.
    |
    */

    'queue' => [
        'connection' => env('SCOUT_QUEUE_CONNECTION', 'redis'),
        'queue' => env('SCOUT_QUEUE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Transactions
    |--------------------------------------------------------------------------
    |
    | This configuration option determines if your data will only be synced
    | to your search indexes after every open database transaction has been
    | committed, thus preventing any discarded data from syncing.
    |
    */

    'after_commit' => false,

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This configuration option determines if Scout will preserve soft deleted
    | models in the search index. When enabled, you may search for soft deleted
    | records. To configure the soft delete behavior for a model, use the
    | `soft_delete` static property on the model.
    |
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Identify Users
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if Scout will identify the user
    | performing the search. This is useful for logging and auditing
    | purposes. The identification is done using the authenticated user.
    |
    */

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Algolia settings. Algolia is a cloud hosted
    | search engine which works great with Scout.
    |
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Meilisearch settings. Meilisearch is an
    | open-source search engine with great typo tolerance and fast search.
    |
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://meilisearch:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            'clients' => [
                'searchableAttributes' => [
                    'name',
                    'email',
                    'reference',
                    'notes',
                    'tags',
                ],
                'filterableAttributes' => [
                    'user_id',
                    'tags',
                    'deleted_at',
                    'status',
                ],
                'sortableAttributes' => [
                    'name',
                    'created_at',
                ],
            ],
            'projects' => [
                'searchableAttributes' => [
                    'reference',
                    'name',
                    'description',
                ],
                'filterableAttributes' => [
                    'user_id',
                    'client_id',
                    'status',
                    'billing_type',
                ],
                'sortableAttributes' => [
                    'name',
                    'deadline',
                    'created_at',
                ],
            ],
            'tasks' => [
                'searchableAttributes' => [
                    'title',
                    'description',
                ],
                'filterableAttributes' => [
                    'project_id',
                    'user_id',
                    'status',
                    'priority',
                ],
                'sortableAttributes' => [
                    'due_date',
                    'created_at',
                ],
            ],
            'invoices' => [
                'searchableAttributes' => [
                    'number',
                    'client_name',
                    'notes',
                ],
                'filterableAttributes' => [
                    'user_id',
                    'client_id',
                    'status',
                ],
                'sortableAttributes' => [
                    'issue_date',
                    'total',
                    'created_at',
                ],
            ],
            'quotes' => [
                'searchableAttributes' => [
                    'number',
                    'client_name',
                    'notes',
                ],
                'filterableAttributes' => [
                    'user_id',
                    'client_id',
                    'status',
                ],
                'sortableAttributes' => [
                    'issue_date',
                    'valid_until',
                    'total',
                    'created_at',
                ],
            ],
            'credit_notes' => [
                'searchableAttributes' => [
                    'number',
                    'client_name',
                    'invoice_number',
                    'reason',
                ],
                'filterableAttributes' => [
                    'user_id',
                    'client_id',
                    'invoice_id',
                    'status',
                ],
                'sortableAttributes' => [
                    'issue_date',
                    'total',
                    'created_at',
                ],
            ],
            'leads' => [
                'searchableAttributes' => [
                    'company_name',
                    'first_name',
                    'last_name',
                    'email',
                    'notes',
                ],
                'filterableAttributes' => [
                    'user_id',
                    'status',
                    'source',
                    'currency',
                    'expected_close_date',
                    'converted_at',
                ],
                'sortableAttributes' => [
                    'estimated_value',
                    'expected_close_date',
                    'pipeline_position',
                    'created_at',
                ],
            ],
            'expenses' => [
                'searchableAttributes' => [
                    'description',
                    'vendor',
                    'reference',
                    'notes',
                ],
                'filterableAttributes' => [
                    'user_id',
                    'expense_category_id',
                    'project_id',
                    'client_id',
                    'status',
                    'is_billable',
                ],
                'sortableAttributes' => [
                    'date',
                    'amount',
                    'created_at',
                ],
            ],
            'documents' => [
                'searchableAttributes' => [
                    'title',
                    'original_filename',
                    'tags',
                ],
                'filterableAttributes' => [
                    'user_id',
                    'client_id',
                    'document_type',
                    'script_language',
                ],
                'sortableAttributes' => [
                    'created_at',
                    'title',
                    'file_size',
                ],
            ],
        ],
    ],

];
