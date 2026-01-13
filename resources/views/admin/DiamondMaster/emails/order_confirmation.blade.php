<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Confirmation - The Carat Casa</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.8; color: #333; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .content { padding: 30px; }
        .order-details { background: #f9f9f9; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .order-id { background: #e8f4fc; padding: 10px; border-radius: 5px; text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; }
        .info-box { background: #e8f5e9; border-left: 4px solid #4CAF50; padding: 15px; margin: 20px 0; }
        .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .items-table th { background: #667eea; color: white; padding: 12px; text-align: left; }
        .items-table td { padding: 12px; border-bottom: 1px solid #ddd; }
        .items-table tr:nth-child(even) { background: #f9f9f9; }
        .total-section { background: #f0f7ff; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .grand-total { font-size: 20px; font-weight: bold; color: #667eea; border-top: 2px solid #667eea; padding-top: 15px; }
        .footer { background: #333; color: white; padding: 20px; text-align: center; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .highlight { color: #667eea; font-weight: bold; }
        @media (max-width: 600px) { .container { width: 100%; } }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🎉 Order Confirmed!</h1>
            <p>Thank you for shopping with The Carat Casa</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p>Dear <span class="highlight">{{ $customer_name }}</span>,</p>
            
            <p>Your order has been successfully received and is now being processed. Below are your order details:</p>
            
            <div class="order-id">
                Order Number: {{ $order_id }}
            </div>
            
            <div class="info-box">
                <p><strong>📅 Order Date:</strong> {{ $order_date }}</p>
                <p><strong>📦 Order Status:</strong> <span style="color: #4CAF50;">{{ $order_status }}</span></p>
                <p><strong>💳 Payment Method:</strong> {{ $payment_method }}</p>
                <p><strong>✅ Payment Status:</strong>
                    <span style="color: {{ $payment_status == 'Paid' ? '#4CAF50' : '#ff9800' }};">
                        {{ $payment_status }}
                    </span>
                </p>
            </div>

            <!-- Order Items -->
            @if(!empty($items))
            <h3>🛍️ Order Summary</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item['name'] ?? $item['title'] ?? 'Product' }}</td>
                        <td>{{ $item['quantity'] ?? 1 }}</td>
                        <td>₹{{ number_format($item['price'] ?? 0, 2) }}</td>
                        <td>₹{{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif

            <!-- Total Section -->
            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>₹{{ $total_amount }}</span>
                </div>

                @if(isset($coupon_discount) && $coupon_discount > 0)
                <div class="total-row" style="color: #4CAF50;">
                    <span>Coupon Discount ({{ $coupon_code ?? 'Coupon' }}):</span>
                    <span>-₹{{ number_format($coupon_discount, 2) }}</span>
                </div>
                @endif

                @if($order->shipping_cost > 0)
                <div class="total-row">
                    <span>Shipping:</span>
                    <span>₹{{ number_format($order->shipping_cost, 2) }}</span>
                </div>
                @endif

                <div class="total-row grand-total">
                    <span>Grand Total:</span>
                    <span>₹{{ number_format($order->grand_total ?? $order->total_price, 2) }}</span>
                </div>
            </div>

            <!-- Shipping Address -->
            @if(!empty($shipping_address))
            <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 10px;">
                <h3>📬 Shipping Address</h3>
                <p style="margin-top: 10px;">
                    {{ $shipping_address['address_line1'] ?? '' }}<br>
                    {{ $shipping_address['address_line2'] ?? '' }}<br>
                    {{ $shipping_address['city'] ?? '' }}, {{ $shipping_address['state'] ?? '' }}
                    {{ $shipping_address['pincode'] ?? $shipping_address['zip_code'] ?? '' }}<br>
                    {{ $shipping_address['country'] ?? 'India' }}
                </p>
            </div>
            @endif

            <!-- Next Steps -->
            <div style="margin-top: 30px; text-align: center;">
                <p>You can track your order status by logging into your account.</p>
                <a href="{{ url('/my-orders') }}" class="button">View Your Order</a>
            </div>

            <p style="margin-top: 30px;">
                Need help? Contact our customer support:<br>
                📞 +91 98765 43210 | 📧 support@thecaratcasa.com
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© {{ date('Y') }} The Carat Casa. All rights reserved.</p>
            <p>123 Jewellery Street, Mumbai, India</p>
            <p style="font-size: 12px; color: #aaa; margin-top: 10px;">
                This is an automated email. Please do not reply to this message.
            </p>
        </div>
    </div>
</body>
</html>
