@extends('reeds.admin.layout.adminlayout')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Employee Scan Data Export</h1>
        <p class="text-gray-600">Export detailed scan reports including normal scans and reward scans</p>
    </div>

    <!-- Export Card -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">Generate Scan Report</h2>
            <p class="text-blue-100 text-sm mt-1">Select date range to export employee scan data</p>
        </div>

        <div class="p-6">
            <form id="exportForm" action="{{ route('admin.employees.scan-data.download') }}" method="GET" target="_blank">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                        <input type="date"
                               name="start_date"
                               id="start_date"
                               value="{{ $startDate }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                        <input type="date"
                               name="end_date"
                               id="end_date"
                               value="{{ $endDate }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Unit (Optional)</label>
                        <select name="unit_id" id="unit_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Units</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ request('unit_id') == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Export Format</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="format" value="excel" checked class="form-radio text-blue-600">
                                <span class="ml-2 text-gray-700">
                                    <i class="fas fa-file-excel text-green-600 mr-1"></i> Excel (CSV)
                                </span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="format" value="pdf" class="form-radio text-blue-600">
                                <span class="ml-2 text-gray-700">
                                    <i class="fas fa-file-pdf text-red-600 mr-1"></i> PDF
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                                class="w-full md:w-auto px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-150 flex items-center justify-center">
                            <i class="fas fa-download mr-2"></i> Generate Report
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Section -->
    <div class="mt-8 bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900">Report Preview</h3>
            <p class="text-sm text-gray-500">What will be included in your report</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <i class="fas fa-users text-blue-600 text-2xl mb-2"></i>
                    <h4 class="font-semibold text-gray-900">Employee Data</h4>
                    <p class="text-sm text-gray-600 mt-1">Complete employee list with names, codes, departments, and units</p>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <i class="fas fa-chart-bar text-green-600 text-2xl mb-2"></i>
                    <h4 class="font-semibold text-gray-900">Scan Statistics</h4>
                    <p class="text-sm text-gray-600 mt-1">Normal scans, reward scans, totals, and amounts per employee</p>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4 text-center">
                    <i class="fas fa-signature text-yellow-600 text-2xl mb-2"></i>
                    <h4 class="font-semibold text-gray-900">Signature Section</h4>
                    <p class="text-sm text-gray-600 mt-1">Signature fields for verification and approval</p>
                </div>
            </div>

            <!-- Sample Table Preview -->
            <div class="mt-6">
                <h4 class="font-medium text-gray-900 mb-3">Sample Report Structure:</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200 text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 border">#</th>
                                <th class="px-3 py-2 border">Employee Name</th>
                                <th class="px-3 py-2 border">Employee Code</th>
                                <th class="px-3 py-2 border">Department</th>
                                <th class="px-3 py-2 border">Unit</th>
                                <th class="px-3 py-2 border">Normal Scans</th>
                                <th class="px-3 py-2 border">Reward Scans</th>
                                <th class="px-3 py-2 border">Total Scans</th>
                                <th class="px-3 py-2 border">Total Amount</th>
                                <th class="px-3 py-2 border">Signature</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-3 py-2 border text-center">1</td>
                                <td class="px-3 py-2 border">John Doe</td>
                                <td class="px-3 py-2 border">EMP001</td>
                                <td class="px-3 py-2 border">IT</td>
                                <td class="px-3 py-2 border">Head Office</td>
                                <td class="px-3 py-2 border text-center">15</td>
                                <td class="px-3 py-2 border text-center">2</td>
                                <td class="px-3 py-2 border text-center">17</td>
                                <td class="px-3 py-2 border text-center">KES 1,700</td>
                                <td class="px-3 py-2 border text-center">_________</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('exportForm').addEventListener('submit', function(e) {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    if (!startDate || !endDate) {
        e.preventDefault();
        alert('Please select both start and end dates');
        return false;
    }

    if (startDate > endDate) {
        e.preventDefault();
        alert('Start date cannot be after end date');
        return false;
    }
});
</script>
@endsection
