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
                <a href="{{ route('admin.analytics') }}" class="text-sm text-secondary-blue hover:text-blue-600">
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Scans</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg/Day</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Retention</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($topVendors as $vendor)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-secondary-blue rounded-full flex items-center justify-center">
                                            <i class="fas fa-store text-white text-xs"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-text-black text-sm">{{ $vendor->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $vendor->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold">{{ $vendor->total_scans }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-green-600">KSh {{ number_format($vendor->total_revenue, 2) }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm">{{ $vendor->avg_daily_scans }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-green-600 h-2 rounded-full"
                                                 style="width: {{ $vendor->customer_retention }}%"></div>
                                        </div>
                                        <span class="text-xs">{{ $vendor->customer_retention }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <button class="vendor-analytics-btn px-3 py-1 bg-blue-100 text-blue-800 text-xs rounded hover:bg-blue-200"
                                            data-vendor-id="{{ $vendor->id }}"
                                            data-vendor-name="{{ $vendor->name }}">
                                        View Analytics
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
            <a href="{{ route('admin.analytics') }}" class="text-sm text-secondary-blue hover:text-blue-600">
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
                            <p class="font-semibold text-green-600">KSh {{ $transaction->amount }}</p>
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
        <div id="vendorAnalyticsContent" class="py-4">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('vendorAnalyticsModal');
    const modalTitle = document.getElementById('vendorModalTitle');
    const modalContent = document.getElementById('vendorAnalyticsContent');
    const closeBtns = document.querySelectorAll('.modal-close-btn');

    // Get CSRF token for AJAX requests
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Open modal when clicking on vendor analytics buttons
    document.querySelectorAll('.vendor-analytics-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const vendorId = this.dataset.vendorId;
            const vendorName = this.dataset.vendorName;

            loadVendorAnalytics(vendorId, vendorName);
        });
    });

    // Close modal
    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.classList.add('hidden');
            modalContent.innerHTML = '';
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
            modalContent.innerHTML = '';
        }
    });

    // Escape key to close modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            modal.classList.add('hidden');
            modalContent.innerHTML = '';
        }
    });

    // Initialize dashboard chart with 7-day data
    initializeDashboardChart();

    // Auto-refresh every 2 minutes for real-time updates
    setInterval(() => {
        updateDashboardStats();
    }, 120000);
});

// =============================================
// CHART FUNCTIONS - UPDATED FOR 30 DAYS
// =============================================

