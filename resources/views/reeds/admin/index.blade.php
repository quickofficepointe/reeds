@extends('reeds.admin.layout.adminlayout')

@section('content')
<script>
    // Pass PHP data to JavaScript
    window.dashboardTrendData = @json($trendData['daily_scans_7d']);
    window.dashboardTrendData30d = @json($trendData['daily_scans_30d'] ?? []);
</script>

<div class="max-w-7xl mx-auto">
    <!-- Header with Stats Summary -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-text-black">Executive Dashboard</h1>
                <p class="text-gray-600 mt-2">Welcome back, {{ Auth::user()->name }}! Here's your comprehensive operations report.</p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                    {{ now()->format('l, F j, Y') }}
                </span>
                <span class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full">
                    {{ now()->format('h:i A') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Executive Summary Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Performance Overview -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-text-black">Performance Overview</h2>
                <span class="text-xs text-gray-500">Today vs Yesterday</span>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <p class="text-sm text-gray-600">Total Scans</p>
                    <div class="flex items-baseline space-x-2">
                        <p class="text-2xl font-bold text-text-black" id="today_scans">{{ $stats['today_scans'] }}</p>
                        @php
                            $growthClass = $stats['scan_growth_rate'] >= 0 ? 'text-green-600' : 'text-red-600';
                            $growthIcon = $stats['scan_growth_rate'] >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
                        @endphp
                        <span class="text-sm {{ $growthClass }}">
                            <i class="{{ $growthIcon }} mr-1"></i>
                            {{ abs($stats['scan_growth_rate']) }}%
                        </span>
                    </div>
                    <p class="text-xs text-gray-500">Yesterday: {{ $stats['yesterday_scans'] }}</p>
                </div>
                <div class="space-y-2">
                    <p class="text-sm text-gray-600">Revenue</p>
                    <div class="flex items-baseline space-x-2">
                        <p class="text-2xl font-bold text-text-black" id="total_revenue_today">KSh {{ number_format($stats['total_revenue_today'], 2) }}</p>
                        @php
                            $revGrowthClass = $stats['revenue_growth_rate'] >= 0 ? 'text-green-600' : 'text-red-600';
                            $revGrowthIcon = $stats['revenue_growth_rate'] >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
                        @endphp
                        <span class="text-sm {{ $revGrowthClass }}">
                            <i class="{{ $revGrowthIcon }} mr-1"></i>
                            {{ abs($stats['revenue_growth_rate']) }}%
                        </span>
                    </div>
                    <p class="text-xs text-gray-500">Yesterday: KSh {{ number_format($stats['total_revenue_yesterday'], 2) }}</p>
                </div>
            </div>
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <div class="text-center">
                        <p class="text-sm text-gray-600">Employee Participation</p>
                        <p class="text-lg font-bold text-text-black" id="employee_participation_rate">{{ $stats['employee_participation_rate'] }}%</p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-600">Avg Scans/Vendor</p>
                        <p class="text-lg font-bold text-text-black">{{ $stats['avg_scans_per_vendor'] }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-600">Peak Hour</p>
                        <p class="text-lg font-bold text-text-black">{{ $stats['peak_hour']['hour'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Forecast & Trends -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-text-black">Tomorrow's Forecast</h2>
                <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                    {{ $forecast['confidence'] }}% confidence
                </span>
            </div>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium text-text-black">Expected Scans</p>
                        <p class="text-sm text-gray-500">Based on 7-day average</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-text-black">{{ $forecast['tomorrow_scans'] }}</p>
                        <p class="text-xs text-gray-500">
                            @if($forecast['trend'] == 'up')
                            <i class="fas fa-arrow-up text-green-600 mr-1"></i> Trending up
                            @else
                            <i class="fas fa-arrow-down text-red-600 mr-1"></i> Trending down
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium text-text-black">Expected Revenue</p>
                        <p class="text-sm text-gray-500">Projected income</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-green-600">KSh {{ number_format($forecast['tomorrow_revenue'], 2) }}</p>
                        <p class="text-xs text-gray-500">Avg: KSh {{ number_format($stats['avg_daily_revenue'], 2) }}/day</p>
                    </div>
                </div>
            </div>
            <div class="mt-6 pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-600 mb-2">Key Insights</p>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Busiest day: {{ $stats['busiest_day']['day'] ?? 'N/A' }}
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-trophy text-yellow-500 mr-2"></i>
                        Top unit: {{ $stats['top_performing_unit']['name'] ?? 'N/A' }}
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-star text-blue-500 mr-2"></i>
                        Top vendor: {{ $stats['top_performing_vendor']['name'] ?? 'N/A' }}
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Security Rewards Section -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-text-black">Security Rewards</h2>
            <a href="{{ route('admin.rewards.index') }}" class="text-sm text-secondary-blue hover:text-blue-600">
                Manage Rewards →
            </a>
        </div>

        @php
            $todayRewards = \App\Models\Reward::getTodayRewards();
            $weekRewards = \App\Models\Reward::where('reward_date', '>=', now()->startOfWeek())->count();
            $claimedRewards = \App\Models\Reward::where('status', 'claimed')->where('reward_date', '>=', now()->startOfMonth())->count();
            $totalRewards = \App\Models\Reward::where('reward_date', '>=', now()->startOfMonth())->count();
            $todayRewardsCount = $todayRewards->count();
            $todayRewardsTotal = $todayRewards->sum('amount');
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Today's Rewards (Multiple) -->
            <div class="bg-gradient-to-r from-yellow-500 to-orange-500 rounded-lg p-4">
                <div class="text-yellow-100 text-sm uppercase tracking-wide">Today's Security Rewards</div>

                @if($todayRewardsCount > 0)
                    <div class="text-white text-2xl font-bold mt-2">{{ $todayRewardsCount }} Reward(s)</div>
                    <div class="text-yellow-100 text-sm">Total: KSh {{ number_format($todayRewardsTotal, 2) }}</div>

                    <div class="mt-3 space-y-2 max-h-40 overflow-y-auto">
                        @foreach($todayRewards as $reward)
                        <div class="bg-white/20 rounded p-2">
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="text-white text-sm font-medium">{{ $reward->employee->formal_name }}</span>
                                    <span class="text-yellow-100 text-xs ml-2">({{ $reward->unit->name }})</span>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-white text-yellow-800">
                                    {{ ucfirst($reward->status) }}
                                </span>
                            </div>
                            <div class="text-yellow-100 text-xs">{{ $reward->employee->employee_code }}</div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-white text-xl mt-2">No rewards assigned for today</div>
                    <div class="text-yellow-100 text-sm mt-1">Click "Manage Rewards" to award</div>
                @endif
            </div>

            <!-- Reward Stats -->
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-3 bg-yellow-50 rounded-lg">
                    <p class="text-2xl font-bold text-yellow-600">{{ $weekRewards }}</p>
                    <p class="text-xs text-gray-600">This Week</p>
                </div>
                <div class="text-center p-3 bg-green-50 rounded-lg">
                    <p class="text-2xl font-bold text-green-600">{{ $claimedRewards }}/{{ $totalRewards }}</p>
                    <p class="text-xs text-gray-600">Claimed This Month</p>
                </div>
                <div class="text-center p-3 bg-blue-50 rounded-lg col-span-2">
                    <p class="text-2xl font-bold text-blue-600">{{ $todayRewardsCount }}</p>
                    <p class="text-xs text-gray-600">Units Rewarded Today</p>
                </div>
            </div>
        </div>
    </div>

    <a href="{{ route('admin.employees.scan-data.export') }}"
       class="block bg-white rounded-xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition duration-150">
        <div class="flex items-center justify-between">
            <div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-3">
                    <i class="fas fa-file-export text-blue-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">Employee Scan Data Export</h3>
                <p class="text-sm text-gray-500 mt-1">Export detailed scan reports with date range</p>
            </div>
            <i class="fas fa-chevron-right text-gray-400"></i>
        </div>
    </a>

    <!-- Main Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Employees -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Employees</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $stats['total_employees'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex justify-between text-xs">
                    <span class="text-gray-500">Active</span>
                    <span class="font-medium">{{ $stats['active_employees'] }}</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-gray-500">Growth</span>
                    <span class="font-medium {{ $stats['employee_growth_rate'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $stats['employee_growth_rate'] }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Vendor Performance -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Vendor Performance</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $stats['verified_vendors'] }}/{{ $stats['total_vendors'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-store text-green-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex justify-between text-xs">
                    <span class="text-gray-500">Verification Rate</span>
                    <span class="font-medium">{{ $stats['vendor_verification_rate'] }}%</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-gray-500">Pending</span>
                    <span class="font-medium text-yellow-600">{{ $stats['pending_verifications'] }}</span>
                </div>
            </div>
        </div>

        <!-- Monthly Revenue -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Monthly Revenue</p>
                    <p class="text-2xl font-bold text-green-600 mt-2">KSh {{ number_format($stats['total_revenue_month'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex justify-between text-xs">
                    <span class="text-gray-500">This Week</span>
                    <span class="font-medium">KSh {{ number_format($stats['total_revenue_week'], 2) }}</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-gray-500">Daily Avg</span>
                    <span class="font-medium">KSh {{ number_format($stats['avg_daily_revenue'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Operational Efficiency -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Efficiency Score</p>
                    <p class="text-2xl font-bold text-text-black mt-2">
                        {{ $unitStats->first()['efficiency_score'] ?? 0 }}/100
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-orange-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-orange-600 h-2 rounded-full"
                         style="width: {{ $unitStats->first()['efficiency_score'] ?? 0 }}%"></div>
                </div>
                <div class="flex justify-between text-xs mt-2">
                    <span class="text-gray-500">Top Unit</span>
                    <span class="font-medium">{{ $unitStats->first()['name'] ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts & Detailed Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Scan Trends Chart -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-text-black">Scan Trends</h2>
                <div class="flex space-x-2" id="chartPeriodButtons">
                    <button class="px-3 py-1 text-xs bg-blue-100 text-blue-800 rounded-full period-btn" data-period="7d" onclick="load7DayChart()">7D</button>
                    <button class="px-3 py-1 text-xs bg-gray-100 text-gray-800 rounded-full period-btn" data-period="30d" onclick="load30DayChart()">30D</button>
                </div>
            </div>
            <div class="h-64" id="chartContainer">
                <canvas id="scanTrendsChart"></canvas>
            </div>
        </div>

        <!-- Unit Performance -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-text-black">Unit Performance</h2>
                <a href="" class="text-sm text-secondary-blue hover:text-blue-600">
                    View Details →
                </a>
            </div>
            <div class="space-y-4">
                @foreach($unitStats->take(3) as $unit)
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                    {{ $unit['scan_growth'] >= 0 ? 'bg-green-100' : 'bg-red-100' }}">
                            <i class="{{ $unit['scan_growth'] >= 0 ? 'fas fa-arrow-up text-green-600' : 'fas fa-arrow-down text-red-600' }}"></i>
                        </div>
                        <div>
                            <p class="font-medium text-text-black">{{ $unit['name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $unit['location'] }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold">{{ $unit['today_scans'] }} scans</p>
                        <p class="text-xs {{ $unit['scan_growth'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $unit['scan_growth'] }}% growth
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Detailed Tables Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Top Vendors -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-text-black">Top Performing Vendors</h2>
                    <span class="text-xs text-gray-500">This Month</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
<th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Scans</th>
<th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Regular (65)</th>
<th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Reward (200)</th>
<th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total Revenue</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Avg/Day</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Retention</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($topVendors as $vendor)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-secondary-blue to-blue-600 rounded-full flex items-center justify-center shadow-sm">
                                            <i class="fas fa-store text-white text-xs"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-text-black text-sm">{{ $vendor->name }}</p>
                                            <p class="text-xs text-gray-500 truncate max-w-[200px]">{{ $vendor->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
    <span class="font-semibold text-gray-900">{{ number_format($vendor->total_scans) }}</span>
</td>
<td class="px-4 py-3 text-center">
    <span class="font-semibold text-green-600">{{ number_format($vendor->regular_scans ?? 0) }}</span>
</td>
<td class="px-4 py-3 text-center">
    <span class="font-semibold text-purple-600">{{ number_format($vendor->reward_scans ?? 0) }}</span>
</td>
<td class="px-4 py-3 text-center">
    <span class="font-semibold text-green-600">KSh {{ number_format($vendor->total_revenue, 2) }}</span>
</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-sm text-gray-700">{{ number_format($vendor->avg_daily_scans, 1) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center">
                                        <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-green-500 h-2 rounded-full transition-all duration-300"
                                                 style="width: {{ min(100, $vendor->customer_retention) }}%"></div>
                                        </div>
                                        <span class="text-xs font-medium {{ $vendor->customer_retention >= 70 ? 'text-green-600' : ($vendor->customer_retention >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ number_format($vendor->customer_retention, 1) }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button class="vendor-analytics-btn px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-100 transition duration-150 shadow-sm"
                                            data-vendor-id="{{ $vendor->id }}"
                                            data-vendor-name="{{ $vendor->name }}">
                                        <i class="fas fa-chart-line mr-1"></i> View Analytics
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Department Participation -->
        <div>
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-text-black mb-4">Department Participation</h2>
                <div class="space-y-4">
                    @foreach($departmentStats->take(5) as $dept)
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium">{{ $dept->name }}</span>
                            <span class="text-sm font-semibold">{{ $dept->participation_rate }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full"
                                 style="width: {{ $dept->participation_rate }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>{{ $dept->fed_today }}/{{ $dept->total_employees }} fed today</span>
                            <span>{{ $dept->active_employees }} active</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="mt-6 bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-text-black mb-4">Quick Stats</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-3 bg-blue-50 rounded-lg">
                        <p class="text-2xl font-bold text-blue-600">{{ $stats['month_scans'] }}</p>
                        <p class="text-xs text-gray-600">Month Scans</p>
                    </div>
                    <div class="text-center p-3 bg-green-50 rounded-lg">
                        <p class="text-2xl font-bold text-green-600">{{ $stats['week_scans'] }}</p>
                        <p class="text-xs text-gray-600">Week Scans</p>
                    </div>
                    <div class="text-center p-3 bg-purple-50 rounded-lg">
                        <p class="text-2xl font-bold text-purple-600">{{ $stats['verified_vendors'] }}</p>
                        <p class="text-xs text-gray-600">Verified Vendors</p>
                    </div>
                    <div class="text-center p-3 bg-orange-50 rounded-lg">
                        <p class="text-2xl font-bold text-orange-600">{{ $stats['new_employees_this_month'] }}</p>
                        <p class="text-xs text-gray-600">New Employees</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="mt-8 bg-white rounded-xl shadow-md border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-text-black">Recent Transactions</h2>
            <a href="" class="text-sm text-secondary-blue hover:text-blue-600">
                View All Transactions →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentTransactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-medium">{{ $transaction->employee->formal_name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500">{{ $transaction->employee->employee_number ?? '' }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm">{{ $transaction->employee->department->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $transaction->employee->unit->name ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 bg-blue-100 rounded flex items-center justify-center">
                                    <i class="fas fa-store text-blue-600 text-xs"></i>
                                </div>
                                <span class="text-sm">{{ $transaction->vendor->name ?? 'Unknown' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm">{{ $transaction->meal_time }}</p>
                            <p class="text-xs text-gray-500">{{ $transaction->meal_date }}</p>
                        </td>
                       <td class="px-4 py-3">
    @if(isset($transaction->is_reward) && $transaction->is_reward)
        <p class="font-semibold text-purple-600">KSh 200.00 🎖️</p>
    @else
        <p class="font-semibold text-green-600">KSh 65.00</p>
    @endif
</td>
<td class="px-4 py-3">
    @if(isset($transaction->is_reward) && $transaction->is_reward)
        <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800"><i class="fas fa-star mr-1"></i>Reward</span>
    @else
        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800"><i class="fas fa-utensils mr-1"></i>Regular</span>
    @endif
</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-history text-3xl mb-2"></i>
                            <p>No transactions yet</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Vendor Analytics Modal -->
<div id="vendorAnalyticsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-5 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
        <!-- Modal header -->
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-2xl font-bold text-text-black" id="vendorModalTitle"></h3>
            <button class="modal-close-btn text-gray-400 hover:text-gray-600 text-3xl">&times;</button>
        </div>
        <!-- Modal body -->
        <div id="vendorAnalyticsContent" class="py-4"></div>
    </div>
</div>

<script>
// =============================================
// GLOBAL VARIABLES
// =============================================
let scanTrendsChart = null;
let timeSeriesChart = null;
let departmentChart = null;

// =============================================
// HELPER FUNCTIONS
// =============================================
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function toNumber(value, defaultValue = 0) {
    const num = parseFloat(value);
    return isNaN(num) ? defaultValue : num;
}

function toInt(value, defaultValue = 0) {
    const num = parseInt(value);
    return isNaN(num) ? defaultValue : num;
}

// =============================================
// DOCUMENT READY
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('vendorAnalyticsModal');
    const modalContent = document.getElementById('vendorAnalyticsContent');
    const closeBtns = document.querySelectorAll('.modal-close-btn');

    document.querySelectorAll('.vendor-analytics-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            loadVendorAnalytics(this.dataset.vendorId, this.dataset.vendorName);
        });
    });

    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.classList.add('hidden');
            modalContent.innerHTML = '';
        });
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
            modalContent.innerHTML = '';
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            modal.classList.add('hidden');
            modalContent.innerHTML = '';
        }
    });

    initializeDashboardChart();

    setInterval(() => {
        updateDashboardStats();
    }, 120000);
});

// =============================================
// CHART FUNCTIONS
// =============================================

function initializeDashboardChart() {
    const ctx = document.getElementById('scanTrendsChart');
    if (!ctx) {
        console.error('Canvas element not found for scan trends');
        return;
    }

    if (typeof window.dashboardTrendData === 'undefined' || !Array.isArray(window.dashboardTrendData) || window.dashboardTrendData.length === 0) {
        showNoChartData();
        return;
    }

    const trendData = window.dashboardTrendData;
    const labels = trendData.map(day => day.date || day.day || 'Unknown');
    const scans = trendData.map(day => toInt(day.scans, 0));

    if (scanTrendsChart && typeof scanTrendsChart.destroy === 'function') {
        scanTrendsChart.destroy();
    }

    try {
        scanTrendsChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Daily Scans',
                    data: scans,
                    borderColor: '#2596be',
                    backgroundColor: 'rgba(37, 150, 190, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#2596be',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { drawBorder: false }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    } catch (error) {
        console.error('Error creating dashboard chart:', error);
        showNoChartData();
    }
}

function showNoChartData() {
    const chartContainer = document.getElementById('chartContainer');
    if (chartContainer) {
        chartContainer.innerHTML = `<div class="flex items-center justify-center h-full"><div class="text-center"><i class="fas fa-chart-bar text-gray-300 text-4xl mb-3"></i><p class="text-gray-500">No chart data available</p></div></div>`;
    }
}

function load7DayChart() {
    const chartContainer = document.getElementById('chartContainer');
    if (!chartContainer.querySelector('canvas')) {
        chartContainer.innerHTML = '<canvas id="scanTrendsChart"></canvas>';
    }
    if (typeof window.dashboardTrendData !== 'undefined') {
        initializeDashboardChart();
    } else {
        showNoChartData();
    }
}

function load30DayChart() {
    const chartContainer = document.getElementById('chartContainer');
    chartContainer.innerHTML = `<div class="flex items-center justify-center h-full"><div class="text-center"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div><p class="mt-4 text-gray-600">Loading 30-day data...</p></div></div>`;

    if (window.dashboardTrendData30d && window.dashboardTrendData30d.length > 0) {
        setTimeout(() => render30DayChart(window.dashboardTrendData30d), 500);
    } else {
        fetch('/admin/analytics/trends/30d', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.trend_data) {
                render30DayChart(data.trend_data);
            } else {
                throw new Error('Failed to load data');
            }
        })
        .catch(error => {
            console.error('Error fetching 30-day data:', error);
            show30DayPlaceholder();
        });
    }
}

function render30DayChart(trendData) {
    const chartContainer = document.getElementById('chartContainer');
    chartContainer.innerHTML = '<canvas id="scanTrendsChart"></canvas>';
    const ctx = document.getElementById('scanTrendsChart');
    if (!ctx) return;

    if (scanTrendsChart && typeof scanTrendsChart.destroy === 'function') {
        scanTrendsChart.destroy();
    }

    const labels = trendData.map(day => day.date || day.day || 'Unknown');
    const scans = trendData.map(day => toInt(day.scans, 0));

    try {
        scanTrendsChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Daily Scans (30 Days)',
                    data: scans,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true },
                    x: { ticks: { maxRotation: 45, minRotation: 45, maxTicksLimit: 15 } }
                }
            }
        });
    } catch (error) {
        console.error('Error creating 30-day chart:', error);
        show30DayPlaceholder();
    }
}

function show30DayPlaceholder() {
    const chartContainer = document.getElementById('chartContainer');
    chartContainer.innerHTML = `<div class="flex items-center justify-center h-full"><div class="text-center"><i class="fas fa-chart-line text-blue-300 text-4xl mb-3"></i><p class="text-gray-500">Unable to load 30-day data</p><button onclick="load7DayChart()" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded">Back to 7-day view</button></div></div>`;
}

// =============================================
// VENDOR ANALYTICS FUNCTIONS
// =============================================

function loadVendorAnalytics(vendorId, vendorName) {
    const modal = document.getElementById('vendorAnalyticsModal');
    const modalTitle = document.getElementById('vendorModalTitle');
    const modalContent = document.getElementById('vendorAnalyticsContent');

    modalTitle.textContent = `${vendorName} - Analytics`;
    modalContent.innerHTML = `<div class="flex justify-center items-center h-64"><div class="text-center"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-secondary-blue mx-auto"></div><p class="mt-4">Loading analytics...</p></div></div>`;
    modal.classList.remove('hidden');

    fetch(`/admin/vendor/${vendorId}/analytics?period=month`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            renderVendorAnalytics(data);
        } else {
            throw new Error(data.error || 'Failed to load analytics');
        }
    })
    .catch(error => {
        console.error('Error loading vendor analytics:', error);
        modalContent.innerHTML = `<div class="text-center py-8 text-red-600">Error loading analytics. Please try again.</div>`;
    });
}

function renderVendorAnalytics(data) {
    const modalContent = document.getElementById('vendorAnalyticsContent');
    window.currentVendorData = data;

    modalContent.innerHTML = `
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 overflow-x-auto">
                <button class="tab-btn py-2 px-1 border-b-2 border-secondary-blue font-medium text-sm text-secondary-blue" data-tab="overview">Overview</button>
                <button class="tab-btn py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500" data-tab="daily">Daily Scans</button>
                <button class="tab-btn py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500" data-tab="weekly">Weekly Activity</button>
                <button class="tab-btn py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500" data-tab="charts">Charts</button>
                <button class="tab-btn py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500" data-tab="export">Export</button>
            </nav>
        </div>
        <div id="tabContent"></div>
    `;

    initializeTabs();
    loadOverviewTab();
}

function initializeTabs() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('border-secondary-blue', 'text-secondary-blue');
                b.classList.add('border-transparent', 'text-gray-500');
            });
            this.classList.remove('border-transparent', 'text-gray-500');
            this.classList.add('border-secondary-blue', 'text-secondary-blue');

            const tab = this.dataset.tab;
            if (tab === 'overview') loadOverviewTab();
            else if (tab === 'daily') loadDailyScansTab();
            else if (tab === 'weekly') loadWeeklyActivityTab();
            else if (tab === 'charts') loadChartsTab();
            else if (tab === 'export') loadExportTab();
        });
    });
}

