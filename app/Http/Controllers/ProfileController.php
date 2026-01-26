<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Unit; // Add this import
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
     * Admin: Verify a vendor's profile WITH UNIT ASSIGNMENT
     */
    public function verify(Profile $profile, Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if (!$profile->user->isVendor()) {
            return response()->json([
                'error' => 'Only vendor profiles can be verified.'
            ], 400);
        }

        // Validate unit_id if provided
        $request->validate([
            'unit_id' => 'nullable|exists:units,id'
        ]);

        try {
            // Update profile verification
            $profile->markAsVerified(Auth::id());

            // Assign unit to user if provided
            if ($request->has('unit_id') && $request->unit_id) {
                $profile->user->update(['unit_id' => $request->unit_id]);
            }

            return response()->json([
                'success' => 'Vendor verified successfully!' .
                    ($request->has('unit_id') ? ' Unit assigned.' : ''),
                'unit_assigned' => $request->has('unit_id') ? $request->unit_id : null
            ]);

        } catch (\Exception $e) {
            \Log::error('Verification failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to verify vendor.'
            ], 500);
        }
    }

    /**
     * Admin: List pending vendor verifications WITH UNITS DATA
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
        ->with(['user', 'user.unit']) // Load user's unit relationship
        ->latest()
        ->paginate(10);

        $verifiedThisMonth = Profile::whereHas('user', function ($query) {
            $query->where('role', 2);
        })
        ->where('is_verified', true)
        ->where('updated_at', '>=', now()->startOfMonth())
        ->count();

        $totalVendors = \App\Models\User::where('role', 2)->count();

        // Get all units for assignment dropdown
        $units = Unit::active()->get();

        return view('reeds.admin.verification.index', compact(
            'pendingVendors',
            'verifiedThisMonth',
            'totalVendors',
            'units' // Pass units to the view
        ));
    }

    /**
     * Admin: Reject a vendor's profile
     */
    public function reject(Profile $profile, Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            // You can store rejection reason in profile or separate table
            $profile->update([
                'verification_notes' => 'Rejected: ' . $request->reason,
                'is_verified' => false,
            ]);

            return response()->json([
                'success' => 'Vendor rejected successfully.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Rejection failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to reject vendor.'
            ], 500);
        }
    }

    /**
     * Get vendor details for modal
     */
    public function getVendorDetails(Profile $profile)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $profile->load(['user', 'user.unit']);

        return response()->json([
            'success' => true,
            'vendor' => [
                'id' => $profile->id,
                'name' => $profile->user->name,
                'email' => $profile->user->email,
                'phone' => $profile->phone_number,
                'business_name' => $profile->business_name ?? 'Not provided',
                'contact_person' => $profile->contact_person ?? 'Not specified',
                'location' => $profile->location ?? 'Not specified',
                'description' => $profile->description,
                'photo' => $profile->photo ? Storage::url($profile->photo) : null,
                'registered_at' => $profile->user->created_at->format('M d, Y'),
                'unit' => $profile->user->unit ? [
                    'id' => $profile->user->unit->id,
                    'name' => $profile->user->unit->name,
                    'code' => $profile->user->unit->code,
                ] : null,
                'current_unit' => $profile->user->unit_id
            ]
        ]);
    }
}
