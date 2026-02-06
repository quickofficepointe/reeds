@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-text-black">Manual Meal Entry</h1>
                <p class="text-gray-600 mt-2">For Monday, February 2nd, 2026 only</p>
            </div>
            <div class="mt-4 md:mt-0">
                <span class="px-3 py-1 bg-red-100 text-red-800 text-sm font-medium rounded-full">
                    <i class="fas fa-calendar-day mr-1"></i> Special Entry
                </span>
            </div>
        </div>
    </div>

    <!-- Debug Alert -->
    <div id="debugAlert" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 hidden">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-bug text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700" id="debugMessage"></p>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Entry Forms -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Single Entry Form -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-text-black">Record Manual Meal</h2>
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-utensils text-blue-500"></i>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                        <div>
                            <p class="text-sm text-blue-800">
                                This form is only for recording meals for employees who did not have meal cards
                                on <strong class="font-semibold">Monday, February 2nd, 2026</strong>.
                                These entries will count towards vendor totals for that day.
                            </p>
                        </div>
                    </div>
                </div>

                <form id="manualEntryForm">
                    @csrf
                    <input type="hidden" name="meal_date" value="2026-02-02">

                    <!-- Employee & Vendor Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Employee Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Employee *
                            </label>
                            <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent transition duration-150"
                                    name="employee_id" id="employeeSelect" required>
                                <option value="">Choose employee...</option>
                                @foreach($employees as $employee)
                                    @if(!in_array($employee->id, $scannedEmployees))
                                    <option value="{{ $employee->id }}"
                                            data-code="{{ $employee->employee_code }}"
                                            data-dept="{{ $employee->department->name ?? 'N/A' }}"
                                            data-unit="{{ $employee->unit->name ?? 'N/A' }}">
                                        {{ $employee->formal_name }} ({{ $employee->employee_code }})
                                    </option>
                                    @endif
                                @endforeach
                            </select>
                            <div id="employeeInfo" class="mt-2 text-sm text-gray-600"></div>
                        </div>

                        <!-- Vendor Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Vendor *
                            </label>
                            <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent transition duration-150"
                                    name="vendor_id" id="vendorSelect" required>
                                <option value="">Choose vendor...</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Meal Details -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <!-- Meal Time -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Meal Time *
                            </label>
                            <input type="time" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent"
                                   name="meal_time" id="mealTime" value="12:30" required>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Amount (KES)
                            </label>
                            <div class="relative">
                                <input type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent bg-gray-50"
                                       name="amount" id="amountInput" value="65.00" step="0.01" min="65" max="65" readonly>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-gray-500">KES</span>
                                </div>
                            </div>
                        </div>

                        <!-- Note -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Note (Optional)
                            </label>
                            <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent"
                                   name="note" id="noteInput" placeholder="e.g., Forgot card, new employee">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-3">
                        <button type="button" onclick="submitManualEntry()" id="submitBtn"
                                class="px-6 py-3 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition duration-150 font-medium flex items-center">
                            <i class="fas fa-save mr-2"></i>
                            Record Meal for Feb 2nd, 2026
                        </button>
                        <button type="button" onclick="resetForm()" id="resetBtn"
                                class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-150 font-medium">
                            Clear
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bulk Entry Form -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-text-black">Bulk Entry (Multiple Employees)</h2>
                    <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-yellow-500"></i>
                    </div>
                </div>

                <form id="bulkEntryForm">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Employee Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Employees
                            </label>
                            <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent h-64"
                                    name="employee_ids[]" id="bulkEmployeeSelect" multiple>
                                @foreach($employees as $employee)
                                    @if(!in_array($employee->id, $scannedEmployees))
                                    <option value="{{ $employee->id }}">
                                        {{ $employee->formal_name }} ({{ $employee->employee_code }})
                                    </option>
                                    @endif
                                @endforeach
                            </select>
                            <p class="mt-2 text-sm text-gray-500">
                                <i class="fas fa-mouse-pointer mr-1"></i>
                                Hold Ctrl/Cmd to select multiple employees
                            </p>
                        </div>

                        <!-- Bulk Settings -->
                        <div class="space-y-6">
                            <!-- Vendor Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Vendor *
                                </label>
                                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent"
                                        name="vendor_id" id="bulkVendorSelect" required>
                                    <option value="">Choose vendor...</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Meal Time -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Meal Time for All *
                                </label>
                                <input type="time" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent"
                                       name="meal_time" id="bulkMealTime" value="12:30" required>
                            </div>

                            <!-- Submit Button -->
                            <button type="button" onclick="submitBulkEntry()" id="bulkSubmitBtn"
                                    class="w-full px-6 py-3 bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 transition duration-150 font-medium flex items-center justify-center">
                                <i class="fas fa-users mr-2"></i>
                                Record Bulk Meals
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Stats & Actions -->
        <div class="space-y-8">
            <!-- Stats Card -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-6">February 2nd, 2026 Stats</h3>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <!-- Total Meals -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-secondary-blue">{{ $totalScans }}</div>
                        <div class="text-sm text-gray-600 mt-1">Total Meals</div>
                    </div>

                    <!-- Manual Entries -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">{{ $totalManual }}</div>
                        <div class="text-sm text-gray-600 mt-1">Manual Entries</div>
                    </div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 mr-2"></i>
                        <div>
                            <p class="text-sm text-yellow-800">
                                <strong class="font-semibold">Reminder:</strong> This tool is only for recording meals that should have been scanned on Monday, February 2nd, 2026. All entries will be logged with your admin ID.
                            </p>
                        </div>
                    </div>
                </div>

                <a href="{{ route('admin.meals.feb2-report') }}"
                   class="w-full px-4 py-3 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition duration-150 flex items-center justify-center font-medium">
                    <i class="fas fa-chart-bar mr-2"></i>
                    View Full Report
                </a>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-6">Quick Actions</h3>

                <div class="space-y-3">
                    <button onclick="showUnfedEmployees()"
                            class="w-full px-4 py-3 border border-purple-200 text-purple-700 rounded-lg hover:bg-purple-50 transition duration-150 flex items-center justify-center font-medium">
                        <i class="fas fa-search mr-2"></i>
                        Show Unfed Employees
                    </button>

                    <a href="/vendor/dashboard?date=2026-02-02" target="_blank"
                       class="block w-full px-4 py-3 border border-blue-200 text-blue-700 rounded-lg hover:bg-blue-50 transition duration-150 flex items-center justify-center font-medium">
                        <i class="fas fa-store mr-2"></i>
                        View Vendor Dashboard
                    </a>
                </div>
            </div>

            <!-- Recent Manual Entries -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-text-black mb-4">Recent Manual Entries</h3>
                <div id="recentEntries" class="text-center py-8 text-gray-500">
                    <i class="fas fa-history text-2xl mb-2"></i>
                    <p class="text-sm">Loading recent entries...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Results Modal -->
