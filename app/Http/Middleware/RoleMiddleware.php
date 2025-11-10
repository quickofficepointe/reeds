<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $roles  Comma separated role IDs
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        // Parse role parameters (e.g., "1,2" becomes [1, 2])
        $roleArray = explode(',', $roles);
        $roleIds = array_map('intval', $roleArray);

        // Check if user has any of the required roles
        if (!Auth::user()->hasAnyRole($roleIds)) {
            $roleNames = array_map(function($roleId) {
                return match($roleId) {
                    1 => 'Administrator',
                    2 => 'Vendor',
                    default => 'Role ' . $roleId,
                };
            }, $roleIds);

            $requiredRoles = implode(' or ', $roleNames);
            abort(403, "Insufficient permissions. Required: {$requiredRoles}");
        }

        return $next($request);
    }
}