function loadOverviewTab() {
    const data = window.currentVendorData;
    const stats = data.stats || {};
    const vendor = data.vendor || {};

    // Safely convert all values to numbers using helper functions
    const currentPeriodScans = toInt(stats.current_period_scans, 0);
    const currentPeriodRevenue = toNumber(stats.current_period_revenue, 0);
    const avgTransactionValue = toNumber(stats.avg_transaction_value, 0);
    const previousPeriodScans = toInt(stats.previous_period_scans, 0);
    const previousPeriodRevenue = toNumber(stats.previous_period_revenue, 0);
    const peakHour = stats.peak_hour || { hour: 'N/A', count: 0 };
    const topDepartments = stats.top_departments || [];
    const topCustomers = stats.top_customers || [];

    // Calculate differences safely
    const scanDiff = Math.abs(currentPeriodScans - previousPeriodScans);
    const revenueDiff = Math.abs(currentPeriodRevenue - previousPeriodRevenue);
    const scanTrend = currentPeriodScans > previousPeriodScans ? '↑' : '↓';
    const revenueTrend = currentPeriodRevenue > previousPeriodRevenue ? '↑' : '↓';
    const scanColor = currentPeriodScans > previousPeriodScans ? 'text-green-600' : 'text-red-600';
    const revenueColor = currentPeriodRevenue > previousPeriodRevenue ? 'text-green-600' : 'text-red-600';

    const content = `
        <div class="space-y-6">
            <!-- Vendor Info -->
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div><p class="text-sm text-gray-600">Vendor Name</p><p class="font-semibold">${escapeHtml(vendor.name || 'N/A')}</p></div>
                    <div><p class="text-sm text-gray-600">Location</p><p class="font-semibold">${escapeHtml(vendor.location || 'N/A')}</p></div>
                    <div><p class="text-sm text-gray-600">Contact</p><p class="font-semibold">${escapeHtml(vendor.email || 'N/A')}</p></div>
                </div>
            </div>

            <!-- Key Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-lg border shadow-sm">
                    <p class="text-sm text-gray-600">Current Period Scans</p>
                    <p class="text-2xl font-bold text-text-black">${currentPeriodScans.toLocaleString()}</p>
                    ${previousPeriodScans ? `<p class="text-xs ${scanColor}">${scanTrend} ${scanDiff} vs previous period</p>` : ''}
                </div>
                <div class="bg-white p-4 rounded-lg border shadow-sm">
                    <p class="text-sm text-gray-600">Current Period Revenue</p>
                    <p class="text-2xl font-bold text-green-600">KSh ${currentPeriodRevenue.toFixed(2)}</p>
                    ${previousPeriodRevenue ? `<p class="text-xs ${revenueColor}">${revenueTrend} KSh ${revenueDiff.toFixed(2)} vs previous period</p>` : ''}
                </div>
                <div class="bg-white p-4 rounded-lg border shadow-sm">
                    <p class="text-sm text-gray-600">Avg. Transaction</p>
                    <p class="text-2xl font-bold text-text-black">KSh ${avgTransactionValue.toFixed(2)}</p>
                    <p class="text-xs text-gray-500">Per scan</p>
                </div>
                <div class="bg-white p-4 rounded-lg border shadow-sm">
                    <p class="text-sm text-gray-600">Peak Hour</p>
                    <p class="text-2xl font-bold text-text-black">${escapeHtml(peakHour.hour)}</p>
                    <p class="text-xs text-gray-500">${toInt(peakHour.count, 0)} scans</p>
                </div>
            </div>

            ${topDepartments.length > 0 ? `
            <div class="bg-white p-4 rounded-lg border shadow-sm">
                <h4 class="font-semibold mb-3">Top Departments</h4>
                <div class="space-y-2">
                    ${topDepartments.map(dept => {
                        const revenue = toNumber(dept.revenue, 0);
                        const scans = toInt(dept.scans, 0);
                        return `<div class="flex justify-between items-center p-2 hover:bg-gray-50 rounded"><span>${escapeHtml(dept.department || 'Unknown')}</span><div class="flex items-center space-x-4"><span class="text-sm text-gray-600">${scans.toLocaleString()} scans</span><span class="text-sm font-semibold text-green-600">KSh ${revenue.toFixed(2)}</span></div></div>`;
                    }).join('')}
                </div>
            </div>
            ` : ''}

            ${topCustomers.length > 0 ? `
            <div class="bg-white p-4 rounded-lg border shadow-sm">
                <h4 class="font-semibold mb-3">Top Customers</h4>
                <div class="space-y-2">
                    ${topCustomers.slice(0, 5).map(customer => {
                        const visits = toInt(customer.visits, 0);
                        const totalSpent = toNumber(customer.total_spent, 0);
                        return `<div class="flex justify-between items-center p-2 hover:bg-gray-50 rounded"><div><p class="font-medium">${escapeHtml(customer.formal_name || 'Unknown')}</p><p class="text-xs text-gray-500">${escapeHtml(customer.employee_number || '')}</p></div><div class="text-right"><p class="text-sm font-semibold">${visits.toLocaleString()} visits</p><p class="text-xs text-green-600">KSh ${totalSpent.toFixed(2)}</p></div></div>`;
                    }).join('')}
                </div>
            </div>
            ` : ''}
        </div>
    `;

    document.getElementById('tabContent').innerHTML = content;
}

