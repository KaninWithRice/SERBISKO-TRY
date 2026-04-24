<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the problematic table
        Schema::dropIfExists('documents');

        // Recreate it with proper foreign key
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            
            // Reference the primary key ID instead of LRN to avoid SQLite mismatch
            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('cascade');

            $table->string('document_type');
            $table->string('file_path');
            
            $table->text('raw_ocr_data')->nullable();
            $table->boolean('is_authenticated')->default(false);
            $table->boolean('is_synced_to_auxiliary')->default(false);
            
            $table->enum('status', ['Pending', 'Verified', 'Rejected'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
