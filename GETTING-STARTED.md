# Getting Started Checklist

Follow this checklist to get your Laravel Permissions package up and running.

## ✅ Pre-Installation Checklist

-   [ ] Laravel 11 or 12 installed
-   [ ] PHP 8.2 or higher
-   [ ] Database configured (MySQL recommended)
-   [ ] Composer available

## ✅ Installation Steps

### Step 1: Configure Composer

-   [ ] Open your main Laravel project's `composer.json`
-   [ ] Add repository configuration:

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

-   [ ] Run `composer update`

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --tag=permissions-config
```

-   [ ] Config file created at `config/permissions.php`

### Step 3: Environment Configuration

-   [ ] Add to your `.env` file:

```env
PERMISSION_DB_CONNECTION=mysql
PERMISSION_DB_NAME=laravel_permission
PERMISSION_CACHE_ENABLED=true
PERMISSION_CACHE_EXPIRATION=3600
```

### Step 4: Database Setup

-   [ ] Create database:

```sql
CREATE DATABASE laravel_permission CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

-   [ ] Or configure to use existing database in `config/permissions.php`

### Step 5: Run Migrations

```bash
php artisan migrate
```

-   [ ] Verify 5 tables created:
    -   [ ] `roles`
    -   [ ] `permissions`
    -   [ ] `role_has_permissions`
    -   [ ] `model_has_roles`
    -   [ ] `model_has_permissions`

### Step 6: Update User Model

-   [ ] Open `app/Models/User.php`
-   [ ] Add trait:

```php
use Saeedvir\LaravelPermissions\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
    // ...
}
```

## ✅ First Usage

### Create Your First Role & Permission

```php
use Saeedvir\LaravelPermissions\Models\Role;
use Saeedvir\LaravelPermissions\Models\Permission;

// Create a permission
$permission = Permission::create([
    'name' => 'Create Post',
    'slug' => 'create-post',
    'description' => 'Can create blog posts'
]);

// Create a role
$admin = Role::create([
    'name' => 'Administrator',
    'slug' => 'admin',
    'description' => 'Full system access'
]);

// Assign permission to role
$admin->givePermissionTo('create-post');
```

### Assign Role to User

```php
$user = User::find(1);
$user->assignRole('admin');
```

### Test in Tinker

```bash
php artisan tinker
```

```php
$user = User::find(1);
$user->assignRole('admin');
$user->hasRole('admin'); // Should return true
```

## ✅ Protect Your First Route

### Add Middleware to Route

In `routes/web.php`:

```php
Route::get('/admin', function () {
    return 'Welcome Admin!';
})->middleware('role:admin');

Route::post('/posts', function () {
    return 'Post created!';
})->middleware('permission:create-post');
```

### Test Routes

-   [ ] Try accessing `/admin` without being logged in → Should redirect/block
-   [ ] Login as user with admin role → Should allow access
-   [ ] Login as user without admin role → Should block

## ✅ Use in Blade Templates

Create a test blade file:

```blade
@extends('layouts.app')

@section('content')
    <h1>Dashboard</h1>

    @role('admin')
        <a href="/admin/users" class="btn btn-primary">
            Manage Users (Admin Only)
        </a>
    @endrole

    @permission('create-post')
        <a href="/posts/create" class="btn btn-success">
            Create New Post
        </a>
    @endpermission

    @hasanypermission(['edit-post', 'delete-post'])
        <a href="/posts" class="btn btn-warning">
            Manage Posts
        </a>
    @endhasanypermission
@endsection
```

## ✅ Use in Controllers

```php
class PostController extends Controller
{
    public function store(Request $request)
    {
        // Check permission in controller
        if (!auth()->user()->hasPermission('create-post')) {
            abort(403, 'Unauthorized');
        }

        // Create post logic...
        return response()->json(['message' => 'Post created']);
    }

    public function destroy($id)
    {
        $user = auth()->user();

        // Multiple checks
        if (!$user->hasRole('admin') && !$user->hasPermission('delete-post')) {
            abort(403, 'Unauthorized');
        }

        // Delete post logic...
        return response()->json(['message' => 'Post deleted']);
    }
}
```

## ✅ Verification Tests

### Test 1: Role Assignment

```php
$user = User::factory()->create();
$user->assignRole('admin');
$this->assertTrue($user->hasRole('admin'));
```

### Test 2: Permission Assignment

