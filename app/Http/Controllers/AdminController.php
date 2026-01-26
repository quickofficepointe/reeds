<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\MealTransaction;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Profile;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function index()
    {
        $today = now()->format('Y-m-d');
        $weekStart = now()->startOfWeek()->format('Y-m-d');
        $monthStart = now()->startOfMonth()->format('Y-m-d');

        // Get dashboard statistics
        $stats = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('is_active', true)->count(),
            'total_vendors' => User::where('role', 2)->count(),
            'verified_vendors' => Profile::whereHas('user', function($q) {
                $q->where('role', 2);
            })->where('is_verified', true)->count(),
            'pending_verifications' => Profile::whereHas('user', function($q) {
                $q->where('role', 2);
            })->where('is_verified', false)->count(),
            'today_scans' => MealTransaction::whereDate('meal_date', $today)->count(),
            'week_scans' => MealTransaction::whereDate('meal_date', '>=', $weekStart)->count(),
            'month_scans' => MealTransaction::whereDate('meal_date', '>=', $monthStart)->count(),
            'total_revenue_today' => MealTransaction::whereDate('meal_date', $today)->sum('amount'),
            'total_revenue_week' => MealTransaction::whereDate('meal_date', '>=', $weekStart)->sum('amount'),
            'total_revenue_month' => MealTransaction::whereDate('meal_date', '>=', $monthStart)->sum('amount'),
        ];

        // Get recent meal transactions
        $recentTransactions = MealTransaction::with([
                'employee.department',
                'employee.unit',
                'vendor'
            ])
            ->whereHas('employee')
            ->whereHas('vendor')
            ->latest()
            ->take(10)
            ->get();

        // Get top vendors
        $topVendors = User::where('role', 2)
            ->withCount(['mealTransactions as total_scans' => function($query) use ($monthStart) {
                $query->where('meal_date', '>=', $monthStart);
            }])
            ->withSum(['mealTransactions as total_revenue' => function($query) use ($monthStart) {
                $query->where('meal_date', '>=', $monthStart);
            }], 'amount')
            ->orderBy('total_scans', 'desc')
            ->take(5)
            ->get();

        // Get department-wise employee count
        $departmentStats = Department::withCount(['employees as total_employees'])
            ->having('total_employees', '>', 0)
            ->orderBy('total_employees', 'desc')
            ->get();

        // Get unit statistics
        $unitStats = $this->getUnitStats();

        return view('reeds.admin.index', compact(
            'stats',
            'recentTransactions',
            'topVendors',
            'departmentStats',
            'unitStats'
        ));
    }

    /**
     * Get unit statistics
     */
    private function getUnitStats()
{
    $units = Unit::active()
        ->withCount([
            'employees as total_employees',
            'employees as active_employees' => function($query) {
                $query->where('is_active', true);
            }
        ])
        ->get();

    return $units->map(function($unit) {
        // Calculate scans using the computed attributes
        $totalScans = $unit->mealTransactions()->count();
        $monthScans = $unit->mealTransactions()
            ->where('meal_date', '>=', now()->startOfMonth())
            ->count();
        $todayScans = $unit->mealTransactions()
            ->whereDate('meal_date', today())
            ->count();
        $monthRevenue = $unit->mealTransactions()
            ->where('meal_date', '>=', now()->startOfMonth())
            ->sum('amount');

        // Count active vendors directly
        $activeVendorsCount = User::where('role', User::ROLE_VENDOR)
            ->where('unit_id', $unit->id)
            ->whereHas('profile', function($q) {
                $q->where('is_verified', true);
            })
            ->count();

        // Calculate capacity utilization
        $capacityUtilization = null;
        if ($unit->capacity && $unit->capacity > 0) {
            $capacityUtilization = round(($unit->current_employee_count / $unit->capacity) * 100, 0);
        }

        return [
            'id' => $unit->id,
            'name' => $unit->name,
            'code' => $unit->code, // Added
            'location' => $unit->location, // Added
            'capacity' => $unit->capacity, // Added
            'current_employee_count' => $unit->current_employee_count, // Added
            'capacity_utilization' => $capacityUtilization, // Added
            'total_employees' => $unit->total_employees,
            'active_employees' => $unit->active_employees,
            'total_scans' => $totalScans,
            'month_scans' => $monthScans,
            'today_scans' => $todayScans,
            'month_revenue' => $monthRevenue,
            'active_vendors' => $activeVendorsCount,
        ];
    });
}

    /**
     * Get analytics data for charts WITH UNIT FILTER
     */
    public function getAnalyticsData(Request $request)
    {
        $period = $request->get('period', 'week');
        $unitId = $request->get('unit_id', 'all');

        switch ($period) {
            case 'week':
                $startDate = now()->subDays(7);
                $format = 'Y-m-d';
                break;
            case 'month':
                $startDate = now()->subDays(30);
                $format = 'Y-m-d';
                break;
            case 'year':
                $startDate = now()->subMonths(12);
                $format = 'Y-m';
                break;
            default:
                $startDate = now()->subDays(7);
                $format = 'Y-m-d';
        }

        // Base query with unit filter
        $baseQuery = MealTransaction::query();
        if ($unitId !== 'all') {
            $baseQuery->whereHas('employee', function($query) use ($unitId) {
                $query->where('unit_id', $unitId);
            });
        }

        // Daily/Monthly scans data
        $scansData = $baseQuery->where('created_at', '>=', $startDate)
            ->select(
                DB::raw("DATE_FORMAT(meal_date, '{$format}') as period"),
                DB::raw('COUNT(*) as scans'),
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Vendor performance data (with unit filter)
        $vendorQuery = User::where('role', 2);
        if ($unitId !== 'all') {
            $vendorQuery->where('unit_id', $unitId);
        }

        $vendorPerformance = $vendorQuery
            ->withCount(['mealTransactions as total_scans' => function($query) use ($startDate, $unitId) {
                $query->where('created_at', '>=', $startDate);
                if ($unitId !== 'all') {
                    $query->whereHas('employee', function($q) use ($unitId) {
                        $q->where('unit_id', $unitId);
                    });
                }
            }])
            ->withSum(['mealTransactions as total_revenue' => function($query) use ($startDate, $unitId) {
                $query->where('created_at', '>=', $startDate);
                if ($unitId !== 'all') {
                    $query->whereHas('employee', function($q) use ($unitId) {
                        $q->where('unit_id', $unitId);
                    });
                }
            }], 'amount')
            ->having('total_scans', '>', 0)
            ->orderBy('total_scans', 'desc')
            ->take(10)
            ->get()
            ->map(function($vendor) {
                return [
                    'name' => $vendor->name,
                    'scans' => $vendor->total_scans,
                    'revenue' => $vendor->total_revenue
                ];
            });

        // Unit performance data
        $unitPerformance = Unit::active()
            ->withCount(['mealTransactions as scans' => function($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }])
            ->withSum(['mealTransactions as revenue' => function($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }], 'amount')
            ->withCount(['employees as employees'])
            ->having('scans', '>', 0)
            ->orderBy('scans', 'desc')
            ->get()
            ->map(function($unit) {
                return [
                    'name' => $unit->name,
                    'employees' => $unit->employees,
                    'scans' => $unit->scans,
                    'revenue' => $unit->revenue
                ];
            });

        // Department-wise feeding data (with unit filter)
        $departmentQuery = Department::query();
        if ($unitId !== 'all') {
            $departmentQuery->whereHas('employees', function($q) use ($unitId) {
                $q->where('unit_id', $unitId);
            });
        }

        $departmentFeeding = $departmentQuery
            ->withCount(['employees as employee_count' => function($query) use ($unitId) {
                if ($unitId !== 'all') {
                    $query->where('unit_id', $unitId);
                }
            }])
            ->withCount(['employees as fed_today_count' => function($query) use ($unitId) {
                $query->whereHas('mealTransactions', function($q) {
                    $q->whereDate('meal_date', today());
                });
                if ($unitId !== 'all') {
                    $query->where('unit_id', $unitId);
                }
            }])
            ->having('employee_count', '>', 0)
            ->get()
            ->map(function($dept) {
                return [
                    'name' => $dept->name,
                    'total_employees' => $dept->employee_count,
                    'fed_today' => $dept->fed_today_count,
                    'feeding_rate' => $dept->employee_count > 0 ?
                        round(($dept->fed_today_count / $dept->employee_count) * 100, 2) : 0
                ];
            });

        // Employee feeding behavior (with unit filter)
        $employeeQuery = Employee::query();
        if ($unitId !== 'all') {
            $employeeQuery->where('unit_id', $unitId);
        }

        $employeeBehavior = [
            'frequent_eaters' => $employeeQuery
                ->withCount(['mealTransactions as meal_count' => function($query) use ($startDate) {
                    $query->where('created_at', '>=', $startDate);
                }])
                ->having('meal_count', '>', 0)
                ->orderBy('meal_count', 'desc')
                ->take(10)
                ->get(),

            'preferred_vendors' => DB::table('meal_transactions')
                ->join('employees', 'meal_transactions.employee_id', '=', 'employees.id')
                ->join('users', 'meal_transactions.vendor_id', '=', 'users.id')
                ->select(
                    'employees.formal_name as employee_name',
                    'users.name as vendor_name',
                    DB::raw('COUNT(*) as visit_count')
                )
                ->where('meal_transactions.created_at', '>=', $startDate)
                ->when($unitId !== 'all', function($query) use ($unitId) {
                    $query->where('employees.unit_id', $unitId); // Fixed: use employees.unit_id
                })
                ->groupBy('meal_transactions.employee_id', 'meal_transactions.vendor_id')
                ->orderBy('visit_count', 'desc')
                ->take(10)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'scans_data' => $scansData,
            'vendor_performance' => $vendorPerformance,
            'unit_performance' => $unitPerformance,
            'department_feeding' => $departmentFeeding,
            'employee_behavior' => $employeeBehavior,
            'period' => $period,
            'unit_id' => $unitId
        ]);
    }

    /**
     * Show analytics page WITH UNIT STATS
     */
    public function analytics()
    {
        $today = now()->format('Y-m-d');
        $weekStart = now()->startOfWeek()->format('Y-m-d');
        $monthStart = now()->startOfMonth()->format('Y-m-d');

        // Get analytics statistics
        $stats = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('is_active', true)->count(),
            'total_vendors' => User::where('role', 2)->count(),
            'verified_vendors' => Profile::whereHas('user', function($q) {
                $q->where('role', 2);
            })->where('is_verified', true)->count(),
            'today_scans' => MealTransaction::whereDate('meal_date', $today)->count(),
            'week_scans' => MealTransaction::whereDate('meal_date', '>=', $weekStart)->count(),
            'month_scans' => MealTransaction::whereDate('meal_date', '>=', $monthStart)->count(),
            'total_revenue_today' => MealTransaction::whereDate('meal_date', $today)->sum('amount'),
            'total_revenue_week' => MealTransaction::whereDate('meal_date', '>=', $weekStart)->sum('amount'),
            'total_revenue_month' => MealTransaction::whereDate('meal_date', '>=', $monthStart)->sum('amount'),
            'avg_daily_scans_week' => MealTransaction::whereDate('meal_date', '>=', $weekStart)
                ->groupBy('meal_date')
                ->select(DB::raw('COUNT(*) as daily_count'))
                ->get()
                ->avg('daily_count') ?? 0,
            'avg_daily_scans_month' => MealTransaction::whereDate('meal_date', '>=', $monthStart)
                ->groupBy('meal_date')
                ->select(DB::raw('COUNT(*) as daily_count'))
                ->get()
                ->avg('daily_count') ?? 0,
        ];

        // Get top vendors for the current month
        $topVendors = User::where('role', 2)
            ->withCount(['mealTransactions as total_scans' => function($query) use ($monthStart) {
                $query->where('meal_date', '>=', $monthStart);
            }])
            ->withSum(['mealTransactions as total_revenue' => function($query) use ($monthStart) {
                $query->where('meal_date', '>=', $monthStart);
            }], 'amount')
            ->orderBy('total_scans', 'desc')
            ->take(5)
            ->get();

        // Get department statistics
        $departmentStats = Department::withCount(['employees as total_employees'])
            ->withCount(['employees as active_employees' => function($query) {
                $query->where('is_active', true);
            }])
            ->having('total_employees', '>', 0)
            ->orderBy('total_employees', 'desc')
            ->get();

        // Get unit statistics
        $unitStats = $this->getUnitStats();

        // Get all units for filtering
        $units = Unit::active()->get();

        // Get recent transactions for the table - FIXED: access unit through employee
        $recentTransactions = MealTransaction::with([
                'employee.department',
                'employee.unit', // Fixed: access unit through employee
                'vendor'
            ])
            ->whereHas('employee')
            ->whereHas('vendor')
            ->latest()
            ->take(10)
            ->get();

        return view('reeds.admin.analytics', compact(
            'stats',
            'topVendors',
            'departmentStats',
            'unitStats',
            'units',
            'recentTransactions'
        ));
    }

    /**
     * Get unit-specific analytics
     */
    public function getUnitAnalytics(Unit $unit)
    {
        try {
            // Get monthly trends (last 30 days)
            $monthlyTrends = [
                'labels' => [],
                'scans' => [],
                'revenue' => []
            ];

            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $formattedDate = $date->format('M d');

                $dailyStats = MealTransaction::whereHas('employee', function($query) use ($unit) {
                        $query->where('unit_id', $unit->id);
                    })
                    ->whereDate('meal_date', $date)
                    ->select(
                        DB::raw('COUNT(*) as scans'),
                        DB::raw('SUM(amount) as revenue')
                    )
                    ->first();

                $monthlyTrends['labels'][] = $formattedDate;
                $monthlyTrends['scans'][] = $dailyStats->scans ?? 0;
                $monthlyTrends['revenue'][] = $dailyStats->revenue ?? 0;
            }

            // Get top employees in this unit
            $topEmployees = MealTransaction::whereHas('employee', function($query) use ($unit) {
                    $query->where('unit_id', $unit->id);
                })
                ->where('meal_date', '>=', now()->startOfMonth())
                ->with('employee.department')
                ->select(
                    'employee_id',
                    DB::raw('COUNT(*) as meal_count'),
                    DB::raw('SUM(amount) as total_amount')
                )
                ->groupBy('employee_id')
                ->orderByDesc('meal_count')
                ->limit(5)
                ->get()
                ->map(function($transaction) {
                    return [
                        'formal_name' => $transaction->employee->formal_name ?? 'Unknown',
                        'department' => $transaction->employee->department->name ?? 'N/A',
                        'meal_count' => $transaction->meal_count,
                        'total_amount' => $transaction->total_amount,
                    ];
                });

            // Get active vendors in this unit
            $vendors = User::where('role', 2)
                ->where('unit_id', $unit->id)
                ->whereHas('profile', function($q) {
                    $q->where('is_verified', true);
                })
                ->with(['mealTransactions' => function($query) use ($unit) {
                    $query->whereHas('employee', function($q) use ($unit) {
                        $q->where('unit_id', $unit->id);
                    })
                    ->latest()
                    ->limit(1);
                }])
                ->withCount(['mealTransactions as scans' => function($query) use ($unit) {
                    $query->whereHas('employee', function($q) use ($unit) {
                        $q->where('unit_id', $unit->id);
                    })
                    ->where('meal_date', '>=', now()->startOfMonth());
                }])
                ->get()
                ->map(function($vendor) {
                    return [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'email' => $vendor->email,
                        'scans' => $vendor->scans,
                        'last_scan' => $vendor->mealTransactions->first()
                            ? $vendor->mealTransactions->first()->meal_date->format('M d, Y')
                            : 'Never'
                    ];
                });

            // Get unit statistics
            $unitStats = [
                'id' => $unit->id,
                'name' => $unit->name,
                'code' => $unit->code,
                'location' => $unit->location,
                'total_employees' => $unit->employees()->count(),
                'active_employees' => $unit->employees()->where('is_active', true)->count(),
                'today_scans' => $unit->mealTransactions()->whereDate('meal_date', today())->count(),
                'month_scans' => $unit->mealTransactions()
                    ->where('meal_date', '>=', now()->startOfMonth())
                    ->count(),
                'month_revenue' => $unit->mealTransactions()
                    ->where('meal_date', '>=', now()->startOfMonth())
                    ->sum('amount'),
                'active_vendors' => $unit->users()
                    ->where('role', 2)
                    ->whereHas('profile', function($q) {
                        $q->where('is_verified', true);
                    })
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'unit' => array_merge($unitStats, [
                    'monthly_trends' => $monthlyTrends,
                    'top_employees' => $topEmployees,
                    'vendors' => $vendors,
                ])
            ]);

        } catch (\Exception $e) {
            Log::error('Unit analytics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load unit analytics'
            ], 500);
        }
    }

    /**
     * Get vendor details
     */
    public function getVendorDetails($vendorId)
    {
        $vendor = User::with(['profile'])->findOrFail($vendorId);

        $stats = [
            'total_scans' => $vendor->mealTransactions()->count(),
            'today_scans' => $vendor->mealTransactions()->whereDate('meal_date', today())->count(),
            'week_scans' => $vendor->mealTransactions()->whereDate('meal_date', '>=', now()->startOfWeek())->count(),
            'month_scans' => $vendor->mealTransactions()->whereDate('meal_date', '>=', now()->startOfMonth())->count(),
            'total_revenue' => $vendor->mealTransactions()->sum('amount'),
            'avg_daily_scans' => $vendor->mealTransactions()
                ->whereDate('meal_date', '>=', now()->subDays(30))
                ->groupBy('meal_date')
                ->select(DB::raw('COUNT(*) as daily_count'))
                ->get()
                ->avg('daily_count') ?? 0
        ];

        // Recent transactions - FIXED: access unit through employee
        $recentTransactions = $vendor->mealTransactions()
            ->with(['employee.department', 'employee.unit'])
            ->latest()
            ->take(10)
            ->get();

        // Top employees served
        $topEmployees = $vendor->mealTransactions()
            ->join('employees', 'meal_transactions.employee_id', '=', 'employees.id')
            ->select(
                'employees.id',
                'employees.formal_name',
                'employees.department_id',
                DB::raw('COUNT(*) as visit_count')
            )
            ->groupBy('employee_id')
            ->orderBy('visit_count', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'vendor' => $vendor,
            'stats' => $stats,
            'recent_transactions' => $recentTransactions,
            'top_employees' => $topEmployees
        ]);
    }
}
