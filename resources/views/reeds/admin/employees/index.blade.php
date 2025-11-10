@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-text-black">Employees</h1>
            <p class="text-gray-600 mt-2">Manage employee information and QR codes</p>
        </div>
        <button onclick="openCreateModal()" class="mt-4 md:mt-0 bg-primary-red text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#c22120] transition duration-300 shadow-md flex items-center space-x-2">
            <i class="fas fa-plus"></i>
            <span>Add Employee</span>
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Employees</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $employees->total() }}</p>
                </div>
                <div class="w-12 h-12 bg-primary-red bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-primary-red text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Employees</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $employees->where('is_active', true)->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">With QR Codes</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $employees->where('qr_code', '!=', null)->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-secondary-blue bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-qrcode text-secondary-blue text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Regular Staff</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $employees->where('employment_type', 'Regular')->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-tie text-purple-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Employees Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Designation</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Employment</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">QR Code</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($employees as $employee)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-text-black">{{ $employee->formal_name }}</div>
                                <div class="text-sm text-gray-500">{{ $employee->employee_code }}</div>
                                @if($employee->payroll_no)
                                <div class="text-xs text-gray-400">Payroll: {{ $employee->payroll_no }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $employee->department->name }}</div>
                            @if($employee->subDepartment)
                            <div class="text-xs text-gray-500">{{ $employee->subDepartment->name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $employee->designation ?? 'N/A' }}</div>
                            @if($employee->category)
                            <div class="text-xs text-gray-500">{{ $employee->category }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $employee->employment_type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($employee->qr_code)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check mr-1"></i> Generated
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-times mr-1"></i> Pending
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $employee->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $employee->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <button onclick="openEditModal({{ $employee }})" class="text-secondary-blue hover:text-[#1e7a9e] transition duration-150">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @if(!$employee->qr_code)
                                <button onclick="generateQrCode({{ $employee->id }})" class="text-green-600 hover:text-green-800 transition duration-150" title="Generate QR Code">
                                    <i class="fas fa-qrcode"></i>
                                </button>
                                @endif
                                <button onclick="toggleStatus({{ $employee->id }})" class="text-{{ $employee->is_active ? 'yellow' : 'green' }}-600 hover:text-{{ $employee->is_active ? 'yellow' : 'green' }}-800 transition duration-150">
                                    <i class="fas fa-{{ $employee->is_active ? 'pause' : 'play' }}"></i>
                                </button>
                                <button onclick="confirmDelete({{ $employee->id }}, '{{ $employee->formal_name }}')" class="text-primary-red hover:text-[#c22120] transition duration-150">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-3 text-gray-300"></i>
                            <p class="text-lg">No employees found</p>
                            <p class="text-sm mt-1">Get started by adding your first employee</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($employees->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $employees->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="employeeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modalTitle" class="text-xl font-bold text-text-black">Add Employee</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="employeeForm" class="space-y-4">
                @csrf
                <input type="hidden" id="employee_id" name="id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="employee_code" class="block text-sm font-medium text-gray-700 mb-1">Employee Code *</label>
                        <input type="text" id="employee_code" name="employee_code" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="e.g., EMP000464">
                        <div id="employee_code_error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>

                    <div>
                        <label for="payroll_no" class="block text-sm font-medium text-gray-700 mb-1">Payroll Number</label>
                        <input type="text" id="payroll_no" name="payroll_no"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="Enter payroll number">
                        <div id="payroll_no_error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
                        <select id="department_id" name="department_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black">
                            <option value="">Select Department</option>
                            @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <div id="department_id_error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>

                    <div>
                        <label for="sub_department_id" class="block text-sm font-medium text-gray-700 mb-1">Sub-Department</label>
                        <select id="sub_department_id" name="sub_department_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black">
                            <option value="">Select Sub-Department</option>
                            @foreach($subDepartments as $subDepartment)
                            <option value="{{ $subDepartment->id }}" data-department="{{ $subDepartment->department_id }}">{{ $subDepartment->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <select id="title" name="title"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black">
                            <option value="">Select Title</option>
                            <option value="Mr.">Mr.</option>
                            <option value="Mrs.">Mrs.</option>
                            <option value="Miss">Miss</option>
                            <option value="Dr.">Dr.</option>
                            <option value="Prof.">Prof.</option>
                        </select>
                    </div>

                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="Enter first name">
                        <div id="first_name_error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="Enter last name">
                        <div id="last_name_error" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>
                </div>

                <div>
                    <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                           placeholder="Enter middle name (optional)">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="employment_type" class="block text-sm font-medium text-gray-700 mb-1">Employment Type *</label>
                        <select id="employment_type" name="employment_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black">
                            <option value="Regular">Regular</option>
                            <option value="Contract">Contract</option>
                            <option value="Temporary">Temporary</option>
                            <option value="Intern">Intern</option>
                        </select>
                    </div>

                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <select id="gender" name="gender"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="designation" class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                        <input type="text" id="designation" name="designation"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="e.g., Mason, Welder">
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <input type="text" id="category" name="category"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                               placeholder="e.g., New ham, Omuford">
                    </div>
                </div>

                <div>
                    <label for="icard_number" class="block text-sm font-medium text-gray-700 mb-1">ICard Number</label>
                    <input type="text" id="icard_number" name="icard_number"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                           placeholder="Enter ICard number">
                    <div id="icard_number_error" class="text-red-500 text-xs mt-1 hidden"></div>
                </div>

                <div id="statusField" class="hidden">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" id="is_active" name="is_active" class="rounded border-gray-300 text-secondary-blue focus:ring-secondary-blue">
                        <span class="text-sm font-medium text-gray-700">Active Employee</span>
                    </label>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition duration-150">
                        Cancel
                    </button>
                    <button type="submit" id="submitBtn" class="bg-primary-red text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#c22120] transition duration-300 shadow-md">
                        Save Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Add Employee';
        document.getElementById('employeeForm').reset();
        document.getElementById('employee_id').value = '';
        document.getElementById('statusField').classList.add('hidden');
        document.getElementById('employeeModal').classList.remove('hidden');
        clearErrors();
        filterSubDepartments();
    }

    function openEditModal(employee) {
        document.getElementById('modalTitle').textContent = 'Edit Employee';
        document.getElementById('employee_id').value = employee.id;
        document.getElementById('employee_code').value = employee.employee_code;
        document.getElementById('payroll_no').value = employee.payroll_no || '';
        document.getElementById('department_id').value = employee.department_id;
        document.getElementById('sub_department_id').value = employee.sub_department_id || '';
        document.getElementById('title').value = employee.title || '';
        document.getElementById('first_name').value = employee.first_name;
        document.getElementById('middle_name').value = employee.middle_name || '';
        document.getElementById('last_name').value = employee.last_name;
        document.getElementById('employment_type').value = employee.employment_type;
        document.getElementById('gender').value = employee.gender || '';
        document.getElementById('designation').value = employee.designation || '';
        document.getElementById('category').value = employee.category || '';
        document.getElementById('icard_number').value = employee.icard_number || '';
        document.getElementById('is_active').checked = employee.is_active;
        document.getElementById('statusField').classList.remove('hidden');
        document.getElementById('employeeModal').classList.remove('hidden');
        clearErrors();
        filterSubDepartments();
    }

    function closeModal() {
        document.getElementById('employeeModal').classList.add('hidden');
        clearErrors();
    }

    function clearErrors() {
        document.querySelectorAll('[id$="_error"]').forEach(el => {
            el.classList.add('hidden');
            el.textContent = '';
        });
    }

    // Filter sub-departments based on selected department
    function filterSubDepartments() {
        const departmentId = document.getElementById('department_id').value;
        const subDepartmentSelect = document.getElementById('sub_department_id');
        const options = subDepartmentSelect.getElementsByTagName('option');

        for (let i = 1; i < options.length; i++) { // Start from 1 to skip "Select Sub-Department"
            const option = options[i];
            const optionDepartment = option.getAttribute('data-department');

            if (!departmentId || optionDepartment === departmentId) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
                if (option.selected) {
                    option.selected = false;
                }
            }
        }
    }

    // Add event listener for department change
    document.getElementById('department_id').addEventListener('change', filterSubDepartments);

    // Handle form submission
    // Handle form submission - FIXED VERSION
document.getElementById('employeeForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const employeeId = document.getElementById('employee_id').value;
    const url = employeeId ? `/admin/employees/${employeeId}` : '/admin/employees';
    const method = employeeId ? 'PUT' : 'POST';

    // Convert FormData to JSON for better handling
    const data = {};
    formData.forEach((value, key) => {
        // Handle boolean values properly
        if (key === 'is_active') {
            data[key] = value === 'on';
        } else if (value !== '') {
            data[key] = value;
        }
    });

    // Ensure sub_department_id is null if empty
    if (!data.sub_department_id) {
        data.sub_department_id = null;
    }

    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw err;
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeModal();
            showNotification('success', data.success);
            setTimeout(() => window.location.reload(), 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (error.errors) {
            // Display validation errors
            displayValidationErrors(error.errors);
        } else {
            showNotification('error', error.error || 'An error occurred. Please try again.');
        }
    });
});

