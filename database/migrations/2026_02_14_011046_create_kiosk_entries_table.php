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
        Schema::create('kiosk_enrollments', function (Blueprint $table) {
            $table->id();
            
            // Link to the master student profile
            $table->string('student_lrn');
            $table->foreign('student_lrn')
                ->references('lrn')
                ->on('students')
                ->onDelete('cascade');

            // The Specific Choices made at the Kiosk
            $table->string('academic_status'); // Regular, Transferee, Balik-Aral
            $table->string('grade_level');     // G11 or G12
            $table->string('track')->nullable();    // FOR G11; ACADEMIC or TechPro, etc.
            $table->string('cluster')->nullable();  // Specific grouping for G11
            
            // Kiosk Session Info
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kiosk_enrollments');
    }
};
