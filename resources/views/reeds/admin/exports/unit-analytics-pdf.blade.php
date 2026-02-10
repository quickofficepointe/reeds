<!DOCTYPE html>
<html>
<head>
    <title>Unit Analytics Report</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #333; margin-bottom: 5px; }
        .header p { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f8f9fa; text-align: left; padding: 10px; border: 1px solid #dee2e6; }
        td { padding: 8px; border: 1px solid #dee2e6; }
        .summary { margin-bottom: 30px; }
        .summary-item { margin-bottom: 10px; }
        .total-row { font-weight: bold; background-color: #f8f9fa; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Unit Analytics Report</h1>
        <p>Generated on: {{ $generatedAt }}</p>
        @if($startDate && $endDate)
            <p>Period: {{ $startDate }} to {{ $endDate }}</p>
        @endif
    </div>

    <div class="summary">
        <h2>Report Summary</h2>
        <p>Total Units: {{ count($units) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Unit Name</th>
                <th>Location</th>
                <th>Employees</th>
                <th>Active Employees</th>
                <th>Total Scans</th>
                <th>Total Revenue</th>
                <th>Avg Daily Scans</th>
                <th>Active Vendors</th>
            </tr>
        </thead>
        <tbody>
            @foreach($units as $unit)
            <tr>
                <td>{{ $unit['name'] }}</td>
                <td>{{ $unit['location'] ?? 'N/A' }}</td>
                <td>{{ $unit['total_employees'] }}</td>
                <td>{{ $unit['active_employees'] }}</td>
                <td>{{ $unit['total_scans'] }}</td>
                <td>KSh {{ number_format($unit['total_revenue'], 2) }}</td>
                <td>{{ $unit['avg_daily_scans'] }}</td>
                <td>{{ $unit['active_vendors'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(count($units) > 0)
    <div class="summary">
        <h2>Performance Metrics</h2>
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalScans = collect($units)->sum('total_scans');
                    $totalRevenue = collect($units)->sum('total_revenue');
                    $totalEmployees = collect($units)->sum('total_employees');
                    $totalActiveEmployees = collect($units)->sum('active_employees');
                @endphp
                <tr>
                    <td>Total Scans Across All Units</td>
                    <td>{{ number_format($totalScans) }}</td>
                </tr>
                <tr>
                    <td>Total Revenue</td>
                    <td>KSh {{ number_format($totalRevenue, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Employees</td>
                    <td>{{ number_format($totalEmployees) }}</td>
                </tr>
                <tr>
                    <td>Total Active Employees</td>
                    <td>{{ number_format($totalActiveEmployees) }}</td>
                </tr>
                <tr>
                    <td>Average Revenue per Scan</td>
                    <td>KSh {{ $totalScans > 0 ? number_format($totalRevenue / $totalScans, 2) : '0.00' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</body>
</html>
