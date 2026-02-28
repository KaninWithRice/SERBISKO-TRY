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
        Schema::table('students', function (Blueprint $table) {
            // Current Address
            $table->string('curr_house_number')->nullable();
            $table->string('curr_street')->nullable();
            $table->string('curr_barangay')->nullable();
            $table->string('curr_city')->nullable();
            $table->string('curr_province')->nullable();
            $table->string('curr_zip_code')->nullable();

            // Permanent Address Group
            $table->boolean('is_perm_same_as_curr')->default(false);
            $table->string('perm_house_number')->nullable();
            $table->string('perm_street')->nullable();
            $table->string('perm_barangay')->nullable();
            $table->string('perm_city')->nullable();
            $table->string('perm_province')->nullable();
            $table->string('perm_zip_code')->nullable();

            // Mother's Information
            $table->string('mother_last_name')->nullable();
            $table->string('mother_first_name')->nullable();
            $table->string('mother_middle_name')->nullable();
            $table->string('mother_contact_number')->nullable();

            // Father's Information
            $table->string('father_last_name')->nullable();
            $table->string('father_first_name')->nullable();
            $table->string('father_middle_name')->nullable();
            $table->string('father_contact_number')->nullable();

            // Guardian's Information (Required)
            $table->string('guardian_last_name');
            $table->string('guardian_first_name');
            $table->string('guardian_middle_name')->nullable();
            $table->string('guardian_contact_number');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'curr_house_number', 'curr_street', 'curr_barangay', 'curr_city', 'curr_province', 'curr_zip_code',
                'is_perm_same_as_curr', 'perm_house_number', 'perm_street', 'perm_barangay', 'perm_city', 'perm_province', 'perm_zip_code',
                'mother_last_name', 'mother_first_name', 'mother_middle_name', 'mother_contact_number',
                'father_last_name', 'father_first_name', 'father_middle_name', 'father_contact_number',
                'guardian_last_name', 'guardian_first_name', 'guardian_middle_name', 'guardian_contact_number'
            ]);
        });
    }
};
