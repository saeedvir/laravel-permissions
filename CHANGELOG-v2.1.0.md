# Version 2.1.0 - Expirable Roles Feature

## 🎉 New Feature

### ⏰ Expirable Roles Support

Added the ability to assign roles with expiration dates, similar to the existing expirable permissions feature.

#### What's New

- **`assignRoleUntil($role, $expiresAt)`** - Assign roles with expiration dates
- **Automatic expiration filtering** - All role checks automatically exclude expired roles
- **Permission inheritance** - Permissions from expired roles are automatically revoked
- **Cache-aware** - Works seamlessly with the existing caching system
- **Query scope support** - `User::role('admin')` automatically filters expired roles

#### Configuration

```php
'expirable_roles' => [
    'enabled' => env('PERMISSION_EXPIRABLE_ROLES_ENABLED', false),
],
```

#### Usage Examples

```php
// Assign temporary roles
$user->assignRoleUntil('premium', now()->addMonth());
$user->assignRoleUntil('trial-user', now()->addDays(7));
$user->assignRoleUntil('seasonal-mod', Carbon::parse('2025-12-31'));

// All checks automatically filter expired roles
$user->hasRole('premium'); // Returns false after expiration
$user->hasPermission('premium-feature'); // Also checks role expiration
User::role('premium')->get(); // Only active premium users
```

## 🔧 Implementation Details

### Files Modified

1. **config/permissions.php** - Added `expirable_roles` configuration
2. **src/Traits/HasRolesAndPermissions.php** - Updated all role-related methods:
   - Updated `roles()` relationship to include `expires_at` pivot
   - Added `assignRoleUntil()` method for temporary role assignment
   - Added `getActiveRoles()` helper method to filter expired roles
   - Updated `hasRole()` to use active roles only
   - Updated `hasPermission()` to check permissions from active roles only
   - Updated `getAllPermissions()` to include only active role permissions
   - Updated `scopeRole()` and `scopeWithoutRole()` to filter expired roles

### Database Schema

The migration already exists: `2024_01_01_000007_add_expires_at_to_pivot_tables.php`

The `model_has_roles` table includes:
- `expires_at` (nullable timestamp) - When null, role never expires

## 📚 Documentation

- Updated **README.md** with expirable roles examples
- Created **EXPIRABLE-ROLES.md** with comprehensive documentation
- Added usage examples and configuration details

## 🔄 Backward Compatibility

- Feature is **opt-in** via configuration (disabled by default)
- Existing roles continue to work as permanent roles
- No breaking changes to existing API
- Roles with `expires_at = null` never expire

## 🎯 Benefits

1. **Temporary Access Control** - Perfect for trial periods, seasonal roles
2. **Automatic Cleanup** - No manual intervention needed
3. **Consistent Behavior** - Works exactly like expirable permissions
4. **Performance Optimized** - Database-level filtering
5. **Cache Compatible** - Integrates with existing cache system

## Version Info

- **Version**: 2.1.0
- **Release Date**: November 6, 2025
- **Laravel Compatibility**: 11.x, 12.x
- **PHP Requirement**: 8.2+