// Function to display validation errors
function displayValidationErrors(errors) {
    clearErrors();

    Object.keys(errors).forEach(field => {
        const errorElement = document.getElementById(`${field}_error`);
        if (errorElement) {
            errorElement.textContent = errors[field][0];
            errorElement.classList.remove('hidden');

            // Highlight the problematic field
            const inputElement = document.getElementById(field);
            if (inputElement) {
                inputElement.classList.add('border-red-500');
            }
        }
    });
}

    function generateQrCode(employeeId) {
        if (confirm('Are you sure you want to generate QR code for this employee?')) {
            fetch(`/admin/employees/${employeeId}/generate-qr`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', data.success);
                    setTimeout(() => window.location.reload(), 1000);
                } else if (data.error) {
                    showNotification('error', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'An error occurred. Please try again.');
            });
        }
    }

    function toggleStatus(employeeId) {
        if (confirm('Are you sure you want to change the status of this employee?')) {
            fetch(`/admin/employees/${employeeId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', data.success);
                    setTimeout(() => window.location.reload(), 1000);
                } else if (data.error) {
                    showNotification('error', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'An error occurred. Please try again.');
            });
        }
    }

    function confirmDelete(employeeId, employeeName) {
        if (confirm(`Are you sure you want to delete "${employeeName}"? This action cannot be undone.`)) {
            fetch(`/admin/employees/${employeeId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', data.success);
                    setTimeout(() => window.location.reload(), 1000);
                } else if (data.error) {
                    showNotification('error', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'An error occurred. Please try again.');
            });
        }
    }

    function showNotification(type, message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.innerHTML = `
            <div class="flex items-center space-x-2">
                <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(notification);

        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Close modal when clicking outside
    document.getElementById('employeeModal').addEventListener('click', function(e) {
        if (e.target.id === 'employeeModal') {
            closeModal();
        }
    });
</script>
@endsection
