@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Security Rewards Program</h1>
        <p class="text-gray-600">Manually award 200 KES security reward to employees</p>
    </div>

    <!-- Today's Reward Card -->
    <div class="bg-gradient-to-r from-yellow-500 to-orange-500 rounded-lg shadow-lg mb-8 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-yellow-100 text-sm uppercase tracking-wide">Today's Security Reward</div>
                    <div class="text-white text-3xl font-bold mt-2">
                        {{ $todayReward?->employee?->formal_name ?? 'Not Awarded Yet' }}
                    </div>
                    <div class="text-yellow-100 mt-1">
                        {{ $todayReward?->employee?->employee_code ?? 'No reward for today' }}
                    </div>
                    @if($todayReward)
                        <div class="mt-3 flex space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white text-yellow-800">
                                {{ ucfirst($todayReward->status) }}
                            </span>
                            @if($todayReward->sms_sent)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    SMS Sent
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="text-right">
                    <div class="text-white text-4xl font-bold">200 KES</div>
                    <div class="text-yellow-100 text-sm">Security Reward</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm">Total Rewards Issued</div>
            <div class="text-2xl font-bold">{{ $stats['total_rewards_issued'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm">Claimed</div>
            <div class="text-2xl font-bold text-green-600">{{ $stats['total_rewards_claimed'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm">Pending</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['total_rewards_pending'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-gray-500 text-sm">Total Distributed</div>
            <div class="text-2xl font-bold text-yellow-600">{{ number_format($stats['total_amount_distributed'], 2) }}</div>
        </div>
    </div>

    <!-- Award Today's Reward (Manual) -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Award Today's Security Reward</h2>

        @if($todayReward)
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-green-800 font-medium">Reward Already Awarded for Today</div>
                        <div class="text-green-600">Employee: {{ $todayReward->employee->formal_name }}</div>
                        <div class="text-green-600 text-sm">Code: {{ $todayReward->employee->employee_code }}</div>
                        <div class="text-green-600 text-sm">Unit: {{ $todayReward->employee->unit->name ?? 'N/A' }}</div>
                        @if($todayReward->sms_sent)
                            <div class="text-green-500 text-xs mt-1">SMS sent at {{ $todayReward->sms_sent_at?->format('h:i A') }}</div>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-green-800 font-bold">{{ $todayReward->formatted_amount }}</div>
                        <div class="text-green-600 text-sm">{{ $todayReward->reward_date->format('F j, Y') }}</div>
                        <button onclick="resendSms({{ $todayReward->id }})" class="mt-2 text-blue-600 text-sm hover:underline">Resend SMS</button>
                    </div>
                </div>
            </div>
        @else
            <form id="rewardTodayForm" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Employee to Award 200 KES Today</label>
                    <select name="employee_id" id="rewardTodayEmployee" class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200" required>
                        <option value="">-- Select Employee --</option>
                        @foreach($availableEmployees as $employee)
                            <option value="{{ $employee->id }}">
                                {{ $employee->formal_name }} ({{ $employee->employee_code }}) - {{ $employee->unit->name ?? 'No Unit' }} - {{ $employee->department->name ?? 'N/A' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Selected employee will receive 200 KES meal today. SMS will be sent immediately.</p>
                </div>
                <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-6 rounded-lg transition">
                    Award 200 KES Reward for Today
                </button>
            </form>
        @endif
    </div>

    <!-- Award Tomorrow's Reward (Optional - Manual) -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Schedule Tomorrow's Reward (Optional)</h2>

        @if($tomorrowReward)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-blue-800 font-medium">Reward Already Scheduled for Tomorrow</div>
                        <div class="text-blue-600">Employee: {{ $tomorrowReward->employee->formal_name }}</div>
                        <div class="text-blue-600 text-sm">Code: {{ $tomorrowReward->employee->employee_code }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-blue-800 font-bold">{{ $tomorrowReward->formatted_amount }}</div>
                        <div class="text-blue-600 text-sm">{{ $tomorrowReward->reward_date->format('F j, Y') }}</div>
                        <button onclick="cancelReward({{ $tomorrowReward->id }})" class="mt-2 text-red-600 text-sm hover:underline">Cancel</button>
                    </div>
                </div>
            </div>
        @else
            <form id="rewardTomorrowForm" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Employee for Tomorrow's Reward (Optional)</label>
                    <select name="employee_id" id="rewardTomorrowEmployee" class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                        <option value="">-- Select Employee (leave empty if not scheduling) --</option>
                        @foreach($availableEmployees as $employee)
                            <option value="{{ $employee->id }}">
                                {{ $employee->formal_name }} ({{ $employee->employee_code }}) - {{ $employee->unit->name ?? 'No Unit' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">If selected, SMS will be sent tonight at 10 PM. Leave empty to not schedule.</p>
                </div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-lg transition">
                    Schedule Reward for Tomorrow
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SMS</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Claimed At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Awarded By</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($rewards as $reward)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $reward->reward_date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $reward->employee->formal_name }}</div>
                            <div class="text-sm text-gray-500">{{ $reward->employee->employee_code }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $reward->employee->unit->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-yellow-600">
                            {{ $reward->formatted_amount }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ $reward->status === 'claimed' ? 'bg-green-100 text-green-800' :
                                   ($reward->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ ucfirst($reward->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($reward->sms_sent)
                                <span class="text-green-600 text-xs">Sent</span>
                            @else
                                <span class="text-red-600 text-xs">Failed</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $reward->mealTransaction?->created_at?->format('M d, Y H:i') ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $reward->sender?->name ?? 'Admin' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            No rewards issued yet
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
// Award today's reward
document.getElementById('rewardTodayForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const employeeId = formData.get('employee_id');

    if (!employeeId) {
        alert('Please select an employee');
        return;
    }

    const button = e.target.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Awarding...';
    button.disabled = true;

    try {
        const response = await fetch('{{ route("admin.rewards.reward-today") }}', {
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
        alert('Failed to award reward. Please try again.');
    } finally {
        button.innerHTML = originalText;
        button.disabled = false;
    }
});

// Schedule tomorrow's reward
document.getElementById('rewardTomorrowForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const employeeId = formData.get('employee_id');

    const button = e.target.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Scheduling...';
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

function resendSms(rewardId) {
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

function cancelReward(rewardId) {
    if (confirm('Are you sure you want to cancel this reward?')) {
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
