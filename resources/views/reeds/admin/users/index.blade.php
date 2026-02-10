@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="p-4 md:p-6">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">User Management</h1>
                <p class="text-gray-600 mt-1">Manage all registered users, assign units, and update permissions</p>
            </div>
            <div class="mt-4 md:mt-0">
                <button onclick="showAddUserModal()"
                        class="bg-primary-red hover:bg-red-700 text-white font-medium py-2.5 px-5 rounded-lg transition duration-200 flex items-center">
                    <i class="fas fa-user-plus mr-2"></i>
                    Add New User
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-lg bg-blue-100 text-blue-600">
                <i class="fas fa-users text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-600">Total Users</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['totalUsers'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-lg bg-green-100 text-green-600">
                <i class="fas fa-user-shield text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-600">Administrators</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['adminCount'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-lg bg-orange-100 text-orange-600">
                <i class="fas fa-store text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-600">Vendors</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['vendorCount'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-lg bg-red-100 text-red-600">
                <i class="fas fa-building text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-600">Unassigned Users</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['unassignedCount'] }}</p>
            </div>
        </div>
    </div>
</div>

        <!-- Filters & Search -->
        <div class="bg-white rounded-xl shadow mb-6">
            <div class="p-4 border-b border-gray-200">
                <div class="flex flex-col md:flex-row md:items-center justify-between">
                    <div class="flex-1 md:mr-4">
                        <div class="relative">
                            <input type="text"
                                   id="searchInput"
                                   placeholder="Search by name, email, or role..."
                                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-3 md:mt-0">
                        <select id="roleFilter"
                                class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                            <option value="">All Roles</option>
                            <option value="1">Administrator</option>
                            <option value="2">Vendor</option>
                        </select>
                        <select id="unitFilter"
                                class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                            <option value="">All Units</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                       <select id="statusFilter"
        class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
    <option value="">All Status</option>
    <option value="active">Verified</option>
    <option value="inactive">Unverified</option>
    <option value="unassigned">Unassigned Unit</option>
</select>
                        <button onclick="resetFilters()"
                                class="px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-redo mr-1"></i> Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
      <!-- Users Table -->
<div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">User</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Email</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Role</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Unit</th>
              <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">
    Status
</th>

</td>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Registered</th>
                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Actions</th>
            </tr>
        </thead>

                    <tbody id="usersTableBody">
                        @foreach($users as $user)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition" data-user-id="{{ $user->id }}">
                            <td class="py-3 px-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-primary-red bg-opacity-10 flex items-center justify-center mr-3">
                                        @if($user->profile && $user->profile->photo)
                                            <img src="{{ Storage::url($user->profile->photo) }}"
                                                 alt="{{ $user->name }}"
                                                 class="w-10 h-10 rounded-full object-cover">
                                        @else
                                            <i class="fas fa-user text-primary-red"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800">{{ $user->name }}</p>
                                        @if($user->profile && $user->profile->phone)
                                            <p class="text-sm text-gray-500">{{ $user->profile->phone }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <p class="text-gray-700">{{ $user->email }}</p>
                                @if($user->email_verified_at)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle text-xs mr-1"></i> Verified
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock text-xs mr-1"></i> Pending
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    {{ $user->role == 1 ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">
                                    <i class="fas {{ $user->role == 1 ? 'fa-user-shield' : 'fa-store' }} mr-1"></i>
                                    {{ $user->getRoleName() }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @if($user->unit)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                        <i class="fas fa-building mr-1"></i>
                                        {{ $user->unit->name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        Unassigned
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($user->email_verified_at)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Verified
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock mr-1"></i>
                                        Pending
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-2">
                                    <button onclick="showEditUserModal({{ $user->id }})"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                            title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="showAssignUnitModal({{ $user->id }})"
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition"
                                            title="Assign Unit">
                                        <i class="fas fa-building"></i>
                                    </button>
                                    <button onclick="showResetPasswordModal({{ $user->id }})"
                                            class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition"
                                            title="Reset Password">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button onclick="confirmDelete({{ $user->id }})"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                                            title="Delete User">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
            <div class="p-4 border-t border-gray-200">
                {{ $users->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">Add New User</h3>
                <button onclick="closeAddUserModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="addUserForm" action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text"
                               name="name"
                               required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email"
                               name="email"
                               required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select name="role"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                            <option value="1">Administrator</option>
                            <option value="2">Vendor</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Assign Unit</label>
                        <select name="unit_id"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                            <option value="">Select a Unit (Optional)</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password"
                               name="password"
                               required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                        <input type="password"
                               name="password_confirmation"
                               required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                    </div>
                </div>
                <div class="mt-8 flex justify-end space-x-3">
                    <button type="button"
                            onclick="closeAddUserModal()"
                            class="px-5 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 bg-primary-red text-white rounded-lg hover:bg-red-700 transition font-medium">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <!-- Modal content will be loaded via AJAX -->
</div>

<!-- Assign Unit Modal -->
<div id="assignUnitModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <!-- Modal content will be loaded via AJAX -->
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <!-- Modal content will be loaded via AJAX -->
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="p-6">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Delete User</h3>
                <p class="text-sm text-gray-500 mb-6">
                    Are you sure you want to delete this user? This action cannot be undone.
                </p>
            </div>
            <div class="flex justify-center space-x-3">
                <button onclick="closeDeleteConfirmation()"
                        type="button"
                        class="px-5 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <form id="deleteUserForm" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                        Delete User
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    // Modal Functions
    function showAddUserModal() {
        document.getElementById('addUserModal').classList.remove('hidden');
    }

    function closeAddUserModal() {
        document.getElementById('addUserModal').classList.add('hidden');
    }

    function showEditUserModal(userId) {
        fetch(`/admin/users/${userId}/edit`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('editUserModal').innerHTML = html;
                document.getElementById('editUserModal').classList.remove('hidden');
            });
    }

    function closeEditUserModal() {
        document.getElementById('editUserModal').classList.add('hidden');
    }

    function showAssignUnitModal(userId) {
        fetch(`/admin/users/${userId}/assign-unit`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('assignUnitModal').innerHTML = html;
                document.getElementById('assignUnitModal').classList.remove('hidden');
            });
    }

    function closeAssignUnitModal() {
        document.getElementById('assignUnitModal').classList.add('hidden');
    }

    function showResetPasswordModal(userId) {
        fetch(`/admin/users/${userId}/reset-password`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('resetPasswordModal').innerHTML = html;
                document.getElementById('resetPasswordModal').classList.remove('hidden');
            });
    }

    function closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').classList.add('hidden');
    }

    // Delete Functions
    let userToDelete = null;

    function confirmDelete(userId) {
        userToDelete = userId;
        document.getElementById('deleteConfirmationModal').classList.remove('hidden');
        document.getElementById('deleteUserForm').action = `/admin/users/${userId}`;
    }

    function closeDeleteConfirmation() {
        document.getElementById('deleteConfirmationModal').classList.add('hidden');
        userToDelete = null;
    }

    // Filter Functions
    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('roleFilter').value = '';
        document.getElementById('unitFilter').value = '';
        document.getElementById('statusFilter').value = '';
        filterUsers();
    }

    // Live Search and Filter
    document.getElementById('searchInput').addEventListener('input', filterUsers);
    document.getElementById('roleFilter').addEventListener('change', filterUsers);
    document.getElementById('unitFilter').addEventListener('change', filterUsers);
    document.getElementById('statusFilter').addEventListener('change', filterUsers);

    function filterUsers() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const roleFilter = document.getElementById('roleFilter').value;
        const unitFilter = document.getElementById('unitFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;

        const rows = document.querySelectorAll('#usersTableBody tr');

        rows.forEach(row => {
            const name = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
            const email = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const role = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const unit = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
            const status = row.querySelector('td:nth-child(5)').textContent.toLowerCase();

            let show = true;

            // Search filter
            if (searchTerm && !name.includes(searchTerm) && !email.includes(searchTerm)) {
                show = false;
            }

            // Role filter
            if (roleFilter) {
                const roleValue = role.includes('admin') ? '1' : '2';
                if (roleValue !== roleFilter) {
                    show = false;
                }
            }

            // Unit filter
            if (unitFilter) {
                const unitId = row.getAttribute('data-unit-id');
                if (unitId !== unitFilter) {
                    show = false;
                }
            }

            // Status filter
          // Status filter
if (statusFilter === 'active' && !status.includes('verified')) {
    show = false;
} else if (statusFilter === 'inactive' && !status.includes('pending')) {
    show = false;
} else if (statusFilter === 'unassigned' && !unit.includes('unassigned')) {
    show = false;
}

            row.style.display = show ? '' : 'none';
        });
    }

    // Close modals on outside click
    document.addEventListener('click', function(event) {
        const addModal = document.getElementById('addUserModal');
        const editModal = document.getElementById('editUserModal');
        const assignModal = document.getElementById('assignUnitModal');
        const resetModal = document.getElementById('resetPasswordModal');
        const deleteModal = document.getElementById('deleteConfirmationModal');

        if (event.target === addModal) closeAddUserModal();
        if (event.target === editModal) closeEditUserModal();
        if (event.target === assignModal) closeAssignUnitModal();
        if (event.target === resetModal) closeResetPasswordModal();
        if (event.target === deleteModal) closeDeleteConfirmation();
    });

    // Success message handling
    @if(session('success'))
        showNotification('{{ session('success') }}', 'success');
    @endif

    @if(session('error'))
        showNotification('{{ session('error') }}', 'error');
    @endif

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
