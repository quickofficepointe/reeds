{{-- resources/views/emails/admin-invoice-notification.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice_number }} from {{ $vendor->name }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #2596be 0%, #1a6f8f 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .status-banner {
            padding: 15px;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border-bottom: 2px solid #ffeeba;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
            border-bottom: 2px solid #c3e6cb;
        }
        .status-overdue {
            background-color: #f8d7da;
            color: #721c24;
            border-bottom: 2px solid #f5c6cb;
        }
        .content {
            padding: 30px;
        }
        .invoice-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #e9ecef;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: 600;
            color: #495057;
        }
        .value {
            color: #2596be;
            font-weight: 600;
        }
        .amount-large {
            font-size: 32px;
            color: #2596be;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
        }
        .vendor-info {
            background-color: #e3f2fd;
            border-left: 4px solid #2596be;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .vendor-info h3 {
            margin-top: 0;
            color: #2596be;
        }
        .payment-info {
            background-color: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #2596be 0%, #1a6f8f 100%);
            color: white;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
        }
        .button:hover {
            background: linear-gradient(135deg, #1a6f8f 0%, #124b63 100%);
        }
        .button-secondary {
            background: #6c757d;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .table th {
            background-color: #2596be;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .table td {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .signature {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed #e9ecef;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 10px;
                width: auto;
            }
            .detail-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1> INVOICE NOTIFICATION</h1>
            <p>Invoice #{{ $invoice_number }}</p>
        </div>

        <!-- Status Banner -->
        @if($invoice->status == 'overdue' || $invoice->isOverdue())
            <div class="status-banner status-overdue">
                THIS INVOICE IS OVERDUE 
            </div>
        @elseif($invoice->status == 'pending')
            <div class="status-banner status-pending">
                 PENDING PAYMENT
            </div>
        @elseif($invoice->status == 'paid')
            <div class="status-banner status-paid">
                PAID INVOICE
            </div>
        @endif

        <div class="content">
            <!-- Greeting -->
            <p style="font-size: 16px;">Dear Finance Team,</p>

            <p style="font-size: 16px;">
                @if($invoice->status == 'overdue' || $invoice->isOverdue())
                    <strong> URGENT:</strong>
                @endif
                An invoice has been generated and requires your attention.
            </p>

            <!-- Amount Highlight -->
            <div class="amount-large">
                {{ $invoice->formatted_total }}
            </div>

            <!-- Quick Summary -->
            <div style="display: flex; justify-content: space-between; margin: 20px 0; text-align: center;">
                <div style="flex: 1;">
                    <div style="font-size: 24px; font-weight: bold; color: #2596be;">{{ $invoice->total_scans }}</div>
                    <div style="font-size: 12px; color: #6c757d;">Total Meals</div>
                </div>
                <div style="flex: 1;">
                    <div style="font-size: 24px; font-weight: bold; color: #2596be;">65</div>
                    <div style="font-size: 12px; color: #6c757d;">Rate (Ksh)</div>
                </div>
                <div style="flex: 1;">
                    <div style="font-size: 24px; font-weight: bold; color: {{ $invoice->isOverdue() ? '#dc3545' : '#2596be' }};">
                        {{ $daysUntilDue > 0 ? $daysUntilDue : 'Overdue' }}
                    </div>
                    <div style="font-size: 12px; color: #6c757d;">Days {{ $daysUntilDue > 0 ? 'to Due' : 'Overdue' }}</div>
                </div>
            </div>

            <!-- Invoice Details Card -->
            <div class="invoice-details">
                <h3 style="margin-top: 0; color: #2596be; border-bottom: 2px solid #2596be; padding-bottom: 10px;">
                     INVOICE DETAILS
                </h3>

                <div class="detail-row">
                    <span class="label">Invoice Number:</span>
                    <span class="value">{{ $invoice_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Period Covered:</span>
                    <span class="value">{{ $period }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Cycle Number:</span>
                    <span class="value">Cycle {{ $invoice->cycle_number ?? '1' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Invoice Date:</span>
                    <span class="value">{{ $invoice->invoice_date->format('F j, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Due Date:</span>
                    <span class="value" style="{{ $invoice->isOverdue() ? 'color: #dc3545; font-weight: bold;' : '' }}">
                        {{ $dueDate }}
                        @if($invoice->isOverdue())
                            <br><small style="color: #dc3545;">({{ $daysUntilDue }} days overdue)</small>
                        @elseif($daysUntilDue <= 7)
                            <br><small style="color: #ff9800;">(Due in {{ $daysUntilDue }} days)</small>
                        @endif
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Total Meals:</span>
                    <span class="value">{{ number_format($invoice->total_scans) }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Rate per Meal:</span>
                    <span class="value">Ksh 65.00</span>
                </div>
                <div class="detail-row">
                    <span class="label">Subtotal:</span>
                    <span class="value">Ksh {{ number_format($invoice->total_scans * 65, 2) }}</span>
                </div>
                <div class="detail-row" style="border-top: 2px solid #2596be; margin-top: 10px; padding-top: 15px;">
                    <span class="label" style="font-size: 18px;">TOTAL AMOUNT:</span>
                    <span class="value" style="font-size: 24px; color: #2596be;">{{ $invoice->formatted_total }}</span>
                </div>
            </div>

            <!-- Vendor Information -->
            <div class="vendor-info">
                <h3> VENDOR INFORMATION</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 5px 0;"><strong>Vendor Name:</strong></td>
                        <td style="padding: 5px 0;">{{ $vendor->name }}</td>
                    </tr>
                    @if($vendor->profile && $vendor->profile->business_name)
                    <tr>
                        <td style="padding: 5px 0;"><strong>Business Name:</strong></td>
                        <td style="padding: 5px 0;">{{ $vendor->profile->business_name }}</td>
                    </tr>
                    @endif
                    @if($vendor->profile && $vendor->profile->phone_number)
                    <tr>
                        <td style="padding: 5px 0;"><strong>Contact Phone:</strong></td>
                        <td style="padding: 5px 0;">{{ $vendor->profile->phone_number }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 5px 0;"><strong>Email:</strong></td>
                        <td style="padding: 5px 0;">{{ $vendor->email }}</td>
                    </tr>
                    @if($vendor->unit)
                    <tr>
                        <td style="padding: 5px 0;"><strong>Unit/Location:</strong></td>
                        <td style="padding: 5px 0;">{{ $vendor->unit->name }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            <!-- Daily Breakdown Table -->
            @if($invoice->items && count($invoice->items) > 0)
            <h3 style="color: #2596be; margin-top: 30px;"> DAILY BREAKDOWN</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th style="text-align: right;">Meals</th>
                        <th style="text-align: right;">Rate</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalScans = 0; @endphp
                    @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->date->format('M j, Y') }}</td>
                        <td>{{ $item->date->format('l') }}</td>
                        <td style="text-align: right;">{{ number_format($item->scans) }}</td>
                        <td style="text-align: right;">Ksh 65.00</td>
                        <td style="text-align: right;">Ksh {{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @php $totalScans += $item->scans; @endphp
                    @endforeach
                </tbody>
                <tfoot style="background-color: #f8f9fa; font-weight: bold;">
                    <tr>
                        <td colspan="2" style="text-align: right;"><strong>TOTALS:</strong></td>
                        <td style="text-align: right;">{{ number_format($totalScans) }}</td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;">{{ $invoice->formatted_total }}</td>
                    </tr>
                </tfoot>
            </table>
            @endif

            <!-- Payment Information -->
            <div class="payment-info">
                <h3 style="margin-top: 0; color: #ff9800;">PAYMENT INSTRUCTIONS</h3>

                @php $bankDetails = $invoice->vendor_bank_details; @endphp

                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 8px 0;"><strong>Bank Name:</strong></td>
                        <td style="padding: 8px 0;">{{ $bankDetails['bank_name'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>Account Name:</strong></td>
                        <td style="padding: 8px 0;">{{ $bankDetails['account_name'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>Account Number:</strong></td>
                        <td style="padding: 8px 0;">{{ $bankDetails['account_number'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>Bank Branch:</strong></td>
                        <td style="padding: 8px 0;">{{ $bankDetails['bank_branch'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>SWIFT Code:</strong></td>
                        <td style="padding: 8px 0;">{{ $bankDetails['swift_code'] }}</td>
                    </tr>
                </table>

                <div style="background-color: #fff; padding: 15px; border-radius: 5px; margin-top: 15px;">
                    <p style="margin: 0; font-size: 14px;">
                        <strong> PAYMENT REFERENCE:</strong>
                        <span style="color: #2596be; font-size: 18px; font-weight: bold;">{{ $invoice_number }}</span>
                    </p>
                    <p style="margin: 5px 0 0; font-size: 12px; color: #666;">
                        Please use this reference when making payment
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/admin/invoices/' . $invoice->id) }}" class="button">
                    <i class="fas fa-eye" style="margin-right: 8px;"></i> View Full Invoice
                </a>
                <a href="{{ url('/admin/invoices/' . $invoice->id . '/download') }}" class="button button-secondary">
                    <i class="fas fa-download" style="margin-right: 8px;"></i> Download PDF
                </a>
            </div>

            <!-- Alert based on status -->
            @if($invoice->status == 'overdue' || $invoice->isOverdue())
            <div class="alert alert-danger">
                <strong> URGENT ACTION REQUIRED:</strong> This invoice is overdue by {{ abs($daysUntilDue) }} days.
                Please process payment immediately to avoid service interruption.
            </div>
            @elseif($daysUntilDue <= 7 && $daysUntilDue > 0)
            <div class="alert alert-warning">
                <strong>UPCOMING DUE DATE:</strong> This invoice is due in {{ $daysUntilDue }} days
                ({{ $dueDate }}). Please schedule payment accordingly.
            </div>
            @endif

            <!-- Signature -->
            <div class="signature">
                <p style="margin: 0;">
                    <strong>REEDS Africa Talent Gateway</strong><br>
                    QR Feeding System<br>
                    <small style="color: #666;">This is an automated notification. Please do not reply to this email.</small>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 10px;">
                <strong>REEDS Africa Talent Gateway</strong><br>
                P.O. Box 12345-00100, Nairobi, Kenya<br>
                Email: accounting@reedsafrica.com | Phone: +254 712 345 678
            </p>
            <p style="margin: 0; font-size: 10px;">
                © {{ date('Y') }} REEDS Africa Talent Gateway. All rights reserved.<br>
                Invoice #{{ $invoice_number }} | Generated on {{ $invoice->created_at->format('F j, Y \a\t g:i A') }}
            </p>
            @if($invoice->is_test)
            <p style="color: #ff0000; font-weight: bold; margin-top: 10px;">
                *** THIS IS A TEST INVOICE - NOT FOR PAYMENT ***
            </p>
            @endif
        </div>
    </div>
</body>
</html>
