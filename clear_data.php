<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Models\Student;
use App\Models\User;

try {
    // 1. Disable foreign key checks for truncation
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    // 2. Truncate submission-related tables (Implicitly commits transaction in MySQL)
    $tables = [
        'pre_enrollments',
        'kiosk_enrollments',
        'documents',
        'scans',
        'sync_histories',
        'sync_conflicts',
        'notifications',
    ];

    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            DB::table($table)->truncate();
            echo "Truncated table: $table\n";
        } else {
            echo "Table $table does not exist, skipping.\n";
        }
    }

    // 3. Reset student passwords and status
    $students = Student::with('user')->get();
    echo "Processing " . $students->count() . " students...\n";

    DB::transaction(function () use ($students) {
        foreach ($students as $student) {
            if ($student->user) {
                // Reset password to LRN (hashed via cast) and set password_changed_at to null
                $student->user->password = $student->lrn; 
                $student->user->password_changed_at = null;
                $student->user->save();
            }

            // Reset enrollment-related fields in students table
            $student->grade_level = null;
            $student->section_id = null;
            $student->is_manually_edited = 0;
            $student->save();
        }
    });

    // 4. Re-enable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    echo "Database operations completed successfully.\n";

    // 5. Delete physical files in storage/app/public/scans
    $files = Storage::disk('public')->allFiles('scans');
    if (!empty($files)) {
        Storage::disk('public')->delete($files);
        echo "Deleted " . count($files) . " scanned files.\n";
    } else {
        echo "No scanned files found to delete.\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
