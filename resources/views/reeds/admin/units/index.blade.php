@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Units Management</h1>
        <p class="text-gray-600 mt-1">Manage organizational units and their capacities</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-building text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Units</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalUnits }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-check-circle text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Active Units</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $activeUnits }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-times-circle text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Inactive Units</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $inactiveUnits }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-users text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Capacity</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalCapacity }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions and Filters Card -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Units List</h2>
            <button onclick="showCreateModal()"
                    class="px-4 py-2 bg-primary-red text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-red">
                <i class="fas fa-plus mr-2"></i>Add New Unit
            </button>
        </div>

        <div class="p-4">
            <form id="filterForm" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent"
                           placeholder="Name, Code, Location...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent">
                        <option value="">All Status</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit" class="px-4 py-2 bg-primary-red text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-red">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                    <a href="{{ route('admin.units.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Units Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($units as $unit)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $unit->name }}</div>
                            @if($unit->description)
                            <div class="text-sm text-gray-500 truncate max-w-xs">{{ $unit->description }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-mono bg-gray-100 rounded">{{ $unit->code ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $unit->location ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {{ $unit->current_employee_count }}/{{ $unit->capacity ?? '∞' }}
                            </div>
                            @if($unit->capacity)
                            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                <div class="bg-primary-red h-1.5 rounded-full"
                                     style="width: {{ min(100, ($unit->current_employee_count / $unit->capacity) * 100) }}%"></div>
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $unit->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $unit->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="editUnit({{ $unit->id }})"
                                    class="text-secondary-blue hover:text-blue-700 mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="toggleStatus({{ $unit->id }}, {{ $unit->is_active ? 'true' : 'false' }})"
                                    class="text-yellow-600 hover:text-yellow-800 mr-3">
                                <i class="fas fa-power-off"></i> {{ $unit->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button onclick="deleteUnit({{ $unit->id }})"
                                    class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-building text-4xl mb-3"></i>
                            <p class="text-lg">No units found</p>
                            <p class="text-sm mt-2">Click "Add New Unit" to create your first unit</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($units->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $units->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Create Unit Modal -->
<div id="createUnitModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-bold text-gray-900">Add New Unit</h3>
            <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <form id="createUnitForm" class="py-4 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit Name *</label>
                <input type="text" name="name" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent"
                       placeholder="Enter unit name">
                <div class="text-red-500 text-xs mt-1" id="nameError"></div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit Code</label>
                <input type="text" name="code"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent"
                       placeholder="Optional unique code">
                <div class="text-red-500 text-xs mt-1" id="codeError"></div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <input type="text" name="location"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent"
                       placeholder="Enter location">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                <input type="number" name="capacity" min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent"
                       placeholder="Leave empty for unlimited">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent"
                          placeholder="Enter description (optional)"></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-primary-red text-white rounded-md hover:bg-red-700">
                    Create Unit
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Unit Modal -->
<div id="editUnitModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-bold text-gray-900">Edit Unit</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <form id="editUnitForm" class="py-4 space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="unit_id" id="editUnitId">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit Name *</label>
                <input type="text" name="name" id="editUnitName" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent">
                <div class="text-red-500 text-xs mt-1" id="editNameError"></div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit Code</label>
                <input type="text" name="code" id="editUnitCode"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent">
                <div class="text-red-500 text-xs mt-1" id="editCodeError"></div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <input type="text" name="location" id="editUnitLocation"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                <input type="number" name="capacity" id="editUnitCapacity" min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="editUnitDescription" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-red focus:border-transparent"></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-primary-red text-white rounded-md hover:bg-red-700">
                    Update Unit
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-lg font-bold text-gray-900">Confirm Delete</h3>
            <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="py-4">
            <p class="text-gray-700 mb-4">Are you sure you want to delete this unit? This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    Cancel
                </button>
                <button type="button" onclick="confirmDelete()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentUnitId = null;

// Create Modal Functions
function showCreateModal() {
    document.getElementById('createUnitModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createUnitModal').classList.add('hidden');
    clearCreateErrors();
}

// Edit Modal Functions
function editUnit(id) {
    currentUnitId = id;

    // Show loading
    const form = document.getElementById('editUnitForm');
    form.style.opacity = '0.5';

    fetch(`/admin/units/${id}/edit-modal`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const unit = data.unit;
                document.getElementById('editUnitId').value = unit.id;
                document.getElementById('editUnitName').value = unit.name;
                document.getElementById('editUnitCode').value = unit.code || '';
                document.getElementById('editUnitLocation').value = unit.location || '';
                document.getElementById('editUnitCapacity').value = unit.capacity || '';
                document.getElementById('editUnitDescription').value = unit.description || '';

                document.getElementById('editUnitModal').classList.remove('hidden');
            } else {
                alert('Failed to load unit data');
            }
            form.style.opacity = '1';
        })
        .catch(error => {
            alert('Error loading unit data');
            form.style.opacity = '1';
        });
}

function closeEditModal() {
    document.getElementById('editUnitModal').classList.add('hidden');
    clearEditErrors();
}

// Delete Modal Functions
function deleteUnit(id) {
    currentUnitId = id;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    currentUnitId = null;
}

async function confirmDelete() {
    try {
        const response = await fetch(`/admin/units/${currentUnitId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('Error deleting unit');
    }
    closeDeleteModal();
}

// Toggle Status
async function toggleStatus(id, isActive) {
    if (!confirm(`Are you sure you want to ${isActive ? 'deactivate' : 'activate'} this unit?`)) {
        return;
    }

    try {
        const response = await fetch(`/admin/units/${id}/toggle-status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('Error updating unit status');
    }
}

// Form Handling
function clearCreateErrors() {
    const errors = ['nameError', 'codeError'];
    errors.forEach(id => document.getElementById(id).innerHTML = '');
}

function clearEditErrors() {
    const errors = ['editNameError', 'editCodeError'];
    errors.forEach(id => document.getElementById(id).innerHTML = '');
}

// Create Unit Form
document.getElementById('createUnitForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    clearCreateErrors();

    try {
        const response = await fetch('{{ route("admin.units.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            closeCreateModal();
            location.reload();
        } else {
            // Display validation errors
            if (data.errors) {
                for (const [field, errors] of Object.entries(data.errors)) {
                    const errorElement = document.getElementById(`${field}Error`);
                    if (errorElement) {
                        errorElement.innerHTML = errors[0];
                    }
                }
            } else {
                alert(data.message);
            }
        }
    } catch (error) {
        alert('Error creating unit');
    }
});

// Edit Unit Form
document.getElementById('editUnitForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const unitId = document.getElementById('editUnitId').value;
    clearEditErrors();

    try {
        const response = await fetch(`/admin/units/${unitId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-HTTP-Method-Override': 'PUT'
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            closeEditModal();
            location.reload();
        } else {
            // Display validation errors
            if (data.errors) {
                for (const [field, errors] of Object.entries(data.errors)) {
                    const errorElement = document.getElementById(`edit${field.charAt(0).toUpperCase() + field.slice(1)}Error`);
                    if (errorElement) {
                        errorElement.innerHTML = errors[0];
                    }
                }
            } else {
                alert(data.message);
            }
        }
    } catch (error) {
        alert('Error updating unit');
    }
});

// Close modals when clicking outside
document.getElementById('createUnitModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateModal();
    }
});

document.getElementById('editUnitModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>
@endsection
