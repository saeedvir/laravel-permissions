<?php

namespace Saeedvir\LaravelPermissions;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
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
        // Merge package config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/permissions.php',
            'permissions'
        );

        // Register PermissionCache singleton
        $this->app->singleton(PermissionCache::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only run publishing & migrations in console
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        $this->registerMiddlewares();
        $this->registerBladeDirectives();
        $this->registerGate();
    }

    /**
     * Console-specific boot logic.
     */
    protected function bootForConsole(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/permissions.php' => config_path('permissions.php'),
        ], 'permissions-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'permissions-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
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

        /*
        |--------------------------------------------------------------------------
        | Role Directives
        |--------------------------------------------------------------------------
        */

        $blade->directive('role', fn ($exp) =>
            $this->compileAuthDirective('hasRole', $exp)
        );

        $blade->directive('hasrole', fn ($exp) =>
            $this->compileAuthDirective('hasRole', $exp)
        );

        $blade->directive('endrole', fn () => '<?php endif; ?>');
        $blade->directive('endhasrole', fn () => '<?php endif; ?>');

        /*
        |--------------------------------------------------------------------------
        | Permission Directives
        |--------------------------------------------------------------------------
        */

        $blade->directive('permission', fn ($exp) =>
            $this->compileAuthDirective('hasPermission', $exp)
        );

        $blade->directive('haspermission', fn ($exp) =>
            $this->compileAuthDirective('hasPermission', $exp)
        );

        $blade->directive('endpermission', fn () => '<?php endif; ?>');
        $blade->directive('endhaspermission', fn () => '<?php endif; ?>');

        /*
        |--------------------------------------------------------------------------
        | Multi Role / Permission
        |--------------------------------------------------------------------------
        */

        $blade->directive('hasanyrole', fn ($exp) =>
            $this->compileAuthDirective('hasAnyRole', $exp)
        );

        $blade->directive('hasallroles', fn ($exp) =>
            $this->compileAuthDirective('hasAllRoles', $exp)
        );

        $blade->directive('hasanypermission', fn ($exp) =>
            $this->compileAuthDirective('hasAnyPermission', $exp)
        );

        $blade->directive('hasallpermissions', fn ($exp) =>
            $this->compileAuthDirective('hasAllPermissions', $exp)
        );

        $blade->directive('endhasanyrole', fn () => '<?php endif; ?>');
        $blade->directive('endhasallroles', fn () => '<?php endif; ?>');
        $blade->directive('endhasanypermission', fn () => '<?php endif; ?>');
        $blade->directive('endhasallpermissions', fn () => '<?php endif; ?>');

        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        */

        $blade->directive('isSuperAdmin', fn () =>
            $this->compileAuthDirective('isSuperAdmin')
        );

        $blade->directive('endisSuperAdmin', fn () => '<?php endif; ?>');
    }

    /**
     * Compile reusable auth-based Blade directive.
     */
    protected function compileAuthDirective(string $method, ?string $expression = null): string
    {
        $call = $expression
            ? "auth()->user()->{$method}({$expression})"
            : "auth()->user()->{$method}()";

        return "<?php if(auth()->check() && {$call}): ?>";
    }

    /**
     * Register permissions with Laravel Gate.
     */
    protected function registerGate(): void
    {
        $gateConfig = config('permissions.gate', []);

        if (!($gateConfig['enabled'] ?? true)) {
            return;
        }

        /** @var GateContract $gate */
        $gate = $this->app->make(GateContract::class);

        $gate->before(function ($user, string $ability) use ($gateConfig) {

            if (!method_exists($user, 'hasPermission')) {
                return null;
            }

            if (!($gateConfig['before_callback'] ?? true)) {
                return null;
            }

            // Super admin bypass
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }

            // Permission check
            if ($user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}
