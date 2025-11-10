<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ProfileCompleteMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Skip profile check for these routes
        if ($request->routeIs('profile.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        // Check if profile exists and is complete
        if (!$user->profile || !$user->profile->isComplete()) {
            return redirect()->route('profile.edit')->with('warning', 'Please complete your profile before proceeding.');
        }

        // For vendors, check if they are verified by admin
        if ($user->isVendor() && !$user->profile->isVerified()) {
            return redirect()->route('profile.edit')->with('warning', 'Your account is pending verification by an administrator.');
        }

        return $next($request);
    }
}
