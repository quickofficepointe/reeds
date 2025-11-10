<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function show()
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return redirect()->route('profile.edit');
        }

        return view('reeds.vendor.profile.show', compact('profile'));
    }

    /**
     * Show the form for editing the profile.
     */
    public function edit()
    {
        $user = Auth::user();
        $profile = $user->profile ?? new Profile();

        return view('reeds.vendor.profile.edit', compact('profile'));
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'phone_number' => 'required|string|max:20',
            'bio' => 'nullable|string|max:500',
        ]);

        $profileData = [
            'phone_number' => $request->phone_number,
            'bio' => $request->bio,
        ];

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->profile && $user->profile->photo) {
                Storage::delete($user->profile->photo);
            }

            $path = $request->file('photo')->store('profiles', 'public');
            $profileData['photo'] = $path;
        }

        // Create or update profile
        if ($user->profile) {
            $user->profile->update($profileData);
            $user->profile->markAsComplete();
        } else {
            $profile = Profile::create(array_merge($profileData, [
                'user_id' => $user->id,
                'is_complete' => true,
            ]));
        }

        return redirect()->route('profile.show')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Admin: Verify a vendor's profile
     */
    public function verify(Profile $profile)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if (!$profile->user->isVendor()) {
            return redirect()->back()->with('error', 'Only vendor profiles can be verified.');
        }

        $profile->markAsVerified(Auth::id());

        return redirect()->back()->with('success', 'Vendor profile verified successfully!');
    }

    /**
     * Admin: List pending vendor verifications
     */
   /**
 * Admin: List pending vendor verifications
 */
public function pendingVerifications()
{
    if (!Auth::user()->isAdmin()) {
        abort(403, 'Unauthorized action.');
    }

    $pendingVendors = Profile::whereHas('user', function ($query) {
        $query->where('role', 2); // Vendor role
    })
    ->where('is_verified', false)
    ->with('user')
    ->latest()
    ->paginate(10);

    $verifiedThisMonth = Profile::whereHas('user', function ($query) {
        $query->where('role', 2);
    })
    ->where('is_verified', true)
    ->where('updated_at', '>=', now()->startOfMonth())
    ->count();

    $totalVendors = \App\Models\User::where('role', 2)->count();

    return view('reeds.admin.verification.index', compact(
        'pendingVendors',
        'verifiedThisMonth',
        'totalVendors'
    ));
}

}
