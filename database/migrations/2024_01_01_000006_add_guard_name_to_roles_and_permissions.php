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
        
        // Add guard_name to roles
        Schema::connection($connection)->table(
            config('permissions.tables.roles', 'roles'),
            function (Blueprint $table) {
                $table->string('guard_name', 50)->default('web')->after('slug');
                
                // Drop old unique index
                $table->dropUnique([config('permissions.tables.roles', 'roles') . '_slug_unique']);
                
                // Add new composite unique index
                $table->unique(['slug', 'guard_name']);
            }
        );

        // Add guard_name to permissions
        Schema::connection($connection)->table(
            config('permissions.tables.permissions', 'permissions'),
            function (Blueprint $table) {
                $table->string('guard_name', 50)->default('web')->after('slug');
                
                // Drop old unique index
                $table->dropUnique([config('permissions.tables.permissions', 'permissions') . '_slug_unique']);
                
                // Add new composite unique index
                $table->unique(['slug', 'guard_name']);
            }
        );
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
