@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Employee Onboarding Applications</h1>
        <p class="text-gray-600 mt-1">Manage and review all employee onboarding submissions</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-users text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Applications</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-clock text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Pending Review</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['submitted'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-check-circle text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Approved</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['approved'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-times-circle text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Rejected</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['rejected'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Statistics Section -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Applications by Department</h2>
            <span class="text-sm text-gray-500">Click any department to view applications</span>
        </div>
        <div class="p-4">
            @if($departmentStats->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($departmentStats as $stat)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-lg transition-all duration-200 transform hover:-translate-y-1 cursor-pointer bg-gradient-to-br from-white to-gray-50 department-card"
                             data-department-id="{{ $stat->id }}"
                             data-department-name="{{ $stat->name }}">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 truncate" title="{{ $stat->name }}">{{ $stat->name }}</h3>
                                    <p class="text-xs text-gray-500 mt-1">Department</p>
                                </div>
                                <span class="bg-primary-red text-white text-xs px-2.5 py-1.5 rounded-full font-bold ml-2">
                                    {{ $stat->total_applications }}
                                </span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mb-4">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-600">Completion Rate</span>
                                    <span class="font-medium">
                                        @if($stat->total_applications > 0)
                                            {{ round(($stat->approved_count / $stat->total_applications) * 100) }}%
                                        @else
                                            0%
                                        @endif
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full"
                                         style="width: {{ $stat->total_applications > 0 ? ($stat->approved_count / $stat->total_applications) * 100 : 0 }}%">
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div class="text-center p-2 bg-yellow-50 rounded-lg">
                                    <div class="text-lg font-bold text-yellow-700">{{ $stat->pending_count }}</div>
                                    <div class="text-xs text-yellow-600">Pending</div>
                                </div>
                                <div class="text-center p-2 bg-green-50 rounded-lg">
                                    <div class="text-lg font-bold text-green-700">{{ $stat->approved_count }}</div>
                                    <div class="text-xs text-green-600">Approved</div>
                                </div>
                            </div>

                            <div class="pt-3 border-t flex justify-between">
                                <button onclick="viewDepartmentApplications({{ $stat->id }})"
                                        class="text-sm px-3 py-1.5 bg-primary-red text-white rounded-md hover:bg-red-700 transition-colors flex items-center">
                                    <i class="fas fa-eye mr-1.5"></i> View All
                                </button>
                                <a href="{{ route('admin.onboarding.department.export', $stat->id) }}"
                                   class="text-sm px-3 py-1.5 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors flex items-center">
                                    <i class="fas fa-file-excel mr-1.5"></i> Export
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Summary -->
                <div class="mt-6 pt-4 border-t">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">
                                Showing {{ $departmentStats->count() }} department(s) with applications
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                Total applications across all departments: {{ $stats['total'] }}
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                                <i class="fas fa-building mr-1"></i> Active Departments
                            </span>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                        <i class="fas fa-building text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Department Applications</h3>
                    <p class="text-gray-500 max-w-md mx-auto">
                        No applications have been submitted yet. Applications will appear here organized by department.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Filters Card -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">Filter Applications</h2>
        </div>
        <div class="p-4">
            <form id="filterForm" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent"
                           placeholder="Name, Email, Token...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select name="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit" class="px-4 py-2 bg-primary-red text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-red transition-colors flex items-center">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                    <a href="{{ route('admin.onboarding.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Applications Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b flex justify-between items-center">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">All Applications</h2>
                <p class="text-sm text-gray-500 mt-1">Browse and manage individual applications</p>
            </div>
            <span class="text-sm text-gray-500">{{ $applications->total() }} records found</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Submitted</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($applications as $application)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-100 to-blue-50 rounded-full flex items-center justify-center shadow-sm">
                                    <span class="text-blue-600 font-medium text-sm">
                                        {{ strtoupper(substr($application->first_name, 0, 1)) }}{{ strtoupper(substr($application->last_name, 0, 1)) }}
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $application->first_name }} {{ $application->last_name }}
                                    </div>
                                    <div class="text-sm text-gray-500 truncate max-w-[180px]">{{ $application->personal_email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $application->designation }}</div>
                            <div class="text-xs text-gray-500 px-2 py-0.5 bg-gray-100 rounded-full inline-block mt-1">
                                {{ $application->employment_type ?: 'Regular' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ optional($application->department)->name ?: 'N/A' }}</div>
                            <div class="text-xs text-gray-500">{{ optional($application->unit)->name ?: 'No Unit' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'submitted' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                                    'reviewed' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                    'approved' => 'bg-green-100 text-green-800 border border-green-200',
                                    'rejected' => 'bg-red-100 text-red-800 border border-red-200',
                                    'on_hold' => 'bg-gray-100 text-gray-800 border border-gray-200'
                                ];
                            @endphp
                            <span class="px-3 py-1.5 inline-flex text-xs leading-4 font-semibold rounded-full {{ $statusColors[$application->status] ?? 'bg-gray-100 text-gray-800' }}">
                                <i class="fas fa-circle mr-1.5 text-xs mt-0.5"></i>
                                {{ ucfirst(str_replace('_', ' ', $application->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $application->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $application->created_at->format('h:i A') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-3">
                                <button onclick="viewApplication({{ $application->id }})"
                                        class="text-primary-red hover:text-red-700 transition-colors flex items-center group">
                                    <i class="fas fa-eye mr-1.5 group-hover:scale-110 transition-transform"></i> View
                                </button>
                                <a href="{{ route('employee.onboarding.confirmation', $application->token) }}"
                                   target="_blank"
                                   class="text-secondary-blue hover:text-blue-700 transition-colors flex items-center group">
                                    <i class="fas fa-external-link-alt mr-1.5 group-hover:scale-110 transition-transform"></i> Public
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                <i class="fas fa-inbox text-2xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Applications Found</h3>
                            <p class="text-gray-500 max-w-md mx-auto">
                                @if(request()->hasAny(['search', 'status', 'department_id']))
                                    No applications match your filters. Try adjusting your search criteria.
                                @else
                                    No applications have been submitted yet. Check back later.
                                @endif
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($applications->hasPages())
        <div class="px-6 py-4 border-t bg-gray-50">
            {{ $applications->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Department Applications Modal -->
<div id="departmentApplicationsModal" class="fixed inset-0 bg-gray-900/60 hidden overflow-y-auto h-full w-full z-[60] backdrop-blur-sm">
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="relative w-full max-w-7xl bg-white rounded-xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <!-- Modal Header -->
            <div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 h-10 w-10 bg-primary-red/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-building text-primary-red text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900" id="departmentModalTitle">Department Applications</h3>
                            <p class="text-sm text-gray-500" id="departmentModalSubtitle">Loading department details...</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="#" id="exportDepartmentBtn"
                           class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-green-600 to-green-700 text-white font-medium rounded-lg hover:from-green-700 hover:to-green-800 transition-all shadow-sm hover:shadow-md">
                            <i class="fas fa-file-excel mr-2.5"></i>
                            Export to Excel
                        </a>
                        <button onclick="closeDepartmentModal()"
                                class="inline-flex items-center justify-center h-10 w-10 rounded-lg hover:bg-gray-100 transition-colors text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Stats Bar -->
                <div id="departmentStatsBar" class="mt-4 grid grid-cols-3 gap-3">
                    <div class="text-center p-3 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg">
                        <div class="text-2xl font-bold text-blue-700" id="totalApplicationsCount">0</div>
                        <div class="text-xs font-medium text-blue-600 uppercase tracking-wider mt-1">Total</div>
                    </div>
                    <div class="text-center p-3 bg-gradient-to-r from-yellow-50 to-yellow-100 rounded-lg">
                        <div class="text-2xl font-bold text-yellow-700" id="pendingApplicationsCount">0</div>
                        <div class="text-xs font-medium text-yellow-600 uppercase tracking-wider mt-1">Pending</div>
                    </div>
                    <div class="text-center p-3 bg-gradient-to-r from-green-50 to-green-100 rounded-lg">
                        <div class="text-2xl font-bold text-green-700" id="approvedApplicationsCount">0</div>
                        <div class="text-xs font-medium text-green-600 uppercase tracking-wider mt-1">Approved</div>
                    </div>
                </div>
            </div>

            <!-- Modal Content -->
            <div class="flex-1 overflow-hidden">
                <div id="departmentApplicationsContainer" class="h-full overflow-y-auto p-6">
                    <!-- Loading State -->
                    <div class="h-full flex flex-col items-center justify-center py-12">
                        <div class="relative">
                            <div class="w-16 h-16 border-4 border-primary-red/20 border-t-primary-red rounded-full animate-spin"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <i class="fas fa-building text-primary-red text-xl"></i>
                            </div>
                        </div>
                        <p class="mt-4 text-gray-600 font-medium">Loading department applications...</p>
                        <p class="text-sm text-gray-500 mt-1">Please wait while we fetch the data</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-500" id="departmentSummaryText">
                        Loading summary...
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="closeDepartmentModal()"
                                class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Application Modal (Existing) -->
<div id="viewApplicationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-6xl shadow-lg rounded-md bg-white max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-bold text-gray-900">Application Details</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <div id="applicationDetails" class="py-4">
            <!-- Content will be loaded via AJAX -->
        </div>

        <div class="flex justify-end space-x-3 pt-4 border-t">
            <button onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                Close
            </button>
            <button onclick="updateStatusModal()" class="px-4 py-2 bg-primary-red text-white rounded-md hover:bg-red-700">
                Update Status
            </button>
        </div>
    </div>
</div>

<!-- Update Status Modal (Existing) -->
<div id="updateStatusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-lg font-bold text-gray-900">Update Application Status</h3>
            <button onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="statusForm" class="py-4">
            @csrf
            <input type="hidden" id="applicationId" name="application_id">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="statusSelect" name="status" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                <textarea id="statusNotes" name="notes" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent"
                          placeholder="Add any notes or comments..."></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" onclick="closeStatusModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-primary-red text-white rounded-md hover:bg-red-700">
                    Update Status
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('styles')
<style>
.department-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.department-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #EF4444, #DC2626);
    transform: translateY(-100%);
    transition: transform 0.3s ease;
}

.department-card:hover::before {
    transform: translateY(0);
}

.department-card:hover {
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    border-color: #EF4444;
}

/* Modal animations */
#departmentApplicationsModal {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Status badge animations */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

.status-badge {
    animation: pulse 2s infinite;
}
</style>
@endsection

@section('scripts')
<script>
let currentApplicationId = null;
let currentDepartmentId = null;

// Department Functions
function viewDepartmentApplications(departmentId) {
    currentDepartmentId = departmentId;
    const departmentCard = document.querySelector(`[data-department-id="${departmentId}"]`);
    const departmentName = departmentCard ? departmentCard.getAttribute('data-department-name') : 'Department';

    // Update modal title
    document.getElementById('departmentModalTitle').textContent = `${departmentName} Applications`;
    document.getElementById('departmentModalSubtitle').textContent = 'All applications in this department';

    // Set export link
    const exportBtn = document.getElementById('exportDepartmentBtn');
    exportBtn.href = `/admin/onboarding/department/${departmentId}/export`;

    // Show modal
    document.getElementById('departmentApplicationsModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Load applications
    loadDepartmentApplications(departmentId);
}

function loadDepartmentApplications(departmentId) {
    const container = document.getElementById('departmentApplicationsContainer');

    // Show loading skeleton
    container.innerHTML = `
        <div class="space-y-4">
            <!-- Skeleton Header -->
            <div class="flex justify-between items-center mb-6">
                <div class="h-8 bg-gray-200 rounded w-1/4 animate-pulse"></div>
                <div class="h-10 bg-gray-200 rounded w-32 animate-pulse"></div>
            </div>

            <!-- Skeleton Table -->
            <div class="space-y-3">
                ${Array(5).fill().map((_, i) => `
                    <div class="grid grid-cols-6 gap-4 p-4 border rounded-lg">
                        <div class="col-span-2">
                            <div class="flex items-center space-x-3">
                                <div class="h-10 w-10 bg-gray-200 rounded-full animate-pulse"></div>
                                <div class="space-y-2">
                                    <div class="h-4 bg-gray-200 rounded w-24 animate-pulse"></div>
                                    <div class="h-3 bg-gray-200 rounded w-32 animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-4 bg-gray-200 rounded w-20 animate-pulse"></div>
                            <div class="h-3 bg-gray-200 rounded w-16 animate-pulse"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-4 bg-gray-200 rounded w-16 animate-pulse"></div>
                            <div class="h-3 bg-gray-200 rounded w-20 animate-pulse"></div>
                        </div>
                        <div><div class="h-6 bg-gray-200 rounded w-20 animate-pulse"></div></div>
                        <div class="space-y-2">
                            <div class="h-4 bg-gray-200 rounded w-16 animate-pulse"></div>
                            <div class="h-3 bg-gray-200 rounded w-12 animate-pulse"></div>
                        </div>
                        <div class="flex space-x-2">
                            <div class="h-8 bg-gray-200 rounded w-16 animate-pulse"></div>
                            <div class="h-8 bg-gray-200 rounded w-16 animate-pulse"></div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;

    fetch(`/admin/onboarding/department/${departmentId}`)
        .then(response => response.json())
        .then(data => {
            const { applications, department } = data;

            // Update stats
            const totalCount = applications.length;
            const pendingCount = applications.filter(app => app.status === 'submitted' || app.status === 'reviewed').length;
            const approvedCount = applications.filter(app => app.status === 'approved').length;

            document.getElementById('totalApplicationsCount').textContent = totalCount;
            document.getElementById('pendingApplicationsCount').textContent = pendingCount;
            document.getElementById('approvedApplicationsCount').textContent = approvedCount;

            // Update summary text
            const summaryText = document.getElementById('departmentSummaryText');
            if (totalCount === 0) {
                summaryText.innerHTML = `<span class="text-red-600 font-medium">No applications found in this department</span>`;
            } else {
                summaryText.innerHTML = `
                    Showing <span class="font-bold text-primary-red">${totalCount}</span> application(s) in
                    <span class="font-bold text-gray-900">${department.name}</span>
                `;
            }

            if (applications.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-16">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-6">
                            <i class="fas fa-inbox text-3xl text-gray-400"></i>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 mb-3">No Applications Found</h3>
                        <p class="text-gray-500 max-w-md mx-auto mb-6">
                            There are no applications in the <strong>${department.name}</strong> department yet.
                        </p>
                        <button onclick="closeDepartmentModal()"
                                class="px-5 py-2.5 bg-primary-red text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                        </button>
                    </div>
                `;
                return;
            }

            let html = `
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">All Applications</h4>
                            <p class="text-sm text-gray-500">Sorted by most recent submission</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">Filter:</span>
                            <select onchange="filterDepartmentApplications(this.value)" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary-red">
                                <option value="all">All Status</option>
                                <option value="submitted">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Submitted</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="departmentApplicationsTableBody">
            `;

            applications.forEach(app => {
                const statusColors = {
                    'submitted': 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                    'reviewed': 'bg-blue-100 text-blue-800 border border-blue-200',
                    'approved': 'bg-green-100 text-green-800 border border-green-200',
                    'rejected': 'bg-red-100 text-red-800 border border-red-200',
                    'on_hold': 'bg-gray-100 text-gray-800 border border-gray-200'
                };

                const statusIcons = {
                    'submitted': 'fa-clock',
                    'reviewed': 'fa-search',
                    'approved': 'fa-check-circle',
                    'rejected': 'fa-times-circle',
                    'on_hold': 'fa-pause-circle'
                };

                html += `
                    <tr class="hover:bg-gray-50 transition-colors" data-status="${app.status}">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-100 to-blue-50 rounded-full flex items-center justify-center shadow-sm">
                                    <span class="text-blue-600 font-medium text-sm">
                                        ${app.first_name.charAt(0).toUpperCase()}${app.last_name.charAt(0).toUpperCase()}
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        ${app.first_name} ${app.last_name}
                                    </div>
                                    <div class="text-sm text-gray-500 truncate max-w-[180px]">${app.personal_email}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">${app.designation}</div>
                            <div class="text-xs text-gray-500 px-2 py-0.5 bg-gray-100 rounded-full inline-block mt-1">
                                ${app.employment_type || 'Regular'}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">${app.unit ? app.unit.name : 'N/A'}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1.5 inline-flex text-xs leading-4 font-semibold rounded-full ${statusColors[app.status]}">
                                <i class="fas ${statusIcons[app.status]} mr-1.5 text-xs mt-0.5"></i>
                                ${app.status.charAt(0).toUpperCase() + app.status.slice(1).replace('_', ' ')}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">${new Date(app.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</div>
                            <div class="text-xs text-gray-500">${new Date(app.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <button onclick="viewApplicationFromDepartment(${app.id})"
                                        class="px-3 py-1.5 bg-primary-red text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors flex items-center">
                                    <i class="fas fa-eye mr-1.5"></i> View
                                </button>
                                <a href="/employee-onboarding/confirmation/${app.token}"
                                   target="_blank"
                                   class="px-3 py-1.5 bg-secondary-blue text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                                    <i class="fas fa-external-link-alt mr-1.5"></i> Public
                                </a>
                            </div>
                        </td>
                    </tr>
                `;
            });

            html += `
                            </tbody>
                        </table>
                    </div>

                    <!-- Application Status Summary -->
                    <div class="mt-6 p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200">
                        <h5 class="text-sm font-semibold text-gray-900 mb-3">Status Distribution</h5>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                            <div class="text-center p-3 bg-white rounded-lg border border-gray-200">
                                <div class="text-lg font-bold text-gray-900">${totalCount}</div>
                                <div class="text-xs text-gray-600">Total</div>
                            </div>
                            <div class="text-center p-3 bg-white rounded-lg border border-yellow-200">
                                <div class="text-lg font-bold text-yellow-700">${applications.filter(a => a.status === 'submitted').length}</div>
                                <div class="text-xs text-yellow-600">Submitted</div>
                            </div>
                            <div class="text-center p-3 bg-white rounded-lg border border-blue-200">
                                <div class="text-lg font-bold text-blue-700">${applications.filter(a => a.status === 'reviewed').length}</div>
                                <div class="text-xs text-blue-600">Reviewed</div>
                            </div>
                            <div class="text-center p-3 bg-white rounded-lg border border-green-200">
                                <div class="text-lg font-bold text-green-700">${approvedCount}</div>
                                <div class="text-xs text-green-600">Approved</div>
                            </div>
                            <div class="text-center p-3 bg-white rounded-lg border border-red-200">
                                <div class="text-lg font-bold text-red-700">${applications.filter(a => a.status === 'rejected').length}</div>
                                <div class="text-xs text-red-600">Rejected</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            container.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = `
                <div class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-100 mb-6">
                        <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-medium text-gray-900 mb-3">Error Loading Data</h3>
                    <p class="text-gray-500 max-w-md mx-auto mb-6">
                        There was an error loading department applications. Please try again.
                    </p>
                    <button onclick="loadDepartmentApplications(${departmentId})"
                            class="px-5 py-2.5 bg-primary-red text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-redo mr-2"></i> Try Again
                    </button>
                </div>
            `;
        });
}

function filterDepartmentApplications(status) {
    const rows = document.querySelectorAll('#departmentApplicationsTableBody tr');

    rows.forEach(row => {
        if (status === 'all' || row.getAttribute('data-status') === status) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
}

function viewApplicationFromDepartment(appId) {
    closeDepartmentModal();
    viewApplication(appId);
}

function closeDepartmentModal() {
    document.getElementById('departmentApplicationsModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    currentDepartmentId = null;
}

// Existing Application Functions
function viewApplication(id) {
    currentApplicationId = id;

    // Show loading
    document.getElementById('applicationDetails').innerHTML = `
        <div class="flex justify-center items-center h-40">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-red"></div>
        </div>
    `;

    // Show modal
    document.getElementById('viewApplicationModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Load application details
    fetch(`/admin/onboarding/${id}`)
        .then(response => response.json())
        .then(data => {
            // ... existing view application code ...
        })
        .catch(error => {
            document.getElementById('applicationDetails').innerHTML = `
                <div class="text-center text-red-600 p-4">
                    <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                    <p>Error loading application details</p>
                </div>
            `;
        });
}

function closeModal() {
    document.getElementById('viewApplicationModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function updateStatusModal() {
    closeModal();
    document.getElementById('updateStatusModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.getElementById('applicationId').value = currentApplicationId;
}

function closeStatusModal() {
    document.getElementById('updateStatusModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to department cards
    const departmentCards = document.querySelectorAll('.department-card');
    departmentCards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.closest('button') && !e.target.closest('a')) {
                const deptId = this.getAttribute('data-department-id');
                viewDepartmentApplications(deptId);
            }
        });
    });

    // Close modals when clicking outside
    document.getElementById('departmentApplicationsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDepartmentModal();
        }
    });

    document.getElementById('viewApplicationModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    document.getElementById('updateStatusModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeStatusModal();
        }
    });

    // Escape key to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDepartmentModal();
            closeModal();
            closeStatusModal();
        }
    });
});
// Existing Application Functions
function viewApplication(id) {
    currentApplicationId = id;

    // Show loading
    document.getElementById('applicationDetails').innerHTML = `
        <div class="flex justify-center items-center h-40">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-red"></div>
        </div>
    `;

    // Show modal
    document.getElementById('viewApplicationModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Load application details
    fetch(`/admin/onboarding/${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const app = data;
            const dept = app.department ? app.department.name : 'N/A';
            const unit = app.unit ? app.unit.name : 'N/A';

            const documents = [
                { key: 'national_id_photo', label: 'National ID Photo' },
                { key: 'passport_photo', label: 'Passport Photo' },
                { key: 'passport_size_photo', label: 'Passport Size Photo' },
                { key: 'nssf_card_photo', label: 'NSSF Card Photo' },
                { key: 'sha_card_photo', label: 'SHA Card Photo' },
                { key: 'kra_certificate_photo', label: 'KRA Certificate Photo' }
            ];

            let docsHtml = '';
            documents.forEach(doc => {
                if (app[doc.key]) {
                    docsHtml += `
                    <div class="mb-2">
                        <span class="text-sm text-gray-600">${doc.label}:</span>
                        <a href="/admin/onboarding/${id}/download/${doc.key}"
                           class="ml-2 text-sm text-secondary-blue hover:text-blue-700">
                            <i class="fas fa-download mr-1"></i>Download
                        </a>
                    </div>
                    `;
                }
            });

            const statusColors = {
                'submitted': 'bg-yellow-100 text-yellow-800',
                'reviewed': 'bg-blue-100 text-blue-800',
                'approved': 'bg-green-100 text-green-800',
                'rejected': 'bg-red-100 text-red-800',
                'on_hold': 'bg-gray-100 text-gray-800'
            };

            document.getElementById('applicationDetails').innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Information -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">Personal Information</h4>
                        <div class="space-y-2">
                            <div><span class="text-sm text-gray-600">Full Name:</span> ${app.first_name} ${app.middle_name ? app.middle_name + ' ' : ''}${app.last_name}</div>
                            <div><span class="text-sm text-gray-600">Email:</span> ${app.personal_email}</div>
                            <div><span class="text-sm text-gray-600">Phone:</span> ${app.personal_phone}</div>
                            <div><span class="text-sm text-gray-600">Date of Birth:</span> ${app.date_of_birth || 'N/A'}</div>
                            <div><span class="text-sm text-gray-600">Gender:</span> ${app.gender || 'N/A'}</div>
                        </div>
                    </div>

                    <!-- Employment Details -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">Employment Details</h4>
                        <div class="space-y-2">
                            <div><span class="text-sm text-gray-600">Designation:</span> ${app.designation}</div>
                            <div><span class="text-sm text-gray-600">Date of Joining:</span> ${app.date_of_joining}</div>
                            <div><span class="text-sm text-gray-600">Department:</span> ${dept}</div>
                            <div><span class="text-sm text-gray-600">Unit:</span> ${unit}</div>
                            <div><span class="text-sm text-gray-600">Employment Type:</span> ${app.employment_type || 'N/A'}</div>
                        </div>
                    </div>

                    <!-- Statutory Information -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">Statutory Information</h4>
                        <div class="space-y-2">
                            <div><span class="text-sm text-gray-600">National ID:</span> ${app.national_id_number}</div>
                            <div><span class="text-sm text-gray-600">Passport No:</span> ${app.passport_number || 'N/A'}</div>
                            <div><span class="text-sm text-gray-600">NSSF No:</span> ${app.nssf_number}</div>
                            <div><span class="text-sm text-gray-600">SHA No:</span> ${app.sha_number}</div>
                            <div><span class="text-sm text-gray-600">KRA PIN:</span> ${app.kra_pin}</div>
                        </div>
                    </div>

                    <!-- Next of Kin -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">Next of Kin</h4>
                        <div class="space-y-2">
                            <div><span class="text-sm text-gray-600">Name:</span> ${app.next_of_kin_name}</div>
                            <div><span class="text-sm text-gray-600">Phone:</span> ${app.next_of_kin_phone}</div>
                            <div><span class="text-sm text-gray-600">Relationship:</span> ${app.next_of_kin_relationship}</div>
                            <div><span class="text-sm text-gray-600">Email:</span> ${app.next_of_kin_email || 'N/A'}</div>
                            <div><span class="text-sm text-gray-600">Address:</span> ${app.next_of_kin_address || 'N/A'}</div>
                        </div>
                    </div>

                    <!-- Documents -->
                    <div class="md:col-span-2">
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">Documents</h4>
                        <div class="bg-gray-50 p-4 rounded-md">
                            ${docsHtml || 'No documents uploaded'}
                        </div>
                    </div>

                    <!-- Admin Notes -->
                    ${app.admin_notes ? `
                    <div class="md:col-span-2">
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">Admin Notes</h4>
                        <div class="bg-yellow-50 p-4 rounded-md border border-yellow-200">
                            <p class="text-sm text-gray-700">${app.admin_notes}</p>
                        </div>
                    </div>
                    ` : ''}

                    <!-- System Information -->
                    <div class="md:col-span-2">
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">System Information</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <span class="text-sm text-gray-600">Application Token:</span>
                                <div class="font-mono text-sm">${app.token}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Status:</span>
                                <div><span class="px-2 py-1 text-xs rounded-full ${statusColors[app.status]}">${app.status.charAt(0).toUpperCase() + app.status.slice(1)}</span></div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Submitted:</span>
                                <div>${new Date(app.created_at).toLocaleDateString()}</div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Location:</span>
                                <div>${app.location}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error loading application:', error);
            document.getElementById('applicationDetails').innerHTML = `
                <div class="text-center text-red-600 p-4">
                    <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                    <p class="font-medium">Error loading application details</p>
                    <p class="text-sm mt-2">Please try again or contact support if the problem persists.</p>
                </div>
            `;
        });
}
// Handle status form submission
document.getElementById('statusForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const id = formData.get('application_id');

    fetch(`/admin/onboarding/${id}/status`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            status: document.getElementById('statusSelect').value,
            notes: document.getElementById('statusNotes').value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Status updated successfully');
            closeStatusModal();
            location.reload();
        } else {
            alert('Error updating status');
        }
    })
    .catch(error => {
        alert('Error updating status');
    });
});
</script>
@endsection
