<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Unit;
use App\Models\DocumentInvitation;
use Carbon\Carbon;
use App\Models\EmployeeDocument;
use App\Services\AdvantaSMSService;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeesImport;
use App\Exports\EmployeesExport;
use App\Models\MealTransaction;
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
/**
 * Get QR code data for employee - FIXED VERSION with better error handling
 */
/**
 * Get QR code data for employee - SIMPLIFIED VERSION
 */
public function getQrData($id) // Changed from Employee $employee to $id
{
    try {
        Log::info('QR data requested for employee ID: ' . $id);

        // Manually find the employee
        $employee = Employee::find($id);

        if (!$employee) {
            Log::error('Employee not found with ID: ' . $id);
            return response()->json([
                'success' => false,
                'error' => 'Employee not found'
            ], 404);
        }

        // Get department name safely
        $departmentName = 'N/A';
        if ($employee->department) {
            $departmentName = $employee->department->name;
        }

        // Generate simple QR data without any relationships that might fail
        $qrData = [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'formal_name' => $employee->formal_name,
            'department' => $departmentName,
            'designation' => $employee->designation ?? 'N/A',
            'qr_data' => $employee->employee_code, // Use employee code as QR data
            'display_text' => $employee->employee_code . ' - ' . $employee->formal_name
        ];

        Log::info('QR data generated successfully for employee: ' . $employee->id);

        return response()->json([
            'success' => true,
            'qr_data' => $qrData
        ]);

    } catch (\Exception $e) {
        Log::error('Error in getQrData: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());

        return response()->json([
            'success' => false,
            'error' => 'Server error: ' . $e->getMessage()
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
   /**
 * Export employees as CSV
 */
public function export()
{
    $employees = Employee::with(['department', 'subDepartment', 'unit'])
        ->latest()
        ->get();

    $filename = 'employees_' . date('Y-m-d') . '.csv';

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    $callback = function() use ($employees) {
        $file = fopen('php://output', 'w');

        // Add BOM for UTF-8 to handle special characters
        fwrite($file, "\xEF\xBB\xBF");

        // Add headers
        fputcsv($file, [
            'ID',
            'Employee Code',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Department',
            'Sub Department',
            'Unit',
            'Designation',
            'Status',
            'Created At'
        ]);

        // Add data rows
        foreach ($employees as $employee) {
            fputcsv($file, [
                $employee->id,
                $employee->employee_code,
                $employee->first_name,
                $employee->last_name,
                $employee->email,
                $employee->phone,
                $employee->department?->name,
                $employee->subDepartment?->name,
                $employee->unit?->name,
                $employee->designation,
                $employee->is_active ? 'Active' : 'Inactive',
                $employee->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    /**
     * Show QR codes page
     */
    /**
 * Show QR codes page with search
 */
/**
 * Show QR codes page with search
 */
public function qrCodes(Request $request)
{
    $query = Employee::with(['department', 'subDepartment', 'unit'])
        ->whereNotNull('qr_code');

    // Apply search if provided
    if ($request->has('search') && !empty($request->search)) {
        $searchTerm = $request->search;
        $query->where(function($q) use ($searchTerm) {
            $q->where('employee_code', 'LIKE', "%{$searchTerm}%")
              ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('middle_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('payroll_no', 'LIKE', "%{$searchTerm}%")
              ->orWhere('designation', 'LIKE', "%{$searchTerm}%")
              ->orWhere('category', 'LIKE', "%{$searchTerm}%")
              ->orWhere('email', 'LIKE', "%{$searchTerm}%")
              ->orWhere('phone', 'LIKE', "%{$searchTerm}%")
              ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$searchTerm}%")

              // Search in related department
              ->orWhereHas('department', function($deptQuery) use ($searchTerm) {
                  $deptQuery->where('name', 'LIKE', "%{$searchTerm}%");
              })

              // Search in related sub-department
              ->orWhereHas('subDepartment', function($subDeptQuery) use ($searchTerm) {
                  $subDeptQuery->where('name', 'LIKE', "%{$searchTerm}%");
              })

              // Search in related unit
              ->orWhereHas('unit', function($unitQuery) use ($searchTerm) {
                  $unitQuery->where('name', 'LIKE', "%{$searchTerm}%");
              });
        });
    }

    // Apply department filter if provided
    if ($request->has('department_id') && !empty($request->department_id)) {
        $query->where('department_id', $request->department_id);
    }

    // Apply unit filter if provided
    if ($request->has('unit_id') && !empty($request->unit_id)) {
        $query->where('unit_id', $request->unit_id);
    }

    $employees = $query->latest()->paginate(2000)->withQueryString();

    // Get departments and units for filters
    $departments = Department::active()->get();
    $units = Unit::active()->get();

    // If it's an AJAX request, return JSON with HTML
    if ($request->ajax()) {
        $html = '';
        foreach ($employees as $employee) {
            $html .= '<div class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">';
            $html .= '<div class="p-4 border-b border-gray-100">';
            $html .= '<div class="bg-gray-50 rounded-lg p-3 flex items-center justify-center h-40">';
            $html .= '<div id="qrcode-' . $employee->id . '" class="qrcode-container flex items-center justify-center"></div>';
            $html .= '</div></div>';
            $html .= '<div class="p-4">';
            $html .= '<h4 class="font-semibold text-gray-900 text-sm mb-1 truncate">' . $employee->formal_name . '</h4>';
            $html .= '<p class="text-xs text-gray-500 mb-2">' . $employee->employee_code . '</p>';
            $html .= '<p class="text-xs text-gray-600 mb-3 truncate">' . ($employee->department->name ?? 'N/A') . '</p>';
            $html .= '<div class="flex items-center justify-between mb-3">';
            $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . ($employee->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . '">';
            $html .= $employee->is_active ? 'Active' : 'Inactive';
            $html .= '</span></div>';
            $html .= '<button onclick="downloadMealCard(' . $employee->id . ')" class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">';
            $html .= '<i class="fas fa-file-pdf mr-2 text-xs"></i>Download PDF</button>';
            $html .= '</div></div>';
        }

        if ($employees->isEmpty()) {
            $html = '<div class="col-span-full text-center py-12"><div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4"><i class="fas fa-qrcode text-3xl text-gray-400"></i></div><h3 class="text-lg font-medium text-gray-900 mb-2">No QR codes found</h3><p class="text-gray-500 mb-6">Try adjusting your search or filters.</p></div>';
        }

        $pagination = $employees->links()->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'pagination' => $pagination,
            'total' => $employees->total()
        ]);
    }

    return view('reeds.admin.employees.qr-codes', compact('employees', 'departments', 'units'));
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
  /**
 * Search employees - FIXED VERSION
 */
public function search(Request $request)
{
    try {
        $searchTerm = $request->get('search', '');
        $departmentId = $request->get('department_id', '');
        $unitId = $request->get('unit_id', '');
        $documentStatus = $request->get('document_status', '');

        Log::info('Search request received:', [
            'search' => $searchTerm,
            'department_id' => $departmentId,
            'unit_id' => $unitId,
            'document_status' => $documentStatus
        ]);

        $query = Employee::with(['department', 'subDepartment', 'unit', 'documents', 'documentInvitation']);

        // Apply search if provided
        if (!empty($searchTerm)) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('employee_code', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('middle_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('payroll_no', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('icard_number', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('designation', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('category', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('phone', 'LIKE', "%{$searchTerm}%")
                  ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$searchTerm}%")

                  // Search in related department
                  ->orWhereHas('department', function($deptQuery) use ($searchTerm) {
                      $deptQuery->where('name', 'LIKE', "%{$searchTerm}%");
                  })

                  // Search in related sub-department
                  ->orWhereHas('subDepartment', function($subDeptQuery) use ($searchTerm) {
                      $subDeptQuery->where('name', 'LIKE', "%{$searchTerm}%");
                  })

                  // Search in related unit
                  ->orWhereHas('unit', function($unitQuery) use ($searchTerm) {
                      $unitQuery->where('name', 'LIKE', "%{$searchTerm}%");
                  })

                  // Search in documents (next of kin info)
                  ->orWhereHas('documents', function($docQuery) use ($searchTerm) {
                      $docQuery->where('next_of_kin_name', 'LIKE', "%{$searchTerm}%")
                               ->orWhere('next_of_kin_phone', 'LIKE', "%{$searchTerm}%")
                               ->orWhere('next_of_kin_email', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        // Apply department filter
        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        // Apply unit filter
        if (!empty($unitId)) {
            $query->where('unit_id', $unitId);
        }

        // Apply document status filter
        if (!empty($documentStatus)) {
            switch ($documentStatus) {
                case 'complete':
                    $query->whereHas('documents', function($q) {
                        $q->whereNotNull('national_id_photo')
                          ->whereNotNull('passport_size_photo')
                          ->whereNotNull('nssf_card_photo')
                          ->whereNotNull('sha_card_photo')
                          ->whereNotNull('kra_certificate_photo');
                    });
                    break;
                case 'incomplete':
                    $query->whereHas('documents', function($q) {
                        $q->where(function($subQ) {
                            $subQ->whereNull('national_id_photo')
                                 ->orWhereNull('passport_size_photo')
                                 ->orWhereNull('nssf_card_photo')
                                 ->orWhereNull('sha_card_photo')
                                 ->orWhereNull('kra_certificate_photo');
                        });
                    });
                    break;
                case 'invited':
                    $query->whereHas('documentInvitation', function($q) {
                        $q->whereIn('status', ['sent', 'opened']);
                    });
                    break;
                case 'pending':
                    $query->whereDoesntHave('documents')
                          ->whereDoesntHave('documentInvitation', function($q) {
                              $q->whereIn('status', ['sent', 'opened', 'completed']);
                          });
                    break;
            }
        }

        // Get paginated results
        $employees = $query->latest()->paginate(20);

        // Get total count for the current search
        $totalCount = $employees->total();

        Log::info('Search results count: ' . $totalCount);

        // Render the HTML for table rows
        $html = '';
        foreach ($employees as $employee) {
            $hasDocuments = $employee->documents &&
                $employee->documents->national_id_photo &&
                $employee->documents->passport_size_photo &&
                $employee->documents->nssf_card_photo &&
                $employee->documents->sha_card_photo &&
                $employee->documents->kra_certificate_photo;

            $invitation = $employee->documentInvitation;
            $documentStatus = 'pending';
            $statusColor = 'red';
            $statusIcon = 'times-circle';
            $statusText = 'Missing';

            if ($hasDocuments) {
                $documentStatus = 'complete';
                $statusColor = 'green';
                $statusIcon = 'check-circle';
                $statusText = 'Complete';
            } elseif ($invitation) {
                if ($invitation->status === 'completed') {
                    $documentStatus = 'complete';
                    $statusColor = 'green';
                    $statusIcon = 'check-circle';
                    $statusText = 'Complete';
                } elseif ($invitation->status === 'opened') {
                    $documentStatus = 'invited';
                    $statusColor = 'yellow';
                    $statusIcon = 'eye';
                    $statusText = 'Link Opened';
                } elseif ($invitation->status === 'sent') {
                    $documentStatus = 'invited';
                    $statusColor = 'blue';
                    $statusIcon = 'paper-plane';
                    $statusText = 'Invitation Sent';
                }
            }

            $html .= '<tr class="hover:bg-gray-50 transition duration-150"
                         data-dept-id="' . $employee->department_id . '"
                         data-unit-id="' . ($employee->unit_id ?? '') . '"
                         data-doc-status="' . $documentStatus . '">';
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $html .= '<input type="checkbox" value="' . $employee->id . '" class="employee-checkbox rounded border-gray-300 text-secondary-blue focus:ring-secondary-blue" data-employee-id="' . $employee->id . '" ' . (!$employee->phone ? 'disabled' : '') . '>';
            $html .= '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $html .= '<div>';
            $html .= '<div class="text-sm font-medium text-text-black">' . $employee->formal_name . '</div>';
            $html .= '<div class="text-sm text-gray-500">' . $employee->employee_code . '</div>';
            if ($employee->payroll_no) {
                $html .= '<div class="text-xs text-gray-400">Payroll: ' . $employee->payroll_no . '</div>';
            }
            $html .= '</div>';
            $html .= '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            if ($employee->email) {
                $html .= '<div class="text-sm text-gray-900 flex items-center space-x-1"><i class="fas fa-envelope text-gray-400 text-xs"></i><span>' . $employee->email . '</span></div>';
            }
            if ($employee->phone) {
                $html .= '<div class="text-sm text-gray-500 flex items-center space-x-1"><i class="fas fa-phone text-gray-400 text-xs"></i><span>' . $employee->phone . '</span></div>';
            } else {
                $html .= '<div class="text-sm text-red-500 flex items-center space-x-1"><i class="fas fa-exclamation-circle text-xs"></i><span>No phone number</span></div>';
            }
            $html .= '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $html .= '<div class="text-sm text-gray-900">' . ($employee->department->name ?? 'N/A') . '</div>';
            if ($employee->subDepartment) {
                $html .= '<div class="text-xs text-gray-500">' . $employee->subDepartment->name . '</div>';
            }
            if ($employee->unit) {
                $html .= '<div class="text-xs text-blue-600 font-medium mt-1"><i class="fas fa-building mr-1"></i>' . $employee->unit->name . '</div>';
            }
            $html .= '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $html .= '<div class="text-sm text-gray-900">' . ($employee->designation ?? 'N/A') . '</div>';
            if ($employee->category) {
                $html .= '<div class="text-xs text-gray-500">' . $employee->category . '</div>';
            }
            $html .= '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-' . $statusColor . '-100 text-' . $statusColor . '-800">';
            $html .= '<i class="fas fa-' . $statusIcon . ' mr-1"></i> ' . $statusText;
            if ($invitation && $invitation->reminder_count > 0 && $invitation->status === 'sent') {
                $html .= '<span class="ml-1 text-xs">(' . $invitation->reminder_count . 'R)</span>';
            }
            $html .= '</span>';
            $html .= '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            if ($employee->qr_code) {
                $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-check mr-1"></i> Generated</span>';
            } else {
                $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"><i class="fas fa-times mr-1"></i> Pending</span>';
            }
            $html .= '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . ($employee->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . '">';
            $html .= $employee->is_active ? 'Active' : 'Inactive';
            $html .= '</span>';
            $html .= '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">';
            $html .= '<div class="flex items-center space-x-2">';
            $html .= '<button onclick="openEditModal(' . $employee->id . ')" class="text-secondary-blue hover:text-[#1e7a9e] transition duration-150" title="Edit"><i class="fas fa-edit"></i></button>';
            $html .= '<button onclick="viewEmployee(' . $employee->id . ')" class="text-gray-600 hover:text-gray-900 transition duration-150" title="View Details"><i class="fas fa-eye"></i></button>';
            if (!$employee->qr_code) {
                $html .= '<button onclick="generateQrCode(' . $employee->id . ')" class="text-green-600 hover:text-green-800 transition duration-150" title="Generate QR Code"><i class="fas fa-qrcode"></i></button>';
            }
            if (!$hasDocuments && $employee->phone) {
                $html .= '<button onclick="sendDocumentInvitation(' . $employee->id . ')" class="text-purple-600 hover:text-purple-800 transition duration-150" title="Send Document Invitation"><i class="fas fa-file-upload"></i></button>';
            }
            if ($invitation && $invitation->status === 'sent' && $invitation->reminder_count < 3 && $employee->phone) {
                $html .= '<button onclick="sendDocumentReminder(' . $employee->id . ')" class="text-orange-600 hover:text-orange-800 transition duration-150" title="Send Reminder (' . $invitation->reminder_count . '/3)"><i class="fas fa-bell"></i></button>';
            }
            $html .= '<button onclick="toggleStatus(' . $employee->id . ')" class="text-' . ($employee->is_active ? 'yellow' : 'green') . '-600 hover:text-' . ($employee->is_active ? 'yellow' : 'green') . '-800 transition duration-150" title="' . ($employee->is_active ? 'Deactivate' : 'Activate') . '"><i class="fas fa-' . ($employee->is_active ? 'pause' : 'play') . '"></i></button>';
            $html .= '<button onclick="confirmDelete(' . $employee->id . ', \'' . addslashes($employee->formal_name) . '\')" class="text-primary-red hover:text-[#c22120] transition duration-150" title="Delete"><i class="fas fa-trash"></i></button>';
            $html .= '</div>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        if ($employees->isEmpty()) {
            $html = '<tr><td colspan="9" class="px-6 py-8 text-center text-gray-500"><i class="fas fa-users text-4xl mb-3 text-gray-300"></i><p class="text-lg">No employees found</p><p class="text-sm mt-1">Try adjusting your search or filters</p></td></tr>';
        }

        // Render pagination HTML
        $pagination = $employees->appends($request->all())->links()->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'pagination' => $pagination,
            'total_count' => $totalCount
        ]);

    } catch (\Exception $e) {
        Log::error('Employee search failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        return response()->json([
            'success' => false,
            'error' => 'Failed to search employees: ' . $e->getMessage()
        ], 500);
    }
}
/**
 * Download employee document
 */
public function downloadDocument(Employee $employee, $documentType)
{
    $allowedDocuments = [
        'national_id_photo',
        'passport_photo',
        'passport_size_photo',
        'nssf_card_photo',
        'sha_card_photo',
        'kra_certificate_photo'
    ];

    if (!in_array($documentType, $allowedDocuments)) {
        abort(404);
    }

    $document = $employee->documents;

    if (!$document || !$document->$documentType) {
        abort(404);
    }

    $path = $document->$documentType;
    $filename = $this->getDocumentFilename($documentType, $employee->employee_code);

    return Storage::disk('public')->download($path, $filename);
}

/**
 * View employee document
 */
public function viewDocument(Employee $employee, $documentType)
{
    $allowedDocuments = [
        'national_id_photo',
        'passport_photo',
        'passport_size_photo',
        'nssf_card_photo',
        'sha_card_photo',
        'kra_certificate_photo'
    ];

    if (!in_array($documentType, $allowedDocuments)) {
        abort(404);
    }

    $document = $employee->documents;

    if (!$document || !$document->$documentType) {
        abort(404);
    }

    $path = $document->$documentType;
    $fullPath = Storage::disk('public')->path($path);
    $mimeType = mime_content_type($fullPath);
    $filename = $this->getDocumentFilename($documentType, $employee->employee_code);

    // Check if it's an image
    if (str_starts_with($mimeType, 'image/')) {
        $fileContent = Storage::disk('public')->get($path);
        $base64 = base64_encode($fileContent);

        return view('reeds.admin.employees.document-view', [
            'employee' => $employee,
            'documentType' => $documentType,
            'documentTitle' => $this->getDocumentTitle($documentType),
            'mimeType' => $mimeType,
            'base64' => $base64,
            'filename' => $filename,
            'isImage' => true
        ]);
    } else {
        // For PDFs and other files, show download option
        return view('reeds.admin.employees.document-view', [
            'employee' => $employee,
            'documentType' => $documentType,
            'documentTitle' => $this->getDocumentTitle($documentType),
            'mimeType' => $mimeType,
            'fileUrl' => Storage::disk('public')->url($path),
            'filename' => $filename,
            'isImage' => false
        ]);
    }
}

/**
 * Get document filename
 */
private function getDocumentFilename($documentType, $employeeCode)
{
    $names = [
        'national_id_photo' => 'National-ID',
        'passport_photo' => 'Passport-Photo',
        'passport_size_photo' => 'Passport-Size-Photo',
        'nssf_card_photo' => 'NSSF-Card',
        'sha_card_photo' => 'SHA-Card',
        'kra_certificate_photo' => 'KRA-Certificate'
    ];

    $name = $names[$documentType] ?? $documentType;
    return "{$employeeCode}-{$name}.jpg";
}

/**
 * Get document title
 */
private function getDocumentTitle($documentType)
{
    $titles = [
        'national_id_photo' => 'National ID',
        'passport_photo' => 'Passport Photo',
        'passport_size_photo' => 'Passport Size Photo',
        'nssf_card_photo' => 'NSSF Card',
        'sha_card_photo' => 'SHA Card',
        'kra_certificate_photo' => 'KRA Certificate'
    ];

    return $titles[$documentType] ?? $documentType;
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
 * Show scan data export page with date range picker
 */
/**
 * Show scan data export page with date range picker
 */
public function exportScanData(Request $request)
{
    $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
    $endDate = $request->get('end_date', now()->format('Y-m-d'));

    // Get all active units for the filter dropdown
    $units = Unit::where('is_active', true)->orderBy('name')->get();

    return view('reeds.admin.employees.scan-data-export', compact('startDate', 'endDate', 'units'));
}

/**
 * Generate and export scan data report - WITH UNIT FILTER
 */
public function generateScanDataReport(Request $request)
{
    $request->validate([
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'format' => 'nullable|in:excel,pdf',
        'unit_id' => 'nullable|exists:units,id'
    ]);

    $startDate = Carbon::parse($request->start_date)->startOfDay();
    $endDate = Carbon::parse($request->end_date)->endOfDay();
    $format = $request->get('format', 'excel');
    $unitId = $request->get('unit_id');

    // Get employees - filter by unit if provided
    $employeeQuery = Employee::with(['department', 'unit']);

    if ($unitId) {
        $employeeQuery->where('unit_id', $unitId);
    }

    $employees = $employeeQuery->get();

    $reportData = [];
    $totals = [
        'total_normal_scans' => 0,
        'total_reward_scans' => 0,
        'total_scans' => 0,
        'total_amount' => 0
    ];

    foreach ($employees as $employee) {
        // Get normal meal scans (non-reward)
        $normalScans = MealTransaction::where('employee_id', $employee->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->where('is_security_reward', false)
            ->get();

        // Get reward scans
        $rewardScans = MealTransaction::where('employee_id', $employee->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->where('is_security_reward', true)
            ->get();

        $normalCount = $normalScans->count();
        $rewardCount = $rewardScans->count();
        $totalCount = $normalCount + $rewardCount;
        $totalAmount = $normalScans->sum('amount') + $rewardScans->sum('amount');

        $reportData[] = [
            'employee_id' => $employee->id,
            'employee_name' => $employee->formal_name,
            'employee_code' => $employee->employee_code,
            'department' => $employee->department->name ?? 'N/A',
            'unit' => $employee->unit->name ?? 'N/A',
            'normal_scans' => $normalCount,
            'reward_scans' => $rewardCount,
            'total_scans' => $totalCount,
            'total_amount' => $totalAmount,
            'normal_scans_list' => $normalScans,
            'reward_scans_list' => $rewardScans,
        ];

        $totals['total_normal_scans'] += $normalCount;
        $totals['total_reward_scans'] += $rewardCount;
        $totals['total_scans'] += $totalCount;
        $totals['total_amount'] += $totalAmount;
    }

    // Sort by total scans descending
    usort($reportData, function($a, $b) {
        return $b['total_scans'] - $a['total_scans'];
    });

    // Get unit name for the report
    $unitName = null;
    if ($unitId) {
        $unit = Unit::find($unitId);
        $unitName = $unit ? $unit->name : null;
    }

    $data = [
        'employees' => $reportData,
        'totals' => $totals,
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
        'generated_at' => now()->format('Y-m-d H:i:s'),
        'generated_by' => auth()->user()->name,
        'unit_id' => $unitId,
        'unit_name' => $unitName
    ];

    if ($format === 'pdf') {
        return $this->exportScanDataPDF($data);
    }

    return $this->exportScanDataExcel($data);
}

/**
 * Export scan data to Excel/CSV - WITH UNIT FILTER
 */
private function exportScanDataExcel($data)
{
    $filename = 'employee_scan_data_' . $data['start_date'] . '_to_' . $data['end_date'];
    if ($data['unit_name']) {
        $filename .= '_' . str_replace(' ', '_', $data['unit_name']);
    }
    $filename .= '.csv';

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    $callback = function() use ($data) {
        $file = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility
        fwrite($file, "\xEF\xBB\xBF");

        // Report Header
        fputcsv($file, ['EMPLOYEE SCAN DATA REPORT']);
        fputcsv($file, ['Period:', $data['start_date'] . ' to ' . $data['end_date']]);
        if ($data['unit_name']) {
            fputcsv($file, ['Unit:', $data['unit_name']]);
        }
        fputcsv($file, ['Generated On:', $data['generated_at']]);
        fputcsv($file, ['Generated By:', $data['generated_by']]);
        fputcsv($file, []); // Empty row

        // Totals Summary
        fputcsv($file, ['SUMMARY']);
        fputcsv($file, ['Total Employees with Scans', count(array_filter($data['employees'], function($e) { return $e['total_scans'] > 0; }))]);
        fputcsv($file, ['Total Normal Scans', $data['totals']['total_normal_scans']]);
        fputcsv($file, ['Total Reward Scans', $data['totals']['total_reward_scans']]);
        fputcsv($file, ['Total Scans', $data['totals']['total_scans']]);
        fputcsv($file, ['Total Amount', 'KES ' . number_format($data['totals']['total_amount'], 2)]);
        fputcsv($file, []); // Empty row

        // Main Data Table
        fputcsv($file, ['EMPLOYEE SCAN DETAILS']);
        fputcsv($file, [
            '#',
            'Employee Name',
            'Employee Code',
            'Department',
            'Unit',
            'Normal Scans',
            'Reward Scans',
            'Total Scans',
            'Total Amount',
            'Signature'
        ]);

        $counter = 1;
        foreach ($data['employees'] as $employee) {
            fputcsv($file, [
                $counter++,
                $employee['employee_name'],
                $employee['employee_code'],
                $employee['department'],
                $employee['unit'],
                $employee['normal_scans'],
                $employee['reward_scans'],
                $employee['total_scans'],
                'KES ' . number_format($employee['total_amount'], 2),
                '' // Signature column
            ]);
        }

        fputcsv($file, []); // Empty row
        fputcsv($file, ['SIGNATURE']);
        fputcsv($file, ['Authorized Signature:', '_________________________']);
        fputcsv($file, ['Date:', '_________________________']);
        fputcsv($file, ['Printed Name:', '_________________________']);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

/**
 * Export scan data to PDF - WITH UNIT FILTER
 */
private function exportScanDataPDF($data)
{
    if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
        throw new \Exception('PDF generation library not installed. Run: composer require barryvdh/laravel-dompdf');
    }

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reeds.admin.employees.scan-data-pdf', $data);
    $pdf->setPaper('A4', 'landscape');

    $filename = 'employee_scan_data_' . $data['start_date'] . '_to_' . $data['end_date'];
    if ($data['unit_name']) {
        $filename .= '_' . str_replace(' ', '_', $data['unit_name']);
    }
    $filename .= '.pdf';

    return $pdf->download($filename);
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
