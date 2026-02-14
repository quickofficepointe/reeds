{{-- resources/views/emails/vendor-invoice-generated.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Generated</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2596be;
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #e0e0e0;
            border-top: none;
            border-radius: 0 0 10px 10px;
        }
        .invoice-box {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .amount {
            font-size: 28px;
            color: #2596be;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background-color: #2596be;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #999;
            font-size: 12px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice Generated</h1>
            <p>Invoice #{{ $invoice->invoice_number }}</p>
        </div>

        <div class="content">
            <p>Dear {{ $vendor->name }},</p>

            <p>Your invoice for meals provided during the period <strong>{{ $period }}</strong> has been generated.</p>

            <div class="amount">
                {{ $totalAmount }}
            </div>

            <div class="invoice-box">
                <h3 style="margin-top: 0;">Invoice Details</h3>

                <div class="detail-row">
                    <span>Invoice Number:</span>
                    <span><strong>{{ $invoice->invoice_number }}</strong></span>
                </div>
                <div class="detail-row">
                    <span>Period:</span>
                    <span><strong>{{ $period }}</strong></span>
                </div>
                <div class="detail-row">
                    <span>Cycle:</span>
                    <span><strong>Cycle {{ $cycleNumber }}</strong></span>
                </div>
                <div class="detail-row">
                    <span>Total Scans:</span>
                    <span><strong>{{ number_format($totalScans) }}</strong></span>
                </div>
                <div class="detail-row">
                    <span>Rate per Meal:</span>
                    <span><strong>{{ $ratePerMeal }}</strong></span>
                </div>
                <div class="detail-row">
                    <span>Total Amount:</span>
                    <span><strong>{{ $totalAmount }}</strong></span>
                </div>
                <div class="detail-row">
                    <span>Due Date:</span>
                    <span><strong>{{ $dueDate }}</strong></span>
                </div>
                <div class="detail-row">
                    <span>Days Until Due:</span>
                    <span><strong>{{ $dueInDays }} days</strong></span>
                </div>
            </div>

            <div style="background-color: #e8f4f8; padding: 20px; border-radius: 5px; margin: 20px 0;">
                <h4 style="margin-top: 0;">💳 Payment Information</h4>
                <p><strong>Bank:</strong> Cooperative Bank of Kenya</p>
                <p><strong>Account Name:</strong> REEDS Africa Talent Gateway</p>
                <p><strong>Account Number:</strong> 011 123 456 78900</p>
                <p><strong>Payment Reference:</strong> {{ $invoice->invoice_number }}</p>
            </div>

            <div style="text-align: center;">
                <a href="{{ url('/vendor/invoices/' . $invoice->id) }}" class="button">
                    View Invoice Details
                </a>
            </div>

            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                A PDF copy of this invoice is attached to this email for your records.
            </p>

            <div class="footer">
                <p>This is an automated notification from the REEDS Africa QR Feeding System.</p>
                <p>© {{ date('Y') }} REEDS Africa Talent Gateway. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
