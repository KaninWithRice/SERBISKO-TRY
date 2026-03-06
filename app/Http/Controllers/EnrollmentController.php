<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnrollmentController extends Controller
{
    // Helper to get LRN since it's used in every method
    private function getStudentLrn() {
        $student = DB::table('students')->where('user_id', session('user_id'))->first();
        return $student->lrn ?? null;
    }

    public function saveGrade(Request $request) {
        $request->validate(['grade_level' => 'required|in:11,12']);
        $lrn = $this->getStudentLrn();
        
        if (!$lrn) return back()->withErrors(['error' => 'No student record found.']);

        session(['grade_level' => $request->grade_level]);
        DB::table('kiosk_enrollments')->updateOrInsert(
            ['student_lrn' => $lrn],
            ['grade_level' => $request->grade_level, 'updated_at' => now()]
        );

        return redirect('/student/status-selection');
    }

    public function saveStatus(Request $request) {
        $lrn = $this->getStudentLrn();
        session(['student_status' => $request->student_status]);
        
        DB::table('kiosk_enrollments')->where('student_lrn', $lrn)
            ->update(['student_status' => $request->student_status]);

        return redirect('/student/track-selection');
    }

    public function saveTrack(Request $request) {
        $lrn = $this->getStudentLrn();
        session(['track' => $request->track]);
        
        DB::table('kiosk_enrollments')->where('student_lrn', $lrn)
            ->update(['track' => $request->track]);

        return redirect('/student/cluster-selection');
    }

    public function saveCluster(Request $request) {
        $cluster = $request->input('cluster');
        $lrn = $this->getStudentLrn();
        session(['cluster' => $cluster]);

        // Update Database
        DB::table('kiosk_enrollments')->where('student_lrn', $lrn)
            ->update(['cluster_choice' => $cluster]);

        // Arduino Physical Triggers
        try {
            Http::timeout(3)->post('http://127.0.0.1:51234/api/strand/' . $cluster);
            Http::timeout(3)->post('http://127.0.0.1:51234/api/door', ['action' => 'close']);
        } catch (\Exception $e) {
            Log::error("Arduino offline (Sorting Trigger): " . $e->getMessage());
        }

        return redirect('/student/cluster-loading');
    }

    public function saveChecklist(Request $request) {
    $studentStatus = session('student_status', 'Regular'); 
    $statusKey = strtolower($studentStatus);
    
    $firstDocs = [
        'regular'    => 'Report Card (SF9)',
        'als'        => 'ALS Certificate',
        'transferee' => 'Report Card (SF9)',
        'balik-aral' => 'Report Card (SF9)'
    ];
    
    session(['current_doc' => $firstDocs[$statusKey] ?? 'Report Card (SF9)']);
    return redirect('/student/capture');
    }

    public function showCapture(Request $request) {
        if (!session()->has('user_id')) return redirect('/');
        
        try {
            Http::post('http://127.0.0.1:51234/api/door', ['action' => 'open']);
        } catch (\Exception $e) {
            Log::error("Arduino Offline (Slot Open): " . $e->getMessage());
        }

        if ($request->has('doc')) {
            session(['current_doc' => $request->query('doc')]);
        }

        return view('student.capture');
    }
}