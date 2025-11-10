<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\MealTransaction;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Profile;
use Illuminate\Http\Request;
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
        $recentTransactions = MealTransaction::with(['employee.department', 'vendor'])
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

        return view('reeds.admin.index', compact('stats', 'recentTransactions', 'topVendors', 'departmentStats'));
    }

    /**
     * Get analytics data for charts
     */
    public function getAnalyticsData(Request $request)
    {
        $period = $request->get('period', 'week'); // week, month, year

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

        // Daily/Monthly scans data
        $scansData = MealTransaction::where('created_at', '>=', $startDate)
            ->select(
                DB::raw("DATE_FORMAT(meal_date, '{$format}') as period"),
                DB::raw('COUNT(*) as scans'),
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Vendor performance data
        $vendorPerformance = User::where('role', 2)
            ->withCount(['mealTransactions as total_scans' => function($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }])
            ->withSum(['mealTransactions as total_revenue' => function($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
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

        // Department-wise feeding data
        $departmentFeeding = Department::withCount(['employees as employee_count'])
            ->withCount(['employees as fed_today_count' => function($query) {
                $query->whereHas('mealTransactions', function($q) {
                    $q->whereDate('meal_date', today());
                });
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

        // Employee feeding behavior
        $employeeBehavior = [
            'frequent_eaters' => Employee::withCount(['mealTransactions as meal_count' => function($query) use ($startDate) {
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
                ->groupBy('employee_id', 'vendor_id')
                ->orderBy('visit_count', 'desc')
                ->take(10)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'scans_data' => $scansData,
            'vendor_performance' => $vendorPerformance,
            'department_feeding' => $departmentFeeding,
            'employee_behavior' => $employeeBehavior,
            'period' => $period
        ]);
    }

    /**
     * Show analytics page
     */
    public function analytics()
    {
        return view('reeds.admin.analytics');
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

        // Recent transactions
        $recentTransactions = $vendor->mealTransactions()
            ->with('employee.department')
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
