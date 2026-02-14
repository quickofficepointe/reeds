{{-- resources/views/emails/invoice-generation-summary.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Generation Summary</title>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        .stat-box {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #2596be;
            margin: 10px 0;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .success-rate {
            font-size: 48px;
            color: #28a745;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .error-list {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
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
            <h1> Invoice Generation Summary</h1>
            <p>{{ $date }}</p>
        </div>

        <div class="content">
            <div class="success-rate">
                {{ $successRate }}
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value">{{ $total }}</div>
                    <div class="stat-label">Total Vendors</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">{{ $generated }}</div>
                    <div class="stat-label">Invoices Generated</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">{{ $skipped }}</div>
                    <div class="stat-label">Skipped (No Transactions)</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">{{ count($errors) }}</div>
                    <div class="stat-label">Errors</div>
                </div>
            </div>

            @if(!empty($errors))
                <div class="error-list">
                    <h4 style="margin-top: 0;"> Errors Encountered:</h4>
                    <ul style="margin-bottom: 0;">
                        @foreach($errors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <strong> All invoices generated successfully!</strong> No errors were encountered.
                </div>
            @endif

            <div style="background-color: white; padding: 20px; border-radius: 5px; margin: 20px 0;">
                <h4 style="margin-top: 0;">📈 Summary</h4>
                <p>Successfully generated invoices for <strong>{{ $generated }}</strong> out of <strong>{{ $total }}</strong> vendors.</p>
                <p>Next invoice generation will occur in 14 days.</p>
            </div>

            <div class="footer">
                <p>This is an automated summary from the REEDS Africa QR Feeding System.</p>
                <p>© {{ date('Y') }} REEDS Africa Talent Gateway. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
