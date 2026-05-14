<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kiosk_enrollments', function (Blueprint $table) {
            $table->string('milestone_status')->default('Registered')->after('cluster');
        });
    }

    public function down(): void
    {
        Schema::table('kiosk_enrollments', function (Blueprint $table) {
            $table->dropColumn('milestone_status');
        });
    }
};
