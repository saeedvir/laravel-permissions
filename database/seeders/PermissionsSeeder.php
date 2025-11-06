<?php

namespace Saeedvir\LaravelPermissions\Database\Seeders;

use Illuminate\Database\Seeder;
use Saeedvir\LaravelPermissions\Models\Role;
use Saeedvir\LaravelPermissions\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Post permissions
            ['name' => 'Create Post', 'slug' => 'create-post', 'description' => 'Can create posts'],
            ['name' => 'Edit Post', 'slug' => 'edit-post', 'description' => 'Can edit posts'],
            ['name' => 'Delete Post', 'slug' => 'delete-post', 'description' => 'Can delete posts'],
            ['name' => 'View Post', 'slug' => 'view-post', 'description' => 'Can view posts'],
            
            // User permissions
            ['name' => 'Create User', 'slug' => 'create-user', 'description' => 'Can create users'],
            ['name' => 'Edit User', 'slug' => 'edit-user', 'description' => 'Can edit users'],
            ['name' => 'Delete User', 'slug' => 'delete-user', 'description' => 'Can delete users'],
            ['name' => 'View User', 'slug' => 'view-user', 'description' => 'Can view users'],
            
            // Settings permissions
            ['name' => 'Manage Settings', 'slug' => 'manage-settings', 'description' => 'Can manage system settings'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Create roles
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'description' => 'Administrator role with full access'
            ]
        );

        $editorRole = Role::firstOrCreate(
            ['slug' => 'editor'],
            [
                'name' => 'Editor',
                'description' => 'Editor role with limited access'
            ]
        );

        $userRole = Role::firstOrCreate(
            ['slug' => 'user'],
            [
                'name' => 'User',
                'description' => 'Regular user role'
            ]
        );

        // Assign all permissions to admin
        $adminRole->syncPermissions(Permission::all()->pluck('slug')->toArray());

        // Assign some permissions to editor
        $editorRole->syncPermissions([
            'create-post',
            'edit-post',
            'view-post',
            'view-user',
        ]);

        // Assign basic permissions to user
        $userRole->syncPermissions([
            'view-post',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
