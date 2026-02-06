<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\MealTransaction;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Profile;
use App\Models\Vendor;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AdminController extends Controller
{
    /**
     * Display admin dashboard with comprehensive statistics
     */
    public function index()
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');
        $weekStart = now()->startOfWeek()->format('Y-m-d');
        $monthStart = now()->startOfMonth()->format('Y-m-d');
        $lastMonthStart = now()->subMonth()->startOfMonth()->format('Y-m-d');
        $lastMonthEnd = now()->subMonth()->endOfMonth()->format('Y-m-d');

        // Get comprehensive dashboard statistics
        $stats = [
            // Employee Statistics
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('is_active', true)->count(),
            'inactive_employees' => Employee::where('is_active', false)->count(),
            'new_employees_this_month' => Employee::where('created_at', '>=', $monthStart)->count(),
            'employee_growth_rate' => $this->calculateGrowthRate(
                Employee::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count(),
                Employee::where('created_at', '>=', $monthStart)->count()
            ),

            // Vendor Statistics
            'total_vendors' => User::where('role', 2)->count(),
            'verified_vendors' => Profile::whereHas('user', function($q) {
                $q->where('role', 2);
            })->where('is_verified', true)->count(),
            'pending_verifications' => Profile::whereHas('user', function($q) {
                $q->where('role', 2);
            })->where('is_verified', false)->count(),
            'vendor_verification_rate' => $this->calculateVerificationRate(),

            // Transaction Statistics
            'today_scans' => MealTransaction::whereDate('meal_date', $today)->count(),
            'yesterday_scans' => MealTransaction::whereDate('meal_date', $yesterday)->count(),
            'week_scans' => MealTransaction::whereDate('meal_date', '>=', $weekStart)->count(),
            'month_scans' => MealTransaction::whereDate('meal_date', '>=', $monthStart)->count(),
            'scan_growth_rate' => $this->calculateScanGrowthRate(),

            // Revenue Statistics
            'total_revenue_today' => MealTransaction::whereDate('meal_date', $today)->sum('amount'),
            'total_revenue_yesterday' => MealTransaction::whereDate('meal_date', $yesterday)->sum('amount'),
            'total_revenue_week' => MealTransaction::whereDate('meal_date', '>=', $weekStart)->sum('amount'),
            'total_revenue_month' => MealTransaction::whereDate('meal_date', '>=', $monthStart)->sum('amount'),
            'avg_daily_revenue' => $this->calculateAverageDailyRevenue(),
            'revenue_growth_rate' => $this->calculateRevenueGrowthRate(),

            // Performance Metrics
            'employee_participation_rate' => $this->calculateEmployeeParticipationRate(),
            'avg_scans_per_vendor' => $this->calculateAverageScansPerVendor(),
            'peak_hour' => $this->getPeakHour(),
            'busiest_day' => $this->getBusiestDay(),
            'top_performing_unit' => $this->getTopPerformingUnit(),
            'top_performing_vendor' => $this->getTopPerformingVendor(),
        ];

        // Get trend data for charts
        $trendData = [
            'daily_scans_7d' => $this->getDailyScansTrend(7),
            'daily_scans_30d' => $this->getDailyScansTrend(30),
            'revenue_trend_30d' => $this->getRevenueTrend(30),
            'vendor_performance_trend' => $this->getVendorPerformanceTrend(),
            'department_participation' => $this->getDepartmentParticipation(),
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

        // Get top vendors with detailed metrics
        $topVendors = User::where('role', 2)
            ->withCount(['mealTransactions as total_scans' => function($query) use ($monthStart) {
                $query->where('meal_date', '>=', $monthStart);
            }])
            ->withSum(['mealTransactions as total_revenue' => function($query) use ($monthStart) {
                $query->where('meal_date', '>=', $monthStart);
            }], 'amount')
            ->with(['profile'])
            ->orderBy('total_scans', 'desc')
            ->take(6)
            ->get()
            ->map(function($vendor) use ($monthStart) {
                // Calculate additional metrics
                $vendor->avg_daily_scans = $this->calculateVendorDailyAverage($vendor->id, $monthStart);
                $vendor->customer_retention = $this->calculateVendorRetention($vendor->id);
                $vendor->peak_performance_hour = $this->getVendorPeakHourSimple($vendor->id);

                return $vendor;
            });

        // Get department-wise statistics
        $departmentStats = Department::withCount(['employees as total_employees'])
            ->withCount(['employees as active_employees' => function($query) {
                $query->where('is_active', true);
            }])
            ->withCount(['employees as fed_today' => function($query) use ($today) {
                $query->whereHas('mealTransactions', function($q) use ($today) {
                    $q->whereDate('meal_date', $today);
                });
            }])
            ->having('total_employees', '>', 0)
            ->orderBy('total_employees', 'desc')
            ->get()
            ->map(function($dept) {
                $dept->participation_rate = $dept->total_employees > 0
                    ? round(($dept->fed_today / $dept->total_employees) * 100, 1)
                    : 0;
                return $dept;
            });

        // Get comprehensive unit statistics
        $unitStats = $this->getComprehensiveUnitStats();

        // Get alerts and notifications
        $alerts = $this->getSystemAlerts();

        // Get forecast data
        $forecast = $this->getForecastData();

        return view('reeds.admin.index', compact(
            'stats',
            'trendData',
            'recentTransactions',
            'topVendors',
            'departmentStats',
            'unitStats',
            'alerts',
            'forecast'
        ));
    }

    /**
     * Calculate growth rate
     */
    private function calculateGrowthRate($previous, $current)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Calculate verification rate
     */
    private function calculateVerificationRate()
    {
        $totalVendors = User::where('role', 2)->count();
        $verifiedVendors = Profile::whereHas('user', function($q) {
            $q->where('role', 2);
        })->where('is_verified', true)->count();

        if ($totalVendors == 0) return 0;
        return round(($verifiedVendors / $totalVendors) * 100, 1);
    }

    /**
     * Calculate scan growth rate
     */
    private function calculateScanGrowthRate()
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $todayScans = MealTransaction::whereDate('meal_date', $today)->count();
        $yesterdayScans = MealTransaction::whereDate('meal_date', $yesterday)->count();

        return $this->calculateGrowthRate($yesterdayScans, $todayScans);
    }

    /**
     * Calculate average daily revenue
     */
    private function calculateAverageDailyRevenue()
    {
        $monthStart = now()->startOfMonth()->format('Y-m-d');
        $days = now()->diffInDays(Carbon::parse($monthStart)) + 1;

        $totalRevenue = MealTransaction::whereDate('meal_date', '>=', $monthStart)->sum('amount');

        return $days > 0 ? round($totalRevenue / $days, 2) : 0;
    }

    /**
     * Calculate revenue growth rate
     */
    private function calculateRevenueGrowthRate()
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $todayRevenue = MealTransaction::whereDate('meal_date', $today)->sum('amount');
        $yesterdayRevenue = MealTransaction::whereDate('meal_date', $yesterday)->sum('amount');

        return $this->calculateGrowthRate($yesterdayRevenue, $todayRevenue);
    }

    /**
     * Calculate employee participation rate
     */
    private function calculateEmployeeParticipationRate()
    {
        $today = now()->format('Y-m-d');
        $activeEmployees = Employee::where('is_active', true)->count();
        $fedToday = MealTransaction::whereDate('meal_date', $today)
            ->distinct('employee_id')
            ->count('employee_id');

        if ($activeEmployees == 0) return 0;
        return round(($fedToday / $activeEmployees) * 100, 1);
    }

    /**
     * Calculate average scans per vendor
     */
    private function calculateAverageScansPerVendor()
    {
        $vendorCount = User::where('role', 2)->count();
        $todayScans = MealTransaction::whereDate('meal_date', now()->format('Y-m-d'))->count();

        if ($vendorCount == 0) return 0;
        return round($todayScans / $vendorCount, 1);
    }

    /**
     * Get peak hour
     */
    private function getPeakHour()
    {
        $peak = MealTransaction::whereDate('meal_date', now()->format('Y-m-d'))
            ->select(
                DB::raw('HOUR(meal_time) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('HOUR(meal_time)'))
            ->orderBy('count', 'desc')
            ->first();

        return $peak ? [
            'hour' => $peak->hour . ':00',
            'scans' => $peak->count
        ] : null;
    }

    /**
     * Get busiest day
     */
    private function getBusiestDay()
    {
        $busiest = MealTransaction::where('meal_date', '>=', now()->subDays(7))
            ->select(
                DB::raw('DAYNAME(meal_date) as day'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('DAYNAME(meal_date)'))
            ->orderBy('count', 'desc')
            ->first();

        return $busiest ? [
            'day' => $busiest->day,
            'scans' => $busiest->count
        ] : null;
    }

    /**
     * Get top performing unit
     */
    private function getTopPerformingUnit()
    {
        $topUnit = Unit::where('is_active', true)
            ->withCount(['mealTransactions as total_scans' => function($query) {
                $query->whereDate('meal_date', now()->format('Y-m-d'));
            }])
            ->having('total_scans', '>', 0)
            ->orderBy('total_scans', 'desc')
            ->first();

        return $topUnit ? [
            'name' => $topUnit->name,
            'scans' => $topUnit->total_scans
        ] : null;
    }

    /**
     * Get top performing vendor
     */
    private function getTopPerformingVendor()
    {
        $topVendor = User::where('role', 2)
            ->withCount(['mealTransactions as total_scans' => function($query) {
                $query->whereDate('meal_date', now()->format('Y-m-d'));
            }])
            ->withSum(['mealTransactions as total_revenue' => function($query) {
                $query->whereDate('meal_date', now()->format('Y-m-d'));
            }], 'amount')
            ->having('total_scans', '>', 0)
            ->orderBy('total_scans', 'desc')
            ->first();

        return $topVendor ? [
            'name' => $topVendor->name,
            'scans' => $topVendor->total_scans,
            'revenue' => $topVendor->total_revenue
        ] : null;
    }

    /**
     * Get daily scans trend
     */
    private function getDailyScansTrend($days = 7)
    {
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $trendData = MealTransaction::whereBetween('meal_date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(meal_date) as date'),
                DB::raw('COUNT(*) as scans')
            )
            ->groupBy(DB::raw('DATE(meal_date)'))
            ->orderBy('date')
            ->get();

        // Fill missing dates with zero
        $period = CarbonPeriod::create($startDate, $endDate);
        $completeTrend = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $dayData = $trendData->firstWhere('date', $dateStr);

            $completeTrend[] = [
                'date' => $date->format('M d'),
                'scans' => $dayData ? $dayData->scans : 0,
                'day' => $date->format('D')
            ];
        }

        return $completeTrend;
    }

    /**
     * Get revenue trend
     */
    private function getRevenueTrend($days = 30)
    {
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $trendData = MealTransaction::whereBetween('meal_date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(meal_date) as date'),
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy(DB::raw('DATE(meal_date)'))
            ->orderBy('date')
            ->get();

        $period = CarbonPeriod::create($startDate, $endDate);
        $completeTrend = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $dayData = $trendData->firstWhere('date', $dateStr);

            $completeTrend[] = [
                'date' => $date->format('M d'),
                'revenue' => $dayData ? $dayData->revenue : 0,
                'day' => $date->format('D')
            ];
        }

        return $completeTrend;
    }

    /**
     * Get vendor performance trend
     */
    private function getVendorPerformanceTrend()
    {
        $monthStart = now()->startOfMonth()->format('Y-m-d');

        $vendors = User::where('role', 2)
            ->withCount(['mealTransactions as month_scans' => function($query) use ($monthStart) {
                $query->where('meal_date', '>=', $monthStart);
            }])
            ->withSum(['mealTransactions as month_revenue' => function($query) use ($monthStart) {
                $query->where('meal_date', '>=', $monthStart);
            }], 'amount')
            ->orderBy('month_scans', 'desc')
            ->take(5)
            ->get()
            ->map(function($vendor) {
                return [
                    'name' => $vendor->name,
                    'scans' => $vendor->month_scans,
                    'revenue' => $vendor->month_revenue,
                    'avg_transaction' => $vendor->month_scans > 0 ? round($vendor->month_revenue / $vendor->month_scans, 2) : 0
                ];
            });

        return $vendors;
    }

    /**
     * Get department participation
     */
    private function getDepartmentParticipation()
    {
        $today = now()->format('Y-m-d');

        $departments = Department::withCount(['employees as total_employees'])
            ->withCount(['employees as fed_today' => function($query) use ($today) {
                $query->whereHas('mealTransactions', function($q) use ($today) {
                    $q->whereDate('meal_date', $today);
                });
            }])
            ->having('total_employees', '>', 0)
            ->orderBy('fed_today', 'desc')
            ->take(5)
            ->get()
            ->map(function($dept) {
                return [
                    'name' => $dept->name,
                    'total_employees' => $dept->total_employees,
                    'fed_today' => $dept->fed_today,
                    'participation_rate' => $dept->total_employees > 0 ? round(($dept->fed_today / $dept->total_employees) * 100, 1) : 0
                ];
            });

        return $departments;
    }

    /**
     * Get comprehensive unit statistics
     */
    private function getComprehensiveUnitStats()
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
            $today = now()->format('Y-m-d');
            $monthStart = now()->startOfMonth()->format('Y-m-d');

            // Calculate scans
            $totalScans = $unit->mealTransactions()->count();
            $monthScans = $unit->mealTransactions()
                ->where('meal_date', '>=', $monthStart)
                ->count();
            $todayScans = $unit->mealTransactions()
                ->whereDate('meal_date', $today)
                ->count();
            $yesterdayScans = $unit->mealTransactions()
                ->whereDate('meal_date', now()->subDay()->format('Y-m-d'))
                ->count();

            // Calculate revenue
            $monthRevenue = $unit->mealTransactions()
                ->where('meal_date', '>=', $monthStart)
                ->sum('amount');
            $todayRevenue = $unit->mealTransactions()
                ->whereDate('meal_date', $today)
                ->sum('amount');

            // Calculate growth rates
            $scanGrowth = $yesterdayScans > 0
                ? round((($todayScans - $yesterdayScans) / $yesterdayScans) * 100, 1)
                : ($todayScans > 0 ? 100 : 0);

            // Calculate capacity utilization
            $capacityUtilization = null;
            if ($unit->capacity && $unit->capacity > 0) {
                $capacityUtilization = round(($unit->current_employee_count / $unit->capacity) * 100, 0);
            }

            // Calculate employee participation rate
            $participationRate = $unit->active_employees > 0
                ? round(($todayScans / $unit->active_employees) * 100, 1)
                : 0;

            // Active vendors
            $activeVendorsCount = User::where('role', 2)
                ->where('unit_id', $unit->id)
                ->whereHas('profile', function($q) {
                    $q->where('is_verified', true);
                })
                ->count();

            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'code' => $unit->code,
                'location' => $unit->location,
                'capacity' => $unit->capacity,
                'current_employee_count' => $unit->current_employee_count,
                'capacity_utilization' => $capacityUtilization,
                'total_employees' => $unit->total_employees,
                'active_employees' => $unit->active_employees,
                'total_scans' => $totalScans,
                'month_scans' => $monthScans,
                'today_scans' => $todayScans,
                'yesterday_scans' => $yesterdayScans,
                'scan_growth' => $scanGrowth,
                'month_revenue' => $monthRevenue,
                'today_revenue' => $todayRevenue,
                'avg_transaction_value' => $todayScans > 0 ? round($todayRevenue / $todayScans, 2) : 0,
                'active_vendors' => $activeVendorsCount,
                'participation_rate' => $participationRate,
                'efficiency_score' => $this->calculateUnitEfficiencyScore($unit, $todayScans, $todayRevenue)
            ];
        })->sortByDesc('today_scans')->values();
    }

    /**
     * Calculate unit efficiency score
     */
    private function calculateUnitEfficiencyScore($unit, $todayScans, $todayRevenue)
    {
        $score = 0;

        // Capacity utilization (max 30 points)
        if ($unit->capacity && $unit->capacity > 0) {
            $utilization = ($unit->current_employee_count / $unit->capacity) * 100;
            $score += min(30, $utilization * 0.3);
        }

        // Employee participation (max 30 points)
        if ($unit->active_employees > 0) {
            $participation = ($todayScans / $unit->active_employees) * 100;
            $score += min(30, $participation * 0.3);
        }

        // Revenue per employee (max 20 points)
        if ($unit->active_employees > 0) {
            $revenuePerEmployee = $todayRevenue / $unit->active_employees;
            // Assuming KSh 70 per meal, adjust scaling as needed
            $score += min(20, ($revenuePerEmployee / 70) * 20);
        }

        // Vendor density (max 20 points)
        $activeVendors = User::where('role', 2)
            ->where('unit_id', $unit->id)
            ->whereHas('profile', function($q) {
                $q->where('is_verified', true);
            })->count();

        if ($unit->active_employees > 0) {
            $vendorRatio = $activeVendors / $unit->active_employees;
            $score += min(20, $vendorRatio * 100);
        }

        return round(min(100, $score), 1);
    }

    /**
     * Get system alerts
     */
    private function getSystemAlerts()
    {
        $alerts = [];

        // Check for inactive vendors
        $inactiveVendors = User::where('role', 2)
            ->whereDoesntHave('mealTransactions', function($query) {
                $query->whereDate('meal_date', '>=', now()->subDays(7));
            })
            ->count();

        if ($inactiveVendors > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$inactiveVendors} vendors have no scans in the last 7 days",
                'action' => route('admin.verifications'),
                'action_text' => 'Review Vendors'
            ];
        }

        // Check for low employee participation
        $participationRate = $this->calculateEmployeeParticipationRate();
        if ($participationRate < 30) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "Low employee participation rate: {$participationRate}%",
                'action' => route('admin.analytics'),
                'action_text' => 'View Analytics'
            ];
        }

        // Check for pending verifications
        $pending = Profile::whereHas('user', function($q) {
                $q->where('role', 2);
            })->where('is_verified', false)->count();

        if ($pending > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "{$pending} vendor verifications pending",
                'action' => route('admin.verifications'),
                'action_text' => 'Verify Now'
            ];
        }

        return $alerts;
    }

    /**
     * Get forecast data
     */
    private function getForecastData()
    {
        $today = now()->format('Y-m-d');
        $todayScans = MealTransaction::whereDate('meal_date', $today)->count();

        // Simple forecast based on last 7 days average
        $last7DaysAvg = MealTransaction::where('meal_date', '>=', now()->subDays(7))
            ->where('meal_date', '<', $today)
            ->select(DB::raw('DATE(meal_date) as date'), DB::raw('COUNT(*) as scans'))
            ->groupBy('date')
            ->get()
            ->avg('scans');

        $forecastTomorrow = round($last7DaysAvg ?? $todayScans, 0);

        // Revenue forecast
        $todayRevenue = MealTransaction::whereDate('meal_date', $today)->sum('amount');
        $last7DaysRevenueAvg = MealTransaction::where('meal_date', '>=', now()->subDays(7))
            ->where('meal_date', '<', $today)
            ->select(DB::raw('DATE(meal_date) as date'), DB::raw('SUM(amount) as revenue'))
            ->groupBy('date')
            ->get()
            ->avg('revenue');

        $forecastRevenueTomorrow = round($last7DaysRevenueAvg ?? $todayRevenue, 2);

        return [
            'tomorrow_scans' => $forecastTomorrow,
            'tomorrow_revenue' => $forecastRevenueTomorrow,
            'confidence' => $last7DaysAvg ? 85 : 70,
            'trend' => $forecastTomorrow > $todayScans ? 'up' : 'down'
        ];
    }

    /**
     * Calculate vendor daily average
     */
    private function calculateVendorDailyAverage($vendorId, $startDate)
    {
        $days = now()->diffInDays(Carbon::parse($startDate)) + 1;
        $totalScans = MealTransaction::where('vendor_id', $vendorId)
            ->where('meal_date', '>=', $startDate)
            ->count();

        return $days > 0 ? round($totalScans / $days, 1) : 0;
    }

    /**
     * Calculate vendor retention
     */
    private function calculateVendorRetention($vendorId)
    {
        $lastWeekStart = now()->subDays(7)->format('Y-m-d');
        $previousWeekStart = now()->subDays(14)->format('Y-m-d');
        $previousWeekEnd = now()->subDays(8)->format('Y-m-d');

        // Customers last week
        $customersLastWeek = MealTransaction::where('vendor_id', $vendorId)
            ->whereBetween('meal_date', [$lastWeekStart, now()->format('Y-m-d')])
            ->distinct('employee_id')
            ->count('employee_id');

        // Customers previous week
        $customersPreviousWeek = MealTransaction::where('vendor_id', $vendorId)
            ->whereBetween('meal_date', [$previousWeekStart, $previousWeekEnd])
            ->distinct('employee_id')
            ->count('employee_id');

        // Returning customers
        $returningCustomers = MealTransaction::where('vendor_id', $vendorId)
            ->whereBetween('meal_date', [$previousWeekStart, $previousWeekEnd])
            ->whereIn('employee_id', function($query) use ($vendorId, $lastWeekStart) {
                $query->select('employee_id')
                    ->from('meal_transactions')
                    ->where('vendor_id', $vendorId)
                    ->whereBetween('meal_date', [$lastWeekStart, now()->format('Y-m-d')])
                    ->distinct();
            })
            ->distinct('employee_id')
            ->count('employee_id');

        if ($customersPreviousWeek == 0) return 0;
        return round(($returningCustomers / $customersPreviousWeek) * 100, 1);
    }

    /**
     * Get vendor peak hour (simple version - returns just hour string)
     */
    private function getVendorPeakHourSimple($vendorId)
    {
        $peak = MealTransaction::where('vendor_id', $vendorId)
            ->whereDate('meal_date', now()->format('Y-m-d'))
            ->select(
                DB::raw('HOUR(meal_time) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('HOUR(meal_time)'))
            ->orderBy('count', 'desc')
            ->first();

        return $peak ? $peak->hour . ':00' : 'N/A';
    }

    /**
     * Get analytics data for charts WITH UNIT FILTER
     */
    public function getAnalyticsData(Request $request)
    {
        try {
            $period = $request->get('period', 'month');
            $unitId = $request->get('unit_id', 'all');

            // Calculate period dates
            switch ($period) {
                case 'week':
                    $startDate = now()->subDays(7);
                    $dateFormat = "DATE_FORMAT(meal_date, '%Y-%m-%d')";
                    break;
                case 'year':
                    $startDate = now()->subMonths(12);
                    $dateFormat = "DATE_FORMAT(meal_date, '%Y-%m')";
                    break;
                default: // month
                    $startDate = now()->subDays(30);
                    $dateFormat = "DATE_FORMAT(meal_date, '%Y-%m-%d')";
            }

            // Scans over time data
            $scansQuery = MealTransaction::query();

            if ($unitId !== 'all') {
                $scansQuery->whereHas('employee', function($q) use ($unitId) {
                    $q->where('unit_id', $unitId);
                });
            }

            $scansData = $scansQuery->where('meal_transactions.created_at', '>=', $startDate)
                ->select(
                    DB::raw("$dateFormat as period"),
                    DB::raw('COUNT(*) as scans')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            // Unit performance data
            $unitPerformanceQuery = Unit::where('is_active', true)
                ->withCount(['employees as employees_count']);

            // Add scans count with date filter
            $unitPerformance = $unitPerformanceQuery->get()
                ->map(function($unit) use ($startDate, $unitId) {
                    // If specific unit filter, only include that unit
                    if ($unitId !== 'all' && $unit->id != $unitId) {
                        return null;
                    }

                    // Count scans for this unit with date filter
                    $scansCount = MealTransaction::whereHas('employee', function($q) use ($unit) {
                            $q->where('unit_id', $unit->id);
                        })
                        ->where('meal_transactions.created_at', '>=', $startDate)
                        ->count();

                    // Sum revenue for this unit with date filter
                    $revenueSum = MealTransaction::whereHas('employee', function($q) use ($unit) {
                            $q->where('unit_id', $unit->id);
                        })
                        ->where('meal_transactions.created_at', '>=', $startDate)
                        ->sum('amount');

                    return [
                        'name' => $unit->name,
                        'scans' => $scansCount,
                        'employees' => $unit->employees_count,
                        'revenue' => $revenueSum ?? 0
                    ];
                })
                ->filter() // Remove null values
                ->values();

            // Department feeding rates
            $departmentFeeding = Department::withCount(['employees as employee_count'])
                ->get()
                ->map(function($dept) use ($startDate, $unitId) {
                    // Get employees in this department
                    $employeeQuery = $dept->employees();

                    if ($unitId !== 'all') {
                        $employeeQuery->where('unit_id', $unitId);
                    }

                    $employeeCount = $employeeQuery->count();

                    if ($employeeCount === 0) {
                        return null;
                    }

                    // Count employees who were fed during the period
                    $fedQuery = $dept->employees()
                        ->whereHas('mealTransactions', function($q) use ($startDate) {
                            $q->where('meal_transactions.created_at', '>=', $startDate);
                        });

                    if ($unitId !== 'all') {
                        $fedQuery->where('unit_id', $unitId);
                    }

                    $fedCount = $fedQuery->count();

                    return [
                        'name' => $dept->name,
                        'total_employees' => $employeeCount,
                        'fed_today' => $fedCount,
                        'feeding_rate' => $employeeCount > 0 ?
                            round(($fedCount / $employeeCount) * 100, 2) : 0
                    ];
                })
                ->filter()
                ->values();

            // Vendor performance
            $vendorPerformanceQuery = User::where('role', 2);

            if ($unitId !== 'all') {
                $vendorPerformanceQuery->where('unit_id', $unitId);
            }

            $vendorPerformance = $vendorPerformanceQuery
                ->withCount(['mealTransactions as total_scans' => function($query) use ($startDate) {
                    $query->where('meal_transactions.created_at', '>=', $startDate);
                }])
                ->withSum(['mealTransactions as total_revenue' => function($query) use ($startDate) {
                    $query->where('meal_transactions.created_at', '>=', $startDate);
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

            // Employee behavior
            $employeeQuery = Employee::query();

            if ($unitId !== 'all') {
                $employeeQuery->where('unit_id', $unitId);
            }

            $frequentEaters = $employeeQuery
                ->withCount(['mealTransactions as meal_count' => function($query) use ($startDate) {
                    $query->where('meal_transactions.created_at', '>=', $startDate);
                }])
                ->having('meal_count', '>', 0)
                ->orderBy('meal_count', 'desc')
                ->take(10)
                ->get()
                ->map(function($employee) {
                    return [
                        'formal_name' => $employee->formal_name,
                        'meal_count' => $employee->meal_count
                    ];
                });

            return response()->json([
                'success' => true,
                'scans_data' => $scansData,
                'unit_performance' => $unitPerformance,
                'department_feeding' => $departmentFeeding,
                'vendor_performance' => $vendorPerformance,
                'employee_behavior' => [
                    'frequent_eaters' => $frequentEaters
                ],
                'period' => $period,
                'unit_id' => $unitId
            ]);

        } catch (\Exception $e) {
            Log::error('Analytics data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load analytics data',
                'message' => $e->getMessage()
            ], 500);
        }
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
        $unitStats = $this->getComprehensiveUnitStats();

        // Get all units for filtering
        $units = Unit::active()->get();

        // Get recent transactions for the table
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
                'scans' => []
            ];

            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $formattedDate = $date->format('M d');

                $dailyScans = MealTransaction::whereHas('employee', function($query) use ($unit) {
                        $query->where('unit_id', $unit->id);
                    })
                    ->whereDate('meal_date', $date)
                    ->count();

                $monthlyTrends['labels'][] = $formattedDate;
                $monthlyTrends['scans'][] = $dailyScans;
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
                ->withCount(['mealTransactions as scans' => function($query) use ($unit) {
                    $query->whereHas('employee', function($q) use ($unit) {
                        $q->where('unit_id', $unit->id);
                    })
                    ->where('meal_date', '>=', now()->startOfMonth());
                }])
                ->get()
                ->map(function($vendor) {
                    $lastTransaction = MealTransaction::where('vendor_id', $vendor->id)
                        ->latest()
                        ->first();

                    return [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'email' => $vendor->email,
                        'scans' => $vendor->scans,
                        'last_scan' => $lastTransaction
                            ? $lastTransaction->meal_date->format('M d, Y')
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
                'active_vendors' => User::where('role', 2)
                    ->where('unit_id', $unit->id)
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
                'error' => 'Failed to load unit analytics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive vendor analytics
     */
    public function getVendorAnalytics(Request $request, $vendorId)
    {
        try {
            Log::info('=== VENDOR ANALYTICS REQUEST START ===');
            Log::info('Vendor ID:', ['id' => $vendorId]);
            Log::info('Request Data:', $request->all());

            $vendor = User::with(['profile', 'unit'])->findOrFail($vendorId);

            Log::info('Vendor Found:', [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'email' => $vendor->email,
                'has_meal_transactions' => $vendor->mealTransactions()->exists()
            ]);

            // Get filter parameters
            $period = $request->get('period', 'today');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            // Log date ranges
            Log::info('Period Configuration:', [
                'period' => $period,
                'start_date_param' => $startDate,
                'end_date_param' => $endDate
            ]);

            // Set date ranges
            switch ($period) {
                case 'week':
                    $dateStart = now()->startOfWeek();
                    $dateEnd = now()->endOfWeek();
                    break;
                case 'month':
                    $dateStart = now()->startOfMonth();
                    $dateEnd = now()->endOfMonth();
                    break;
                case 'custom':
                    $dateStart = Carbon::parse($startDate)->startOfDay();
                    $dateEnd = Carbon::parse($endDate)->endOfDay();
                    break;
                default: // today
                    $dateStart = today();
                    $dateEnd = today()->endOfDay();
            }

            Log::info('Date Range Calculated:', [
                'dateStart' => $dateStart,
                'dateEnd' => $dateEnd,
                'days_diff' => $dateStart->diffInDays($dateEnd)
            ]);

            // Get total scans in this period for debugging
            $totalPeriodScans = $vendor->mealTransactions()
                ->whereBetween('meal_date', [$dateStart, $dateEnd])
                ->count();

            Log::info('Total Scans in Period:', ['count' => $totalPeriodScans]);

            // Calculate vendor statistics
            $stats = [
                'current_period_scans' => $totalPeriodScans,
                'current_period_revenue' => $vendor->mealTransactions()
                    ->whereBetween('meal_date', [$dateStart, $dateEnd])
                    ->sum('amount'),
                'previous_period_scans' => $this->getPreviousPeriodScans($vendor, $period),
                'previous_period_revenue' => $this->getPreviousPeriodRevenue($vendor, $period),
                'avg_transaction_value' => $totalPeriodScans > 0 ?
                    round($vendor->mealTransactions()
                        ->whereBetween('meal_date', [$dateStart, $dateEnd])
                        ->avg('amount'), 2) : 0,
                'peak_hour' => $this->getVendorPeakHourDetailed($vendor, $dateStart, $dateEnd),
                'top_departments' => $this->getVendorTopDepartments($vendor, $dateStart, $dateEnd),
                'top_customers' => $this->getVendorTopCustomers($vendor, $dateStart, $dateEnd),
            ];

            // Get daily scans data
            $dailyScans = $this->getVendorDailyScans($vendor, $dateStart, $dateEnd);

            // Get weekly activity
            $weeklyActivity = $this->getWeeklyActivity($vendor, $dateStart, $dateEnd);

            // Get time series data
            $timeSeriesData = $this->getVendorTimeSeriesData($vendor, $dateStart, $dateEnd);

            // Get department distribution
            $departmentDistribution = $this->getDepartmentDistribution($vendor, $dateStart, $dateEnd);

            // Get hourly pattern
            $hourlyPattern = $this->getHourlyPattern($vendor, $dateStart, $dateEnd);

            // Get employee frequency
            $employeeFrequency = $this->getEmployeeFrequency($vendor, $dateStart, $dateEnd);

            // Get revenue trends
            $revenueTrends = $this->getRevenueTrends($vendor, $dateStart, $dateEnd);

            return response()->json([
                'success' => true,
                'debug' => [
                    'vendor_id' => $vendorId,
                    'period' => $period,
                    'date_range' => [
                        'start' => $dateStart->format('Y-m-d H:i:s'),
                        'end' => $dateEnd->format('Y-m-d H:i:s')
                    ],
                    'total_scans_in_period' => $totalPeriodScans
                ],
                'vendor' => [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'email' => $vendor->email,
                    'phone' => $vendor->profile->phone ?? 'N/A',
                    'location' => $vendor->unit->name ?? 'N/A',
                    'verified_at' => $vendor->profile->verified_at ?? null,
                ],
                'stats' => $stats,
                'daily_scans' => $dailyScans,
                'weekly_activity' => $weeklyActivity,
                'time_series_data' => $timeSeriesData,
                'department_distribution' => $departmentDistribution,
                'hourly_pattern' => $hourlyPattern,
                'employee_frequency' => $employeeFrequency,
                'revenue_trends' => $revenueTrends,
                'period' => $period,
                'date_range' => [
                    'start' => $dateStart->format('Y-m-d'),
                    'end' => $dateEnd->format('Y-m-d'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Vendor analytics error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load vendor analytics',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Get previous period scans for comparison
     */
    private function getPreviousPeriodScans($vendor, $period)
    {
        switch ($period) {
            case 'week':
                $startDate = now()->subWeek()->startOfWeek();
                $endDate = now()->subWeek()->endOfWeek();
                break;
            case 'month':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                break;
            default: // today
                $startDate = now()->subDay()->startOfDay();
                $endDate = now()->subDay()->endOfDay();
        }

        return $vendor->mealTransactions()
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get previous period revenue for comparison
     */
    private function getPreviousPeriodRevenue($vendor, $period)
    {
        switch ($period) {
            case 'week':
                $startDate = now()->subWeek()->startOfWeek();
                $endDate = now()->subWeek()->endOfWeek();
                break;
            case 'month':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                break;
            default: // today
                $startDate = now()->subDay()->startOfDay();
                $endDate = now()->subDay()->endOfDay();
        }

        return $vendor->mealTransactions()
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Get vendor peak hour with details (for analytics)
     */
    private function getVendorPeakHourDetailed($vendor, $startDate, $endDate)
    {
        $peak = MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->select(
                DB::raw('HOUR(meal_time) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('HOUR(meal_time)'))
            ->orderBy('count', 'desc')
            ->first();

        return $peak ? [
            'hour' => $peak->hour . ':00',
            'count' => $peak->count
        ] : null;
    }

    /**
     * Get vendor top departments
     */
    private function getVendorTopDepartments($vendor, $startDate, $endDate)
    {
        return MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->join('employees', 'meal_transactions.employee_id', '=', 'employees.id')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->select(
                'departments.name as department',
                DB::raw('COUNT(*) as scans'),
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('scans', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get vendor top customers
     */
    private function getVendorTopCustomers($vendor, $startDate, $endDate)
    {
        return MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->with('employee')
            ->select(
                'employee_id',
                DB::raw('COUNT(*) as visits'),
                DB::raw('SUM(amount) as total_spent'),
                DB::raw('AVG(amount) as avg_spent')
            )
            ->groupBy('employee_id')
            ->orderBy('visits', 'desc')
            ->limit(10)
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => $transaction->employee_id,
                    'formal_name' => $transaction->employee->formal_name ?? 'Unknown',
                    'employee_number' => $transaction->employee->employee_number ?? 'N/A',
                    'visits' => $transaction->visits,
                    'total_spent' => $transaction->total_spent,
                    'avg_spent' => $transaction->avg_spent,
                ];
            });
    }

    /**
     * Get vendor daily scans
     */
    private function getVendorDailyScans($vendor, $startDate, $endDate)
    {
        $scans = MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->with(['employee.department', 'employee.unit'])
            ->orderBy('meal_date', 'desc')
            ->orderBy('meal_time', 'desc')
            ->get()
            ->groupBy(function($transaction) {
                return $transaction->meal_date->format('Y-m-d');
            });

        return $scans;
    }

    /**
     * Get weekly activity
     */
    private function getWeeklyActivity($vendor, $startDate, $endDate)
    {
        $activity = [];
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        // Get all transactions in the period
        $transactions = $vendor->mealTransactions()
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->get();

        // Group by day of week
        $grouped = $transactions->groupBy(function($transaction) {
            return Carbon::parse($transaction->meal_date)->dayName;
        });

        foreach ($daysOfWeek as $day) {
            $dayTransactions = $grouped->get($day, collect());

            $scans = $dayTransactions->count();
            $revenue = $dayTransactions->sum('amount');

            $activity[] = [
                'day' => $day,
                'scans' => $scans,
                'revenue' => $revenue,
                'avg_per_scan' => $scans > 0 ? round($revenue / $scans, 2) : 0
            ];
        }

        return $activity;
    }

    /**
     * Get vendor time series data
     */
    private function getVendorTimeSeriesData($vendor, $startDate, $endDate)
    {
        $diffInDays = $startDate->diffInDays($endDate);

        if ($diffInDays <= 31) {
            // Daily data
            $data = MealTransaction::where('vendor_id', $vendor->id)
                ->whereBetween('meal_date', [$startDate, $endDate])
                ->select(
                    DB::raw("DATE(meal_date) as date"),
                    DB::raw('COUNT(*) as scans'),
                    DB::raw('SUM(amount) as revenue'),
                    DB::raw('AVG(amount) as avg_amount')
                )
                ->groupBy(DB::raw("DATE(meal_date)"))
                ->orderBy('date')
                ->get();
        } else {
            // Weekly data
            $data = MealTransaction::where('vendor_id', $vendor->id)
                ->whereBetween('meal_date', [$startDate, $endDate])
                ->select(
                    DB::raw("YEARWEEK(meal_date) as week"),
                    DB::raw('COUNT(*) as scans'),
                    DB::raw('SUM(amount) as revenue'),
                    DB::raw('AVG(amount) as avg_amount')
                )
                ->groupBy(DB::raw("YEARWEEK(meal_date)"))
                ->orderBy('week')
                ->get();
        }

        return $data;
    }

    /**
     * Get department distribution
     */
    private function getDepartmentDistribution($vendor, $startDate, $endDate)
    {
        $totalScans = MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->count();

        if ($totalScans === 0) {
            return collect([]);
        }

        return MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->join('employees', 'meal_transactions.employee_id', '=', 'employees.id')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->select(
                'departments.name as department',
                DB::raw('COUNT(*) as count'),
                DB::raw('COUNT(*) * 100.0 / ' . $totalScans . ' as percentage')
            )
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Get hourly pattern
     */
    private function getHourlyPattern($vendor, $startDate, $endDate)
    {
        return MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->select(
                DB::raw('HOUR(meal_time) as hour'),
                DB::raw('COUNT(*) as scans'),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('AVG(amount) as avg_amount')
            )
            ->groupBy(DB::raw('HOUR(meal_time)'))
            ->orderBy('hour')
            ->get();
    }

    /**
     * Get employee frequency
     */
    private function getEmployeeFrequency($vendor, $startDate, $endDate)
    {
        // First, get the distinct days visited for each employee
        $employeeDays = MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->select(
                'employee_id',
                DB::raw('COUNT(DISTINCT DATE(meal_date)) as days_visited')
            )
            ->groupBy('employee_id')
            ->get();

        // Then, group by the days_visited count
        return $employeeDays->groupBy('days_visited')
            ->map(function($group, $days) {
                return [
                    'days_visited' => (int)$days,
                    'employee_count' => $group->count()
                ];
            })
            ->sortBy('days_visited')
            ->values();
    }

    /**
     * Get revenue trends
     */
    private function getRevenueTrends($vendor, $startDate, $endDate)
    {
        return MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->select(
                DB::raw("DATE_FORMAT(meal_date, '%Y-%m') as month"),
                DB::raw('SUM(amount) as total_revenue'),
                DB::raw('COUNT(*) as total_scans'),
                DB::raw('AVG(amount) as avg_transaction_value')
            )
            ->groupBy(DB::raw("DATE_FORMAT(meal_date, '%Y-%m')"))
            ->orderBy('month')
            ->get();
    }

    /**
     * Export vendor analytics
     */
  public function exportVendorAnalytics(Request $request, $vendorId)
{
    try {
        $vendor = User::findOrFail($vendorId);
        $format = $request->get('format', 'excel');
        $period = $request->get('period', 'month');

        // Get analytics data
        $analyticsData = $this->getVendorAnalyticsData($vendor, $period);

        if ($format === 'pdf') {
            return $this->exportVendorAnalyticsPDF($vendor, $analyticsData);
        }

        return $this->exportVendorAnalyticsExcel($vendor, $analyticsData);

    } catch (\Exception $e) {
        Log::error('Export vendor analytics error: ' . $e->getMessage());

        // Return JSON error for AJAX requests
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to export analytics: ' . $e->getMessage()
            ], 500);
        }

        return back()->with('error', 'Failed to export analytics: ' . $e->getMessage());
    }
}
    /**
     * Get vendor analytics data for export
     */
    private function getVendorAnalyticsData($vendor, $period)
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        if ($period === 'week') {
            $startDate = now()->startOfWeek();
            $endDate = now()->endOfWeek();
        } elseif ($period === 'custom') {
            $startDate = Carbon::parse(request('start_date'))->startOfDay();
            $endDate = Carbon::parse(request('end_date'))->endOfDay();
        }

        return [
            'vendor' => $vendor,
            'transactions' => MealTransaction::with(['employee.department', 'employee.unit'])
                ->where('vendor_id', $vendor->id)
                ->whereBetween('meal_date', [$startDate, $endDate])
                ->get(),
            'summary' => [
                'total_scans' => MealTransaction::where('vendor_id', $vendor->id)
                    ->whereBetween('meal_date', [$startDate, $endDate])
                    ->count(),
                'total_revenue' => MealTransaction::where('vendor_id', $vendor->id)
                    ->whereBetween('meal_date', [$startDate, $endDate])
                    ->sum('amount'),
                'avg_daily_scans' => MealTransaction::where('vendor_id', $vendor->id)
                    ->whereBetween('meal_date', [$startDate, $endDate])
                    ->groupBy('meal_date')
                    ->select(DB::raw('COUNT(*) as daily_count'))
                    ->get()
                    ->avg('daily_count') ?? 0,
                'top_department' => MealTransaction::where('vendor_id', $vendor->id)
                    ->whereBetween('meal_date', [$startDate, $endDate])
                    ->join('employees', 'meal_transactions.employee_id', '=', 'employees.id')
                    ->join('departments', 'employees.department_id', '=', 'departments.id')
                    ->select('departments.name', DB::raw('COUNT(*) as count'))
                    ->groupBy('departments.id', 'departments.name')
                    ->orderBy('count', 'desc')
                    ->first(),
            ],
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ];
    }

    /**
     * Export vendor analytics to PDF
     */


    /**
     * Export vendor analytics to Excel
     */
   private function exportVendorAnalyticsExcel($vendor, $analyticsData)
{
    try {
        // Generate filename
        $filename = 'vendor-analytics-' . $vendor->id . '-' . now()->format('Y-m-d') . '.csv';

        // Create CSV content
        $csvData = [];

        // Add header
        $csvData[] = ['Vendor Analytics Report - ' . $vendor->name];
        $csvData[] = ['Generated on: ' . now()->format('Y-m-d H:i:s')];
        $csvData[] = []; // Empty row

        // Summary section
        $csvData[] = ['SUMMARY'];
        $csvData[] = ['Metric', 'Value'];
        $csvData[] = ['Total Scans', $analyticsData['summary']['total_scans'] ?? 0];
        $csvData[] = ['Total Revenue', 'KSh ' . number_format($analyticsData['summary']['total_revenue'] ?? 0, 2)];
        $csvData[] = ['Average Daily Scans', $analyticsData['summary']['avg_daily_scans'] ?? 0];
        $csvData[] = ['Top Department', $analyticsData['summary']['top_department']->name ?? 'N/A'];
        $csvData[] = []; // Empty row

        // Transactions section
        $csvData[] = ['RECENT TRANSACTIONS'];
        $csvData[] = ['Date', 'Employee', 'Department', 'Amount', 'Time'];

        foreach ($analyticsData['transactions'] as $transaction) {
            $csvData[] = [
                $transaction->meal_date,
                $transaction->employee->formal_name ?? 'Unknown',
                $transaction->employee->department->name ?? 'N/A',
                'KSh ' . number_format($transaction->amount, 2),
                $transaction->meal_time
            ];
        }

        // Create CSV string
        $output = '';
        foreach ($csvData as $row) {
            $output .= $this->formatCsvRow($row) . "\n";
        }

        // Return file download response
        return response($output, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);

    } catch (\Exception $e) {
        Log::error('Excel export error: ' . $e->getMessage());
        throw $e;
    }
}
private function formatCsvRow($row)
{
    if (is_array($row)) {
        $formatted = array_map(function($cell) {
            // Escape quotes and wrap in quotes if contains comma, newline, or quote
            if (strpos($cell, ',') !== false ||
                strpos($cell, "\n") !== false ||
                strpos($cell, '"') !== false) {
                return '"' . str_replace('"', '""', $cell) . '"';
            }
            return $cell;
        }, $row);
        return implode(',', $formatted);
    }
    return $row;
}
    /**
     * Share vendor analytics via email
     */
    public function shareVendorAnalytics(Request $request, $vendorId)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'subject' => 'required|string|max:255',
                'message' => 'nullable|string',
                'include_attachment' => 'boolean',
                'format' => 'required|in:summary,detailed,excel,pdf'
            ]);

            $vendor = User::findOrFail($vendorId);
            $period = $request->get('period', 'month');

            // Get analytics data
            $analyticsData = $this->getVendorAnalyticsData($vendor, $period);

            // Prepare email data
            $emailData = [
                'vendor' => $vendor,
                'summary' => $analyticsData['summary'],
                'date_range' => $analyticsData['date_range'],
                'custom_message' => $request->message,
                'period' => $period
            ];

            // Send email
            // Note: You need to implement the email sending logic
            // For now, we'll return a success response
            return response()->json([
                'success' => true,
                'message' => 'Email sharing feature coming soon',
                'email_data' => $emailData
            ]);

        } catch (\Exception $e) {
            Log::error('Share vendor analytics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to share analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vendor details
     */
    public function getVendorDetails($vendorId)
    {
        try {
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
                ->with(['employee.department', 'employee.unit'])
                ->latest()
                ->take(10)
                ->get();

            // Top employees served
            $topEmployees = $vendor->mealTransactions()
                ->with('employee.department')
                ->select(
                    'employee_id',
                    DB::raw('COUNT(*) as visit_count')
                )
                ->groupBy('employee_id')
                ->orderBy('visit_count', 'desc')
                ->take(5)
                ->get()
                ->map(function($transaction) {
                    return [
                        'id' => $transaction->employee_id,
                        'formal_name' => $transaction->employee->formal_name ?? 'Unknown',
                        'department' => $transaction->employee->department->name ?? 'N/A',
                        'visit_count' => $transaction->visit_count
                    ];
                });

            return response()->json([
                'success' => true,
                'vendor' => $vendor,
                'stats' => $stats,
                'recent_transactions' => $recentTransactions,
                'top_employees' => $topEmployees
            ]);

        } catch (\Exception $e) {
            Log::error('Vendor details error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load vendor details'
            ], 500);
        }
    }

    /**
     * Get real-time dashboard stats (for auto-refresh)
     */
    public function getDashboardStats()
    {
        try {
            $today = now()->format('Y-m-d');
            $yesterday = now()->subDay()->format('Y-m-d');

            $stats = [
                'today_scans' => MealTransaction::whereDate('meal_date', $today)->count(),
                'total_revenue_today' => MealTransaction::whereDate('meal_date', $today)->sum('amount'),
                'employee_participation_rate' => $this->calculateEmployeeParticipationRate(),
                'active_employees' => Employee::where('is_active', true)->count(),
                'verified_vendors' => Profile::whereHas('user', function($q) {
                    $q->where('role', 2);
                })->where('is_verified', true)->count(),
                'pending_verifications' => Profile::whereHas('user', function($q) {
                    $q->where('role', 2);
                })->where('is_verified', false)->count(),
            ];

            // Get recent alerts
            $alerts = $this->getSystemAlerts();

            // Get recent transactions count
            $recentTransactionsCount = MealTransaction::whereDate('meal_date', $today)
                ->where('meal_time', '>=', now()->subHours(1))
                ->count();

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'alerts' => $alerts,
                'recent_activity' => [
                    'last_hour_scans' => $recentTransactionsCount,
                    'last_update' => now()->format('h:i A')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard stats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load dashboard stats'
            ], 500);
        }
    }

    /**
     * Get quick actions for dashboard
     */
    public function getQuickActions()
    {
        $actions = [
            [
                'title' => 'Upload Employees',
                'description' => 'CSV/Excel import',
                'icon' => 'fas fa-upload',
                'color' => 'primary-red',
                'route' => route('admin.employees.import'),
                'count' => 0
            ],
            [
                'title' => 'Verify Vendors',
                'description' => 'Pending verifications',
                'icon' => 'fas fa-user-check',
                'color' => 'yellow-500',
                'route' => route('admin.verifications'),
                'count' => Profile::whereHas('user', function($q) {
                    $q->where('role', 2);
                })->where('is_verified', false)->count()
            ],
            [
                'title' => 'View QR Codes',
                'description' => 'Employee QR codes',
                'icon' => 'fas fa-qrcode',
                'color' => 'secondary-blue',
                'route' => route('admin.employees.qr-codes'),
                'count' => 0
            ],
            [
                'title' => 'View Analytics',
                'description' => 'Reports & Insights',
                'icon' => 'fas fa-chart-bar',
                'color' => 'green-500',
                'route' => route('admin.analytics'),
                'count' => 0
            ]
        ];

        return response()->json([
            'success' => true,
            'actions' => $actions
        ]);
    }

    /**
     * Get performance comparison data
     */
    public function getPerformanceComparison($period = 'week')
    {
        try {
            $startDate = null;
            $endDate = now();

            switch ($period) {
                case 'week':
                    $startDate = now()->subWeek();
                    break;
                case 'month':
                    $startDate = now()->subMonth();
                    break;
                case 'quarter':
                    $startDate = now()->subMonths(3);
                    break;
                default:
                    $startDate = now()->subWeek();
            }

            $currentPeriodData = $this->getPeriodData($startDate, $endDate);
            $previousPeriodData = $this->getPeriodData(
                $startDate->copy()->sub($period === 'week' ? 1 : ($period === 'month' ? 1 : 3), $period === 'week' ? 'week' : 'month'),
                $startDate
            );

            return response()->json([
                'success' => true,
                'current_period' => $currentPeriodData,
                'previous_period' => $previousPeriodData,
                'growth_rates' => [
                    'scans' => $this->calculateGrowthRate($previousPeriodData['total_scans'], $currentPeriodData['total_scans']),
                    'revenue' => $this->calculateGrowthRate($previousPeriodData['total_revenue'], $currentPeriodData['total_revenue']),
                    'employees' => $this->calculateGrowthRate($previousPeriodData['total_employees'], $currentPeriodData['total_employees']),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Performance comparison error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load performance comparison'
            ], 500);
        }
    }

    /**
     * Get period data for comparison
     */
    private function getPeriodData($startDate, $endDate)
    {
        return [
            'total_scans' => MealTransaction::whereBetween('meal_date', [$startDate, $endDate])->count(),
            'total_revenue' => MealTransaction::whereBetween('meal_date', [$startDate, $endDate])->sum('amount'),
            'total_employees' => Employee::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_vendors' => User::where('role', 2)->whereBetween('created_at', [$startDate, $endDate])->count(),
            'avg_daily_scans' => MealTransaction::whereBetween('meal_date', [$startDate, $endDate])
                ->groupBy('meal_date')
                ->select(DB::raw('COUNT(*) as daily_count'))
                ->get()
                ->avg('daily_count') ?? 0,
            'peak_day' => $this->getPeakDay($startDate, $endDate),
        ];
    }

    /**
     * Get peak day in a period
     */
    private function getPeakDay($startDate, $endDate)
    {
        $peak = MealTransaction::whereBetween('meal_date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(meal_date) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('count', 'desc')
            ->first();

        return $peak ? [
            'date' => $peak->date,
            'scans' => $peak->count
        ] : null;
    }

    /**
     * Get system health status
     */
    public function getSystemHealth()
    {
        try {
            $health = [
                'database' => $this->checkDatabaseHealth(),
                'scan_system' => $this->checkScanSystemHealth(),
                'vendor_system' => $this->checkVendorSystemHealth(),
                'employee_system' => $this->checkEmployeeSystemHealth(),
                'overall_score' => 0
            ];

            // Calculate overall score
            $scores = array_values(array_filter(array_map(function($check) {
                return $check['score'] ?? 0;
            }, $health)));

            if (count($scores) > 0) {
                $health['overall_score'] = round(array_sum($scores) / count($scores), 1);
            }

            return response()->json([
                'success' => true,
                'health' => $health,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error('System health check error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to check system health'
            ], 500);
        }
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth()
    {
        try {
            // Simple database check
            DB::connection()->getPdo();

            // Check table counts
            $employeeCount = Employee::count();
            $vendorCount = User::where('role', 2)->count();
            $transactionCount = MealTransaction::count();

            $score = 100; // Base score
            if ($employeeCount === 0) $score -= 20;
            if ($vendorCount === 0) $score -= 20;
            if ($transactionCount === 0) $score -= 10;

            return [
                'status' => 'healthy',
                'score' => max(0, $score),
                'checks' => [
                    'connection' => true,
                    'tables' => [
                        'employees' => $employeeCount,
                        'vendors' => $vendorCount,
                        'transactions' => $transactionCount
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'score' => 0,
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check scan system health
     */
    private function checkScanSystemHealth()
    {
        $todayScans = MealTransaction::whereDate('meal_date', today())->count();
        $yesterdayScans = MealTransaction::whereDate('meal_date', now()->subDay())->count();

        $score = 70; // Base score
        if ($todayScans > 0) $score += 20;
        if ($todayScans > $yesterdayScans) $score += 10;

        return [
            'status' => $todayScans > 0 ? 'active' : 'inactive',
            'score' => min(100, $score),
            'metrics' => [
                'today_scans' => $todayScans,
                'yesterday_scans' => $yesterdayScans,
                'growth' => $yesterdayScans > 0 ? round((($todayScans - $yesterdayScans) / $yesterdayScans) * 100, 1) : ($todayScans > 0 ? 100 : 0)
            ]
        ];
    }

    /**
     * Check vendor system health
     */
    private function checkVendorSystemHealth()
    {
        $totalVendors = User::where('role', 2)->count();
        $activeVendors = User::where('role', 2)
            ->whereHas('mealTransactions', function($query) {
                $query->whereDate('meal_date', '>=', now()->subDays(7));
            })
            ->count();

        $score = 60; // Base score
        if ($totalVendors > 0) $score += 20;
        if ($activeVendors > 0) $score += 20;

        return [
            'status' => $totalVendors > 0 ? 'operational' : 'setup_required',
            'score' => min(100, $score),
            'metrics' => [
                'total_vendors' => $totalVendors,
                'active_vendors' => $activeVendors,
                'activity_rate' => $totalVendors > 0 ? round(($activeVendors / $totalVendors) * 100, 1) : 0
            ]
        ];
    }
/**
 * Export vendor analytics
 */


/**
 * Export vendor analytics to PDF (working version)
 */
private function exportVendorAnalyticsPDF($vendor, $analyticsData)
{
    try {
        // Check if DomPDF is available
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new \Exception('PDF generation library not installed. Run: composer require barryvdh/laravel-dompdf');
        }

        // Create HTML content for PDF
        $html = view('reeds.admin.exports.vendor-pdf', [
            'vendor' => $vendor,
            'data' => $analyticsData,
            'date' => now()->format('Y-m-d H:i:s')
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');

        // Generate filename
        $filename = 'vendor-analytics-' . $vendor->id . '-' . now()->format('Y-m-d') . '.pdf';

        // Return download response
        return $pdf->download($filename);

    } catch (\Exception $e) {
        Log::error('PDF export error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Export vendor analytics to Excel (working version)
 */

    /**
     * Check employee system health
     */
    private function checkEmployeeSystemHealth()
    {
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('is_active', true)->count();
        $participationRate = $this->calculateEmployeeParticipationRate();

        $score = 50; // Base score
        if ($totalEmployees > 0) $score += 20;
        if ($activeEmployees > 0) $score += 20;
        if ($participationRate > 30) $score += 10;

        return [
            'status' => $totalEmployees > 0 ? 'active' : 'setup_required',
            'score' => min(100, $score),
            'metrics' => [
                'total_employees' => $totalEmployees,
                'active_employees' => $activeEmployees,
                'participation_rate' => $participationRate
            ]
        ];
    }
}
