<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyncConflict;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SyncConflictController extends Controller
{
    /**
     * Display a listing of pending conflicts.
     */
    public function index() 
    {
        $conflicts = SyncConflict::pending()
            ->with(['existingUser.student', 'resolver'])
            ->latest()
            ->paginate(10);
                
        return view('admin.syncconflict', compact('conflicts'));
    }

    /**
     * Resolve a specific data conflict.
     */
    public function resolve(Request $request, $id)
    {
        $action = $request->input('action');
        $conflict = SyncConflict::findOrFail($id);
        $incoming = $conflict->incoming_data_json;

        DB::beginTransaction();
        try {
            if ($action === 'accept_new') {
                // --- AUDIT INTEGRITY CHECK ---
                // If the user was deleted, existing_user_id is NULL. 
                // We shouldn't try to find it, but we should still mark the conflict resolved.
                if (!$conflict->existing_user_id) {
                    $conflict->status = 'resolved';
                    $conflict->resolution_action = 'ignored_missing_user';
                    $conflict->resolution_notes = "User record no longer exists. " . $request->input('notes');
                } else {
                    // User exists, proceed with update
                    $user = User::findOrFail($conflict->existing_user_id);
                    $student = $user->student;

                    // --- DATA SANITIZATION START ---
                    
                    // 1. Convert "Yes"/"No" strings to Boolean/Integer
                    if (isset($incoming['is_perm_same_as_curr'])) {
                        $incoming['is_perm_same_as_curr'] = (strtolower($incoming['is_perm_same_as_curr']) === 'yes') ? 1 : 0;
                    }

                    // 2. Format Birthday for MySQL (Y-m-d)
                    if (isset($incoming['birthday'])) {
                        try {
                            $incoming['birthday'] = Carbon::parse($incoming['birthday'])->format('Y-m-d');
                        } catch (\Exception $e) {
                            // Fallback if date format is weird
                        }
                    }

                    // 3. Clean up empty strings to NULL for optional fields
                    foreach ($incoming as $key => $value) {
                        if ($value === '' || $value === 'null' || $value === 'NULL') {
                            $incoming[$key] = null;
                        }
                    }

                    // --- DATA SANITIZATION END ---

                    // Update LRN first as it's the anchor
                    if (isset($incoming['lrn'])) {
                        $student->lrn = $incoming['lrn'];
                        $student->save();
                    }

                    // Map and Update remaining fields using fillables
                    $userFields = array_intersect_key($incoming, array_flip($user->getFillable()));
                    $studentFields = array_intersect_key($incoming, array_flip($student->getFillable()));

                    $user->update($userFields);
                    $student->update($studentFields);

                    $conflict->status = 'resolved';
                    $conflict->resolution_action = 'accept_new';
                }
            } else 
                {
                    // Admin chose "Keep Existing"
                    $conflict->status = 'resolved';
                    $conflict->resolution_action = 'rejected_new';

                    // OPTION 3: Set the shield flag so future syncs ignore this record
                    if ($conflict->existingUser && $conflict->existingUser->student) {
                        $conflict->existingUser->student->update(['is_manually_edited' => 1]);
                    }
                }

            // Finalize Conflict Record status for Audit Trail
            $conflict->resolved_by = Auth::id();
            $conflict->resolved_at = now();
            if (!$conflict->resolution_notes) { // Don't overwrite if we set a 'Missing User' note above
                $conflict->resolution_notes = $request->input('notes');
            }
            $conflict->save();

            DB::commit();
            return back()->with('success', 'Conflict resolution processed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Update Failed: ' . $e->getMessage());
        }
    }
}