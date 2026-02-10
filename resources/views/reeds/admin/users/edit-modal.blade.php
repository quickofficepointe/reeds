<div class="bg-white rounded-xl shadow-lg w-full max-w-md">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-800">Edit User: {{ $user->name }}</h3>
            <button onclick="closeEditUserModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editUserForm" action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input type="text"
                           name="name"
                           value="{{ $user->name }}"
                           required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <input type="email"
                           name="email"
                           value="{{ $user->email }}"
                           required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select name="role"
                            required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                        <option value="1" {{ $user->role == 1 ? 'selected' : '' }}>Administrator</option>
                        <option value="2" {{ $user->role == 2 ? 'selected' : '' }}>Vendor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assign Unit</label>
                    <select name="unit_id"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                        <option value="">Select a Unit (Optional)</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ $user->unit_id == $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
               <div>
    <label class="inline-flex items-center">
        <input type="checkbox"
               name="verify_email"
               value="1"
               {{ $user->email_verified_at ? 'checked' : '' }}
               class="rounded border-gray-300 text-primary-red focus:ring-primary-red">
        <span class="ml-2 text-sm text-gray-700">Email Verified</span>
    </label>
</div>
            </div>
            <div class="mt-8 flex justify-end space-x-3">
                <button type="button"
                        onclick="closeEditUserModal()"
                        class="px-5 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2.5 bg-primary-red text-white rounded-lg hover:bg-red-700 transition font-medium">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>
