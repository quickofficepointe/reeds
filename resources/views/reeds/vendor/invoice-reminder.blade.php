<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Reminder</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2596be; padding: 20px; text-align: center; color: white; }
        .content { padding: 30px; background-color: #f9f9f9; }
        .invoice-details { background-color: white; padding: 20px; border-radius: 5px; border: 1px solid #ddd; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .btn { display: inline-block; background-color: #2596be; color: white; padding: 12px 24px;
               text-decoration: none; border-radius: 5px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice Payment Reminder</h1>
        </div>

        <div class="content">
            <p>Dear {{ $vendor->name }},</p>

            <div class="warning">
                <p><strong>Friendly Reminder:</strong> Your invoice #{{ $invoice->invoice_number }} is due in {{ $dueIn }} days.</p>
            </div>

            <div class="invoice-details">
                <h3>Invoice Details</h3>
                <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Period:</strong> {{ $invoice->period_start->format('F j, Y') }} - {{ $invoice->period_end->format('F j, Y') }}</p>
                <p><strong>Total Amount:</strong> Ksh {{ number_format($invoice->total_amount, 2) }}</p>
                <p><strong>Due Date:</strong> {{ $invoice->due_date->format('F j, Y') }}</p>
            </div>

            <p>To avoid any service interruption, please ensure payment is made by the due date.</p>

            <p><strong>Payment Instructions:</strong></p>
            <ul>
                <li>Bank: [Your Bank Name]</li>
                <li>Account Name: REEDS Africa Talent Gateway</li>
                <li>Account Number: [Account Number]</li>
                <li>Reference: {{ $invoice->invoice_number }}</li>
            </ul>

            <p>You can view and download your invoice by logging into your vendor dashboard.</p>

            <a href="{{ url('/vendor/invoices') }}" class="btn">View Invoice in Dashboard</a>

            <p>If you have already made the payment, please disregard this reminder.</p>
        </div>

        <div class="footer">
            <p>This is an automated reminder from the QR Feeding System.</p>
            <p>If you have any questions, please contact accounting@reedsafrica.com</p>
            <p>© {{ date('Y') }} REEDS Africa Talent Gateway. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
