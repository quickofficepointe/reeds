@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header with Export Buttons -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-text-black">Analytics & Reports</h1>
                <p class="text-gray-600 mt-2">Comprehensive insights into feeding patterns and vendor performance</p>
            </div>
            <div class="mt-4 md:mt-0 flex flex-wrap gap-3">
                <!-- Export Buttons -->
                <div class="flex items-center space-x-2">
                    <button onclick="exportUnitPerformance('excel')"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-150 flex items-center">
                        <i class="fas fa-file-excel mr-2"></i> Export Excel
                    </button>
                    <button onclick="exportUnitPerformance('pdf')"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-150 flex items-center">
                        <i class="fas fa-file-pdf mr-2"></i> Export PDF
                    </button>
                    <button onclick="exportUnitPerformance('csv')"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150 flex items-center">
                        <i class="fas fa-file-csv mr-2"></i> Export CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- Date Range Filters -->
        <div class="mt-6 bg-white rounded-xl shadow-md border border-gray-100 p-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-text-black mb-3">Date Range Filter</h3>
                    <div class="flex flex-wrap gap-3">
                        <!-- Quick Range Buttons -->
                        <div class="flex flex-wrap gap-2">
                            <button onclick="setDateRange('today')"
                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">
                                Today
                            </button>
                            <button onclick="setDateRange('yesterday')"
                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">
                                Yesterday
                            </button>
                            <button onclick="setDateRange('week')"
                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">
                                This Week
                            </button>
                            <button onclick="setDateRange('biweekly')"
                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">
                                Bi-Weekly
                            </button>
                            <button onclick="setDateRange('month')"
                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">
                                This Month
                            </button>
                            <button onclick="setDateRange('quarter')"
                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">
                                This Quarter
                            </button>
                            <button onclick="setDateRange('year')"
                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">
                                This Year
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Custom Date Range -->
                <div class="mt-4 md:mt-0">
                    <h3 class="text-lg font-semibold text-text-black mb-3">Custom Range</h3>
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="flex items-center">
                            <label class="text-sm text-gray-600 mr-2">From:</label>
                            <input type="date"
                                   id="customStartDate"
                                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                        </div>
                        <div class="flex items-center">
                            <label class="text-sm text-gray-600 mr-2">To:</label>
                            <input type="date"
                                   id="customEndDate"
                                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                        </div>
                        <button onclick="applyCustomRange()"
                                class="px-4 py-2 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition duration-150">
                            Apply
                        </button>
                    </div>
                </div>
            </div>

            <!-- Active Filters Display -->
            <div id="activeFilters" class="mt-4 p-3 bg-blue-50 rounded-lg hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium text-blue-800">Active Filters:</span>
                        <span id="filterText" class="text-sm text-blue-600 ml-2"></span>
                    </div>
                    <button onclick="clearFilters()"
                            class="text-sm text-blue-600 hover:text-blue-800">
                        <i class="fas fa-times mr-1"></i> Clear
                    </button>
                </div>
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

                        <!-- Quick Export Button -->
                        <button onclick="exportUnitData({{ $unit['id'] }})"
                                class="w-full mt-2 px-3 py-2 bg-green-50 text-green-600 hover:bg-green-100 rounded-lg text-sm font-medium transition duration-150 flex items-center justify-center">
                            <i class="fas fa-download mr-2"></i> Export Unit Data
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
        <!-- Charts Grid with Fixed Heights -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Scans Over Time Chart -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Scans Over Time</h3>
                <div class="h-80"> <!-- Fixed height container -->
                    <canvas id="scansChart"></canvas>
                </div>
            </div>

            <!-- Unit Performance Chart -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Unit Performance</h3>
                <div class="h-80"> <!-- Fixed height container -->
                    <canvas id="unitChart"></canvas>
                </div>
            </div>

            <!-- Department Feeding Rates -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Department Feeding Rates</h3>
                <div class="h-80"> <!-- Fixed height container -->
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>

            <!-- Employee Behavior -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Frequent Eaters</h3>
                <div class="h-80"> <!-- Fixed height container -->
                    <canvas id="employeeChart"></canvas>
                </div>
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

