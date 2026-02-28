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
            $table->integer('records_synced')->default(0);
            $table->string('status')->default('Success');
            $table->timestamp('created_at')->useCurrent();
            // We use timestamps() or just created_at since your controller 
            // specifically calls for 'created_at' in the orderBy clause.
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
