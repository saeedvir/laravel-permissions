[![Latest Version](https://img.shields.io/packagist/v/saeedvir/laravel-permissions.svg?style=flat-square)](https://packagist.org/packages/saeedvir/laravel-permissions)
[![Total Downloads](https://img.shields.io/packagist/dt/saeedvir/laravel-permissions.svg?style=flat-square)](https://packagist.org/packages/saeedvir/laravel-permissions)
[![License](https://img.shields.io/packagist/l/saeedvir/laravel-permissions.svg?style=flat-square)](https://packagist.org/packages/saeedvir/laravel-permissions)

# Laravel Permissions Package

A highly optimized role and permission package for Laravel 11/12 with caching support and flexible configuration.

## Features

-   ✅ **Role-based Access Control (RBAC)**
-   ✅ **Permission Management**
-   ✅ **Direct User Permissions**
-   ✅ **Configurable Caching** for optimal performance
-   ✅ **Multiple Database Support**
-   ✅ **Middleware Protection**
-   ✅ **Memory Optimized**
-   ✅ **Eager Loading Support**
-   ✅ **Laravel 11/12 Compatible**

## Installation

### Step 1: Add Package to Composer

Add the package to your main project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/saeedvir/laravel-permissions"
        }
    ],
    "require": {
        "saeedvir/laravel-permissions": "*"
    }
}
```

Then run:

```bash
composer update
```

### Step 2: Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=permissions-config
```

### Step 3: Configure Database

Update your `.env` file:

```env
PERMISSION_DB_CONNECTION=mysql
PERMISSION_DB_NAME=laravel_permission
PERMISSION_CACHE_ENABLED=true
PERMISSION_CACHE_EXPIRATION=3600
```

### Step 4: Configure Database Connection

Update `config/permissions.php` to set your database connection properly.

### Step 5: Run Migrations

```bash
php artisan migrate
```

Or publish and customize migrations first:

```bash
php artisan vendor:publish --tag=permissions-migrations
php artisan migrate
```

## Setup

### Add Trait to User Model

Add the `HasRolesAndPermissions` trait to your User model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Saeedvir\LaravelPermissions\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;

    // ... rest of your User model
}
```

## Usage

### Creating Roles and Permissions

```php
use Saeedvir\LaravelPermissions\Models\Role;
use Saeedvir\LaravelPermissions\Models\Permission;

// Create roles
$admin = Role::create([
    'name' => 'Administrator',
    'slug' => 'admin',
    'description' => 'Administrator role with full access'
]);

$editor = Role::create([
    'name' => 'Editor',
    'slug' => 'editor',
    'description' => 'Editor role'
]);

// Create permissions
$createPost = Permission::create([
    'name' => 'Create Post',
    'slug' => 'create-post',
    'description' => 'Can create posts'
]);

$editPost = Permission::create([
    'name' => 'Edit Post',
    'slug' => 'edit-post',
    'description' => 'Can edit posts'
]);

$deletePost = Permission::create([
    'name' => 'Delete Post',
    'slug' => 'delete-post',
    'description' => 'Can delete posts'
]);
```

### Assigning Permissions to Roles

```php
// Give permissions to role
$admin->givePermissionTo('create-post', 'edit-post', 'delete-post');
$editor->givePermissionTo('create-post', 'edit-post');

// Or using Permission models
$admin->givePermissionTo($createPost, $editPost, $deletePost);

// Revoke permission
$editor->revokePermissionTo('edit-post');

// Sync permissions (removes old, adds new)
$editor->syncPermissions(['create-post']);
```

### Assigning Roles to Users

```php
$user = User::find(1);

// Assign role
$user->assignRole('admin');

// Assign multiple roles
$user->assignRole('admin', 'editor');

// Or using Role models
$user->assignRole($admin, $editor);

// Remove role
$user->removeRole('editor');

// Sync roles (removes old, adds new)
$user->syncRoles(['admin']);
```

### Giving Direct Permissions to Users

```php
$user = User::find(1);

// Give direct permission to user
$user->givePermissionTo('create-post');

// Give multiple permissions
$user->givePermissionTo('create-post', 'edit-post');

// Revoke permission
$user->revokePermissionTo('edit-post');

// Sync permissions
$user->syncPermissions(['create-post']);
```

### Checking Roles and Permissions

```php
$user = User::find(1);

// Check if user has role
if ($user->hasRole('admin')) {
    // User is admin
}

// Check multiple roles (any)
if ($user->hasAnyRole(['admin', 'editor'])) {
    // User has at least one of these roles
}

// Check multiple roles (all)
if ($user->hasAllRoles(['admin', 'editor'])) {
    // User has all these roles
}

// Check permission (includes permissions from roles)
if ($user->hasPermission('create-post')) {
    // User can create posts
}

// Check multiple permissions (any)
if ($user->hasAnyPermission(['create-post', 'edit-post'])) {
    // User has at least one of these permissions
}

// Check multiple permissions (all)
if ($user->hasAllPermissions(['create-post', 'edit-post'])) {
    // User has all these permissions
}

// Get all user permissions (direct + from roles)
$permissions = $user->getAllPermissions();
```

### Using Blade Directives (Optional - needs implementation)

```blade
@role('admin')
    <p>You are an administrator!</p>
@endrole

@hasrole('admin')
    <p>You are an administrator!</p>
@endhasrole

@permission('create-post')
    <a href="/posts/create">Create Post</a>
@endpermission

@haspermission('create-post')
    <a href="/posts/create">Create Post</a>
@endhaspermission
```

## Middleware Usage

The package provides three middlewares:

### 1. CheckAuth Middleware

Checks if user is authenticated:

```php
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('check.auth');
```

### 2. CheckRole Middleware

Checks if user has specific role(s):

```php
// Single role
Route::get('/admin', function () {
    return view('admin.dashboard');
})->middleware('role:admin');

// Multiple roles (user needs at least one)
Route::get('/admin', function () {
    return view('admin.dashboard');
})->middleware('role:admin|super-admin');

// In route groups
Route::middleware(['role:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/settings', [SettingController::class, 'index']);
});
```

### 3. CheckPermission Middleware

Checks if user has specific permission(s):

```php
// Single permission
Route::post('/posts', [PostController::class, 'store'])
    ->middleware('permission:create-post');

// Multiple permissions (user needs at least one)
Route::put('/posts/{post}', [PostController::class, 'update'])
    ->middleware('permission:edit-post|edit-own-post');

// In route groups
Route::middleware(['permission:manage-posts'])->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
});
```

### Combining Middlewares

```php
Route::middleware(['check.auth', 'role:admin', 'permission:delete-post'])
    ->delete('/posts/{post}', [PostController::class, 'destroy']);
