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

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Scans</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="totalScans">0</p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-qrcode text-green-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="totalRevenue">KSh 0</p>
                </div>
                <div class="w-12 h-12 bg-primary-red bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-primary-red text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Vendors</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="activeVendors">0</p>
                </div>
                <div class="w-12 h-12 bg-secondary-blue bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-store text-secondary-blue text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Avg. Daily Scans</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="avgDailyScans">0</p>
                </div>
                <div class="w-12 h-12 bg-purple-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-purple-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

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
@endsection

@section('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let scansChart, vendorChart, departmentChart, employeeChart;

    // Load analytics data
    function loadAnalyticsData(period = 'month') {
        fetch(`/admin/analytics/data?period=${period}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateStats(data);
                    updateCharts(data);
                    updateTables(data);
                }
            })
            .catch(error => {
                console.error('Error loading analytics:', error);
            });
    }

    // Update stats cards
    function updateStats(data) {
        const totalScans = data.scans_data.reduce((sum, item) => sum + item.scans, 0);
        const totalRevenue = data.scans_data.reduce((sum, item) => sum + item.revenue, 0);
        const activeVendors = data.vendor_performance.length;
        const avgDailyScans = data.scans_data.length > 0 ? (totalScans / data.scans_data.length).toFixed(1) : 0;

        document.getElementById('totalScans').textContent = totalScans.toLocaleString();
        document.getElementById('totalRevenue').textContent = 'KSh ' + totalRevenue.toLocaleString();
        document.getElementById('activeVendors').textContent = activeVendors;
        document.getElementById('avgDailyScans').textContent = avgDailyScans;
    }

    // Update charts
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
                    data: data.scans_data.map(item => item.scans),
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
                    data: data.vendor_performance.map(item => item.scans),
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
                    data: data.department_feeding.map(item => item.feeding_rate),
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
                    data: data.employee_behavior.frequent_eaters.map(item => item.meal_count),
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

    // Update tables
    function updateTables(data) {
        // Vendor table
        const vendorTable = document.getElementById('vendorTable');
        vendorTable.innerHTML = data.vendor_performance.map(vendor => `
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-3 text-sm text-text-black">${vendor.name}</td>
                <td class="py-3 text-sm text-gray-700 text-right">${vendor.scans}</td>
                <td class="py-3 text-sm text-green-600 text-right">KSh ${vendor.revenue.toLocaleString()}</td>
            </tr>
        `).join('');

        // Preferences table
        const prefsTable = document.getElementById('preferencesTable');
        if (data.employee_behavior.preferred_vendors.length > 0) {
            prefsTable.innerHTML = data.employee_behavior.preferred_vendors.map(pref => `
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 text-sm text-text-black">${pref.employee_name}</td>
                    <td class="py-3 text-sm text-gray-700">${pref.vendor_name}</td>
                    <td class="py-3 text-sm text-gray-700 text-right">${pref.visit_count}</td>
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
