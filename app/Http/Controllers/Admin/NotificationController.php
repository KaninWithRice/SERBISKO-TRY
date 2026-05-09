<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyncConflict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Get all pending notifications for the admin header.
     */
    public function index()
    {
        $notifications = collect();

        // 1. Sync Conflicts
        $conflicts = SyncConflict::where('status', 'pending')->get();
        foreach ($conflicts as $conflict) {
            $notifications->push([
                'id' => 'sync_' . $conflict->id,
                'type' => 'sync_conflict',
                'title' => 'Sync Conflict Detected',
                'description' => 'A data mismatch was found for ' . ($conflict->incoming_data_json['first_name'] ?? 'a student'),
                'time' => Carbon::parse($conflict->created_at)->diffForHumans(),
                'link' => route('admin.action-center') . '?tab=conflict',
                'is_read' => false, // We'll handle read status if needed, but for now showing active ones
            ]);
        }

        // 2. Manual Verification Queue
        $pendingScans = DB::table('scans')
            ->where('status', 'manual_verification')
            ->join('users', 'scans.user_id', '=', 'users.id')
            ->select('scans.*', 'users.first_name', 'users.last_name')
            ->get();
        
        foreach ($pendingScans as $scan) {
            $notifications->push([
                'id' => 'verify_' . $scan->id,
                'type' => 'verification',
                'title' => 'Manual Verification Needed',
                'description' => $scan->first_name . ' ' . $scan->last_name . ' submitted a ' . $scan->document_type,
                'time' => Carbon::parse($scan->created_at)->diffForHumans(),
                'link' => route('admin.action-center') . '?tab=verify',
                'is_read' => false,
            ]);
        }

        // 3. Rejected Papers (Physical Bin)
        $enrollmentsWithRejections = DB::table('kiosk_enrollments')
            ->whereNotNull('rejected_papers')
            ->where('rejected_papers', '!=', '[]')
            ->join('students', 'kiosk_enrollments.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select('users.first_name', 'users.last_name', 'kiosk_enrollments.rejected_papers', 'kiosk_enrollments.updated_at')
            ->get();

        foreach ($enrollmentsWithRejections as $enrollment) {
            $papers = json_decode($enrollment->rejected_papers, true) ?? [];
            foreach ($papers as $paper) {
                $notifications->push([
                    'id' => 'rejected_' . $enrollment->first_name . '_' . ($paper['rejected_at'] ?? ''),
                    'type' => 'rejected_paper',
                    'title' => 'Paper Rejected (Physical Bin)',
                    'description' => $enrollment->first_name . '\'s ' . ($paper['document_type'] ?? 'document') . ' was rejected.',
                    'time' => Carbon::parse($paper['rejected_at'] ?? $enrollment->updated_at)->diffForHumans(),
                    'link' => route('admin.action-center') . '?tab=bin',
                    'is_read' => false,
                ]);
            }
        }

        // Sort by time (most recent first)
        $sorted = $notifications->sortByDesc(function ($n) {
            return $n['time'];
        })->values();

        return response()->json([
            'count' => $sorted->count(),
            'notifications' => $sorted->take(10), // Limit to 10 for the dropdown
        ]);
    }

    /**
     * Mark a notification as read (Placeholder for future database notification integration)
     */
    public function markAsRead(Request $request)
    {
        // For now, since we query active records, "marking as read" would mean resolving the underlying issue.
        // If we use Laravel's notification system later, this is where we'd mark it read in the DB.
        return response()->json(['status' => 'success']);
    }
}