```

## Configuration

### Cache Settings

Control caching behavior in `config/permissions.php`:

```php
'cache' => [
    'enabled' => env('PERMISSION_CACHE_ENABLED', true),
    'expiration_time' => env('PERMISSION_CACHE_EXPIRATION', 3600), // in seconds
    'key_prefix' => 'saeedvir_permissions',
    'store' => env('PERMISSION_CACHE_STORE', 'default'),
],
```

### Middleware Responses

Configure unauthorized/unauthenticated responses:

```php
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
```

### Performance Settings

```php
'performance' => [
    'eager_loading' => true, // Enable eager loading for relationships
    'chunk_size' => 1000, // Chunk size for batch operations
],
```

## Cache Management

### Manual Cache Clearing

```php
use Saeedvir\LaravelPermissions\Services\PermissionCache;

$cache = app(PermissionCache::class);

// Clear specific user cache
$cache->clearUserCache($userId);

// Clear specific role cache
$cache->clearRoleCache($roleId);

// Flush all permission caches
$cache->flush();
```

### Automatic Cache Clearing

The package automatically clears relevant caches when:

-   Roles are assigned/removed from users
-   Permissions are assigned/removed from users or roles
-   Roles or permissions are deleted or updated

## Database Structure

### Tables Created

1. **roles** - Stores role definitions
2. **permissions** - Stores permission definitions
3. **role_has_permissions** - Pivot table for role-permission relationships
4. **model_has_roles** - Polymorphic pivot table for user-role relationships
5. **model_has_permissions** - Polymorphic pivot table for user-permission relationships

## Performance Optimization Tips

1. **Enable Caching**: Set `PERMISSION_CACHE_ENABLED=true` in `.env`
2. **Use Eager Loading**: The package uses eager loading when checking permissions from roles
3. **Database Indexing**: All tables have proper indexes for fast queries
4. **Use Slugs**: Always use slugs (strings) instead of IDs for better cache utilization
5. **Chunk Large Operations**: Use the configured chunk size for batch operations

## Testing

```bash
composer test
```

## Security

If you discover any security-related issues, please email saeed.es91@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see License File for more information.

## Credits

-   [Saeedvir](https://github.com/saeedvir)

## Support

For support, please open an issue on GitHub or contact saeed.es91@gmail.com
