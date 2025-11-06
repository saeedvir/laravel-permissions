<?php

namespace Saeedvir\LaravelPermissions\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PermissionCache
{
    /**
     * Get cache key prefix from config.
     */
    protected function getPrefix(): string
    {
        return config('permissions.cache.key_prefix', 'saeedvir_permissions');
    }

    /**
     * Get cache store from config.
     */
    protected function getStore(): string
    {
        return config('permissions.cache.store', 'default');
    }

    /**
     * Get cache expiration time from config.
     */
    protected function getExpiration(): int
    {
        return config('permissions.cache.expiration_time', 3600);
    }

    /**
     * Check if caching is enabled.
     */
    public function isEnabled(): bool
    {
        return config('permissions.cache.enabled', true);
    }

    /**
     * Check if role caching is enabled.
     */
    public function isRoleCacheEnabled(): bool
    {
        return $this->isEnabled() && config('permissions.cache.cache_roles', true);
    }

    /**
     * Check if permission caching is enabled.
     */
    public function isPermissionCacheEnabled(): bool
    {
        return $this->isEnabled() && config('permissions.cache.cache_permissions', true);
    }

    /**
     * Check if cache tags are enabled and supported.
     */
    public function usesTags(): bool
    {
        if (!config('permissions.cache.use_tags', true)) {
            return false;
        }

        // Only Redis, Memcached, and Array drivers support tags
        $driver = Cache::store($this->getStore())->getStore();
        return method_exists($driver, 'tags');
    }

    /**
     * Get cache tags.
     */
    protected function getCacheTags(): array
    {
        return ['permissions'];
    }

    /**
     * Get cache key.
     */
    protected function getCacheKey(string $key): string
    {
        return $this->getPrefix() . '.' . $key;
    }

    /**
     * Get cached value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->isEnabled()) {
            return $default;
        }

        return Cache::store($this->getStore())->get(
            $this->getCacheKey($key),
            $default
        );
    }

    /**
     * Store value in cache.
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $ttl = $ttl ?? $this->getExpiration();

        return Cache::store($this->getStore())->put(
            $this->getCacheKey($key),
            $value,
            $ttl
        );
    }

    /**
     * Remember value in cache.
     */
    public function remember(string $key, \Closure $callback, ?int $ttl = null): mixed
    {
        if (!$this->isEnabled()) {
            return $callback();
        }

        $ttl = $ttl ?? $this->getExpiration();

        if ($this->usesTags()) {
            return Cache::store($this->getStore())->tags($this->getCacheTags())->remember(
                $this->getCacheKey($key),
                $ttl,
                $callback
            );
        }

        return Cache::store($this->getStore())->remember(
            $this->getCacheKey($key),
            $ttl,
            $callback
        );
    }

    /**
     * Forget cached value.
     */
    public function forget(string $key): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if ($this->usesTags()) {
            return Cache::store($this->getStore())->tags($this->getCacheTags())->forget(
                $this->getCacheKey($key)
            );
        }

        return Cache::store($this->getStore())->forget(
            $this->getCacheKey($key)
        );
    }

    /**
     * Flush all permission caches.
     * FIXED: Now properly clears all caches using tags or complete flush.
     */
    public function flush(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // If using cache tags (Redis), flush by tag - MUCH better performance
        if ($this->usesTags()) {
            Cache::store($this->getStore())->tags($this->getCacheTags())->flush();
            return true;
        }

        // Fallback: Clear the entire cache store (not ideal but works)
        // In production, you should use Redis with tags
        Cache::store($this->getStore())->flush();
        
        return true;
    }

    /**
     * Get user roles cache key.
     */
    public function getUserRolesKey($userId): string
    {
        return "user_roles_{$userId}";
    }

    /**
     * Get user permissions cache key.
     */
    public function getUserPermissionsKey($userId): string
    {
        return "user_permissions_{$userId}";
    }

    /**
     * Get role permissions cache key.
     */
    public function getRolePermissionsKey($roleId): string
    {
        return "role_permissions_{$roleId}";
    }

    /**
     * Clear user cache.
     * FIXED: Now also clears the _ids suffix used in getAllPermissions().
     */
    public function clearUserCache($userId): bool
    {
        $this->forget($this->getUserRolesKey($userId));
        $this->forget($this->getUserPermissionsKey($userId));
        $this->forget($this->getUserPermissionsKey($userId) . '_ids'); // FIXED: Was missing
        
        return true;
    }

    /**
     * Clear role cache.
     */
    public function clearRoleCache($roleId): bool
    {
        return $this->forget($this->getRolePermissionsKey($roleId));
    }

    /**
     * Clear caches for all users who have a specific role.
     * FIXED: New method to handle role permission changes.
     */
    public function clearAffectedUsersCaches($roleId): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            // Get all users with this role
            $userIds = DB::table(config('permissions.tables.model_has_roles', 'model_has_roles'))
                ->where('role_id', $roleId)
                ->pluck('model_id');

            // Clear cache for each affected user
            foreach ($userIds as $userId) {
                $this->clearUserCache($userId);
            }

            // Also clear the role's own cache
            $this->clearRoleCache($roleId);

            return true;
        } catch (\Exception $e) {
            // Log error but don't fail
            return false;
        }
    }
}