// Initialize main dashboard chart with data from PHP
function initializeDashboardChart() {
    const ctx = document.getElementById('scanTrendsChart');
    if (!ctx) {
        console.error('Canvas element not found for scan trends');
        return;
    }

    // Use 7-day data by default
    if (typeof window.dashboardTrendData === 'undefined' || !Array.isArray(window.dashboardTrendData) || window.dashboardTrendData.length === 0) {
        console.warn('No valid trend data');
        showNoChartData();
        return;
    }

    const trendData = window.dashboardTrendData;

    // Prepare data
    const labels = trendData.map(day => day.date || day.day || 'Unknown');
    const scans = trendData.map(day => day.scans || 0);

    // Update button states
    document.querySelectorAll('.period-btn').forEach(btn => {
        if (btn.dataset.period === '7d') {
            btn.classList.remove('bg-gray-100', 'text-gray-800');
            btn.classList.add('bg-blue-100', 'text-blue-800');
        } else {
            btn.classList.remove('bg-blue-100', 'text-blue-800');
            btn.classList.add('bg-gray-100', 'text-gray-800');
        }
    });

    // Check if we already have a chart instance and destroy it properly
    if (window.scanTrendsChart && typeof window.scanTrendsChart.destroy === 'function') {
        window.scanTrendsChart.destroy();
    }

    try {
        window.scanTrendsChart = new Chart(ctx.getContext('2d'), {
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
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Scans: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                if (Math.floor(value) === value) {
                                    return value;
                                }
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        console.log('Dashboard chart created successfully');
    } catch (error) {
        console.error('Error creating dashboard chart:', error);
        showNoChartData();
    }
}

// Show placeholder when no chart data
function showNoChartData() {
    const chartContainer = document.getElementById('chartContainer');
    if (chartContainer) {
        chartContainer.innerHTML = `
            <div class="flex items-center justify-center h-full">
                <div class="text-center">
                    <i class="fas fa-chart-bar text-gray-300 text-4xl mb-3"></i>
                    <p class="text-gray-500">No chart data available</p>
                </div>
            </div>
        `;
    }
}

// Load 7-day chart
function load7DayChart() {
    console.log('Loading 7-day chart...');

    // Recreate canvas if it was replaced
    const chartContainer = document.getElementById('chartContainer');
    if (!chartContainer.querySelector('canvas')) {
        chartContainer.innerHTML = '<canvas id="scanTrendsChart"></canvas>';
    }

    // Update button states
    document.querySelectorAll('.period-btn').forEach(btn => {
        if (btn.dataset.period === '7d') {
            btn.classList.remove('bg-gray-100', 'text-gray-800');
            btn.classList.add('bg-blue-100', 'text-blue-800');
        } else {
            btn.classList.remove('bg-blue-100', 'text-blue-800');
            btn.classList.add('bg-gray-100', 'text-gray-800');
        }
    });

    // Re-initialize the chart with 7-day data
    if (typeof window.dashboardTrendData !== 'undefined') {
        initializeDashboardChart();
    } else {
        showNoChartData();
    }
}

// Load 30-day chart
function load30DayChart() {
    console.log('Loading 30-day chart...');

    // Show loading state
    const chartContainer = document.getElementById('chartContainer');
    chartContainer.innerHTML = `
        <div class="flex items-center justify-center h-full">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
                <p class="mt-4 text-gray-600">Loading 30-day data...</p>
                <p class="text-sm text-gray-400 mt-2">Fetching analytics for the last 30 days</p>
            </div>
        </div>
    `;

    // Update button states
    document.querySelectorAll('.period-btn').forEach(btn => {
        if (btn.dataset.period === '30d') {
            btn.classList.remove('bg-gray-100', 'text-gray-800');
            btn.classList.add('bg-blue-100', 'text-blue-800');
        } else {
            btn.classList.remove('bg-blue-100', 'text-blue-800');
            btn.classList.add('bg-gray-100', 'text-gray-800');
        }
    });

    // Check if we already have 30-day data from PHP
    if (typeof window.dashboardTrendData30d !== 'undefined' &&
        Array.isArray(window.dashboardTrendData30d) &&
        window.dashboardTrendData30d.length > 0) {

        // Use existing 30-day data
        setTimeout(() => {
            render30DayChart(window.dashboardTrendData30d);
        }, 500);
    } else {
        // Fetch 30-day data from server
        fetch30DayData();
    }
}

// Fetch 30-day data from server
function fetch30DayData() {
    fetch('/admin/analytics/trends/30d', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.trend_data) {
            render30DayChart(data.trend_data);
        } else {
            throw new Error(data.error || 'Failed to load data');
        }
    })
    .catch(error => {
        console.error('Error fetching 30-day data:', error);
        show30DayPlaceholder();
    });
}