<!-- Export Modal -->
<div id="exportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-text-black">Export Unit Analytics</h3>
                <button onclick="closeExportModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Export Format</label>
                    <select id="exportFormat"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                        <option value="excel">Excel (.xlsx)</option>
                        <option value="csv">CSV (.csv)</option>
                        <option value="pdf">PDF (.pdf)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Unit Selection</label>
                    <select id="exportUnitFilter"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                        <option value="all">All Units</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                    <select id="exportDateRange"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="week">This Week</option>
                        <option value="biweekly">Bi-Weekly</option>
                        <option value="month" selected>This Month</option>
                        <option value="quarter">This Quarter</option>
                        <option value="year">This Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div id="customExportRange" class="hidden space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input type="date"
                               id="exportStartDate"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input type="date"
                               id="exportEndDate"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Include Data</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" checked class="rounded border-gray-300 text-secondary-blue focus:ring-secondary-blue">
                            <span class="ml-2 text-sm text-gray-700">Unit Statistics</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" checked class="rounded border-gray-300 text-secondary-blue focus:ring-secondary-blue">
                            <span class="ml-2 text-sm text-gray-700">Scan Details</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-secondary-blue focus:ring-secondary-blue">
                            <span class="ml-2 text-sm text-gray-700">Revenue Breakdown</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-secondary-blue focus:ring-secondary-blue">
                            <span class="ml-2 text-sm text-gray-700">Vendor Performance</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="mt-8 flex justify-end space-x-3">
                <button type="button"
                        onclick="closeExportModal()"
                        class="px-5 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button onclick="processExport()"
                        class="px-5 py-2.5 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition font-medium">
                    <i class="fas fa-download mr-2"></i> Export Now
                </button>
            </div>
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

    // Date Range Functions
    let currentStartDate = null;
    let currentEndDate = null;

    function setDateRange(range) {
        const today = new Date();
        let startDate, endDate;

        switch (range) {
            case 'today':
                startDate = new Date(today);
                endDate = new Date(today);
                break;
            case 'yesterday':
                startDate = new Date(today);
                startDate.setDate(today.getDate() - 1);
                endDate = new Date(startDate);
                break;
            case 'week':
                startDate = new Date(today);
                startDate.setDate(today.getDate() - today.getDay() + 1); // Monday of current week
                endDate = new Date(today);
                break;
            case 'biweekly':
                startDate = new Date(today);
                startDate.setDate(today.getDate() - 14);
                endDate = new Date(today);
                break;
            case 'month':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                endDate = new Date(today);
                break;
            case 'quarter':
                const quarter = Math.floor(today.getMonth() / 3);
                startDate = new Date(today.getFullYear(), quarter * 3, 1);
                endDate = new Date(today);
                break;
            case 'year':
                startDate = new Date(today.getFullYear(), 0, 1);
                endDate = new Date(today);
                break;
            default:
                startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                endDate = new Date(today);
        }

        currentStartDate = startDate;
        currentEndDate = endDate;

        updateFilterDisplay(range);
        loadAnalyticsWithDateRange();
    }

    function applyCustomRange() {
        const startInput = document.getElementById('customStartDate');
        const endInput = document.getElementById('customEndDate');

        if (!startInput || !endInput) {
            console.error('Date input elements not found');
            return;
        }

        if (!startInput.value || !endInput.value) {
            alert('Please select both start and end dates');
            return;
        }

        currentStartDate = new Date(startInput.value);
        currentEndDate = new Date(endInput.value);

        if (currentStartDate > currentEndDate) {
            [currentStartDate, currentEndDate] = [currentEndDate, currentStartDate];
        }

        updateFilterDisplay('custom');
        loadAnalyticsWithDateRange();
    }

    function updateFilterDisplay(range) {
        const filterElement = document.getElementById('activeFilters');
        const filterText = document.getElementById('filterText');

        if (!filterElement || !filterText) {
            console.error('Filter display elements not found');
            return;
        }

        let displayText = '';
        const formatDate = (date) => date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });

        if (range === 'custom') {
            displayText = `Custom: ${formatDate(currentStartDate)} - ${formatDate(currentEndDate)}`;
        } else {
            const rangeNames = {
                'today': 'Today',
                'yesterday': 'Yesterday',
                'week': 'This Week',
                'biweekly': 'Last 14 Days',
                'month': 'This Month',
                'quarter': 'This Quarter',
                'year': 'This Year'
            };
            displayText = `${rangeNames[range]} (${formatDate(currentStartDate)} - ${formatDate(currentEndDate)})`;
        }

        filterText.textContent = displayText;
        filterElement.classList.remove('hidden');
    }

    function clearFilters() {
        currentStartDate = null;
        currentEndDate = null;

        const activeFilters = document.getElementById('activeFilters');
        if (activeFilters) {
            activeFilters.classList.add('hidden');
        }

        const customStartDate = document.getElementById('customStartDate');
        const customEndDate = document.getElementById('customEndDate');
        if (customStartDate) customStartDate.value = '';
        if (customEndDate) customEndDate.value = '';

        const unitFilter = document.getElementById('unitFilter');
        if (unitFilter) unitFilter.value = 'all';

        setDateRange('month');
    }

    // Load analytics with date range
    function loadAnalyticsWithDateRange() {
        const unitFilter = document.getElementById('unitFilter');
        const unitId = unitFilter ? unitFilter.value : 'all';

        // For the analytics data, we're using 'month' as default period since periodSelect doesn't exist
        loadAnalyticsData('month', unitId);
    }

    // Export Functions
    function exportUnitPerformance(format) {
        showExportModal();
    }

    function exportUnitData(unitId) {
        const exportUnitFilter = document.getElementById('exportUnitFilter');
        if (exportUnitFilter) {
            exportUnitFilter.value = unitId;
        }
        showExportModal();
    }

    function showExportModal() {
        const exportModal = document.getElementById('exportModal');
        if (exportModal) {
            exportModal.classList.remove('hidden');
        }
    }

    function closeExportModal() {
        const exportModal = document.getElementById('exportModal');
        if (exportModal) {
            exportModal.classList.add('hidden');
        }
    }

    function processExport() {
        const exportFormat = document.getElementById('exportFormat');
        const exportDateRange = document.getElementById('exportDateRange');
        const exportUnitFilter = document.getElementById('exportUnitFilter');

        if (!exportFormat || !exportDateRange || !exportUnitFilter) {
            alert('Export configuration elements not found');
            return;
        }

        const format = exportFormat.value;
        const dateRange = exportDateRange.value;
        const unitId = exportUnitFilter.value || 'all';

        let startDate, endDate;

        if (dateRange === 'custom') {
            const exportStartDate = document.getElementById('exportStartDate');
            const exportEndDate = document.getElementById('exportEndDate');

            if (!exportStartDate || !exportEndDate) {
                alert('Custom date range elements not found');
                return;
            }

            startDate = exportStartDate.value;
            endDate = exportEndDate.value;

            if (!startDate || !endDate) {
                alert('Please select custom date range');
                return;
            }
        } else {
            startDate = currentStartDate?.toISOString().split('T')[0];
            endDate = currentEndDate?.toISOString().split('T')[0];
        }

        let url = `/admin/analytics/export/units?format=${format}`;

        if (unitId !== 'all') {
            url += `&unit_id=${unitId}`;
        }

        if (startDate && endDate) {
            url += `&start_date=${startDate}&end_date=${endDate}`;
        }

        window.open(url, '_blank');

        showNotification('Export started successfully!', 'success');
        closeExportModal();
    }

    // Load analytics data with unit filter
    function loadAnalyticsData(period = 'month', unitId = 'all') {
        showLoading();

        let url = `/admin/analytics/data?period=${period}&unit_id=${unitId}`;

        if (currentStartDate && currentEndDate) {
            const startStr = currentStartDate.toISOString().split('T')[0];
            const endStr = currentEndDate.toISOString().split('T')[0];
            url += `&custom_start=${startStr}&custom_end=${endStr}`;
        }

        fetch(url)
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

    // Update charts with fixed height configuration
    function updateCharts(data) {
        // Common chart options for fixed height
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
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
                    }
                }
            }
        };

        // Scans over time chart
        const scansCanvas = document.getElementById('scansChart');
        if (!scansCanvas) {
            console.error('Scans chart canvas not found');
            return;
        }

        const scansCtx = scansCanvas.getContext('2d');
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
                    fill: true,
                    borderWidth: 2
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Unit performance chart
        const unitCanvas = document.getElementById('unitChart');
        if (unitCanvas) {
            const unitCtx = unitCanvas.getContext('2d');
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
                options: commonOptions
            });
        }

        // Department feeding rates
        const deptCanvas = document.getElementById('departmentChart');
        if (deptCanvas) {
            const deptCtx = deptCanvas.getContext('2d');
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
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }

        // Employee behavior
        const empCanvas = document.getElementById('employeeChart');
        if (empCanvas) {
            const empCtx = empCanvas.getContext('2d');
            if (employeeChart) employeeChart.destroy();

            employeeChart = new Chart(empCtx, {
                type: 'bar',
                data: {
                    labels: data.employee_behavior.frequent_eaters.map(item => item.formal_name),
                    datasets: [{
                        label: 'Meals',
                        data: data.employee_behavior.frequent_eaters.map(item => item.meal_count || 0),
                        backgroundColor: '#10b981',
                        borderColor: '#10b981',
                        borderWidth: 1
                    }]
                },
                options: {
                    ...commonOptions,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    }

    // Update tables with safe data
    function updateTables(data) {
        // Unit table
        const unitTable = document.getElementById('unitTable');
        if (unitTable) {
            if (data.unit_performance && data.unit_performance.length > 0) {
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
        }

        // Vendor table
        const vendorTable = document.getElementById('vendorTable');
        if (vendorTable) {
            if (data.vendor_performance && data.vendor_performance.length > 0) {
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
    }

    // View unit analytics
    function viewUnitAnalytics(unitId) {
        const unitDetailsContent = document.getElementById('unitDetailsContent');
        if (unitDetailsContent) {
            unitDetailsContent.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl text-secondary-blue mb-2"></i>
                    <p>Loading unit analytics...</p>
                </div>
            `;
        }

        const unitDetailsModal = document.getElementById('unitDetailsModal');
        if (unitDetailsModal) {
            unitDetailsModal.classList.remove('hidden');
        }

        fetch(`/admin/analytics/unit/${unitId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.unit && unitDetailsContent) {
                    const unit = data.unit;

                    const unitModalTitle = document.getElementById('unitModalTitle');
                    if (unitModalTitle) {
                        unitModalTitle.textContent = `${unit.name} Analytics`;
                    }

                    unitDetailsContent.innerHTML = `
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

                            <!-- Charts Section with Fixed Height -->
                            <div>
                                <h4 class="text-lg font-semibold text-text-black mb-4">Monthly Trends</h4>
                                <div class="h-64">
                                    <canvas id="unitMonthlyChart"></canvas>
                                </div>
                            </div>

                            <!-- Top Employees -->
                            <div>
                                <h4 class="text-lg font-semibold text-text-black mb-4">Top Employees</h4>
                                <div class="space-y-3">
                                    ${unit.top_employees && unit.top_employees.length > 0 ? unit.top_employees.map(emp => `
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
                                    ${unit.vendors && unit.vendors.length > 0 ? unit.vendors.map(vendor => `
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

                    // Initialize unit chart with fixed height
                    const ctx = document.getElementById('unitMonthlyChart');
                    if (ctx) {
                        new Chart(ctx.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: unit.monthly_trends.labels,
                                datasets: [{
                                    label: 'Daily Scans',
                                    data: unit.monthly_trends.scans,
                                    borderColor: '#2596be',
                                    backgroundColor: 'rgba(37, 150, 190, 0.1)',
                                    tension: 0.4,
                                    fill: true,
                                    borderWidth: 2
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
                    }
                } else {
                    unitDetailsContent.innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-2xl text-red-500 mb-2"></i>
                            <p>Failed to load unit analytics.</p>
                            <p class="text-sm text-gray-600 mt-1">${data.error || 'Please try again.'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                if (unitDetailsContent) {
                    unitDetailsContent.innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-2xl text-red-500 mb-2"></i>
                            <p>Error loading unit analytics.</p>
                            <p class="text-sm text-gray-600 mt-1">Please try again later.</p>
                        </div>
                    `;
                }
            });
    }

    // Close unit modal
    function closeUnitModal() {
        const unitDetailsModal = document.getElementById('unitDetailsModal');
        if (unitDetailsModal) {
            unitDetailsModal.classList.add('hidden');
        }
    }

    // Show export modal for custom date range
    document.addEventListener('DOMContentLoaded', function() {
        const exportDateRange = document.getElementById('exportDateRange');
        if (exportDateRange) {
            exportDateRange.addEventListener('change', function() {
                const customRangeDiv = document.getElementById('customExportRange');
                if (this.value === 'custom') {
                    if (customRangeDiv) {
                        customRangeDiv.classList.remove('hidden');
                    }

                    const exportStartDate = document.getElementById('exportStartDate');
                    const exportEndDate = document.getElementById('exportEndDate');
                    if (exportStartDate && exportEndDate) {
                        exportStartDate.value = currentStartDate?.toISOString().split('T')[0] || '';
                        exportEndDate.value = currentEndDate?.toISOString().split('T')[0] || '';
                    }
                } else {
                    if (customRangeDiv) {
                        customRangeDiv.classList.add('hidden');
                    }
                }
            });
        }

        // Initialize date range
        setDateRange('month');

        // Load initial analytics data
        loadAnalyticsData('month');

        // Unit filter event listener
        const unitFilter = document.getElementById('unitFilter');
        if (unitFilter) {
            unitFilter.addEventListener('change', function() {
                loadAnalyticsData('month', this.value);
            });
        }
    });

    // Notification function
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transform transition-all duration-300 ${
            type === 'success' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'
        } border`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
</script>
@endsection
