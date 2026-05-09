<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyncConflict;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
     *
     * Actions:
     *  - accept_new   → overwrite existing record with incoming data
     *  - rejected_new → keep existing record; lock it against future auto-sync
     */
    public function resolve(Request $request, $id)
    {
        $action   = $request->input('action');
        $conflict = SyncConflict::findOrFail($id);
        $incoming = $conflict->incoming_data_json;  // already decoded by model cast

        DB::beginTransaction();
        try {

            if ($action === 'accept_new') {

                // ── Guard: existing user may have been deleted since conflict was logged ──
                if (! $conflict->existing_user_id) {
                    $conflict->status            = 'resolved';
                    $conflict->resolution_action = 'ignored_missing_user';
                    $conflict->resolution_notes  =
                        'User record no longer exists. ' . $request->input('notes');

                } else {

                    $user    = User::findOrFail($conflict->existing_user_id);
                    $student = $user->student;

                    // ── DATA SANITIZATION ──────────────────────────────────────────────
                    // Normalize empty-string / "null" strings → actual null
                    foreach ($incoming as $key => $value) {
                        if ($value === '' || $value === 'null' || $value === 'NULL') {
                            $incoming[$key] = null;
                        }
                    }

                    // Boolean coercion for is_perm_same_as_curr
                    if (isset($incoming['is_perm_same_as_curr'])) {
                        $incoming['is_perm_same_as_curr'] =
                            (strtolower((string) $incoming['is_perm_same_as_curr']) === 'yes') ? 1 : 0;
                    }

                    // Date normalisation for birthday
                    if (isset($incoming['birthday'])) {
                        try {
                            $incoming['birthday'] = Carbon::parse($incoming['birthday'])->format('Y-m-d');
                        } catch (\Exception $e) {
                            // Keep original value if Carbon cannot parse it
                        }
                    }

                    // ── LRN CHANGE — Rule §1.2 & §4 ───────────────────────────────────
                    // Any approved LRN change must:
                    //   1. Update the LRN on the student record
                    //   2. Reset the user's password to the new LRN (students use LRN as password)
                    if (isset($incoming['lrn']) && $incoming['lrn'] !== $student->lrn) {
                        $newLrn        = $incoming['lrn'];
                        $student->lrn  = $newLrn;

                        // Rule §4: password = LRN (hashed via bcrypt through the model mutator)
                        $user->password = $newLrn;   // mutator in User model hashes this

                        unset($incoming['lrn']); // prevent double-update via fillable loop below
                    }

                    // ── MAP INCOMING DATA TO MODELS VIA FILLABLE ───────────────────────
                    $userFields    = array_intersect_key($incoming, array_flip($user->getFillable()));
                    $studentFields = array_intersect_key($incoming, array_flip($student->getFillable()));

                    if (! empty($userFields)) {
                        $user->update($userFields);
                    }

                    // Lock this record against future auto-sync so sync.js respects admin decision
                    $studentFields['is_manually_edited'] = 1;
                    $student->update($studentFields);

                    // ── INSERT NEW pre_enrollments VERSION ────────────────────────────
                    // sync.js exits early on conflict and never writes a pre_enrollments row.
                    // We insert one here so extra fields (cluster, track, grade_level,
                    // academic_status) are reflected in the dashboard and student profile.
                    $excludedKeys = [
                        'first_name', 'last_name', 'middle_name', 'extension_name',
                        'birthday', 'lrn', 'password', 'role', 'updated_at', 'created_at',
                        'isSynced', 'extra_fields', 'form_id', 'submitted_at', 'school_year',
                        'sex', 'age', 'place_of_birth', 'mother_tongue',
                        'curr_house_number', 'curr_street', 'curr_barangay', 'curr_city',
                        'curr_province', 'curr_zip_code', 'curr_country', 'is_perm_same_as_curr',
                        'perm_house_number', 'perm_street', 'perm_barangay', 'perm_city',
                        'perm_province', 'perm_zip_code', 'perm_country',
                        'mother_last_name', 'mother_first_name', 'mother_middle_name', 'mother_contact_number',
                        'father_last_name', 'father_first_name', 'father_middle_name', 'father_contact_number',
                        'guardian_last_name', 'guardian_first_name', 'guardian_middle_name', 'guardian_contact_number',
                    ];

                    // Extra fields may be at top level OR nested under 'extra_fields' key
                    $incomingJson = $conflict->incoming_data_json;
                    $nestedExtras = $incomingJson['extra_fields'] ?? [];
                    $topLevelExtras = array_diff_key($incomingJson, array_flip($excludedKeys));

                    // Merge both — nested extra_fields takes priority
                    $extraFields = array_merge($topLevelExtras, $nestedExtras);
                    unset($extraFields['extra_fields']); // remove the nested key itself if present

                    if (!empty($extraFields) && $student) {
                        $latestVersion = DB::table('pre_enrollments')
                            ->where('student_id', $student->id)
                            ->max('submission_version') ?? 0;

                        DB::table('pre_enrollments')->insert([
                            'student_id'         => $student->id,
                            'submission_version' => $latestVersion + 1,
                            'responses'          => json_encode($extraFields),
                            'status'             => 'Synced',
                            'created_at'         => now(),
                        ]);
                    }

                    $conflict->status            = 'resolved';
                    $conflict->resolution_action = 'accept_new';
                }

            } else {

                // ── KEEP EXISTING ──────────────────────────────────────────────────
                // Admin rejected the incoming data; shield the existing record
                // from future auto-sync attempts by locking is_manually_edited.
                $conflict->status            = 'resolved';
                $conflict->resolution_action = 'rejected_new';

                if ($conflict->existingUser && $conflict->existingUser->student) {
                    $conflict->existingUser->student->update(['is_manually_edited' => 1]);
                }
            }

            // ── AUDIT TRAIL ────────────────────────────────────────────────────────
            $conflict->resolved_by = Auth::id();
            $conflict->resolved_at = now();

            if (! $conflict->resolution_notes) {
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