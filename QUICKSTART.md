# Quick Start Guide

Get started with Laravel Permissions in 5 minutes!

## 1. Install Package

Add to your main `composer.json`:

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

Run:
```bash
composer update
```

## 2. Publish Config & Update .env

```bash
php artisan vendor:publish --tag=permissions-config
```

Add to `.env`:
```env
PERMISSION_DB_NAME=laravel_permission
PERMISSION_CACHE_ENABLED=true
```

## 3. Run Migrations

```bash
php artisan migrate
```

## 4. Add Trait to User Model

```php
use Saeedvir\LaravelPermissions\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
}
```

## 5. Create Roles & Permissions

```php
use Saeedvir\LaravelPermissions\Models\Role;
use Saeedvir\LaravelPermissions\Models\Permission;

// Create permissions
$createPost = Permission::create([
    'name' => 'Create Post',
    'slug' => 'create-post'
]);

// Create role
$admin = Role::create([
    'name' => 'Administrator',
    'slug' => 'admin'
]);

// Assign permission to role
$admin->givePermissionTo('create-post');

// Assign role to user
$user = User::find(1);
$user->assignRole('admin');
```

## 6. Protect Routes

```php
// Check role
Route::get('/admin', fn() => 'Admin Area')
    ->middleware('role:admin');

// Check permission
Route::post('/posts', [PostController::class, 'store'])
    ->middleware('permission:create-post');
```

## 7. Use in Blade

```blade
@role('admin')
    <a href="/admin">Admin Panel</a>
@endrole

@permission('create-post')
    <a href="/posts/create">Create Post</a>
@endpermission
```

## 8. Check in Code

```php
if ($user->hasRole('admin')) {
    // User is admin
}

if ($user->hasPermission('create-post')) {
    // User can create posts
}
```

## That's it! 🎉

Check [README.md](README.md) for full documentation.
