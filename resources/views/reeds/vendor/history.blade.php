@extends('reeds.vendor.layout.vendorlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-text-black">Scan History</h1>
                <p class="text-gray-600 mt-2">View and analyze your scan transactions</p>
            </div>
        </div>
    </div>

    <!-- Enhanced Date Filter -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-8">
        <div class="flex flex-col lg:flex-row gap-4 items-end">
            <!-- Quick Period Selection -->
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Quick Period</label>
                <div class="flex flex-wrap gap-2">
                    <button onclick="setPeriod('today')" class="period-btn px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition text-sm" data-period="today">
                        <i class="fas fa-calendar-day mr-1"></i> Today
                    </button>
                    <button onclick="setPeriod('yesterday')" class="period-btn px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition text-sm" data-period="yesterday">
                        <i class="fas fa-calendar-day mr-1"></i> Yesterday
                    </button>
                    <button onclick="setPeriod('this_week')" class="period-btn px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition text-sm" data-period="this_week">
                        <i class="fas fa-calendar-week mr-1"></i> This Week
                    </button>
                    <button onclick="setPeriod('last_week')" class="period-btn px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition text-sm" data-period="last_week">
                        <i class="fas fa-calendar-week mr-1"></i> Last Week
                    </button>
                    <button onclick="setPeriod('this_month')" class="period-btn px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition text-sm" data-period="this_month">
                        <i class="fas fa-calendar-alt mr-1"></i> This Month
                    </button>
                    <button onclick="setPeriod('last_month')" class="period-btn px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition text-sm" data-period="last_month">
                        <i class="fas fa-calendar-alt mr-1"></i> Last Month
                    </button>
                    <button onclick="setPeriod('last_30_days')" class="period-btn px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition text-sm bg-secondary-blue text-white border-secondary-blue" data-period="last_30_days">
                        <i class="fas fa-calendar-check mr-1"></i> Last 30 Days
                    </button>
                </div>
            </div>
        </div>

        <!-- Custom Date Range -->
        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Custom Date Range</label>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="flex-1">
                            <input type="date" id="startDate" value="{{ now()->subDays(30)->format('Y-m-d') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                        </div>
                        <div class="flex items-center justify-center text-gray-500">
                            <i class="fas fa-arrow-right hidden sm:block"></i>
                            <i class="fas fa-arrow-down sm:hidden"></i>
                        </div>
                        <div class="flex-1">
                            <input type="date" id="endDate" value="{{ now()->format('Y-m-d') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                        </div>
                    </div>
                </div>
                <button onclick="loadHistoryWithCustomRange()" class="bg-secondary-blue text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200 flex items-center whitespace-nowrap">
                    <i class="fas fa-search mr-2"></i> Apply Range
                </button>
            </div>
        </div>
    </div>

    <!-- Enhanced Stats Overview -->
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
                    <span class="text-gray-500">Period Average</span>
                    <span class="font-semibold" id="avgDailyScans">0/day</span>
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
                    <span class="text-gray-500">Avg per Scan</span>
                    <span class="font-semibold" id="avgPerScan">Ksh 0.00</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Unique Employees</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="uniqueEmployees">0</p>
                </div>
                <div class="w-12 h-12 bg-purple-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-purple-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Repeat Rate</span>
                    <span class="font-semibold" id="repeatRate">0%</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Busiest Day</p>
                    <p class="text-lg font-bold text-text-black mt-2" id="busiestDay">N/A</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-yellow-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500" id="busiestDayCount">0 scans</span>
                    <span class="font-semibold" id="busiestDayRevenue">Ksh 0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Mini Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Daily Trend Mini Chart -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-text-black">Daily Trend</h3>
                <span class="text-sm text-gray-500" id="trendPeriod">Last 7 days</span>
            </div>
            <div class="h-48">
                <canvas id="miniTrendChart"></canvas>
            </div>
        </div>

        <!-- Department Distribution Mini Chart -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-text-black">By Department</h3>
                <span class="text-sm text-gray-500">Top 5</span>
            </div>
            <div class="h-48">
                <canvas id="miniDepartmentChart"></canvas>
            </div>
        </div>
    </div>

    <!-- History Table with Summary -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-text-black">Transaction History</h3>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500" id="transactionCount">0 transactions</span>
                    <button onclick="exportHistory()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="py-12 text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-secondary-blue mx-auto mb-4"></div>
            <p class="text-gray-600">Loading history...</p>
        </div>

        <!-- Table Content -->
        <div id="tableContent" class="hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction Code</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- History will be loaded here -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700" id="paginationInfo">
                        Showing <span id="startRecord">0</span> to <span id="endRecord">0</span> of <span id="totalRecords">0</span> results
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="prevPage()" id="prevBtn" class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Previous
                        </button>
                        <button onclick="nextPage()" id="nextBtn" class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="py-12 text-center hidden">
            <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No transactions found for the selected period</p>
            <p class="text-sm text-gray-400 mt-1">Try selecting a different date range</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // State management
    let currentPeriod = 'last_30_days';
    let currentPage = 1;
    let itemsPerPage = 20;
    let totalTransactions = 0;
    let allTransactions = [];
    let miniTrendChart = null;
    let miniDepartmentChart = null;

    // Chart colors
    const chartColors = {
        primary: '#2596be',
        secondary: '#e92c2a',
        success: '#10b981',
        warning: '#f59e0b',
        purple: '#8b5cf6'
    };

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('History page loaded');
        highlightActivePeriod('last_30_days');
        loadHistoryWithRange(now().subDays(30).format('YYYY-MM-DD'), now().format('YYYY-MM-DD'));
    });

    // Set period from quick buttons
    function setPeriod(period) {
        currentPeriod = period;
        highlightActivePeriod(period);

        const { startDate, endDate } = getPeriodDates(period);
        document.getElementById('startDate').value = startDate;
        document.getElementById('endDate').value = endDate;

        loadHistoryWithRange(startDate, endDate);
    }

    // Get dates for period
    function getPeriodDates(period) {
        const today = new Date();
        let startDate, endDate;

        switch(period) {
            case 'today':
                startDate = formatDate(today);
                endDate = formatDate(today);
                break;
            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                startDate = formatDate(yesterday);
                endDate = formatDate(yesterday);
                break;
            case 'this_week':
                startDate = formatDate(getStartOfWeek(today));
                endDate = formatDate(today);
                break;
            case 'last_week':
                const lastWeekStart = getStartOfWeek(today);
                lastWeekStart.setDate(lastWeekStart.getDate() - 7);
                const lastWeekEnd = new Date(lastWeekStart);
                lastWeekEnd.setDate(lastWeekEnd.getDate() + 6);
                startDate = formatDate(lastWeekStart);
                endDate = formatDate(lastWeekEnd);
                break;
            case 'this_month':
                startDate = formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
                endDate = formatDate(today);
                break;
            case 'last_month':
                startDate = formatDate(new Date(today.getFullYear(), today.getMonth() - 1, 1));
                endDate = formatDate(new Date(today.getFullYear(), today.getMonth(), 0));
                break;
            case 'last_30_days':
            default:
                const thirtyDaysAgo = new Date(today);
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                startDate = formatDate(thirtyDaysAgo);
                endDate = formatDate(today);
                break;
        }

        return { startDate, endDate };
    }

    // Load history with custom range
    function loadHistoryWithCustomRange() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        if (!startDate || !endDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Range',
                text: 'Please select both start and end dates'
            });
            return;
        }

        if (startDate > endDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Range',
                text: 'Start date cannot be after end date'
            });
            return;
        }

        highlightActivePeriod(null);
        loadHistoryWithRange(startDate, endDate);
    }

    // Load history with date range
    async function loadHistoryWithRange(startDate, endDate) {
        showLoading(true);
        hideTableContent();
        hideEmptyState();

        try {
            const response = await fetch(`{{ route('vendor.history.range') }}?start_date=${startDate}&end_date=${endDate}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('API Response:', data);

            if (data.success) {
                allTransactions = data.transactions || [];
                totalTransactions = allTransactions.length;
                currentPage = 1;

                updateStats(data.stats, data.summary);
                updateMiniCharts(data.charts);
                updateTable();

                if (allTransactions.length > 0) {
                    showTableContent();
                    document.getElementById('transactionCount').textContent =
                        `${allTransactions.length} transaction${allTransactions.length !== 1 ? 's' : ''}`;
                } else {
                    showEmptyState();
                }
            } else {
                throw new Error(data.message || 'Failed to load history');
            }
        } catch (error) {
            console.error('Error loading history:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error Loading History',
                text: error.message || 'Failed to load history data'
            });
            showEmptyState();
        } finally {
            showLoading(false);
        }
    }

    // Update enhanced stats
    function updateStats(stats, summary) {
        if (!stats) return;

        document.getElementById('totalScans').textContent = stats.total_scans || 0;
        document.getElementById('totalRevenue').textContent =
            'Ksh ' + parseFloat(stats.total_revenue || 0).toLocaleString('en-KE', { minimumFractionDigits: 2 });

        document.getElementById('avgDailyScans').textContent =
            (stats.avg_daily || 0).toFixed(1) + '/day';

        const avgPerScan = stats.total_scans > 0
            ? (stats.total_revenue / stats.total_scans)
            : 0;
        document.getElementById('avgPerScan').textContent =
            'Ksh ' + avgPerScan.toLocaleString('en-KE', { minimumFractionDigits: 2 });

        // Unique employees and repeat rate
        document.getElementById('uniqueEmployees').textContent = summary?.unique_employees || 0;

        const repeatRate = stats.total_scans > 0 && summary?.unique_employees > 0
            ? ((stats.total_scans - summary.unique_employees) / stats.total_scans * 100).toFixed(1)
            : 0;
        document.getElementById('repeatRate').textContent = repeatRate + '%';

        // Busiest day
        if (summary?.busiest_day) {
            document.getElementById('busiestDay').textContent = summary.busiest_day.date;
            document.getElementById('busiestDayCount').textContent = summary.busiest_day.count + ' scans';
            document.getElementById('busiestDayRevenue').textContent =
                'Ksh ' + parseFloat(summary.busiest_day.revenue || 0).toLocaleString('en-KE', { minimumFractionDigits: 2 });
        } else {
            document.getElementById('busiestDay').textContent = 'N/A';
            document.getElementById('busiestDayCount').textContent = '0 scans';
            document.getElementById('busiestDayRevenue').textContent = 'Ksh 0';
        }
    }

    // Update mini charts
    function updateMiniCharts(charts) {
        if (!charts) return;

        // Destroy existing charts
        if (miniTrendChart) miniTrendChart.destroy();
        if (miniDepartmentChart) miniDepartmentChart.destroy();

        // Mini Trend Chart
        const trendCtx = document.getElementById('miniTrendChart').getContext('2d');
        miniTrendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: charts.trend?.labels || [],
                datasets: [{
                    data: charts.trend?.data || [],
                    borderColor: chartColors.primary,
                    backgroundColor: 'rgba(37, 150, 190, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointHoverRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { display: false },
                        ticks: { stepSize: 1 }
                    },
                    x: {
                        display: false,
                        grid: { display: false }
                    }
                }
            }
        });

        // Mini Department Chart
        const deptCtx = document.getElementById('miniDepartmentChart').getContext('2d');
        miniDepartmentChart = new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: charts.departments?.labels || [],
                datasets: [{
                    data: charts.departments?.data || [],
                    backgroundColor: [
                        chartColors.primary,
                        chartColors.success,
                        chartColors.warning,
                        chartColors.purple,
                        chartColors.secondary
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 10, padding: 10 }
                    }
                }
            }
        });
    }

    // Update table with pagination
    function updateTable() {
        const tbody = document.getElementById('historyTableBody');
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, totalTransactions);
        const pageTransactions = allTransactions.slice(startIndex, endIndex);

        if (pageTransactions.length === 0) {
            tbody.innerHTML = '';
            return;
        }

        tbody.innerHTML = pageTransactions.map(transaction => `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 font-medium">${transaction.meal_date}</div>
                    <div class="text-sm text-gray-500">${transaction.meal_time}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="font-medium text-gray-900">${transaction.employee.formal_name}</div>
                    <div class="text-sm text-gray-500">${transaction.employee.employee_code}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                        ${transaction.employee.department.name}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="font-semibold text-green-600">
                        Ksh ${parseFloat(transaction.amount).toLocaleString('en-KE', { minimumFractionDigits: 2 })}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <code class="text-xs bg-gray-100 px-2 py-1 rounded font-mono">
                        ${transaction.transaction_code}
                    </code>
                </td>
            </tr>
        `).join('');

        // Update pagination info
        document.getElementById('startRecord').textContent = totalTransactions > 0 ? startIndex + 1 : 0;
        document.getElementById('endRecord').textContent = endIndex;
        document.getElementById('totalRecords').textContent = totalTransactions;

        // Update pagination buttons
        document.getElementById('prevBtn').disabled = currentPage === 1;
        document.getElementById('nextBtn').disabled = endIndex >= totalTransactions;
    }

    // Pagination functions
    function prevPage() {
        if (currentPage > 1) {
            currentPage--;
            updateTable();
        }
    }

    function nextPage() {
        if ((currentPage * itemsPerPage) < totalTransactions) {
            currentPage++;
            updateTable();
        }
    }

    // Helper function to format date
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Helper function to get start of week (Monday)
    function getStartOfWeek(date) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(d.setDate(diff));
    }

    // Helper function for current date
    function now() {
        return new Date();
    }

    // Highlight active period button
    function highlightActivePeriod(period) {
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.classList.remove('bg-secondary-blue', 'text-white', 'border-secondary-blue');
            btn.classList.add('border-gray-300', 'hover:bg-gray-50');

            if (btn.dataset.period === period) {
                btn.classList.remove('border-gray-300', 'hover:bg-gray-50');
                btn.classList.add('bg-secondary-blue', 'text-white', 'border-secondary-blue');
            }
        });
    }

    // UI Helper Functions
    function showLoading(show) {
        document.getElementById('loadingState').classList.toggle('hidden', !show);
    }

    function showTableContent() {
        document.getElementById('tableContent').classList.remove('hidden');
        document.getElementById('emptyState').classList.add('hidden');
    }

    function hideTableContent() {
        document.getElementById('tableContent').classList.add('hidden');
    }

    function showEmptyState() {
        document.getElementById('emptyState').classList.remove('hidden');
        document.getElementById('tableContent').classList.add('hidden');
    }

    function hideEmptyState() {
        document.getElementById('emptyState').classList.add('hidden');
    }

    // Export function
    function exportHistory() {
        if (allTransactions.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'No Data',
                text: 'No transactions to export'
            });
            return;
        }

        // Convert to CSV
        const headers = ['Date', 'Time', 'Employee Name', 'Employee Code', 'Department', 'Amount', 'Transaction Code'];
        const csvData = allTransactions.map(t => [
            t.meal_date,
            t.meal_time,
            t.employee.formal_name,
            t.employee.employee_code,
            t.employee.department.name,
            t.amount,
            t.transaction_code
        ]);

        const csv = [headers, ...csvData].map(row => row.join(',')).join('\n');

        // Download CSV
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `scan_history_${document.getElementById('startDate').value}_to_${document.getElementById('endDate').value}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
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

    #emptyState {
        min-height: 200px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .period-btn {
        transition: all 0.2s ease;
    }

    .period-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Responsive adjustments */
    @media (max-width: 640px) {
        .grid-cols-4 {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>
@endsection
