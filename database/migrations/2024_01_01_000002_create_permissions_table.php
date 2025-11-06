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
            config('permissions.tables.permissions', 'permissions'), 
            function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 100)->unique();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index('slug');
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
            config('permissions.tables.permissions', 'permissions')
        );
    }
};
