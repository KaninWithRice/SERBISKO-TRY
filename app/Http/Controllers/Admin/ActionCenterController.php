<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyncConflict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ActionCenterController extends Controller
{
    public function index()
    {
        // 1. Manual Verification Data
        $pendingScans = DB::table('scans')
            ->join('users', 'scans.user_id', '=', 'users.id')
            ->leftJoin('students', 'users.id', '=', 'students.user_id')
            ->leftJoin('kiosk_enrollments', 'students.id', '=', 'kiosk_enrollments.student_id')
            ->leftJoin('pre_enrollments as pe', 'students.id', '=', 'pe.student_id')
            ->where('scans.status', 'manual_verification')
            ->select(
                'scans.id',
                'scans.document_type',
                'scans.file_path',
                'scans.created_at',
                'users.first_name', 
                'users.last_name',
                'users.id as user_primary_id',
                'kiosk_enrollments.grade_level as kiosk_grade',
                'pe.responses'
            )
            ->get()
            ->map(function($scan) {
                $details = json_decode($scan->responses, true) ?? [];
                $rawGrade = $scan->kiosk_grade ?? ($details['Grade Level to Enroll'] ?? '—');
                $scan->display_grade = is_array($rawGrade) ? implode(', ', $rawGrade) : $rawGrade;
                return $scan;
            });

        // 2. Data Conflicts Data
        $conflicts = SyncConflict::pending()
            ->with(['existingUser.student', 'resolver'])
            ->latest()
            ->get(); // Using get() instead of paginate for the unified view counts

        // 3. Rejection Bin Data
        $enrollmentsWithRejections = DB::table('kiosk_enrollments')
            ->whereNotNull('rejected_papers')
            ->where('rejected_papers', '!=', '[]')
            ->join('students', 'kiosk_enrollments.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->leftJoin('pre_enrollments as pe', 'students.id', '=', 'pe.student_id')
            ->select(
                'users.id as user_id',
                'users.first_name',
                'users.last_name',
                'kiosk_enrollments.grade_level as kiosk_grade',
                'kiosk_enrollments.rejected_papers',
                'pe.responses'
            )
            ->get();

        $rejectedPapers = collect();
        foreach ($enrollmentsWithRejections as $enrollment) {
            $papers = json_decode($enrollment->rejected_papers, true) ?? [];
            $details = json_decode($enrollment->responses, true) ?? [];
            $rawGrade = $enrollment->kiosk_grade ?? ($details['Grade Level to Enroll'] ?? '—');
            $displayGrade = is_array($rawGrade) ? implode(', ', $rawGrade) : $rawGrade;
            
            foreach ($papers as $paper) {
                $rejectedPapers->push((object)[
                    'user_id' => $enrollment->user_id,
                    'first_name' => $enrollment->first_name,
                    'last_name' => $enrollment->last_name,
                    'display_grade' => $displayGrade,
                    'document_type' => $paper['document_type'] ?? 'Unknown',
                    'rejected_at' => $paper['rejected_at'] ?? now()->toDateTimeString(),
                ]);
            }
        }
        $rejectedPapers = $rejectedPapers->sortByDesc('rejected_at');

        if (request()->ajax()) {
            return view('admin.partials.verification-table', compact('pendingScans'))->render();
        }

        return view('admin.action-center', compact('pendingScans', 'conflicts', 'rejectedPapers'));
    }
}
