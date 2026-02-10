@extends('reeds.vendor.layout.vendorlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-text-black">Scan History</h1>
                <p class="text-gray-600 mt-2">View and filter your scan transactions</p>
            </div>

            <!-- Date Filter -->
            <div class="mt-4 md:mt-0 flex space-x-3">
                <input type="date" id="dateFilter" value="{{ date('Y-m-d') }}"
                       class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                <select id="dateType" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                    <option value="specific">Specific Date</option>
                    <option value="all">All Dates</option>
                </select>
                <button onclick="loadHistory()" class="bg-secondary-blue text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200 flex items-center">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Scans Today</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="averageDaily">0</p>
                </div>
                <div class="w-12 h-12 bg-purple-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-bar text-purple-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-text-black">Transaction History</h3>
                <span class="text-sm text-gray-500" id="transactionCount">0 transactions</span>
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
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="py-12 text-center hidden">
            <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No transactions found for the selected date</p>
            <p class="text-sm text-gray-400 mt-1">Try selecting a different date or view all dates</p>
        </div>
    </div>



</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('History page loaded');
        loadHistory();

        // Add event listener for date type change
        document.getElementById('dateType').addEventListener('change', function() {
            const dateInput = document.getElementById('dateFilter');
            dateInput.disabled = this.value === 'all';
        });
    });

    // Load history
    async function loadHistory() {
        showLoading(true);
        hideTableContent();
        hideEmptyState();

        const dateType = document.getElementById('dateType').value;
        const dateFilter = document.getElementById('dateFilter').value;

        let dateParam = dateType === 'all' ? 'all' : dateFilter;

        console.log('Loading history with params:', { dateType, dateFilter, dateParam });

        try {
            const response = await fetch(`{{ route('vendor.history.data') }}?date=${dateParam}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('API Response:', data);

            // Show debug info (remove in production)
           
            if (data.success) {
                updateHistoryTable(data.transactions);
                updateStats(data.stats);

                if (data.transactions && data.transactions.length > 0) {
                    showTableContent();
                    document.getElementById('transactionCount').textContent =
                        `${data.transactions.length} transaction${data.transactions.length !== 1 ? 's' : ''}`;
                } else {
                    showEmptyState();
                }
            } else {
                throw new Error(data.message || 'Failed to load history');
            }
        } catch (error) {
            console.error('Error loading history:', error);
            showError('Error Loading History', error.message || 'Failed to load history data');
            showEmptyState();
        } finally {
            showLoading(false);
        }
    }

    // Update history table
    function updateHistoryTable(transactions) {
        const tbody = document.getElementById('historyTableBody');

        if (!transactions || transactions.length === 0) {
            tbody.innerHTML = '';
            return;
        }

        tbody.innerHTML = transactions.map(transaction => `
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
    }

    // Update stats
    function updateStats(stats) {
        if (!stats) return;

        document.getElementById('totalScans').textContent = stats.total_scans || 0;
        document.getElementById('totalRevenue').textContent =
            'Ksh ' + parseFloat(stats.total_revenue || 0).toLocaleString('en-KE', { minimumFractionDigits: 2 });
        document.getElementById('averageDaily').textContent = stats.average_daily || 0;
    }

    // UI Helper Functions
    function showLoading(show) {
        const loadingState = document.getElementById('loadingState');
        if (show) {
            loadingState.classList.remove('hidden');
        } else {
            loadingState.classList.add('hidden');
        }
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

    function showError(title, message) {
        Swal.fire({
            icon: 'error',
            title: title,
            text: message,
            timer: 3000
        });
    }

    // Export functionality (if needed later)
    function exportHistory() {
        Swal.fire({
            title: 'Export Feature',
            text: 'Export functionality will be implemented soon.',
            icon: 'info'
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

    #emptyState {
        min-height: 200px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
</style>
@endsection
