# Expirable Roles Feature

## Overview
This feature allows you to assign roles with expiration dates, similar to the existing expirable permissions feature. When a role expires, the user will no longer have that role and all permissions associated with it.

## Configuration

Enable expirable roles in your `config/permissions.php`:

```php
'expirable_roles' => [
    'enabled' => env('PERMISSION_EXPIRABLE_ROLES_ENABLED', false),
],
```

Or set it in your `.env` file:
```
PERMISSION_EXPIRABLE_ROLES_ENABLED=true
```

## Database Migration

The migration already exists at:
`database/migrations/2024_01_01_000007_add_expires_at_to_pivot_tables.php`

This migration adds the `expires_at` column to both `model_has_roles` and `model_has_permissions` tables.

Run the migration if you haven't already:
```bash
php artisan migrate
```

## Usage

### Assign Role with Expiration

```php
use Carbon\Carbon;

// Assign a role that expires in 30 days
$user->assignRoleUntil('premium', now()->addDays(30));

// Assign a role that expires on a specific date
$user->assignRoleUntil('editor', Carbon::parse('2025-12-31'));

// Using role ID
$user->assignRoleUntil(1, now()->addMonth());

// Using Role model
$role = Role::where('slug', 'moderator')->first();
$user->assignRoleUntil($role, now()->addWeeks(2));
```

### Checking Roles

All existing role-checking methods automatically filter out expired roles when `expirable_roles` is enabled:

```php
// These methods automatically exclude expired roles
$user->hasRole('admin');
$user->hasAllRoles(['editor', 'author']);
$user->hasAnyRole(['admin', 'moderator']);

// Query scopes also filter expired roles
User::role('admin')->get();
User::withoutRole('banned')->get();
```

### Checking Permissions

When checking permissions, the system now:
1. Filters out expired direct permissions
2. Filters out expired roles
3. Only considers permissions from active (non-expired) roles

```php
// This checks both direct permissions and permissions from active roles
$user->hasPermission('posts.create');
$user->hasAllPermissions(['posts.create', 'posts.edit']);
```

## How It Works

### 1. Role Relationship
The `roles()` relationship now includes the `expires_at` pivot column:
```php
public function roles(): BelongsToMany
{
    $relation = $this->morphToMany(/*...*/)
        ->withTimestamps();
    
    if (config('permissions.expirable_roles.enabled', false)) {
        $relation->withPivot('expires_at');
    }
    
    return $relation;
}
```

### 2. Active Roles Filtering
The `getActiveRoles()` method filters out expired roles:
```php
protected function getActiveRoles()
{
    if (!config('permissions.expirable_roles.enabled', false)) {
        return $this->roles;
    }
    
    return $this->roles()
        ->where(function ($query) {
            $query->whereNull('model_has_roles.expires_at')
                  ->orWhere('model_has_roles.expires_at', '>', now());
        })->get();
}
```

### 3. Updated Methods
The following methods now use `getActiveRoles()` instead of `$this->roles`:
- `hasRole()` - Checks if user has a specific role
- `hasPermission()` - Checks permissions from active roles only
- `getAllPermissions()` - Gets permissions from active roles only
- `isSuperAdmin()` - Uses `hasRole()` which checks active roles
- `scopeRole()` - Query scope filters expired roles
- `scopeWithoutRole()` - Query scope filters expired roles

## Examples

### Temporary Access
```php
// Give user premium access for 1 month trial
$user->assignRoleUntil('premium', now()->addMonth());

// After 1 month, hasRole('premium') will return false automatically
```

### Seasonal Roles
```php
// Assign a seasonal moderator role
$user->assignRoleUntil('summer-moderator', Carbon::parse('2025-09-01'));
```

### Time-Limited Promotions
```php
// Temporary promotion to editor
$user->assignRoleUntil('editor', now()->addMonths(6));
```

### Combining Permanent and Temporary Roles
```php
// User can have both permanent and temporary roles
$user->assignRole('member'); // Permanent
$user->assignRoleUntil('premium', now()->addMonth()); // Temporary

// After expiration, user will still have 'member' but not 'premium'
```

## Important Notes

1. **Backward Compatibility**: When `expirable_roles` is disabled in config, the feature has no effect and all roles work as before.

2. **Null Expiration**: Roles with `expires_at = null` never expire (permanent roles).

3. **Cache**: The permission cache system automatically works with expirable roles. When roles expire, they won't be included in cached results.

4. **Performance**: The filtering is done at the database level for optimal performance.

5. **Existing Roles**: Existing roles without `expires_at` will continue to work as permanent roles.

## Migration from Non-Expirable Roles

If you already have roles assigned and want to enable this feature:

1. Run the migration to add the `expires_at` column
2. Enable the feature in config
3. All existing roles will have `expires_at = null` (permanent)
4. Use `assignRoleUntil()` for new temporary role assignments

## API Reference

### Methods Added

- **`assignRoleUntil($role, $expiresAt)`** - Assign a role with expiration date
- **`getActiveRoles()`** - Get only non-expired roles (protected method)

### Methods Updated

All role-checking methods now filter expired roles when the feature is enabled.
