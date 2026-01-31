<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Invoice - DILSE</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.7;
            background: linear-gradient(180deg, #f7f7ff 0%, #f4f4f4 100%);
            padding: 20px 0;
            color: #2b2b2b;
        }

        .container {
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.06);
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #8B4513 0%, #D4AF37 45%, #A0522D 100%);
            padding: 30px 20px;
            text-align: center;
            color: #fff;
        }

        .header h2 {
            font-size: 26px;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.95;
        }

        /* Content */
        .content {
            padding: 30px 25px;
            background-color: #ffffff;
        }

        .title {
            font-size: 18px;
            font-weight: 800;
            color: #0d3b66;
            margin-bottom: 12px;
        }

        .summary-box {
            margin-top: 18px;
            background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);
            border-radius: 14px;
            padding: 18px;
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 8px 18px rgba(13, 59, 102, 0.06);
        }

        .summary-box ul {
            list-style: none;
            padding-left: 0;
            margin-top: 10px;
        }

        .summary-box li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed rgba(0,0,0,0.08);
            font-size: 14px;
        }

        .summary-box li:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: 700;
            color: #444;
        }

        .value {
            font-weight: 600;
            color: #111;
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-success {
            background: rgba(76, 175, 80, 0.15);
            color: #2e7d32;
        }

        .badge-warning {
            background: rgba(255, 152, 0, 0.18);
            color: #e65100;
        }

        .note-box {
            margin-top: 18px;
            background: linear-gradient(135deg, #fff7e6 0%, #fff 100%);
            border-radius: 14px;
            padding: 16px;
            border: 1px solid rgba(0,0,0,0.06);
            color: #5a3b1f;
            font-size: 14px;
        }

        .currency {
            font-family: Arial, sans-serif;
            margin-right: 2px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px 15px;
            background: linear-gradient(135deg, #1f1f1f 0%, #333 100%);
            color: rgba(255,255,255,0.9);
            font-size: 13px;
        }

        .footer p {
            margin: 3px 0;
        }

        .footer small {
            display: block;
            margin-top: 10px;
            color: rgba(255,255,255,0.65);
            font-size: 12px;
        }

        @media(max-width: 600px) {
            .container { width: 100%; border-radius: 0; }
            .content { padding: 25px 18px; }
        }
    </style>
</head>

<body>
<div class="container">

    <!-- Header -->
    <div class="header">
        <h2>DILSE ✨</h2>
        <p>Order Invoice</p>
    </div>

    <!-- Content -->
    <div class="content">
        <p>Hello <strong>{{ $order->user_name }}</strong>,</p>

        <p style="margin-top: 10px;">
            Your order <strong>#{{ $order->order_id }}</strong> has been processed successfully.
        </p>

        <div class="summary-box">
            <div class="title">📦 Order Summary</div>

            <ul>
                <li>
                    <span class="label">Order ID</span>
                    <span class="value">{{ $order->order_id }}</span>
                </li>

                <li>
                    <span class="label">Invoice Number</span>
                    <span class="value">{{ $order->invoice_number ?? 'Not Generated' }}</span>
                </li>

                <li>
                    <span class="label">Invoice Date</span>
                    <span class="value">{{ $order->invoice_date ? $order->invoice_date->format('d/m/Y') : 'Not Set' }}</span>
                </li>

                <li>
                    <span class="label">Order Date</span>
                    <span class="value">{{ $order->created_at->format('d/m/Y') }}</span>
                </li>

                <li>
                    <span class="label">Total Amount</span>
                    <span class="value">
                        <span class="currency">₹</span>{{ number_format($order->total_price + $order->shipping_cost - $order->discount, 2) }}
                    </span>
                </li>

                <li>
                    <span class="label">Status</span>
                    <span class="value">
                        @if(ucfirst($order->order_status) == 'Completed' || ucfirst($order->order_status) == 'Delivered')
                            <span class="badge badge-success">{{ ucfirst($order->order_status) }}</span>
                        @else
                            <span class="badge badge-warning">{{ ucfirst($order->order_status) }}</span>
                        @endif
                    </span>
                </li>
            </ul>
        </div>

        <div class="note-box">
            📄 The PDF invoice is attached to this email.  
            Please keep it for your records.
        </div>

        <p style="margin-top: 18px;">
            Thank you,<br>
            <strong>DILSE Jewels</strong>
        </p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>© {{ date('Y') }} DILSE. All rights reserved.</p>
        <p>Luxury Diamonds & Fine Jewelry</p>
        <small>This is an automated message, please do not reply.</small>
    </div>

</div>
</body>
</html>
