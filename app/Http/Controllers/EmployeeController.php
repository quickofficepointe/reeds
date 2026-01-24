<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Unit;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeesImport;
use App\Exports\EmployeesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::with(['department', 'subDepartment', 'unit'])
            ->latest()
            ->paginate(20);

        $departments = Department::active()->get();
        $subDepartments = SubDepartment::active()->get();
        $units = Unit::active()->get(); // Add units

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
        return view('reeds.admin.employees.import');
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
            \Log::error('Employee import failed: ' . $e->getMessage());
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
            'unit_id' => 'nullable|exists:units,id', // Added unit validation
            'payroll_no' => 'nullable|string|max:50|unique:employees',
            'employment_type' => 'required|string|max:50',
            'title' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100|unique:employees', // Added email validation
            'phone' => 'nullable|string|max:20', // Added phone validation
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
            \Log::error('Employee creation failed: ' . $e->getMessage());
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
            'unit_id' => 'nullable|exists:units,id', // Added unit validation
            'payroll_no' => 'nullable|string|max:50|unique:employees,payroll_no,' . $employee->id,
            'employment_type' => 'required|string|max:50',
            'title' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100|unique:employees,email,' . $employee->id, // Added email validation
            'phone' => 'nullable|string|max:20', // Added phone validation
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
            \Log::error('Employee update failed: ' . $e->getMessage());
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
            \Log::error('Employee deletion failed: ' . $e->getMessage());
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
            \Log::error('QR code generation failed: ' . $e->getMessage());
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
            \Log::error('Bulk QR regeneration failed: ' . $e->getMessage());
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
            \Log::error('Employee status toggle failed: ' . $e->getMessage());
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
            \Log::error('Fetch employees by department failed: ' . $e->getMessage());
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
            \Log::error('Fetch employees by sub-department failed: ' . $e->getMessage());
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
            \Log::error('Fetch employees by unit failed: ' . $e->getMessage());
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
        $units = Unit::active()->get(); // Add units

        return response()->json([
            'departments' => $departments,
            'sub_departments' => $subDepartments,
            'units' => $units // Add units to response
        ]);
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee)
    {
        $employee->load(['department', 'subDepartment', 'unit']); // Load unit

        $departments = Department::active()->get();
        $subDepartments = SubDepartment::active()->get();
        $units = Unit::active()->get(); // Add units

        return response()->json([
            'employee' => $employee,
            'departments' => $departments,
            'sub_departments' => $subDepartments,
            'units' => $units // Add units to response
        ]);
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee)
    {
        try {
            $employee->load(['department', 'subDepartment', 'unit']); // Load unit

            return response()->json([
                'success' => true,
                'employee' => $employee
            ]);
        } catch (\Exception $e) {
            \Log::error('Employee show failed: ' . $e->getMessage());
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
                          ->orWhere('email', 'LIKE', "%{$searchTerm}%") // Added email search
                          ->orWhere('phone', 'LIKE', "%{$searchTerm}%") // Added phone search
                          ->orWhereHas('department', function($q) use ($searchTerm) {
                              $q->where('name', 'LIKE', "%{$searchTerm}%");
                          })
                          ->orWhereHas('subDepartment', function($q) use ($searchTerm) {
                              $q->where('name', 'LIKE', "%{$searchTerm}%");
                          })
                          ->orWhereHas('unit', function($q) use ($searchTerm) { // Added unit search
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
            \Log::error('Employee search failed: ' . $e->getMessage());
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
            \Log::error('Bulk employee deletion failed: ' . $e->getMessage());
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
            \Log::error('Bulk employee status update failed: ' . $e->getMessage());
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
                    'department_stats' => $departmentStats,
                    'unit_stats' => $unitStats // Added unit stats
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Employee stats fetch failed: ' . $e->getMessage());
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
            \Log::error('Bulk phone update failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update phone numbers!'], 500);
        }
    }
}
