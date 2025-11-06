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
            config('permissions.tables.model_has_roles', 'model_has_roles'), 
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->morphs('model'); // Creates model_type and model_id
                $table->timestamps();

                $table->foreign('role_id')
                    ->references('id')
                    ->on(config('permissions.tables.roles', 'roles'))
                    ->onDelete('cascade');

                $table->unique(['role_id', 'model_id', 'model_type']);
                $table->index('role_id');
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
            config('permissions.tables.model_has_roles', 'model_has_roles')
        );
    }
};
