<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Unit;
use App\Models\DocumentInvitation;
use App\Models\EmployeeDocument;
use App\Services\AdvantaSMSService;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeesImport;
use App\Exports\EmployeesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::with(['department', 'subDepartment', 'unit', 'documents', 'documentInvitation'])
            ->latest()
            ->paginate(20);

        $departments = Department::active()->get();
        $subDepartments = SubDepartment::active()->get();
        $units = Unit::active()->get();

        return view('reeds.admin.employees.index', compact('employees', 'departments', 'subDepartments', 'units'));
    }

    /**
     * Get QR code data for employee - UPDATED
     */
    public function getQrData(Employee $employee)
    {
        try {
            $qrData = $employee->generateQrCodeData();

            return response()->json([
                'success' => true,
                'qr_data' => $qrData,
                'message' => 'QR code uses minimal format for faster scanning'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate QR code data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function import()
    {
        $departments = Department::active()->get();
        $units = Unit::active()->get();

        return view('reeds.admin.employees.import', compact('departments', 'units'));
    }

    /**
     * Process employee import
     */
    public function processImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed: ' . $validator->errors()->first()
            ], 422);
        }

        try {
            $import = new EmployeesImport;
            Excel::import($import, $request->file('file'));

            $importedCount = $import->getImportedCount();
            $skippedCount = $import->getSkippedCount();

            $message = "Import completed successfully!";
            if ($importedCount > 0) {
                $message .= " {$importedCount} employees imported.";
            }
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} records skipped (duplicates or invalid data).";
            }

            return response()->json([
                'success' => $message,
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Employee import failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to import employees: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export employees
     */
    public function export()
    {
        return Excel::download(new EmployeesExport, 'employees_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Show QR codes page
     */
    public function qrCodes()
    {
        $employees = Employee::with(['department', 'subDepartment', 'unit'])
            ->whereNotNull('qr_code')
            ->latest()
            ->paginate(500);

        return view('reeds.admin.employees.qr-codes', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_code' => 'required|string|max:50|unique:employees',
            'department_id' => 'required|exists:departments,id',
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'unit_id' => 'nullable|exists:units,id',
            'payroll_no' => 'nullable|string|max:50|unique:employees',
            'employment_type' => 'required|string|max:50',
            'title' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100|unique:employees',
            'phone' => 'required|string|max:20',
            'icard_number' => 'nullable|string|max:50|unique:employees',
            'gender' => 'nullable|in:Male,Female,Other',
            'designation' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            DB::transaction(function () use ($request) {
                $employee = Employee::create($request->all());
                $employee->generateQrCode();
            });

            return response()->json([
                'success' => 'Employee created successfully! Minimal QR code generated.'
            ]);
        } catch (\Exception $e) {
            Log::error('Employee creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create employee: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'employee_code' => 'required|string|max:50|unique:employees,employee_code,' . $employee->id,
            'department_id' => 'required|exists:departments,id',
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'unit_id' => 'nullable|exists:units,id',
            'payroll_no' => 'nullable|string|max:50|unique:employees,payroll_no,' . $employee->id,
            'employment_type' => 'required|string|max:50',
            'title' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100|unique:employees,email,' . $employee->id,
            'phone' => 'required|string|max:20',
            'icard_number' => 'nullable|string|max:50|unique:employees,icard_number,' . $employee->id,
            'gender' => 'nullable|in:Male,Female,Other',
            'designation' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $employee->update($request->all());
            return response()->json(['success' => 'Employee updated successfully!']);
        } catch (\Exception $e) {
            Log::error('Employee update failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update employee: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        try {
            $employee->delete();
            return response()->json(['success' => 'Employee deleted successfully!']);
        } catch (\Exception $e) {
            Log::error('Employee deletion failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete employee: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate QR code for employee - UPDATED
     */
    public function generateQrCode(Employee $employee)
    {
        try {
            $qrCode = $employee->generateQrCode();

            return response()->json([
                'success' => 'QR code generated successfully!',
                'qr_code' => $qrCode,
                'message' => 'Minimal QR code created using employee code: ' . $employee->employee_code
            ]);
        } catch (\Exception $e) {
            Log::error('QR code generation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate QR code!'], 500);
        }
    }

    /**
     * Bulk regenerate QR codes - NEW METHOD
     */
    public function bulkRegenerateQrCodes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:employees,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $count = 0;
            $employees = Employee::whereIn('id', $request->ids)->get();

            foreach ($employees as $employee) {
                $employee->generateQrCode();
                $count++;
            }

            return response()->json([
                'success' => "{$count} QR codes regenerated successfully with minimal format!"
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk QR regeneration failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to regenerate QR codes!'], 500);
        }
    }

    /**
     * Toggle employee status
     */
    public function toggleStatus(Employee $employee)
    {
        try {
            $employee->update([
                'is_active' => !$employee->is_active
            ]);

            $status = $employee->is_active ? 'activated' : 'deactivated';
            return response()->json(['success' => "Employee {$status} successfully!"]);
        } catch (\Exception $e) {
            Log::error('Employee status toggle failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update employee status!'], 500);
        }
    }

    /**
     * Get employees by department (for filtering)
     */
    public function byDepartment(Department $department)
    {
        try {
            $employees = $department->employees()
                ->with('subDepartment')
                ->where('is_active', true)
                ->get();

            return response()->json($employees);
        } catch (\Exception $e) {
            Log::error('Fetch employees by department failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch employees!'], 500);
        }
    }

    /**
     * Get employees by sub-department (for filtering)
     */
    public function bySubDepartment(SubDepartment $subDepartment)
    {
        try {
            $employees = $subDepartment->employees()
                ->where('is_active', true)
                ->get();

            return response()->json($employees);
        } catch (\Exception $e) {
            Log::error('Fetch employees by sub-department failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch employees!'], 500);
        }
    }

    /**
     * Get employees by unit (NEW METHOD)
     */
    public function byUnit(Unit $unit)
    {
        try {
            $employees = $unit->employees()
                ->with(['department', 'subDepartment'])
                ->where('is_active', true)
                ->get();

            return response()->json($employees);
        } catch (\Exception $e) {
            Log::error('Fetch employees by unit failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch employees!'], 500);
        }
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create()
    {
        $departments = Department::active()->get();
        $subDepartments = SubDepartment::active()->get();
        $units = Unit::active()->get();

        return response()->json([
            'departments' => $departments,
            'sub_departments' => $subDepartments,
            'units' => $units
        ]);
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee)
    {
        $employee->load(['department', 'subDepartment', 'unit']);

        $departments = Department::active()->get();
        $subDepartments = SubDepartment::active()->get();
        $units = Unit::active()->get();

        return response()->json([
            'employee' => $employee,
            'departments' => $departments,
            'sub_departments' => $subDepartments,
            'units' => $units
        ]);
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee)
    {
        try {
            $employee->load(['department', 'subDepartment', 'unit', 'documents', 'documentInvitation']);

            return response()->json([
                'success' => true,
                'employee' => $employee
            ]);
        } catch (\Exception $e) {
            Log::error('Employee show failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch employee details!'], 500);
        }
    }

    /**
     * Search employees - UPDATED with email, phone, and unit search
     */
    public function search(Request $request)
    {
        try {
            $searchTerm = $request->get('search');

            $employees = Employee::with(['department', 'subDepartment', 'unit'])
                ->where(function($query) use ($searchTerm) {
                    $query->where('employee_code', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('payroll_no', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('icard_number', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('designation', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('phone', 'LIKE', "%{$searchTerm}%")
                          ->orWhereHas('department', function($q) use ($searchTerm) {
                              $q->where('name', 'LIKE', "%{$searchTerm}%");
                          })
                          ->orWhereHas('subDepartment', function($q) use ($searchTerm) {
                              $q->where('name', 'LIKE', "%{$searchTerm}%");
                          })
                          ->orWhereHas('unit', function($q) use ($searchTerm) {
                              $q->where('name', 'LIKE', "%{$searchTerm}%");
                          });
                })
                ->latest()
                ->paginate(20);

            return response()->json([
                'success' => true,
                'employees' => $employees
            ]);
        } catch (\Exception $e) {
            Log::error('Employee search failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to search employees!'], 500);
        }
    }

    /**
     * Bulk delete employees
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:employees,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $count = Employee::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => "{$count} employees deleted successfully!"
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk employee deletion failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete employees!'], 500);
        }
    }

    /**
     * Bulk status update
     */
    public function bulkStatusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:employees,id',
            'status' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $count = Employee::whereIn('id', $request->ids)
                ->update(['is_active' => $request->status]);

            $status = $request->status ? 'activated' : 'deactivated';

            return response()->json([
                'success' => "{$count} employees {$status} successfully!"
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk employee status update failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update employee status!'], 500);
        }
    }

    /**
     * Get employee statistics - UPDATED with unit stats
     */
    public function getStats()
    {
        try {
            $totalEmployees = Employee::count();
            $activeEmployees = Employee::where('is_active', true)->count();
            $inactiveEmployees = Employee::where('is_active', false)->count();
            $employeesWithQr = Employee::whereNotNull('qr_code')->count();

            // Document statistics
            $employeesWithDocuments = Employee::whereHas('documents', function($query) {
                $query->whereHasAllRequiredDocuments();
            })->count();

            $pendingInvitations = DocumentInvitation::whereIn('status', ['pending', 'sent', 'opened'])->count();
            $completedInvitations = DocumentInvitation::where('status', 'completed')->count();

            $departmentStats = Department::withCount(['employees as total_employees',
                'employees as active_employees' => function($query) {
                    $query->where('is_active', true);
                }])->get();

            $unitStats = Unit::withCount(['employees as total_employees',
                'employees as active_employees' => function($query) {
                    $query->where('is_active', true);
                }])->get();

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_employees' => $totalEmployees,
                    'active_employees' => $activeEmployees,
                    'inactive_employees' => $inactiveEmployees,
                    'employees_with_qr' => $employeesWithQr,
                    'employees_with_documents' => $employeesWithDocuments,
                    'pending_invitations' => $pendingInvitations,
                    'completed_invitations' => $completedInvitations,
                    'department_stats' => $departmentStats,
                    'unit_stats' => $unitStats
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Employee stats fetch failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch employee statistics!'], 500);
        }
    }

    /**
     * Bulk update employee phone numbers (NEW METHOD for phone import)
     */
    public function bulkUpdatePhones(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'data.*.employee_code' => 'required|exists:employees,employee_code',
            'data.*.phone' => 'nullable|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $updatedCount = 0;

            foreach ($request->data as $item) {
                $employee = Employee::where('employee_code', $item['employee_code'])->first();

                if ($employee && !empty($item['phone'])) {
                    $employee->phone = $item['phone'];
                    $employee->save();
                    $updatedCount++;
                }
            }

            return response()->json([
                'success' => "{$updatedCount} phone numbers updated successfully!"
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk phone update failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update phone numbers!'], 500);
        }
    }

    // =============================================
    // DOCUMENT INVITATION METHODS - FIXED VERSION
    // =============================================

    /**
     * Send document invitation to employee - FIXED VERSION
     */
    public function sendDocumentInvitation(Request $request, Employee $employee)
    {
        // Check if employee has phone number
        if (!$employee->phone) {
            return response()->json([
                'error' => 'Employee does not have a phone number. Please add phone number first.'
            ], 422);
        }

        // Check if employee already has complete documents
        if ($employee->documents && $employee->documents->hasAllRequiredDocuments()) {
            return response()->json([
                'error' => 'Employee already has all required documents.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check for existing active invitation
            $existingInvitation = DocumentInvitation::where('employee_id', $employee->id)
                ->whereIn('status', ['pending', 'sent', 'opened'])
                ->first();

            if ($existingInvitation && !$existingInvitation->isExpired()) {
                return response()->json([
                    'error' => 'Employee already has an active invitation. Please send a reminder instead.'
                ], 422);
            }

            // Create or update invitation
            $invitation = DocumentInvitation::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'token' => DocumentInvitation::generateToken(),
                    'status' => 'pending',
                    'expires_at' => now()->addDays(30),
                    'sent_by' => auth()->id(),
                ]
            );

            // Generate SMS message
            $smsService = new AdvantaSMSService();
            $message = "Hello {$employee->first_name}, please upload your documents using this link: " .
                      url("/d/{$invitation->token}") .
                      " - Reeds Africa";

            // Send SMS
            $response = $smsService->sendSMS($employee->phone, $message, $invitation->id);

            // DEBUG: Log the response
            Log::info('SMS Response:', $response);

            // Check response - FIXED LOGIC
            $smsSent = false;
            $smsMessageId = null;
            $smsStatus = 'failed';
            $smsError = 'Unknown error';

            if (isset($response['responses'][0])) {
                $smsResponse = $response['responses'][0];

                // Check different possible success indicators
                if (isset($smsResponse['respose-code']) && $smsResponse['respose-code'] == 200) {
                    $smsSent = true;
                    $smsStatus = 'sent';
                    $smsMessageId = $smsResponse['messageid'] ?? null;
                }
                // Also check for other success indicators
                elseif (isset($smsResponse['response-code']) && $smsResponse['response-code'] == 200) {
                    $smsSent = true;
                    $smsStatus = 'sent';
                    $smsMessageId = $smsResponse['messageid'] ?? $smsResponse['message-id'] ?? null;
                }
                // Check if message was accepted
                elseif (isset($smsResponse['response-description']) &&
                       (str_contains(strtolower($smsResponse['response-description']), 'accepted') ||
                        str_contains(strtolower($smsResponse['response-description']), 'success'))) {
                    $smsSent = true;
                    $smsStatus = 'sent';
                    $smsMessageId = $smsResponse['messageid'] ?? null;
                }

                if (isset($smsResponse['response-description'])) {
                    $smsError = $smsResponse['response-description'];
                }
            }

            if ($smsSent) {
                // Update invitation with SMS details
                $invitation->update([
                    'status' => 'sent',
                    'sms_sent' => true,
                    'sms_message_id' => $smsMessageId,
                    'sms_status' => $smsStatus,
                    'sent_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => 'Document invitation sent successfully to ' . $employee->formal_name,
                    'invitation' => $invitation,
                    'sms_response' => $response // For debugging
                ]);
            } else {
                // SMS failed
                $invitation->update([
                    'sms_sent' => false,
                    'sms_status' => 'failed',
                    'sms_error' => $smsError,
                ]);

                DB::commit();

                Log::error('SMS failed to send', [
                    'employee_id' => $employee->id,
                    'phone' => $employee->phone,
                    'response' => $response,
                    'error' => $smsError
                ]);

                return response()->json([
                    'error' => 'Failed to send SMS: ' . $smsError,
                    'debug' => $response // For debugging
                ], 500);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Document invitation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send invitation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Send bulk document invitations - FIXED VERSION
     */
    public function bulkSendDocumentInvitations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:employees,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $employees = Employee::whereIn('id', $request->ids)
            ->whereNotNull('phone')
            ->whereDoesntHave('documentInvitation', function($query) {
                $query->whereIn('status', ['sent', 'opened']);
            })
            ->get();

        if ($employees->isEmpty()) {
            return response()->json([
                'error' => 'No employees found with phone numbers or all already have pending invitations.'
            ], 422);
        }

        $successCount = 0;
        $failedCount = 0;
        $smsService = new AdvantaSMSService();
        $invitations = [];

        // Create invitations FIRST
        foreach ($employees as $employee) {
            // Skip if already has complete documents
            if ($employee->documents && $employee->documents->hasAllRequiredDocuments()) {
                continue;
            }

            // Create invitation
            $token = Str::random(32);
            $invitation = DocumentInvitation::create([
                'employee_id' => $employee->id,
                'token' => $token,
                'status' => 'pending',
                'expires_at' => now()->addDays(30),
                'sent_by' => auth()->id(),
            ]);

            $invitations[] = [
                'invitation_id' => $invitation->id,
                'token' => $token,
                'employee' => $employee,
                'message' => "Hello {$employee->first_name}, please upload your documents using this link: " .
                            url("/d/{$token}") .
                            " - Reeds Africa",
            ];
        }

        if (empty($invitations)) {
            return response()->json([
                'error' => 'No invitations to send. All selected employees already have documents or invitations.'
            ], 422);
        }

        try {
            // Prepare SMS messages
            $messages = [];
            foreach ($invitations as $inv) {
                $messages[] = [
                    'mobile' => $inv['employee']->phone,
                    'message' => $inv['message'],
                    'client_sms_id' => $inv['invitation_id'],
                ];
            }

            // Send bulk SMS (max 20 per request)
            $chunks = array_chunk($messages, 20);

            foreach ($chunks as $chunk) {
                $response = $smsService->sendBulkSMS($chunk);

                if (isset($response['responses'])) {
                    foreach ($response['responses'] as $smsResponse) {
                        if (isset($smsResponse['respose-code']) && $smsResponse['respose-code'] == 200) {
                            $invitationId = $smsResponse['clientsmsid'] ?? null;
                            if ($invitationId) {
                                DocumentInvitation::where('id', $invitationId)->update([
                                    'status' => 'sent',
                                    'sms_sent' => true,
                                    'sms_message_id' => $smsResponse['messageid'] ?? null,
                                    'sms_status' => 'sent',
                                    'sent_at' => now(),
                                ]);
                                $successCount++;
                            }
                        } else {
                            $failedCount++;
                            Log::error('Bulk SMS failed for invitation', [
                                'invitation_id' => $smsResponse['clientsmsid'] ?? null,
                                'error' => $smsResponse['response-description'] ?? 'Unknown error'
                            ]);
                        }
                    }
                }
            }

            Log::info('Bulk invitations sent', [
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'total' => count($invitations)
            ]);

            return response()->json([
                'success' => "{$successCount} invitations sent successfully. {$failedCount} failed.",
                'success_count' => $successCount,
                'failed_count' => $failedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk document invitation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send bulk invitations: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Send reminder for document upload - FIXED VERSION
     */
    public function sendDocumentReminder(Request $request, Employee $employee)
    {
        $invitation = $employee->documentInvitation;

        if (!$invitation || !in_array($invitation->status, ['sent', 'opened'])) {
            return response()->json([
                'error' => 'No active invitation found for this employee.'
            ], 422);
        }

        if ($invitation->reminder_count >= 3) {
            return response()->json([
                'error' => 'Maximum reminders (3) already sent.'
            ], 422);
        }

        try {
            $smsService = new AdvantaSMSService();
            $message = "REMINDER: Hello {$employee->first_name}, please upload your documents using this link: " .
                      url("/d/{$invitation->token}") .
                      " - Reeds Africa";

            $response = $smsService->sendSMS($employee->phone, $message, $invitation->id);

            if (isset($response['responses'][0]['respose-code']) && $response['responses'][0]['respose-code'] == 200) {
                $invitation->update([
                    'reminder_count' => $invitation->reminder_count + 1,
                    'last_reminder_sent_at' => now(),
                ]);

                return response()->json([
                    'success' => 'Reminder sent successfully to ' . $employee->formal_name
                ]);
            } else {
                return response()->json([
                    'error' => 'Failed to send reminder: ' . ($response['responses'][0]['response-description'] ?? 'Unknown error')
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Document reminder failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send reminder: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show document upload form (public) - FIXED VERSION
     */
    public function showDocumentUploadForm($token)
    {
        // First, try to find the invitation with any valid status
        $invitation = DocumentInvitation::where('token', $token)
            ->with('employee')
            ->first();

        if (!$invitation) {
            return view('documents.expired', [
                'message' => 'This invitation link has expired or is invalid.'
            ]);
        }

        // Check if expired
        if ($invitation->expires_at && $invitation->expires_at->isPast()) {
            $invitation->update(['status' => 'expired']);
            return view('documents.expired', [
                'message' => 'This invitation link has expired. Please request a new one.'
            ]);
        }

        // Check if already completed
        if ($invitation->status === 'completed') {
            return redirect()->route('documents.success', ['token' => $token]);
        }

        // Allow access if status is 'pending', 'sent', or 'opened'
        if (!in_array($invitation->status, ['pending', 'sent', 'opened'])) {
            return view('documents.expired', [
                'message' => 'This invitation is not active. Please request a new one.'
            ]);
        }

        // Mark as opened if status is 'pending' or 'sent'
        if (in_array($invitation->status, ['pending', 'sent'])) {
            $invitation->update(['status' => 'opened', 'opened_at' => now()]);
        }

        return view('documents.upload', [
            'invitation' => $invitation,
            'employee' => $invitation->employee
        ]);
    }

    /**
     * Process document upload (public) - FIXED VERSION
     */
    public function processDocumentUpload(Request $request, $token)
    {
        // Allow 'pending', 'sent', and 'opened' statuses
        $invitation = DocumentInvitation::where('token', $token)
            ->whereIn('status', ['pending', 'sent', 'opened'])
            ->where('expires_at', '>', now())
            ->with('employee')
            ->first();

        if (!$invitation) {
            return response()->json([
                'error' => 'This invitation link has expired or is invalid.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'next_of_kin_name' => 'required|string|max:255',
            'next_of_kin_relationship' => 'required|string|max:255',
            'next_of_kin_phone' => 'required|string|max:20',
            'next_of_kin_email' => 'nullable|email|max:255',
            'next_of_kin_address' => 'nullable|string',
            'national_id_photo' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'passport_photo' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'passport_size_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'nssf_card_photo' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'sha_card_photo' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'kra_certificate_photo' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $employee = $invitation->employee;
            $data = $request->except([
                'national_id_photo',
                'passport_photo',
                'passport_size_photo',
                'nssf_card_photo',
                'sha_card_photo',
                'kra_certificate_photo'
            ]);

            // Handle file uploads
            $fileFields = [
                'national_id_photo',
                'passport_photo',
                'passport_size_photo',
                'nssf_card_photo',
                'sha_card_photo',
                'kra_certificate_photo'
            ];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $path = $file->store("employee_documents/{$employee->employee_code}", 'public');
                    $data[$field] = $path;
                }
            }

            // Create or update documents
            if ($employee->documents) {
                $employee->documents->update($data);
            } else {
                $employee->documents()->create($data);
            }

            // Mark invitation as completed
            $invitation->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'redirect_url' => route('documents.success', ['token' => $token])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Document upload failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to upload documents. Please try again.'
            ], 500);
        }
    }

    /**
     * Show upload success page
     */
    public function showUploadSuccess($token)
    {
        $invitation = DocumentInvitation::where('token', $token)
            ->where('status', 'completed')
            ->with('employee')
            ->first();

        if (!$invitation) {
            return redirect()->route('documents.upload', ['token' => $token]);
        }

        return view('documents.success', [
            'invitation' => $invitation,
            'employee' => $invitation->employee
        ]);
    }

    /**
     * Redirect short link to full URL - FIXED VERSION
     */
    public function redirectShortLink($token)
    {
        // Check if invitation exists
        $invitation = DocumentInvitation::where('token', $token)->first();

        if (!$invitation) {
            return view('documents.expired', [
                'message' => 'Invalid invitation link. Please request a new one.'
            ]);
        }

        // If already completed, redirect to success page
        if ($invitation->status === 'completed') {
            return redirect()->route('documents.success', ['token' => $token]);
        }

        // If expired, show expired page
        if ($invitation->expires_at && $invitation->expires_at->isPast()) {
            return view('documents.expired', [
                'message' => 'This invitation link has expired. Please request a new one.'
            ]);
        }

        return redirect()->route('documents.upload', ['token' => $token]);
    }

    /**
     * Get document invitation status - NEW METHOD
     */
    public function getInvitationStatus($token)
    {
        $invitation = DocumentInvitation::where('token', $token)
            ->with('employee')
            ->first();

        if (!$invitation) {
            return response()->json([
                'error' => 'Invitation not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'invitation' => [
                'id' => $invitation->id,
                'token' => $invitation->token,
                'status' => $invitation->status,
                'employee_id' => $invitation->employee_id,
                'employee_name' => $invitation->employee->formal_name ?? 'N/A',
                'expires_at' => $invitation->expires_at,
                'created_at' => $invitation->created_at,
                'sms_status' => $invitation->sms_status,
                'sms_sent' => $invitation->sms_sent,
            ]
        ]);
    }

    /**
     * Resend invitation - NEW METHOD
     */
    public function resendInvitation(Request $request, $token)
    {
        $invitation = DocumentInvitation::where('token', $token)
            ->with('employee')
            ->first();

        if (!$invitation) {
            return response()->json([
                'error' => 'Invitation not found'
            ], 404);
        }

        $employee = $invitation->employee;

        // Check if employee has phone number
        if (!$employee->phone) {
            return response()->json([
                'error' => 'Employee does not have a phone number.'
            ], 422);
        }

        try {
            // Generate new token
            $newToken = Str::random(32);

            // Update invitation with new token
            $invitation->update([
                'token' => $newToken,
                'status' => 'pending',
                'expires_at' => now()->addDays(30),
                'sent_at' => null,
                'sms_sent' => false,
                'sms_status' => null,
            ]);

            // Send new SMS
            $smsService = new AdvantaSMSService();
            $message = "Hello {$employee->first_name}, please upload your documents using this link: " .
                      url("/d/{$newToken}") .
                      " - Reeds Africa";

            $response = $smsService->sendSMS($employee->phone, $message, $invitation->id);

            if (isset($response['responses'][0]['respose-code']) && $response['responses'][0]['respose-code'] == 200) {
                $invitation->update([
                    'status' => 'sent',
                    'sms_sent' => true,
                    'sms_message_id' => $response['responses'][0]['messageid'] ?? null,
                    'sms_status' => 'sent',
                    'sent_at' => now(),
                ]);

                return response()->json([
                    'success' => 'Invitation resent successfully!',
                    'new_token' => $newToken,
                    'new_url' => url("/d/{$newToken}")
                ]);
            } else {
                return response()->json([
                    'error' => 'Failed to send SMS: ' . ($response['responses'][0]['response-description'] ?? 'Unknown error')
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Resend invitation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to resend invitation: ' . $e->getMessage()], 500);
        }
    }
}
