@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-text-black">Manual Meal Entry & Management</h1>
                <p class="text-gray-600 mt-2">Record meals for any date | Manage and delete entries</p>
            </div>
            <div class="mt-4 md:mt-0">
                <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                    <i class="fas fa-calendar-check mr-1"></i> Any Date Allowed
                </span>
            </div>
        </div>
    </div>

    <!-- Date Selection -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center space-x-3">
                <i class="fas fa-calendar-alt text-secondary-blue text-xl"></i>
                <h3 class="font-medium text-text-black">Select Date:</h3>
                <input type="date" id="datePicker" value="{{ $selectedDate }}"
                       class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent">
                <button onclick="changeDate()"
                        class="px-4 py-2 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-search mr-1"></i> Load
                </button>
            </div>
            <div class="flex items-center space-x-2">
                <button onclick="goToPreviousDay()" class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button onclick="goToToday()" class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-calendar-day"></i> Today
                </button>
                <button onclick="goToNextDay()" class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Quick Stats for selected date -->
        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-secondary-blue" id="totalMeals">{{ $totalScans }}</div>
                    <div class="text-sm text-gray-600">Total Meals</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600" id="manualMeals">{{ $totalManual }}</div>
                    <div class="text-sm text-gray-600">Manual Entries</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600" id="regularMeals">{{ $totalScans - $totalManual }}</div>
                    <div class="text-sm text-gray-600">QR Scans</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600" id="totalAmount">KES {{ number_format($totalScans * 65, 2) }}</div>
                    <div class="text-sm text-gray-600">Total Amount</div>
                </div>
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
                                Record meals for employees for <strong id="selectedDateDisplay">{{ \Carbon\Carbon::parse($selectedDate)->format('l, F jS, Y') }}</strong>.
                                You can record for any past, present, or future date.
                            </p>
                        </div>
                    </div>
                </div>

                <form id="manualEntryForm">
                    @csrf
                    <input type="hidden" name="meal_date" id="mealDate" value="{{ $selectedDate }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Employee *
                            </label>
                            <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent"
                                    name="employee_id" id="employeeSelect" required>
                                <option value="">Choose employee...</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}"
                                            data-code="{{ $employee->employee_code }}"
                                            data-dept="{{ $employee->department->name ?? 'N/A' }}"
                                            data-unit="{{ $employee->unit->name ?? 'N/A' }}">
                                        {{ $employee->formal_name }} ({{ $employee->employee_code }})
                                    </option>
                                @endforeach
                            </select>
                            <div id="employeeInfo" class="mt-2 text-sm text-gray-600"></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Vendor *
                            </label>
                            <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue focus:border-transparent"
                                    name="vendor_id" id="vendorSelect" required>
                                <option value="">Choose vendor...</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Meal Time *
                            </label>
                            <input type="time" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue"
                                   name="meal_time" id="mealTime" value="12:30" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Amount (KES)
                            </label>
                            <input type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue"
                                   name="amount" id="amountInput" value="65.00" step="0.01" min="0" max="200">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Note (Optional)
                            </label>
                            <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary-blue"
                                   name="note" id="noteInput" placeholder="Reason for manual entry">
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <button type="button" onclick="submitManualEntry()" id="submitBtn"
                                class="px-6 py-3 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition font-medium flex items-center">
                            <i class="fas fa-save mr-2"></i>
                            Record Meal
                        </button>
                        <button type="button" onclick="resetForm()"
                                class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
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
                    <input type="hidden" name="meal_date" value="{{ $selectedDate }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Employees
                            </label>
                            <select class="w-full px-4 py-3 border border-gray-300 rounded-lg h-64"
                                    name="employee_ids[]" id="bulkEmployeeSelect" multiple>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">
                                        {{ $employee->formal_name }} ({{ $employee->employee_code }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-sm text-gray-500">
                                <i class="fas fa-mouse-pointer mr-1"></i>
                                Hold Ctrl/Cmd to select multiple employees
                            </p>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Vendor *
                                </label>
                                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg"
                                        name="vendor_id" id="bulkVendorSelect" required>
                                    <option value="">Choose vendor...</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Meal Time for All *
                                </label>
                                <input type="time" class="w-full px-4 py-3 border border-gray-300 rounded-lg"
                                       name="meal_time" id="bulkMealTime" value="12:30" required>
                            </div>

                            <button type="button" onclick="submitBulkEntry()" id="bulkSubmitBtn"
                                    class="w-full px-6 py-3 bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 transition font-medium flex items-center justify-center">
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
                <h3 class="text-lg font-bold text-text-black mb-4">Quick Actions</h3>

                <div class="space-y-3">
                    <button onclick="showUnfedEmployees()"
                            class="w-full px-4 py-3 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition font-medium flex items-center justify-center">
                        <i class="fas fa-search mr-2"></i>
                        Show Unfed Employees
                    </button>

                    <button onclick="openDeleteModal()"
                            class="w-full px-4 py-3 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition font-medium flex items-center justify-center">
                        <i class="fas fa-trash-alt mr-2"></i>
                        Delete Meal Entries
                    </button>

                    <button onclick="refreshData()"
                            class="w-full px-4 py-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition font-medium flex items-center justify-center">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Refresh Data
                    </button>
                </div>
            </div>

            <!-- Recent Entries -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-text-black">Recent Entries</h3>
                    <span class="text-xs text-gray-500" id="entryCount"></span>
                </div>
                <div id="recentEntries" class="max-h-96 overflow-y-auto">
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                        <p class="text-sm">Loading entries...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal - Updated to allow deletion of ANY entry type -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-text-black">Delete Meal Entries</h3>
            <button onclick="closeDeleteModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="mb-6">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-red-600 mt-0.5 mr-2"></i>
                    <div>
                        <p class="text-sm text-red-800 font-semibold">⚠️ WARNING: This action cannot be undone!</p>
                        <p class="text-sm text-red-700 mt-1">
                            You can delete BOTH manual entries AND QR scan entries. This is for removing fraudulent
                            scans where employees were not actually present. All deletions are logged with admin details and reason.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Deletion Reason -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Reason for Deletion <span class="text-red-500">*</span>
                </label>
                <select id="deletionReason" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                    <option value="">Select a reason...</option>
                    <option value="Employee not present - fraudulent scan">Employee not present - fraudulent scan</option>
                    <option value="Duplicate entry - accidental scan">Duplicate entry - accidental scan</option>
                    <option value="Wrong employee scanned">Wrong employee scanned</option>
                    <option value="Test entry - should not count">Test entry - should not count</option>
                    <option value="System error - incorrect recording">System error - incorrect recording</option>
                    <option value="Other">Other (please specify)</option>
                </select>
                <input type="text" id="customReason" placeholder="Please specify reason..."
                       class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 hidden">
            </div>

            <!-- Filter entries -->
            <div class="flex gap-2 mb-4">
                <button type="button" onclick="filterEntries('all')" class="filter-btn px-3 py-1 rounded-lg text-sm bg-blue-600 text-white">
                    All Entries
                </button>
                <button type="button" onclick="filterEntries('manual')" class="filter-btn px-3 py-1 rounded-lg text-sm bg-gray-200 text-gray-700">
                    Manual Only
                </button>
                <button type="button" onclick="filterEntries('qr')" class="filter-btn px-3 py-1 rounded-lg text-sm bg-gray-200 text-gray-700">
                    QR Scans Only
                </button>
            </div>

            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <input type="checkbox" id="selectAllEntries" onchange="toggleSelectAll()">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="deleteEntriesList" class="bg-white divide-y divide-gray-200">
                        <!-- Entries will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <button onclick="closeDeleteModal()"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                Cancel
            </button>
            <button onclick="confirmDeleteEntries()"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                <i class="fas fa-trash-alt mr-1"></i> Delete Selected
            </button>
        </div>
    </div>
</div>

<!-- Results Modal -->
<div id="resultModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-6">
            <h3 id="modalTitle" class="text-xl font-bold text-text-black">Result</h3>
            <button onclick="closeResultModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="mb-6" id="resultContent"></div>
        <div class="flex justify-end">
            <button onclick="closeResultModal()"
                    class="px-4 py-2 bg-secondary-blue text-white rounded-lg hover:bg-blue-600 transition font-medium">
                Close
            </button>
        </div>
    </div>
</div>

<script>
let currentDate = '{{ $selectedDate }}';
let allEntries = [];
let currentFilter = 'all';

document.addEventListener('DOMContentLoaded', function() {
    loadRecentEntries();
    updateDateDisplay();

    // Employee selection info
    document.getElementById('employeeSelect').addEventListener('change', function() {
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

    // Deletion reason dropdown handler
    const reasonSelect = document.getElementById('deletionReason');
    const customReasonInput = document.getElementById('customReason');

    reasonSelect.addEventListener('change', function() {
        if (this.value === 'Other') {
            customReasonInput.classList.remove('hidden');
            customReasonInput.required = true;
        } else {
            customReasonInput.classList.add('hidden');
            customReasonInput.required = false;
            customReasonInput.value = '';
        }
    });
});

function changeDate() {
    const newDate = document.getElementById('datePicker').value;
    if (newDate) {
        window.location.href = `{{ route('admin.meals.manual-entry') }}?date=${newDate}`;
    }
}

function goToPreviousDay() {
    const currentDate = new Date(document.getElementById('datePicker').value);
    currentDate.setDate(currentDate.getDate() - 1);
    const newDate = currentDate.toISOString().split('T')[0];
    window.location.href = `{{ route('admin.meals.manual-entry') }}?date=${newDate}`;
}

function goToNextDay() {
    const currentDate = new Date(document.getElementById('datePicker').value);
    currentDate.setDate(currentDate.getDate() + 1);
    const newDate = currentDate.toISOString().split('T')[0];
    window.location.href = `{{ route('admin.meals.manual-entry') }}?date=${newDate}`;
}

function goToToday() {
    const today = new Date().toISOString().split('T')[0];
    window.location.href = `{{ route('admin.meals.manual-entry') }}?date=${today}`;
}

function updateDateDisplay() {
    const datePicker = document.getElementById('datePicker');
    const selectedDateDisplay = document.getElementById('selectedDateDisplay');
    const mealDate = document.getElementById('mealDate');

    if (datePicker && selectedDateDisplay) {
        const date = new Date(datePicker.value);
        selectedDateDisplay.textContent = date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        if (mealDate) mealDate.value = datePicker.value;
    }
}

function submitManualEntry() {
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

    const formData = new FormData(document.getElementById('manualEntryForm'));

    fetch("{{ route('admin.meals.manual-entry.process') }}", {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        showResultModal(data);
        if (data.success) {
            setTimeout(() => {
                location.reload();
            }, 2000);
        }
    })
    .catch(error => {
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

    const formData = new FormData(document.getElementById('bulkEntryForm'));

    fetch("{{ route('admin.meals.bulk-manual-entry') }}", {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        showResultModal(data);
        if (data.success) {
            setTimeout(() => {
                location.reload();
            }, 2000);
        }
    })
    .catch(error => {
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
    document.getElementById('mealTime').value = '12:30';
    document.getElementById('amountInput').value = '65.00';
}

function loadRecentEntries() {
    const date = document.getElementById('datePicker').value;

    console.log('Loading recent entries for date:', date);

    fetch(`{{ route('admin.meals.recent-entries') }}?date=${date}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Recent entries response:', data);
            const container = document.getElementById('recentEntries');
            const entryCount = document.getElementById('entryCount');

            if (entryCount) {
                entryCount.textContent = `${data.entries.length} entries`;
            }

            if (data.success && data.entries && data.entries.length > 0) {
                let html = '';
                data.entries.forEach(entry => {
                    const isManual = entry.is_manual;
                    const badge = isManual ?
                        '<span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">Manual</span>' :
                        '<span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">QR Scan</span>';

                    html += `
                    <div class="border-b border-gray-100 pb-3 mb-3 last:border-0 last:pb-0 last:mb-0">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="font-medium text-text-black text-sm">${escapeHtml(entry.employee_name)}</p>
                                    ${badge}
                                </div>
                                <div class="flex items-center space-x-2 text-xs text-gray-500">
                                    <span>${escapeHtml(entry.employee_code)}</span>
                                    <span>•</span>
                                    <span>${escapeHtml(entry.vendor)}</span>
                                </div>
                                ${entry.note ? `<p class="text-xs text-gray-600 mt-1 italic">"${escapeHtml(entry.note)}"</p>` : ''}
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-xs text-gray-400">${entry.meal_time}</span>
                                    <span class="text-xs font-medium text-green-600">KES ${entry.amount}</span>
                                </div>
                            </div>
                            <button onclick="deleteSingleEntry(${entry.id})"
                                    class="ml-2 text-red-500 hover:text-red-700">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>`;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-history text-2xl mb-2"></i>
                        <p class="text-sm">No entries for this date</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading entries:', error);
            document.getElementById('recentEntries').innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p class="text-sm">Failed to load entries. Please check console for errors.</p>
                </div>
            `;
        });
}

// Add this helper function for security
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function filterEntries(type) {
    currentFilter = type;

    // Update button styles
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('bg-blue-600', 'text-white');
        btn.classList.add('bg-gray-200', 'text-gray-700');
    });
    event.target.classList.remove('bg-gray-200', 'text-gray-700');
    event.target.classList.add('bg-blue-600', 'text-white');

    let filteredEntries = allEntries;
    if (type === 'manual') {
        filteredEntries = allEntries.filter(entry => entry.entry_type === 'Manual Entry');
    } else if (type === 'qr') {
        filteredEntries = allEntries.filter(entry => entry.entry_type === 'QR Scan');
    }

    renderDeleteEntriesTable(filteredEntries);
}

function renderDeleteEntriesTable(entries) {
    const tbody = document.getElementById('deleteEntriesList');
    if (entries.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                    No entries found for this filter
                </td>
            </tr>
        `;
    } else {
        tbody.innerHTML = entries.map(entry => `
            <tr class="${entry.entry_type === 'QR Scan' ? 'bg-yellow-50' : ''}">
                <td class="px-4 py-3">
                    <input type="checkbox" class="entry-checkbox" value="${entry.id}" data-type="${entry.entry_type}">
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-1 rounded ${entry.entry_type === 'Manual Entry' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'}">
                        ${entry.entry_type}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm">
                    <div class="font-medium">${entry.employee_name}</div>
                    <div class="text-xs text-gray-500">${entry.employee_code}</div>
                </td>
                <td class="px-4 py-3 text-sm">${entry.vendor_name || entry.vendor}</td>
                <td class="px-4 py-3 text-sm">${entry.meal_date}</td>
                <td class="px-4 py-3 text-sm">${entry.meal_time}</td>
                <td class="px-4 py-3 text-sm font-medium text-green-600">KES ${entry.amount}</td>
            </tr>
        `).join('');
    }
}

function openDeleteModal() {
    const date = document.getElementById('datePicker').value;
    const modal = document.getElementById('deleteModal');

    console.log('Fetching entries for date:', date);

    // Use the working recent-entries endpoint instead
    fetch(`{{ route('admin.meals.recent-entries') }}?date=${date}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Data received:', data);

            if (data.success && data.entries && data.entries.length > 0) {
                console.log(`Found ${data.entries.length} entries`);

                // Map the entries to the format expected by the delete modal
                allEntries = data.entries.map(entry => ({
                    id: entry.id,
                    employee_name: entry.employee_name,
                    employee_code: entry.employee_code,
                    vendor_name: entry.vendor,
                    meal_date: entry.meal_date,
                    meal_time: entry.meal_time,
                    amount: entry.amount,
                    entry_type: entry.is_manual ? 'Manual Entry' : 'QR Scan',
                    transaction_code: entry.transaction_code
                }));

                renderDeleteEntriesTable(allEntries);
                modal.classList.remove('hidden');
            } else {
                alert(`No entries found for ${date}. Total entries: ${data.entries ? data.entries.length : 0}`);
            }
        })
        .catch(error => {
            console.error('Error loading entries:', error);
            alert('Failed to load entries for deletion: ' + error.message);
        });
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deletionReason').value = '';
    document.getElementById('customReason').classList.add('hidden');
    document.getElementById('selectAllEntries').checked = false;
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAllEntries');
    const checkboxes = document.querySelectorAll('.entry-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}

function getDeletionReason() {
    const reasonSelect = document.getElementById('deletionReason');
    const customReason = document.getElementById('customReason');

    if (reasonSelect.value === 'Other') {
        return customReason.value.trim() || 'Other reason provided';
    }
    return reasonSelect.value;
}

function confirmDeleteEntries() {
    const selectedCheckboxes = document.querySelectorAll('.entry-checkbox:checked');
    const selectedIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));
    const reason = getDeletionReason();

    if (selectedIds.length === 0) {
        alert('Please select at least one entry to delete.');
        return;
    }

    if (!reason) {
        alert('Please select a reason for deletion.');
        return;
    }

    const manualCount = Array.from(selectedCheckboxes).filter(cb => cb.dataset.type === 'Manual Entry').length;
    const qrCount = selectedIds.length - manualCount;

    if (!confirm(`⚠️ WARNING: You are about to delete ${selectedIds.length} meal entry/entries (${manualCount} Manual, ${qrCount} QR Scans).\n\nReason: ${reason}\n\nThis action CANNOT be undone!\n\nAre you absolutely sure?`)) {
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Deleting...';

    fetch("{{ route('admin.meals.bulk-delete') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            transaction_ids: selectedIds,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        showResultModal({
            success: data.success,
            message: data.message
        });
        closeDeleteModal();
        setTimeout(() => {
            location.reload();
        }, 2000);
    })
    .catch(error => {
        showResultModal({
            success: false,
            message: 'Failed to delete entries: ' + error.message
        });
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function deleteSingleEntry(entryId) {
    const reason = prompt('Please provide a reason for deleting this entry:', 'Employee not present - fraudulent scan');

    if (!reason) {
        alert('Deletion cancelled. Reason is required.');
        return;
    }

    if (!confirm(`⚠️ WARNING: This entry will be permanently deleted.\n\nReason: ${reason}\n\nThis action cannot be undone!\n\nAre you sure?`)) {
        return;
    }

    fetch(`/admin/meals/entry/${entryId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ reason: reason })
    })
    .then(response => response.json())
    .then(data => {
        showResultModal(data);
        if (data.success) {
            setTimeout(() => {
                location.reload();
            }, 1500);
        }
    })
    .catch(error => {
        showResultModal({
            success: false,
            message: 'Failed to delete entry: ' + error.message
        });
    });
}

function showUnfedEmployees() {
    const date = document.getElementById('datePicker').value;

    fetch(`{{ route('admin.meals.unfed-employees') }}?date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.count > 0) {
                    let employeeList = data.employees.map(e =>
                        `• ${e.formal_name} (${e.employee_code}) - ${e.department}`
                    ).join('\n');
                    alert(`Unfed employees (${data.count}):\n\n${employeeList}`);
                } else {
                    alert('All employees have been fed for this date!');
                }
            } else {
                alert('Failed to load unfed employees.');
            }
        })
        .catch(() => {
            alert('Error loading unfed employees.');
        });
}

function refreshData() {
    loadRecentEntries();
    location.reload();
}

function showResultModal(response) {
    const modal = document.getElementById('resultModal');
    const content = document.getElementById('resultContent');
    const title = document.getElementById('modalTitle');

    if (response.success) {
        title.textContent = 'Success!';
        let detailsHtml = '';
        if (response.deleted_count !== undefined) {
            detailsHtml = `
                <div class="mt-3 pt-3 border-t border-green-200">
                    <p class="text-sm text-gray-600">Deleted: ${response.deleted_count} entries</p>
                    ${response.reason ? `<p class="text-xs text-gray-500 mt-1">Reason: ${response.reason}</p>` : ''}
                </div>
            `;
        }
        content.innerHTML = `
            <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 text-lg mt-0.5 mr-3"></i>
                    <div>
                        <h4 class="font-semibold text-gray-900">Success!</h4>
                        <p class="text-sm text-gray-700 mt-1">${response.message}</p>
                        ${detailsHtml}
                    </div>
                </div>
            </div>
        `;
    } else {
        title.textContent = 'Failed!';
        content.innerHTML = `
            <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                <div class="flex items-start">
                    <i class="fas fa-times-circle text-red-500 text-lg mt-0.5 mr-3"></i>
                    <div>
                        <h4 class="font-semibold text-gray-900">Failed!</h4>
                        <p class="text-sm text-red-700 mt-1">${response.message}</p>
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
</script>

@endsection
