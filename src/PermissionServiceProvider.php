<?php

namespace Saeedvir\LaravelPermissions;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Saeedvir\LaravelPermissions\Services\PermissionCache;
use Saeedvir\LaravelPermissions\Middleware\CheckRole;
use Saeedvir\LaravelPermissions\Middleware\CheckPermission;
use Saeedvir\LaravelPermissions\Middleware\CheckAuth;

class PermissionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/permissions.php', 'permissions'
        );

        // Register PermissionCache as singleton
        $this->app->singleton(PermissionCache::class, function ($app) {
            return new PermissionCache();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/permissions.php' => config_path('permissions.php'),
        ], 'permissions-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'permissions-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register middlewares
        $this->registerMiddlewares();

        // Register Blade directives
        $this->registerBladeDirectives();

        // NEW: Register with Laravel Gate
        $this->registerGate();
    }

    /**
     * Register package middlewares.
     */
    protected function registerMiddlewares(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('role', CheckRole::class);
        $router->aliasMiddleware('permission', CheckPermission::class);
        $router->aliasMiddleware('check.auth', CheckAuth::class);
    }

    /**
     * Register Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        $blade = $this->app['blade.compiler'];

        // @role('admin')
        $blade->directive('role', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasRole({$expression})): ?>";
        });
        $blade->directive('endrole', function () {
            return '<?php endif; ?>';
        });

        // @hasrole('admin')
        $blade->directive('hasrole', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasRole({$expression})): ?>";
        });
        $blade->directive('endhasrole', function () {
            return '<?php endif; ?>';
        });

        // @permission('create-post')
        $blade->directive('permission', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasPermission({$expression})): ?>";
        });
        $blade->directive('endpermission', function () {
            return '<?php endif; ?>';
        });

        // @haspermission('create-post')
        $blade->directive('haspermission', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasPermission({$expression})): ?>";
        });
        $blade->directive('endhaspermission', function () {
            return '<?php endif; ?>';
        });

        // @hasanyrole(['admin', 'editor'])
        $blade->directive('hasanyrole', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasAnyRole({$expression})): ?>";
        });
        $blade->directive('endhasanyrole', function () {
            return '<?php endif; ?>';
        });

        // @hasallroles(['admin', 'editor'])
        $blade->directive('hasallroles', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasAllRoles({$expression})): ?>";
        });
        $blade->directive('endhasallroles', function () {
            return '<?php endif; ?>';
        });

        // @hasanypermission(['create-post', 'edit-post'])
        $blade->directive('hasanypermission', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasAnyPermission({$expression})): ?>";
        });
        $blade->directive('endhasanypermission', function () {
            return '<?php endif; ?>';
        });

        // @hasallpermissions(['create-post', 'edit-post'])
        $blade->directive('hasallpermissions', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasAllPermissions({$expression})): ?>";
        });
        $blade->directive('endhasallpermissions', function () {
            return '<?php endif; ?>';
        });
    }

    /**
     * NEW: Register permissions with Laravel Gate.
     * This integrates the package with Laravel's native authorization system.
     */
    protected function registerGate(): void
    {
        if (!config('permissions.gate.enabled', true)) {
            return;
        }

        Gate::before(function ($user, $ability) {
            if (!method_exists($user, 'hasPermission')) {
                return null;
            }

            // Check if before callback is enabled
            if (!config('permissions.gate.before_callback', true)) {
                return null;
            }

            // Super admin bypasses all gates
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }

            // Check if user has permission
            if ($user->hasPermission($ability)) {
                return true;
            }

            // Don't interfere with other gate checks
            return null;
        });
    }
}
