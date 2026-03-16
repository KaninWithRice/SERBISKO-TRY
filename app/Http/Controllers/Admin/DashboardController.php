<?php

namespace App\Http\Controllers\Admin; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request) 
    {
        $settings = DB::table('system_settings')->first();
        $activeSY = $settings ? $settings->active_school_year : '2025-2026';
        $grade = $request->grade_level;

        // --- NEW: Subquery for latest version ---
        $latestPreEnrollments = DB::table('pre_enrollments')
            ->select('student_id', DB::raw('MAX(submission_version) as max_version'))
            ->groupBy('student_id');

        // Reusable filter updated with Version Control
        $applyFilter = function($query) use ($grade, $activeSY) {
            $query->where('students.school_year', $activeSY);

            if (!empty($grade)) {
                $query->where(function($q) use ($grade) {
                    $q->where('kiosk_enrollments.grade_level', '=', $grade)
                    ->orWhere(function($sq) use ($grade) {
                        $sq->whereNull('kiosk_enrollments.grade_level')
                            ->where('pre_enrollments.responses', 'like', '%"Grade Level to Enroll":"' . $grade . '"%');
                    });
                });
            }
            return $query;
        };

        // --- Core Enrollment Stats (Now Joined with Latest Version Only) ---
        $baseQuery = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id') 
            ->whereNull('users.deleted_at')
            // Join the version map
            ->leftJoinSub($latestPreEnrollments, 'latest_v', function($join) {
                $join->on('students.id', '=', 'latest_v.student_id');
            })
            // Join the actual responses for the latest version only
            ->leftJoin('pre_enrollments', function($join) {
                $join->on('students.id', '=', 'pre_enrollments.student_id')
                     ->on('pre_enrollments.submission_version', '=', 'latest_v.max_version');
            })
            ->leftJoin('kiosk_enrollments', 'users.id', '=', 'kiosk_enrollments.id');

        $totalRegistrations = $applyFilter(clone $baseQuery)->count();

        $totalSubmissions = $applyFilter(clone $baseQuery)
            ->whereNotNull('kiosk_enrollments.id')
            ->count();

        $totalEnrolled = $applyFilter(clone $baseQuery)
            ->where('kiosk_enrollments.academic_status', '=', 'Officially Enrolled')
            ->count();

        // --- Stats Calculation ---
        $max = $totalRegistrations > 0 ? $totalRegistrations : 1;
        $percVerified = ($totalSubmissions / $max) * 100;
        $percEnrolled = ($totalEnrolled / $max) * 100;

        // --- Elective Counting (Filtered by SY and User Status) ---
        $rawCounts = DB::table('kiosk_enrollments')
            ->join('users', 'kiosk_enrollments.id', '=', 'users.id')
            ->join('students', 'users.id', '=', 'students.user_id')
            ->whereNull('users.deleted_at')
            ->where('students.school_year', $activeSY)
            ->when(!empty($grade), function($query) use ($grade) {
                return $query->where('kiosk_enrollments.grade_level', $grade);
            })
            ->whereIn('cluster', ['STEM', 'ASSH', 'BE', 'TechPro'])
            ->select('cluster', DB::raw('count(*) as count'))
            ->groupBy('cluster')
            ->pluck('count', 'cluster')
            ->toArray();

        $electiveCounts = [
            'STEM'    => $rawCounts['STEM'] ?? 0,
            'ASSH'    => $rawCounts['ASSH'] ?? 0,
            'BE'      => $rawCounts['BE'] ?? 0,
            'TechPro' => $rawCounts['TechPro'] ?? 0
        ];

        // --- Recent Submissions ---
        $recentKioskSubmissions = DB::table('kiosk_enrollments')
            ->join('users', 'kiosk_enrollments.id', '=', 'users.id')
            ->join('students', 'users.id', '=', 'students.user_id')
            ->whereNull('users.deleted_at')
            ->where('students.school_year', $activeSY)
            ->select(
                'users.first_name', 'users.middle_name', 'users.last_name',
                'users.extension_name', 'kiosk_enrollments.grade_level',
                'kiosk_enrollments.track', 'kiosk_enrollments.cluster',
                'kiosk_enrollments.completed_at', 'kiosk_enrollments.academic_status as status'
            )
            ->when(!empty($grade), function($q) use ($grade) {
                return $q->where('kiosk_enrollments.grade_level', $grade);
            })
            ->orderBy('kiosk_enrollments.completed_at', 'desc')
            ->limit(5)
            ->get();

        // --- Sync & Gradient logic ---
        $lastSync = DB::table('sync_histories')->where('status', 'Success')->latest()->first();
        $lastSyncTime = $lastSync ? Carbon::parse($lastSync->created_at)->diffForHumans() : 'Never';

        $totalElectives = array_sum($electiveCounts) ?: 1;
        $pSTEM = ($electiveCounts['STEM'] / $totalElectives) * 100;
        $pASSH = ($electiveCounts['ASSH'] / $totalElectives) * 100;
        $pBE   = ($electiveCounts['BE'] / $totalElectives) * 100;
        $pTech = ($electiveCounts['TechPro'] / $totalElectives) * 100;

        $stop1 = $pSTEM;
        $stop2 = $stop1 + $pASSH;
        $stop3 = $stop2 + $pBE;

        $donutGradient = "conic-gradient(#00568d 0% {$stop1}%, #00897b {$stop1}% {$stop2}%, #1a8a44 {$stop2}% {$stop3}%, #facc15 {$stop3}% 100%)";

        $data = compact(
            'totalRegistrations', 'totalSubmissions', 'totalEnrolled',
            'percVerified', 'percEnrolled', 'electiveCounts',
            'donutGradient', 'lastSyncTime', 'recentKioskSubmissions', 'activeSY'
        );

        if ($request->ajax()) {
            return view('admin.dashboardpage.partials._dashboard_wrapper', $data)->render();
        }

        return view('admin.dashboardpage.dashboard', $data);
    }

    public function checkUserStatus($id)
    {
        $isOnline = Cache::has('user-is-online-' . $id);
        return response()->json(['online' => $isOnline]);
    }
}