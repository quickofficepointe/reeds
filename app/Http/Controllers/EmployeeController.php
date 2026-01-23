<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\SubDepartment;
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
        $employees = Employee::with(['department', 'subDepartment'])
            ->latest()
            ->paginate(20);

        $departments = Department::active()->get();
        $subDepartments = SubDepartment::active()->get();

        return view('reeds.admin.employees.index', compact('employees', 'departments', 'subDepartments'));
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
        $employees = Employee::with(['department', 'subDepartment'])
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
            'payroll_no' => 'nullable|string|max:50|unique:employees',
            'employment_type' => 'required|string|max:50',
            'title' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
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
            'payroll_no' => 'nullable|string|max:50|unique:employees,payroll_no,' . $employee->id,
            'employment_type' => 'required|string|max:50',
            'title' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
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
     * Show the form for creating a new employee.
     */
    public function create()
    {
        $departments = Department::active()->get();
        $subDepartments = SubDepartment::active()->get();

        return response()->json([
            'departments' => $departments,
            'sub_departments' => $subDepartments
        ]);
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee)
    {
        $employee->load(['department', 'subDepartment']);

        $departments = Department::active()->get();
        $subDepartments = SubDepartment::active()->get();

        return response()->json([
            'employee' => $employee,
            'departments' => $departments,
            'sub_departments' => $subDepartments
        ]);
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee)
    {
        try {
            $employee->load(['department', 'subDepartment']);

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
     * Search employees
     */
    public function search(Request $request)
    {
        try {
            $searchTerm = $request->get('search');

            $employees = Employee::with(['department', 'subDepartment'])
                ->where(function($query) use ($searchTerm) {
                    $query->where('employee_code', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('payroll_no', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('icard_number', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('designation', 'LIKE', "%{$searchTerm}%")
                          ->orWhereHas('department', function($q) use ($searchTerm) {
                              $q->where('name', 'LIKE', "%{$searchTerm}%");
                          })
                          ->orWhereHas('subDepartment', function($q) use ($searchTerm) {
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
     * Get employee statistics
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

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_employees' => $totalEmployees,
                    'active_employees' => $activeEmployees,
                    'inactive_employees' => $inactiveEmployees,
                    'employees_with_qr' => $employeesWithQr,
                    'department_stats' => $departmentStats
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Employee stats fetch failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch employee statistics!'], 500);
        }
    }
}
