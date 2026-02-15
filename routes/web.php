<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- 1. PUBLIC ROUTES (Login) ---

Route::get('/', function () {
    // If already logged in, redirect based on role
    if (session()->has('user_id')) {
        if (session('user_role') === 'admin') {
            return redirect('/admin/dashboard');
        }
        // If student, go to the first step of enrollment
        return redirect('/student/grade-selection');
    }
    return view('login');
});

// Handle Login Form Submission
Route::post('/login', [AuthController::class, 'login']);

// Logout Logic
Route::get('/logout', [AuthController::class, 'logout']);


// --- 2. ADMIN ROUTES (Protected) ---

Route::get('/admin/dashboard', function () {
    // Security Check: Must be logged in AND be an admin
    if (!session()->has('user_id') || session('user_role') !== 'admin') {
        return redirect('/');
    }
    
    return view('admin.dashboard');
});


// --- 3. STUDENT ROUTES (Protected) ---

// STEP 1: Grade Selection Screen
Route::get('/student/grade-selection', function () {
    if (!session()->has('user_id')) return redirect('/');
    
    return view('student.selection');
});

// STEP 1-B: Handle Grade Selection (Save & Redirect)
Route::post('/student/save-grade', function (Request $request) {
    // Save the selected grade to the session
    session(['grade_level' => $request->input('grade_level')]);
    
    // Redirect to the next step (Status Selection)
    return redirect('/student/status-selection');
});

// STEP 2: Status Selection Screen
Route::get('/student/status-selection', function () {
    if (!session()->has('user_id')) return redirect('/');
    
    return view('student.status');
});

// STEP 2-B: Handle Status Selection (Save & Debug)
Route::post('/student/save-status', function (Request $request) {
    // Save the selected status to the session
    session(['student_status' => $request->input('student_status')]);
    
    // TEMPORARY: Dump the session data so you can verify it works
    // We will replace this with a redirect to the next page later
    dd(session()->all());
});