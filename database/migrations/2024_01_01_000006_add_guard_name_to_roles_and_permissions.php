<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName, string $connection): bool
    {
        $indexes = DB::connection($connection)
            ->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        
        return count($indexes) > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = config('permissions.database_connection', 'mysql');
        $rolesTable = config('permissions.tables.roles', 'roles');
        $permissionsTable = config('permissions.tables.permissions', 'permissions');
        
        // Add guard_name to roles
        if (!Schema::connection($connection)->hasColumn($rolesTable, 'guard_name')) {
            Schema::connection($connection)->table($rolesTable, function (Blueprint $table) {
                $table->string('guard_name', 50)->default('web')->after('slug');
            });
        }
        
        // Drop old unique index on roles if exists
        if ($this->indexExists($rolesTable, $rolesTable . '_slug_unique', $connection)) {
            Schema::connection($connection)->table($rolesTable, function (Blueprint $table) use ($rolesTable) {
                $table->dropUnique($rolesTable . '_slug_unique');
            });
        }
        
        // Add new composite unique index on roles
        if (!$this->indexExists($rolesTable, $rolesTable . '_slug_guard_name_unique', $connection)) {
            Schema::connection($connection)->table($rolesTable, function (Blueprint $table) {
                $table->unique(['slug', 'guard_name']);
            });
        }

        // Add guard_name to permissions
        if (!Schema::connection($connection)->hasColumn($permissionsTable, 'guard_name')) {
            Schema::connection($connection)->table($permissionsTable, function (Blueprint $table) {
                $table->string('guard_name', 50)->default('web')->after('slug');
            });
        }
        
        // Drop old unique index on permissions if exists
        if ($this->indexExists($permissionsTable, $permissionsTable . '_slug_unique', $connection)) {
            Schema::connection($connection)->table($permissionsTable, function (Blueprint $table) use ($permissionsTable) {
                $table->dropUnique($permissionsTable . '_slug_unique');
            });
        }
        
        // Add new composite unique index on permissions
        if (!$this->indexExists($permissionsTable, $permissionsTable . '_slug_guard_name_unique', $connection)) {
            Schema::connection($connection)->table($permissionsTable, function (Blueprint $table) {
                $table->unique(['slug', 'guard_name']);
            });
        }
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