<div id="resultModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-6">
            <h3 id="modalTitle" class="text-xl font-bold text-text-black">Entry Result</h3>
            <button onclick="closeResultModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="mb-6" id="resultContent">
            <!-- Results will be shown here -->
        </div>
        <div class="flex justify-end space-x-3">
            <button onclick="closeResultModal()"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-150 font-medium">
                Close
            </button>
            <button onclick="location.reload()"
                    class="px-4 py-2 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition duration-150 font-medium">
                Refresh Page
            </button>
        </div>
    </div>
</div>

<script>
// Debug function
function showDebug(message) {
    console.log('DEBUG:', message);
    if (document.getElementById('debugMessage')) {
        document.getElementById('debugMessage').textContent = message;
        document.getElementById('debugAlert').classList.remove('hidden');
    }
}

// Check if jQuery is loaded
if (typeof jQuery === 'undefined') {
    showDebug('jQuery is not loaded! Using vanilla JavaScript.');
}

// Simple JavaScript functions
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');

    // Load recent entries
    loadRecentEntries();

    // Employee selection info
    const employeeSelect = document.getElementById('employeeSelect');
    if (employeeSelect) {
        employeeSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const employeeInfo = document.getElementById('employeeInfo');
            if (selected.value && employeeInfo) {
                employeeInfo.innerHTML = `
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
                            <i class="fas fa-id-card mr-1"></i>${selected.dataset.code}
                        </span>
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded">
                            <i class="fas fa-sitemap mr-1"></i>${selected.dataset.dept}
                        </span>
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded">
                            <i class="fas fa-building mr-1"></i>${selected.dataset.unit}
                        </span>
                    </div>
                `;
            } else if (employeeInfo) {
                employeeInfo.innerHTML = '';
            }
        });
    }
});

