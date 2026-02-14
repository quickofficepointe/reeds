{{-- resources/views/emails/invoice-payment-reminder.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reminder</title>
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
            background-color: {{ $isOverdue ? '#dc3545' : '#ffc107' }};
            color: {{ $isOverdue ? 'white' : '#333' }};
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
        .warning-box {
            background-color: {{ $isOverdue ? '#f8d7da' : '#fff3cd' }};
            border: 1px solid {{ $isOverdue ? '#f5c6cb' : '#ffeeba' }};
            color: {{ $isOverdue ? '#721c24' : '#856404' }};
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .invoice-details {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $isOverdue ? '⚠️ OVERDUE INVOICE' : '🔔 PAYMENT REMINDER' }}</h1>
        </div>

        <div class="content">
            <p>Dear {{ $vendor->name }},</p>

            <div class="warning-box">
                @if($isOverdue)
                    <h3 style="margin-top: 0;">Your invoice is OVERDUE!</h3>
                    <p>Payment was due on <strong>{{ $dueDate }}</strong> ({{ $daysRemaining }} days ago).</p>
                    <p style="margin-bottom: 0;"><strong>Please make payment immediately to avoid service interruption.</strong></p>
                @else
                    <h3 style="margin-top: 0;">Friendly Reminder</h3>
                    <p>Your invoice #{{ $invoiceNumber }} is due in <strong>{{ $daysRemaining }} days</strong>.</p>
                    <p style="margin-bottom: 0;">Due Date: <strong>{{ $dueDate }}</strong></p>
                @endif
            </div>

            <div class="invoice-details">
                <h3>Invoice Summary</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0;"><strong>Invoice Number:</strong></td>
                        <td style="padding: 8px 0;">{{ $invoiceNumber }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>Period:</strong></td>
                        <td style="padding: 8px 0;">{{ $period }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>Total Amount:</strong></td>
                        <td style="padding: 8px 0; font-size: 18px; color: #2596be; font-weight: bold;">{{ $totalAmount }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>Due Date:</strong></td>
                        <td style="padding: 8px 0; {{ $isOverdue ? 'color: #dc3545; font-weight: bold;' : '' }}">{{ $dueDate }}</td>
                    </tr>
                </table>
            </div>

            <div style="text-align: center;">
                <a href="{{ url('/vendor/invoices/' . $invoice->id) }}" class="button">
                    View Invoice Details
                </a>
            </div>

            <div class="footer">
                <p>This is an automated reminder from the REEDS Africa QR Feeding System.</p>
                <p>© {{ date('Y') }} REEDS Africa Talent Gateway. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
