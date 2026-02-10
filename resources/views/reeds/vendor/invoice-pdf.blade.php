<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #2596be;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info {
            float: right;
            text-align: right;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #2596be;
            margin-bottom: 10px;
        }
        .invoice-info {
            margin-bottom: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 12px;
        }
        .info-value {
            font-size: 14px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
        }
        .table td {
            border: 1px solid #dee2e6;
            padding: 10px;
        }
        .total-section {
            float: right;
            width: 300px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .total-row.grand-total {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #2596be;
            margin-top: 10px;
            padding-top: 10px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h2>REEDS Africa Talent Gateway</h2>
                <p>P.O. Box 12345-00100</p>
                <p>Nairobi, Kenya</p>
                <p>Email: accounting@reedsafrica.com</p>
                <p>Phone: +254 712 345 678</p>
            </div>

            <div class="invoice-title">
                INVOICE
            </div>
            <div class="invoice-info">
                <p><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Date:</strong> {{ $invoice->invoice_date->format('F j, Y') }}</p>
                <p><strong>Status:</strong>
                    <span class="status-badge status-{{ $invoice->status }}">
                        {{ strtoupper($invoice->status) }}
                    </span>
                </p>
            </div>
        </div>

        <!-- Vendor Information -->
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <div class="info-label">BILL TO</div>
                    <div class="info-value">
                        <strong>{{ $invoice->vendor->name }}</strong><br>
                        {{ $invoice->vendor->email }}<br>
                        @if($invoice->vendor->profile && $invoice->vendor->profile->phone)
                            {{ $invoice->vendor->profile->phone }}<br>
                        @endif
                        @if($invoice->vendor->profile && $invoice->vendor->profile->business_name)
                            {{ $invoice->vendor->profile->business_name }}
                        @endif
                    </div>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <div class="info-label">INVOICE DETAILS</div>
                    <div class="info-value">
                        <strong>Period:</strong> {{ $invoice->period_start->format('M j, Y') }} - {{ $invoice->period_end->format('M j, Y') }}<br>
                        <strong>Due Date:</strong> {{ $invoice->due_date->format('F j, Y') }}<br>
                        <strong>Total Scans:</strong> {{ $invoice->total_scans }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Items -->
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Scans</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->date->format('M j, Y') }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->scans }}</td>
                    <td>Ksh {{ number_format($item->rate, 2) }}</td>
                    <td>Ksh {{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="total-section">
            <div class="total-row">
                <span>Total Scans:</span>
                <span>{{ $invoice->total_scans }}</span>
            </div>
            <div class="total-row">
                <span>Rate per Scan:</span>
                <span>Ksh 70.00</span>
            </div>
            <div class="total-row grand-total">
                <span>TOTAL AMOUNT:</span>
                <span>Ksh {{ number_format($invoice->total_amount, 2) }}</span>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="page-break"></div>
        <div style="margin-top: 40px;">
            <h3 style="color: #2596be; border-bottom: 1px solid #2596be; padding-bottom: 10px;">
                PAYMENT INFORMATION
            </h3>

            <div style="margin: 20px 0; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">
                <p><strong>Bank:</strong> Cooperative Bank of Kenya</p>
                <p><strong>Account Name:</strong> REEDS Africa Talent Gateway</p>
                <p><strong>Account Number:</strong> 011 123 456 78900</p>
                <p><strong>Swift Code:</strong> KCOOKENA</p>
                <p><strong>Payment Reference:</strong> <strong style="color: #2596be;">{{ $invoice->invoice_number }}</strong></p>
            </div>

            <p style="font-size: 12px; color: #666;">
                <strong>Note:</strong> Please use the invoice number as the payment reference.
                Payment should be made within 14 days of invoice date.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>REEDS Africa Talent Gateway - QR Feeding System</p>
            <p>This is a computer-generated invoice. No signature required.</p>
            @if($invoice->is_test)
            <p style="color: #ff0000; font-weight: bold;">*** TEST INVOICE - NOT FOR PAYMENT ***</p>
            @endif
        </div>
    </div>
</body>
</html>
