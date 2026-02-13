<?php

namespace Saeedvir\LaravelPermissions\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $redirectTo = null): Response
    {
        if (!Auth::check()) {
            return $this->handleUnauthenticated($request, $redirectTo);
        }

        return $next($request);
    }

    /**
     * Handle unauthenticated user.
     */
    protected function handleUnauthenticated(Request $request, $redirectTo): Response
    {
        $config = config('permissions.middleware.unauthenticated_response');
        $type = $config['type'] ?? 'json';

        return match ($type) {
            'redirect' => empty($redirectTo) ? redirect($config['redirect_to'] ?? '/login') : redirect($redirectTo),
            'abort' => abort($config['abort_code'] ?? 401, $config['json_message'] ?? 'Unauthenticated.'),
            default => response()->json([
                'message' => $config['json_message'] ?? 'Unauthenticated.',
                'success' => false,
            ], 401),
        };
    }
}
