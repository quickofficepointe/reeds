<?php

namespace App\Http\Controllers;

use App\Models\SubDepartment;
use App\Models\Department;
use Illuminate\Http\Request;

class SubDepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subDepartments = SubDepartment::with(['department', 'employees'])
            ->latest()
            ->get();

        $departments = Department::active()->get(); // For modal dropdown

        return view('reeds.admin.departments.subdepartment.index', compact('subDepartments', 'departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        try {
            // Check if sub-department already exists in this department
            $exists = SubDepartment::where('department_id', $request->department_id)
                ->where('name', $request->name)
                ->exists();

            if ($exists) {
                return response()->json(['error' => 'Sub-department with this name already exists in the selected department!'], 422);
            }

            SubDepartment::create($request->all());
            return response()->json(['success' => 'Sub-department created successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create sub-department: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SubDepartment $subDepartment)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        try {
            // Check if sub-department name already exists in this department (excluding current)
            $exists = SubDepartment::where('department_id', $request->department_id)
                ->where('name', $request->name)
                ->where('id', '!=', $subDepartment->id)
                ->exists();

            if ($exists) {
                return response()->json(['error' => 'Sub-department with this name already exists in the selected department!'], 422);
            }

            $subDepartment->update($request->all());
            return response()->json(['success' => 'Sub-department updated successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update sub-department: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubDepartment $subDepartment)
    {
        try {
            if ($subDepartment->employees()->exists()) {
                return response()->json(['error' => 'Cannot delete sub-department with existing employees!'], 422);
            }

            $subDepartment->delete();
            return response()->json(['success' => 'Sub-department deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete sub-department: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Toggle sub-department status
     */
    public function toggleStatus(SubDepartment $subDepartment)
    {
        try {
            $subDepartment->update([
                'is_active' => !$subDepartment->is_active
            ]);

            $status = $subDepartment->is_active ? 'activated' : 'deactivated';
            return response()->json(['success' => "Sub-department {$status} successfully!"]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update sub-department status!'], 500);
        }
    }

    /**
     * Get sub-departments by department (for dropdowns)
     */
    public function byDepartment(Department $department)
    {
        try {
            $subDepartments = $department->subDepartments()->active()->get();
            return response()->json($subDepartments);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch sub-departments!'], 500);
        }
    }
}
