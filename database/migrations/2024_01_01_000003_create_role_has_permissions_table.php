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
        
        Schema::connection($connection)->create(
            config('permissions.tables.role_has_permissions', 'role_has_permissions'), 
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('permission_id');
                $table->timestamps();

                $table->foreign('role_id')
                    ->references('id')
                    ->on(config('permissions.tables.roles', 'roles'))
                    ->onDelete('cascade');

                $table->foreign('permission_id')
                    ->references('id')
                    ->on(config('permissions.tables.permissions', 'permissions'))
                    ->onDelete('cascade');

                $table->unique(['role_id', 'permission_id']);
                $table->index('role_id');
                $table->index('permission_id');
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('permissions.database_connection', 'mysql');
        
        Schema::connection($connection)->dropIfExists(
            config('permissions.tables.role_has_permissions', 'role_has_permissions')
        );
    }
};