function loadDailyScansTab() {
    const data = window.currentVendorData;
    const dailyScans = data.daily_scans || {};

    let content = `
        <div class="space-y-6">
            <div class="bg-white p-4 rounded-lg border shadow-sm">
                <h4 class="font-semibold text-lg mb-2">Employee Daily Scans</h4>
                <p class="text-sm text-gray-500">Period: ${escapeHtml(data.date_range?.start || 'N/A')} to ${escapeHtml(data.date_range?.end || 'N/A')}</p>
                <p class="text-xs text-gray-400 mt-1">Showing detailed scan records with employee information and scan times</p>
            </div>
    `;

    if (Object.keys(dailyScans).length === 0) {
        content += `<div class="text-center py-8 text-gray-500">
            <i class="fas fa-calendar-day text-4xl mb-3"></i>
            <p>No scans found for this period</p>
        </div>`;
    } else {
        // Sort dates in descending order (most recent first)
        const sortedDates = Object.keys(dailyScans).sort().reverse();

        sortedDates.forEach((date) => {
            const transactions = dailyScans[date];

            // Calculate totals
            let totalAmount = 0;
            let regularScans = 0;
            let rewardScans = 0;

            transactions.forEach(t => {
                totalAmount += t.amount;
                if (t.is_reward) {
                    rewardScans++;
                } else {
                    regularScans++;
                }
            });

            const totalScans = transactions.length;

            // Format date nicely
            const formattedDate = new Date(date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            content += `
                <div class="border rounded-lg overflow-hidden shadow-sm">
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-3 flex justify-between items-center cursor-pointer hover:from-gray-100 hover:to-gray-200 transition-all" onclick="toggleDailyDate('${date}')">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-calendar-alt text-blue-600"></i>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-800">${formattedDate}</span>
                                <div class="text-sm text-gray-500">
                                    <span class="inline-flex items-center">
                                        <i class="fas fa-users mr-1"></i>${totalScans} scans
                                    </span>
                                    <span class="mx-2">•</span>
                                    <span class="text-green-600 font-medium">KSh ${totalAmount.toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-right hidden md:block">
                                <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">Regular: ${regularScans}</span>
                                <span class="text-xs px-2 py-1 bg-purple-100 text-purple-700 rounded-full ml-2">Reward: ${rewardScans}</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200" id="icon-${date}"></i>
                        </div>
                    </div>
                    <div id="details-${date}" class="hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department / Unit</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scan Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
            `;

            transactions.forEach((transaction) => {
                const type = transaction.is_reward ? 'Reward 🎖️' : 'Regular 🍽️';
                const typeClass = transaction.is_reward ? 'text-purple-600' : 'text-green-600';
                const amount = transaction.amount;

                content += `
                    <tr class="hover:bg-blue-50 transition duration-150">
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-user text-blue-600 text-xs"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 text-sm">${escapeHtml(transaction.employee_name)}</p>
                                    <p class="text-xs text-gray-500">${escapeHtml(transaction.employee_code)}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm text-gray-800">${escapeHtml(transaction.department)}</p>
                            <p class="text-xs text-gray-500">${escapeHtml(transaction.unit)}</p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 bg-purple-100 rounded flex items-center justify-center">
                                    <i class="fas fa-store text-purple-600 text-xs"></i>
                                </div>
                                <span class="text-sm text-gray-800 font-medium">${escapeHtml(transaction.vendor_name)}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <i class="fas fa-clock text-gray-400 text-xs mr-2"></i>
                                <span class="text-sm text-gray-700">${transaction.scan_time}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${typeClass} bg-opacity-10">
                                ${type}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-semibold ${typeClass}">KSh ${amount.toFixed(2)}</span>
                        </td>
                    </tr>
                `;
            });

            content += `
                                </tbody>
                            </table>
                        </div>
                        <div class="bg-gray-50 px-4 py-2 text-right border-t">
                            <span class="text-sm text-gray-600">Total for ${formattedDate}:</span>
                            <span class="font-semibold text-green-600 ml-2">KSh ${totalAmount.toFixed(2)}</span>
                            <span class="text-gray-400 mx-2">|</span>
                            <span class="text-sm text-gray-600">${totalScans} scans</span>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    content += `</div>`;
    document.getElementById('tabContent').innerHTML = content;
}

// Add this helper function if not already present
function toggleDailyDate(date) {
    const detailsDiv = document.getElementById(`details-${date}`);
    const icon = document.getElementById(`icon-${date}`);

    if (detailsDiv) {
        detailsDiv.classList.toggle('hidden');
        if (icon) {
            icon.classList.toggle('fa-chevron-down');
            icon.classList.toggle('fa-chevron-up');
        }
    }
}

function loadWeeklyActivityTab() {
    const weekly = window.currentVendorData.weekly_activity || [];
    document.getElementById('tabContent').innerHTML = `
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left">Day</th><th class="px-4 py-2 text-left">Scans</th><th class="px-4 py-2 text-left">Revenue</th></tr></thead>
            <tbody>${weekly.map(w => `<tr><td class="px-4 py-2">${escapeHtml(w.day)}</td><td class="px-4 py-2">${toInt(w.scans, 0).toLocaleString()}</td><td class="px-4 py-2">KSh ${toNumber(w.revenue, 0).toFixed(2)}</td></tr>`).join('')}</tbody>
        </table>
    `;
}

function loadChartsTab() {
    const data = window.currentVendorData;
    const hasData = (data.time_series_data?.length > 0) || (data.department_distribution?.length > 0);
    document.getElementById('tabContent').innerHTML = `
        <div class="space-y-6">
            ${!hasData ? '<div class="text-center py-12 text-gray-500">No chart data available</div>' : `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><h5 class="font-medium mb-2">Scans Over Time</h5><div class="h-64"><canvas id="timeSeriesChart"></canvas></div></div>
                <div><h5 class="font-medium mb-2">Department Distribution</h5><div class="h-64"><canvas id="departmentChart"></canvas></div></div>
            </div>`}
        </div>
    `;
    if (hasData) {
        if (data.time_series_data?.length) initializeTimeSeriesChart(data.time_series_data);
        if (data.department_distribution?.length) initializeDepartmentChart(data.department_distribution);
    }
}

function initializeTimeSeriesChart(timeSeriesData) {
    const ctx = document.getElementById('timeSeriesChart');
    if (!ctx) return;
    if (timeSeriesChart && typeof timeSeriesChart.destroy === 'function') timeSeriesChart.destroy();
    timeSeriesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: timeSeriesData.map(d => d.date ? new Date(d.date).toLocaleDateString() : d.period),
            datasets: [{ label: 'Scans', data: timeSeriesData.map(d => toInt(d.scans, 0)), borderColor: '#2596be', fill: true }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}

function initializeDepartmentChart(departmentData) {
    const ctx = document.getElementById('departmentChart');
    if (!ctx) return;
    if (departmentChart && typeof departmentChart.destroy === 'function') departmentChart.destroy();
    const sorted = [...departmentData].sort((a, b) => (toInt(b.scans, 0) - toInt(a.scans, 0))).slice(0, 5);
    departmentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: sorted.map(d => d.department || 'Unknown'),
            datasets: [{ label: 'Scans', data: sorted.map(d => toInt(d.scans, 0)), backgroundColor: '#2596be' }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}

function loadExportTab() {
    const vendor = window.currentVendorData.vendor;
    const months = [];
    for (let i = 0; i < 12; i++) {
        const d = new Date();
        d.setMonth(d.getMonth() - i);
        months.push({ value: `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`, name: d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) });
    }

    document.getElementById('tabContent').innerHTML = `
        <div class="space-y-6">
            <div class="p-4 bg-gray-50 rounded-lg"><label class="font-medium block mb-2">Custom Date Range</label><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><input type="date" id="customStartDate" class="border rounded p-2"><input type="date" id="customEndDate" class="border rounded p-2"><button onclick="loadCustomRangeData()" class="bg-blue-500 text-white rounded p-2 hover:bg-blue-600">Load Range Data</button></div><div id="customRangeStatus" class="mt-2 text-sm"></div></div>
            <div class="p-4 bg-gray-50 rounded-lg"><label class="font-medium block mb-2">Select Month</label><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><select id="exportMonth" class="border rounded p-2"><option value="">Select Month</option>${months.map(m => `<option value="${m.value}">${m.name}</option>`).join('')}</select><button onclick="loadCustomMonthData()" class="bg-blue-500 text-white rounded p-2 hover:bg-blue-600">Load Month Data</button></div><div id="monthDataStatus" class="mt-2 text-sm"></div></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="border rounded-lg p-4"><h4 class="font-semibold mb-3">Excel Export</h4><div><label><input type="radio" name="excelPeriod" value="current" checked> Current Month</label></div><div><label><input type="radio" name="excelPeriod" value="selected"> Selected Month</label></div><div><label><input type="radio" name="excelPeriod" value="custom"> Custom Range</label></div><button onclick="exportVendorData('excel')" class="w-full bg-green-500 text-white rounded p-2 mt-3 hover:bg-green-600">Export to Excel</button></div>
            <div class="border rounded-lg p-4"><h4 class="font-semibold mb-3">PDF Report</h4><div><label><input type="radio" name="pdfPeriod" value="current" checked> Current Month</label></div><div><label><input type="radio" name="pdfPeriod" value="selected"> Selected Month</label></div><div><label><input type="radio" name="pdfPeriod" value="custom"> Custom Range</label></div><button onclick="exportVendorData('pdf')" class="w-full bg-red-500 text-white rounded p-2 mt-3 hover:bg-red-600">Export to PDF</button></div></div>
            <div id="dataPreviewSection" class="hidden border rounded-lg p-4"><h4 class="font-semibold mb-2">Data Preview</h4><div id="dataPreviewContent"></div></div>
        </div>
    `;
}

function loadCustomRangeData() {
    const startDate = document.getElementById('customStartDate').value;
    const endDate = document.getElementById('customEndDate').value;
    const statusEl = document.getElementById('customRangeStatus');

    if (!startDate || !endDate) {
        statusEl.innerHTML = '<span class="text-red-600">Please select both dates</span>';
        return;
    }
    if (startDate > endDate) {
        statusEl.innerHTML = '<span class="text-red-600">Start date cannot be after end date</span>';
        return;
    }

    statusEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

    fetch(`/admin/vendor/${window.currentVendorData.vendor.id}/analytics?period=custom&start_date=${startDate}&end_date=${endDate}`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusEl.innerHTML = '<span class="text-green-600">Data loaded successfully</span>';
            const previewRevenue = toNumber(data.stats?.current_period_revenue, 0);
            document.getElementById('dataPreviewContent').innerHTML = `<div class="bg-blue-50 p-3 rounded"><div class="grid grid-cols-2 md:grid-cols-4 gap-3"><div><b>Scans:</b> ${toInt(data.stats?.current_period_scans, 0).toLocaleString()}</div><div><b>Revenue:</b> KSh ${previewRevenue.toFixed(2)}</div><div><b>Avg per Scan:</b> KSh ${toNumber(data.stats?.avg_transaction_value, 0).toFixed(2)}</div><div><b>Peak Hour:</b> ${escapeHtml(data.stats?.peak_hour?.hour || 'N/A')}</div></div></div>`;
            document.getElementById('dataPreviewSection').classList.remove('hidden');
        } else {
            throw new Error(data.error || 'Failed to load');
        }
    })
    .catch(error => {
        statusEl.innerHTML = '<span class="text-red-600">Failed to load data</span>';
        console.error(error);
    });
}

function loadCustomMonthData() {
    const monthSelect = document.getElementById('exportMonth');
    const statusEl = document.getElementById('monthDataStatus');

    if (!monthSelect.value) {
        alert('Please select a month first');
        return;
    }

    const [year, month] = monthSelect.value.split('-');
    const monthName = monthSelect.options[monthSelect.selectedIndex].text;
    const lastDayOfMonth = new Date(parseInt(year), parseInt(month), 0).getDate();

    statusEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

    fetch(`/admin/vendor/${window.currentVendorData.vendor.id}/analytics/month/${year}/${month}`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusEl.innerHTML = `<span class="text-green-600">${monthName} data loaded (${lastDayOfMonth} days)</span>`;
            const summaryRevenue = toNumber(data.data?.summary?.total_revenue, 0);
            let preview = document.getElementById('monthDataPreview');
            if (!preview) {
                preview = document.createElement('div');
                preview.id = 'monthDataPreview';
                preview.className = 'mt-4 p-3 bg-blue-50 rounded';
                document.getElementById('tabContent').appendChild(preview);
            }
            preview.innerHTML = `<b>${escapeHtml(data.data.month)}</b> - Scans: ${toInt(data.data.summary.total_scans, 0).toLocaleString()} | Revenue: KSh ${summaryRevenue.toFixed(2)} | Days: ${toInt(data.data.summary.days_in_month, 0)}`;
        } else {
            throw new Error(data.error || 'Failed to load');
        }
    })
    .catch(error => {
        statusEl.innerHTML = '<span class="text-red-600">Failed to load month data</span>';
        console.error(error);
    });
}

function exportVendorData(format) {
    const vendorId = window.currentVendorData?.vendor?.id;
    if (!vendorId) {
        alert('No vendor data available');
        return;
    }

    let startDate = '', endDate = '';

    if (format === 'excel') {
        const excelPeriod = document.querySelector('input[name="excelPeriod"]:checked')?.value;
        if (excelPeriod === 'selected') {
            const month = document.getElementById('exportMonth').value;
            if (!month) { alert('Please select a month first'); return; }
            const [year, monthNum] = month.split('-');
            startDate = `${year}-${monthNum}-01`;
            const lastDay = new Date(parseInt(year), parseInt(monthNum), 0).getDate();
            endDate = `${year}-${monthNum}-${lastDay}`;
        } else if (excelPeriod === 'custom') {
            startDate = document.getElementById('customStartDate')?.value;
            endDate = document.getElementById('customEndDate')?.value;
            if (!startDate || !endDate) { alert('Please select custom date range first'); return; }
            if (startDate > endDate) { alert('Start date cannot be after end date'); return; }
        }
    } else if (format === 'pdf') {
        const pdfPeriod = document.querySelector('input[name="pdfPeriod"]:checked')?.value;
        if (pdfPeriod === 'selected') {
            const month = document.getElementById('exportMonth').value;
            if (!month) { alert('Please select a month first'); return; }
            const [year, monthNum] = month.split('-');
            startDate = `${year}-${monthNum}-01`;
            const lastDay = new Date(parseInt(year), parseInt(monthNum), 0).getDate();
            endDate = `${year}-${monthNum}-${lastDay}`;
        } else if (pdfPeriod === 'custom') {
            startDate = document.getElementById('customStartDate')?.value;
            endDate = document.getElementById('customEndDate')?.value;
            if (!startDate || !endDate) { alert('Please select custom date range first'); return; }
            if (startDate > endDate) { alert('Start date cannot be after end date'); return; }
        }
    }

    let url = `/admin/vendor/${vendorId}/analytics/export?format=${format}`;
    if (startDate && endDate) {
        url += `&period=custom&start_date=${startDate}&end_date=${endDate}`;
    } else {
        url += '&period=month';
    }
    window.open(url, '_blank');
}

async function updateDashboardStats() {
    try {
        const response = await fetch('/admin/dashboard/stats');
        const data = await response.json();
        if (data.success) {
            const todayScansEl = document.getElementById('today_scans');
            const revenueEl = document.getElementById('total_revenue_today');
            const participationEl = document.getElementById('employee_participation_rate');
            if (todayScansEl) todayScansEl.textContent = data.stats.today_scans;
            if (revenueEl) revenueEl.textContent = 'KSh ' + data.stats.total_revenue_today.toLocaleString();
            if (participationEl) participationEl.textContent = data.stats.employee_participation_rate + '%';
        }
    } catch (error) {
        console.error('Failed to update dashboard stats:', error);
    }
}
</script>
@endsection
