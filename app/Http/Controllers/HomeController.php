<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Check if user is authenticated using Auth facade
        if (Auth::check()) {
            $user = Auth::user();

            // Redirect users based on their role
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->isVendor()) {
                return redirect()->route('vendor.dashboard');
            }
        }

        // If not authenticated or no specific role, show default home
        return view('home');
    }
}
