<?php

namespace Saeedvir\LaravelPermissions\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  string|array  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return $this->handleUnauthenticated($request);
        }

        $user = Auth::user();

        // Check if user has any of the required roles
        if (empty($roles) || !method_exists($user, 'hasRole')) {
            return $this->handleUnauthorized($request, 'Invalid role configuration.');
        }

        // Support pipe-separated roles (role1|role2)
        $rolesArray = [];
        foreach ($roles as $role) {
            if (str_contains($role, '|')) {
                $rolesArray = array_merge($rolesArray, explode('|', $role));
            } else {
                $rolesArray[] = $role;
            }
        }

        if ($user->hasAnyRole($rolesArray)) {
            return $next($request);
        }

        return $this->handleUnauthorized($request, 'User does not have the required role.');
    }

    /**
     * Handle unauthenticated user.
     */
    protected function handleUnauthenticated(Request $request): Response
    {
        $config = config('permissions.middleware.unauthenticated_response');
        $type = $config['type'] ?? 'json';

        return match($type) {
            'redirect' => redirect($config['redirect_to'] ?? '/login'),
            'abort' => abort($config['abort_code'] ?? 401, $config['json_message'] ?? 'Unauthenticated.'),
            default => response()->json([
                'message' => $config['json_message'] ?? 'Unauthenticated.',
                'success' => false,
            ], 401),
        };
    }

    /**
     * Handle unauthorized user.
     */
    protected function handleUnauthorized(Request $request, string $message = 'Unauthorized.'): Response
    {
        $config = config('permissions.middleware.unauthorized_response');
        $type = $config['type'] ?? 'json';

        return match($type) {
            'redirect' => redirect($config['redirect_to'] ?? '/unauthorized'),
            'abort' => abort($config['abort_code'] ?? 403, $message),
            default => response()->json([
                'message' => $config['json_message'] ?? $message,
                'success' => false,
            ], 403),
        };
    }
}
