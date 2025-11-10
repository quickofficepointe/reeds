@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-text-black">Departments</h1>
            <p class="text-gray-600 mt-2">Manage employee departments and organizational structure</p>
        </div>
        <button onclick="openCreateModal()" class="mt-4 md:mt-0 bg-primary-red text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#c22120] transition duration-300 shadow-md flex items-center space-x-2">
            <i class="fas fa-plus"></i>
            <span>Add Department</span>
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Departments</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $departments->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-primary-red bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-building text-primary-red text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Departments</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $departments->where('is_active', true)->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Employees</p>
                    <p class="text-2xl font-bold text-text-black mt-2">{{ $departments->sum('employees_count') }}</p>
                </div>
                <div class="w-12 h-12 bg-secondary-blue bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-secondary-blue text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Departments Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Employees</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Sub-Depts</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($departments as $department)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-text-black">{{ $department->name }}</div>
                                @if($department->description)
                                <div class="text-sm text-gray-500 mt-1">{{ Str::limit($department->description, 50) }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $department->code ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $department->employees_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                {{ $department->sub_departments_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $department->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <button onclick="openEditModal({{ $department }})" class="text-secondary-blue hover:text-[#1e7a9e] transition duration-150">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="toggleStatus({{ $department->id }})" class="text-{{ $department->is_active ? 'yellow' : 'green' }}-600 hover:text-{{ $department->is_active ? 'yellow' : 'green' }}-800 transition duration-150">
                                    <i class="fas fa-{{ $department->is_active ? 'pause' : 'play' }}"></i>
                                </button>
                                <button onclick="confirmDelete({{ $department->id }}, '{{ $department->name }}')" class="text-primary-red hover:text-[#c22120] transition duration-150">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-building text-4xl mb-3 text-gray-300"></i>
                            <p class="text-lg">No departments found</p>
                            <p class="text-sm mt-1">Get started by creating your first department</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="departmentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modalTitle" class="text-xl font-bold text-text-black">Add Department</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="departmentForm" class="space-y-4">
                @csrf
                <input type="hidden" id="department_id" name="id">

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Department Name *</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                           placeholder="Enter department name">
                    <div id="name_error" class="text-red-500 text-xs mt-1 hidden"></div>
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Department Code</label>
                    <input type="text" id="code" name="code"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black"
                           placeholder="Enter department code (optional)">
                    <div id="code_error" class="text-red-500 text-xs mt-1 hidden"></div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary-blue focus:border-secondary-blue transition duration-150 text-text-black resize-none"
                              placeholder="Enter department description (optional)"></textarea>
                </div>

                <div id="statusField" class="hidden">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" id="is_active" name="is_active" class="rounded border-gray-300 text-secondary-blue focus:ring-secondary-blue">
                        <span class="text-sm font-medium text-gray-700">Active Department</span>
                    </label>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition duration-150">
                        Cancel
                    </button>
                    <button type="submit" id="submitBtn" class="bg-primary-red text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#c22120] transition duration-300 shadow-md">
                        Save Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let currentDepartmentId = null;

    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Add Department';
        document.getElementById('departmentForm').reset();
        document.getElementById('department_id').value = '';
        document.getElementById('statusField').classList.add('hidden');
        document.getElementById('departmentModal').classList.remove('hidden');
        clearErrors();
    }

    function openEditModal(department) {
        document.getElementById('modalTitle').textContent = 'Edit Department';
        document.getElementById('department_id').value = department.id;
        document.getElementById('name').value = department.name;
        document.getElementById('code').value = department.code || '';
        document.getElementById('description').value = department.description || '';
        document.getElementById('is_active').checked = department.is_active;
        document.getElementById('statusField').classList.remove('hidden');
        document.getElementById('departmentModal').classList.remove('hidden');
        clearErrors();
    }

    function closeModal() {
        document.getElementById('departmentModal').classList.add('hidden');
        clearErrors();
    }

    function clearErrors() {
        document.querySelectorAll('[id$="_error"]').forEach(el => {
            el.classList.add('hidden');
            el.textContent = '';
        });
    }

    // Handle form submission
   // Handle form submission - FIXED VERSION
document.getElementById('departmentForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const departmentId = document.getElementById('department_id').value;
    const url = departmentId ? `/admin/departments/${departmentId}` : '/admin/departments';
    const method = departmentId ? 'PUT' : 'POST';

    // Convert FormData to JSON
    const data = {};
    formData.forEach((value, key) => {
        if (key === 'is_active') {
            data[key] = value === 'on';
        } else if (value !== '') {
            data[key] = value;
        }
    });

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
            displayValidationErrors(error.errors);
        } else {
            showNotification('error', error.error || 'An error occurred. Please try again.');
        }
    });
});

    function toggleStatus(departmentId) {
        if (confirm('Are you sure you want to change the status of this department?')) {
            fetch(`/admin/departments/${departmentId}/toggle-status`, {
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

    function confirmDelete(departmentId, departmentName) {
        if (confirm(`Are you sure you want to delete "${departmentName}"? This action cannot be undone.`)) {
            fetch(`/admin/departments/${departmentId}`, {
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
    document.getElementById('departmentModal').addEventListener('click', function(e) {
        if (e.target.id === 'departmentModal') {
            closeModal();
        }
    });
</script>
@endsection
