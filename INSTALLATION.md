# Installation Guide

Complete step-by-step installation guide for Laravel Permissions Package.

## Requirements

-   PHP 8.2 or higher
-   Laravel 11.x or 12.x
-   Composer

## Installation Steps

### Step 1: Add Package Repository

Add the package to your main Laravel project's `composer.json`:

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

### Step 2: Install Package

Run composer update:

```bash
composer update saeedvir/laravel-permissions
```

Or install fresh:

```bash
composer install
```

### Step 3: Publish Configuration File

Publish the package configuration:

```bash
php artisan vendor:publish --tag=permissions-config
```

This will create `config/permissions.php` in your Laravel application.

### Step 4: Configure Environment Variables

Add these variables to your `.env` file:

```env
# Permission Package Configuration
PERMISSION_DB_CONNECTION=mysql
PERMISSION_DB_NAME=laravel_permission
PERMISSION_CACHE_ENABLED=true
PERMISSION_CACHE_EXPIRATION=3600
PERMISSION_CACHE_STORE=default
```

#### Environment Variables Explained:

-   `PERMISSION_DB_CONNECTION`: The database connection to use (default: mysql)
-   `PERMISSION_DB_NAME`: The database name where permission tables will be stored
-   `PERMISSION_CACHE_ENABLED`: Enable/disable caching (true/false)
-   `PERMISSION_CACHE_EXPIRATION`: Cache expiration time in seconds (default: 3600)
-   `PERMISSION_CACHE_STORE`: Cache store to use (default: default)

### Step 5: Create Database

Create the database specified in `PERMISSION_DB_NAME`:

```sql
CREATE DATABASE laravel_permission CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Or update your database configuration in `config/database.php` to use the existing database.

### Step 6: Configure Database Connection (Optional)

If you want to use a separate database, update `config/database.php`:

```php
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'laravel'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        // ... other settings
    ],

    // Add this if you want a separate connection for permissions
    'permission_mysql' => [
        'driver' => 'mysql',
        'host' => env('PERMISSION_DB_HOST', '127.0.0.1'),
        'port' => env('PERMISSION_DB_PORT', '3306'),
        'database' => env('PERMISSION_DB_NAME', 'laravel_permission'),
        'username' => env('PERMISSION_DB_USERNAME', 'root'),
        'password' => env('PERMISSION_DB_PASSWORD', ''),
        // ... other settings
    ],
],
```

Then update your `.env`:

```env
PERMISSION_DB_CONNECTION=permission_mysql
PERMISSION_DB_HOST=127.0.0.1
PERMISSION_DB_PORT=3306
PERMISSION_DB_NAME=laravel_permission
PERMISSION_DB_USERNAME=root
PERMISSION_DB_PASSWORD=
```

### Step 7: Run Migrations

#### Option A: Use Package Migrations Directly

```bash
php artisan migrate
```

#### Option B: Publish and Customize Migrations

```bash
php artisan vendor:publish --tag=permissions-migrations
php artisan migrate
```

### Step 8: Add Trait to User Model

Update your `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Saeedvir\LaravelPermissions\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use Notifiable, HasRolesAndPermissions;

    // ... rest of your User model
}
```

### Step 9: Register Middlewares in Kernel (Optional for Laravel 11+)

For Laravel 11+, middlewares are automatically registered. For older versions, add to `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ... other middlewares
    'role' => \Saeedvir\LaravelPermissions\Middleware\CheckRole::class,
    'permission' => \Saeedvir\LaravelPermissions\Middleware\CheckPermission::class,
    'check.auth' => \Saeedvir\LaravelPermissions\Middleware\CheckAuth::class,
];
```

### Step 10: Seed Initial Data (Optional)

Create a seeder in your application to add initial roles and permissions:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Saeedvir\LaravelPermissions\Models\Role;
use Saeedvir\LaravelPermissions\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
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

        // Create roles
        $admin = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'Administrator with full access'
        ]);

        $editor = Role::create([
            'name' => 'Editor',
            'slug' => 'editor',
            'description' => 'Editor with limited access'
        ]);

        // Assign permissions to roles
        $admin->givePermissionTo($createPost, $editPost);
        $editor->givePermissionTo($createPost);

        // Assign role to user
        $user = User::find(1);
        if ($user) {
            $user->assignRole('admin');
        }
    }
}
```

Run the seeder:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

## Verification

### Test Installation

Create a test route in `routes/web.php`:

```php
use Illuminate\Support\Facades\Route;

Route::get('/test-permissions', function () {
    $user = auth()->user();

    if (!$user) {
        return 'Please login first';
    }

    return [
        'user' => $user->name,
        'roles' => $user->roles->pluck('name'),
        'permissions' => $user->getAllPermissions()->pluck('name'),
    ];
})->middleware('auth');
```

### Clear Caches

If you encounter any issues, clear all caches:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

## Configuration Options

### Cache Configuration

Edit `config/permissions.php`:

```php
'cache' => [
    'enabled' => env('PERMISSION_CACHE_ENABLED', true),
    'expiration_time' => env('PERMISSION_CACHE_EXPIRATION', 3600),
    'key_prefix' => 'saeedvir_permissions',
    'store' => env('PERMISSION_CACHE_STORE', 'default'),
],
```

### Middleware Response Configuration

Configure how the middleware handles unauthorized access:

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

## Troubleshooting

### Issue: Tables not created

**Solution**: Make sure the database exists and the connection is properly configured in `.env`.

```bash
php artisan migrate:status
php artisan migrate --force
```

### Issue: Class not found

**Solution**: Clear composer autoload:

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Issue: Blade directives not working

**Solution**: Clear view cache:

```bash
php artisan view:clear
```

### Issue: Permissions not cached

**Solution**: Make sure cache is enabled and working:

```bash
php artisan cache:clear
php artisan config:cache
```

Check if Redis or your cache driver is running properly.

### Issue: Middleware not working

**Solution**: For Laravel 11+, make sure the service provider is registered. Check `bootstrap/providers.php` or run:

```bash
php artisan about
```

## Uninstallation

If you need to uninstall the package:

1. Remove from composer.json
2. Drop tables from database:

```sql
DROP TABLE IF EXISTS model_has_permissions;
DROP TABLE IF EXISTS model_has_roles;
DROP TABLE IF EXISTS role_has_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
```

3. Remove config file:

```bash
rm config/permissions.php
```

4. Update composer:

```bash
composer update
```

## Next Steps

After installation, check out:

-   [README.md](README.md) for usage examples
-   Configure middleware responses
-   Set up roles and permissions
-   Test with your User model

## Support

For issues or questions:

-   Email: saeed.es91@gmail.com
-   GitHub: https://github.com/saeedvir/laravel-permissions
