<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection to be used for permission tables.
    |
    */
    'database_connection' => env('PERMISSION_DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Name
    |--------------------------------------------------------------------------
    |
    | The database name where permission tables are stored.
    |
    */
    'database_name' => env('PERMISSION_DB_NAME', 'laravel_permission'),

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | The table names used by the package.
    |
    */
    'tables' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_roles' => 'model_has_roles',
        'model_has_permissions' => 'model_has_permissions',
        'role_has_permissions' => 'role_has_permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Enable/disable caching and set cache expiration time.
    |
    */
    'cache' => [
        'enabled' => env('PERMISSION_CACHE_ENABLED', true),
        'cache_roles' => env('PERMISSION_CACHE_ROLES', true), // Cache roles separately
        'cache_permissions' => env('PERMISSION_CACHE_PERMISSIONS', true), // Cache permissions separately
        'expiration_time' => env('PERMISSION_CACHE_EXPIRATION', 3600), // in seconds
        'key_prefix' => 'saeedvir_permissions',
        'store' => env('PERMISSION_CACHE_STORE', 'default'),
        'use_tags' => env('PERMISSION_CACHE_USE_TAGS', true), // Use cache tags (Redis only)
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Settings
    |--------------------------------------------------------------------------
    |
    | Configure middleware behavior for unauthorized access.
    |
    */
    'middleware' => [
        'unauthorized_response' => [
            'type' => 'json', // 'json', 'redirect', 'abort'
            'redirect_to' => '/unauthorized',
            'abort_code' => 403,
            'json_message' => 'Unauthorized access.',
        ],
        'unauthenticated_response' => [
            'type' => 'redirect', // 'json', 'redirect', 'abort'
            'redirect_to' => '/login',
            'abort_code' => 401,
            'json_message' => 'Unauthenticated.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Settings
    |--------------------------------------------------------------------------
    |
    | Configure the models used by the package.
    |
    */
    'models' => [
        'role' => \Saeedvir\LaravelPermissions\Models\Role::class,
        'permission' => \Saeedvir\LaravelPermissions\Models\Permission::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    |
    | Multiple guard support for different user types.
    |
    */
    'guards' => [
        'enabled' => env('PERMISSION_GUARDS_ENABLED', false),
        'default' => 'web',
    ],

    /*
    |--------------------------------------------------------------------------
    | Wildcard Permissions
    |--------------------------------------------------------------------------
    |
    | Enable wildcard permission matching (e.g., 'posts.*' matches 'posts.create').
    |
    */
    'wildcard_permissions' => [
        'enabled' => env('PERMISSION_WILDCARD_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    |
    | Super admin role that has all permissions.
    |
    */
    'super_admin' => [
        'enabled' => env('PERMISSION_SUPER_ADMIN_ENABLED', false),
        'role_slug' => env('PERMISSION_SUPER_ADMIN_SLUG', 'super-admin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Expirable Permissions
    |--------------------------------------------------------------------------
    |
    | Allow permissions to expire after a certain time.
    |
    */
    'expirable_permissions' => [
        'enabled' => env('PERMISSION_EXPIRABLE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Expirable Roles
    |--------------------------------------------------------------------------
    |
    | Allow roles to expire after a certain time.
    |
    */
    'expirable_roles' => [
        'enabled' => env('PERMISSION_EXPIRABLE_ROLES_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gate Registration
    |--------------------------------------------------------------------------
    |
    | Register permissions with Laravel's Gate.
    |
    */
    'gate' => [
        'enabled' => env('PERMISSION_GATE_ENABLED', true),
        'before_callback' => true, // Run before other gate checks
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Optimize package performance.
    |
    */
    'performance' => [
        'eager_loading' => true, // Enable eager loading for relationships
        'chunk_size' => 1000, // Chunk size for batch operations
        'use_transactions' => env('PERMISSION_USE_TRANSACTIONS', true), // Use database transactions
    ],
];
