<?php

namespace App\Http\Controllers;

use App\Models\NewEmployeeOnboarding;
use App\Models\Department;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeOnboardingController extends Controller
{
    /**
     * Show onboarding start page
     */
    public function start()
    {
        $departments = Department::where('is_active', true)->get();
        $units = Unit::where('is_active', true)->get();

        return view('frontend.index', compact('departments', 'units'));
    }

    /**
     * Store new onboarding (SINGLE PAGE - ALL FIELDS)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Personal Information
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'middle_name'       => 'nullable|string|max:255',
            'personal_phone'    => 'required|string|max:20',
            'personal_email'    => 'required|email|unique:new_employee_onboarding,personal_email',
            'date_of_birth'     => 'nullable|date',
            'gender'            => 'nullable|in:Male,Female,Other',

            // Employment Details
            'designation'       => 'required|string|max:255',
            'date_of_joining'   => 'required|date',
            'department_id'     => 'required|exists:departments,id',
            'unit_id'           => 'nullable|exists:units,id',
            'employment_type'   => 'nullable|in:Regular,Contract,Temporary,Intern',

            // Statutory Numbers (ALL REQUIRED)
            'national_id_number' => 'required|string|max:50',
            'passport_number'    => 'nullable|string|max:50',
            'nssf_number'        => 'required|string|max:50',
            'sha_number'         => 'required|string|max:50',
            'kra_pin'            => 'required|string|max:50',

            // Next of kin
            'next_of_kin_name'          => 'required|string|max:255',
            'next_of_kin_phone'         => 'required|string|max:20',
            'next_of_kin_relationship'  => 'required|string|max:255',
            'next_of_kin_email'         => 'nullable|email',
            'next_of_kin_address'       => 'nullable|string',

            // Documents (ALL REQUIRED)
            'national_id_photo'         => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'passport_photo'            => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'passport_size_photo'       => 'required|image|max:5120',
            'nssf_card_photo'           => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'sha_card_photo'            => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'kra_certificate_photo'     => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',

            // Terms
            'terms'                     => 'required|accepted',
        ]);

        $token = Str::uuid()->toString();

        // Create onboarding record
        $onboarding = NewEmployeeOnboarding::create([
            ...collect($validated)->except([
                'national_id_photo',
                'passport_photo',
                'passport_size_photo',
                'nssf_card_photo',
                'sha_card_photo',
                'kra_certificate_photo',
                'terms'
            ])->toArray(),
            'token'    => $token,
            'status'   => 'submitted', // Directly submit since all is required
            'location' => 'Mombasa',
        ]);

        // Handle document uploads
        $this->uploadDocument($request, $onboarding, 'national_id_photo');
        $this->uploadDocument($request, $onboarding, 'passport_photo');
        $this->uploadDocument($request, $onboarding, 'passport_size_photo', true);
        $this->uploadDocument($request, $onboarding, 'nssf_card_photo');
        $this->uploadDocument($request, $onboarding, 'sha_card_photo');
        $this->uploadDocument($request, $onboarding, 'kra_certificate_photo');

        // Redirect to confirmation page
        return redirect()
            ->route('employee.onboarding.confirmation', $token)
            ->with('success', 'Application submitted successfully. HR will review your documents.');
    }

    /**
     * Upload document helper
     */
    private function uploadDocument(Request $request, NewEmployeeOnboarding $onboarding, string $field, bool $imageOnly = false): void
    {
        if (!$request->hasFile($field)) {
            return;
        }

        $file = $request->file($field);

        if (!$file->isValid()) {
            return;
        }

        $filename = $field . '_' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs(
            "onboarding/{$onboarding->token}",
            $filename,
            'public'
        );

        $onboarding->update([$field => $path]);
    }

    /**
     * Confirmation page
     */
    public function showConfirmation(string $token)
    {
        $onboarding = NewEmployeeOnboarding::where('token', $token)->firstOrFail();

        return view('frontend.confirmation', compact('onboarding'));
    }
}
