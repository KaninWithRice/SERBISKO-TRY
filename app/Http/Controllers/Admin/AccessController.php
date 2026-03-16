<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


class AccessController extends Controller
{
    public function accountsettings(){ return view('admin.account_settings.accountsettings'); }
    /**
     * Show the list of Staff/Admins
     */
    public function accessManagement(Request $request)
    {
        $activeTab = $request->get('role', 'All');

        if ($activeTab === 'Archived') {
            $staff = User::onlyTrashed()
                ->whereIn('role', ['super_admin', 'admin', 'facilitator'])
                ->get();
        } else {
            $query = User::query()->whereIn('role', ['super_admin', 'admin', 'facilitator']);

            if ($activeTab !== 'All') {
                $roleMap = [
                    'Administrator' => 'admin',
                    'Facilitator'   => 'facilitator'
                ];
                $targetRole = $roleMap[$activeTab] ?? strtolower($activeTab);
                $query->where('role', $targetRole);
            }
            $staff = $query->get();
        }

        return view('admin.accessmanagement_page.accessmanagement', compact('staff'));
    }

    /**
     * Create a new Staff/Admin user
     */
    public function storeUser(Request $request)
        {
            $validated = $request->validate([
                'first_name'     => 'required|string|max:255', 
                'last_name'      => 'required|string|max:255',
                'middle_name'    => 'nullable|string|max:255',
                'extension_name' => 'nullable|string|max:10',
                'birthday'       => 'required|date',
                'role'           => 'required|string|in:admin,administrator,facilitator,super_admin',
                'password'       => 'sometimes|nullable|string|min:8',
            ]);

            $roleMap = [
                'admin'         => 'admin',
                'administrator' => 'admin',
                'facilitator'   => 'facilitator',
                'super_admin'   => 'super_admin'
            ];

            $finalRole = $roleMap[strtolower($validated['role'])] ?? 'facilitator';

            $user = User::withTrashed()
                ->where('first_name', $validated['first_name'])
                ->where('last_name', $validated['last_name'])
                ->where('birthday', $validated['birthday'])
                ->first();

            if ($user) {
                if ($user->trashed()) {
                    $user->restore();
                    $statusMessage = 'User found in archives and access has been restored!';
                } else {
                    return back()->with('info', 'This user is already active in the system.');
                }

                $updateData = [
                    'middle_name'    => $validated['middle_name'],
                    'extension_name' => $validated['extension_name'],
                    'role'           => $finalRole,
                ];

                if ($request->filled('password')) {
                    $updateData['password'] = Hash::make($request->password);
                }

                $user->update($updateData);

            } else {
                if (!$request->filled('password')) {
                    return back()->withErrors(['password' => 'A password is required for new users.']);
                }

                User::create([
                    'first_name'     => $validated['first_name'],
                    'last_name'      => $validated['last_name'],
                    'middle_name'    => $validated['middle_name'],
                    'extension_name' => $validated['extension_name'],
                    'birthday'       => $validated['birthday'],
                    'role'           => $finalRole,
                    'password'       => Hash::make($request->password),
                ]);
                $statusMessage = 'New staff member added successfully!';
            }

            return redirect()->route('admin.accessmanagement')->with('success', $statusMessage);
        }

    /**
     * Update a user's role
     */
    public function updateRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (Auth::id() == $user->id) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,facilitator,super_admin',
        ]);

        $user->update(['role' => $validated['role']]);

        return back()->with('success', "Role updated successfully.");
    }

    /**
     * Soft delete a user
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (Auth::id() == $user->id) {
            return back()->with('error', 'You cannot revoke your own access.');
        }

        $user->delete(); 

        return back()->with('success', 'User access has been revoked successfully.');
    }

    /**
     * Restore a deleted user
     */
    public function restoreUser($id)
    {
        User::onlyTrashed()->findOrFail($id)->restore();
        return back()->with('success', 'User access restored!');
    }

}