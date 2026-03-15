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
        Schema::table('kiosk_enrollments', function (Blueprint $table) {
            // Good Moral Certificate
            $table->string('good_moral_path')->after('affidavit_attempts')->nullable();
            $table->string('good_moral_status')->after('good_moral_path')->default('pending');
            $table->string('good_moral_remarks')->after('good_moral_status')->nullable();
            $table->integer('good_moral_attempts')->after('good_moral_remarks')->default(0);

            // Form 137 / SF10
            $table->string('sf10_path')->after('good_moral_attempts')->nullable();
            $table->string('sf10_status')->after('sf10_path')->default('pending');
            $table->string('sf10_remarks')->after('sf10_status')->nullable();
            $table->integer('sf10_attempts')->after('sf10_remarks')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kiosk_enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'good_moral_path', 'good_moral_status', 'good_moral_remarks', 'good_moral_attempts',
                'sf10_path', 'sf10_status', 'sf10_remarks', 'sf10_attempts'
            ]);
        });
    }
};
