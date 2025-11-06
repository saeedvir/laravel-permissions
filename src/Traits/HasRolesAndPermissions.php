<?php

namespace Saeedvir\LaravelPermissions\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Saeedvir\LaravelPermissions\Models\Role;
use Saeedvir\LaravelPermissions\Models\Permission;
use Saeedvir\LaravelPermissions\Services\PermissionCache;

trait HasRolesAndPermissions
{
    /**
     * User belongs to many roles (polymorphic).
     */
    public function roles(): BelongsToMany
    {
        return $this->morphToMany(
            config('permissions.models.role', Role::class),
            'model',
            config('permissions.tables.model_has_roles', 'model_has_roles'),
            'model_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * User belongs to many permissions (direct permissions - polymorphic).
     * IMPROVED: Now supports expirable permissions via pivot.
     */
    public function permissions(): BelongsToMany
    {
        $relation = $this->morphToMany(
            config('permissions.models.permission', Permission::class),
            'model',
            config('permissions.tables.model_has_permissions', 'model_has_permissions'),
            'model_id',
            'permission_id'
        )->withTimestamps();

        // Add expires_at pivot column if expirable permissions are enabled
        if (config('permissions.expirable_permissions.enabled', false)) {
            $relation->withPivot('expires_at');
        }

        return $relation;
    }

    /**
     * Assign role to user.
     */
    public function assignRole(string|int|Role ...$roles): self
    {
        $roleIds = collect($roles)->map(function ($role) {
            return $role instanceof Role 
                ? $role->id 
                : (is_numeric($role) 
                    ? $role 
                    : Role::where('slug', $role)->firstOrFail()->id);
        });

        $this->roles()->syncWithoutDetaching($roleIds);

        // Clear user cache
        app(PermissionCache::class)->clearUserCache($this->id);

        return $this;
    }

    /**
     * Remove role from user.
     */
    public function removeRole(string|int|Role ...$roles): self
    {
        $roleIds = collect($roles)->map(function ($role) {
            return $role instanceof Role 
                ? $role->id 
                : (is_numeric($role) 
                    ? $role 
                    : Role::where('slug', $role)->firstOrFail()->id);
        });

        $this->roles()->detach($roleIds);

        // Clear user cache
        app(PermissionCache::class)->clearUserCache($this->id);

        return $this;
    }

    /**
     * Give permission to user (direct permission).
     */
    public function givePermissionTo(string|int|Permission ...$permissions): self
    {
        $permissionIds = collect($permissions)->map(function ($permission) {
            return $permission instanceof Permission 
                ? $permission->id 
                : (is_numeric($permission) 
                    ? $permission 
                    : Permission::where('slug', $permission)->firstOrFail()->id);
        });

        $this->permissions()->syncWithoutDetaching($permissionIds);

        // Clear user cache
        app(PermissionCache::class)->clearUserCache($this->id);

        return $this;
    }

    /**
     * Revoke permission from user.
     */
    public function revokePermissionTo(string|int|Permission ...$permissions): self
    {
        $permissionIds = collect($permissions)->map(function ($permission) {
            return $permission instanceof Permission 
                ? $permission->id 
                : (is_numeric($permission) 
                    ? $permission 
                    : Permission::where('slug', $permission)->firstOrFail()->id);
        });

        $this->permissions()->detach($permissionIds);

        // Clear user cache
        app(PermissionCache::class)->clearUserCache($this->id);

        return $this;
    }

    /**
     * Check if user has role.
     */
    public function hasRole(string|int|Role|array $roles): bool
    {
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }
            return false;
        }

        $cache = app(PermissionCache::class);
        
        // Get user roles (with or without cache based on config)
        if ($cache->isRoleCacheEnabled()) {
            $userRoles = $cache->remember(
                $cache->getUserRolesKey($this->id),
                fn() => $this->roles->pluck('slug')->toArray()
            );
        } else {
            $userRoles = $this->roles->pluck('slug')->toArray();
        }

        $roleSlug = $roles instanceof Role 
            ? $roles->slug 
            : (is_numeric($roles) 
                ? Role::findOrFail($roles)->slug 
                : $roles);

        return in_array($roleSlug, $userRoles);
    }

