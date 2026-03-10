<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MarkUserAsOnline
{
    public function handle(Request $request, Closure $next)
    {
        // Use whatever session key you use for the ID (e.g., 'user_id')
        $userId = session('user_id'); 

        if ($userId) {
            \Illuminate\Support\Facades\Cache::put('user-is-online-' . $userId, true, now()->addMinutes(2));
        }

        return $next($request);
    }
}