{{-- resources/views/emails/overdue-invoice-notification.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdue Invoice Notification</title>
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
            background-color: #dc3545;
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
        .alert-box {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .vendor-details {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
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
            <h1>🚨 OVERDUE INVOICE ALERT</h1>
            <p>Action Required</p>
        </div>

        <div class="content">
            <p>Dear Admin,</p>

            <div class="alert-box">
                <h3 style="margin-top: 0;">Invoice #{{ $invoiceNumber }} is OVERDUE</h3>
                <p>This invoice was due on <strong>{{ $dueDate }}</strong> and is now <strong>{{ $daysOverdue }} days overdue</strong>.</p>
            </div>

            <div class="vendor-details">
                <h3 style="margin-top: 0; color: #dc3545;">Vendor Information</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0;"><strong>Vendor Name:</strong></td>
                        <td style="padding: 8px 0;">{{ $vendorName }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>Contact Phone:</strong></td>
                        <td style="padding: 8px 0;">{{ $vendorPhone }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>Email:</strong></td>
                        <td style="padding: 8px 0;">{{ $vendorEmail }}</td>
                    </tr>
                </table>
            </div>

            <div style="background-color: white; padding: 20px; border-radius: 5px; margin: 20px 0;">
                <h3 style="margin-top: 0;">Invoice Details</h3>
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
                        <td style="padding: 8px 0; font-size: 18px; color: #dc3545; font-weight: bold;">{{ $totalAmount }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>Due Date:</strong></td>
                        <td style="padding: 8px 0; color: #dc3545; font-weight: bold;">{{ $dueDate }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>Days Overdue:</strong></td>
                        <td style="padding: 8px 0; color: #dc3545; font-weight: bold;">{{ $daysOverdue }}</td>
                    </tr>
                </table>
            </div>

            <div style="text-align: center;">
                <a href="{{ url('/admin/invoices/' . $invoice->id) }}" class="button">
                    View Invoice Details
                </a>
            </div>

            <div style="margin-top: 30px; padding: 20px; background-color: #f8d7da; border-radius: 5px;">
                <p style="margin: 0; font-size: 14px;">
                    <strong>⚠️ Required Action:</strong> Please follow up with the vendor immediately regarding this overdue payment.
                </p>
            </div>

            <div class="footer">
                <p>This is an automated alert from the REEDS Africa QR Feeding System.</p>
                <p>© {{ date('Y') }} REEDS Africa Talent Gateway. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