    /**
     * Check if user has all roles.
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user has any role.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->hasRole($roles);
    }

    /**
     * Check if user has permission (including permissions from roles).
     * IMPROVED: Now supports super admin, wildcard permissions, and expirable permissions.
     */
    public function hasPermission(string|int|Permission $permission): bool
    {
        // NEW: Super admin check - has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        $cache = app(PermissionCache::class);
        
        // Get all user permissions (direct + from roles) - with or without cache
        if ($cache->isPermissionCacheEnabled()) {
            $userPermissions = $cache->remember(
                $cache->getUserPermissionsKey($this->id),
                function () use ($cache) {
                    // Direct permissions - filter expired ones
                    $directPermissions = $this->getActivePermissions()->pluck('slug')->toArray();
                    
                    // Permissions from roles
                    $rolePermissions = [];
                    if (config('permissions.performance.eager_loading', true)) {
                        $rolePermissions = $this->roles()
                            ->with('permissions')
                            ->get()
                            ->pluck('permissions')
                            ->flatten()
                            ->pluck('slug')
                            ->unique()
                            ->toArray();
                    } else {
                        foreach ($this->roles as $role) {
                            $rolePerms = $cache->remember(
                                $cache->getRolePermissionsKey($role->id),
                                fn() => $role->permissions->pluck('slug')->toArray()
                            );
                            $rolePermissions = array_merge($rolePermissions, $rolePerms);
                        }
                    }
                    
                    return array_unique(array_merge($directPermissions, $rolePermissions));
                }
            );
        } else {
            // Direct query without cache
            $directPermissions = $this->getActivePermissions()->pluck('slug')->toArray();
            
            $rolePermissions = [];
            if (config('permissions.performance.eager_loading', true)) {
                $rolePermissions = $this->roles()
                    ->with('permissions')
                    ->get()
                    ->pluck('permissions')
                    ->flatten()
                    ->pluck('slug')
                    ->unique()
                    ->toArray();
            } else {
                foreach ($this->roles as $role) {
                    $rolePerms = $role->permissions->pluck('slug')->toArray();
                    $rolePermissions = array_merge($rolePermissions, $rolePerms);
                }
            }
            
            $userPermissions = array_unique(array_merge($directPermissions, $rolePermissions));
        }

        $permissionSlug = $permission instanceof Permission 
            ? $permission->slug 
            : (is_numeric($permission) 
                ? Permission::findOrFail($permission)->slug 
                : $permission);

        // NEW: Check wildcard permissions
        if (config('permissions.wildcard_permissions.enabled', false)) {
            if ($this->hasWildcardPermission($permissionSlug, $userPermissions)) {
                return true;
            }
        }

        return in_array($permissionSlug, $userPermissions);
    }

    /**
     * Check if user has all permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user has any permission.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sync roles.
     */
    public function syncRoles(array $roles): self
    {
        $roleIds = collect($roles)->map(function ($role) {
            return $role instanceof Role 
                ? $role->id 
                : (is_numeric($role) 
                    ? $role 
                    : Role::where('slug', $role)->firstOrFail()->id);
        });

        $this->roles()->sync($roleIds);

        // Clear user cache
        app(PermissionCache::class)->clearUserCache($this->id);

        return $this;
    }

    /**
     * Sync permissions.
     */
    public function syncPermissions(array $permissions): self
    {
        $permissionIds = collect($permissions)->map(function ($permission) {
            return $permission instanceof Permission 
                ? $permission->id 
                : (is_numeric($permission) 
                    ? $permission 
                    : Permission::where('slug', $permission)->firstOrFail()->id);
        });

        $this->permissions()->sync($permissionIds);

        // Clear user cache
        app(PermissionCache::class)->clearUserCache($this->id);

        return $this;
    }

