<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KioskEnrollment extends Model
{
    protected $fillable = [
        'student_id', 'academic_status', 'grade_level', 'track', 'cluster',
        'sf9_path', 'sf9_status', 'sf9_remarks', 'sf9_attempts',
        'psa_path', 'psa_status', 'psa_remarks', 'psa_attempts',
        'enroll_form_path', 'enroll_form_status', 'enroll_form_remarks', 'enroll_form_attempts',
        'als_cert_path', 'als_cert_status', 'als_cert_remarks', 'als_cert_attempts',
        'affidavit_path', 'affidavit_status', 'affidavit_remarks', 'affidavit_attempts',
        'good_moral_path', 'good_moral_status', 'good_moral_remarks', 'good_moral_attempts',
        'sf10_path', 'sf10_status', 'sf10_remarks', 'sf10_attempts',
        'latest_scan_type', 'latest_scan_status', 'latest_scan_remarks',
        'rejected_papers', 'started_at', 'completed_at', 'receipt_number'
    ];

    protected $casts = [
        'rejected_papers' => 'array', // Automatically handles JSON conversion
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}