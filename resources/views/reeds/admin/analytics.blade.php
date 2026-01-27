@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-text-black">Analytics & Reports</h1>
                <p class="text-gray-600 mt-2">Comprehensive insights into feeding patterns and vendor performance</p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-3">
                <select id="unitFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                    <option value="all">All Units</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
                <select id="periodSelect" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                    <option value="week">Last 7 Days</option>
                    <option value="month" selected>Last 30 Days</option>
                    <option value="year">Last 12 Months</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Unit Analytics Section -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-text-black">Unit Performance Overview</h2>
            <div class="text-sm text-gray-500">
                {{ $unitStats->count() }} Active Units
            </div>
        </div>

        @if($unitStats->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($unitStats as $unit)
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 hover:shadow-md transition duration-150">
                    <!-- Unit Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-text-black text-lg">{{ $unit['name'] }}</h3>
                            @if($unit['code'])
                                <p class="text-sm text-gray-500">{{ $unit['code'] }}</p>
                            @endif
                            @if($unit['location'])
                                <p class="text-xs text-gray-400 mt-1">
                                    <i class="fas fa-map-marker-alt mr-1"></i>{{ $unit['location'] }}
                                </p>
                            @endif
                        </div>
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-building text-blue-500"></i>
                        </div>
                    </div>

                    <!-- Unit Stats -->
                    <div class="space-y-3">
                        <!-- Employee Count -->
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Employees</span>
                            <div class="flex items-center space-x-2">
                                <span class="font-semibold text-text-black">{{ $unit['active_employees'] }}/{{ $unit['total_employees'] }}</span>
                                @if($unit['capacity_utilization'])
                                    <span class="text-xs px-2 py-1 rounded-full {{ $unit['capacity_utilization'] > 90 ? 'bg-red-100 text-red-800' : ($unit['capacity_utilization'] > 70 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                        {{ $unit['capacity_utilization'] }}%
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Scans Today -->
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Scans Today</span>
                            <div class="flex items-center space-x-2">
                                <span class="font-semibold text-text-black">{{ $unit['today_scans'] }}</span>
                                <span class="text-xs text-gray-500">
                                    {{ $unit['active_employees'] > 0 ? round(($unit['today_scans'] / $unit['active_employees']) * 100, 0) : 0 }}%
                                </span>
                            </div>
                        </div>

                        <!-- Monthly Scans -->
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Monthly Scans</span>
                            <div class="flex items-center space-x-2">
                                <span class="font-semibold text-text-black">{{ $unit['month_scans'] }}</span>
                                <span class="text-xs text-green-600">
                                    KSh {{ number_format($unit['month_revenue'], 0) }}
                                </span>
                            </div>
                        </div>

                        <!-- Active Vendors -->
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Vendors</span>
                            <span class="font-semibold text-text-black">{{ $unit['active_vendors'] }}</span>
                        </div>

                        <!-- View Unit Details Button -->
                        <button onclick="viewUnitAnalytics({{ $unit['id'] }})"
                                class="w-full mt-4 px-3 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-sm font-medium transition duration-150 flex items-center justify-center">
                            <i class="fas fa-chart-bar mr-2"></i> View Details
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-building text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-text-black mb-2">No Units Found</h3>
                <p class="text-gray-600 mb-6">Create units to start tracking analytics per location.</p>
                <a href="{{ route('admin.units.index') }}" class="inline-flex items-center px-4 py-2 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition duration-150">
                    <i class="fas fa-plus mr-2"></i> Create Units
                </a>
            </div>
        @endif
    </div>

    <!-- Static Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Scans This Month -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Scans (Month)</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ number_format($stats['month_scans']) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-qrcode text-green-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-xs text-gray-500">{{ number_format($stats['week_scans']) }} this week</span>
            </div>
        </div>

        <!-- Total Revenue This Month -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Monthly Revenue</p>
                    <p class="text-2xl font-bold text-text-black mt-2">KSh {{ number_format($stats['total_revenue_month'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-primary-red bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-primary-red text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-xs text-gray-500">KSh {{ number_format($stats['total_revenue_week'], 2) }} this week</span>
            </div>
        </div>

        <!-- Active Vendors -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Vendors</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $stats['total_vendors'] }}</p>
                </div>
                <div class="w-12 h-12 bg-secondary-blue bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-store text-secondary-blue text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-xs text-gray-500">{{ $stats['verified_vendors'] }} verified</span>
            </div>
        </div>

        <!-- Average Daily Scans -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Avg. Daily Scans</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ number_format($stats['avg_daily_scans_month'], 1) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-purple-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-xs text-gray-500">{{ number_format($stats['avg_daily_scans_week'], 1) }} this week</span>
            </div>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Employee Overview -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-text-black mb-4">Employee Overview</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total Employees</span>
                    <span class="font-semibold text-text-black">{{ $stats['total_employees'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Active Employees</span>
                    <span class="font-semibold text-green-600">{{ $stats['active_employees'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Feeding Rate Today</span>
                    <span class="font-semibold text-text-black">
                        {{ $stats['total_employees'] > 0 ? round(($stats['today_scans'] / $stats['total_employees']) * 100, 1) : 0 }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- Today's Activity -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-text-black mb-4">Today's Activity</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Scans Today</span>
                    <span class="font-semibold text-text-black">{{ $stats['today_scans'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Revenue Today</span>
                    <span class="font-semibold text-green-600">KSh {{ number_format($stats['total_revenue_today'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Avg. per Scan</span>
                    <span class="font-semibold text-text-black">
                        KSh {{ $stats['today_scans'] > 0 ? number_format($stats['total_revenue_today'] / $stats['today_scans'], 2) : '0.00' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Top Vendor This Month -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-text-black mb-4">Top Vendor This Month</h3>
            @if($topVendors->count() > 0)
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Vendor</span>
                    <span class="font-semibold text-text-black">{{ $topVendors->first()->name }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total Scans</span>
                    <span class="font-semibold text-text-black">{{ $topVendors->first()->total_scans }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Revenue</span>
                    <span class="font-semibold text-green-600">KSh {{ number_format($topVendors->first()->total_revenue, 2) }}</span>
                </div>
            </div>
            @else
            <div class="text-center py-4 text-gray-500">
                <i class="fas fa-store text-2xl mb-2"></i>
                <p class="text-sm">No vendor data available</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Loading State for Dynamic Charts -->
    <div id="loadingState" class="bg-white rounded-xl shadow-md border border-gray-100 p-8 text-center mb-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-secondary-blue mx-auto mb-4"></div>
        <p class="text-gray-600">Loading analytics charts...</p>
    </div>

    <!-- Error State -->
    <div id="errorState" class="bg-white rounded-xl shadow-md border border-gray-100 p-8 text-center mb-8 hidden">
        <i class="fas fa-exclamation-triangle text-yellow-500 text-3xl mb-4"></i>
        <h3 class="text-lg font-semibold text-text-black mb-2">Unable to Load Charts</h3>
        <p class="text-gray-600 mb-4" id="errorMessage">There was an error loading the analytics charts.</p>
        <button onclick="loadAnalyticsData('month')" class="px-4 py-2 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition duration-150">
            Try Again
        </button>
    </div>

    <!-- Dynamic Charts Section -->
    <div id="chartsSection" class="hidden">
        <!-- Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Scans Over Time Chart -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Scans Over Time</h3>
                       <canvas id="scansChart"></canvas> <!-- Change to canvas -->
            </div>

            <!-- Unit Performance Chart -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Unit Performance</h3>
       <canvas id="unitChart"></canvas> <!-- Change to canvas -->
            </div>

            <!-- Department Feeding Rates -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Department Feeding Rates</h3>
                 <canvas id="departmentChart"></canvas> <!-- Change to canvas -->
            </div>

            <!-- Employee Behavior -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Frequent Eaters</h3>
               <canvas id="employeeChart"></canvas> <!-- Change to canvas -->
            </div>
        </div>

        <!-- Detailed Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Unit Performance Table -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Unit Performance</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 text-sm font-medium text-gray-700">Unit</th>
                                <th class="text-right py-3 text-sm font-medium text-gray-700">Employees</th>
                                <th class="text-right py-3 text-sm font-medium text-gray-700">Scans</th>
                                <th class="text-right py-3 text-sm font-medium text-gray-700">Revenue</th>
                            </tr>
                        </thead>
                        <tbody id="unitTable">
                            <!-- Unit data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Vendor Performance Table -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Vendor Performance</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 text-sm font-medium text-gray-700">Vendor</th>
                                <th class="text-right py-3 text-sm font-medium text-gray-700">Scans</th>
                                <th class="text-right py-3 text-sm font-medium text-gray-700">Revenue</th>
                            </tr>
                        </thead>
                        <tbody id="vendorTable">
                            <!-- Vendor data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mt-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-text-black">Recent Transactions</h3>
            <a href="" class="text-sm text-secondary-blue hover:text-blue-600">View All</a>
        </div>
        <div class="space-y-3">
            @forelse($recentTransactions as $transaction)
            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-utensils text-green-600"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-text-black text-sm">
                            {{ $transaction->employee->formal_name ?? 'Unknown Employee' }}
                        </p>
                        <div class="flex items-center space-x-2 text-xs text-gray-500">
                            <span>{{ $transaction->employee->department->name ?? 'No Department' }}</span>
                            <span>•</span>
                            <span class="flex items-center">
                                <i class="fas fa-building mr-1"></i>
                                {{ $transaction->unit->name ?? 'No Unit' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-green-600">KSh {{ number_format($transaction->amount, 2) }}</p>
                    <div class="flex items-center justify-end space-x-2 text-xs text-gray-500">
                        <span>{{ $transaction->meal_time }}</span>
                        <span>•</span>
                        <span>{{ $transaction->vendor->name ?? 'Unknown' }}</span>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-history text-3xl mb-2"></i>
                <p>No transactions yet</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Unit Details Modal -->
<div id="unitDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 id="unitModalTitle" class="text-xl font-bold text-text-black">Unit Analytics</h3>
            <button onclick="closeUnitModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="unitDetailsContent">
            <!-- Unit details will be loaded here -->
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let scansChart, unitChart, departmentChart, employeeChart;

    // UI State Management
    function showLoading() {
        document.getElementById('loadingState').classList.remove('hidden');
        document.getElementById('errorState').classList.add('hidden');
        document.getElementById('chartsSection').classList.add('hidden');
    }

    function showError(message) {
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('errorState').classList.remove('hidden');
        document.getElementById('chartsSection').classList.add('hidden');
        document.getElementById('errorMessage').textContent = message || 'There was an error loading the analytics charts.';
    }

    function showCharts() {
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('errorState').classList.add('hidden');
        document.getElementById('chartsSection').classList.remove('hidden');
    }

    // Load analytics data with unit filter
    function loadAnalyticsData(period = 'month', unitId = 'all') {
        showLoading();

        fetch(`/admin/analytics/data?period=${period}&unit_id=${unitId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    updateCharts(data);
                    updateTables(data);
                    showCharts();
                } else {
                    throw new Error(data.error || 'Failed to load data');
                }
            })
            .catch(error => {
                console.error('Error loading analytics:', error);
                showError(error.message);
            });
    }

    // Update charts with safe data handling
    function updateCharts(data) {
        // Scans over time chart
        const scansCtx = document.getElementById('scansChart').getContext('2d');
        if (scansChart) scansChart.destroy();

        scansChart = new Chart(scansCtx, {
            type: 'line',
            data: {
                labels: data.scans_data.map(item => item.period),
                datasets: [{
                    label: 'Scans',
                    data: data.scans_data.map(item => item.scans || 0),
                    borderColor: '#2596be',
                    backgroundColor: 'rgba(37, 150, 190, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Unit performance chart
        const unitCtx = document.getElementById('unitChart').getContext('2d');
        if (unitChart) unitChart.destroy();

        unitChart = new Chart(unitCtx, {
            type: 'bar',
            data: {
                labels: data.unit_performance.map(item => item.name),
                datasets: [
                    {
                        label: 'Scans',
                        data: data.unit_performance.map(item => item.scans || 0),
                        backgroundColor: '#e92c2a',
                        borderColor: '#e92c2a',
                        borderWidth: 1
                    },
                    {
                        label: 'Employees',
                        data: data.unit_performance.map(item => item.employees || 0),
                        backgroundColor: '#2596be',
                        borderColor: '#2596be',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });

        // Department feeding rates
        const deptCtx = document.getElementById('departmentChart').getContext('2d');
        if (departmentChart) departmentChart.destroy();

        departmentChart = new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: data.department_feeding.map(item => item.name),
                datasets: [{
                    data: data.department_feeding.map(item => item.feeding_rate || 0),
                    backgroundColor: [
                        '#e92c2a', '#2596be', '#10b981', '#f59e0b', '#8b5cf6',
                        '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Employee behavior
        const empCtx = document.getElementById('employeeChart').getContext('2d');
        if (employeeChart) employeeChart.destroy();

        employeeChart = new Chart(empCtx, {
            type: 'bar',
            data: {
                labels: data.employee_behavior.frequent_eaters.map(item => item.formal_name),
                datasets: [{
                    label: 'Meals',
                    data: data.employee_behavior.frequent_eaters.map(item => item.meal_count || 0),
                    backgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // Update tables with safe data
    function updateTables(data) {
        // Unit table
        const unitTable = document.getElementById('unitTable');
        if (data.unit_performance.length > 0) {
            unitTable.innerHTML = data.unit_performance.map(unit => `
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 text-sm text-text-black">${unit.name}</td>
                    <td class="py-3 text-sm text-gray-700 text-right">${unit.employees || 0}</td>
                    <td class="py-3 text-sm text-gray-700 text-right">${unit.scans || 0}</td>
                    <td class="py-3 text-sm text-green-600 text-right">KSh ${(unit.revenue || 0).toLocaleString()}</td>
                </tr>
            `).join('');
        } else {
            unitTable.innerHTML = `
                <tr>
                    <td colspan="4" class="py-8 text-center text-gray-500">
                        <i class="fas fa-building text-2xl mb-2"></i>
                        <p>No unit data available</p>
                    </td>
                </tr>
            `;
        }

        // Vendor table
        const vendorTable = document.getElementById('vendorTable');
        if (data.vendor_performance.length > 0) {
            vendorTable.innerHTML = data.vendor_performance.map(vendor => `
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 text-sm text-text-black">${vendor.name}</td>
                    <td class="py-3 text-sm text-gray-700 text-right">${vendor.scans || 0}</td>
                    <td class="py-3 text-sm text-green-600 text-right">KSh ${(vendor.revenue || 0).toLocaleString()}</td>
                </tr>
            `).join('');
        } else {
            vendorTable.innerHTML = `
                <tr>
                    <td colspan="3" class="py-8 text-center text-gray-500">
                        <i class="fas fa-store text-2xl mb-2"></i>
                        <p>No vendor data available</p>
                    </td>
                </tr>
            `;
        }
    }

    // View unit analytics
    function viewUnitAnalytics(unitId) {
        // Show loading state
        document.getElementById('unitDetailsContent').innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-2xl text-secondary-blue mb-2"></i>
                <p>Loading unit analytics...</p>
            </div>
        `;

        document.getElementById('unitDetailsModal').classList.remove('hidden');

        // Fetch unit details via AJAX
        fetch(`/admin/analytics/unit/${unitId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.unit) {
                    const unit = data.unit;

                    document.getElementById('unitModalTitle').textContent = `${unit.name} Analytics`;
                    document.getElementById('unitDetailsContent').innerHTML = `
                        <div class="space-y-6">
                            <!-- Unit Overview -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <h4 class="text-sm font-medium text-blue-800 mb-1">Total Employees</h4>
                                    <p class="text-2xl font-bold text-text-black">${unit.total_employees}</p>
                                    <p class="text-xs text-blue-600">${unit.active_employees} active</p>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <h4 class="text-sm font-medium text-green-800 mb-1">Monthly Scans</h4>
                                    <p class="text-2xl font-bold text-text-black">${unit.month_scans}</p>
                                    <p class="text-xs text-green-600">${unit.today_scans} today</p>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <h4 class="text-sm font-medium text-purple-800 mb-1">Monthly Revenue</h4>
                                    <p class="text-2xl font-bold text-text-black">KSh ${unit.month_revenue.toLocaleString()}</p>
                                    <p class="text-xs text-purple-600">${unit.active_vendors} active vendors</p>
                                </div>
                            </div>

                            <!-- Charts Section -->
                            <div>
                                <h4 class="text-lg font-semibold text-text-black mb-4">Monthly Trends</h4>
                                <div class="bg-gray-50 p-6 rounded-lg">
                                    <canvas id="unitMonthlyChart" height="300"></canvas>
                                </div>
                            </div>

                            <!-- Top Employees -->
                            <div>
                                <h4 class="text-lg font-semibold text-text-black mb-4">Top Employees</h4>
                                <div class="space-y-3">
                                    ${unit.top_employees.length > 0 ? unit.top_employees.map(emp => `
                                        <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg">
                                            <div>
                                                <p class="font-medium text-text-black">${emp.formal_name}</p>
                                                <p class="text-sm text-gray-500">${emp.department}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-semibold text-green-600">${emp.meal_count} meals</p>
                                                <p class="text-xs text-gray-500">KSh ${emp.total_amount.toLocaleString()}</p>
                                            </div>
                                        </div>
                                    `).join('') : `
                                        <div class="text-center py-4 text-gray-500">
                                            <p>No employee data available</p>
                                        </div>
                                    `}
                                </div>
                            </div>

                            <!-- Unit Vendors -->
                            <div>
                                <h4 class="text-lg font-semibold text-text-black mb-4">Active Vendors</h4>
                                <div class="space-y-3">
                                    ${unit.vendors.length > 0 ? unit.vendors.map(vendor => `
                                        <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg">
                                            <div>
                                                <p class="font-medium text-text-black">${vendor.name}</p>
                                                <p class="text-sm text-gray-500">${vendor.email}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-semibold text-green-600">${vendor.scans} scans</p>
                                                <p class="text-xs text-gray-500">${vendor.last_scan}</p>
                                            </div>
                                        </div>
                                    `).join('') : `
                                        <div class="text-center py-4 text-gray-500">
                                            <p>No vendors assigned to this unit</p>
                                        </div>
                                    `}
                                </div>
                            </div>
                        </div>
                    `;

                    // Initialize unit chart
                    const ctx = document.getElementById('unitMonthlyChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: unit.monthly_trends.labels,
                            datasets: [{
                                label: 'Daily Scans',
                                data: unit.monthly_trends.scans,
                                borderColor: '#2596be',
                                backgroundColor: 'rgba(37, 150, 190, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                } else {
                    document.getElementById('unitDetailsContent').innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-2xl text-red-500 mb-2"></i>
                            <p>Failed to load unit analytics.</p>
                            <p class="text-sm text-gray-600 mt-1">${data.error || 'Please try again.'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('unitDetailsContent').innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-2xl text-red-500 mb-2"></i>
                        <p>Error loading unit analytics.</p>
                        <p class="text-sm text-gray-600 mt-1">Please try again later.</p>
                    </div>
                `;
            });
    }

    // Close unit modal
    function closeUnitModal() {
        document.getElementById('unitDetailsModal').classList.add('hidden');
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        loadAnalyticsData('month');

        // Period selector
        document.getElementById('periodSelect').addEventListener('change', function() {
            const unitId = document.getElementById('unitFilter').value;
            loadAnalyticsData(this.value, unitId);
        });

        // Unit filter
        document.getElementById('unitFilter').addEventListener('change', function() {
            const period = document.getElementById('periodSelect').value;
            loadAnalyticsData(period, this.value);
        });
    });
</script>
@endsection
