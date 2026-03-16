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
        Schema::create('sync_histories', function (Blueprint $table) {
            $table->id();
            
            // Core Identity
            $table->string('school_year')->nullable(); 
            
            // Statistics
            $table->integer('records_synced')->default(0); // Total (New + Updated)
            $table->integer('new_records')->default(0);
            $table->integer('updated_records')->default(0);
            
            // Status & Timestamps
            $table->string('status')->default('Success');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_histories');
    }
};