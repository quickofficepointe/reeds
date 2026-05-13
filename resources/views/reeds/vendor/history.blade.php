@extends('reeds.vendor.layout.vendorlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-text-black">Scan History</h1>
                <p class="text-gray-600 mt-2">View and analyze your scan transactions</p>
                <div class="flex items-center space-x-4 mt-2">
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-circle mr-1 text-green-500 text-xs"></i> Regular: 65 KES
                    </span>
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                        <i class="fas fa-star mr-1 text-purple-500 text-xs"></i> Reward: 200 KES
                    </span>
                </div>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-3">
                <button onclick="exportHistory()" class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300 flex items-center space-x-2 shadow-md">
                    <i class="fas fa-file-excel mr-2"></i>
                    <span>Export CSV</span>
                </button>
                <button onclick="window.print()" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition duration-300 flex items-center space-x-2">
                    <i class="fas fa-print mr-2"></i>
                    <span>Print</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
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
                            <div class="relative">
                                <i class="fas fa-calendar-alt absolute left-3 top-3 text-gray-400"></i>
                                <input type="date" id="startDate" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                            </div>
                        </div>
                        <div class="flex items-center justify-center text-gray-500">
                            <i class="fas fa-arrow-right hidden sm:block"></i>
                            <i class="fas fa-arrow-down sm:hidden"></i>
                        </div>
                        <div class="flex-1">
                            <div class="relative">
                                <i class="fas fa-calendar-alt absolute left-3 top-3 text-gray-400"></i>
                                <input type="date" id="endDate" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                            </div>
                        </div>
                    </div>
                </div>
                <button onclick="loadHistoryWithCustomRange()" class="bg-secondary-blue text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200 flex items-center whitespace-nowrap">
                    <i class="fas fa-search mr-2"></i> Apply Range
                </button>
            </div>
        </div>

        <!-- Active Filters Display -->
        <div id="activeFilters" class="mt-4 p-3 bg-blue-50 rounded-lg hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm font-medium text-blue-800">Active Filters:</span>
                    <span id="filterText" class="text-sm text-blue-600 ml-2"></span>
                </div>
                <button onclick="clearFilters()" class="text-sm text-blue-600 hover:text-blue-800">
                    <i class="fas fa-times mr-1"></i> Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-6 mb-8">
        <!-- Total Scans -->
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

        <!-- Total Revenue -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                    <p class="text-2xl font-bold text-green-600 mt-2" id="totalRevenue">KSh 0.00</p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-green-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Regular (65)</span>
                    <span class="font-semibold text-green-600" id="regularRevenue">KSh 0</span>
                </div>
                <div class="flex justify-between text-sm mt-1">
                    <span class="text-gray-500">Reward (200)</span>
                    <span class="font-semibold text-purple-600" id="rewardRevenue">KSh 0</span>
                </div>
            </div>
        </div>

        <!-- Regular Meals -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Regular Meals</p>
                    <p class="text-2xl font-bold text-green-600 mt-2" id="regularScans">0</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-utensils text-green-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <span class="text-xs text-gray-500">65 KES per meal</span>
            </div>
        </div>

        <!-- Reward Meals -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Reward Meals</p>
                    <p class="text-2xl font-bold text-purple-600 mt-2" id="rewardScans">0</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-star text-purple-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <span class="text-xs text-gray-500">200 KES per reward meal 🎖️</span>
            </div>
        </div>

        <!-- Unique Employees -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Unique Employees</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="uniqueEmployees">0</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <span class="text-xs text-gray-500" id="repeatRate">Repeat rate: 0%</span>
            </div>
        </div>

        <!-- Busiest Day -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Busiest Day</p>
                    <p class="text-lg font-bold text-text-black mt-2" id="busiestDay">N/A</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500" id="busiestDayCount">0 scans</span>
                    <span class="font-semibold text-green-600" id="busiestDayRevenue">KSh 0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Mini Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Daily Trend Chart -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-text-black">Daily Trend</h3>
                <span class="text-sm text-gray-500" id="trendPeriod">Last 7 days</span>
            </div>
            <div class="h-64">
                <canvas id="miniTrendChart"></canvas>
            </div>
        </div>

        <!-- Regular vs Reward Chart -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-text-black">Regular vs Reward</h3>
                <span class="text-sm text-gray-500">Selected Period</span>
            </div>
            <div class="h-64">
                <canvas id="comparisonChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Transaction History Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-text-black">Transaction History</h3>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500" id="transactionCount">0 transactions</span>
                    <button onclick="refreshHistory()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-sync-alt"></i>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction Code</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Dynamic content -->
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
                        <button onclick="prevPage()" id="prevBtn" class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            <i class="fas fa-chevron-left mr-1"></i> Previous
                        </button>
                        <button onclick="nextPage()" id="nextBtn" class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            Next <i class="fas fa-chevron-right ml-1"></i>
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
    let allTransactions = [];
    let currentPage = 1;
    let itemsPerPage = 20;
    let totalTransactions = 0;
    let miniTrendChart = null;
    let comparisonChart = null;

    // Helper Functions
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    function formatDate(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }

    function getStartOfWeek(date) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(d.setDate(diff));
    }

    // UI Helper Functions
    function showLoading(show) {
        document.getElementById('loadingState').classList.toggle('hidden', !show);
        if (!show) {
            document.getElementById('tableContent').classList.remove('hidden');
            document.getElementById('emptyState').classList.add('hidden');
        }
    }

    function showEmptyState() {
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('tableContent').classList.add('hidden');
        document.getElementById('emptyState').classList.remove('hidden');
    }

    function updateFilterDisplay(range, startDate, endDate) {
        const filterElement = document.getElementById('activeFilters');
        const filterText = document.getElementById('filterText');
        const formatDateDisplay = (date) => new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

        filterText.textContent = `${range} (${formatDateDisplay(startDate)} - ${formatDateDisplay(endDate)})`;
        filterElement.classList.remove('hidden');
    }

    function clearFilters() {
        setPeriod('last_30_days');
        document.getElementById('activeFilters').classList.add('hidden');
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
    }

    // Date Range Functions
    function setPeriod(period) {
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

        document.getElementById('startDate').value = startDate;
        document.getElementById('endDate').value = endDate;

        updateFilterDisplay(period.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()), startDate, endDate);
        loadHistoryWithRange(startDate, endDate);

        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.classList.remove('bg-secondary-blue', 'text-white', 'border-secondary-blue');
            btn.classList.add('border-gray-300', 'hover:bg-gray-50');
            if (btn.dataset.period === period) {
                btn.classList.remove('border-gray-300', 'hover:bg-gray-50');
                btn.classList.add('bg-secondary-blue', 'text-white', 'border-secondary-blue');
            }
        });
    }

    function loadHistoryWithCustomRange() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        if (!startDate || !endDate) {
            Swal.fire({ icon: 'warning', title: 'Invalid Range', text: 'Please select both start and end dates' });
            return;
        }

        if (startDate > endDate) {
            Swal.fire({ icon: 'warning', title: 'Invalid Range', text: 'Start date cannot be after end date' });
            return;
        }

        updateFilterDisplay('Custom Range', startDate, endDate);
        loadHistoryWithRange(startDate, endDate);
    }

    async function loadHistoryWithRange(startDate, endDate) {
        showLoading(true);
        document.getElementById('tableContent').classList.add('hidden');
        document.getElementById('emptyState').classList.add('hidden');

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
                updateCharts(data);
                updateTable();

                if (allTransactions.length > 0) {
                    showLoading(false);
                    document.getElementById('transactionCount').textContent = `${allTransactions.length} transaction${allTransactions.length !== 1 ? 's' : ''}`;
                } else {
                    showEmptyState();
                }
            } else {
                throw new Error(data.message || 'Failed to load history');
            }
        } catch (error) {
            console.error('Error loading history:', error);
            Swal.fire({ icon: 'error', title: 'Error Loading History', text: error.message || 'Failed to load history data' });
            showEmptyState();
        } finally {
            showLoading(false);
        }
    }

    function updateStats(stats, summary) {
        if (!stats) return;

        // Basic stats
        document.getElementById('totalScans').textContent = stats.total_scans || 0;
        document.getElementById('totalRevenue').textContent = 'KSh ' + parseFloat(stats.total_revenue || 0).toLocaleString('en-KE', { minimumFractionDigits: 2 });
        document.getElementById('avgDailyScans').textContent = (stats.avg_daily || 0).toFixed(1) + '/day';

        // Regular vs Reward breakdown
        document.getElementById('regularScans').textContent = stats.regular_scans || 0;
        document.getElementById('rewardScans').textContent = stats.reward_scans || 0;
        document.getElementById('regularRevenue').textContent = 'KSh ' + parseFloat(stats.regular_revenue || 0).toLocaleString('en-KE', { minimumFractionDigits: 2 });
        document.getElementById('rewardRevenue').textContent = 'KSh ' + parseFloat(stats.reward_revenue || 0).toLocaleString('en-KE', { minimumFractionDigits: 2 });

        // Unique employees and repeat rate
        document.getElementById('uniqueEmployees').textContent = summary?.unique_employees || 0;

        const repeatRate = stats.total_scans > 0 && summary?.unique_employees > 0
            ? ((stats.total_scans - summary.unique_employees) / stats.total_scans * 100).toFixed(1)
            : 0;
        document.getElementById('repeatRate').textContent = `Repeat rate: ${repeatRate}%`;

        // Busiest day
        if (summary?.busiest_day) {
            document.getElementById('busiestDay').textContent = summary.busiest_day.date;
            document.getElementById('busiestDayCount').textContent = summary.busiest_day.count + ' scans';
            document.getElementById('busiestDayRevenue').textContent = 'KSh ' + parseFloat(summary.busiest_day.revenue || 0).toLocaleString('en-KE', { minimumFractionDigits: 2 });
        } else {
            document.getElementById('busiestDay').textContent = 'N/A';
            document.getElementById('busiestDayCount').textContent = '0 scans';
            document.getElementById('busiestDayRevenue').textContent = 'KSh 0';
        }
    }

    function updateCharts(data) {
        // Destroy existing charts
        if (miniTrendChart) miniTrendChart.destroy();
        if (comparisonChart) comparisonChart.destroy();

        // Daily Trend Chart
        const trendCtx = document.getElementById('miniTrendChart').getContext('2d');
        miniTrendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: data.charts?.trend?.labels || [],
                datasets: [{
                    data: data.charts?.trend?.data || [],
                    borderColor: '#2596be',
                    backgroundColor: 'rgba(37, 150, 190, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => `Scans: ${ctx.parsed.y}` } }
                },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });

        // Regular vs Reward Pie Chart
        const compCtx = document.getElementById('comparisonChart').getContext('2d');
        comparisonChart = new Chart(compCtx, {
            type: 'doughnut',
            data: {
                labels: ['Regular Meals (65 KES)', 'Reward Meals (200 KES)'],
                datasets: [{
                    data: [data.stats?.regular_scans || 0, data.stats?.reward_scans || 0],
                    backgroundColor: ['#10b981', '#8b5cf6'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10 } },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.raw} meals (${((ctx.raw / (data.stats?.total_scans || 1)) * 100).toFixed(1)}%)` } }
                }
            }
        });
    }

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
            <tr class="hover:bg-gray-50 transition duration-150">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 font-medium">${transaction.meal_date}</div>
                    <div class="text-sm text-gray-500">${transaction.meal_time}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="font-medium text-gray-900">${escapeHtml(transaction.employee.formal_name)}</div>
                    <div class="text-sm text-gray-500">${escapeHtml(transaction.employee.employee_code)}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                        ${escapeHtml(transaction.employee.department.name)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${transaction.is_reward ?
                        '<span class="font-semibold text-purple-600">KSh 200.00 🎖️</span>' :
                        '<span class="font-semibold text-green-600">KSh 65.00</span>'
                    }
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${transaction.is_reward ?
                        '<span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800"><i class="fas fa-star mr-1"></i>Reward</span>' :
                        '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800"><i class="fas fa-utensils mr-1"></i>Regular</span>'
                    }
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

    function refreshHistory() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        if (startDate && endDate) {
            loadHistoryWithRange(startDate, endDate);
        } else {
            setPeriod('last_30_days');
        }
        Swal.fire({ icon: 'success', title: 'Refreshed!', text: 'History updated', timer: 1500, showConfirmButton: false });
    }

    function exportHistory() {
        if (allTransactions.length === 0) {
            Swal.fire({ icon: 'info', title: 'No Data', text: 'No transactions to export' });
            return;
        }

        // Prepare CSV data
        const headers = ['Date', 'Time', 'Employee Name', 'Employee Code', 'Department', 'Unit', 'Amount', 'Type', 'Transaction Code'];
        const csvData = allTransactions.map(t => [
            t.meal_date,
            t.meal_time,
            t.employee.formal_name,
            t.employee.employee_code,
            t.employee.department.name,
            t.employee.unit?.name || 'N/A',
            t.is_reward ? 200 : 65,
            t.is_reward ? 'Reward (200 KES)' : 'Regular (65 KES)',
            t.transaction_code
        ]);

        const csv = [headers, ...csvData].map(row => row.join(',')).join('\n');
        const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `scan_history_${document.getElementById('startDate').value}_to_${document.getElementById('endDate').value}.csv`;
        a.click();
        URL.revokeObjectURL(url);

        Swal.fire({ icon: 'success', title: 'Exported!', text: 'CSV file downloaded', timer: 2000, showConfirmButton: false });
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('History page loaded');
        setPeriod('last_30_days');
    });
</script>

<style>
    .animate-spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .period-btn {
        transition: all 0.2s ease;
    }
    .period-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    #prevBtn:disabled, #nextBtn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>
@endsection
