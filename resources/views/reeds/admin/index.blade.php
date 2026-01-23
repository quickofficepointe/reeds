@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Welcome Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-text-black">Dashboard</h1>
        <p class="text-gray-600 mt-2">Welcome back, {{ Auth::user()->name }}! Here's what's happening today.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Employees Card -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Employees</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $stats['total_employees'] }}</p>
                </div>
                <div class="w-12 h-12 bg-primary-red bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-primary-red text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-xs text-gray-500">{{ $stats['active_employees'] }} active</span>
            </div>
        </div>

        <!-- Pending Verifications Card -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending Verifications</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $stats['pending_verifications'] }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-clock text-yellow-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.verifications') }}" class="text-xs text-secondary-blue hover:text-[#1e7a9e] font-medium">
                    Review pending vendors →
                </a>
            </div>
        </div>

        <!-- Today's Scans Card -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Today's Scans</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $stats['today_scans'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-qrcode text-green-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-xs text-gray-500">KSh {{ number_format($stats['total_revenue_today'], 2) }} revenue</span>
            </div>
        </div>

        <!-- Total Vendors Card -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Vendors</p>
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
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Quick Actions & Recent Activity -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-text-black mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 gap-4">
                    <a href="{{ route('admin.employees.import') }}" class="p-4 border border-gray-200 rounded-lg hover:border-secondary-blue hover:bg-blue-50 transition duration-150 group">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-primary-red rounded-lg flex items-center justify-center group-hover:bg-primary-red">
                                <i class="fas fa-upload text-white text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium text-text-black text-sm">Upload Employees</p>
                                <p class="text-xs text-gray-500">CSV/Excel</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('admin.verifications') }}" class="p-4 border border-gray-200 rounded-lg hover:border-secondary-blue hover:bg-blue-50 transition duration-150 group">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center group-hover:bg-yellow-500">
                                <i class="fas fa-user-check text-white text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium text-text-black text-sm">Verify Vendors</p>
                                <p class="text-xs text-gray-500">{{ $stats['pending_verifications'] }} pending</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('admin.employees.qr-codes') }}" class="p-4 border border-gray-200 rounded-lg hover:border-secondary-blue hover:bg-blue-50 transition duration-150 group">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-secondary-blue rounded-lg flex items-center justify-center group-hover:bg-secondary-blue">
                                <i class="fas fa-qrcode text-white text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium text-text-black text-sm">View QR Codes</p>
                                <p class="text-xs text-gray-500">Employee QR codes</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('admin.analytics') }}" class="p-4 border border-gray-200 rounded-lg hover:border-secondary-blue hover:bg-blue-50 transition duration-150 group">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center group-hover:bg-green-500">
                                <i class="fas fa-chart-bar text-white text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium text-text-black text-sm">View Analytics</p>
                                <p class="text-xs text-gray-500">Reports & Insights</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-text-black">Recent Transactions</h2>
                    <a href="{{ route('admin.analytics') }}" class="text-sm text-secondary-blue hover:text-blue-600">View All</a>
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
                            <p class="font-semibold text-green-600">KSh {{ $transaction->amount }}</p>
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

        <!-- Right Sidebar -->
        <div class="space-y-8">
            <!-- Top Vendors -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-text-black mb-4">Top Vendors This Month</h2>
                <div class="space-y-3">
                    @forelse($topVendors as $vendor)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-secondary-blue rounded-full flex items-center justify-center">
                                <i class="fas fa-store text-white text-xs"></i>
                            </div>
                            <div>
                                <p class="font-medium text-text-black text-sm">{{ $vendor->name }}</p>
                                <p class="text-xs text-gray-500">{{ $vendor->total_scans }} scans</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-green-600">KSh {{ number_format($vendor->total_revenue, 2) }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-gray-500">
                        <p class="text-sm">No vendor activity yet</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Department Stats -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-text-black mb-4">Department Overview</h2>
                <div class="space-y-3">
                    @forelse($departmentStats as $department)
                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                        <div>
                            <p class="font-medium text-text-black text-sm">{{ $department->name }}</p>
                            <p class="text-xs text-gray-500">{{ $department->total_employees }} employees</p>
                        </div>
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                            {{ $department->total_employees }}
                        </span>
                    </div>
                    @empty
                    <div class="text-center py-4 text-gray-500">
                        <p class="text-sm">No departments yet</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-text-black mb-4">System Status</h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-text-black">Employee Database</p>
                            <p class="text-sm text-gray-500">{{ $stats['total_employees'] }} employees</p>
                        </div>
                        <span class="px-2 py-1 {{ $stats['total_employees'] > 0 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }} text-xs rounded-full">
                            {{ $stats['total_employees'] > 0 ? 'Active' : 'Setup Required' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-text-black">Vendor System</p>
                            <p class="text-sm text-gray-500">{{ $stats['total_vendors'] }} vendors</p>
                        </div>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-text-black">QR Scanning</p>
                            <p class="text-sm text-gray-500">{{ $stats['today_scans'] }} today</p>
                        </div>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Operational</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-refresh dashboard stats every 30 seconds
    setInterval(() => {
        window.location.reload();
    }, 30000);
</script>
@endsection