function submitManualEntry() {
    console.log('submitManualEntry called');

    const employeeId = document.getElementById('employeeSelect').value;
    const vendorId = document.getElementById('vendorSelect').value;

    if (!employeeId || !vendorId) {
        alert('Please select both employee and vendor.');
        return;
    }

    const btn = document.getElementById('submitBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

    // Collect form data
    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    formData.append('meal_date', '2026-02-02');
    formData.append('employee_id', employeeId);
    formData.append('vendor_id', vendorId);
    formData.append('meal_time', document.getElementById('mealTime').value);
    formData.append('amount', document.getElementById('amountInput').value);
    formData.append('note', document.getElementById('noteInput').value);

    console.log('Submitting form data...');

    fetch("{{ route('admin.meals.manual-entry.process') }}", {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        showResultModal(data);

        if (data.success) {
            setTimeout(() => {
                location.reload();
            }, 2000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showResultModal({
            success: false,
            message: 'Server error occurred. Please try again.'
        });
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function submitBulkEntry() {
    console.log('submitBulkEntry called');

    const selectedOptions = document.getElementById('bulkEmployeeSelect').selectedOptions;
    const selectedCount = selectedOptions.length;

    if (selectedCount === 0) {
        alert('Please select at least one employee.');
        return;
    }

    if (!confirm(`Are you sure you want to record meals for ${selectedCount} employees?`)) {
        return;
    }

    const btn = document.getElementById('bulkSubmitBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

    // Collect form data
    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);

    // Add selected employee IDs
    for (let option of selectedOptions) {
        formData.append('employee_ids[]', option.value);
    }

    formData.append('vendor_id', document.getElementById('bulkVendorSelect').value);
    formData.append('meal_time', document.getElementById('bulkMealTime').value);

    console.log('Submitting bulk form data...');

    fetch("{{ route('admin.meals.bulk-manual-entry') }}", {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Bulk response:', data);
        showResultModal(data);

        if (data.success) {
            setTimeout(() => {
                location.reload();
            }, 2000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showResultModal({
            success: false,
            message: 'Server error occurred. Please try again.'
        });
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function resetForm() {
    document.getElementById('manualEntryForm').reset();
    document.getElementById('employeeInfo').innerHTML = '';
}

function loadRecentEntries() {
    fetch("{{ route('admin.meals.recent-entries') }}?date=2026-02-02")
        .then(response => response.json())
        .then(data => {
            console.log('Recent entries:', data);
            const container = document.getElementById('recentEntries');

            if (data.success && data.entries.length > 0) {
                let html = '';
                data.entries.forEach(entry => {
                    html += `
                    <div class="border-b border-gray-100 pb-3 mb-3 last:border-0 last:pb-0 last:mb-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-text-black text-sm">${entry.employee_name}</p>
                                <div class="flex items-center space-x-2 text-xs text-gray-500 mt-1">
                                    <span>${entry.employee_code}</span>
                                    <span>•</span>
                                    <span>${entry.vendor}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-medium text-green-600">KES ${entry.amount}</span>
                                <p class="text-xs text-gray-500 mt-1">${entry.meal_time}</p>
                            </div>
                        </div>
                        ${entry.note ? `<p class="text-xs text-gray-600 mt-2 italic">"${entry.note}"</p>` : ''}
                    </div>`;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-history text-2xl mb-2"></i>
                        <p class="text-sm">No recent manual entries</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading recent entries:', error);
            document.getElementById('recentEntries').innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p class="text-sm">Failed to load recent entries</p>
                </div>
            `;
        });
}

function showResultModal(response) {
    console.log('Showing result modal:', response);
    const modal = document.getElementById('resultModal');
    const content = document.getElementById('resultContent');
    const title = document.getElementById('modalTitle');

    if (response.success) {
        title.textContent = 'Success!';
        if (response.success_count !== undefined) {
            // Bulk entry response
            const errorList = response.errors && response.errors.length > 0 ?
                `<div class="mt-4">
                    <details>
                        <summary class="text-sm font-medium text-gray-700 cursor-pointer hover:text-gray-900">
                            View ${response.errors.length} error${response.errors.length > 1 ? 's' : ''}
                        </summary>
                        <ul class="mt-2 space-y-1 text-sm text-red-600">
                            ${response.errors.map(error => `<li class="border-l-2 border-red-300 pl-2">${error}</li>`).join('')}
                        </ul>
                    </details>
                </div>` : '';

            content.innerHTML = `
                <div class="rounded-lg border ${response.failed_count > 0 ? 'border-yellow-200 bg-yellow-50' : 'border-green-200 bg-green-50'} p-4">
                    <div class="flex items-start">
                        <i class="fas ${response.failed_count > 0 ? 'fa-exclamation-triangle text-yellow-500' : 'fa-check-circle text-green-500'} text-lg mt-0.5 mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-gray-900">Bulk Entry Complete</h4>
                            <p class="text-sm text-gray-700 mt-1">${response.message}</p>
                            <div class="grid grid-cols-2 gap-4 mt-3">
                                <div class="text-center">
                                    <div class="text-xl font-bold text-green-600">${response.success_count}</div>
                                    <div class="text-xs text-gray-600">Successful</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-bold ${response.failed_count > 0 ? 'text-red-600' : 'text-gray-600'}">${response.failed_count}</div>
                                    <div class="text-xs text-gray-600">Failed</div>
                                </div>
                            </div>
                            ${errorList}
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Single entry response
            content.innerHTML = `
                <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                    <div class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 text-lg mt-0.5 mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-gray-900">Success!</h4>
                            <p class="text-sm text-gray-700 mt-1">${response.message}</p>
                            <div class="space-y-2 mt-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Transaction Code:</span>
                                    <span class="font-medium">${response.transaction.code}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Employee:</span>
                                    <span class="font-medium">${response.transaction.employee_name}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Time:</span>
                                    <span class="font-medium">${response.transaction.meal_time}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Amount:</span>
                                    <span class="font-medium text-green-600">KES ${response.transaction.amount}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    } else {
        title.textContent = 'Failed!';
        const errorDetails = response.errors ?
            `<div class="mt-3">
                <pre class="bg-gray-50 p-3 rounded text-xs overflow-auto max-h-32">${JSON.stringify(response.errors, null, 2)}</pre>
            </div>` : '';

        content.innerHTML = `
            <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                <div class="flex items-start">
                    <i class="fas fa-times-circle text-red-500 text-lg mt-0.5 mr-3"></i>
                    <div>
                        <h4 class="font-semibold text-gray-900">Failed!</h4>
                        <p class="text-sm text-red-700 mt-1">${response.message}</p>
                        ${errorDetails}
                    </div>
                </div>
            </div>
        `;
    }

    modal.classList.remove('hidden');
}

function closeResultModal() {
    document.getElementById('resultModal').classList.add('hidden');
}

function showUnfedEmployees() {
    fetch("{{ route('admin.meals.unfed-employees') }}?date=2026-02-02")
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                if (response.count > 0) {
                    let employeeList = response.employees.map(e =>
                        `• ${e.formal_name} (${e.employee_code}) - ${e.department}`
                    ).join('\n');

                    alert(`Unfed employees for Feb 2nd, 2026: ${response.count}\n\n${employeeList}`);
                } else {
                    alert('All employees have been fed for Feb 2nd, 2026!');
                }
            } else {
                alert('Failed to load unfed employees.');
            }
        })
        .catch(() => {
            alert('Error loading unfed employees.');
        });
}
</script>


@endsection
