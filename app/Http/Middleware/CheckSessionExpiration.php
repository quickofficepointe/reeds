<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSessionExpiration
{
    public function handle(Request $request, Closure $next)
    {
        $lastActivity = session('last_activity');
        $sessionLifetime = config('session.lifetime') * 60; // Convert minutes to seconds

        if ($lastActivity && (time() - $lastActivity > $sessionLifetime)) {
            Auth::logout();
            session()->flush();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'session_expired' => true,
                    'message' => 'Your session has expired.'
                ], 401);
            }
          
            return redirect()->route('login')
                ->with('error', 'Your session has expired. Please login again.');
        }

        // Update last activity
        session(['last_activity' => time()]);

        return $next($request);
    }
}
