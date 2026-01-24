<?php

namespace App\Http\Controllers;

use App\Models\NewEmployeeOnboarding;
use App\Models\Department;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

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
     * ADMIN: View all applications
     */
    // In EmployeeOnboardingController.php - update adminIndex method

public function adminIndex(Request $request)
{
    $query = NewEmployeeOnboarding::with(['department', 'unit'])
        ->latest();

    // Apply filters
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('personal_email', 'like', "%{$search}%")
              ->orWhere('token', 'like', "%{$search}%");
        });
    }

    if ($request->filled('department_id')) {
        $query->where('department_id', $request->department_id);
    }

    $applications = $query->paginate(20);
    $departments = Department::where('is_active', true)->get();

    // Get department-wise statistics
    $departmentStats = NewEmployeeOnboarding::select(
            'departments.id',
            'departments.name',
            \DB::raw('COUNT(new_employee_onboarding.id) as total_applications'),
            \DB::raw('SUM(CASE WHEN new_employee_onboarding.status = "approved" THEN 1 ELSE 0 END) as approved_count'),
            \DB::raw('SUM(CASE WHEN new_employee_onboarding.status = "submitted" THEN 1 ELSE 0 END) as pending_count')
        )
        ->join('departments', 'new_employee_onboarding.department_id', '=', 'departments.id')
        ->groupBy('departments.id', 'departments.name')
        ->orderBy('total_applications', 'desc')
        ->get();

    $statuses = [
        'submitted' => 'Submitted',
        'reviewed' => 'Reviewed',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'on_hold' => 'On Hold'
    ];

    $stats = [
        'total' => NewEmployeeOnboarding::count(),
        'submitted' => NewEmployeeOnboarding::where('status', 'submitted')->count(),
        'approved' => NewEmployeeOnboarding::where('status', 'approved')->count(),
        'rejected' => NewEmployeeOnboarding::where('status', 'rejected')->count(),
    ];

    return view('reeds.admin.employeeonboarding.index', compact(
        'applications',
        'departments',
        'statuses',
        'stats',
        'departmentStats'
    ));
}

// Add this new method for department details
public function getDepartmentApplications($department_id)
{
    $applications = NewEmployeeOnboarding::with(['department', 'unit'])
        ->where('department_id', $department_id)
        ->latest()
        ->get();

    return response()->json([
        'applications' => $applications,
        'department' => Department::find($department_id)
    ]);
}

