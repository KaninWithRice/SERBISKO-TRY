<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CheckAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Check if the session has the 'admin' role
        if (Session::get('user_role') === 'admin') {
            return $next($request);
        }

        // If not admin, kick them back to login
        return redirect('/')->withErrors(['message' => 'Unauthorized access.']);
    }
}