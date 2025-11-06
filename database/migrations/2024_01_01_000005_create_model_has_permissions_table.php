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
            config('permissions.tables.model_has_permissions', 'model_has_permissions'), 
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('permission_id');
                $table->morphs('model'); // Creates model_type and model_id
                $table->timestamps();

                $table->foreign('permission_id')
                    ->references('id')
                    ->on(config('permissions.tables.permissions', 'permissions'))
                    ->onDelete('cascade');

                $table->unique(['permission_id', 'model_id', 'model_type']);
                $table->index('permission_id');
                $table->index(['model_id', 'model_type']);
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
            config('permissions.tables.model_has_permissions', 'model_has_permissions')
        );
    }
};
