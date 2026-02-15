<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    // Handle the Login Submission
    public function login(Request $request)
    {
        // 1. Get inputs
        $lastName = $request->input('last_name');
        $givenName = $request->input('given_name');
        $middleName = $request->input('middle_name');
        $dob = $request->input('dob'); // YYYY-MM-DD
        $password = $request->input('password');

        // 2. Find user in Database
        // IMPROVEMENT: Now checks Middle Name as well
        $user = DB::table('users')
            ->where('last_name', $lastName)
            ->where('first_name', $givenName)
            ->where('middle_name', $middleName) // <--- Added this check
            ->where('birthday', $dob)
            ->first();

        // 3. Check if user exists and password is correct
        if ($user && Hash::check($password, $user->password)) {
            
            // Save user info to session
            Session::put('user_id', $user->id);
            Session::put('user_role', $user->role);
            Session::put('user_name', $user->first_name);

            // 4. Redirect based on Role
            if ($user->role === 'admin') {
                return redirect('/admin/dashboard');
            } else {
                return redirect('/student/grade-selection');
            }
        }

        // 5. Login Failed
        return back()->withErrors(['message' => 'Invalid credentials. Please check your details.']);
    }

    public function logout() {
        Session::flush();
        return redirect('/');
    }
}