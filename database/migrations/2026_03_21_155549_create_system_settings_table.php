<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('active_spreadsheet_id')->nullable();
            $table->string('active_sheet_range')->default('Form Responses 1!A1:Z');
            $table->string('active_school_year')->nullable();
            $table->string('edit_form_url')->nullable();
            $table->string('public_form_url')->nullable();
            
            // The "Audit Trail" Column
            $table->foreignId('last_updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
