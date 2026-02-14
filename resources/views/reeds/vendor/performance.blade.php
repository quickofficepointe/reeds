@extends('reeds.vendor.layout.vendorlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-text-black">Performance Analytics</h1>
                <p class="text-gray-600 mt-2">Track your performance with detailed analytics and insights</p>
            </div>

            <!-- Time Range Selector -->
            <div class="mt-4 md:mt-0 flex space-x-3">
                <div class="relative">
                    <select id="timeRange" onchange="loadAnalytics()"
                            class="appearance-none px-4 py-2 pr-8 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent bg-white">
                        <option value="daily">Daily (Last 30 Days)</option>
                        <option value="weekly" selected>Weekly (Last 12 Weeks)</option>
                        <option value="monthly">Monthly (Last 12 Months)</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>
                <button onclick="refreshAnalytics()" class="bg-secondary-blue text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200 flex items-center">
                    <i class="fas fa-sync-alt mr-2"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Scans</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="totalScans">0</p>
                </div>
                <div class="w-12 h-12 bg-secondary-blue bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-qrcode text-secondary-blue text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">vs previous period</span>
                    <span class="font-semibold" id="scansGrowth">+0%</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="totalRevenue">Ksh 0.00</p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-green-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">avg per scan</span>
                    <span class="font-semibold" id="avgPerScan">Ksh 0.00</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Avg Daily Scans</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="avgDailyScans">0.0</p>
                </div>
                <div class="w-12 h-12 bg-purple-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-purple-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500" id="avgPeriodLabel">this period</span>
                    <span class="font-semibold" id="avgDailyTrend">+0%</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Peak Performance</p>
                    <p class="text-lg font-bold text-text-black mt-2" id="bestDay">N/A</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-trophy text-yellow-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500" id="bestDayCount">0 scans</span>
                    <span class="font-semibold" id="bestDayRevenue">Ksh 0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Scan Trend Chart -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-text-black">Scan Trend</h3>
                    <p class="text-sm text-gray-500" id="scanChartLabel">Daily scan volume over time</p>
                </div>
                <div class="flex space-x-2">
                    <button onclick="toggleChartType('scans')" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">
                        <i class="fas fa-chart-line"></i>
                    </button>
                    <button onclick="toggleChartType('scans', 'bar')" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">
                        <i class="fas fa-chart-bar"></i>
                    </button>
                </div>
            </div>
            <div class="h-80 relative">
                <canvas id="dailyScansChart"></canvas>
                <div id="scansChartLoading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center hidden">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-secondary-blue"></div>
                </div>
            </div>
        </div>

        <!-- Revenue Trend Chart -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-text-black">Revenue Trend</h3>
                    <p class="text-sm text-gray-500" id="revenueChartLabel">Daily revenue over time</p>
                </div>
                <div class="flex space-x-2">
                    <button onclick="toggleChartType('revenue')" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">
                        <i class="fas fa-chart-line"></i>
                    </button>
                    <button onclick="toggleChartType('revenue', 'bar')" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">
                        <i class="fas fa-chart-bar"></i>
                    </button>
                </div>
            </div>
            <div class="h-80 relative">
                <canvas id="revenueChart"></canvas>
                <div id="revenueChartLoading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center hidden">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-secondary-blue"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Department Distribution -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-text-black">Department Distribution</h3>
                    <p class="text-sm text-gray-500">Scan distribution by department</p>
                </div>
            </div>
            <div class="h-80 relative">
                <canvas id="departmentChart"></canvas>
                <div id="deptChartLoading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center hidden">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-secondary-blue"></div>
                </div>
            </div>
        </div>

        <!-- Peak Hours Chart -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-text-black">Peak Scanning Hours</h3>
                    <p class="text-sm text-gray-500">Scan distribution by hour (6 AM - 6 PM)</p>
                </div>
            </div>
            <div class="h-80 relative">
                <canvas id="peakHoursChart"></canvas>
                <div id="peakChartLoading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center hidden">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-secondary-blue"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Analytics Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Weekly Comparison -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-text-black mb-4">Weekly Comparison</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">This Week</span>
                        <span class="font-semibold" id="thisWeek">0</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-secondary-blue h-2 rounded-full" id="thisWeekBar" style="width: 0%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Last Week</span>
                        <span class="font-semibold" id="lastWeek">0</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-400 h-2 rounded-full" id="lastWeekBar" style="width: 0%"></div>
                    </div>
                </div>
                <div class="pt-2 text-center">
                    <span class="text-sm" id="weeklyChange">+0% vs last week</span>
                </div>
            </div>
        </div>

        <!-- Day of Week Pattern -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-text-black mb-4">Day of Week Pattern</h3>
            <div class="h-32">
                <canvas id="dayOfWeekChart"></canvas>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-text-black mb-4">Quick Insights</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Most Active Day</span>
                    <span class="font-semibold" id="mostActiveDay">Wednesday</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Peak Hour</span>
                    <span class="font-semibold" id="peakHour">12:00 - 13:00</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Top Department</span>
                    <span class="font-semibold" id="topDepartment">N/A</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Growth Rate</span>
                    <span class="font-semibold text-green-600" id="growthRate">+12.5%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Employees Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-text-black">Top Performing Employees</h3>
            <span class="text-sm text-gray-500" id="topEmployeesLabel">Most frequent scans this period</span>
        </div>

        <!-- Loading State -->
        <div id="topEmployeesLoading" class="py-8 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-secondary-blue mx-auto mb-4"></div>
            <p class="text-gray-600">Loading top employees...</p>
        </div>

        <!-- Table Content -->
        <div id="topEmployeesContent" class="hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Scans</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Scan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Daily</th>
                        </tr>
                    </thead>
                    <tbody id="topEmployeesBody" class="bg-white divide-y divide-gray-200">
                        <!-- Top employees will be loaded here -->
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div id="topEmployeesEmpty" class="py-8 text-center hidden">
                <i class="fas fa-users text-3xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">No employee data available</p>
                <p class="text-sm text-gray-400 mt-1">Start scanning to see employee statistics</p>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Chart instances
    let dailyScansChart = null;
    let revenueChart = null;
    let departmentChart = null;
    let peakHoursChart = null;
    let dayOfWeekChart = null;

    // Current state
    let currentTimeRange = 'weekly';
    let currentChartTypes = {
        scans: 'line',
        revenue: 'bar'
    };

    // Chart colors
    const chartColors = {
        primary: '#2596be',
        secondary: '#e92c2a',
        success: '#10b981',
        warning: '#f59e0b',
        danger: '#ef4444',
        info: '#3b82f6',
        purple: '#8b5cf6',
        pink: '#ec4899',
        gray: '#6b7280',
        lightGray: '#e5e7eb'
    };

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Performance analytics page initialized');
        loadAnalytics();
    });

    // Load analytics data
    async function loadAnalytics() {
        currentTimeRange = document.getElementById('timeRange').value;
        console.log(`Loading ${currentTimeRange} analytics...`);

        // Show loading states
        showChartLoading('scans');
        showChartLoading('revenue');
        showChartLoading('dept');
        showChartLoading('peak');
        showTopEmployeesLoading(true);

        try {
            // Determine endpoint based on time range
            let endpoint;
            switch(currentTimeRange) {
                case 'daily':
                    endpoint = '{{ route("vendor.analytics.daily") }}';
                    break;
                case 'weekly':
                    endpoint = '{{ route("vendor.analytics.weekly") }}';
                    break;
                case 'monthly':
                    endpoint = '{{ route("vendor.analytics.monthly") }}';
                    break;
                default:
                    endpoint = '{{ route("vendor.analytics.weekly") }}';
            }

            const response = await fetch(endpoint);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Analytics data received:', data);

            if (data.success) {
                updateMetrics(data.metrics || {});
                updateCharts(data.charts || {});
                updateTopEmployees(data.top_employees || []);
                updateQuickInsights(data.insights || {});
                updateWeeklyComparison(data.weekly_comparison || {});
            } else {
                throw new Error(data.message || 'Failed to load analytics data');
            }
        } catch (error) {
            console.error('Error loading analytics:', error);
            showError('Failed to Load Analytics', error.message);

            // Initialize empty charts
            initializeEmptyCharts();
        } finally {
            // Hide loading states
            hideChartLoading('scans');
            hideChartLoading('revenue');
            hideChartLoading('dept');
            hideChartLoading('peak');
            showTopEmployeesLoading(false);
        }
    }

    // Update metrics
    function updateMetrics(metrics) {
        document.getElementById('totalScans').textContent = formatNumber(metrics.total_scans || 0);
        document.getElementById('totalRevenue').textContent = 'Ksh ' + formatCurrency(metrics.total_revenue || 0);

        const avgPerScan = metrics.total_scans > 0
            ? (metrics.total_revenue / metrics.total_scans)
            : 0;
        document.getElementById('avgPerScan').textContent = 'Ksh ' + formatCurrency(avgPerScan);

        document.getElementById('avgDailyScans').textContent = (metrics.average_daily || 0).toFixed(1);

        // Growth indicators
        document.getElementById('scansGrowth').textContent = formatPercentage(metrics.scans_growth || 0);
        document.getElementById('scansGrowth').className = (metrics.scans_growth || 0) >= 0
            ? 'font-semibold text-green-600'
            : 'font-semibold text-red-600';

        document.getElementById('avgDailyTrend').textContent = formatPercentage(metrics.daily_trend || 0);
        document.getElementById('avgDailyTrend').className = (metrics.daily_trend || 0) >= 0
            ? 'font-semibold text-green-600'
            : 'font-semibold text-red-600';

        // Best day
        if (metrics.best_day) {
            document.getElementById('bestDay').textContent = metrics.best_day.date || 'N/A';
            document.getElementById('bestDayCount').textContent = (metrics.best_day.count || 0) + ' scans';
            document.getElementById('bestDayRevenue').textContent = 'Ksh ' + formatCurrency(metrics.best_day.revenue || 0);
        }

        // Update labels based on time range
        updatePeriodLabels();
    }

    // Update charts
    function updateCharts(charts) {
        // Destroy existing charts
        destroyCharts();

        // Ensure we have data
        const scanData = charts.daily_scans || { labels: [], data: [] };
        const revenueData = charts.revenue || { labels: [], data: [] };
        const deptData = charts.departments || { labels: [], data: [] };
        const peakData = charts.peak_hours || { labels: [], data: [] };
        const dayOfWeekData = charts.day_of_week || { labels: [], data: [] };

        // Initialize charts with data
        initializeScansChart(scanData);
        initializeRevenueChart(revenueData);
        initializeDepartmentChart(deptData);
        initializePeakHoursChart(peakData);
        initializeDayOfWeekChart(dayOfWeekData);
    }

    // Initialize empty charts when no data
    function initializeEmptyCharts() {
        destroyCharts();
        initializeScansChart({ labels: [], data: [] });
        initializeRevenueChart({ labels: [], data: [] });
        initializeDepartmentChart({ labels: [], data: [] });
        initializePeakHoursChart({ labels: [], data: [] });
        initializeDayOfWeekChart({ labels: [], data: [] });
    }

    // Destroy all charts
    function destroyCharts() {
        if (dailyScansChart) { dailyScansChart.destroy(); dailyScansChart = null; }
        if (revenueChart) { revenueChart.destroy(); revenueChart = null; }
        if (departmentChart) { departmentChart.destroy(); departmentChart = null; }
        if (peakHoursChart) { peakHoursChart.destroy(); peakHoursChart = null; }
        if (dayOfWeekChart) { dayOfWeekChart.destroy(); dayOfWeekChart = null; }
    }

    // Initialize scans chart
    function initializeScansChart(data) {
        const ctx = document.getElementById('dailyScansChart').getContext('2d');

        const chartData = {
            labels: data.labels || [],
            datasets: [{
                label: 'Number of Scans',
                data: data.data || [],
                borderColor: chartColors.primary,
                backgroundColor: currentChartTypes.scans === 'line'
                    ? 'rgba(37, 150, 190, 0.1)'
                    : chartColors.primary,
                borderWidth: 2,
                fill: currentChartTypes.scans === 'line',
                tension: 0.4,
                pointBackgroundColor: chartColors.primary,
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        };

        const options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: chartColors.primary,
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return `Scans: ${context.raw}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        stepSize: 1,
                        color: '#6b7280',
                        callback: function(value) {
                            return Math.floor(value);
                        }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#6b7280',
                        maxRotation: 45,
                        maxTicksLimit: 10
                    }
                }
            }
        };

        dailyScansChart = new Chart(ctx, {
            type: currentChartTypes.scans,
            data: chartData,
            options: options
        });
    }

    // Initialize revenue chart
    function initializeRevenueChart(data) {
        const ctx = document.getElementById('revenueChart').getContext('2d');

        const chartData = {
            labels: data.labels || [],
            datasets: [{
                label: 'Revenue (Ksh)',
                data: data.data || [],
                borderColor: chartColors.success,
                backgroundColor: currentChartTypes.revenue === 'line'
                    ? 'rgba(16, 185, 129, 0.1)'
                    : chartColors.success,
                borderWidth: 2,
                fill: currentChartTypes.revenue === 'line',
                tension: 0.4,
                pointBackgroundColor: chartColors.success,
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        };

        const options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: chartColors.success,
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: Ksh ' + formatCurrency(context.raw);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        color: '#6b7280',
                        callback: function(value) {
                            return 'Ksh ' + formatCurrency(value);
                        }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#6b7280',
                        maxRotation: 45,
                        maxTicksLimit: 10
                    }
                }
            }
        };

        revenueChart = new Chart(ctx, {
            type: currentChartTypes.revenue,
            data: chartData,
            options: options
        });
    }

    // Initialize department chart
    function initializeDepartmentChart(data) {
        const ctx = document.getElementById('departmentChart').getContext('2d');

        const departmentColors = [
            chartColors.primary,
            chartColors.success,
            chartColors.warning,
            chartColors.purple,
            chartColors.secondary,
            chartColors.info,
            chartColors.pink,
            '#f97316'
        ];

        const chartData = {
            labels: data.labels || ['No Data'],
            datasets: [{
                data: data.data || [1],
                backgroundColor: data.data && data.data.length > 0
                    ? departmentColors.slice(0, data.data.length)
                    : ['#e5e7eb'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        };

        const options = {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        color: '#6b7280',
                        boxWidth: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? Math.round((context.raw / total) * 100) : 0;
                            return `${context.label}: ${context.raw} scans (${percentage}%)`;
                        }
                    }
                }
            }
        };

        departmentChart = new Chart(ctx, {
            type: 'doughnut',
            data: chartData,
            options: options
        });
    }

    // Initialize peak hours chart
    function initializePeakHoursChart(data) {
        const ctx = document.getElementById('peakHoursChart').getContext('2d');

        const chartData = {
            labels: data.labels || [],
            datasets: [{
                label: 'Scans per Hour',
                data: data.data || [],
                backgroundColor: chartColors.purple,
                borderColor: chartColors.purple,
                borderWidth: 1,
                borderRadius: 4
            }]
        };

        const options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.raw} scans`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        stepSize: 1,
                        color: '#6b7280'
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#6b7280',
                        maxRotation: 45
                    }
                }
            }
        };

        peakHoursChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: options
        });
    }

    // Initialize day of week chart
    function initializeDayOfWeekChart(data) {
        const ctx = document.getElementById('dayOfWeekChart').getContext('2d');

        const chartData = {
            labels: data.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                data: data.data || [0, 0, 0, 0, 0, 0, 0],
                backgroundColor: chartColors.info,
                borderColor: chartColors.info,
                borderWidth: 1,
                borderRadius: 4
            }]
        };

        const options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    display: false,
                    grid: { display: false }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#6b7280' }
                }
            }
        };

        dayOfWeekChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: options
        });
    }

    // Update top employees
    function updateTopEmployees(employees) {
        const tbody = document.getElementById('topEmployeesBody');
        const emptyState = document.getElementById('topEmployeesEmpty');
        const content = document.getElementById('topEmployeesContent');

        if (!employees || employees.length === 0) {
            tbody.innerHTML = '';
            emptyState.classList.remove('hidden');
            content.classList.add('hidden');
            return;
        }

        emptyState.classList.add('hidden');
        content.classList.remove('hidden');

        tbody.innerHTML = employees.map((employee, index) => {
            const rankColors = [
                'bg-yellow-100 text-yellow-800 border-yellow-200', // 1st
                'bg-gray-100 text-gray-800 border-gray-200',     // 2nd
                'bg-orange-100 text-orange-800 border-orange-200'  // 3rd
            ];
            const rankColor = index < 3 ? rankColors[index] : 'bg-blue-50 text-blue-800 border-blue-200';

            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full ${rankColor} font-semibold text-sm border">
                            ${index + 1}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div>
                                <div class="text-sm font-medium text-gray-900">${employee.formal_name || 'N/A'}</div>
                                <div class="text-sm text-gray-500">${employee.employee_code || 'N/A'}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                            ${employee.department || 'N/A'}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-semibold">${employee.total_scans || 0}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${employee.last_scan || 'N/A'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${(employee.avg_daily || 0).toFixed(1)}/day
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Update quick insights
    function updateQuickInsights(insights) {
        document.getElementById('mostActiveDay').textContent = insights.most_active_day || 'N/A';
        document.getElementById('peakHour').textContent = insights.peak_hour || 'N/A';
        document.getElementById('topDepartment').textContent = insights.top_department || 'N/A';
        document.getElementById('growthRate').textContent = formatPercentage(insights.growth_rate || 0);
    }

    // Update weekly comparison
    function updateWeeklyComparison(comparison) {
        const thisWeek = comparison.this_week || 0;
        const lastWeek = comparison.last_week || 0;
        const max = Math.max(thisWeek, lastWeek, 1);

        document.getElementById('thisWeek').textContent = thisWeek;
        document.getElementById('lastWeek').textContent = lastWeek;

        document.getElementById('thisWeekBar').style.width = (thisWeek / max * 100) + '%';
        document.getElementById('lastWeekBar').style.width = (lastWeek / max * 100) + '%';

        const change = lastWeek > 0 ? ((thisWeek - lastWeek) / lastWeek * 100) : 0;
        const changeText = formatPercentage(change) + ' vs last week';
        const changeElement = document.getElementById('weeklyChange');
        changeElement.textContent = changeText;
        changeElement.className = change >= 0 ? 'text-sm text-green-600' : 'text-sm text-red-600';
    }

    // Toggle chart type
    function toggleChartType(chart, type = null) {
        if (type) {
            currentChartTypes[chart] = type;
        } else {
            currentChartTypes[chart] = currentChartTypes[chart] === 'line' ? 'bar' : 'line';
        }

        // Reload charts with new type
        loadAnalytics();
    }

    // Update period labels
    function updatePeriodLabels() {
        const labels = {
            'daily': 'Last 30 days',
            'weekly': 'Last 12 weeks',
            'monthly': 'Last 12 months'
        };
        const label = labels[currentTimeRange] || 'This period';
        document.getElementById('avgPeriodLabel').textContent = label;
        document.getElementById('topEmployeesLabel').textContent = `Most frequent scans (${label})`;
    }

    // Chart loading states
    function showChartLoading(chart) {
        const element = document.getElementById(`${chart}ChartLoading`);
        if (element) element.classList.remove('hidden');
    }

    function hideChartLoading(chart) {
        const element = document.getElementById(`${chart}ChartLoading`);
        if (element) element.classList.add('hidden');
    }

    // Top employees loading states
    function showTopEmployeesLoading(show) {
        const loading = document.getElementById('topEmployeesLoading');
        const content = document.getElementById('topEmployeesContent');
        const empty = document.getElementById('topEmployeesEmpty');

        if (show) {
            loading.classList.remove('hidden');
            content.classList.add('hidden');
            empty.classList.add('hidden');
        } else {
            loading.classList.add('hidden');
        }
    }

    // Refresh analytics
    function refreshAnalytics() {
        loadAnalytics();
    }

    // Utility functions
    function formatNumber(num) {
        return num?.toLocaleString() || '0';
    }

    function formatCurrency(num) {
        return parseFloat(num || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatPercentage(num) {
        const value = parseFloat(num || 0);
        return (value >= 0 ? '+' : '') + value.toFixed(1) + '%';
    }

    function showError(title, message) {
        Swal.fire({
            icon: 'error',
            title: title,
            text: message,
            confirmButtonText: 'OK',
            confirmButtonColor: chartColors.primary
        });
    }
</script>

<style>
    /* Additional styles for better UI */
    #loadingState {
        min-height: 200px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Chart container styles */
    .chart-container {
        position: relative;
        height: 100%;
        width: 100%;
    }

    /* Loading overlay */
    .chart-loading {
        backdrop-filter: blur(2px);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .grid-cols-4 {
            grid-template-columns: repeat(2, 1fr);
        }

        .lg\:grid-cols-2,
        .lg\:grid-cols-3 {
            grid-template-columns: 1fr;
        }
    }

    /* Custom scrollbar for tables */
    .overflow-x-auto::-webkit-scrollbar {
        height: 6px;
    }

    .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb:hover {
        background: #a1a1a1;
    }

    /* Hover effects for metric cards */
    .bg-white.rounded-xl {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .bg-white.rounded-xl:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }

    /* Chart tooltips */
    .chart-tooltip {
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        border: 1px solid #2596be;
    }
</style>
@endsection
