<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnrollmentController extends Controller
{
    private function getUserId() {
        return session('user_id');
    }

    public function saveGrade(Request $request) {
        $request->validate(['grade_level' => 'required|in:11,12']);
        $userId = $this->getUserId();
        
        if (!$userId) return redirect('/login')->withErrors(['error' => 'Session expired.']);

        session(['grade_level' => $request->grade_level]);
        
        DB::table('kiosk_enrollments')->updateOrInsert(
            ['id' => $userId],
            [
                'grade_level' => $request->grade_level, 
                'updated_at' => now(),
                'started_at' => DB::raw('IFNULL(started_at, NOW())')
            ]
        );

        return redirect('/student/status-selection');
    }

    public function saveStatus(Request $request) {
        $userId = $this->getUserId();
        session(['student_status' => $request->student_status]);
        
        DB::table('kiosk_enrollments')->where('id', $userId)
            ->update(['academic_status' => $request->student_status]);

        return redirect('/student/track-selection');
    }

    public function saveTrack(Request $request) {
        $userId = $this->getUserId();
        session(['track' => $request->track]);
        
        DB::table('kiosk_enrollments')->where('id', $userId)
            ->update(['track' => $request->track]);

        return redirect('/student/cluster-selection');
    }

    public function saveCluster(Request $request) {
        $cluster = $request->input('cluster');
        $userId = $this->getUserId();
        session(['cluster' => $cluster]);

        // Update Database
        DB::table('kiosk_enrollments')->where('id', $userId)
            ->update(['cluster' => $cluster]);

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