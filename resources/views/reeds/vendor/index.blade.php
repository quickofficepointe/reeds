@extends('reeds.vendor.layout.vendorlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Welcome Header with Quick Scan -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex items-center space-x-4">
                <div>
                    <h1 class="text-3xl font-bold text-text-black">Vendor Dashboard</h1>
                    <p class="text-gray-600 mt-2">Welcome back, {{ Auth::user()->name }}!</p>
                </div>

                @if(Auth::user()->profile && Auth::user()->profile->isVerified())
                <!-- Quick Scan Button - Always Visible when Verified -->
                <a href="{{ route('vendor.scan') }}"
                   class="flex items-center space-x-2 px-6 py-3 bg-primary-red text-white rounded-lg hover:bg-red-700 transition duration-150 shadow-md">
                    <i class="fas fa-camera text-white"></i>
                    <span class="font-semibold">Scan QR</span>
                </a>
                @endif
            </div>

            @if(Auth::user()->profile && Auth::user()->profile->isVerified())
            <div class="mt-4 md:mt-0">
                <span class="px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium flex items-center space-x-2">
                    <i class="fas fa-check-circle"></i>
                    <span>Verified Vendor</span>
                </span>
            </div>
            @else
            <div class="mt-4 md:mt-0">
                <span class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium flex items-center space-x-2">
                    <i class="fas fa-clock"></i>
                    <span>Pending Verification</span>
                </span>
            </div>
            @endif
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Today's Scans Card -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Today's Scans</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="todayScans">0</p>
                </div>
                <div class="w-12 h-12 bg-primary-red bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-qrcode text-primary-red text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-xs text-gray-500" id="todayScansTime">Loading...</span>
            </div>
        </div>

        <!-- Total Scans Card -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Scans</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="totalScans">0</p>
                </div>
                <div class="w-12 h-12 bg-secondary-blue bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-history text-secondary-blue text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-xs text-gray-500">All time scans</span>
            </div>
        </div>

        <!-- Revenue Card -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Today's Revenue</p>
                    <p class="text-2xl font-bold text-text-black mt-2" id="todayRevenue">Ksh 0</p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-green-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-xs text-gray-500">Based on 70 per scan</span>
            </div>
        </div>
    </div>

    <!-- Quick Actions - Reordered with Scan First -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Main Action Card -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <h2 class="text-xl font-bold text-text-black mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if(Auth::user()->profile && Auth::user()->profile->isVerified())
                <!-- Primary Scan Action - More Prominent -->
                <a href="{{ route('vendor.scan') }}"
                   class="p-6 border-2 border-secondary-blue bg-secondary-blue bg-opacity-5 rounded-lg hover:bg-secondary-blue hover:bg-opacity-10 transition duration-150 group relative">
                    <!-- Highlight Badge -->
                    <div class="absolute -top-2 -right-2 bg-primary-red text-white text-xs px-2 py-1 rounded-full">
                        Quick Scan
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-secondary-blue rounded-lg flex items-center justify-center group-hover:bg-secondary-blue">
                            <i class="fas fa-camera text-white text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-text-black text-lg">Scan QR Code</p>
                            <p class="text-sm text-gray-500 mt-1">Start scanning employee QR codes</p>
                        </div>
                    </div>
                </a>
                @else
                <div class="p-6 border-2 border-gray-300 rounded-lg bg-gray-50 opacity-75">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gray-400 rounded-lg flex items-center justify-center">
                            <i class="fas fa-camera text-white text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-text-black text-lg">Scan QR Code</p>
                            <p class="text-sm text-gray-500 mt-1">Available after verification</p>
                        </div>
                    </div>
                </div>
                @endif

                <a href="#" onclick="showScanHistory()" class="p-6 border-2 border-gray-200 rounded-lg hover:border-primary-red hover:bg-red-50 transition duration-150 group">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-primary-red rounded-lg flex items-center justify-center group-hover:bg-primary-red">
                            <i class="fas fa-history text-white text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-text-black text-lg">Scan History</p>
                            <p class="text-sm text-gray-500 mt-1">View previous scans</p>
                        </div>
                    </div>
                </a>

                <a href="#" onclick="refreshStats()" class="p-6 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition duration-150 group">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center group-hover:bg-green-500">
                            <i class="fas fa-chart-line text-white text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-text-black text-lg">Today's Stats</p>
                            <p class="text-sm text-gray-500 mt-1">View daily analytics</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('profile.show') }}" class="p-6 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition duration-150 group">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center group-hover:bg-blue-500">
                            <i class="fas fa-user text-white text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-text-black text-lg">My Profile</p>
                            <p class="text-sm text-gray-500 mt-1">Update your information</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Recent Scans Section -->
            <div class="mt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-text-black">Today's Scans</h3>
                    <button onclick="refreshStats()" class="text-sm text-secondary-blue hover:text-primary-red flex items-center space-x-1">
                        <i class="fas fa-sync-alt"></i>
                        <span>Refresh</span>
                    </button>
                </div>
                <div id="recentScansContainer">
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-history text-3xl mb-2"></i>
                        <p>No scans today</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Card -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <h2 class="text-xl font-bold text-text-black mb-4">Account Status</h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-text-black">Verification Status</span>
                    @if(Auth::user()->profile && Auth::user()->profile->isVerified())
                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Verified</span>
                    @else
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Pending</span>
                    @endif
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-text-black">Profile Complete</span>
                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Yes</span>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-text-black">Scans Today</span>
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full" id="statusScansToday">0</span>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-text-black">Total Revenue</span>
                    <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full" id="statusTotalRevenue">Ksh 0</span>
                </div>
            </div>

            <!-- Quick Scan Help Section -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-camera text-secondary-blue mt-1"></i>
                    <div>
                        <p class="text-sm font-medium text-text-black">Quick Scan Tip</p>
                        <p class="text-xs text-gray-600 mt-1">Use the "Scan QR" button above for immediate scanning access.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scan History Modal -->
