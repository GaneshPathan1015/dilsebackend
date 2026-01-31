<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Confirmation - DILSE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.8;
            color: #2b2b2b;
            background: linear-gradient(180deg, #f7f7ff 0%, #f4f4f4 100%);
            padding: 20px 0;
        }

        .container {
            max-width: 650px;
            margin: 0 auto;
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.06);
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #8B4513 0%, #D4AF37 45%, #A0522D 100%);
            color: white;
            padding: 35px 20px;
            text-align: center;
            position: relative;
        }

        .header::after {
            content: "";
            position: absolute;
            bottom: -30px;
            left: 0;
            width: 100%;
            height: 60px;
            background: radial-gradient(circle at top, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0) 70%);
        }

        .header h1 {
            font-size: 30px;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .header p {
            font-size: 15px;
            opacity: 0.95;
        }

        /* Content */
        .content {
            padding: 35px 28px 30px;
        }

        .highlight {
            color: #8B4513;
            font-weight: 700;
        }

        /* Order ID */
        .order-id {
            background: linear-gradient(135deg, #e8f4fc 0%, #f0f7ff 100%);
            padding: 14px;
            border-radius: 10px;
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            margin: 18px 0 22px;
            border: 1px solid rgba(0,0,0,0.06);
            color: #0d3b66;
        }

        /* Info box */
        .info-box {
            background: linear-gradient(135deg, #e8f5e9 0%, #f3fff6 100%);
            border-left: 5px solid #4CAF50;
            padding: 16px 18px;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: 0 6px 16px rgba(76,175,80,0.08);
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .badge-success {
            background: rgba(76, 175, 80, 0.15);
            color: #2e7d32;
        }

        .badge-warning {
            background: rgba(255, 152, 0, 0.18);
            color: #e65100;
        }

        /* Order details card */
        .order-details {
            background: linear-gradient(135deg, #fff7e6 0%, #fff 100%);
            border-radius: 14px;
            padding: 18px;
            margin: 20px 0;
            border: 1px solid rgba(0,0,0,0.06);
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 18px 0 10px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.06);
        }

        .items-table th {
            background: linear-gradient(135deg, #8B4513 0%, #D4AF37 100%);
            color: white;
            padding: 13px;
            text-align: left;
            font-size: 14px;
        }

        .items-table td {
            padding: 12px 13px;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            font-size: 14px;
            background: #fff;
        }

        .items-table tr:nth-child(even) td {
            background: #faf7f2;
        }

        /* Total Section */
        .total-section {
            background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);
            padding: 20px;
            border-radius: 14px;
            margin-top: 18px;
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 8px 18px rgba(13, 59, 102, 0.06);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 15px;
        }

        .grand-total {
            font-size: 20px;
            font-weight: 800;
            color: #8B4513;
            border-top: 2px dashed rgba(139, 69, 19, 0.45);
            padding-top: 14px;
            margin-top: 10px;
        }

        /* Button */
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #8B4513 0%, #D4AF37 100%);
            color: white !important;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 999px;
            margin: 10px 0;
            font-weight: 700;
            letter-spacing: 0.3px;
            box-shadow: 0 10px 20px rgba(139, 69, 19, 0.22);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(139, 69, 19, 0.28);
        }

        /* Cards */
        .card {
            margin-top: 26px;
            padding: 18px;
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 8px 18px rgba(0,0,0,0.06);
        }

        .card-title {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 10px;
            color: #0d3b66;
        }

        .muted {
            color: #666;
            font-size: 14px;
        }

        .currency {
            font-family: 'Arial', sans-serif;
            margin-right: 2px;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #1f1f1f 0%, #333 100%);
            color: white;
            padding: 22px 18px;
            text-align: center;
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

        @media (max-width: 600px) {
            .container { width: 100%; border-radius: 0; }
            .content { padding: 25px 18px; }
            .header h1 { font-size: 26px; }
        }
    </style>
</head>

<body>
<div class="container">

    <!-- Header -->
    <div class="header">
        <h1>🎉 Order Confirmed!</h1>
        <p>Thank you for shopping with DILSE ✨</p>
    </div>

    <!-- Content -->
    <div class="content">
        <p>Dear <span class="highlight">{{ $customer_name }}</span>,</p>

        <p style="margin-top: 10px;">
            Your order has been successfully received and is now being processed.
            Below are your order details:
        </p>

        <div class="order-id">
            Order Number: {{ $order_id }}
        </div>

        <div class="info-box">
            <p><strong>📄 Invoice Number:</strong> {{ $order->invoice_number ?? 'Not Generated' }}</p>
            <p><strong>📅 Invoice Date:</strong> {{ $order->invoice_date ? $order->invoice_date->format('d/m/Y') : 'Not Set' }}</p>
            <p><strong>📦 Order Status:</strong>
                <span class="badge badge-success">{{ $order_status }}</span>
            </p>
            <p><strong>💳 Payment Method:</strong> {{ $payment_method }}</p>
            <p><strong>✅ Payment Status:</strong>
                @if($payment_status == 'Paid')
                    <span class="badge badge-success">{{ $payment_status }}</span>
                @else
                    <span class="badge badge-warning">{{ $payment_status }}</span>
                @endif
            </p>
        </div>

        <!-- Order Items -->
        @if(!empty($items))
            <h3 style="margin-top: 18px;">🛍️ Order Summary</h3>
            <table class="items-table">
                <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>{{ $item['name'] ?? $item['title'] ?? 'Product' }}</td>
                        <td>{{ $item['quantity'] ?? 1 }}</td>
                        <td><span class="currency">₹</span>{{ number_format($item['price'] ?? 0, 2) }}</td>
                        <td><span class="currency">₹</span>{{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        <!-- Total Section -->
        <div class="total-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span><span class="currency">₹</span>{{ number_format($total_amount, 2) }}</span>
            </div>

            @if(isset($coupon_discount) && $coupon_discount > 0)
                <div class="total-row" style="color: #2e7d32; font-weight: 700;">
                    <span>Coupon Discount ({{ $coupon_code ?? 'Coupon' }}):</span>
                    <span>-<span class="currency">₹</span>{{ number_format($coupon_discount, 2) }}</span>
                </div>
            @endif

            @if($order->shipping_cost > 0)
                <div class="total-row">
                    <span>Shipping:</span>
                    <span><span class="currency">₹</span>{{ number_format($order->shipping_cost, 2) }}</span>
                </div>
            @endif

            <div class="total-row grand-total">
                <span>Grand Total:</span>
                <span><span class="currency">₹</span>{{ number_format($order->grand_total ?? $order->total_price, 2) }}</span>
            </div>
        </div>

        <!-- Shipping Address -->
        @if(!empty($shipping_address))
            <div class="card">
                <div class="card-title">📬 Shipping Address</div>
                <p class="muted">
                    {{ $shipping_address['address_line1'] ?? '' }}<br>
                    {{ $shipping_address['address_line2'] ?? '' }}<br>
                    {{ $shipping_address['city'] ?? '' }}, {{ $shipping_address['state'] ?? '' }}
                    {{ $shipping_address['pincode'] ?? $shipping_address['zip_code'] ?? '' }}<br>
                    {{ $shipping_address['country'] ?? 'India' }}
                </p>
            </div>
        @endif

        <!-- Invoice Information -->
        <div class="card" style="text-align: center; background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);">
            <div class="card-title">📄 Invoice Details</div>
            <p class="muted" style="margin: 10px 0;">
                <strong>Invoice Number:</strong> {{ $order->invoice_number ?? 'Will be generated soon' }}<br>
                <strong>Invoice Date:</strong> {{ $order->invoice_date ? $order->invoice_date->format('d F Y') : 'Pending' }}
            </p>
            <p style="color: #0d3b66; font-size: 13px; font-weight: 700;">
                A detailed invoice PDF is attached to this email.
            </p>
        </div>

        <!-- Next Steps -->
        <div style="margin-top: 28px; text-align: center;">
            <p class="muted">You can track your order status by logging into your account.</p>
            <a href="{{ url('/my-orders') }}" class="button">View Your Order</a>
        </div>

        <p style="margin-top: 25px;">
            Need help? Contact our customer support:<br>
            📞 <strong>+91 98765 43210</strong> | 📧 <strong>support@dilse.com</strong>
        </p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>© {{ date('Y') }} DILSE. All rights reserved.</p>
        <p>Luxury Diamonds & Fine Jewelry ✨</p>
        <p>123 Jewel Street, Mumbai, India</p>
        <small>This is an automated email. Please do not reply to this message.</small>
    </div>

</div>
</body>
</html>
