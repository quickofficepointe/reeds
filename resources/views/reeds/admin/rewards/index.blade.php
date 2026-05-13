{{-- resources/views/reeds/admin/rewards/index.blade.php --}}

@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Security Rewards Program</h1>
        <p class="text-gray-600">Award 200 KES security rewards to employees (one per unit per day)</p>
        <div class="flex items-center space-x-4 mt-2">
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                <i class="fas fa-circle mr-1 text-green-500 text-xs"></i> Regular Meal: 65 KES
            </span>
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                <i class="fas fa-star mr-1 text-purple-500 text-xs"></i> Reward Meal: 200 KES
            </span>
        </div>
    </div>

    <!-- Today's Rewards Cards -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Today's Security Rewards</h2>

        @if($todayRewards->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($todayRewards as $reward)
                <div class="bg-gradient-to-r from-yellow-500 to-orange-500 rounded-lg shadow-lg overflow-hidden">
                    <div class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-yellow-100 text-xs uppercase tracking-wide">{{ $reward->unit->name }}</div>
                                <div class="text-white text-xl font-bold mt-1">{{ $reward->employee->formal_name }}</div>
                                <div class="text-yellow-100 text-sm">{{ $reward->employee->employee_code }}</div>
                                <div class="mt-2 flex space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-white text-yellow-800">
                                        {{ ucfirst($reward->status) }}
                                    </span>
                                    @if($reward->sms_sent)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            SMS Sent
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-white text-3xl font-bold">{{ $reward->formatted_amount }}</div>
                                <div class="text-yellow-100 text-xs">Security Reward (200 KES)</div>
                                @if($reward->status == 'pending')
                                    <button onclick="resendSms({{ $reward->id }})" class="mt-2 text-white text-xs hover:underline">
                                        Resend SMS
                                    </button>
                                @endif
                            </div>
                        </div>
                        @if($reward->mealTransaction)
                            <div class="mt-3 pt-2 border-t border-yellow-400">
                                <div class="text-yellow-100 text-xs">Claimed via: {{ $reward->mealTransaction->vendor->name ?? 'Unknown Vendor' }}</div>
                                <div class="text-yellow-100 text-xs">Time: {{ $reward->mealTransaction->created_at->format('h:i A') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="bg-gray-50 rounded-lg p-8 text-center">
                <p class="text-gray-500">No rewards awarded for today</p>
            </div>
        @endif
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm">Total Rewards Issued</div>
            <div class="text-2xl font-bold">{{ $stats['total_rewards_issued'] }}</div>
            <div class="text-xs text-gray-400 mt-1">200 KES each</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm">Claimed</div>
            <div class="text-2xl font-bold text-green-600">{{ $stats['total_rewards_claimed'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Converted to meals</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm">Pending</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['total_rewards_pending'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Awaiting claim</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm">Total Distributed</div>
            <div class="text-2xl font-bold text-purple-600">KSh {{ number_format($stats['total_amount_distributed'], 2) }}</div>
            <div class="text-xs text-gray-400 mt-1">Value of claimed rewards</div>
        </div>
    </div>

    <!-- Award Single Reward -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Award Security Reward (200 KES)</h2>

        <form id="rewardTodayForm" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Unit</label>
                    <select name="unit_id" id="rewardUnit" class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200" required>
                        <option value="">-- Select Unit --</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" data-unit-name="{{ $unit->name }}">
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Employee</label>
                    <select name="employee_id" id="rewardEmployee" class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200" required disabled>
                        <option value="">-- First Select Unit --</option>
                    </select>
                </div>
            </div>
            <div class="bg-yellow-50 p-3 rounded-lg">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    Selected employee will receive a <strong class="font-bold">200 KES reward meal</strong> today (instead of regular 65 KES).
                    SMS will be sent immediately. Each unit can receive only one reward per day.
                </p>
            </div>
            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-6 rounded-lg transition">
                <i class="fas fa-gift mr-2"></i> Award 200 KES Reward
            </button>
        </form>
    </div>

    <!-- Bulk Award (Multiple Units) -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Bulk Award (Multiple Units)</h2>
        <p class="text-sm text-gray-600 mb-4">Award 200 KES reward meals to multiple units at once</p>

        <div id="bulkRewardsContainer">
            <div class="bulk-reward-item grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <select name="bulk_unit_id[]" class="bulk-unit-select w-full rounded-lg border-gray-300" required>
                    <option value="">-- Select Unit --</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <select name="bulk_employee_id[]" class="bulk-employee-select w-full rounded-lg border-gray-300" required disabled>
                        <option value="">-- First Select Unit --</option>
                    </select>
                    <button type="button" class="remove-bulk-item text-red-500 hover:text-red-700 px-3 py-2 border rounded-lg">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex space-x-3 mt-4">
            <button type="button" id="addBulkReward" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-plus mr-1"></i> Add Another Unit
            </button>
            <button type="button" id="submitBulkRewards" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                Award All Selected Units (200 KES Each)
            </button>
        </div>
    </div>

    <!-- Schedule Tomorrow's Reward -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Schedule Tomorrow's Reward (200 KES)</h2>

        @if($tomorrowReward)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-blue-800 font-medium">Reward Already Scheduled for Tomorrow</div>
                        <div class="text-blue-600">Employee: {{ $tomorrowReward->employee->formal_name }}</div>
                        <div class="text-blue-600 text-sm">Code: {{ $tomorrowReward->employee->employee_code }}</div>
                        <div class="text-blue-600 text-sm">Unit: {{ $tomorrowReward->unit->name ?? 'N/A' }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-blue-800 font-bold">{{ $tomorrowReward->formatted_amount }}</div>
                        <div class="text-blue-600 text-sm">200 KES Reward Meal</div>
                        <div class="text-blue-600 text-sm">{{ $tomorrowReward->reward_date->format('F j, Y') }}</div>
                        <button onclick="cancelReward({{ $tomorrowReward->id }})" class="mt-2 text-red-600 text-sm hover:underline">Cancel</button>
                    </div>
                </div>
            </div>
        @else
            <form id="rewardTomorrowForm" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Employee for Tomorrow's Reward</label>
                    <select name="employee_id" id="rewardTomorrowEmployee" class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                        <option value="">-- Select Employee --</option>
                        @foreach($availableEmployees as $employee)
                            <option value="{{ $employee->id }}" data-unit-id="{{ $employee->unit_id }}" data-unit-name="{{ $employee->unit->name ?? 'No Unit' }}">
                                {{ $employee->formal_name }} ({{ $employee->employee_code }}) - {{ $employee->unit->name ?? 'No Unit' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">SMS will be sent tonight at 10 PM. Employee will receive 200 KES reward meal tomorrow.</p>
                </div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-lg transition">
                    <i class="fas fa-calendar-plus mr-2"></i> Schedule 200 KES Reward for Tomorrow
                </button>
            </form>
        @endif
    </div>

    <!-- Rewards History Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Rewards History</h2>
            <a href="{{ route('admin.rewards.export') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded text-sm">
                <i class="fas fa-download mr-1"></i> Export CSV
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Claimed Via</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SMS</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Claimed At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($rewards as $reward)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $reward->reward_date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ $reward->unit->name ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $reward->employee->formal_name }}</div>
                            <div class="text-sm text-gray-500">{{ $reward->employee->employee_code }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-semibold text-purple-600">{{ $reward->formatted_amount }} 🎖️</span>
                            <div class="text-xs text-gray-400">Reward Meal (200 KES)</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ $reward->status === 'claimed' ? 'bg-green-100 text-green-800' :
                                   ($reward->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ ucfirst($reward->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($reward->mealTransaction)
                                <span class="text-green-600 text-sm">
                                    <i class="fas fa-qrcode mr-1"></i> QR Scan
                                </span>
                                <div class="text-xs text-gray-400">
                                    {{ $reward->mealTransaction->vendor->name ?? 'Unknown Vendor' }}
                                </div>
                            @else
                                <span class="text-gray-400 text-sm">Not claimed</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($reward->sms_sent)
                                <span class="text-green-600 text-xs"><i class="fas fa-check-circle mr-1"></i>Sent</span>
                            @else
                                <span class="text-red-600 text-xs"><i class="fas fa-exclamation-circle mr-1"></i>Failed</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $reward->mealTransaction?->created_at?->format('M d, Y H:i') ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($reward->status == 'pending')
                                <button onclick="resendSms({{ $reward->id }})" class="text-blue-600 hover:text-blue-800 mr-2" title="Resend SMS">
                                    <i class="fas fa-envelope"></i>
                                </button>
                                <button onclick="cancelReward({{ $reward->id }})" class="text-red-600 hover:text-red-800" title="Cancel Reward">
                                    <i class="fas fa-times"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-gift text-3xl mb-2 text-gray-300"></i>
                            <p>No rewards issued yet</p>
                            <p class="text-sm mt-1">Use the form above to award 200 KES security rewards</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $rewards->links() }}
        </div>
    </div>
</div>

<script>
// Load employees when unit is selected (Single Award)
document.getElementById('rewardUnit')?.addEventListener('change', async function() {
    const unitId = this.value;
    const employeeSelect = document.getElementById('rewardEmployee');

    if (!unitId) {
        employeeSelect.innerHTML = '<option value="">-- First Select Unit --</option>';
        employeeSelect.disabled = true;
        return;
    }

    employeeSelect.innerHTML = '<option value="">Loading employees...</option>';
    employeeSelect.disabled = true;

    try {
        const response = await fetch(`/admin/units/${unitId}/available-employees`);
        const data = await response.json();

        if (data.success && data.employees.length > 0) {
            employeeSelect.innerHTML = '<option value="">-- Select Employee --</option>' +
                data.employees.map(emp => `<option value="${emp.id}">${emp.name} (${emp.code}) - ${emp.department}</option>`).join('');
            employeeSelect.disabled = false;
        } else {
            employeeSelect.innerHTML = '<option value="">No employees available for this unit</option>';
        }
    } catch (error) {
        console.error('Error:', error);
        employeeSelect.innerHTML = '<option value="">Error loading employees</option>';
    }
});

// Single award form submission
document.getElementById('rewardTodayForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const unitId = document.getElementById('rewardUnit').value;
    const employeeId = document.getElementById('rewardEmployee').value;

    if (!unitId || !employeeId) {
        alert('Please select both unit and employee');
        return;
    }

    const button = e.target.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Awarding 200 KES...';
    button.disabled = true;

    try {
        const response = await fetch('{{ route("admin.rewards.reward-today") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ unit_id: unitId, employee_id: employeeId })
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('Failed to award reward. Please try again.');
    } finally {
        button.innerHTML = originalText;
        button.disabled = false;
    }
});

// Bulk award functionality
function attachBulkEventListeners(container) {
    const unitSelect = container.querySelector('.bulk-unit-select');
    const employeeSelect = container.querySelector('.bulk-employee-select');
    const removeBtn = container.querySelector('.remove-bulk-item');

    if (unitSelect) {
        unitSelect.addEventListener('change', async function() {
            const unitId = this.value;

            if (!unitId) {
                employeeSelect.innerHTML = '<option value="">-- First Select Unit --</option>';
                employeeSelect.disabled = true;
                return;
            }

            employeeSelect.innerHTML = '<option value="">Loading employees...</option>';
            employeeSelect.disabled = true;

            try {
                const response = await fetch(`/admin/units/${unitId}/available-employees`);
                const data = await response.json();

                if (data.success && data.employees.length > 0) {
                    employeeSelect.innerHTML = '<option value="">-- Select Employee --</option>' +
                        data.employees.map(emp => `<option value="${emp.id}">${emp.name} (${emp.code}) - ${emp.department}</option>`).join('');
                    employeeSelect.disabled = false;
                } else {
                    employeeSelect.innerHTML = '<option value="">No employees available</option>';
                }
            } catch (error) {
                employeeSelect.innerHTML = '<option value="">Error loading employees</option>';
            }
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            container.remove();
        });
    }
}

// Initialize existing bulk items
document.querySelectorAll('.bulk-reward-item').forEach(item => {
    attachBulkEventListeners(item);
});

// Add new bulk reward row
document.getElementById('addBulkReward')?.addEventListener('click', () => {
    const container = document.getElementById('bulkRewardsContainer');
    const newItem = document.createElement('div');
    newItem.className = 'bulk-reward-item grid grid-cols-1 md:grid-cols-2 gap-4 mb-4';
    newItem.innerHTML = `
        <select name="bulk_unit_id[]" class="bulk-unit-select w-full rounded-lg border-gray-300" required>
            <option value="">-- Select Unit --</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <select name="bulk_employee_id[]" class="bulk-employee-select w-full rounded-lg border-gray-300" required disabled>
                <option value="">-- First Select Unit --</option>
            </select>
            <button type="button" class="remove-bulk-item text-red-500 hover:text-red-700 px-3 py-2 border rounded-lg">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(newItem);
    attachBulkEventListeners(newItem);
});

// Submit bulk rewards
document.getElementById('submitBulkRewards')?.addEventListener('click', async () => {
    const unitSelects = document.querySelectorAll('.bulk-unit-select');
    const employeeSelects = document.querySelectorAll('.bulk-employee-select');

    const rewards = [];
    let hasError = false;

    for (let i = 0; i < unitSelects.length; i++) {
        const unitId = unitSelects[i].value;
        const employeeId = employeeSelects[i].value;

        if (!unitId || !employeeId) {
            alert(`Please complete row ${i + 1}`);
            hasError = true;
            break;
        }

        rewards.push({ unit_id: unitId, employee_id: employeeId });
    }

    if (hasError) return;
    if (rewards.length === 0) {
        alert('No rewards to submit');
        return;
    }

    const button = document.getElementById('submitBulkRewards');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Awarding 200 KES rewards...';
    button.disabled = true;

    try {
        const response = await fetch('{{ route("admin.rewards.multiple-units") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ rewards: rewards })
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'Failed to award some rewards');
        }
    } catch (error) {
        alert('Failed to award rewards. Please try again.');
    } finally {
        button.innerHTML = originalText;
        button.disabled = false;
    }
});

// Schedule tomorrow's reward
document.getElementById('rewardTomorrowForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const employeeId = document.getElementById('rewardTomorrowEmployee').value;

    if (!employeeId) {
        alert('Please select an employee');
        return;
    }

    const button = e.target.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Scheduling 200 KES reward...';
    button.disabled = true;

    try {
        const response = await fetch('{{ route("admin.rewards.schedule-tomorrow") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ employee_id: employeeId })
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('Failed to schedule reward. Please try again.');
    } finally {
        button.innerHTML = originalText;
        button.disabled = false;
    }
});

// Resend SMS
function resendSms(rewardId) {
    if (!confirm('Resend SMS notification for this 200 KES reward?')) return;

    fetch(`/admin/rewards/${rewardId}/resend-sms`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('SMS resent successfully!');
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Failed to resend SMS');
    });
}

// Cancel reward
function cancelReward(rewardId) {
    if (confirm('Are you sure you want to cancel this 200 KES reward? The employee will not receive the reward meal.')) {
        fetch(`/admin/rewards/${rewardId}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Reward cancelled successfully');
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            alert('Failed to cancel reward');
        });
    }
}
</script>
@endsection
