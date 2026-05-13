@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header with Export Buttons -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-text-black">Analytics & Reports</h1>
                <p class="text-gray-600 mt-2">Comprehensive insights into feeding patterns and vendor performance</p>
                <div class="flex items-center space-x-4 mt-2">
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-circle mr-1 text-green-500 text-xs"></i> Regular Meal: 65 KES
                    </span>
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                        <i class="fas fa-star mr-1 text-purple-500 text-xs"></i> Reward Meal: 200 KES
                    </span>
                </div>
            </div>
            <div class="mt-4 md:mt-0 flex flex-wrap gap-3">
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
                        <div class="flex flex-wrap gap-2">
                            <button onclick="setDateRange('today')" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">Today</button>
                            <button onclick="setDateRange('yesterday')" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">Yesterday</button>
                            <button onclick="setDateRange('week')" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">This Week</button>
                            <button onclick="setDateRange('biweekly')" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">Bi-Weekly</button>
                            <button onclick="setDateRange('month')" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">This Month</button>
                            <button onclick="setDateRange('quarter')" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">This Quarter</button>
                            <button onclick="setDateRange('year')" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition">This Year</button>
                        </div>
                    </div>
                </div>
                <div class="mt-4 md:mt-0">
                    <h3 class="text-lg font-semibold text-text-black mb-3">Custom Range</h3>
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="flex items-center">
                            <label class="text-sm text-gray-600 mr-2">From:</label>
                            <input type="date" id="customStartDate" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue">
                        </div>
                        <div class="flex items-center">
                            <label class="text-sm text-gray-600 mr-2">To:</label>
                            <input type="date" id="customEndDate" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue">
                        </div>
                        <button onclick="applyCustomRange()" class="px-4 py-2 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition">Apply</button>
                    </div>
                </div>
            </div>
            <div id="activeFilters" class="mt-4 p-3 bg-blue-50 rounded-lg hidden">
                <div class="flex items-center justify-between">
                    <div><span class="text-sm font-medium text-blue-800">Active Filters:</span><span id="filterText" class="text-sm text-blue-600 ml-2"></span></div>
                    <button onclick="clearFilters()" class="text-sm text-blue-600 hover:text-blue-800"><i class="fas fa-times mr-1"></i> Clear</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Unit Analytics Section -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-text-black">Unit Performance Overview</h2>
            <div class="text-sm text-gray-500">{{ $unitStats->count() }} Active Units</div>
        </div>

        @if($unitStats->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($unitStats as $unit)
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-text-black text-lg">{{ $unit['name'] }}</h3>
                            @if($unit['code'])<p class="text-sm text-gray-500">{{ $unit['code'] }}</p>@endif
                            @if($unit['location'])<p class="text-xs text-gray-400 mt-1"><i class="fas fa-map-marker-alt mr-1"></i>{{ $unit['location'] }}</p>@endif
                        </div>
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center"><i class="fas fa-building text-blue-500"></i></div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Employees</span>
                            <div class="flex items-center space-x-2">
                                <span class="font-semibold text-text-black">{{ $unit['active_employees'] }}/{{ $unit['total_employees'] }}</span>
                                @if($unit['capacity_utilization'])
                                    <span class="text-xs px-2 py-1 rounded-full {{ $unit['capacity_utilization'] > 90 ? 'bg-red-100 text-red-800' : ($unit['capacity_utilization'] > 70 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">{{ $unit['capacity_utilization'] }}%</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Scans Today</span>
                            <div><span class="font-semibold text-text-black">{{ $unit['today_scans'] }}</span><span class="text-xs text-gray-500 ml-2">{{ $unit['active_employees'] > 0 ? round(($unit['today_scans'] / $unit['active_employees']) * 100, 0) : 0 }}%</span></div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Regular (65)</span>
                            <span class="font-semibold text-green-600">{{ $unit['today_regular_scans'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Reward (200)</span>
                            <span class="font-semibold text-purple-600">{{ $unit['today_reward_scans'] ?? 0 }} 🎖️</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Monthly Revenue</span>
                            <span class="text-xs text-green-600">KSh {{ number_format($unit['month_revenue'], 0) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Active Vendors</span>
                            <span class="font-semibold text-text-black">{{ $unit['active_vendors'] }}</span>
                        </div>
                        <button onclick="viewUnitAnalytics({{ $unit['id'] }})" class="w-full mt-4 px-3 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-sm font-medium flex items-center justify-center"><i class="fas fa-chart-bar mr-2"></i> View Details</button>
                        <button onclick="exportUnitData({{ $unit['id'] }})" class="w-full mt-2 px-3 py-2 bg-green-50 text-green-600 hover:bg-green-100 rounded-lg text-sm font-medium flex items-center justify-center"><i class="fas fa-download mr-2"></i> Export Unit Data</button>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-building text-gray-400 text-2xl"></i></div>
                <h3 class="text-lg font-semibold text-text-black mb-2">No Units Found</h3>
                <p class="text-gray-600 mb-6">Create units to start tracking analytics per location.</p>
                <a href="{{ route('admin.units.index') }}" class="inline-flex items-center px-4 py-2 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition"><i class="fas fa-plus mr-2"></i> Create Units</a>
            </div>
        @endif
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div><p class="text-sm font-medium text-gray-600">Total Scans (Month)</p><p class="text-2xl font-bold mt-2">{{ number_format($stats['month_scans']) }}</p></div>
            <div class="mt-4"><span class="text-xs text-gray-500">{{ number_format($stats['week_scans']) }} this week</span></div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div><p class="text-sm font-medium text-gray-600">Monthly Revenue</p><p class="text-2xl font-bold text-green-600 mt-2">KSh {{ number_format($stats['total_revenue_month'], 2) }}</p></div>
            <div class="mt-4"><span class="text-xs text-gray-500">KSh {{ number_format($stats['total_revenue_week'], 2) }} this week</span></div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div><p class="text-sm font-medium text-gray-600">Regular Meals (65)</p><p class="text-2xl font-bold text-green-600 mt-2">{{ number_format($stats['regular_scans_month'] ?? 0) }}</p></div>
            <div class="mt-4"><span class="text-xs text-gray-500">65 KES per meal</span></div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div><p class="text-sm font-medium text-gray-600">Reward Meals (200)</p><p class="text-2xl font-bold text-purple-600 mt-2">{{ number_format($stats['reward_scans_month'] ?? 0) }} 🎖️</p></div>
            <div class="mt-4"><span class="text-xs text-gray-500">200 KES per reward meal</span></div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div><p class="text-sm font-medium text-gray-600">Active Vendors</p><p class="text-2xl font-bold mt-2">{{ $stats['total_vendors'] }}</p></div>
            <div class="mt-4"><span class="text-xs text-gray-500">{{ $stats['verified_vendors'] }} verified</span></div>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <h3 class="text-lg font-semibold mb-4">Employee Overview</h3>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-sm text-gray-600">Total Employees</span><span class="font-semibold">{{ $stats['total_employees'] }}</span></div>
                <div class="flex justify-between"><span class="text-sm text-gray-600">Active Employees</span><span class="font-semibold text-green-600">{{ $stats['active_employees'] }}</span></div>
                <div class="flex justify-between"><span class="text-sm text-gray-600">Feeding Rate Today</span><span class="font-semibold">{{ $stats['total_employees'] > 0 ? round(($stats['today_scans'] / $stats['total_employees']) * 100, 1) : 0 }}%</span></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <h3 class="text-lg font-semibold mb-4">Today's Activity</h3>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-sm text-gray-600">Scans Today</span><span class="font-semibold">{{ $stats['today_scans'] }}</span></div>
                <div class="flex justify-between"><span class="text-sm text-gray-600">Regular Meals (65)</span><span class="font-semibold text-green-600">{{ $stats['today_regular_scans'] ?? 0 }}</span></div>
                <div class="flex justify-between"><span class="text-sm text-gray-600">Reward Meals (200)</span><span class="font-semibold text-purple-600">{{ $stats['today_reward_scans'] ?? 0 }} 🎖️</span></div>
                <div class="flex justify-between"><span class="text-sm text-gray-600">Revenue Today</span><span class="font-semibold text-green-600">KSh {{ number_format($stats['total_revenue_today'], 2) }}</span></div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <h3 class="text-lg font-semibold mb-4">Top Vendor This Month</h3>
            @if($topVendors->count() > 0)
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-sm text-gray-600">Vendor</span><span class="font-semibold">{{ $topVendors->first()->name }}</span></div>
                <div class="flex justify-between"><span class="text-sm text-gray-600">Total Scans</span><span class="font-semibold">{{ $topVendors->first()->total_scans }}</span></div>
                <div class="flex justify-between"><span class="text-sm text-gray-600">Regular (65)</span><span class="font-semibold text-green-600">{{ $topVendors->first()->regular_scans ?? 0 }}</span></div>
                <div class="flex justify-between"><span class="text-sm text-gray-600">Reward (200)</span><span class="font-semibold text-purple-600">{{ $topVendors->first()->reward_scans ?? 0 }} 🎖️</span></div>
                <div class="flex justify-between"><span class="text-sm text-gray-600">Revenue</span><span class="font-semibold text-green-600">KSh {{ number_format($topVendors->first()->total_revenue, 2) }}</span></div>
            </div>
            @else
            <div class="text-center py-4 text-gray-500"><i class="fas fa-store text-2xl mb-2"></i><p class="text-sm">No vendor data available</p></div>
            @endif
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="bg-white rounded-xl shadow-md border border-gray-100 p-8 text-center mb-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-secondary-blue mx-auto mb-4"></div>
        <p class="text-gray-600">Loading analytics charts...</p>
    </div>

    <!-- Error State -->
    <div id="errorState" class="bg-white rounded-xl shadow-md border border-gray-100 p-8 text-center mb-8 hidden">
        <i class="fas fa-exclamation-triangle text-yellow-500 text-3xl mb-4"></i>
        <h3 class="text-lg font-semibold text-text-black mb-2">Unable to Load Charts</h3>
        <p class="text-gray-600 mb-4" id="errorMessage">There was an error loading the analytics charts.</p>
        <button onclick="loadAnalyticsData('month')" class="px-4 py-2 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition">Try Again</button>
    </div>

    <!-- Dynamic Charts Section -->
    <div id="chartsSection" class="hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold mb-4">Scans Over Time</h3>
                <div class="h-80"><canvas id="scansChart"></canvas></div>
            </div>
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold mb-4">Unit Performance</h3>
                <div class="h-80"><canvas id="unitChart"></canvas></div>
            </div>
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold mb-4">Department Feeding Rates</h3>
                <div class="h-80"><canvas id="departmentChart"></canvas></div>
            </div>
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold mb-4">Frequent Eaters</h3>
                <div class="h-80"><canvas id="employeeChart"></canvas></div>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold mb-4">Unit Performance</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead><tr class="border-b border-gray-200"><th class="text-left py-3 text-sm font-medium text-gray-700">Unit</th><th class="text-right py-3 text-sm font-medium text-gray-700">Employees</th><th class="text-right py-3 text-sm font-medium text-gray-700">Scans</th><th class="text-right py-3 text-sm font-medium text-gray-700">Revenue</th></tr></thead>
                        <tbody id="unitTable"></tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold mb-4">Vendor Performance</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead><tr class="border-b border-gray-200"><th class="text-left py-3 text-sm font-medium text-gray-700">Vendor</th><th class="text-right py-3 text-sm font-medium text-gray-700">Scans</th><th class="text-right py-3 text-sm font-medium text-gray-700">Regular (65)</th><th class="text-right py-3 text-sm font-medium text-gray-700">Reward (200)</th><th class="text-right py-3 text-sm font-medium text-gray-700">Revenue</th></tr></thead>
                        <tbody id="vendorTable"></tbody>
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
                    <div class="w-10 h-10 {{ isset($transaction->is_reward) && $transaction->is_reward ? 'bg-purple-100' : 'bg-green-100' }} rounded-full flex items-center justify-center">
                        <i class="fas fa-utensils {{ isset($transaction->is_reward) && $transaction->is_reward ? 'text-purple-600' : 'text-green-600' }}"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-text-black text-sm">{{ $transaction->employee->formal_name ?? 'Unknown Employee' }}</p>
                        <div class="flex items-center space-x-2 text-xs text-gray-500">
                            <span>{{ $transaction->employee->department->name ?? 'No Department' }}</span>
                            <span>•</span>
                            <span class="flex items-center"><i class="fas fa-building mr-1"></i>{{ $transaction->employee->unit->name ?? 'No Unit' }}</span>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    @if(isset($transaction->is_reward) && $transaction->is_reward)
                        <p class="font-semibold text-purple-600">KSh 200.00 🎖️</p>
                        <p class="text-xs text-purple-500">Reward Meal</p>
                    @else
                        <p class="font-semibold text-green-600">KSh 65.00</p>
                        <p class="text-xs text-gray-500">Regular Meal</p>
                    @endif
                    <div class="flex items-center justify-end space-x-2 text-xs text-gray-500 mt-1">
                        <span>{{ $transaction->meal_time }}</span>
                        <span>•</span>
                        <span>{{ $transaction->vendor->name ?? 'Unknown' }}</span>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-8 text-gray-500"><i class="fas fa-history text-3xl mb-2"></i><p>No transactions yet</p></div>
            @endforelse
        </div>
    </div>
</div>

<!-- Unit Details Modal -->
<div id="unitDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 id="unitModalTitle" class="text-xl font-bold text-text-black">Unit Analytics</h3>
            <button onclick="closeUnitModal()" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div id="unitDetailsContent"></div>
    </div>
</div>

<!-- Export Modal -->
<div id="exportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-text-black">Export Unit Analytics</h3>
                <button onclick="closeExportModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Export Format</label><select id="exportFormat" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg"><option value="excel">Excel (.xlsx)</option><option value="csv">CSV (.csv)</option><option value="pdf">PDF (.pdf)</option></select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Unit Selection</label><select id="exportUnitFilter" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg"><option value="all">All Units</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label><select id="exportDateRange" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg"><option value="today">Today</option><option value="yesterday">Yesterday</option><option value="week">This Week</option><option value="biweekly">Bi-Weekly</option><option value="month" selected>This Month</option><option value="quarter">This Quarter</option><option value="year">This Year</option><option value="custom">Custom Range</option></select></div>
                <div id="customExportRange" class="hidden space-y-3">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">From Date</label><input type="date" id="exportStartDate" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">To Date</label><input type="date" id="exportEndDate" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></div>
                </div>
            </div>
            <div class="mt-8 flex justify-end space-x-3">
                <button onclick="closeExportModal()" class="px-5 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button onclick="processExport()" class="px-5 py-2.5 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition font-medium"><i class="fas fa-download mr-2"></i> Export Now</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let scansChart, unitChart, departmentChart, employeeChart;
    let currentStartDate = null, currentEndDate = null;

    function showLoading() { document.getElementById('loadingState').classList.remove('hidden'); document.getElementById('errorState').classList.add('hidden'); document.getElementById('chartsSection').classList.add('hidden'); }
    function showError(message) { document.getElementById('loadingState').classList.add('hidden'); document.getElementById('errorState').classList.remove('hidden'); document.getElementById('chartsSection').classList.add('hidden'); document.getElementById('errorMessage').textContent = message || 'Error loading charts.'; }
    function showCharts() { document.getElementById('loadingState').classList.add('hidden'); document.getElementById('errorState').classList.add('hidden'); document.getElementById('chartsSection').classList.remove('hidden'); }

    function setDateRange(range) {
        const today = new Date(); let startDate, endDate;
        switch(range) {
            case 'today': startDate = endDate = new Date(today); break;
            case 'yesterday': startDate = new Date(today); startDate.setDate(today.getDate()-1); endDate = new Date(startDate); break;
            case 'week': startDate = new Date(today); startDate.setDate(today.getDate() - today.getDay() + 1); endDate = new Date(today); break;
            case 'biweekly': startDate = new Date(today); startDate.setDate(today.getDate()-14); endDate = new Date(today); break;
            case 'month': startDate = new Date(today.getFullYear(), today.getMonth(), 1); endDate = new Date(today); break;
            case 'quarter': const q = Math.floor(today.getMonth()/3); startDate = new Date(today.getFullYear(), q*3, 1); endDate = new Date(today); break;
            case 'year': startDate = new Date(today.getFullYear(), 0, 1); endDate = new Date(today); break;
            default: startDate = new Date(today.getFullYear(), today.getMonth(), 1); endDate = new Date(today);
        }
        currentStartDate = startDate; currentEndDate = endDate;
        updateFilterDisplay(range);
        loadAnalyticsWithDateRange();
    }

    function applyCustomRange() {
        const start = document.getElementById('customStartDate').value, end = document.getElementById('customEndDate').value;
        if(!start||!end){alert('Please select both dates');return;}
        currentStartDate = new Date(start); currentEndDate = new Date(end);
        if(currentStartDate > currentEndDate)[currentStartDate, currentEndDate]=[currentEndDate,currentStartDate];
        updateFilterDisplay('custom');
        loadAnalyticsWithDateRange();
    }

    function updateFilterDisplay(range) {
        const filterEl=document.getElementById('activeFilters'), filterText=document.getElementById('filterText');
        const formatDate=(d)=>d.toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'});
        filterText.textContent = range==='custom'?`Custom: ${formatDate(currentStartDate)} - ${formatDate(currentEndDate)}`:`${range.charAt(0).toUpperCase()+range.slice(1)} (${formatDate(currentStartDate)} - ${formatDate(currentEndDate)})`;
        filterEl.classList.remove('hidden');
    }

    function clearFilters(){currentStartDate=currentEndDate=null;document.getElementById('activeFilters').classList.add('hidden');document.getElementById('customStartDate').value='';document.getElementById('customEndDate').value='';setDateRange('month');}
    function loadAnalyticsWithDateRange(){loadAnalyticsData('month','all');}
    function exportUnitPerformance(format){showExportModal();}
    function exportUnitData(unitId){const f=document.getElementById('exportUnitFilter');if(f)f.value=unitId;showExportModal();}
    function showExportModal(){document.getElementById('exportModal').classList.remove('hidden');}
    function closeExportModal(){document.getElementById('exportModal').classList.add('hidden');}

    function processExport(){
        const format=document.getElementById('exportFormat').value, dateRange=document.getElementById('exportDateRange').value, unitId=document.getElementById('exportUnitFilter').value||'all';
        let startDate,endDate;
        if(dateRange==='custom'){
            startDate=document.getElementById('exportStartDate').value; endDate=document.getElementById('exportEndDate').value;
            if(!startDate||!endDate){alert('Please select custom date range');return;}
        }else{
            startDate=currentStartDate?.toISOString().split('T')[0]; endDate=currentEndDate?.toISOString().split('T')[0];
        }
        let url=`/admin/analytics/export/units?format=${format}`;
        if(unitId!=='all')url+=`&unit_id=${unitId}`;
        if(startDate&&endDate)url+=`&start_date=${startDate}&end_date=${endDate}`;
        window.open(url,'_blank');
        closeExportModal();
    }

    function loadAnalyticsData(period='month',unitId='all'){
        showLoading();
        let url=`/admin/analytics/data?period=${period}&unit_id=${unitId}`;
        if(currentStartDate&&currentEndDate){url+=`&custom_start=${currentStartDate.toISOString().split('T')[0]}&custom_end=${currentEndDate.toISOString().split('T')[0]}`;}
        fetch(url).then(r=>r.ok?r.json():Promise.reject()).then(data=>{if(data.success){updateCharts(data);updateTables(data);showCharts();}else throw new Error(data.error||'Failed');}).catch(err=>{console.error(err);showError(err.message);});
    }

    function updateCharts(data){
        const commonOptions={responsive:true,maintainAspectRatio:false,plugins:{legend:{display:true,position:'top'}},scales:{y:{beginAtZero:true,grid:{drawBorder:false}},x:{grid:{display:false}}}};
        const scansCtx=document.getElementById('scansChart')?.getContext('2d');if(scansCtx){if(scansChart)scansChart.destroy();scansChart=new Chart(scansCtx,{type:'line',data:{labels:data.scans_data.map(i=>i.period),datasets:[{label:'Scans',data:data.scans_data.map(i=>i.scans||0),borderColor:'#2596be',backgroundColor:'rgba(37,150,190,0.1)',tension:0.4,fill:true,borderWidth:2}]},options:{...commonOptions,plugins:{legend:{display:false}}}});}
        const unitCtx=document.getElementById('unitChart')?.getContext('2d');if(unitCtx){if(unitChart)unitChart.destroy();unitChart=new Chart(unitCtx,{type:'bar',data:{labels:data.unit_performance.map(i=>i.name),datasets:[{label:'Scans',data:data.unit_performance.map(i=>i.scans||0),backgroundColor:'#e92c2a'},{label:'Employees',data:data.unit_performance.map(i=>i.employees||0),backgroundColor:'#2596be'}]},options:commonOptions});}
        const deptCtx=document.getElementById('departmentChart')?.getContext('2d');if(deptCtx){if(departmentChart)departmentChart.destroy();departmentChart=new Chart(deptCtx,{type:'doughnut',data:{labels:data.department_feeding.map(i=>i.name),datasets:[{data:data.department_feeding.map(i=>i.feeding_rate||0),backgroundColor:['#e92c2a','#2596be','#10b981','#f59e0b','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#6366f1'],borderWidth:1}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'right'}}}});}
        const empCtx=document.getElementById('employeeChart')?.getContext('2d');if(empCtx){if(employeeChart)employeeChart.destroy();employeeChart=new Chart(empCtx,{type:'bar',data:{labels:data.employee_behavior.frequent_eaters.map(i=>i.formal_name),datasets:[{label:'Meals',data:data.employee_behavior.frequent_eaters.map(i=>i.meal_count||0),backgroundColor:'#10b981'}]},options:{...commonOptions,indexAxis:'y',plugins:{legend:{display:false}}}});}
    }

    function updateTables(data){
        const unitTable=document.getElementById('unitTable');if(unitTable){unitTable.innerHTML=data.unit_performance?.length?data.unit_performance.map(u=>`<tr class="border-b"><td class="py-3">${u.name}</td><td class="py-3 text-right">${u.employees||0}</td><td class="py-3 text-right">${u.scans||0}</td><td class="py-3 text-right text-green-600">KSh ${(u.revenue||0).toLocaleString()}</td></tr>`).join(''):`<tr><td colspan="4" class="py-8 text-center text-gray-500">No unit data</td></tr>`;}
        const vendorTable=document.getElementById('vendorTable');if(vendorTable){vendorTable.innerHTML=data.vendor_performance?.length?data.vendor_performance.map(v=>`<tr class="border-b"><td class="py-3">${v.name}</td><td class="py-3 text-right">${v.scans||0}</td><td class="py-3 text-right text-green-600">${v.regular_scans||0}</td><td class="py-3 text-right text-purple-600">${v.reward_scans||0}</td><td class="py-3 text-right text-green-600">KSh ${(v.revenue||0).toLocaleString()}</td></tr>`).join(''):`<tr><td colspan="5" class="py-8 text-center text-gray-500">No vendor data</td></tr>`;}
    }

    function viewUnitAnalytics(unitId){
        const modal=document.getElementById('unitDetailsModal'), content=document.getElementById('unitDetailsContent'), title=document.getElementById('unitModalTitle');
        if(content)content.innerHTML=`<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-secondary-blue mb-2"></i><p>Loading...</p></div>`;
        modal?.classList.remove('hidden');
        fetch(`/admin/analytics/unit/${unitId}`).then(r=>r.json()).then(data=>{
            if(data.success&&data.unit){
                const u=data.unit;
                if(title)title.textContent=`${u.name} Analytics`;
                if(content)content.innerHTML=`
                    <div class="space-y-6">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-4 rounded"><h4 class="text-sm font-medium text-blue-800">Total Employees</h4><p class="text-2xl font-bold">${u.total_employees}</p><p class="text-xs text-blue-600">${u.active_employees} active</p></div>
                            <div class="bg-green-50 p-4 rounded"><h4 class="text-sm font-medium text-green-800">Monthly Revenue</h4><p class="text-2xl font-bold">KSh ${u.month_revenue.toLocaleString()}</p><p class="text-xs text-green-600">${u.today_scans} today</p></div>
                            <div class="bg-purple-50 p-4 rounded"><h4 class="text-sm font-medium text-purple-800">Active Vendors</h4><p class="text-2xl font-bold">${u.active_vendors}</p><p class="text-xs text-purple-600">verified</p></div>
                        </div>
                        <div><h4 class="text-lg font-semibold mb-4">Monthly Trends</h4><div class="h-64"><canvas id="unitMonthlyChart"></canvas></div></div>
                        <div><h4 class="text-lg font-semibold mb-4">Top Employees</h4><div class="space-y-3">${u.top_employees?.length?u.top_employees.map(e=>`<div class="flex justify-between p-3 border rounded"><div><p class="font-medium">${e.formal_name}</p><p class="text-sm text-gray-500">${e.department}</p></div><div><p class="font-semibold text-green-600">${e.meal_count} meals</p><p class="text-xs text-gray-500">KSh ${e.total_amount.toLocaleString()}</p></div></div>`).join(''):'<p class="text-center text-gray-500">No data</p>'}</div></div>
                        <div><h4 class="text-lg font-semibold mb-4">Active Vendors</h4><div class="space-y-3">${u.vendors?.length?u.vendors.map(v=>`<div class="flex justify-between p-3 border rounded"><div><p class="font-medium">${v.name}</p><p class="text-sm text-gray-500">${v.email}</p></div><div><p class="font-semibold text-green-600">${v.scans} scans</p><p class="text-xs text-gray-500">${v.last_scan}</p></div></div>`).join(''):'<p class="text-center text-gray-500">No vendors assigned</p>'}</div></div>
                    </div>`;
                const ctx=document.getElementById('unitMonthlyChart')?.getContext('2d');if(ctx)new Chart(ctx,{type:'line',data:{labels:u.monthly_trends.labels,datasets:[{label:'Daily Scans',data:u.monthly_trends.scans,borderColor:'#2596be',backgroundColor:'rgba(37,150,190,0.1)',tension:0.4,fill:true}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}}}});
            }else if(content)content.innerHTML=`<div class="text-center py-8"><i class="fas fa-exclamation-triangle text-2xl text-red-500 mb-2"></i><p>Failed to load unit analytics.</p></div>`;
        }).catch(()=>{if(content)content.innerHTML=`<div class="text-center py-8"><i class="fas fa-exclamation-triangle text-2xl text-red-500 mb-2"></i><p>Error loading unit analytics.</p></div>`;});
    }

    function closeUnitModal(){document.getElementById('unitDetailsModal')?.classList.add('hidden');}

    document.addEventListener('DOMContentLoaded',function(){
        document.getElementById('exportDateRange')?.addEventListener('change',function(){const div=document.getElementById('customExportRange');if(this.value==='custom'){div?.classList.remove('hidden');const s=document.getElementById('exportStartDate'),e=document.getElementById('exportEndDate');if(s&&e){s.value=currentStartDate?.toISOString().split('T')[0]||'';e.value=currentEndDate?.toISOString().split('T')[0]||'';}}else div?.classList.add('hidden');});
        setDateRange('month');
        loadAnalyticsData('month');
    });
</script>
@endsection
