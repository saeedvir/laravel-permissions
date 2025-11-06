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
        
        // Add expires_at to model_has_permissions
        Schema::connection($connection)->table(
            config('permissions.tables.model_has_permissions', 'model_has_permissions'),
            function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('permission_id');
                $table->index('expires_at');
            }
        );

        // Add expires_at to model_has_roles (optional)
        Schema::connection($connection)->table(
            config('permissions.tables.model_has_roles', 'model_has_roles'),
            function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('role_id');
                $table->index('expires_at');
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
            config('permissions.tables.model_has_permissions', 'model_has_permissions'),
            function (Blueprint $table) {
                $table->dropIndex(['expires_at']);
                $table->dropColumn('expires_at');
            }
        );

        Schema::connection($connection)->table(
            config('permissions.tables.model_has_roles', 'model_has_roles'),
            function (Blueprint $table) {
                $table->dropIndex(['expires_at']);
                $table->dropColumn('expires_at');
            }
        );
    }
};