// Render 30-day chart with data
function render30DayChart(trendData) {
    const chartContainer = document.getElementById('chartContainer');
    chartContainer.innerHTML = '<canvas id="scanTrendsChart"></canvas>';

    const ctx = document.getElementById('scanTrendsChart');
    if (!ctx) return;

    const labels = trendData.map(day => day.date || day.day || 'Unknown');
    const scans = trendData.map(day => day.scans || 0);

    // Destroy existing chart if it exists
    if (window.scanTrendsChart && typeof window.scanTrendsChart.destroy === 'function') {
        window.scanTrendsChart.destroy();
    }

    try {
        window.scanTrendsChart = new Chart(ctx.getContext('2d'), {
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
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Scans: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                if (Math.floor(value) === value) {
                                    return value;
                                }
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            maxTicksLimit: 15
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating 30-day chart:', error);
        show30DayPlaceholder();
    }
}

// Show placeholder for 30-day chart
function show30DayPlaceholder() {
    const chartContainer = document.getElementById('chartContainer');
    chartContainer.innerHTML = `
        <div class="flex items-center justify-center h-full">
            <div class="text-center">
                <i class="fas fa-chart-line text-blue-300 text-4xl mb-3"></i>
                <p class="text-gray-500">30-day analytics</p>
                <p class="text-sm text-gray-400 mt-1 mb-4">Unable to load 30-day data</p>
                <button onclick="load7DayChart()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm transition duration-150">
                    <i class="fas fa-arrow-left mr-2"></i> Back to 7-day view
                </button>
            </div>
        </div>
    `;
}

// =============================================
// VENDOR ANALYTICS FUNCTIONS
// =============================================

function loadVendorAnalytics(vendorId, vendorName) {
    console.log('Loading analytics for vendor:', vendorId, vendorName);

    const modal = document.getElementById('vendorAnalyticsModal');
    const modalTitle = document.getElementById('vendorModalTitle');
    const modalContent = document.getElementById('vendorAnalyticsContent');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    modalTitle.textContent = `${vendorName} - Analytics`;
    modalContent.innerHTML = `
        <div class="flex justify-center items-center h-64">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-secondary-blue mx-auto"></div>
                <p class="mt-4 text-gray-600">Loading analytics...</p>
                <p class="text-xs text-gray-400 mt-2">Vendor ID: ${vendorId}</p>
            </div>
        </div>
    `;

    modal.classList.remove('hidden');

    const url = `/admin/vendor/${vendorId}/analytics?period=month&debug=true`;

    console.log('Fetching from URL:', url);

    fetch(url, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        }
    })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.json().then(err => {
                    console.error('Response error:', err);
                    throw new Error(err.error || `HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Analytics data received:', data);
            if (data.success) {
                renderVendorAnalytics(data);
            } else {
                console.error('API returned success:false', data);
                showError(data.error || 'Failed to load analytics data');
            }
        })
        .catch(error => {
            console.error('Error loading vendor analytics:', error);
            showError('Error loading analytics. Please try again.');
        });
}

function showError(message) {
    const modalContent = document.getElementById('vendorAnalyticsContent');
    modalContent.innerHTML = `
        <div class="text-center py-8">
            <i class="fas fa-exclamation-triangle text-yellow-500 text-3xl mb-3"></i>
            <p class="text-gray-600">${message}</p>
            <button onclick="location.reload()" class="mt-4 px-4 py-2 bg-secondary-blue text-white rounded hover:bg-blue-600">
                Retry
            </button>
        </div>
    `;
}

function renderVendorAnalytics(data) {
    const vendor = data.vendor;
    const stats = data.stats;
    const modalContent = document.getElementById('vendorAnalyticsContent');

    modalContent.innerHTML = `
        <!-- Analytics Tabs -->
        <div class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 overflow-x-auto">
                    <button class="tab-btn py-2 px-1 border-b-2 border-secondary-blue font-medium text-sm text-secondary-blue whitespace-nowrap"
                            data-tab="overview">
                        Overview
                    </button>
                    <button class="tab-btn py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap"
                            data-tab="daily">
                        Daily Scans
                    </button>
                    <button class="tab-btn py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap"
                            data-tab="weekly">
                        Weekly Activity
                    </button>
                    <button class="tab-btn py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap"
                            data-tab="charts">
                        Charts & Trends
                    </button>
                    <button class="tab-btn py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap"
                            data-tab="export">
                        Export & Share
                    </button>
                </nav>
            </div>
        </div>

        <!-- Tab Content -->
        <div id="tabContent">
            <!-- Overview tab will be loaded by default -->
        </div>
    `;

    // Store data globally for use in tab functions
    window.currentVendorData = data;

    // Initialize tab switching
    initializeTabs();
    // Load overview by default
    loadOverviewTab();
}

function initializeTabs() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active tab
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('border-secondary-blue', 'text-secondary-blue');
                b.classList.add('border-transparent', 'text-gray-500');
            });

            this.classList.remove('border-transparent', 'text-gray-500');
            this.classList.add('border-secondary-blue', 'text-secondary-blue');

            // Load tab content
            const tab = this.dataset.tab;
            switch(tab) {
                case 'overview':
                    loadOverviewTab();
                    break;
                case 'daily':
                    loadDailyScansTab();
                    break;
                case 'weekly':
                    loadWeeklyActivityTab();
                    break;
                case 'charts':
                    loadChartsTab();
                    break;
                case 'export':
                    loadExportTab();
                    break;
            }
        });
    });
}

function loadOverviewTab() {
    const data = window.currentVendorData;
    const stats = data.stats;
    const vendor = data.vendor;

    // Safely handle potentially undefined data
    const peakHour = stats.peak_hour || { hour: 'N/A', count: 0 };
    const topDepartments = stats.top_departments || [];
    const topCustomers = stats.top_customers || [];

    const content = `
        <div class="space-y-6">
            <!-- Vendor Info -->
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Vendor Name</p>
                        <p class="font-semibold">${vendor.name || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Location</p>
                        <p class="font-semibold">${vendor.location || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Contact</p>
                        <p class="font-semibold">${vendor.email || 'N/A'}</p>
                    </div>
                </div>
            </div>

            <!-- Key Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-lg border shadow-sm">
                    <p class="text-sm text-gray-600">Current Period Scans</p>
                    <p class="text-2xl font-bold text-text-black">${stats.current_period_scans || 0}</p>
                    ${stats.previous_period_scans ? `
                    <p class="text-xs ${stats.current_period_scans > stats.previous_period_scans ? 'text-green-600' : 'text-red-600'}">
                        ${stats.current_period_scans > stats.previous_period_scans ? '↑' : '↓'}
                        ${Math.abs((stats.current_period_scans || 0) - (stats.previous_period_scans || 0))} vs previous period
                    </p>
                    ` : ''}
                </div>

                <div class="bg-white p-4 rounded-lg border shadow-sm">
                    <p class="text-sm text-gray-600">Current Period Revenue</p>
                    <p class="text-2xl font-bold text-green-600">KSh ${parseFloat(stats.current_period_revenue || 0).toFixed(2)}</p>
                    ${stats.previous_period_revenue ? `
                    <p class="text-xs ${stats.current_period_revenue > stats.previous_period_revenue ? 'text-green-600' : 'text-red-600'}">
                        ${stats.current_period_revenue > stats.previous_period_revenue ? '↑' : '↓'}
                        KSh ${Math.abs((parseFloat(stats.current_period_revenue || 0) - parseFloat(stats.previous_period_revenue || 0))).toFixed(2)}
                    </p>
                    ` : ''}
                </div>

                <div class="bg-white p-4 rounded-lg border shadow-sm">
                    <p class="text-sm text-gray-600">Avg. Transaction</p>
                    <p class="text-2xl font-bold text-text-black">KSh ${parseFloat(stats.avg_transaction_value || 0).toFixed(2)}</p>
                    <p class="text-xs text-gray-500">Per scan</p>
                </div>

                <div class="bg-white p-4 rounded-lg border shadow-sm">
                    <p class="text-sm text-gray-600">Peak Hour</p>
                    <p class="text-2xl font-bold text-text-black">${peakHour.hour}</p>
                    <p class="text-xs text-gray-500">${peakHour.count || 0} scans</p>
                </div>
            </div>

            <!-- Top Departments -->
            ${topDepartments.length > 0 ? `
            <div class="bg-white p-4 rounded-lg border shadow-sm">
                <h4 class="font-semibold mb-3">Top Departments</h4>
                <div class="space-y-2">
                    ${topDepartments.map(dept => {
                        const revenue = parseFloat(dept.revenue) || 0;
                        const scans = parseInt(dept.scans) || 0;
                        const departmentName = dept.department || 'Unknown';

                        return `
                            <div class="flex justify-between items-center p-2 hover:bg-gray-50 rounded">
                                <span>${departmentName}</span>
                                <div class="flex items-center space-x-4">
                                    <span class="text-sm text-gray-600">${scans} scans</span>
                                    <span class="text-sm font-semibold text-green-600">KSh ${revenue.toFixed(2)}</span>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
            ` : ''}

            <!-- Top Customers -->
            ${topCustomers.length > 0 ? `
            <div class="bg-white p-4 rounded-lg border shadow-sm">
                <h4 class="font-semibold mb-3">Top Customers</h4>
                <div class="space-y-2">
                    ${topCustomers.slice(0, 5).map(customer => {
                        const visits = parseInt(customer.visits) || 0;
                        const totalSpent = parseFloat(customer.total_spent) || 0;
                        const avgSpent = parseFloat(customer.avg_spent) || 0;
                        const formalName = customer.formal_name || 'Unknown';
                        const employeeNumber = customer.employee_number || '';

                        return `
                            <div class="flex justify-between items-center p-2 hover:bg-gray-50 rounded">
                                <div>
                                    <p class="font-medium">${formalName}</p>
                                    <p class="text-xs text-gray-500">${employeeNumber}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold">${visits} visits</p>
                                    <p class="text-xs text-green-600">KSh ${totalSpent.toFixed(2)}</p>
                                </div>
                            </div>
                        `;
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

    let content = '<div class="space-y-6">';

    // Date filter
    content += `
        <div class="bg-white p-4 rounded-lg border shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h4 class="font-semibold">Daily Scans</h4>
                    <p class="text-sm text-gray-500">Showing scans from ${data.date_range?.start || 'N/A'} to ${data.date_range?.end || 'N/A'}</p>
                </div>
            </div>
        </div>
    `;

    // Daily scans table
    if (Object.keys(dailyScans).length > 0) {
        Object.entries(dailyScans).forEach(([date, transactions]) => {
            const totalAmount = Array.isArray(transactions)
                ? transactions.reduce((sum, t) => sum + (parseFloat(t.amount) || 0), 0)
                : 0;

            content += `
                <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 border-b">
                        <h5 class="font-semibold">${new Date(date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</h5>
                        <p class="text-sm text-gray-500">${Array.isArray(transactions) ? transactions.length : 0} scans • KSh ${totalAmount.toFixed(2)} total</p>
                    </div>
                    ${Array.isArray(transactions) && transactions.length > 0 ? `
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                ${transactions.map(transaction => {
                                    const amount = parseFloat(transaction.amount) || 0;
                                    const employeeName = transaction.employee?.formal_name || 'Unknown';
                                    const employeeNumber = transaction.employee?.employee_number || '';
                                    const departmentName = transaction.employee?.department?.name || 'N/A';
                                    const unitName = transaction.employee?.unit?.name || '';
                                    const mealTime = transaction.meal_time || '';

                                    return `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <p class="font-medium">${employeeName}</p>
                                                <p class="text-xs text-gray-500">${employeeNumber}</p>
                                            </td>
                                            <td class="px-4 py-3">
                                                <p class="text-sm">${departmentName}</p>
                                                <p class="text-xs text-gray-500">${unitName}</p>
                                            </td>
                                            <td class="px-4 py-3">
                                                <p class="text-sm">${mealTime}</p>
                                            </td>
                                            <td class="px-4 py-3">
                                                <p class="font-semibold text-green-600">KSh ${amount.toFixed(2)}</p>
                                            </td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                    ` : `
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-clipboard-list text-3xl mb-2"></i>
                        <p>No transactions for this day</p>
                    </div>
                    `}
                </div>
            `;
        });
    } else {
        content += `
            <div class="text-center py-8">
                <i class="fas fa-clipboard-list text-gray-400 text-3xl mb-3"></i>
                <p class="text-gray-500">No scans found for this period</p>
            </div>
        `;
    }

    content += '</div>';
    document.getElementById('tabContent').innerHTML = content;
}

function loadWeeklyActivityTab() {
    const data = window.currentVendorData;
    const weeklyActivity = data.weekly_activity || [];

    const totalScans = weeklyActivity.reduce((sum, day) => sum + (day.scans || 0), 0);
    const avgScans = weeklyActivity.length > 0 ? totalScans / weeklyActivity.length : 0;

    const content = `
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-lg border shadow-sm">
                <h4 class="font-semibold mb-4">Weekly Activity</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Day</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Scans</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg. per Scan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            ${weeklyActivity.map(day => {
                                const performance = (day.scans || 0) > avgScans ? 'Above Average' : 'Below Average';
                                const performanceColor = (day.scans || 0) > avgScans ? 'text-green-600 bg-green-50' : 'text-yellow-600 bg-yellow-50';

                                return `
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <p class="font-medium">${day.day || 'N/A'}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="text-lg font-semibold">${day.scans || 0}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="text-lg font-semibold text-green-600">KSh ${(day.revenue || 0).toFixed(2)}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="text-sm">KSh ${(day.avg_per_scan || 0).toFixed(2)}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 rounded-full text-xs ${performanceColor}">
                                                ${performance}
                                            </span>
                                        </td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;

    document.getElementById('tabContent').innerHTML = content;
}

function loadChartsTab() {
    const data = window.currentVendorData;

    const content = `
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-lg border shadow-sm">
                <h4 class="font-semibold mb-4">Analytics Charts</h4>
                <p class="text-gray-500 mb-4">Visual representation of vendor performance data</p>

                ${(!data.time_series_data || data.time_series_data.length === 0) &&
                  (!data.department_distribution || data.department_distribution.length === 0) ? `
                <div class="text-center py-12">
                    <i class="fas fa-chart-line text-gray-300 text-4xl mb-3"></i>
                    <p class="text-gray-500">No chart data available for this period</p>
                </div>
                ` : `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h5 class="font-medium mb-2">Scans Over Time</h5>
                        <div class="h-64">
                            <canvas id="timeSeriesChart"></canvas>
                        </div>
                    </div>
                    <div>
                        <h5 class="font-medium mb-2">Department Distribution</h5>
                        <div class="h-64">
                            <canvas id="departmentChart"></canvas>
                        </div>
                    </div>
                </div>
                `}
            </div>
        </div>
    `;

    document.getElementById('tabContent').innerHTML = content;

    // Initialize charts after a short delay
    setTimeout(() => {
        if (data.time_series_data && data.time_series_data.length > 0) {
            initializeTimeSeriesChart(data.time_series_data);
        }

        if (data.department_distribution && data.department_distribution.length > 0) {
            initializeDepartmentChart(data.department_distribution);
        }
    }, 100);
}

function initializeTimeSeriesChart(timeSeriesData) {
    const ctx = document.getElementById('timeSeriesChart');
    if (!ctx) {
        console.error('Canvas element not found for time series');
        return;
    }

    if (window.timeSeriesChart && typeof window.timeSeriesChart.destroy === 'function') {
        window.timeSeriesChart.destroy();
    }

    const labels = timeSeriesData.map(item => {
        if (item.date) {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
        return item.period || 'Unknown';
    });

    const scans = timeSeriesData.map(item => item.scans || 0);

    try {
        window.timeSeriesChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Number of Scans',
                    data: scans,
                    borderColor: '#2596be',
                    backgroundColor: 'rgba(37, 150, 190, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating time series chart:', error);
    }
}

function initializeDepartmentChart(departmentData) {
    const ctx = document.getElementById('departmentChart');
    if (!ctx) return;

    if (window.departmentChart) {
        window.departmentChart.destroy();
    }

    const sortedData = departmentData
        .sort((a, b) => (b.scans || 0) - (a.scans || 0))
        .slice(0, 5);

    const labels = sortedData.map(dept => dept.department || 'Unknown');
    const scans = sortedData.map(dept => dept.scans || 0);

    const colors = [
        '#e92c2a', '#2596be', '#10b981', '#f59e0b', '#8b5cf6',
        '#ef4444', '#3b82f6', '#22c55e', '#eab308', '#a855f7'
    ];

    window.departmentChart = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Scans per Department',
                data: scans,
                backgroundColor: colors.slice(0, labels.length),
                borderColor: colors.slice(0, labels.length).map(color => color + 'CC'),
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });
}

// ENHANCED EXPORT TAB WITH MONTH SELECTOR
function loadExportTab() {
    const data = window.currentVendorData;
    const vendor = data.vendor;

    // Generate month options for the last 12 months
    const monthOptions = [];
    for (let i = 0; i < 12; i++) {
        const date = new Date();
        date.setMonth(date.getMonth() - i);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const monthName = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        const value = `${year}-${month}`;
        monthOptions.push({ value, name: monthName });
    }

    const content = `
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-lg border shadow-sm">
                <h4 class="font-semibold mb-4">Export Analytics</h4>

                <!-- Month Selection -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Month for Export</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <select id="exportMonth" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Month</option>
                                ${monthOptions.map(option => `
                                    <option value="${option.value}">${option.name}</option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="loadCustomMonthData()"
                                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition duration-150">
                                <i class="fas fa-sync-alt mr-2"></i>Load Month Data
                            </button>
                            <span class="text-xs text-gray-500" id="monthDataStatus"></span>
                        </div>
                    </div>
                </div>

                <!-- Export Options -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Excel Export -->
                    <div class="border rounded-lg p-4 hover:border-secondary-blue transition duration-150">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-file-excel text-green-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Excel Export</p>
                                    <p class="text-xs text-gray-500">Detailed spreadsheet with all data</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center space-x-2">
                                <input type="radio" name="excelPeriod" id="excelCurrentMonth" value="current" checked class="h-4 w-4 text-blue-600">
                                <label for="excelCurrentMonth" class="text-sm text-gray-700">Current Month</label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input type="radio" name="excelPeriod" id="excelSelectedMonth" value="selected" class="h-4 w-4 text-blue-600">
                                <label for="excelSelectedMonth" class="text-sm text-gray-700">Selected Month</label>
                            </div>
                            <button onclick="exportVendorData('excel')"
                                    class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition duration-150">
                                <i class="fas fa-download mr-2"></i>Export to Excel
                            </button>
                        </div>
                    </div>

                    <!-- PDF Export -->
                    <div class="border rounded-lg p-4 hover:border-secondary-blue transition duration-150">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-file-pdf text-red-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium">PDF Report</p>
                                    <p class="text-xs text-gray-500">Printable summary report</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center space-x-2">
                                <input type="radio" name="pdfPeriod" id="pdfCurrentMonth" value="current" checked class="h-4 w-4 text-blue-600">
                                <label for="pdfCurrentMonth" class="text-sm text-gray-700">Current Month</label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input type="radio" name="pdfPeriod" id="pdfSelectedMonth" value="selected" class="h-4 w-4 text-blue-600">
                                <label for="pdfSelectedMonth" class="text-sm text-gray-700">Selected Month</label>
                            </div>
                            <button onclick="exportVendorData('pdf')"
                                    class="w-full bg-red-500 text-white py-2 rounded-lg hover:bg-red-600 transition duration-150">
                                <i class="fas fa-download mr-2"></i>Export to PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg border shadow-sm">
                <h4 class="font-semibold mb-4">Share via Email</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Email</label>
                        <input type="email" id="recipientEmail"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="email@example.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                        <input type="text" id="emailSubject"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="${vendor.name} - Analytics Report" placeholder="Report Subject">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message (Optional)</label>
                        <textarea id="emailMessage" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Add a custom message..."></textarea>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="includeAttachment" class="h-4 w-4 text-blue-600">
                            <label for="includeAttachment" class="ml-2 text-sm text-gray-700">Include report as attachment</label>
                        </div>
                        <div>
                            <select id="reportFormat" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                                <option value="summary">Summary</option>
                                <option value="detailed">Detailed</option>
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                            </select>
                        </div>
                    </div>
                    <button onclick="shareVendorAnalytics()"
                            class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition duration-150">
                        <i class="fas fa-paper-plane mr-2"></i>Send Email
                    </button>
                </div>
            </div>
        </div>
    `;

    document.getElementById('tabContent').innerHTML = content;
}

// Function to load data for selected month
// Function to load data for selected month - FIXED VERSION
function loadCustomMonthData() {
    const monthSelect = document.getElementById('exportMonth');
    const statusEl = document.getElementById('monthDataStatus');

    if (!monthSelect) {
        console.error('Month select element not found');
        return;
    }

    if (!monthSelect.value) {
        alert('Please select a month first');
        return;
    }

    const [year, month] = monthSelect.value.split('-');
    const monthName = monthSelect.options[monthSelect.selectedIndex].text;

    statusEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Loading...';

    // Get the vendor ID from current data
    const vendorId = window.currentVendorData?.vendor?.id;
    if (!vendorId) {
        statusEl.innerHTML = '<span class="text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>Vendor ID not found</span>';
        return;
    }

    // Fetch data for the selected month
    fetch(`/admin/vendor/${vendorId}/analytics/month/${year}/${month}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            statusEl.innerHTML = '<span class="text-green-600"><i class="fas fa-check mr-1"></i>' + monthName + ' data loaded</span>';

            // Store the month data for later use
            window.selectedMonthData = data.data;

            // Store in a data attribute on the month select instead
            monthSelect.dataset.selectedMonth = monthSelect.value;
            monthSelect.dataset.selectedMonthName = monthName;

            // Show summary in a small preview
            showMonthDataPreview(data.data);
        } else {
            throw new Error(data.error || 'Failed to load data');
        }
    })
    .catch(error => {
        console.error('Error loading month data:', error);
        statusEl.innerHTML = '<span class="text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>Failed to load data</span>';
    });
}

// Function to show month data preview
// Function to show month data preview - FIXED VERSION
function showMonthDataPreview(data) {
    // Check if preview container exists, if not create it
    let previewEl = document.getElementById('monthDataPreview');

    // Find the export tab content container
    const tabContent = document.getElementById('tabContent');

    if (!previewEl) {
        previewEl = document.createElement('div');
        previewEl.id = 'monthDataPreview';
        previewEl.className = 'mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200';

        // Append to tabContent instead of exportTab
        if (tabContent) {
            tabContent.appendChild(previewEl);
        }
    }

    // Format numbers
    const totalRevenue = new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES',
        minimumFractionDigits: 2
    }).format(data.summary.total_revenue);

    previewEl.innerHTML = `
        <h5 class="font-medium text-blue-800 mb-2">${data.month} - Summary</h5>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            <div>
                <span class="text-blue-600 block">Total Scans</span>
                <span class="font-bold">${data.summary.total_scans}</span>
            </div>
            <div>
                <span class="text-blue-600 block">Total Revenue</span>
                <span class="font-bold">${totalRevenue}</span>
            </div>
            <div>
                <span class="text-blue-600 block">Avg per Scan</span>
                <span class="font-bold">KES ${data.summary.avg_transaction}</span>
            </div>
            <div>
                <span class="text-blue-600 block">Days in Month</span>
                <span class="font-bold">${data.summary.days_in_month}</span>
            </div>
        </div>
    `;
}

// Updated export function with month selection
// Updated export function with month selection - FIXED VERSION
function exportVendorData(format) {
    const vendorId = window.currentVendorData?.vendor?.id;
    if (!vendorId) {
        alert('No vendor data available');
        return;
    }

    // Determine which period to use
    let period = 'month'; // default
    let selectedMonth = '';

    if (format === 'excel') {
        const excelPeriod = document.querySelector('input[name="excelPeriod"]:checked')?.value;
        if (excelPeriod === 'selected') {
            const monthSelect = document.getElementById('exportMonth');
            selectedMonth = monthSelect ? monthSelect.value : '';
            if (!selectedMonth) {
                alert('Please select a month first');
                return;
            }
        }
    } else if (format === 'pdf') {
        const pdfPeriod = document.querySelector('input[name="pdfPeriod"]:checked')?.value;
        if (pdfPeriod === 'selected') {
            const monthSelect = document.getElementById('exportMonth');
            selectedMonth = monthSelect ? monthSelect.value : '';
            if (!selectedMonth) {
                alert('Please select a month first');
                return;
            }
        }
    }

    // Build URL with parameters
    let url = `/admin/vendor/${vendorId}/analytics/export?format=${format}`;

    if (selectedMonth) {
        const [year, month] = selectedMonth.split('-');
        const startDate = `${year}-${month}-01`;
        const endDate = new Date(year, month, 0).toISOString().split('T')[0]; // Last day of month

        url += `&start_date=${startDate}&end_date=${endDate}&period=custom`;
    } else {
        url += '&period=month';
    }

    window.open(url, '_blank');
}
// Share function for vendor analytics
function shareVendorAnalytics() {
    const vendorId = window.currentVendorData?.vendor?.id;
    const email = document.getElementById('recipientEmail').value;
    const subject = document.getElementById('emailSubject').value;
    const message = document.getElementById('emailMessage').value;
    const includeAttachment = document.getElementById('includeAttachment').checked;
    const format = document.getElementById('reportFormat').value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!email) {
        alert('Please enter recipient email');
        return;
    }

    const button = document.querySelector('button[onclick="shareVendorAnalytics()"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';
    button.disabled = true;

    fetch(`/admin/vendor/${vendorId}/analytics/share`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            email: email,
            subject: subject,
            message: message,
            include_attachment: includeAttachment,
            format: format,
            period: 'month'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Email sent successfully!');
            document.getElementById('recipientEmail').value = '';
            document.getElementById('emailMessage').value = '';
        } else {
            alert('Failed to send email: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to send email. Please try again.');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Dashboard stats update function
async function updateDashboardStats() {
    try {
        const response = await fetch('/admin/dashboard/stats');
        const data = await response.json();

        if (data.success) {
            updateMetric('today_scans', data.stats.today_scans);
            updateMetric('total_revenue_today', 'KSh ' + data.stats.total_revenue_today.toLocaleString());
            updateMetric('employee_participation_rate', data.stats.employee_participation_rate + '%');
        }
    } catch (error) {
        console.error('Failed to update dashboard stats:', error);
    }
}

function updateMetric(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = value;
    }
}
</script>
@endsection