    /**
     * Get all permissions (direct + from roles).
     */
    public function getAllPermissions(): Collection
    {
        $cache = app(PermissionCache::class);
        
        $permissionIds = $cache->remember(
            $cache->getUserPermissionsKey($this->id) . '_ids',
            function () {
                // Direct permissions
                $directPermissionIds = $this->permissions->pluck('id')->toArray();
                
                // Permissions from roles
                $rolePermissionIds = $this->roles()
                    ->with('permissions')
                    ->get()
                    ->pluck('permissions')
                    ->flatten()
                    ->pluck('id')
                    ->unique()
                    ->toArray();
                
                return array_unique(array_merge($directPermissionIds, $rolePermissionIds));
            }
        );

        return Permission::whereIn('id', $permissionIds)->get();
    }

    /**
     * NEW: Check if user is super admin.
     * Super admin has all permissions automatically.
     */
    public function isSuperAdmin(): bool
    {
        if (!config('permissions.super_admin.enabled', false)) {
            return false;
        }

        $superAdminSlug = config('permissions.super_admin.role_slug', 'super-admin');
        return $this->hasRole($superAdminSlug);
    }

    /**
     * NEW: Check wildcard permission matching.
     * Example: 'posts.*' matches 'posts.create', 'posts.edit', etc.
     */
    protected function hasWildcardPermission(string $permission, array $userPermissions): bool
    {
        foreach ($userPermissions as $userPermission) {
            // Use fnmatch for wildcard matching (*, ?)
            if (fnmatch($userPermission, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * NEW: Get only active (non-expired) permissions.
     */
    protected function getActivePermissions()
    {
        if (!config('permissions.expirable_permissions.enabled', false)) {
            return $this->permissions;
        }

        return $this->permissions()
            ->where(function ($query) {
                $query->whereNull(config('permissions.tables.model_has_permissions') . '.expires_at')
                      ->orWhere(config('permissions.tables.model_has_permissions') . '.expires_at', '>', now());
            })->get();
    }

    /**
     * NEW: Give permission with expiration date.
     */
    public function givePermissionToUntil(string|int|Permission $permission, \DateTimeInterface $expiresAt): self
    {
        if (!config('permissions.expirable_permissions.enabled', false)) {
            throw new \Exception('Expirable permissions are not enabled in config.');
        }

        $permissionId = $permission instanceof Permission 
            ? $permission->id 
            : (is_numeric($permission) 
                ? $permission 
                : Permission::where('slug', $permission)->firstOrFail()->id);

        $this->permissions()->syncWithoutDetaching([
            $permissionId => ['expires_at' => $expiresAt]
        ]);

        // Clear user cache
        app(PermissionCache::class)->clearUserCache($this->id);

        return $this;
    }

    /**
     * NEW: Model scope - Get users with specific role.
     */
    public function scopeRole($query, string|array $roles)
    {
        if (is_array($roles)) {
            return $query->whereHas('roles', function ($q) use ($roles) {
                $q->whereIn('slug', $roles);
            });
        }

        return $query->whereHas('roles', function ($q) use ($roles) {
            $q->where('slug', $roles);
        });
    }

    /**
     * NEW: Model scope - Get users with specific permission.
     */
    public function scopePermission($query, string|array $permissions)
    {
        if (is_array($permissions)) {
            return $query->whereHas('permissions', function ($q) use ($permissions) {
                $q->whereIn('slug', $permissions);
            });
        }

        return $query->whereHas('permissions', function ($q) use ($permissions) {
            $q->where('slug', $permissions);
        });
    }

    /**
     * NEW: Model scope - Get users without specific role.
     */
    public function scopeWithoutRole($query, string|array $roles)
    {
        if (is_array($roles)) {
            return $query->whereDoesntHave('roles', function ($q) use ($roles) {
                $q->whereIn('slug', $roles);
            });
        }

        return $query->whereDoesntHave('roles', function ($q) use ($roles) {
            $q->where('slug', $roles);
        });
    }

    /**
     * NEW: Model scope - Get users without specific permission.
     */
    public function scopeWithoutPermission($query, string|array $permissions)
    {
        if (is_array($permissions)) {
            return $query->whereDoesntHave('permissions', function ($q) use ($permissions) {
                $q->whereIn('slug', $permissions);
            });
        }

        return $query->whereDoesntHave('permissions', function ($q) use ($permissions) {
            $q->where('slug', $permissions);
        });
    }
}
