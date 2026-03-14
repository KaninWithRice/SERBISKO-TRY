<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop and recreate kiosk_enrollments
        Schema::dropIfExists('kiosk_enrollments');

        Schema::create('kiosk_enrollments', function (Blueprint $table) {
            // New ID (maps to User ID)
            $table->unsignedBigInteger('id')->primary();
            $table->foreign('id')->references('id')->on('users')->onDelete('cascade');

            // Student LRN (Nullable, filled after scan)
            $table->string('student_lrn')->nullable();

            // Choices
            $table->string('academic_status')->nullable();
            $table->string('grade_level')->nullable();
            $table->string('track')->nullable();
            $table->string('cluster')->nullable();

            // Report Card (SF9)
            $table->string('sf9_path')->nullable();
            $table->string('sf9_status')->default('pending'); 
            $table->string('sf9_remarks')->nullable();
            $table->string('sf9_lrn')->nullable();
            $table->integer('sf9_attempts')->default(0);

            // Birth Certificate (PSA)
            $table->string('psa_path')->nullable();
            $table->string('psa_status')->default('pending');
            $table->string('psa_remarks')->nullable();
            $table->integer('psa_attempts')->default(0);

            // Enrollment Form
            $table->string('enroll_form_path')->nullable();
            $table->string('enroll_form_status')->default('pending');
            $table->string('enroll_form_remarks')->nullable();
            $table->integer('enroll_form_attempts')->default(0);

            // ALS Certificate
            $table->string('als_cert_path')->nullable();
            $table->string('als_cert_status')->default('pending');
            $table->string('als_cert_remarks')->nullable();
            $table->integer('als_cert_attempts')->default(0);

            // Affidavit
            $table->string('affidavit_path')->nullable();
            $table->string('affidavit_status')->default('pending');
            $table->string('affidavit_remarks')->nullable();
            $table->integer('affidavit_attempts')->default(0);

            // Latest scan trackers for frontend polling
            $table->string('latest_scan_type')->nullable(); 
            $table->string('latest_scan_status')->nullable(); 
            $table->string('latest_scan_remarks')->nullable();

            // Session Info
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
        });

        // 2. Drop the redundant scans table
        Schema::dropIfExists('scans');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate scans table
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('document_type'); 
            $table->string('file_path');                 
            $table->string('lrn')->nullable();
            $table->string('status')->default('pending');
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        // Revert kiosk_enrollments
        Schema::dropIfExists('kiosk_enrollments');

        Schema::create('kiosk_enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('student_lrn');
            $table->foreign('student_lrn')->references('lrn')->on('students')->onDelete('cascade');
            $table->string('academic_status');
            $table->string('grade_level');
            $table->string('track')->nullable();
            $table->string('cluster')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }
};
