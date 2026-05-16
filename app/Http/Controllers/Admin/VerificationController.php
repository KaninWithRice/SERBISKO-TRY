<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    public function verification()
    { 
        // Query the actual scans table for manual verification requests
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

        // Populate rejected papers from kiosk_enrollments table
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

        // Sort by most recent rejection
        $rejectedPapers = $rejectedPapers->sortByDesc('rejected_at');

        if (request()->ajax()) {
            return view('admin.partials.verification-table', compact('pendingScans'))->render();
        }

        return view('admin.verification', compact('pendingScans', 'rejectedPapers')); 
    }

    public function handleVerificationAction(Request $request) 
    {
        $scanId = $request->input('scan_id'); 
        $action = $request->input('action'); 
        
        $finalStatus = ($action === 'approve') ? 'verified' : 'failed';
        $remarks = 'Manually ' . $action . 'd by Admin';

        // 1. Update the actual scan record
        $scan = DB::table('scans')->where('id', $scanId)->first();
        if (!$scan) {
            return back()->with('error', 'Scan record not found.');
        }

        DB::table('scans')->where('id', $scanId)->update([
            'status' => $finalStatus,
            'remarks' => $remarks,
            'updated_at' => now()
        ]);

        // 2. Sync with kiosk_enrollments
        $student = DB::table('students')->where('user_id', $scan->user_id)->first();
        if ($student) {
            $prefix = $this->getPrefix($scan->document_type);
            
            DB::table('kiosk_enrollments')->where('student_id', $student->id)->update([
                "{$prefix}_status" => $finalStatus,
                "{$prefix}_remarks" => $remarks,
                'latest_scan_status' => $finalStatus,
                'latest_scan_remarks' => $remarks,
                'updated_at' => now()
            ]);
        }

        // 3. Trigger Arduino Success if approved (SKIP for SF9 Front to allow scanning the back)
        if ($action === 'approve') {
            $prefix = $this->getPrefix($scan->document_type);
            if ($prefix !== 'sf9') {
                $this->triggerArduinoSuccess();
            } else {
                Log::info("Admin Verification: SF9 Front approved. Skipping 'c0' command to allow back scan.");
            }
        }

        return back()->with('success', 'Document has been ' . $action . 'd.');
    }

    private function triggerArduinoSuccess() {
        try {
            // 1. Close Slot (F)
            Http::timeout(3)->post('http://' . env('SERVICE_HOST', '127.0.0.1') . ':51234/api/door', ['action' => 'close']);
            
            // 2. Trigger Conveyor Belt (c0)
            Http::timeout(3)->post('http://' . env('SERVICE_HOST', '127.0.0.1') . ':51234/api/conveyor/c0');
            
            Log::info("Admin Verification: Arduino Success commands (F + c0) sent.");
        } catch (\Exception $e) {
            Log::error("Admin Verification: Arduino Success Trigger failed: " . $e->getMessage());
        }
    }

    private function getPrefix($docType) {
        $lowerDoc = strtolower($docType);
        if (str_contains($lowerDoc, 'sf9 back') || str_contains($lowerDoc, 'report card back')) return 'sf9_back';
        if (str_contains($lowerDoc, 'report') || str_contains($lowerDoc, 'sf9')) return 'sf9';
        if (str_contains($lowerDoc, 'birth') || str_contains($lowerDoc, 'psa')) return 'psa';
        if (str_contains($lowerDoc, 'enrollment') || str_contains($lowerDoc, 'form')) return 'enroll_form';
        if (str_contains($lowerDoc, 'als') || str_contains($lowerDoc, 'alternative')) return 'als_cert';
        if (str_contains($lowerDoc, 'affidavit') || str_contains($lowerDoc, 'sworn')) return 'affidavit';
        if (str_contains($lowerDoc, 'moral')) return 'good_moral';
        if (str_contains($lowerDoc, '137') || str_contains($lowerDoc, 'sf10')) return 'sf10';
        return 'sf9'; // Fallback
    }

    public function collectRejectedPaper(Request $request)
    {
        $userId = $request->input('user_id');
        $rejectedAt = $request->input('rejected_at');

        $student = DB::table('students')->where('user_id', $userId)->first();
        if (!$student) {
            return back()->with('error', 'Student not found.');
        }

        $enrollment = DB::table('kiosk_enrollments')->where('student_id', $student->id)->first();
        if (!$enrollment || empty($enrollment->rejected_papers)) {
            return back()->with('error', 'No rejected papers found for this student.');
        }

        $papers = json_decode($enrollment->rejected_papers, true) ?? [];
        
        // Filter out the specific paper that was collected
        $updatedPapers = array_filter($papers, function($paper) use ($rejectedAt) {
            return $paper['rejected_at'] !== $rejectedAt;
        });

        // Re-index array
        $updatedPapers = array_values($updatedPapers);

        DB::table('kiosk_enrollments')->where('student_id', $student->id)->update([
            'rejected_papers' => json_encode($updatedPapers),
            'updated_at' => now()
        ]);

        return back()->with('success', 'Paper marked as collected and removed from the list.');
    }
}