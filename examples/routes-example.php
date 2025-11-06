<?php

/**
 * Example Routes for Laravel Permissions Package
 * 
 * Add these routes to your routes/web.php or routes/api.php
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExampleUsageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;

// Setup routes (remove these in production)
Route::prefix('permissions')->group(function () {
    Route::get('setup', [ExampleUsageController::class, 'setupRolesAndPermissions']);
    Route::get('roles', [ExampleUsageController::class, 'listRoles']);
    Route::get('permissions', [ExampleUsageController::class, 'listPermissions']);
    
    Route::post('assign-role', [ExampleUsageController::class, 'assignRoleToUser']);
    Route::post('give-permission', [ExampleUsageController::class, 'givePermissionToUser']);
    Route::get('check/{user_id}', [ExampleUsageController::class, 'checkUserPermissions']);
    Route::post('clear-cache', [ExampleUsageController::class, 'clearCache']);
});

// ============================================================================
// MIDDLEWARE EXAMPLES
// ============================================================================

// 1. Check Authentication Only
Route::middleware(['check.auth'])->group(function () {
    Route::get('/dashboard', fn() => 'User Dashboard');
    Route::get('/profile', fn() => 'User Profile');
});

// 2. Check Single Role
Route::middleware(['role:admin'])->group(function () {
    Route::get('/admin', [ExampleUsageController::class, 'adminOnly']);
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::get('/admin/settings', fn() => 'Settings');
});

// 3. Check Multiple Roles (user needs at least one)
Route::middleware(['role:admin|super-admin'])->group(function () {
    Route::get('/admin/dashboard', fn() => 'Admin Dashboard');
});

// 4. Check Single Permission
Route::middleware(['permission:create-post'])->group(function () {
    Route::get('/posts/create', [PostController::class, 'create']);
    Route::post('/posts', [PostController::class, 'store']);
});

// 5. Check Multiple Permissions (user needs at least one)
Route::middleware(['permission:edit-post|edit-own-post'])->group(function () {
    Route::get('/posts/{post}/edit', [PostController::class, 'edit']);
    Route::put('/posts/{post}', [PostController::class, 'update']);
});

// 6. Combine Middlewares
Route::middleware(['check.auth', 'role:admin', 'permission:delete-post'])
    ->delete('/posts/{post}', [PostController::class, 'destroy']);

// 7. Individual Route Protection
Route::get('/admin/reports', fn() => 'Reports')->middleware('role:admin');
Route::post('/posts', [PostController::class, 'store'])->middleware('permission:create-post');

// ============================================================================
// API ROUTES EXAMPLES
// ============================================================================

Route::prefix('api')->group(function () {
    // Public routes
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    // Protected routes
    Route::middleware(['check.auth'])->group(function () {
        Route::get('user', fn() => auth()->user());
        
        // Posts - require specific permissions
        Route::middleware(['permission:view-post'])->group(function () {
            Route::get('posts', [PostController::class, 'index']);
            Route::get('posts/{post}', [PostController::class, 'show']);
        });

        Route::middleware(['permission:create-post'])
            ->post('posts', [PostController::class, 'store']);

        Route::middleware(['permission:edit-post'])
            ->put('posts/{post}', [PostController::class, 'update']);

        Route::middleware(['permission:delete-post'])
            ->delete('posts/{post}', [PostController::class, 'destroy']);

        // Admin only routes
        Route::middleware(['role:admin'])->prefix('admin')->group(function () {
            Route::get('users', [UserController::class, 'index']);
            Route::post('users', [UserController::class, 'store']);
            Route::put('users/{user}', [UserController::class, 'update']);
            Route::delete('users/{user}', [UserController::class, 'destroy']);
        });
    });
});

// ============================================================================
// ADVANCED EXAMPLES
// ============================================================================

// Multiple conditions with OR logic
Route::get('/editor-panel', fn() => 'Editor Panel')
    ->middleware('role:editor|admin|super-admin');

// Nested middleware groups
Route::middleware(['check.auth'])->group(function () {
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('/', fn() => 'Admin Home');
        
        // Additional permission check within role check
        Route::middleware(['permission:manage-settings'])
            ->get('settings', fn() => 'Manage Settings');
    });
});

// Dynamic permission checking in controller
Route::get('/posts/{post}', function ($post) {
    $user = auth()->user();
    
    if ($user->hasPermission('view-all-posts') || $post->user_id == $user->id) {
        return "Post content";
    }
    
    return response()->json(['error' => 'Unauthorized'], 403);
})->middleware('check.auth');

// ============================================================================
// RESOURCE ROUTES WITH PERMISSIONS
// ============================================================================

// Protect entire resource
Route::middleware(['permission:manage-posts'])->group(function () {
    Route::resource('posts', PostController::class);
});

// Or protect individual actions
Route::resource('posts', PostController::class)->only(['index', 'show']);

Route::resource('posts', PostController::class)
    ->only(['create', 'store'])
    ->middleware('permission:create-post');

Route::resource('posts', PostController::class)
    ->only(['edit', 'update'])
    ->middleware('permission:edit-post');

Route::resource('posts', PostController::class)
    ->only(['destroy'])
    ->middleware('permission:delete-post');
