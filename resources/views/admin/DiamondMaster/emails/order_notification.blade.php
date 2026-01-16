<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Order Received - The Carat Casa</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 700px; margin: 0 auto; background: #fff; }
        .header { background: #dc3545; color: white; padding: 25px; text-align: center; }
        .alert { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px; border-radius: 5px; }
        .content { padding: 30px; }
        .order-details { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .customer-info { background: #e7f3ff; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .items-table th { background: #343a40; color: white; padding: 12px; text-align: left; }
        .items-table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        .items-table tr:nth-child(even) { background: #f8f9fa; }
        .total-section { background: #e9ecef; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .footer { background: #343a40; color: white; padding: 20px; text-align: center; }
        .button { display: inline-block; background: #dc3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; }
        .highlight { color: #dc3545; font-weight: bold; }
        @media (max-width: 600px) { .container { width: 100%; } }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🆕 New Order Received!</h1>
            <p>Order #{{ $order_id }}</p>
        </div>

        <!-- Alert -->
        <div class="alert">
            <strong>⚠️ Action Required:</strong> A new order has been received and needs to be processed.
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Customer Info -->
            <div class="customer-info">
                <h3>👤 Customer Information</h3>
                <p><strong>Name:</strong> {{ $customer_name }}</p>
                <p><strong>Contact:</strong> {{ $customer_phone }}</p>
                <p><strong>Customer ID:</strong> {{ $order->user_id ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ optional($order->user)->email ?? $order->address_email ?? 'N/A' }}</p>
            </div>

            <!-- Order Details -->
            <div class="order-details">
                <h3>📋 Order Details</h3>
                <p><strong>Order ID:</strong> {{ $order_id }}</p>
                <p><strong>Order Date:</strong> {{ $order_date }}</p>
                <p><strong>Status:</strong> {{ $order_status }}</p>
                <p><strong>Payment Method:</strong> {{ $payment_method }}</p>
                <p><strong>Payment Status:</strong>
                    <span style="color: {{ $payment_status == 'Paid' ? '#28a745' : '#dc3545' }};">
                        {{ $payment_status }}
                    </span>
                </p>
                @if($order->transaction_id)
                <p><strong>Transaction ID:</strong> {{ $order->transaction_id }}</p>
                @endif
            </div>

            <!-- Order Items -->
            @if(!empty($items))
            <h3>🛍️ Order Items</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
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

            <!-- Totals -->
            <div class="total-section">
                <p><strong>Subtotal:</strong> ₹{{ $total_amount }}</p>

                @if(isset($coupon_discount) && $coupon_discount > 0)
                <p>
                    <strong>Coupon Discount:</strong> -₹{{ number_format($coupon_discount, 2) }}
                    (Code: {{ $coupon_code ?? 'N/A' }})
                </p>
                @endif

                @if($order->shipping_cost > 0)
                <p><strong>Shipping:</strong> ₹{{ number_format($order->shipping_cost, 2) }}</p>
                @endif

                @php
                    $grandTotal = $order->total_price;
                    if(isset($coupon_discount)) {
                        $grandTotal -= $coupon_discount;
                    }
                    if($order->shipping_cost > 0) {
                        $grandTotal += $order->shipping_cost;
                    }
                @endphp

                <p style="font-size: 18px; font-weight: bold; color: #dc3545;">
                    <strong>Grand Total:</strong> ₹{{ number_format($grandTotal, 2) }}
                </p>
            </div>

            <!-- Shipping Address -->
            @if(!empty($shipping_address))
            <div style="margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                <h3>📍 Shipping Address</h3>
                <p style="margin-top: 10px;">
                    {{ $shipping_address['address_line1'] ?? '' }}<br>
                    {{ $shipping_address['address_line2'] ?? '' }}<br>
                    {{ $shipping_address['city'] ?? '' }}, {{ $shipping_address['state'] ?? '' }}
                    {{ $shipping_address['pincode'] ?? $shipping_address['zip_code'] ?? '' }}<br>
                    {{ $shipping_address['country'] ?? 'India' }}
                </p>
            </div>
            @endif

            <!-- Action Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/admin/orders') }}" class="button">
                    📋 Process Order
                </a>
            </div>

            <p style="color: #6c757d; font-size: 14px;">
                This is an automated notification. Please process this order within 24 hours.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© {{ date('Y') }} The Carat Casa Admin System</p>
            <p>Order Received At: {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
