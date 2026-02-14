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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('student_lrn');
            
            $table->foreign('student_lrn')
                ->references('lrn')
                ->on('students')
                ->onDelete('cascade');

            $table->string('document_type'); // e.g., 'Report Card'
            $table->string('file_path');     // The scanned image
            
            // OCR & Authentication Fields
            $table->text('raw_ocr_data')->nullable(); // Extracted text from OCR
            $table->boolean('is_authenticated')->default(false); // Result from Auxiliary App
            $table->boolean('is_synced_to_auxiliary')->default(false); // Track the transfer
            
            $table->enum('status', ['Pending', 'Verified', 'Rejected'])->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
