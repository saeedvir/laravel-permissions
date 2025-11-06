<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Saeedvir\LaravelPermissions\Models\Role;
use Saeedvir\LaravelPermissions\Models\Permission;
use App\Models\User;

/**
 * Example controller demonstrating package usage
 * Copy this to your app/Http/Controllers directory
 */
class ExampleUsageController extends Controller
{
    /**
     * Setup example roles and permissions
     */
    public function setupRolesAndPermissions()
    {
        // Create permissions
        $permissions = [
            Permission::firstOrCreate(['slug' => 'create-post'], [
                'name' => 'Create Post',
                'description' => 'Can create posts'
            ]),
            Permission::firstOrCreate(['slug' => 'edit-post'], [
                'name' => 'Edit Post',
                'description' => 'Can edit posts'
            ]),
            Permission::firstOrCreate(['slug' => 'delete-post'], [
                'name' => 'Delete Post',
                'description' => 'Can delete posts'
            ]),
        ];

        // Create roles
        $admin = Role::firstOrCreate(['slug' => 'admin'], [
            'name' => 'Administrator',
            'description' => 'Full system access'
        ]);

        $editor = Role::firstOrCreate(['slug' => 'editor'], [
            'name' => 'Editor',
            'description' => 'Can edit content'
        ]);

        // Assign all permissions to admin
        $admin->syncPermissions(['create-post', 'edit-post', 'delete-post']);

        // Assign limited permissions to editor
        $editor->syncPermissions(['create-post', 'edit-post']);

        return response()->json([
            'message' => 'Roles and permissions created successfully',
            'roles' => Role::with('permissions')->get(),
        ]);
    }

    /**
     * Assign role to user
     */
    public function assignRoleToUser(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $user->assignRole($request->role);

        return response()->json([
            'message' => 'Role assigned successfully',
            'user' => $user->load('roles'),
        ]);
    }

    /**
     * Give direct permission to user
     */
    public function givePermissionToUser(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $user->givePermissionTo($request->permission);

        return response()->json([
            'message' => 'Permission granted successfully',
            'user' => $user->load('permissions'),
        ]);
    }

    /**
     * Check user permissions
     */
    public function checkUserPermissions(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        return response()->json([
            'user' => $user->name,
            'roles' => $user->roles->pluck('name'),
            'direct_permissions' => $user->permissions->pluck('name'),
            'all_permissions' => $user->getAllPermissions()->pluck('name'),
            'has_admin_role' => $user->hasRole('admin'),
            'has_create_permission' => $user->hasPermission('create-post'),
        ]);
    }

    /**
     * Protected route example - only admin
     */
    public function adminOnly()
    {
        return response()->json([
            'message' => 'Welcome Admin!',
            'user' => auth()->user()->name,
        ]);
    }

    /**
     * Protected route example - specific permission
     */
    public function createPost()
    {
        return response()->json([
            'message' => 'You can create posts',
            'user' => auth()->user()->name,
        ]);
    }

    /**
     * Example: Check in controller
     */
    public function dynamicCheck()
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return response()->json(['message' => 'Admin access granted']);
        }

        if ($user->hasPermission('create-post')) {
            return response()->json(['message' => 'You can create posts']);
        }

        return response()->json(['message' => 'Limited access'], 403);
    }

    /**
     * Clear permission cache
     */
    public function clearCache(Request $request)
    {
        $cache = app(\Saeedvir\LaravelPermissions\Services\PermissionCache::class);
        
        if ($request->user_id) {
            $cache->clearUserCache($request->user_id);
            return response()->json(['message' => 'User cache cleared']);
        }

        $cache->flush();
        return response()->json(['message' => 'All permission caches cleared']);
    }

    /**
     * List all roles with permissions
     */
    public function listRoles()
    {
        $roles = Role::with('permissions')->get();

        return response()->json([
            'roles' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'permissions' => $role->permissions->pluck('name'),
                ];
            }),
        ]);
    }

    /**
     * List all permissions
     */
    public function listPermissions()
    {
        $permissions = Permission::all();

        return response()->json([
            'permissions' => $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'slug' => $permission->slug,
                    'description' => $permission->description,
                ];
            }),
        ]);
    }
}
