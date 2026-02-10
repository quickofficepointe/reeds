@extends('reeds.vendor.layout.vendorlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-text-black">Performance Analytics</h1>
                <p class="text-gray-600 mt-2">Track your performance with detailed analytics</p>
            </div>

            <!-- Date Range Selector -->
            <div class="mt-4 md:mt-0 flex space-x-3">
                <select id="timeRange" onchange="loadAnalytics()"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                    <option value="daily">Daily (Last 30 days)</option>
                    <option value="weekly" selected>Weekly (Last 12 weeks)</option>
                    <option value="monthly">Monthly (Last 12 months)</option>
                </select>
                <button onclick="refreshAnalytics()" class="bg-secondary-blue text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200 flex items-center">
                    <i class="fas fa-sync-alt mr-2"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
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
            <div class="mt-4">
                <span class="text-xs text-gray-500" id="periodLabel">This period</span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Revenue</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="totalRevenue">Ksh 0.00</p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-green-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-xs text-gray-500" id="revenuePeriodLabel">This period</span>
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
            <div class="mt-4">
                <span class="text-xs text-gray-500" id="avgPeriodLabel">This period</span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Best Day</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="bestDay">N/A</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-trophy text-yellow-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-xs text-gray-500" id="bestDayCount">0 scans</span>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Daily Scans Chart -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-text-black">Scan Trend</h3>
                <span class="text-sm text-gray-500" id="scanChartLabel">Daily Scans</span>
            </div>
            <div class="h-64">
                <canvas id="dailyScansChart"></canvas>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-text-black">Revenue Trend</h3>
                <span class="text-sm text-gray-500" id="revenueChartLabel">Daily Revenue</span>
            </div>
            <div class="h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Department Distribution & Peak Hours -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Department Breakdown -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-text-black">Department Distribution</h3>
                <span class="text-sm text-gray-500">Top Departments</span>
            </div>
            <div class="h-64">
                <canvas id="departmentChart"></canvas>
            </div>
        </div>

        <!-- Peak Hours -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-text-black">Peak Scanning Hours</h3>
                <span class="text-sm text-gray-500">6 AM - 6 PM</span>
            </div>
            <div class="h-64">
                <canvas id="peakHoursChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Employees -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-text-black">Top Employees</h3>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>

<script>
    let dailyScansChart = null;
    let revenueChart = null;
    let departmentChart = null;
    let peakHoursChart = null;
    let currentTimeRange = 'weekly';

    // Chart colors
    const chartColors = {
        primary: '#2596be',
        secondary: '#e92c2a',
        success: '#10b981',
        warning: '#f59e0b',
        danger: '#ef4444',
        info: '#3b82f6',
        purple: '#8b5cf6',
        pink: '#ec4899'
    };

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Performance analytics page loaded');
        loadAnalytics();

        // Initialize with weekly data
        currentTimeRange = 'weekly';
        document.getElementById('timeRange').value = currentTimeRange;
    });

    // Load analytics data
    async function loadAnalytics() {
        currentTimeRange = document.getElementById('timeRange').value;

        console.log(`Loading ${currentTimeRange} analytics...`);

        showGlobalLoading(true);
        showTopEmployeesLoading(true);

        try {
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
            const data = await response.json();

            console.log('Analytics response:', data);

            if (data.success) {
                updateMetrics(data.metrics);
                updateCharts(data.charts);
                updateTopEmployees(data.top_employees || []);
                updateChartLabels(currentTimeRange);
            } else {
                throw new Error(data.message || 'Failed to load analytics data');
            }
        } catch (error) {
            console.error('Error loading analytics:', error);
            showError('Error Loading Analytics', error.message || 'Failed to load analytics data. Please try again.');
            showTopEmployeesEmpty();
        } finally {
            showGlobalLoading(false);
            showTopEmployeesLoading(false);
        }
    }

    // Update metrics
    function updateMetrics(metrics) {
        if (!metrics) {
            console.warn('No metrics provided');
            return;
        }

        document.getElementById('totalScans').textContent = metrics.total_scans.toLocaleString();
        document.getElementById('totalRevenue').textContent = 'Ksh ' + metrics.total_revenue.toLocaleString('en-KE', { minimumFractionDigits: 2 });
        document.getElementById('avgDailyScans').textContent = metrics.average_daily.toFixed(1);

        if (metrics.best_day && metrics.best_day.date) {
            document.getElementById('bestDay').textContent = metrics.best_day.date;
            document.getElementById('bestDayCount').textContent = metrics.best_day.count + ' scans';
        } else {
            document.getElementById('bestDay').textContent = 'N/A';
            document.getElementById('bestDayCount').textContent = '0 scans';
        }

        // Update period labels
        updatePeriodLabels();
    }

    // Update period labels based on time range
    function updatePeriodLabels() {
        const periodLabels = {
            'daily': 'Last 30 days',
            'weekly': 'Last 12 weeks',
            'monthly': 'Last 12 months'
        };

        const label = periodLabels[currentTimeRange] || 'This period';
        document.getElementById('periodLabel').textContent = label;
        document.getElementById('revenuePeriodLabel').textContent = label;
        document.getElementById('avgPeriodLabel').textContent = label;
    }

    // Update chart labels
    function updateChartLabels(timeRange) {
        const labels = {
            'daily': { scan: 'Daily Scans (Last 30 days)', revenue: 'Daily Revenue (Last 30 days)' },
            'weekly': { scan: 'Weekly Scans (Last 12 weeks)', revenue: 'Weekly Revenue (Last 12 weeks)' },
            'monthly': { scan: 'Monthly Scans (Last 12 months)', revenue: 'Monthly Revenue (Last 12 months)' }
        };

        const currentLabels = labels[timeRange] || labels['weekly'];
        document.getElementById('scanChartLabel').textContent = currentLabels.scan;
        document.getElementById('revenueChartLabel').textContent = currentLabels.revenue;
    }

    // Update charts
    function updateCharts(charts) {
        if (!charts) {
            console.warn('No charts data provided');
            return;
        }

        // Destroy existing charts
        if (dailyScansChart) dailyScansChart.destroy();
        if (revenueChart) revenueChart.destroy();
        if (departmentChart) departmentChart.destroy();
        if (peakHoursChart) peakHoursChart.destroy();

        // Daily Scans Chart
        const dailyCtx = document.getElementById('dailyScansChart').getContext('2d');
        dailyScansChart = new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: charts.daily_scans?.labels || [],
                datasets: [{
                    label: 'Scans',
                    data: charts.daily_scans?.data || [],
                    borderColor: chartColors.primary,
                    backgroundColor: 'rgba(37, 150, 190, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: chartColors.primary,
                    pointBorderColor: '#ffffff',
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
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: chartColors.primary,
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            stepSize: 5,
                            color: '#6b7280'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6b7280'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        revenueChart = new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: charts.revenue?.labels || [],
                datasets: [{
                    label: 'Revenue (Ksh)',
                    data: charts.revenue?.data || [],
                    backgroundColor: chartColors.success,
                    borderColor: chartColors.success,
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
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
                                return 'Revenue: Ksh ' + context.raw.toLocaleString('en-KE', { minimumFractionDigits: 2 });
                            }
                        },
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: '#6b7280',
                            callback: function(value) {
                                return 'Ksh ' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6b7280',
                            maxRotation: 45
                        }
                    }
                }
            }
        });

        // Department Chart
        const deptCtx = document.getElementById('departmentChart').getContext('2d');
        const departmentColors = [
            chartColors.primary,
            chartColors.secondary,
            chartColors.success,
            chartColors.warning,
            chartColors.purple,
            chartColors.info,
            chartColors.pink,
            '#f97316'
        ];

        departmentChart = new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: charts.departments?.labels || [],
                datasets: [{
                    data: charts.departments?.data || [],
                    backgroundColor: departmentColors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
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
                            color: '#6b7280'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((context.raw / total) * 100);
                                return `${context.label}: ${context.raw} scans (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Peak Hours Chart
        const peakCtx = document.getElementById('peakHoursChart').getContext('2d');
        peakHoursChart = new Chart(peakCtx, {
            type: 'bar',
            data: {
                labels: charts.peak_hours?.labels || [],
                datasets: [{
                    label: 'Scans per Hour',
                    data: charts.peak_hours?.data || [],
                    backgroundColor: chartColors.purple,
                    borderColor: chartColors.purple,
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
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
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            stepSize: 1,
                            color: '#6b7280'
                        },
                        title: {
                            display: true,
                            text: 'Number of Scans',
                            color: '#6b7280'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6b7280'
                        },
                        title: {
                            display: true,
                            text: 'Time of Day',
                            color: '#6b7280'
                        }
                    }
                }
            }
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
                'bg-yellow-100 text-yellow-800', // 1st
                'bg-gray-100 text-gray-800',     // 2nd
                'bg-orange-100 text-orange-800'  // 3rd
            ];
            const rankColor = index < 3 ? rankColors[index] : 'bg-blue-100 text-blue-800';

            // Calculate average daily (simplified)
            const avgDaily = currentTimeRange === 'daily'
                ? employee.total_scans / 30
                : currentTimeRange === 'weekly'
                ? employee.total_scans / 12
                : employee.total_scans / 12;

            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-8 h-8 flex items-center justify-center rounded-full ${rankColor} font-semibold mr-3">
                                ${index + 1}
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">${employee.formal_name}</div>
                                <div class="text-sm text-gray-500">${employee.employee_code}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${employee.department}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            ${employee.total_scans} scans
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${employee.last_scan}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${avgDaily.toFixed(1)}/day
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Refresh analytics
    function refreshAnalytics() {
        loadAnalytics();
    }

    // Loading states
    function showGlobalLoading(show) {
        // You can add a global loading indicator if needed
        if (show) {
            // Show loading state
        } else {
            // Hide loading state
        }
    }

    function showTopEmployeesLoading(show) {
        const loading = document.getElementById('topEmployeesLoading');
        const content = document.getElementById('topEmployeesContent');

        if (show) {
            loading.classList.remove('hidden');
            content.classList.add('hidden');
        } else {
            loading.classList.add('hidden');
        }
    }

    function showTopEmployeesEmpty() {
        const emptyState = document.getElementById('topEmployeesEmpty');
        const content = document.getElementById('topEmployeesContent');
        const loading = document.getElementById('topEmployeesLoading');

        loading.classList.add('hidden');
        emptyState.classList.remove('hidden');
        content.classList.add('hidden');
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
        height: 256px;
        width: 100%;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .grid-cols-4 {
            grid-template-columns: repeat(2, 1fr);
        }

        .lg\:grid-cols-2 {
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
</style>
@endsection