// Add this method for export
public function exportDepartmentApplications($department_id)
{
    $department = Department::findOrFail($department_id);
    $applications = NewEmployeeOnboarding::with(['department', 'unit'])
        ->where('department_id', $department_id)
        ->get();

    $fileName = 'applications_' . strtolower(str_replace(' ', '_', $department->name)) . '_' . date('Y-m-d') . '.csv';

    $headers = [
        "Content-type" => "text/csv",
        "Content-Disposition" => "attachment; filename=" . $fileName,
        "Pragma" => "no-cache",
        "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        "Expires" => "0"
    ];

    $columns = [
        'Application ID', 'Token', 'Full Name', 'Email', 'Phone',
        'Designation', 'Department', 'Unit', 'Status',
        'Date of Joining', 'Employment Type', 'Date Submitted'
    ];

    $callback = function() use ($applications, $columns) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columns);

        foreach ($applications as $app) {
            $row = [
                $app->id,
                $app->token,
                $app->first_name . ' ' . $app->last_name,
                $app->personal_email,
                $app->personal_phone,
                $app->designation,
                $app->department ? $app->department->name : 'N/A',
                $app->unit ? $app->unit->name : 'N/A',
                ucfirst($app->status),
                $app->date_of_joining,
                $app->employment_type,
                $app->created_at->format('Y-m-d H:i:s')
            ];

            fputcsv($file, $row);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    /**
     * ADMIN: Show single application details
     */
    public function adminShow($id)
    {
        $application = NewEmployeeOnboarding::with(['department', 'unit'])
            ->findOrFail($id);

        return response()->json($application);
    }

    /**
     * ADMIN: Update application status
     */
    public function adminUpdateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:submitted,reviewed,approved,rejected,on_hold',
            'notes' => 'nullable|string|max:1000'
        ]);

        $application = NewEmployeeOnboarding::findOrFail($id);

        $oldStatus = $application->status;
        $application->update([
            'status' => $request->status,
            'admin_notes' => $request->notes ?: $application->admin_notes
        ]);

        // Send status update email to applicant if status changed
        if ($oldStatus !== $request->status && $application->personal_email) {
            $this->sendStatusUpdateEmail($application, $oldStatus, $request->status);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'application' => $application
        ]);
    }

    /**
     * ADMIN: Download document
     */
    public function adminDownloadDocument($id, $documentType)
    {
        $application = NewEmployeeOnboarding::findOrFail($id);

        $validDocuments = [
            'national_id_photo',
            'passport_photo',
            'passport_size_photo',
            'nssf_card_photo',
            'sha_card_photo',
            'kra_certificate_photo'
        ];

        if (!in_array($documentType, $validDocuments) || empty($application->$documentType)) {
            abort(404, 'Document not found');
        }

        $path = $application->$documentType;

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download($path);
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
        $systemName = "Reeds Africa Talent Gateway";

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
            'status'   => 'submitted',
            'location' => 'Mombasa',
        ]);

        // Handle document uploads
        $this->uploadDocument($request, $onboarding, 'national_id_photo');
        $this->uploadDocument($request, $onboarding, 'passport_photo');
        $this->uploadDocument($request, $onboarding, 'passport_size_photo', true);
        $this->uploadDocument($request, $onboarding, 'nssf_card_photo');
        $this->uploadDocument($request, $onboarding, 'sha_card_photo');
        $this->uploadDocument($request, $onboarding, 'kra_certificate_photo');

        // Send emails
        $this->sendAdminEmail($onboarding, $systemName);
        $this->sendApplicantEmail($onboarding, $systemName);

        return redirect()
            ->route('employee.onboarding.confirmation', $token)
            ->with('success', 'Application submitted successfully. HR will review your documents.')
            ->with('system_name', $systemName);
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
        $systemName = "Reeds Africa Talent Gateway";

        return view('frontend.confirmation', compact('onboarding', 'systemName'));
    }

    /**
     * Send email to admin
     */
    private function sendAdminEmail($onboarding, $systemName)
    {
        $adminEmail = 'monicawareham@gmail.com';
        $adminSubject = "New Employee Onboarding Application - $systemName";

        $adminBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; }
                .details { margin: 20px 0; }
                .detail-item { margin-bottom: 10px; }
                .label { font-weight: bold; color: #333; }
                .value { color: #666; }
                .button { background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>New Employee Onboarding Application Received</h2>
                <p><strong>System:</strong> $systemName</p>
            </div>

            <div class='details'>
                <h3>Applicant Details:</h3>
                <div class='detail-item'><span class='label'>Full Name:</span> <span class='value'>{$onboarding->first_name} {$onboarding->middle_name} {$onboarding->last_name}</span></div>
                <div class='detail-item'><span class='label'>Email:</span> <span class='value'>{$onboarding->personal_email}</span></div>
                <div class='detail-item'><span class='label'>Phone:</span> <span class='value'>{$onboarding->personal_phone}</span></div>
                <div class='detail-item'><span class='label'>Designation:</span> <span class='value'>{$onboarding->designation}</span></div>
                <div class='detail-item'><span class='label'>Date of Joining:</span> <span class='value'>{$onboarding->date_of_joining}</span></div>
                <div class='detail-item'><span class='label'>Department:</span> <span class='value'>" . optional($onboarding->department)->name . "</span></div>
                <div class='detail-item'><span class='label'>Application Token:</span> <span class='value'>{$onboarding->token}</span></div>
                <div class='detail-item'><span class='label'>Submission Time:</span> <span class='value'>" . now()->format('F j, Y g:i A') . "</span></div>
            </div>

            <div>
                <p>Please review this application in the admin panel.</p>
                <a href='" . url('/admin/onboarding') . "' class='button'>View Admin Panel</a>
            </div>

            <hr>
            <p><em>This is an automated notification from $systemName</em></p>
        </body>
        </html>
        ";

        Mail::raw('', function ($mailer) use ($adminEmail, $adminSubject, $adminBody) {
            $mailer->to($adminEmail)
                   ->subject($adminSubject)
                   ->html($adminBody);
        });
    }

    /**
     * Send email to applicant
     */
    private function sendApplicantEmail($onboarding, $systemName)
    {
        if (!$onboarding->personal_email) return;

        $applicantSubject = "Application Received - $systemName";

        $applicantBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; }
                .details { margin: 20px 0; }
                .detail-item { margin-bottom: 10px; }
                .label { font-weight: bold; color: #333; }
                .value { color: #666; }
                .status { background-color: #28a745; color: white; padding: 5px 10px; border-radius: 3px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>Application Received Successfully</h2>
                <p><strong>System:</strong> $systemName</p>
            </div>

            <p>Hello <strong>{$onboarding->first_name} {$onboarding->last_name}</strong>,</p>

            <p>We have successfully received your onboarding application through the $systemName. Our HR team will review your submission and contact you once processed.</p>

            <div class='details'>
                <h3>Application Summary:</h3>
                <div class='detail-item'><span class='label'>Application Reference:</span> <span class='value'>{$onboarding->token}</span></div>
                <div class='detail-item'><span class='label'>Current Status:</span> <span class='status'>" . ucfirst($onboarding->status) . "</span></div>
                <div class='detail-item'><span class='label'>Submission Date:</span> <span class='value'>" . now()->format('F j, Y') . "</span></div>
                <div class='detail-item'><span class='label'>Position Applied:</span> <span class='value'>{$onboarding->designation}</span></div>
            </div>

            <div>
                <h3>Next Steps:</h3>
                <ol>
                    <li>HR will review your documents and information</li>
                    <li>You will be contacted if additional information is required</li>
                    <li>Once approved, you'll receive further instructions</li>
                </ol>
            </div>

            <p>You can use your application reference (<strong>{$onboarding->token}</strong>) for any future inquiries.</p>

            <hr>
            <p><em>This is an automated confirmation from $systemName. Please do not reply to this email.</em></p>
            <p>If you have any questions, please contact the HR department directly.</p>
        </body>
        </html>
        ";

        Mail::raw('', function ($mailer) use ($onboarding, $applicantSubject, $applicantBody) {
            $mailer->to($onboarding->personal_email)
                   ->subject($applicantSubject)
                   ->html($applicantBody);
        });
    }

    /**
     * Send status update email to applicant
     */
    private function sendStatusUpdateEmail($application, $oldStatus, $newStatus)
    {
        $subject = "Application Status Updated - Reeds Africa Talent Gateway";

        $emailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .status-badge {
                    padding: 8px 16px;
                    border-radius: 20px;
                    color: white;
                    display: inline-block;
                    font-weight: bold;
                }
                .status-new { background-color: #28a745; }
                .status-old { background-color: #6c757d; }
            </style>
        </head>
        <body>
            <h2>Application Status Update</h2>

            <p>Hello <strong>{$application->first_name} {$application->last_name}</strong>,</p>

            <p>The status of your onboarding application has been updated:</p>

            <div style='margin: 20px 0;'>
                <span class='status-badge status-old'>" . ucfirst($oldStatus) . "</span>
                <span style='margin: 0 10px;'>→</span>
                <span class='status-badge status-new'>" . ucfirst($newStatus) . "</span>
            </div>

            <p><strong>Application Reference:</strong> {$application->token}</p>
            <p><strong>Position:</strong> {$application->designation}</p>

            <hr>
            <p><em>This is an automated notification from Reeds Africa Talent Gateway</em></p>
        </body>
        </html>
        ";

        Mail::raw('', function ($mailer) use ($application, $subject, $emailBody) {
            $mailer->to($application->personal_email)
                   ->subject($subject)
                   ->html($emailBody);
        });
    }
}
