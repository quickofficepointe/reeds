<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departments = Department::withCount(['employees', 'subDepartments'])->latest()->get();
        return view('reeds.admin.departments.index', compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments',
            'code' => 'nullable|string|max:50|unique:departments',
            'description' => 'nullable|string',
        ]);

        try {
            Department::create($request->all());
            return response()->json(['success' => 'Department created successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create department: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            'code' => 'nullable|string|max:50|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        try {
            $department->update($request->all());
            return response()->json(['success' => 'Department updated successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update department: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        try {
            // Check if department has employees or sub-departments
            if ($department->employees()->exists()) {
                return response()->json(['error' => 'Cannot delete department with existing employees!'], 422);
            }

            if ($department->subDepartments()->exists()) {
                return response()->json(['error' => 'Cannot delete department with existing sub-departments!'], 422);
            }

            $department->delete();
            return response()->json(['success' => 'Department deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete department: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Toggle department status
     */
    public function toggleStatus(Department $department)
    {
        try {
            $department->update([
                'is_active' => !$department->is_active
            ]);

            $status = $department->is_active ? 'activated' : 'deactivated';
            return response()->json(['success' => "Department {$status} successfully!"]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update department status!'], 500);
        }
    }
}
