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
        // 1. Get inputs
        $lastName = $request->input('last_name');
        $givenName = $request->input('given_name');
        $middleName = $request->input('middle_name');
        $dob = $request->input('dob');
        $password = $request->input('password');

        // 2. Find user using Eloquent (Required for Auth::login)
        $query = User::where('last_name', $lastName)
            ->where('first_name', $givenName)
            ->where('birthday', $dob)
            ->whereNull('deleted_at');

        if (!empty($middleName)) {
            $query->where('middle_name', $middleName);
        } else {
            $query->where(function($q) {
                $q->whereNull('middle_name')->orWhere('middle_name', '');
            });
        }

        $user = $query->first();

        // 3. Authenticate
        if ($user && Hash::check($password, $user->password)) {
            
            // CRITICAL: Log the user into Laravel's Auth system
            // This is what makes auth()->user() work in your header
            Auth::login($user);

            $role = strtolower($user->role); 

            // Save manual keys for your CheckAdmin middleware
            Session::put('user_id', $user->id);
            Session::put('user_role', $role); 
            Session::put('user_name', $user->first_name);

            // 4. Redirect based on Role
            if (in_array($role, ['admin', 'super_admin', 'facilitator'])) {
                return redirect('/dashboard'); 
            } else {
                return redirect('/student/grade-selection');
            }
        }

        return back()->withErrors(['message' => 'Invalid credentials. Please check your details.']);
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