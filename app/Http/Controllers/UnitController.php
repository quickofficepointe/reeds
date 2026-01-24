<?php

namespace App\Http\Controllers;

use App\Models\MealTransaction;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $units = Unit::query()
            ->when(request('search'), function($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            })
            ->when(request('status') !== null, function($query) {
                $query->where('is_active', request('status'));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Calculate stats for the dashboard cards
        $totalUnits = Unit::count();
        $activeUnits = Unit::where('is_active', true)->count();
        $inactiveUnits = Unit::where('is_active', false)->count();
        $totalCapacity = Unit::sum('capacity');

        return view('reeds.admin.units.index', compact(
            'units',
            'totalUnits',
            'activeUnits',
            'inactiveUnits',
            'totalCapacity'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:units,code',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $unit = Unit::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Unit created successfully!',
                'unit' => $unit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create unit. Please try again.'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:units,code,' . $unit->id,
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $unit->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Unit updated successfully!',
                'unit' => $unit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update unit. Please try again.'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit)
    {
        try {
            // Check if unit has employees
            if ($unit->current_employee_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete unit. It has employees assigned to it.'
                ], 422);
            }

            $unit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Unit deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete unit. Please try again.'
            ], 500);
        }
    }

    /**
     * Toggle unit status (active/inactive)
     */
    public function toggleStatus(Unit $unit)
    {
        try {
            $unit->update([
                'is_active' => !$unit->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Unit status updated successfully!',
                'unit' => $unit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update unit status. Please try again.'
            ], 500);
        }
    }
 public function analytics(Unit $unit)
    {
        $totalEmployees = $unit->employees()->count();
        $activeEmployees = $unit->employees()->where('is_active', true)->count();

        $totalVendors = User::where('role', 2)->where('unit_id', $unit->id)->count();

        // Get meal transactions for this unit
        $employeeIds = $unit->employees()->pluck('id');
        $totalMeals = MealTransaction::whereIn('employee_id', $employeeIds)->count();
        $todayMeals = MealTransaction::whereIn('employee_id', $employeeIds)
            ->whereDate('meal_date', today())
            ->count();

        // Get recent transactions
        $recentTransactions = MealTransaction::whereIn('employee_id', $employeeIds)
            ->with(['employee', 'vendor'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get employees by department
        $employeesByDepartment = $unit->employees()
            ->with('department')
            ->selectRaw('department_id, count(*) as count')
            ->groupBy('department_id')
            ->get();

        return view('reeds.admin.units.analytics', compact(
            'unit',
            'totalEmployees',
            'activeEmployees',
            'totalVendors',
            'totalMeals',
            'todayMeals',
            'recentTransactions',
            'employeesByDepartment'
        ));
    }

    /**
     * Get employees by unit.
     */
    public function getEmployees(Unit $unit)
    {
        $employees = $unit->employees()
            ->with(['department', 'subDepartment', 'additionalData'])
            ->when(request('search'), function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when(request('department_id'), function($query, $departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->when(request('status') !== null, function($query) {
                $query->where('is_active', request('status'));
            })
            ->orderBy('first_name')
            ->paginate(15);

        return view('reeds.admin.units.employees', compact('unit', 'employees'));
    }

    /**
     * Get vendors by unit.
     */
    public function getVendors(Unit $unit)
    {
        $vendors = User::where('role', 2)
            ->where('unit_id', $unit->id)
            ->when(request('search'), function($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->with('unit')
            ->orderBy('name')
            ->paginate(15);

        return view('reeds.admin.units.vendors', compact('unit', 'vendors'));
    }

    /**
     * Get meal transactions by unit.
     */
    public function getMealTransactions(Unit $unit)
    {
        $employeeIds = $unit->employees()->pluck('id');

        $transactions = MealTransaction::whereIn('employee_id', $employeeIds)
            ->when(request('date_from'), function($query) {
                $query->whereDate('meal_date', '>=', request('date_from'));
            })
            ->when(request('date_to'), function($query) {
                $query->whereDate('meal_date', '<=', request('date_to'));
            })
            ->when(request('vendor_id'), function($query, $vendorId) {
                $query->where('vendor_id', $vendorId);
            })
            ->with(['employee', 'vendor'])
            ->orderBy('meal_date', 'desc')
            ->orderBy('meal_time', 'desc')
            ->paginate(20);

        $totalAmount = $transactions->sum('amount');

        return view('reeds.admin.units.meal-transactions', compact('unit', 'transactions', 'totalAmount'));
    }

    /**
     * Get unit statistics for dashboard.
     */
    public function getStatistics(Unit $unit)
    {
        $totalEmployees = $unit->employees()->count();
        $activeEmployees = $unit->employees()->where('is_active', true)->count();
        $totalVendors = User::where('role', 2)->where('unit_id', $unit->id)->count();

        $employeeIds = $unit->employees()->pluck('id');
        $totalMeals = MealTransaction::whereIn('employee_id', $employeeIds)->count();
        $todayMeals = MealTransaction::whereIn('employee_id', $employeeIds)
            ->whereDate('meal_date', today())
            ->count();

        // Get weekly meal statistics
        $weeklyMeals = MealTransaction::whereIn('employee_id', $employeeIds)
            ->whereDate('meal_date', '>=', now()->subDays(7))
            ->selectRaw('DATE(meal_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_employees' => $totalEmployees,
                'active_employees' => $activeEmployees,
                'total_vendors' => $totalVendors,
                'total_meals' => $totalMeals,
                'today_meals' => $todayMeals,
                'weekly_meals' => $weeklyMeals,
                'unit_capacity' => $unit->capacity,
                'available_slots' => $unit->capacity ? $unit->capacity - $unit->current_employee_count : null,
                'occupancy_rate' => $unit->capacity ? round(($unit->current_employee_count / $unit->capacity) * 100, 2) : null,
            ]
        ]);
    }
    /**
     * Get unit data for edit modal
     */
    public function editModal(Unit $unit)
    {
        try {
            return response()->json([
                'success' => true,
                'unit' => $unit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load unit data.'
            ], 500);
        }
    }
}
