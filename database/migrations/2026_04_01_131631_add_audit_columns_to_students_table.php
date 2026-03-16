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
            // Tracking who manually edited the profile
            // We place it after your existing boolean flag
            $table->unsignedBigInteger('manually_edited_by')->nullable()->after('is_manually_edited');
            
            // Tracking who deleted the record
            $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_at');

            // Foreign Key Constraints
            // 'set null' ensures the student record stays if an admin is deleted
            $table->foreign('manually_edited_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['manually_edited_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropColumn(['edited_by', 'deleted_by']);
        });
    }
};