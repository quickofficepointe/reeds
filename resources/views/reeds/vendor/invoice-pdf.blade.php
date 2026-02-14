{{-- resources/views/reeds/vendor/invoice-pdf.blade.php --}}
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
            overflow: hidden;
        }
        .company-info {
            float: right;
            text-align: right;
            width: 50%;
        }
        .vendor-info {
            float: left;
            text-align: left;
            width: 45%;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #2596be;
            margin: 20px 0 10px 0;
            clear: both;
            text-align: center;
        }
        .invoice-meta {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            clear: both;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-box {
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 5px;
            background-color: #fff;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 14px;
            margin-bottom: 5px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 12px;
        }
        .table th {
            background-color: #2596be;
            color: white;
            border: 1px solid #dee2e6;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
        }
        .table td {
            border: 1px solid #dee2e6;
            padding: 10px 8px;
        }
        .table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .total-section {
            float: right;
            width: 350px;
            margin-top: 20px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
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
            border-bottom: none;
            margin-top: 10px;
            padding-top: 10px;
            color: #2596be;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 11px;
            color: #666;
            clear: both;
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .status-overdue {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .payment-info {
            background-color: #e8f4f8;
            padding: 20px;
            border-radius: 5px;
            margin: 30px 0;
            border-left: 4px solid #2596be;
        }
        .payment-info h3 {
            color: #2596be;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .payment-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .payment-item {
            margin-bottom: 10px;
        }
        .payment-label {
            font-weight: bold;
            color: #666;
            font-size: 11px;
            text-transform: uppercase;
        }
        .payment-value {
            font-size: 14px;
            font-weight: 500;
        }
        .vendor-contact {
            background-color: #fff;
            border: 1px solid #2596be;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .vendor-contact strong {
            color: #2596be;
        }
        .page-break {
            page-break-before: always;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .mb-10 {
            margin-bottom: 10px;
        }
        .mt-20 {
            margin-top: 20px;
        }
        .cycle-badge {
            background-color: #e7f3ff;
            color: #0066cc;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with Company and Vendor Info -->
        <div class="header">
            <div class="vendor-info">
                <h3 style="margin-top: 0; color: #2596be;">VENDOR DETAILS</h3>
                <p><strong>{{ $invoice->vendor->name }}</strong></p>
                @if($invoice->vendor_business_name)
                    <p><strong>Business:</strong> {{ $invoice->vendor_business_name }}</p>
                @endif
                @if($invoice->vendor_phone)
                    <p><strong>Phone:</strong> {{ $invoice->vendor_phone }}</p>
                @endif
                <p><strong>Email:</strong> {{ $invoice->vendor->email }}</p>
            </div>

            <div class="company-info">
                <h2 style="color: #2596be; margin-top: 0;">REEDS Africa</h2>
                <p><strong>Talent Gateway</strong></p>
                <p>P.O. Box 12345-00100</p>
                <p>Nairobi, Kenya</p>
                <p>Email: accounting@reedsafrica.com</p>
                <p>Phone: +254 712 345 678</p>
            </div>
        </div>

        <!-- Invoice Title -->
        <div class="invoice-title">
            INVOICE
        </div>

        <!-- Invoice Meta -->
        <div class="invoice-meta">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 33%;">
                        <div class="info-label">INVOICE NUMBER</div>
                        <div class="info-value" style="font-size: 16px; font-weight: bold;">{{ $invoice->invoice_number }}</div>
                    </td>
                    <td style="width: 33%;">
                        <div class="info-label">DATE</div>
                        <div class="info-value">{{ $invoice->invoice_date->format('F j, Y') }}</div>
                    </td>
                    <td style="width: 33%;">
                        <div class="info-label">STATUS</div>
                        <div class="status-badge status-{{ $invoice->status }}">
                            {{ strtoupper($invoice->status) }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Billing Information Grid -->
        <div class="info-grid">
            <div class="info-box">
                <div class="info-label">BILL TO</div>
                <div class="info-value">
                    <strong>REEDS Africa Talent Gateway</strong><br>
                    Accounts Department<br>
                    P.O. Box 12345-00100<br>
                    Nairobi, Kenya<br>
                    Email: accounting@reedsafrica.com
                </div>
            </div>

            <div class="info-box">
                <div class="info-label">INVOICE PERIOD</div>
                <div class="info-value">
                    <strong>{{ $invoice->period_start->format('l, F j, Y') }}</strong><br>
                    to<br>
                    <strong>{{ $invoice->period_end->format('l, F j, Y') }}</strong><br>
                    <span class="cycle-badge">Cycle {{ $invoice->cycle_number ?? 'N/A' }}</span>
                </div>
                <div class="info-label mt-20">DUE DATE</div>
                <div class="info-value">
                    <strong>{{ $invoice->due_date->format('l, F j, Y') }}</strong><br>
                    <span style="color: #666; font-size: 11px;">(30 days from invoice date)</span>
                </div>
            </div>
        </div>

        <!-- Invoice Items Table -->
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Description</th>
                    <th class="text-right">Scans</th>
                    <th class="text-right">Rate (Ksh)</th>
                    <th class="text-right">Amount (Ksh)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->date->format('M j, Y') }}</td>
                    <td>{{ $item->date->format('l') }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ number_format($item->scans) }}</td>
                    <td class="text-right">{{ number_format($item->rate, 2) }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="total-section">
            <div class="total-row">
                <span>Total Scans:</span>
                <span><strong>{{ number_format($invoice->total_scans) }}</strong></span>
            </div>
            <div class="total-row">
                <span>Rate per Scan:</span>
                <span><strong>Ksh 65.00</strong></span>
            </div>
            <div class="total-row" style="border-bottom: 2px solid #dee2e6;">
                <span>Subtotal:</span>
                <span><strong>Ksh {{ number_format($invoice->total_amount, 2) }}</strong></span>
            </div>
            <div class="total-row grand-total">
                <span>TOTAL AMOUNT DUE:</span>
                <span><strong>Ksh {{ number_format($invoice->total_amount, 2) }}</strong></span>
            </div>
        </div>

        <div style="clear: both;"></div>

        <!-- Payment Information -->
        <div class="payment-info">
            <h3>PAYMENT INFORMATION</h3>

            <div class="vendor-contact">
                <p style="margin-top: 0; font-size: 13px;">
                    <strong>For payment inquiries, please contact the vendor directly:</strong>
                </p>
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%;">
                            <div class="payment-label">Vendor Name</div>
                            <div class="payment-value">{{ $invoice->vendor->name }}</div>
                        </td>
                        <td style="width: 50%;">
                            <div class="payment-label">Contact Phone</div>
                            <div class="payment-value">
                                <strong style="color: #2596be; font-size: 16px;">
                                    {{ $invoice->vendor_phone ?? 'Not provided' }}
                                </strong>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="payment-label">Email</div>
                            <div class="payment-value">{{ $invoice->vendor->email }}</div>
                        </td>
                        <td>
                            <div class="payment-label">Business Name</div>
                            <div class="payment-value">{{ $invoice->vendor_business_name ?? 'N/A' }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="payment-details">
                <div class="payment-item">
                    <div class="payment-label">Bank Name</div>
                    <div class="payment-value">{{ $invoice->vendor_bank_details['bank_name'] }}</div>
                </div>
                <div class="payment-item">
                    <div class="payment-label">Account Name</div>
                    <div class="payment-value">{{ $invoice->vendor_bank_details['account_name'] }}</div>
                </div>
                <div class="payment-item">
                    <div class="payment-label">Account Number</div>
                    <div class="payment-value">{{ $invoice->vendor_bank_details['account_number'] }}</div>
                </div>
                <div class="payment-item">
                    <div class="payment-label">Bank Branch</div>
                    <div class="payment-value">{{ $invoice->vendor_bank_details['bank_branch'] }}</div>
                </div>
                <div class="payment-item">
                    <div class="payment-label">SWIFT Code</div>
                    <div class="payment-value">{{ $invoice->vendor_bank_details['swift_code'] }}</div>
                </div>
                <div class="payment-item">
                    <div class="payment-label">Payment Reference</div>
                    <div class="payment-value"><strong style="color: #2596be;">{{ $invoice->invoice_number }}</strong></div>
                </div>
            </div>

            <p style="font-size: 11px; color: #666; margin-top: 15px; margin-bottom: 0;">
                <strong>Important:</strong> Please use the invoice number as the payment reference.
                Payment should be made within 30 days of invoice date.
                For any queries regarding this invoice, please contact the vendor at
                <strong>{{ $invoice->vendor_phone ?? $invoice->vendor->email }}</strong>.
            </p>
        </div>

        <!-- Additional Notes -->
        @if($invoice->notes)
        <div style="margin-top: 20px; padding: 10px; background-color: #fff3cd; border-radius: 5px;">
            <p style="margin: 0; font-size: 12px;"><strong>Notes:</strong> {{ $invoice->notes }}</p>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>REEDS Africa Talent Gateway - QR Feeding System</p>
            <p>This invoice was generated on {{ $invoice->created_at->format('F j, Y \a\t H:i') }}</p>
            <p style="font-size: 9px; color: #999;">Invoice #{{ $invoice->invoice_number }} | Cycle {{ $invoice->cycle_number ?? 'N/A' }}</p>
            @if($invoice->is_test)
                <p style="color: #ff0000; font-weight: bold; font-size: 14px; margin-top: 10px;">
                  
                </p>
            @endif
        </div>
    </div>
</body>
</html>
