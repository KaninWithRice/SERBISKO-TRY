<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            // 1. Identity & Timeline (New Foundation)
            $table->id(); // AUTO-INCREMENT ID as Primary Key
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('lrn'); 
            $table->string('school_year'); 
            $table->boolean('is_manually_edited')->default(false);

            // 2. Personal Information (From Migration 1)
            $table->enum('sex', ['Male', 'Female']) ->nullable();
            $table->integer('age') ->nullable();
            $table->string('place_of_birth') ->nullable();
            $table->string('mother_tongue')->nullable();
            // contact_number is removed here because your 3rd migration dropped it

            // 3. Current Address (From Migration 2)
            $table->string('curr_house_number')->nullable();
            $table->string('curr_street')->nullable();
            $table->string('curr_barangay')->nullable();
            $table->string('curr_city')->nullable();
            $table->string('curr_province')->nullable();
            $table->string('curr_zip_code')->nullable();
            $table->string('curr_country')->nullable();

            // 4. Permanent Address (From Migration 2)
            $table->boolean('is_perm_same_as_curr')->default(false);
            $table->string('perm_house_number')->nullable();
            $table->string('perm_street')->nullable();
            $table->string('perm_barangay')->nullable();
            $table->string('perm_city')->nullable();
            $table->string('perm_province')->nullable();
            $table->string('perm_zip_code')->nullable();
            $table->string('perm_country')->nullable();

            // 5. Parents & Guardian (From Migration 2)
            $table->string('mother_last_name')->nullable();
            $table->string('mother_first_name')->nullable();
            $table->string('mother_middle_name')->nullable();
            $table->string('mother_contact_number')->nullable();
            $table->string('father_last_name')->nullable();
            $table->string('father_first_name')->nullable();
            $table->string('father_middle_name')->nullable();
            $table->string('father_contact_number')->nullable();
            $table->string('guardian_last_name')->nullable();
            $table->string('guardian_first_name')->nullable();
            $table->string('guardian_middle_name')->nullable();
            $table->string('guardian_contact_number')->nullable();

            $table->timestamps();

            // 6. The Unique Constraint
            $table->unique(['lrn', 'school_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};