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
        Schema::create('field_mappings', function (Blueprint $table) {
            $table->id();

            // 1. The Data Link (Google Sheet Side)
            $table->string('google_header')->unique(); 

            // 2. The Data Link (Database Side)
            $table->string('database_field')->nullable(); // "What's in the DB (e.g. mother_tongue)" ; contains core fields

            // 3. The Presentation Layer (UI Side)
            $table->string('display_label'); 
            $table->string('category')->default('General'); // e.g., Identity, Medical, Family
            $table->integer('priority')->default(99); // Higher numbers appear lower on the profile
            
            // 4. System Logic & Safety
            $table->boolean('is_visible')->default(true); // Admin can toggle visibility
            $table->boolean('is_system_core')->default(false); // If true, cannot be deleted or hidden
            
            // 5. Accountability (Audit Trail)
            // Points to the Super Admin who last modified this mapping
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_mappings');
    }
};