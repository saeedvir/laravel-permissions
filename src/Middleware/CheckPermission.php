<?php

namespace Saeedvir\LaravelPermissions\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  string|array  ...$permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!Auth::check()) {
            return $this->handleUnauthenticated($request);
        }

        $user = Auth::user();

        // Check if user has any of the required permissions
        if (empty($permissions) || !method_exists($user, 'hasPermission')) {
            return $this->handleUnauthorized($request, 'Invalid permission configuration.');
        }

        // Support pipe-separated permissions (permission1|permission2)
        $permissionsArray = [];
        foreach ($permissions as $permission) {
            if (str_contains($permission, '|')) {
                $permissionsArray = array_merge($permissionsArray, explode('|', $permission));
            } else {
                $permissionsArray[] = $permission;
            }
        }

        if ($user->hasAnyPermission($permissionsArray)) {
            return $next($request);
        }

        return $this->handleUnauthorized($request, 'User does not have the required permission.');
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
