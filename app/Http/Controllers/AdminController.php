<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\MealTransaction;
use App\Models\Department;
use App\Models\VendorInvoice;
use App\Models\SubDepartment;
use App\Models\Profile;
use App\Models\Reward;
use App\Models\Vendor;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Unit;
use Illuminate\Validation\Rule;
use App\Services\AdvantaSMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        $stats = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('is_active', true)->count(),
            'inactive_employees' => Employee::where('is_active', false)->count(),
            'new_employees_this_month' => Employee::where('created_at', '>=', $monthStart)->count(),
            'employee_growth_rate' => $this->calculateGrowthRate(
                Employee::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count(),
                Employee::where('created_at', '>=', $monthStart)->count()
            ),
            'total_vendors' => User::where('role', 2)->count(),
            'verified_vendors' => Profile::whereHas('user', function($q) {
                $q->where('role', 2);
            })->where('is_verified', true)->count(),
            'pending_verifications' => Profile::whereHas('user', function($q) {
                $q->where('role', 2);
            })->where('is_verified', false)->count(),
            'vendor_verification_rate' => $this->calculateVerificationRate(),
            'today_scans' => MealTransaction::whereDate('meal_date', $today)->count(),
            'yesterday_scans' => MealTransaction::whereDate('meal_date', $yesterday)->count(),
            'week_scans' => MealTransaction::whereDate('meal_date', '>=', $weekStart)->count(),
            'month_scans' => MealTransaction::whereDate('meal_date', '>=', $monthStart)->count(),
            'scan_growth_rate' => $this->calculateScanGrowthRate(),
            'total_revenue_today' => MealTransaction::whereDate('meal_date', $today)->sum('amount'),
            'total_revenue_yesterday' => MealTransaction::whereDate('meal_date', $yesterday)->sum('amount'),
            'total_revenue_week' => MealTransaction::whereDate('meal_date', '>=', $weekStart)->sum('amount'),
            'total_revenue_month' => MealTransaction::whereDate('meal_date', '>=', $monthStart)->sum('amount'),
            'avg_daily_revenue' => $this->calculateAverageDailyRevenue(),
            'revenue_growth_rate' => $this->calculateRevenueGrowthRate(),
            'employee_participation_rate' => $this->calculateEmployeeParticipationRate(),
            'avg_scans_per_vendor' => $this->calculateAverageScansPerVendor(),
            'peak_hour' => $this->getPeakHour(),
            'busiest_day' => $this->getBusiestDay(),
            'top_performing_unit' => $this->getTopPerformingUnit(),
            'top_performing_vendor' => $this->getTopPerformingVendor(),
        ];

        $trendData = [
            'daily_scans_7d' => $this->getDailyScansTrend(7),
            'daily_scans_30d' => $this->getDailyScansTrend(30),
            'revenue_trend_30d' => $this->getRevenueTrend(30),
            'vendor_performance_trend' => $this->getVendorPerformanceTrend(),
            'department_participation' => $this->getDepartmentParticipation(),
        ];

        $recentTransactions = MealTransaction::with([
                'employee.department',
                'employee.unit',
                'vendor'
            ])
            ->whereHas('employee')
            ->whereHas('vendor')
            ->latest()
            ->take(10)
            ->get()
            ->map(function($transaction) {
                // Add reward detection from scan_data
                $scanData = $transaction->scan_data;
                $transaction->is_reward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                return $transaction;
            });

        $topVendors = User::where('role', 2)
    ->with(['mealTransactions' => function($query) use ($monthStart) {
        $query->where('meal_date', '>=', $monthStart);
    }])
    ->with(['profile'])
    ->get()
    ->map(function($vendor) use ($monthStart) {
        $transactions = $vendor->mealTransactions;
        $totalScans = $transactions->count();

        // Calculate regular and reward scans
        $regularScans = 0;
        $rewardScans = 0;
        $totalRevenue = 0;

        foreach ($transactions as $meal) {
            $scanData = $meal->scan_data;
            $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;

            if ($isReward) {
                $rewardScans++;
                $totalRevenue += 200.00;
            } else {
                $regularScans++;
                $totalRevenue += 65.00;
            }
        }

        $vendor->total_scans = $totalScans;
        $vendor->regular_scans = $regularScans;
        $vendor->reward_scans = $rewardScans;
        $vendor->total_revenue = $totalRevenue;
        $vendor->avg_daily_scans = $this->calculateVendorDailyAverage($vendor->id, $monthStart);
        $vendor->customer_retention = $this->calculateVendorRetention($vendor->id);
        $vendor->peak_performance_hour = $this->getVendorPeakHourSimple($vendor->id);
        return $vendor;
    })
    ->sortByDesc('total_scans')
    ->take(8);

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

        $unitStats = $this->getComprehensiveUnitStats();
        $alerts = $this->getSystemAlerts();
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
     * Get 30-day trends data for AJAX requests
     */
    public function get30DayTrends(Request $request)
    {
        try {
            $trendData = $this->getDailyScansTrend(30);

            return response()->json([
                'success' => true,
                'trend_data' => $trendData
            ]);
        } catch (\Exception $e) {
            Log::error('30-day trends error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load 30-day trends'
            ], 500);
        }
    }

    /**
     * Get vendor data for a specific month with correct end date
     */
    public function getVendorMonthData(Request $request, $vendorId, $year, $month)
    {
        try {
            $vendor = User::findOrFail($vendorId);

            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

            Log::info('Month data date range', [
                'year' => $year,
                'month' => $month,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'days_in_month' => $startDate->daysInMonth
            ]);

            $transactions = MealTransaction::where('vendor_id', $vendorId)
                ->whereBetween('meal_date', [$startDate, $endDate])
                ->get();

            $totalScans = $transactions->count();

            // Calculate revenue correctly (65 for regular, 200 for reward)
            $totalRevenue = $transactions->sum(function($meal) {
                $scanData = $meal->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                return $isReward ? 200.00 : 65.00;
            });

            $avgTransaction = $totalScans > 0 ? round($totalRevenue / $totalScans, 2) : 0;

            $dailyData = $transactions->groupBy(function($transaction) {
                return $transaction->meal_date->format('Y-m-d');
            })->map(function($dayTransactions, $date) {
                return [
                    'date' => $date,
                    'scans' => $dayTransactions->count(),
                    'revenue' => $dayTransactions->sum(function($meal) {
                        $scanData = $meal->scan_data;
                        $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                        return $isReward ? 200.00 : 65.00;
                    })
                ];
            })->values();

            $topDepartments = $this->getVendorTopDepartments($vendor, $startDate, $endDate);
            $topCustomers = $this->getVendorTopCustomers($vendor, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_scans' => $totalScans,
                        'total_revenue' => $totalRevenue,
                        'avg_transaction' => $avgTransaction,
                        'days_in_month' => $startDate->daysInMonth,
                        'avg_daily_scans' => $totalScans / $startDate->daysInMonth,
                    ],
                    'daily_data' => $dailyData,
                    'top_departments' => $topDepartments,
                    'top_customers' => $topCustomers,
                    'month' => $startDate->format('F Y'),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get vendor month data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load month data: ' . $e->getMessage()
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

            $vendor = User::with(['profile', 'unit'])->findOrFail($vendorId);

            $period = $request->get('period', 'today');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

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
                default:
                    $dateStart = today();
                    $dateEnd = today()->endOfDay();
            }

            $transactions = $vendor->mealTransactions()
                ->whereBetween('meal_date', [$dateStart, $dateEnd])
                ->get();

            $totalPeriodScans = $transactions->count();

            // Calculate revenue correctly (65 for regular, 200 for reward)
            $totalPeriodRevenue = $transactions->sum(function($meal) {
                $scanData = $meal->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                return $isReward ? 200.00 : 65.00;
            });

            $stats = [
                'current_period_scans' => $totalPeriodScans,
                'current_period_revenue' => $totalPeriodRevenue,
                'previous_period_scans' => $this->getPreviousPeriodScans($vendor, $period),
                'previous_period_revenue' => $this->getPreviousPeriodRevenue($vendor, $period),
                'avg_transaction_value' => $totalPeriodScans > 0 ? round($totalPeriodRevenue / $totalPeriodScans, 2) : 0,
                'peak_hour' => $this->getVendorPeakHourDetailed($vendor, $dateStart, $dateEnd),
                'top_departments' => $this->getVendorTopDepartments($vendor, $dateStart, $dateEnd),
                'top_customers' => $this->getVendorTopCustomers($vendor, $dateStart, $dateEnd),
            ];

            $dailyScans = $this->getVendorDailyScans($vendor, $dateStart, $dateEnd);
            $weeklyActivity = $this->getWeeklyActivity($vendor, $dateStart, $dateEnd);
            $timeSeriesData = $this->getVendorTimeSeriesData($vendor, $dateStart, $dateEnd);
            $departmentDistribution = $this->getDepartmentDistribution($vendor, $dateStart, $dateEnd);
            $hourlyPattern = $this->getHourlyPattern($vendor, $dateStart, $dateEnd);
            $employeeFrequency = $this->getEmployeeFrequency($vendor, $dateStart, $dateEnd);
            $revenueTrends = $this->getRevenueTrends($vendor, $dateStart, $dateEnd);

            return response()->json([
                'success' => true,
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
            Log::error('Vendor analytics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load vendor analytics',
                'message' => $e->getMessage()
            ], 500);
        }
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
            $startDateParam = $request->get('start_date');
            $endDateParam = $request->get('end_date');

            $analyticsData = $this->getVendorAnalyticsForExport($vendor, $period, $startDateParam, $endDateParam);

            if ($format === 'pdf') {
                return $this->exportVendorAnalyticsPDF($vendor, $analyticsData);
            }

            return $this->exportVendorAnalyticsExcel($vendor, $analyticsData);

        } catch (\Exception $e) {
            Log::error('Export vendor analytics error: ' . $e->getMessage());

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

            $analyticsData = $this->getVendorAnalyticsForExport($vendor, $period);

            $emailData = [
                'vendor' => $vendor,
                'summary' => $analyticsData['summary'],
                'date_range' => $analyticsData['date_range'],
                'custom_message' => $request->message,
                'period' => $period
            ];

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
     * Get real-time dashboard stats (for auto-refresh)
     */
    public function getDashboardStats()
    {
        try {
            $today = now()->format('Y-m-d');

            $todayTransactions = MealTransaction::whereDate('meal_date', $today)->get();

            $todayScans = $todayTransactions->count();
            $todayRevenue = $todayTransactions->sum(function($meal) {
                $scanData = $meal->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                return $isReward ? 200.00 : 65.00;
            });

            $stats = [
                'today_scans' => $todayScans,
                'total_revenue_today' => $todayRevenue,
                'employee_participation_rate' => $this->calculateEmployeeParticipationRate(),
                'active_employees' => Employee::where('is_active', true)->count(),
                'verified_vendors' => Profile::whereHas('user', function($q) {
                    $q->where('role', 2);
                })->where('is_verified', true)->count(),
                'pending_verifications' => Profile::whereHas('user', function($q) {
                    $q->where('role', 2);
                })->where('is_verified', false)->count(),
            ];

            $alerts = $this->getSystemAlerts();

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
     * Get vendor details
     */
    public function getVendorDetails($vendorId)
    {
        try {
            $vendor = User::with(['profile'])->findOrFail($vendorId);

            $transactions = $vendor->mealTransactions()->get();

            $stats = [
                'total_scans' => $transactions->count(),
                'today_scans' => $transactions->where('meal_date', today())->count(),
                'week_scans' => $transactions->where('meal_date', '>=', now()->startOfWeek())->count(),
                'month_scans' => $transactions->where('meal_date', '>=', now()->startOfMonth())->count(),
                'total_revenue' => $transactions->sum(function($meal) {
                    $scanData = $meal->scan_data;
                    $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                    return $isReward ? 200.00 : 65.00;
                }),
                'avg_daily_scans' => $transactions
                    ->where('meal_date', '>=', now()->subDays(30))
                    ->groupBy('meal_date')
                    ->map(function($group) { return $group->count(); })
                    ->avg() ?? 0
            ];

            $recentTransactions = $vendor->mealTransactions()
                ->with(['employee.department', 'employee.unit'])
                ->latest()
                ->take(10)
                ->get()
                ->map(function($transaction) {
                    $scanData = $transaction->scan_data;
                    $transaction->is_reward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                    return $transaction;
                });

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

    // =============================================
    // REWARDS METHODS
    // =============================================

    public function rewardsIndex()
    {
        $todayRewards = Reward::getTodayRewards();
        $tomorrowReward = Reward::whereDate('reward_date', now()->addDay())->first();

        $rewards = Reward::with(['employee', 'employee.department', 'employee.unit', 'mealTransaction'])
            ->orderBy('reward_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_rewards_issued' => Reward::count(),
            'total_rewards_claimed' => Reward::where('status', 'claimed')->count(),
            'total_rewards_pending' => Reward::where('status', 'pending')->count(),
            'total_rewards_expired' => Reward::where('status', 'expired')->count(),
            'total_amount_distributed' => Reward::where('status', 'claimed')->sum('amount'),
            'unique_employees_rewarded' => Reward::distinct('employee_id')->count('employee_id')
        ];

        $availableEmployees = Reward::getAvailableEmployeesForReward();
        $units = Unit::active()->get();

        return view('reeds.admin.rewards.index', compact(
            'todayRewards', 'tomorrowReward', 'rewards', 'stats', 'availableEmployees', 'units'
        ));
    }

    public function rewardToday(Request $request)
    {
        $request->validate([
            'unit_id' => 'required|exists:units,id',
            'employee_id' => 'required|exists:employees,id'
        ]);

        try {
            $employee = Employee::findOrFail($request->employee_id);

            if ($employee->unit_id != $request->unit_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee does not belong to this unit'
                ], 422);
            }

            $existingToday = Reward::whereDate('reward_date', today())
                ->where('unit_id', $request->unit_id)
                ->first();

            if ($existingToday) {
                return response()->json([
                    'success' => false,
                    'message' => 'This unit already has a reward for today. Each unit can have only one reward per day.'
                ], 422);
            }

            $reward = Reward::create([
                'employee_id' => $employee->id,
                'unit_id' => $request->unit_id,
                'reward_date' => today(),
                'amount' => 200.00,
                'reason' => 'Manual reward by admin',
                'status' => 'pending',
                'sent_by' => auth()->id()
            ]);

            $this->sendRewardSms($reward);

            return response()->json([
                'success' => true,
                'message' => $employee->formal_name . ' from ' . $employee->unit->name . ' has been awarded 200 KES for today! SMS sent.'
            ]);

        } catch (\Exception $e) {
            Log::error('Manual reward failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to award reward: ' . $e->getMessage()], 500);
        }
    }

    public function getAvailableEmployeesForUnit(Unit $unit)
    {
        $recentWinners = Reward::where('reward_date', '>=', now()->subDays(7))
            ->where('unit_id', $unit->id)
            ->pluck('employee_id')
            ->toArray();

        $employees = Employee::where('is_active', true)
            ->where('unit_id', $unit->id)
            ->whereNotIn('id', $recentWinners)
            ->get();

        if ($employees->isEmpty()) {
            $employees = Employee::where('is_active', true)
                ->where('unit_id', $unit->id)
                ->get();
        }

        return response()->json([
            'success' => true,
            'employees' => $employees->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->formal_name,
                    'code' => $employee->employee_code,
                    'department' => $employee->department->name ?? 'N/A'
                ];
            })
        ]);
    }

    public function getTodayReward()
    {
        $rewards = Reward::with(['employee', 'employee.unit'])
            ->whereDate('reward_date', today())
            ->get();

        if ($rewards->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No rewards assigned for today'
            ]);
        }

        return response()->json([
            'success' => true,
            'rewards' => $rewards->map(function($reward) {
                return [
                    'id' => $reward->id,
                    'employee_name' => $reward->employee->formal_name,
                    'employee_code' => $reward->employee->employee_code,
                    'department' => $reward->employee->department->name ?? 'N/A',
                    'unit' => $reward->employee->unit->name ?? 'N/A',
                    'amount' => $reward->formatted_amount,
                    'status' => $reward->status,
                    'date' => $reward->reward_date->format('F j, Y')
                ];
            })
        ]);
    }

    // =============================================
    // USER MANAGEMENT METHODS
    // =============================================

    public function usersIndex(Request $request)
    {
        $query = User::with(['unit', 'profile'])
            ->select(['id', 'name', 'email', 'role', 'unit_id', 'email_verified_at', 'created_at'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->status === 'inactive') {
                $query->whereNull('email_verified_at');
            } elseif ($request->status === 'unassigned') {
                $query->whereNull('unit_id');
            }
        }

        $users = $query->paginate(20);
        $units = Unit::active()->get();

        $stats = [
            'totalUsers' => User::count(),
            'adminCount' => User::where('role', User::ROLE_ADMIN)->count(),
            'vendorCount' => User::where('role', User::ROLE_VENDOR)->count(),
            'unassignedCount' => User::whereNull('unit_id')->count(),
        ];

        return view('reeds.admin.users.index', compact('users', 'units', 'stats'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_VENDOR])],
            'unit_id' => 'nullable|exists:units,id',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'unit_id' => $request->unit_id,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function editUser(User $user)
    {
        $units = Unit::active()->get();
        return view('reeds.admin.users.edit-modal', compact('user', 'units'));
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_VENDOR])],
            'unit_id' => 'nullable|exists:units,id',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'unit_id' => $request->unit_id,
        ]);

        if ($request->has('verify_email') && $request->boolean('verify_email')) {
            $user->update(['email_verified_at' => now()]);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'User updated successfully']);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully');
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Password reset successfully']);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Password reset successfully');
    }

    // =============================================
    // ANALYTICS METHODS
    // =============================================

    public function analytics()
    {
        $today = now()->format('Y-m-d');
        $weekStart = now()->startOfWeek()->format('Y-m-d');
        $monthStart = now()->startOfMonth()->format('Y-m-d');

        $todayTransactions = MealTransaction::whereDate('meal_date', $today)->get();
        $weekTransactions = MealTransaction::whereDate('meal_date', '>=', $weekStart)->get();
        $monthTransactions = MealTransaction::whereDate('meal_date', '>=', $monthStart)->get();

        $stats = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('is_active', true)->count(),
            'total_vendors' => User::where('role', 2)->count(),
            'verified_vendors' => Profile::whereHas('user', function($q) {
                $q->where('role', 2);
            })->where('is_verified', true)->count(),
            'today_scans' => $todayTransactions->count(),
            'week_scans' => $weekTransactions->count(),
            'month_scans' => $monthTransactions->count(),
            'total_revenue_today' => $todayTransactions->sum(function($meal) {
                $scanData = $meal->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                return $isReward ? 200.00 : 65.00;
            }),
            'total_revenue_week' => $weekTransactions->sum(function($meal) {
                $scanData = $meal->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                return $isReward ? 200.00 : 65.00;
            }),
            'total_revenue_month' => $monthTransactions->sum(function($meal) {
                $scanData = $meal->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                return $isReward ? 200.00 : 65.00;
            }),
            'avg_daily_scans_week' => $weekTransactions->groupBy('meal_date')->map(function($group) {
                return $group->count();
            })->avg() ?? 0,
            'avg_daily_scans_month' => $monthTransactions->groupBy('meal_date')->map(function($group) {
                return $group->count();
            })->avg() ?? 0,
        ];

      $topVendors = User::where('role', 2)
    ->with(['mealTransactions' => function($query) use ($monthStart) {
        $query->where('meal_date', '>=', $monthStart);
    }])
    ->get()
    ->map(function($vendor) {
        $transactions = $vendor->mealTransactions;
        $totalScans = $transactions->count();

        // Calculate regular and reward scans
        $regularScans = 0;
        $rewardScans = 0;
        $totalRevenue = 0;

        foreach ($transactions as $meal) {
            $scanData = $meal->scan_data;
            $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;

            if ($isReward) {
                $rewardScans++;
                $totalRevenue += 200.00;
            } else {
                $regularScans++;
                $totalRevenue += 65.00;
            }
        }

        $vendor->total_scans = $totalScans;
        $vendor->regular_scans = $regularScans;
        $vendor->reward_scans = $rewardScans;
        $vendor->total_revenue = $totalRevenue;
        return $vendor;
    })
    ->sortByDesc('total_scans')
    ->take(5);

        $departmentStats = Department::withCount(['employees as total_employees'])
            ->withCount(['employees as active_employees' => function($query) {
                $query->where('is_active', true);
            }])
            ->having('total_employees', '>', 0)
            ->orderBy('total_employees', 'desc')
            ->get();

        $unitStats = $this->getComprehensiveUnitStats();
        $units = Unit::active()->get();

        $recentTransactions = MealTransaction::with([
                'employee.department',
                'employee.unit',
                'vendor'
            ])
            ->whereHas('employee')
            ->whereHas('vendor')
            ->latest()
            ->take(10)
            ->get()
            ->map(function($transaction) {
                $scanData = $transaction->scan_data;
                $transaction->is_reward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                return $transaction;
            });

        return view('reeds.admin.analytics', compact(
            'stats',
            'topVendors',
            'departmentStats',
            'unitStats',
            'units',
            'recentTransactions'
        ));
    }

    public function getAnalyticsData(Request $request)
    {
        try {
            $period = $request->get('period', 'month');
            $unitId = $request->get('unit_id', 'all');

            switch ($period) {
                case 'week':
                    $startDate = now()->subDays(7);
                    $dateFormat = "DATE_FORMAT(meal_date, '%Y-%m-%d')";
                    break;
                case 'year':
                    $startDate = now()->subMonths(12);
                    $dateFormat = "DATE_FORMAT(meal_date, '%Y-%m')";
                    break;
                default:
                    $startDate = now()->subDays(30);
                    $dateFormat = "DATE_FORMAT(meal_date, '%Y-%m-%d')";
            }

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

            $unitPerformanceQuery = Unit::where('is_active', true)
                ->withCount(['employees as employees_count']);

            $unitPerformance = $unitPerformanceQuery->get()
                ->map(function($unit) use ($startDate, $unitId) {
                    if ($unitId !== 'all' && $unit->id != $unitId) {
                        return null;
                    }

                    $transactions = MealTransaction::whereHas('employee', function($q) use ($unit) {
                            $q->where('unit_id', $unit->id);
                        })
                        ->where('meal_transactions.created_at', '>=', $startDate)
                        ->get();

                    $scansCount = $transactions->count();
                  $revenueSum = $transactions->sum(function($meal) {
    $scanData = $meal->scan_data;
    $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
    return $isReward ? 200.00 : 65.00;
});

return [
    'name' => $unit->name,
    'scans' => $scansCount,
    'employees' => $unit->employees_count,
    'revenue' => $revenueSum ?? 0
];
                })
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'scans_data' => $scansData,
                'unit_performance' => $unitPerformance,
                'period' => $period,
                'unit_id' => $unitId
            ]);

        } catch (\Exception $e) {
            Log::error('Analytics data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load analytics data'
            ], 500);
        }
    }

    // =============================================
    // PRIVATE HELPER METHODS
    // =============================================

    private function calculateGrowthRate($previous, $current)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function calculateVerificationRate()
    {
        $totalVendors = User::where('role', 2)->count();
        $verifiedVendors = Profile::whereHas('user', function($q) {
            $q->where('role', 2);
        })->where('is_verified', true)->count();

        if ($totalVendors == 0) return 0;
        return round(($verifiedVendors / $totalVendors) * 100, 1);
    }

    private function calculateScanGrowthRate()
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $todayScans = MealTransaction::whereDate('meal_date', $today)->count();
        $yesterdayScans = MealTransaction::whereDate('meal_date', $yesterday)->count();

        return $this->calculateGrowthRate($yesterdayScans, $todayScans);
    }

    private function calculateAverageDailyRevenue()
    {
        $monthStart = now()->startOfMonth()->format('Y-m-d');

        $monthTransactions = MealTransaction::whereDate('meal_date', '>=', $monthStart)->get();
        $totalRevenue = $monthTransactions->sum(function($meal) {
            $scanData = $meal->scan_data;
            $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
            return $isReward ? 200.00 : 65.00;
        });

        $days = now()->diffInDays(Carbon::parse($monthStart)) + 1;

        return $days > 0 ? round($totalRevenue / $days, 2) : 0;
    }

    private function calculateRevenueGrowthRate()
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $todayTransactions = MealTransaction::whereDate('meal_date', $today)->get();
        $todayRevenue = $todayTransactions->sum(function($meal) {
            $scanData = $meal->scan_data;
            $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
            return $isReward ? 200.00 : 65.00;
        });

        $yesterdayTransactions = MealTransaction::whereDate('meal_date', $yesterday)->get();
        $yesterdayRevenue = $yesterdayTransactions->sum(function($meal) {
            $scanData = $meal->scan_data;
            $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
            return $isReward ? 200.00 : 65.00;
        });

        return $this->calculateGrowthRate($yesterdayRevenue, $todayRevenue);
    }

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

    private function calculateAverageScansPerVendor()
    {
        $vendorCount = User::where('role', 2)->count();
        $todayScans = MealTransaction::whereDate('meal_date', now()->format('Y-m-d'))->count();

        if ($vendorCount == 0) return 0;
        return round($todayScans / $vendorCount, 1);
    }

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

        return $peak ? ['hour' => $peak->hour . ':00', 'scans' => $peak->count] : null;
    }

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

        return $busiest ? ['day' => $busiest->day, 'scans' => $busiest->count] : null;
    }

    private function getTopPerformingUnit()
    {
        $topUnit = Unit::where('is_active', true)
            ->with(['mealTransactions' => function($query) {
                $query->whereDate('meal_date', now()->format('Y-m-d'));
            }])
            ->get()
            ->map(function($unit) {
                $scans = $unit->mealTransactions->count();
                return ['name' => $unit->name, 'scans' => $scans];
            })
            ->sortByDesc('scans')
            ->first();

        return $topUnit ? $topUnit : null;
    }

    private function getTopPerformingVendor()
    {
        $topVendor = User::where('role', 2)
            ->with(['mealTransactions' => function($query) {
                $query->whereDate('meal_date', now()->format('Y-m-d'));
            }])
            ->get()
            ->map(function($vendor) {
                $scans = $vendor->mealTransactions->count();
                $revenue = $vendor->mealTransactions->sum(function($meal) {
                    $scanData = $meal->scan_data;
                    $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                    return $isReward ? 200.00 : 65.00;
                });
                return [
                    'name' => $vendor->name,
                    'scans' => $scans,
                    'revenue' => $revenue
                ];
            })
            ->filter(fn($v) => $v['scans'] > 0)
            ->sortByDesc('scans')
            ->first();

        return $topVendor;
    }

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

    private function getRevenueTrend($days = 30)
    {
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $transactions = MealTransaction::whereBetween('meal_date', [$startDate, $endDate])->get();

        $period = CarbonPeriod::create($startDate, $endDate);
        $completeTrend = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $dayTransactions = $transactions->filter(function($t) use ($dateStr) {
                return $t->meal_date->format('Y-m-d') === $dateStr;
            });

            $revenue = $dayTransactions->sum(function($meal) {
                $scanData = $meal->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                return $isReward ? 200.00 : 65.00;
            });

            $completeTrend[] = [
                'date' => $date->format('M d'),
                'revenue' => $revenue,
                'day' => $date->format('D')
            ];
        }

        return $completeTrend;
    }

    private function getVendorPerformanceTrend()
    {
        $monthStart = now()->startOfMonth()->format('Y-m-d');

        $vendors = User::where('role', 2)
            ->with(['mealTransactions' => function($query) use ($monthStart) {
                $query->where('meal_date', '>=', $monthStart);
            }])
            ->get()
            ->map(function($vendor) {
                $scans = $vendor->mealTransactions->count();
                $revenue = $vendor->mealTransactions->sum(function($meal) {
                    $scanData = $meal->scan_data;
                    $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                    return $isReward ? 200.00 : 65.00;
                });
                return [
                    'name' => $vendor->name,
                    'scans' => $scans,
                    'revenue' => $revenue,
                    'avg_transaction' => $scans > 0 ? round($revenue / $scans, 2) : 0
                ];
            })
            ->filter(fn($v) => $v['scans'] > 0)
            ->sortByDesc('scans')
            ->take(5);

        return $vendors;
    }

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

            $todayTransactions = $unit->mealTransactions()
                ->whereDate('meal_date', $today)
                ->get();

            $yesterdayTransactions = $unit->mealTransactions()
                ->whereDate('meal_date', now()->subDay()->format('Y-m-d'))
                ->get();

            $monthTransactions = $unit->mealTransactions()
                ->where('meal_date', '>=', $monthStart)
                ->get();

            $todayScans = $todayTransactions->count();
            $yesterdayScans = $yesterdayTransactions->count();

            $monthRevenue = $monthTransactions->sum(function($meal) {
                $scanData = $meal->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                return $isReward ? 200.00 : 65.00;
            });

            $todayRevenue = $todayTransactions->sum(function($meal) {
                $scanData = $meal->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                return $isReward ? 200.00 : 65.00;
            });

            $scanGrowth = $yesterdayScans > 0
                ? round((($todayScans - $yesterdayScans) / $yesterdayScans) * 100, 1)
                : ($todayScans > 0 ? 100 : 0);

            $capacityUtilization = null;
            if ($unit->capacity && $unit->capacity > 0) {
                $capacityUtilization = round(($unit->current_employee_count / $unit->capacity) * 100, 0);
            }

            $participationRate = $unit->active_employees > 0
                ? round(($todayScans / $unit->active_employees) * 100, 1)
                : 0;

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

    private function calculateUnitEfficiencyScore($unit, $todayScans, $todayRevenue)
    {
        $score = 0;

        if ($unit->capacity && $unit->capacity > 0) {
            $utilization = ($unit->current_employee_count / $unit->capacity) * 100;
            $score += min(30, $utilization * 0.3);
        }

        if ($unit->active_employees > 0) {
            $participation = ($todayScans / $unit->active_employees) * 100;
            $score += min(30, $participation * 0.3);
        }

        if ($unit->active_employees > 0) {
            $revenuePerEmployee = $todayRevenue / $unit->active_employees;
            $score += min(20, ($revenuePerEmployee / 65) * 20);
        }

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

    private function getSystemAlerts()
    {
        $alerts = [];

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

        $participationRate = $this->calculateEmployeeParticipationRate();
        if ($participationRate < 30) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "Low employee participation rate: {$participationRate}%",
                'action' => route('admin.analytics.index'),
                'action_text' => 'View Analytics',
            ];
        }

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

    private function getForecastData()
    {
        $today = now()->format('Y-m-d');
        $todayScans = MealTransaction::whereDate('meal_date', $today)->count();

        $last7DaysAvg = MealTransaction::where('meal_date', '>=', now()->subDays(7))
            ->where('meal_date', '<', $today)
            ->select(DB::raw('DATE(meal_date) as date'), DB::raw('COUNT(*) as scans'))
            ->groupBy('date')
            ->get()
            ->avg('scans');

        $forecastTomorrow = round($last7DaysAvg ?? $todayScans, 0);

        $todayTransactions = MealTransaction::whereDate('meal_date', $today)->get();
        $todayRevenue = $todayTransactions->sum(function($meal) {
            $scanData = $meal->scan_data;
            $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
            return $isReward ? 200.00 : 65.00;
        });

        $last7DaysTransactions = MealTransaction::where('meal_date', '>=', now()->subDays(7))
            ->where('meal_date', '<', $today)
            ->get();

        $last7DaysRevenueAvg = $last7DaysTransactions->groupBy('meal_date')
            ->map(function($group) {
                return $group->sum(function($meal) {
                    $scanData = $meal->scan_data;
                    $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                    return $isReward ? 200.00 : 65.00;
                });
            })
            ->avg();

        $forecastRevenueTomorrow = round($last7DaysRevenueAvg ?? $todayRevenue, 2);

        return [
            'tomorrow_scans' => $forecastTomorrow,
            'tomorrow_revenue' => $forecastRevenueTomorrow,
            'confidence' => $last7DaysAvg ? 85 : 70,
            'trend' => $forecastTomorrow > $todayScans ? 'up' : 'down'
        ];
    }

    private function calculateVendorDailyAverage($vendorId, $startDate)
    {
        $days = now()->diffInDays(Carbon::parse($startDate)) + 1;
        $transactions = MealTransaction::where('vendor_id', $vendorId)
            ->where('meal_date', '>=', $startDate)
            ->get();

        $totalScans = $transactions->count();
        return $days > 0 ? round($totalScans / $days, 1) : 0;
    }

    private function calculateVendorRetention($vendorId)
    {
        $lastWeekStart = now()->subDays(7)->format('Y-m-d');
        $previousWeekStart = now()->subDays(14)->format('Y-m-d');
        $previousWeekEnd = now()->subDays(8)->format('Y-m-d');

        // Customers last week (distinct employees)
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
            default:
                $startDate = now()->subDay()->startOfDay();
                $endDate = now()->subDay()->endOfDay();
        }

        return $vendor->mealTransactions()
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->count();
    }

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
            default:
                $startDate = now()->subDay()->startOfDay();
                $endDate = now()->subDay()->endOfDay();
        }

        $transactions = $vendor->mealTransactions()
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->get();

        return $transactions->sum(function($meal) {
            $scanData = $meal->scan_data;
            $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
            return $isReward ? 200.00 : 65.00;
        });
    }

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

        return $peak ? ['hour' => $peak->hour . ':00', 'count' => $peak->count] : null;
    }

    private function getVendorTopDepartments($vendor, $startDate, $endDate)
    {
        return MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->join('employees', 'meal_transactions.employee_id', '=', 'employees.id')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->select(
                'departments.name as department',
                DB::raw('COUNT(*) as scans'),
                DB::raw('SUM(CASE WHEN JSON_EXTRACT(meal_transactions.scan_data, "$.is_reward") = true THEN 200.00 ELSE 65.00 END) as revenue')
            )
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('scans', 'desc')
            ->limit(5)
            ->get();
    }

    private function getVendorTopCustomers($vendor, $startDate, $endDate)
    {
        return MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->with('employee')
            ->select(
                'employee_id',
                DB::raw('COUNT(*) as visits'),
                DB::raw('SUM(CASE WHEN JSON_EXTRACT(scan_data, "$.is_reward") = true THEN 200.00 ELSE 65.00 END) as total_spent'),
                DB::raw('AVG(CASE WHEN JSON_EXTRACT(scan_data, "$.is_reward") = true THEN 200.00 ELSE 65.00 END) as avg_spent')
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

   private function getVendorDailyScans($vendor, $startDate, $endDate)
{
    $scans = MealTransaction::where('vendor_id', $vendor->id)
        ->whereBetween('meal_date', [$startDate, $endDate])
        ->with(['employee.department', 'employee.unit'])
        ->orderBy('meal_date', 'desc')
        ->orderBy('meal_time', 'desc')
        ->get()
        ->groupBy(function($transaction) {
            return $transaction->meal_date instanceof Carbon
                ? $transaction->meal_date->format('Y-m-d')
                : date('Y-m-d', strtotime($transaction->meal_date));
        })
        ->map(function($transactions, $date) use ($vendor) {
            return $transactions->map(function($transaction) use ($vendor) {
                $scanData = $transaction->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;

                // Safely get scan time
                $scanTime = 'N/A';
                if ($transaction->meal_time) {
                    $scanTime = date('H:i:s', strtotime($transaction->meal_time));
                } elseif ($transaction->created_at) {
                    $scanTime = date('H:i:s', strtotime($transaction->created_at));
                }

                return [
                    'id' => $transaction->id,
                    'employee_name' => $transaction->employee->formal_name ?? 'Unknown',
                    'employee_code' => $transaction->employee->employee_number ?? '',
                    'department' => $transaction->employee->department->name ?? 'N/A',
                    'unit' => $transaction->employee->unit->name ?? 'N/A',
                    'vendor_name' => $vendor->name,
                    'scan_time' => $scanTime,
                    'is_reward' => $isReward,
                    'amount' => $isReward ? 200.00 : 65.00,
                ];
            });
        });

    return $scans;
}

    private function getWeeklyActivity($vendor, $startDate, $endDate)
    {
        $activity = [];
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        $transactions = $vendor->mealTransactions()
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->get();

        $grouped = $transactions->groupBy(function($transaction) {
            return Carbon::parse($transaction->meal_date)->dayName;
        });

        foreach ($daysOfWeek as $day) {
            $dayTransactions = $grouped->get($day, collect());
            $scans = $dayTransactions->count();
            $revenue = $dayTransactions->sum(function($meal) {
                $scanData = $meal->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                return $isReward ? 200.00 : 65.00;
            });

            $activity[] = [
                'day' => $day,
                'scans' => $scans,
                'revenue' => $revenue,
                'avg_per_scan' => $scans > 0 ? round($revenue / $scans, 2) : 0
            ];
        }

        return $activity;
    }

    private function getVendorTimeSeriesData($vendor, $startDate, $endDate)
    {
        $diffInDays = $startDate->diffInDays($endDate);

        if ($diffInDays <= 31) {
            return MealTransaction::where('vendor_id', $vendor->id)
                ->whereBetween('meal_date', [$startDate, $endDate])
                ->select(
                    DB::raw("DATE(meal_date) as date"),
                    DB::raw('COUNT(*) as scans'),
                    DB::raw('SUM(CASE WHEN JSON_EXTRACT(scan_data, "$.is_reward") = true THEN 200.00 ELSE 65.00 END) as revenue'),
                    DB::raw('AVG(CASE WHEN JSON_EXTRACT(scan_data, "$.is_reward") = true THEN 200.00 ELSE 65.00 END) as avg_amount')
                )
                ->groupBy(DB::raw("DATE(meal_date)"))
                ->orderBy('date')
                ->get();
        } else {
            return MealTransaction::where('vendor_id', $vendor->id)
                ->whereBetween('meal_date', [$startDate, $endDate])
                ->select(
                    DB::raw("YEARWEEK(meal_date) as week"),
                    DB::raw('COUNT(*) as scans'),
                    DB::raw('SUM(CASE WHEN JSON_EXTRACT(scan_data, "$.is_reward") = true THEN 200.00 ELSE 65.00 END) as revenue'),
                    DB::raw('AVG(CASE WHEN JSON_EXTRACT(scan_data, "$.is_reward") = true THEN 200.00 ELSE 65.00 END) as avg_amount')
                )
                ->groupBy(DB::raw("YEARWEEK(meal_date)"))
                ->orderBy('week')
                ->get();
        }
    }

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

    private function getHourlyPattern($vendor, $startDate, $endDate)
    {
        return MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->select(
                DB::raw('HOUR(meal_time) as hour'),
                DB::raw('COUNT(*) as scans'),
                DB::raw('SUM(CASE WHEN JSON_EXTRACT(scan_data, "$.is_reward") = true THEN 200.00 ELSE 65.00 END) as revenue'),
                DB::raw('AVG(CASE WHEN JSON_EXTRACT(scan_data, "$.is_reward") = true THEN 200.00 ELSE 65.00 END) as avg_amount')
            )
            ->groupBy(DB::raw('HOUR(meal_time)'))
            ->orderBy('hour')
            ->get();
    }

    private function getEmployeeFrequency($vendor, $startDate, $endDate)
    {
        $employeeDays = MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->select(
                'employee_id',
                DB::raw('COUNT(DISTINCT DATE(meal_date)) as days_visited')
            )
            ->groupBy('employee_id')
            ->get();

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

    private function getRevenueTrends($vendor, $startDate, $endDate)
    {
        return MealTransaction::where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->select(
                DB::raw("DATE_FORMAT(meal_date, '%Y-%m') as month"),
                DB::raw('SUM(CASE WHEN JSON_EXTRACT(scan_data, "$.is_reward") = true THEN 200.00 ELSE 65.00 END) as total_revenue'),
                DB::raw('COUNT(*) as total_scans'),
                DB::raw('AVG(CASE WHEN JSON_EXTRACT(scan_data, "$.is_reward") = true THEN 200.00 ELSE 65.00 END) as avg_transaction_value')
            )
            ->groupBy(DB::raw("DATE_FORMAT(meal_date, '%Y-%m')"))
            ->orderBy('month')
            ->get();
    }

    private function getVendorAnalyticsForExport($vendor, $period, $startDateParam = null, $endDateParam = null)
    {
        if ($period === 'custom' && $startDateParam && $endDateParam) {
            $startDate = Carbon::parse($startDateParam)->startOfDay();
            $endDate = Carbon::parse($endDateParam)->endOfDay();
        } elseif ($period === 'week') {
            $startDate = now()->startOfWeek();
            $endDate = now()->endOfWeek();
        } else {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
        }

        $transactions = MealTransaction::with(['employee.department', 'employee.unit'])
            ->where('vendor_id', $vendor->id)
            ->whereBetween('meal_date', [$startDate, $endDate])
            ->get();

        $totalScans = $transactions->count();
        $totalRevenue = $transactions->sum(function($meal) {
            $scanData = $meal->scan_data;
            $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
            return $isReward ? 200.00 : 65.00;
        });

        return [
            'vendor' => $vendor,
            'transactions' => $transactions,
            'summary' => [
                'total_scans' => $totalScans,
                'total_revenue' => $totalRevenue,
                'avg_daily_scans' => $transactions->groupBy('meal_date')
                    ->map(fn($g) => $g->count())
                    ->avg() ?? 0,
                'top_department' => $this->getVendorTopDepartments($vendor, $startDate, $endDate)->first(),
            ],
            'date_range' => [
                'start' => $startDate,
              'end' => $endDate
            ]
        ];
    }

    private function exportVendorAnalyticsPDF($vendor, $analyticsData)
    {
        try {
            if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                throw new \Exception('PDF generation library not installed. Run: composer require barryvdh/laravel-dompdf');
            }

            $html = view('reeds.admin.exports.vendor-pdf', [
                'vendor' => $vendor,
                'data' => $analyticsData,
                'date' => now()->format('Y-m-d H:i:s')
            ])->render();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');

            $filename = 'vendor-analytics-' . $vendor->id . '-' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('PDF export error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function exportVendorAnalyticsExcel($vendor, $analyticsData)
    {
        try {
            $filename = 'vendor-analytics-' . $vendor->id . '-' . now()->format('Y-m-d') . '.csv';

            $csvData = [];

            $csvData[] = ['Vendor Analytics Report - ' . $vendor->name];
            $csvData[] = ['Generated on: ' . now()->format('Y-m-d H:i:s')];
            $csvData[] = [];

            $csvData[] = ['SUMMARY'];
            $csvData[] = ['Metric', 'Value'];
            $csvData[] = ['Total Scans', $analyticsData['summary']['total_scans'] ?? 0];
            $csvData[] = ['Total Revenue', 'KSh ' . number_format($analyticsData['summary']['total_revenue'] ?? 0, 2)];
            $csvData[] = ['Average Daily Scans', $analyticsData['summary']['avg_daily_scans'] ?? 0];
            $csvData[] = ['Top Department', $analyticsData['summary']['top_department']->name ?? 'N/A'];
            $csvData[] = [];

            $csvData[] = ['RECENT TRANSACTIONS'];
            $csvData[] = ['Date', 'Employee', 'Department', 'Amount', 'Time', 'Type'];

            foreach ($analyticsData['transactions'] as $transaction) {
                $scanData = $transaction->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                $type = $isReward ? 'Reward (200)' : 'Regular (65)';
                $amount = $isReward ? 200.00 : 65.00;

                $csvData[] = [
                    $transaction->meal_date,
                    $transaction->employee->formal_name ?? 'Unknown',
                    $transaction->employee->department->name ?? 'N/A',
                    'KSh ' . number_format($amount, 2),
                    $transaction->meal_time,
                    $type
                ];
            }

            $output = '';
            foreach ($csvData as $row) {
                $output .= $this->formatCsvRow($row) . "\n";
            }

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

    private function sendRewardSms(Reward $reward)
    {
        try {
            $employee = $reward->employee;

            if (!$employee->phone) {
                Log::warning('Cannot send reward SMS - no phone number', ['employee_id' => $employee->id]);
                return;
            }

            $smsService = new AdvantaSMSService();
            $formattedDate = $reward->reward_date->format('l, F jS, Y');

            $message = "SECURITY REWARD ALERT\n\n" .
                       "Hello {$employee->first_name},\n\n" .
                       "You have been awarded 200 KES.\n" .
                       "Your meal card will be worth 200 KES today ({$formattedDate}).\n\n" .
                       "Present your card at the canteen as usual.\n" .
                       "Valid only TODAY.\n\n" .
                       "- Reeds Africa Management";

            $response = $smsService->sendSMS($employee->phone, $message, $reward->id);

            if (isset($response['responses'][0]['respose-code']) && $response['responses'][0]['respose-code'] == 200) {
                $reward->update([
                    'sms_sent' => true,
                    'sms_sent_at' => now(),
                    'sms_message_id' => $response['responses'][0]['messageid'] ?? null,
                    'sms_status' => 'sent'
                ]);
                Log::info('Reward SMS sent successfully', ['reward_id' => $reward->id]);
            } else {
                $error = $response['responses'][0]['response-description'] ?? 'Unknown error';
                $reward->update([
                    'sms_sent' => false,
                    'sms_status' => 'failed',
                    'sms_error' => $error
                ]);
                Log::error('Reward SMS failed', ['reward_id' => $reward->id, 'error' => $error]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send reward SMS: ' . $e->getMessage());
            $reward->update(['sms_sent' => false, 'sms_status' => 'failed', 'sms_error' => $e->getMessage()]);
        }
    }

    /**
     * Get unit-specific analytics
     */
    public function getUnitAnalytics(Unit $unit)
    {
        try {
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
                    $scanData = $transaction->scan_data;
                    $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                    $effectiveAmount = $isReward ? 200.00 : 65.00;

                    return [
                        'formal_name' => $transaction->employee->formal_name ?? 'Unknown',
                        'department' => $transaction->employee->department->name ?? 'N/A',
                        'meal_count' => $transaction->meal_count,
                        'total_amount' => $transaction->total_amount,
                        'effective_amount' => $effectiveAmount
                    ];
                });

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
                    ->get()
                    ->sum(function($meal) {
                        $scanData = $meal->scan_data;
                        $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                        return $isReward ? 200.00 : 65.00;
                    }),
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
     * Export unit analytics
     */
    public function exportUnitAnalytics(Request $request)
    {
        try {
            $format = $request->get('format', 'excel');
            $unitId = $request->get('unit_id', 'all');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $units = $this->getUnitExportData($unitId, $startDate, $endDate);

            if ($format === 'pdf') {
                return $this->exportUnitAnalyticsPDF($units, $startDate, $endDate);
            }

            return $this->exportUnitAnalyticsExcel($units, $startDate, $endDate);

        } catch (\Exception $e) {
            Log::error('Export unit analytics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to export unit analytics'
            ], 500);
        }
    }

    private function getUnitExportData($unitId = 'all', $startDate = null, $endDate = null)
    {
        $query = Unit::query();

        if ($unitId !== 'all') {
            $query->where('id', $unitId);
        }

        $units = $query->get()->map(function($unit) use ($startDate, $endDate) {
            $dateStart = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
            $dateEnd = $endDate ? Carbon::parse($endDate) : now();

            $totalEmployees = $unit->employees()->count();
            $activeEmployees = $unit->employees()->where('is_active', true)->count();

            $transactions = MealTransaction::whereHas('employee', function($q) use ($unit) {
                $q->where('unit_id', $unit->id);
            });

            if ($startDate && $endDate) {
                $transactions->whereBetween('meal_date', [$dateStart, $dateEnd]);
            }

            $allTransactions = $transactions->get();

            $totalScans = $allTransactions->count();
            $totalRevenue = $allTransactions->sum(function($meal) {
                $scanData = $meal->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                return $isReward ? 200.00 : 65.00;
            });

            $avgTransaction = $totalScans > 0 ? $totalRevenue / $totalScans : 0;

            $activeVendors = User::where('role', 2)
                ->where('unit_id', $unit->id)
                ->whereHas('profile', function($q) {
                    $q->where('is_verified', true);
                })
                ->count();

            $daysDiff = $dateStart->diffInDays($dateEnd) + 1;
            $avgDailyScans = $daysDiff > 0 ? $totalScans / $daysDiff : 0;

            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'code' => $unit->code,
                'location' => $unit->location,
                'capacity' => $unit->capacity,
                'current_employees' => $unit->current_employee_count,
                'total_employees' => $totalEmployees,
                'active_employees' => $activeEmployees,
                'employee_participation_rate' => $activeEmployees > 0 ?
                    round(($totalEmployees / $activeEmployees) * 100, 1) : 0,
                'total_scans' => $totalScans,
                'total_revenue' => $totalRevenue,
                'avg_transaction_value' => round($avgTransaction, 2),
                'avg_daily_scans' => round($avgDailyScans, 1),
                'active_vendors' => $activeVendors,
                'capacity_utilization' => $unit->capacity > 0 ?
                    round(($unit->current_employee_count / $unit->capacity) * 100, 0) : null,
                'date_range' => [
                    'start' => $dateStart->format('Y-m-d'),
                    'end' => $dateEnd->format('Y-m-d'),
                    'days' => $daysDiff
                ]
            ];
        });

        return $units;
    }

    private function exportUnitAnalyticsExcel($units, $startDate = null, $endDate = null)
    {
        $dateRange = $startDate && $endDate
            ? $startDate . '_to_' . $endDate
            : now()->format('Y-m-d');

        $filename = 'unit-analytics-' . $dateRange . '.csv';

        $csvData = [];

        $csvData[] = ['UNIT ANALYTICS REPORT'];
        $csvData[] = ['Generated on: ' . now()->format('Y-m-d H:i:s')];
        if ($startDate && $endDate) {
            $csvData[] = ['Period: ' . $startDate . ' to ' . $endDate];
        }
        $csvData[] = [];

        $csvData[] = ['UNIT PERFORMANCE SUMMARY'];
        $csvData[] = [
            'Unit Name', 'Code', 'Location', 'Capacity', 'Current Employees',
            'Total Employees', 'Active Employees', 'Total Scans', 'Total Revenue',
            'Avg Transaction', 'Avg Daily Scans', 'Active Vendors', 'Capacity Utilization'
        ];

        foreach ($units as $unit) {
            $csvData[] = [
                $unit['name'],
                $unit['code'] ?? 'N/A',
                $unit['location'] ?? 'N/A',
                $unit['capacity'] ?? 'N/A',
                $unit['current_employees'],
                $unit['total_employees'],
                $unit['active_employees'],
                $unit['total_scans'],
                'KSh ' . number_format($unit['total_revenue'], 2),
                'KSh ' . number_format($unit['avg_transaction_value'], 2),
                $unit['avg_daily_scans'],
                $unit['active_vendors'],
                $unit['capacity_utilization'] ? $unit['capacity_utilization'] . '%' : 'N/A'
            ];
        }

        $output = '';
        foreach ($csvData as $row) {
            $output .= $this->formatCsvRow($row) . "\n";
        }

        return response($output, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function exportUnitAnalyticsPDF($units, $startDate = null, $endDate = null)
    {
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new \Exception('PDF generation library not installed');
        }

        $data = [
            'units' => $units,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => now()->format('Y-m-d H:i:s')
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reeds.admin.exports.unit-analytics-pdf', $data);

        $filename = 'unit-analytics-' . ($startDate ? $startDate . '_to_' . $endDate : now()->format('Y-m-d')) . '.pdf';

        return $pdf->download($filename);
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

    private function getPeriodData($startDate, $endDate)
    {
        $transactions = MealTransaction::whereBetween('meal_date', [$startDate, $endDate])->get();

        return [
            'total_scans' => $transactions->count(),
            'total_revenue' => $transactions->sum(function($meal) {
                $scanData = $meal->scan_data;
                $isReward = $scanData && isset($scanData['is_reward']) && $scanData['is_reward'] === true;
                return $isReward ? 200.00 : 65.00;
            }),
            'total_employees' => Employee::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_vendors' => User::where('role', 2)->whereBetween('created_at', [$startDate, $endDate])->count(),
            'avg_daily_scans' => $transactions->groupBy('meal_date')
                ->map(fn($g) => $g->count())
                ->avg() ?? 0,
            'peak_day' => $this->getPeakDayInPeriod($startDate, $endDate),
        ];
    }

    private function getPeakDayInPeriod($startDate, $endDate)
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

    private function checkDatabaseHealth()
    {
        try {
            DB::connection()->getPdo();

            $employeeCount = Employee::count();
            $vendorCount = User::where('role', 2)->count();
            $transactionCount = MealTransaction::count();

            $score = 100;
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

    private function checkScanSystemHealth()
    {
        $todayScans = MealTransaction::whereDate('meal_date', today())->count();
        $yesterdayScans = MealTransaction::whereDate('meal_date', now()->subDay())->count();

        $score = 70;
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

    private function checkVendorSystemHealth()
    {
        $totalVendors = User::where('role', 2)->count();
        $activeVendors = User::where('role', 2)
            ->whereHas('mealTransactions', function($query) {
                $query->whereDate('meal_date', '>=', now()->subDays(7));
            })
            ->count();

        $score = 60;
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

    private function checkEmployeeSystemHealth()
    {
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('is_active', true)->count();
        $participationRate = $this->calculateEmployeeParticipationRate();

        $score = 50;
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

    /**
     * INVOICE MANAGEMENT METHODS
     */
    public function invoices()
    {
        $stats = [
            'total_invoices' => VendorInvoice::count(),
            'pending_invoices' => VendorInvoice::where('status', 'pending')->count(),
            'paid_invoices' => VendorInvoice::where('status', 'paid')->count(),
            'overdue_invoices' => VendorInvoice::where('status', 'overdue')->count(),
            'total_revenue' => VendorInvoice::where('status', 'paid')->sum('total_amount'),
            'pending_amount' => VendorInvoice::where('status', 'pending')->sum('total_amount'),
            'overdue_amount' => VendorInvoice::where('status', 'overdue')->sum('total_amount'),
        ];

        return view('reeds.admin.invoices.index', compact('stats'));
    }

    public function invoicesData(Request $request)
    {
        try {
            $query = VendorInvoice::with(['vendor.profile', 'items'])
                ->withCount('items')
                ->orderBy('created_at', 'desc');

            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('vendor_id') && $request->vendor_id !== 'all') {
                $query->where('vendor_id', $request->vendor_id);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('invoice_date', [$request->date_from, $request->date_to]);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('vendor', function($vendorQ) use ($search) {
                          $vendorQ->where('name', 'like', "%{$search}%")
                                 ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            $perPage = $request->get('per_page', 15);
            $invoices = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'invoices' => $invoices->map(function($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'vendor_name' => $invoice->vendor->name,
                        'vendor_email' => $invoice->vendor->email,
                        'vendor_phone' => $invoice->vendor_phone,
                        'period' => $invoice->formatted_period,
                        'period_start' => $invoice->period_start->format('Y-m-d'),
                        'period_end' => $invoice->period_end->format('Y-m-d'),
                        'total_amount' => floatval($invoice->total_amount),
                        'formatted_total' => $invoice->formatted_total,
                        'total_scans' => $invoice->total_scans,
                        'status' => $invoice->status,
                        'status_badge' => $invoice->status_badge,
                        'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
                        'due_date' => $invoice->due_date->format('Y-m-d'),
                        'cycle' => $invoice->cycle_number,
                        'period_name' => $invoice->period_name,
                        'is_test' => $invoice->is_test,
                        'is_overdue' => $invoice->isOverdue(),
                        'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'pagination' => [
                    'current_page' => $invoices->currentPage(),
                    'last_page' => $invoices->lastPage(),
                    'per_page' => $invoices->perPage(),
                    'total' => $invoices->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Admin invoices data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load invoices'
            ], 500);
        }
    }

    public function viewInvoice($id)
    {
        try {
            $invoice = VendorInvoice::with(['items', 'vendor.profile'])
                ->findOrFail($id);

            return view('reeds.admin.invoices.view', compact('invoice'));

        } catch (\Exception $e) {
            Log::error('View invoice error: ' . $e->getMessage());
            return redirect()->route('admin.invoices.index')
                ->with('error', 'Invoice not found');
        }
    }

    public function downloadInvoice($id)
    {
        try {
            $invoice = VendorInvoice::with(['items', 'vendor.profile'])
                ->findOrFail($id);

            $pdf = Pdf::loadView('reeds.vendor.invoice-pdf', compact('invoice'));

            return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');

        } catch (\Exception $e) {
            Log::error('Download invoice error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to download invoice');
        }
    }

    public function markInvoiceAsPaid(Request $request, $id)
    {
        try {
            $invoice = VendorInvoice::findOrFail($id);

            $invoice->status = 'paid';
            $invoice->save();

            Log::info('Invoice marked as paid', [
                'invoice_id' => $id,
                'admin_id' => auth()->id(),
                'invoice_number' => $invoice->invoice_number
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invoice marked as paid successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Mark invoice paid error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark invoice as paid'
            ], 500);
        }
    }
}
