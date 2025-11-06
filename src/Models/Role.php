<?php

namespace Saeedvir\LaravelPermissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Saeedvir\LaravelPermissions\Services\PermissionCache;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'guard_name'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'guard_name' => 'web',
    ];

    /**
     * Get the table name from config.
     */
    public function getTable(): string
    {
        return config('permissions.tables.roles', 'roles');
    }

    /**
     * Get the database connection from config.
     */
    public function getConnectionName(): ?string
    {
        $connection = config('permissions.database_connection', 'mysql');
        
        // Set database name if specified
        if ($dbName = config('permissions.database_name')) {
            config(['database.connections.' . $connection . '.database' => $dbName]);
        }
        
        return $connection;
    }

    /**
     * Role belongs to many permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permissions.models.permission', Permission::class),
            config('permissions.tables.role_has_permissions', 'role_has_permissions'),
            'role_id',
            'permission_id'
        )->withTimestamps();
    }

    /**
     * Give permission to role.
     * IMPROVED: Now uses transactions and clears all affected user caches.
     */
    public function givePermissionTo(string|int|Permission ...$permissions): self
    {
        $callback = function () use ($permissions) {
            $permissionIds = collect($permissions)->map(function ($permission) {
                return $permission instanceof Permission 
                    ? $permission->id 
                    : (is_numeric($permission) 
                        ? $permission 
                        : Permission::where('slug', $permission)->firstOrFail()->id);
            });

            $this->permissions()->syncWithoutDetaching($permissionIds);

            // FIXED: Clear cache for all users with this role
            app(PermissionCache::class)->clearAffectedUsersCaches($this->id);
        };

        if (config('permissions.performance.use_transactions', true)) {
            DB::transaction($callback);
        } else {
            $callback();
        }

        return $this;
    }

    /**
     * Revoke permission from role.
     * IMPROVED: Now uses transactions and clears all affected user caches.
     */
    public function revokePermissionTo(string|int|Permission ...$permissions): self
    {
        $callback = function () use ($permissions) {
            $permissionIds = collect($permissions)->map(function ($permission) {
                return $permission instanceof Permission 
                    ? $permission->id 
                    : (is_numeric($permission) 
                        ? $permission 
                        : Permission::where('slug', $permission)->firstOrFail()->id);
            });

            $this->permissions()->detach($permissionIds);

            // FIXED: Clear cache for all users with this role
            app(PermissionCache::class)->clearAffectedUsersCaches($this->id);
        };

        if (config('permissions.performance.use_transactions', true)) {
            DB::transaction($callback);
        } else {
            $callback();
        }

        return $this;
    }

    /**
     * Check if role has permission.
     */
    public function hasPermission(string|int|Permission $permission): bool
    {
        $cache = app(PermissionCache::class);
        
        $cachedPermissions = $cache->remember(
            $cache->getRolePermissionsKey($this->id),
            fn() => $this->permissions->pluck('slug')->toArray()
        );

        $permissionSlug = $permission instanceof Permission 
            ? $permission->slug 
            : (is_numeric($permission) 
                ? Permission::findOrFail($permission)->slug 
                : $permission);

        return in_array($permissionSlug, $cachedPermissions);
    }

    /**
     * Sync permissions.
     * IMPROVED: Now uses transactions and clears all affected user caches.
     */
    public function syncPermissions(array $permissions): self
    {
        $callback = function () use ($permissions) {
            $permissionIds = collect($permissions)->map(function ($permission) {
                return $permission instanceof Permission 
                    ? $permission->id 
                    : (is_numeric($permission) 
                        ? $permission 
                        : Permission::where('slug', $permission)->firstOrFail()->id);
            });

            $this->permissions()->sync($permissionIds);

            // FIXED: Clear cache for all users with this role
            app(PermissionCache::class)->clearAffectedUsersCaches($this->id);
        };

        if (config('permissions.performance.use_transactions', true)) {
            DB::transaction($callback);
        } else {
            $callback();
        }

        return $this;
    }

    /**
     * Scope to guard.
     */
    public function scopeForGuard($query, string $guardName)
    {
        if (!config('permissions.guards.enabled', false)) {
            return $query;
        }

        return $query->where('guard_name', $guardName);
    }

    /**
     * Scope with permissions eager loaded.
     */
    public function scopeWithPermissions($query)
    {
        return $query->with('permissions');
    }

    /**
     * Get by slug and guard.
     */
    public static function findBySlug(string $slug, ?string $guardName = null): ?self
    {
        $guardName = $guardName ?? config('permissions.guards.default', 'web');
        
        $query = static::where('slug', $slug);
        
        if (config('permissions.guards.enabled', false)) {
            $query->where('guard_name', $guardName);
        }
        
        return $query->first();
    }

    /**
     * Find or create role.
     */
    public static function findOrCreate(string $slug, string $name = null, ?string $guardName = null): self
    {
        $guardName = $guardName ?? config('permissions.guards.default', 'web');
        $name = $name ?? ucwords(str_replace('-', ' ', $slug));

        $query = static::where('slug', $slug);
        
        if (config('permissions.guards.enabled', false)) {
            $query->where('guard_name', $guardName);
        }

        return $query->firstOrCreate(
            ['slug' => $slug, 'guard_name' => $guardName],
            ['name' => $name]
        );
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Set default guard if not set
        static::creating(function ($role) {
            if (empty($role->guard_name)) {
                $role->guard_name = config('permissions.guards.default', 'web');
            }
        });

        static::deleted(function ($role) {
            // FIXED: Clear cache for all users with this role
            app(PermissionCache::class)->clearAffectedUsersCaches($role->id);
        });
    }
}
