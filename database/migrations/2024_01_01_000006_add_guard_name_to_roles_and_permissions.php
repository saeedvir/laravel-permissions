<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = config('permissions.database_connection', 'mysql');
        $rolesTable = config('permissions.tables.roles', 'roles');
        $permissionsTable = config('permissions.tables.permissions', 'permissions');
        
        // Add guard_name to roles
        Schema::connection($connection)->table($rolesTable, function (Blueprint $table) use ($rolesTable) {
            $table->string('guard_name', 50)->default('web')->after('slug');
            
            // Drop old unique index - use just the column name
            try {
                $table->dropUnique($rolesTable . '_slug_unique');
            } catch (\Exception $e) {
                // Index might not exist or have different name, skip
            }
            
            // Add new composite unique index
            $table->unique(['slug', 'guard_name']);
        });

        // Add guard_name to permissions
        Schema::connection($connection)->table($permissionsTable, function (Blueprint $table) use ($permissionsTable) {
            $table->string('guard_name', 50)->default('web')->after('slug');
            
            // Drop old unique index - use just the column name
            try {
                $table->dropUnique($permissionsTable . '_slug_unique');
            } catch (\Exception $e) {
                // Index might not exist or have different name, skip
            }
            
            // Add new composite unique index
            $table->unique(['slug', 'guard_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('permissions.database_connection', 'mysql');
        
        Schema::connection($connection)->table(
            config('permissions.tables.roles', 'roles'),
            function (Blueprint $table) {
                $table->dropUnique(['slug', 'guard_name']);
                $table->unique('slug');
                $table->dropColumn('guard_name');
            }
        );

        Schema::connection($connection)->table(
            config('permissions.tables.permissions', 'permissions'),
            function (Blueprint $table) {
                $table->dropUnique(['slug', 'guard_name']);
                $table->unique('slug');
                $table->dropColumn('guard_name');
            }
        );
    }
};
