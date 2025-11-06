<?php

namespace Saeedvir\LaravelPermissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Saeedvir\LaravelPermissions\Services\PermissionCache;

class Permission extends Model
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
        return config('permissions.tables.permissions', 'permissions');
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
     * Permission belongs to many roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permissions.models.role', Role::class),
            config('permissions.tables.role_has_permissions', 'role_has_permissions'),
            'permission_id',
            'role_id'
        )->withTimestamps();
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
     * Find or create permission.
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
        static::creating(function ($permission) {
            if (empty($permission->guard_name)) {
                $permission->guard_name = config('permissions.guards.default', 'web');
            }
        });

        static::deleted(function ($permission) {
            // Clear all role caches when permission is deleted
            app(PermissionCache::class)->flush();
        });

        static::updated(function ($permission) {
            // Clear all role caches when permission is updated
            app(PermissionCache::class)->flush();
        });
    }
}
