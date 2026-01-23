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
            <div class="mt-4 md:mt-0">
                <select id="periodSelect" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                    <option value="week">Last 7 Days</option>
                    <option value="month" selected>Last 30 Days</option>
                    <option value="year">Last 12 Months</option>
                </select>
            </div>
        </div>
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
                <div id="scansChart" class="h-80"></div>
            </div>

            <!-- Vendor Performance Chart -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Top Vendors</h3>
                <div id="vendorChart" class="h-80"></div>
            </div>

            <!-- Department Feeding Rates -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Department Feeding Rates</h3>
                <div id="departmentChart" class="h-80"></div>
            </div>

            <!-- Employee Behavior -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Frequent Eaters</h3>
                <div id="employeeChart" class="h-80"></div>
            </div>
        </div>

        <!-- Detailed Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
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

            <!-- Employee Preferences -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Employee Vendor Preferences</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 text-sm font-medium text-gray-700">Employee</th>
                                <th class="text-left py-3 text-sm font-medium text-gray-700">Preferred Vendor</th>
                                <th class="text-right py-3 text-sm font-medium text-gray-700">Visits</th>
                            </tr>
                        </thead>
                        <tbody id="preferencesTable">
                            <!-- Preferences data will be loaded here -->
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
                        <p class="text-xs text-gray-500">
                            {{ $transaction->employee->department->name ?? 'No Department' }} •
                            {{ $transaction->vendor->name ?? 'Unknown Vendor' }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-green-600">KSh {{ number_format($transaction->amount, 2) }}</p>
                    <p class="text-xs text-gray-500">{{ $transaction->meal_time }}</p>
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
@endsection

@section('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let scansChart, vendorChart, departmentChart, employeeChart;

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

    // Load analytics data
    function loadAnalyticsData(period = 'month') {
        showLoading();

        fetch(`/admin/analytics/data?period=${period}`)
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

        // Vendor performance chart
        const vendorCtx = document.getElementById('vendorChart').getContext('2d');
        if (vendorChart) vendorChart.destroy();

        vendorChart = new Chart(vendorCtx, {
            type: 'bar',
            data: {
                labels: data.vendor_performance.map(item => item.name),
                datasets: [{
                    label: 'Scans',
                    data: data.vendor_performance.map(item => item.scans || 0),
                    backgroundColor: '#e92c2a',
                    borderColor: '#e92c2a',
                    borderWidth: 1
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

        // Preferences table
        const prefsTable = document.getElementById('preferencesTable');
        if (data.employee_behavior.preferred_vendors.length > 0) {
            prefsTable.innerHTML = data.employee_behavior.preferred_vendors.map(pref => `
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 text-sm text-text-black">${pref.employee_name}</td>
                    <td class="py-3 text-sm text-gray-700">${pref.vendor_name}</td>
                    <td class="py-3 text-sm text-gray-700 text-right">${pref.visit_count || 0}</td>
                </tr>
            `).join('');
        } else {
            prefsTable.innerHTML = `
                <tr>
                    <td colspan="3" class="py-8 text-center text-gray-500">
                        <i class="fas fa-users text-2xl mb-2"></i>
                        <p>No employee preferences data yet</p>
                    </td>
                </tr>
            `;
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        loadAnalyticsData('month');

        // Period selector
        document.getElementById('periodSelect').addEventListener('change', function() {
            loadAnalyticsData(this.value);
        });
    });
</script>
@endsection
