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
    Schema::create('pre_enrollments', function (Blueprint $table) {
        $table->id(); // Internal enrollment ID
        
        // 1. Link to the student using the NEW auto-increment ID
        // This replaces the student_lrn string
        $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('cascade');

        $table->integer('submission_version')->default(1);
        
        // 2. The Flexible JSON Column
        $table->json('responses'); 
        
        // 3. Track the status
        $table->string('status')->default('Pending'); 
        
        $table->timestamps();
    });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_enrollments');
    }
};