<div id="scanHistoryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-text-black">Scan History</h3>
            <button onclick="closeScanHistory()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="scanHistoryContent">
            <!-- History will be loaded here -->
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Load dashboard stats
    function loadDashboardStats() {
        fetch('{{ route("vendor.dashboard-stats") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update stats cards
                    document.getElementById('todayScans').textContent = data.today.scans;
                    document.getElementById('totalScans').textContent = data.total.scans;
                    document.getElementById('todayRevenue').textContent = 'Ksh ' + (data.today.revenue || 0);

                    // Update status section
                    document.getElementById('statusScansToday').textContent = data.today.scans;
                    document.getElementById('statusTotalRevenue').textContent = 'Ksh ' + (data.total.revenue || 0);

                    // Update timestamp
                    document.getElementById('todayScansTime').textContent = 'Updated: ' + new Date().toLocaleTimeString();
                }
            })
            .catch(error => {
                console.error('Error loading stats:', error);
            });
    }

    // Load recent scans
    function loadRecentScans() {
        fetch('{{ route("vendor.scan-history") }}')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('recentScansContainer');

                if (data.success && data.transactions.length > 0) {
                    container.innerHTML = data.transactions.map(transaction => `
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg mb-2 hover:bg-gray-50 transition duration-150">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-utensils text-green-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-text-black">${transaction.employee.formal_name}</p>
                                    <p class="text-sm text-gray-500">${transaction.employee.employee_code} • ${transaction.employee.department.name}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-green-600">KSh ${transaction.amount}</p>
                                <p class="text-sm text-gray-500">${transaction.meal_time}</p>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-history text-3xl mb-2"></i>
                            <p>No scans today</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading recent scans:', error);
            });
    }

    // Show scan history modal
    function showScanHistory() {
        const modal = document.getElementById('scanHistoryModal');
        const content = document.getElementById('scanHistoryContent');

        // Show loading
        content.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-2xl text-secondary-blue mb-2"></i>
                <p>Loading history...</p>
            </div>
        `;

        modal.classList.remove('hidden');

        // Load history
        fetch('{{ route("vendor.scan-history") }}?date=all')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.transactions.length > 0) {
                    content.innerHTML = `
                        <div class="space-y-3">
                            ${data.transactions.map(transaction => `
                                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-150">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-utensils text-green-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-text-black">${transaction.employee.formal_name}</p>
                                            <p class="text-sm text-gray-500">${transaction.employee.employee_code}</p>
                                            <p class="text-xs text-gray-400">${transaction.meal_date} at ${transaction.meal_time}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-green-600">KSh ${transaction.amount}</p>
                                        <p class="text-xs text-gray-500">${transaction.transaction_code}</p>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                } else {
                    content.innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-history text-3xl mb-2"></i>
                            <p>No scan history found</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                content.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                        <p>Error loading history</p>
                    </div>
                `;
            });
    }

    // Close scan history modal
    function closeScanHistory() {
        document.getElementById('scanHistoryModal').classList.add('hidden');
    }

    // Refresh stats
    function refreshStats() {
        loadDashboardStats();
        loadRecentScans();

        // Show refresh notification
        Swal.fire({
            icon: 'success',
            title: 'Refreshed!',
            text: 'Dashboard stats updated',
            timer: 1500,
            showConfirmButton: false
        });
    }

    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
        loadDashboardStats();
        loadRecentScans();

        // Auto-refresh every 30 seconds
        setInterval(loadDashboardStats, 30000);
    });
</script>
@endsection
