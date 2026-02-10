<div class="bg-white rounded-xl shadow-lg w-full max-w-md">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-800">Reset Password</h3>
            <button onclick="closeResetPasswordModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="resetPasswordForm" action="{{ route('admin.users.reset-password', $user) }}" method="POST">
            @csrf
            <div class="mb-4">
                <p class="text-sm text-gray-600">Resetting password for: <strong>{{ $user->name }}</strong></p>
                <p class="text-xs text-gray-500 mt-1">User will need to use this new password to login.</p>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password"
                           name="password"
                           required
                           minlength="8"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                    <input type="password"
                           name="password_confirmation"
                           required
                           minlength="8"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-red focus:border-transparent">
                </div>
            </div>
            <div class="mt-8 flex justify-end space-x-3">
                <button type="button"
                        onclick="closeResetPasswordModal()"
                        class="px-5 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2.5 bg-primary-red text-white rounded-lg hover:bg-red-700 transition font-medium">
                    Reset Password
                </button>
            </div>
        </form>
    </div>
</div>
