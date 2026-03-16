<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clean up any old versions
        Schema::dropIfExists('kiosk_enrollments');

        Schema::create('kiosk_enrollments', function (Blueprint $table) {
            $table->id(); // PK for the enrollment itself
            
            // 1. LINK TO STUDENT (Timeline Aware)
            // This replaces student_lrn and user_id as the main connection
            $table->foreignId('student_id')
                  ->constrained('students')
                  ->onDelete('cascade');

            // 2. CHOICES
            $table->string('academic_status')->nullable();
            $table->string('grade_level')->nullable();
            $table->string('track')->nullable();
            $table->string('cluster')->nullable();

            // 3. DOCUMENT TRACKING (Merged from all migrations)
            $table->string('sf9_path')->nullable();
            $table->string('sf9_status')->default('pending'); 
            $table->string('sf9_remarks')->nullable();
            $table->integer('sf9_attempts')->default(0);

            $table->string('psa_path')->nullable();
            $table->string('psa_status')->default('pending');
            $table->string('psa_remarks')->nullable();
            $table->integer('psa_attempts')->default(0);

            $table->string('enroll_form_path')->nullable();
            $table->string('enroll_form_status')->default('pending');
            $table->string('enroll_form_remarks')->nullable();
            $table->integer('enroll_form_attempts')->default(0);

            $table->string('als_cert_path')->nullable();
            $table->string('als_cert_status')->default('pending');
            $table->string('als_cert_remarks')->nullable();
            $table->integer('als_cert_attempts')->default(0);

            $table->string('affidavit_path')->nullable();
            $table->string('affidavit_status')->default('pending');
            $table->string('affidavit_remarks')->nullable();
            $table->integer('affidavit_attempts')->default(0);

            $table->string('good_moral_path')->nullable();
            $table->string('good_moral_status')->default('pending');
            $table->string('good_moral_remarks')->nullable();
            $table->integer('good_moral_attempts')->default(0);

            $table->string('sf10_path')->nullable();
            $table->string('sf10_status')->default('pending');
            $table->string('sf10_remarks')->nullable();
            $table->integer('sf10_attempts')->default(0);

            // 4. LOGISTICS & SCANNING
            $table->string('latest_scan_type')->nullable(); 
            $table->string('latest_scan_status')->nullable(); 
            $table->string('latest_scan_remarks')->nullable();
            $table->json('rejected_papers')->nullable(); // From the 4th migration

            // 5. SESSION INFO
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
        });

        // Ensure redundant table is gone
        Schema::dropIfExists('scans');
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosk_enrollments');
    }
};