```php
$user = User::factory()->create();
$user->givePermissionTo('create-post');
$this->assertTrue($user->hasPermission('create-post'));
```

### Test 3: Role with Permissions

```php
$role = Role::create(['name' => 'Editor', 'slug' => 'editor']);
$role->givePermissionTo('create-post', 'edit-post');
$user = User::factory()->create();
$user->assignRole('editor');
$this->assertTrue($user->hasPermission('create-post'));
$this->assertTrue($user->hasPermission('edit-post'));
```

### Test 4: Cache Verification

```php
// First call - hits database
$user->hasPermission('create-post');

// Second call - should use cache
$user->hasPermission('create-post');

// Verify cache is working
$cache = app(\Saeedvir\LaravelPermissions\Services\PermissionCache::class);
$key = $cache->getUserPermissionsKey($user->id);
$cached = $cache->get($key);
$this->assertNotNull($cached);
```

## ✅ Common Patterns

### Pattern 1: Admin Setup Seeder

Create `database/seeders/AdminSeeder.php`:

```php
public function run()
{
    $admin = Role::firstOrCreate(['slug' => 'admin'], [
        'name' => 'Administrator',
    ]);

    $permissions = ['create-post', 'edit-post', 'delete-post', 'manage-users'];

    foreach ($permissions as $slug) {
        $permission = Permission::firstOrCreate(['slug' => $slug], [
            'name' => ucwords(str_replace('-', ' ', $slug)),
        ]);
        $admin->givePermissionTo($slug);
    }

    $user = User::where('email', 'admin@example.com')->first();
    if ($user) {
        $user->assignRole('admin');
    }
}
```

Run: `php artisan db:seed --class=AdminSeeder`

### Pattern 2: Resource Controller Protection

```php
class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-post')->only(['index', 'show']);
        $this->middleware('permission:create-post')->only(['create', 'store']);
        $this->middleware('permission:edit-post')->only(['edit', 'update']);
        $this->middleware('permission:delete-post')->only(['destroy']);
    }
}
```

### Pattern 3: Dynamic Permission Check

```php
if (auth()->user()->hasAnyPermission(['edit-own-post', 'edit-all-posts'])) {
    // Can edit posts
}

if (auth()->user()->hasAllPermissions(['create-post', 'publish-post'])) {
    // Can create and publish
}
```

## ✅ Troubleshooting

### Issue: "Class not found"

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Issue: Migrations not running

```bash
php artisan migrate:status
php artisan migrate --path=vendor/saeedvir/laravel-permissions/database/migrations
```

### Issue: Middleware not working

```bash
php artisan route:list
php artisan config:cache
```

### Issue: Cache not working

```bash
# Check cache driver
php artisan tinker
>>> config('cache.default')

# Clear all caches
php artisan cache:clear
php artisan config:clear
```

### Issue: Blade directives not working

```bash
php artisan view:clear
php artisan config:clear
```

## ✅ Next Steps

-   [ ] Read full documentation in [README.md](README.md)
-   [ ] Check [examples/](examples/) folder for more use cases
-   [ ] Configure middleware responses in `config/permissions.php`
-   [ ] Set up your role/permission structure
-   [ ] Create seeders for initial data
-   [ ] Add tests for your permission logic
-   [ ] Configure cache settings for production

## ✅ Production Checklist

Before deploying to production:

-   [ ] Cache enabled in `.env`: `PERMISSION_CACHE_ENABLED=true`
-   [ ] Proper cache driver configured (Redis recommended)
-   [ ] All roles and permissions seeded
-   [ ] Middleware configured on all protected routes
-   [ ] User authentication working
-   [ ] Cache clearing strategy in place
-   [ ] Error handling configured
-   [ ] Logging enabled for permission denials

## 📚 Documentation Links

-   **Full Documentation**: [README.md](README.md)
-   **Installation Guide**: [INSTALLATION.md](INSTALLATION.md)
-   **Quick Start**: [QUICKSTART.md](QUICKSTART.md)
-   **Package Summary**: [PACKAGE-SUMMARY.md](PACKAGE-SUMMARY.md)
-   **Structure**: [STRUCTURE.md](STRUCTURE.md)

## 🆘 Need Help?

-   Email: saeed.es91@gmail.com
-   Check the examples folder for working code
-   Review the test cases in the documentation

---

**Happy Coding! 🚀**
