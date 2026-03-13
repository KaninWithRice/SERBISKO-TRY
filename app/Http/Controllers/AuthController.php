<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Backend Validation
        $request->validate([
            'last_name' => 'required|string',
            'given_name' => 'required|string',
            'dob' => 'required|date',
            'password' => 'required',
        ], [
            'last_name.required' => 'Required.',
            'given_name.required' => 'Required.',
            'dob.required' => 'Required.',
            'password.required' => 'Required.',
        ]);

        // 2. Build the query for a full match (including Middle Name logic)
        $query = User::where('last_name', $request->last_name)
            ->where('first_name', $request->given_name)
            ->where('birthday', $request->dob)
            ->whereNull('deleted_at');

        if ($request->filled('middle_name')) {
            $query->where('middle_name', $request->middle_name);
        } else {
            $query->where(function($q) {
                $q->whereNull('middle_name')->orWhere('middle_name', '');
            });
        }

        $user = $query->first();

        // 3. Handle Identity Failure
        if (!$user) {
            // Check if the user exists ignoring the middle name
            $partialMatch = User::where('last_name', $request->last_name)
                ->where('first_name', $request->given_name)
                ->where('birthday', $request->dob)
                ->whereNull('deleted_at')
                ->exists();

            if ($partialMatch) {
                // Logic: Identity is correct, but Middle Name input vs DB value mismatched
                return back()->withErrors([
                    'middle_name' => 'Please enter your middle name.'
                ])->withInput($request->except('password'));
            }

            // Generic error for all other identity failures
            return back()->withErrors([
                'message' => 'Access Denied. The information provided does not match our records.'
            ])->withInput($request->except('password'));
        }

        // 4. Password Verification
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Incorrect password.'
            ])->withInput($request->except('password'));
        }

        // 5. Success - Authenticate and Session management
        Auth::login($user);
        $role = strtolower($user->role); 

        Session::put('user_id', $user->id);
        Session::put('user_role', $role); 
        Session::put('user_name', $user->first_name);

        if (in_array($role, ['admin', 'super_admin', 'facilitator'])) {
            return redirect('/dashboard'); 
        }
        
        return redirect('/student/grade-selection');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['user_id', 'user_role', 'user_name']);

        // Standard Laravel logout
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function updatePassword(Request $request)
    {
        // 1. Backend Validation
        // We use the same rules as your frontend to ensure security if JS is disabled.
        $request->validate([
            'current_password' => ['required'],
            'new_password' => [
                'required',
                'confirmed', // Automatically looks for new_password_confirmation
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ], [
            // Custom messages to match your specific UI requirements
            'current_password.required' => 'Current password is required.',
            'new_password.required' => 'New password is required.',
            'new_password.confirmed' => 'Passwords do not match.',
        ]);

        $user = Auth::user();

        // 2. Verify the "Current Password" field matches the actual DB password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'The provided password does not match our records.'
            ])->withInput();
        }

        // 3. Hash and Save the new password
        $user->password = Hash::make($request->new_password);
        $user->save();

        // 4. Redirect with a success notification
        return back()->with('success', 'Your password has been successfully updated.');
    }
}