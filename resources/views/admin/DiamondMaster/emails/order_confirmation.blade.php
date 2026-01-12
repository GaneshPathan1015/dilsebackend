<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - {{ $order->order_id }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .logo i {
            color: #D4AF37;
            margin-right: 10px;
        }
        .content {
            padding: 30px;
        }
        .order-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #D4AF37;
        }
        .order-info {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .info-item {
            flex: 1;
            min-width: 200px;
            margin: 5px;
        }
        .order-items {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .order-items th {
            background: #8B4513;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .order-items td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .total-section {
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        .total-section h3 {
            color: #8B4513;
            margin-bottom: 10px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #8B4513, #A0522D);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #eee;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-confirmed {
            background: #28a745;
            color: white;
        }
        .status-pending {
            background: #ffc107;
            color: #000;
        }
        .badge-diamond {
            background: linear-gradient(135deg, #B9F2FF, #7ED4FF);
            color: #0D47A1;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
        }
        .badge-jewelry {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #5D4037;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-gem"></i> The Carat Casa
            </div>
            <h1>Order Confirmation</h1>
            <p>Thank you for your purchase!</p>
        </div>
        
        <div class="content">
            <p>Dear <strong>{{ $order->user_name }}</strong>,</p>
            
            <p>We're delighted to confirm that your order has been successfully placed. Here are your order details:</p>
            
            <div class="order-details">
                <div class="order-info">
                    <div class="info-item">
                        <strong>Order ID:</strong><br>
                        {{ $order->order_id }}
                    </div>
                    <div class="info-item">
                        <strong>Order Date:</strong><br>
                        {{ $order->created_at->format('F d, Y h:i A') }}
                    </div>
                    <div class="info-item">
                        <strong>Status:</strong><br>
                        <span class="status-badge status-confirmed">Confirmed</span>
                    </div>
                </div>
                
                <div class="order-info">
                    <div class="info-item">
                        <strong>Payment Method:</strong><br>
                        {{ ucfirst($order->payment_mode) }}
                    </div>
                    <div class="info-item">
                        <strong>Payment Status:</strong><br>
                        {{ ucfirst($order->payment_status) }}
                    </div>
                    <div class="info-item">
                        <strong>Contact:</strong><br>
                        {{ $order->contact_number }}
                    </div>
                </div>
            </div>
            
            <h3>Order Items:</h3>
            @if(!empty($processedItems))
            <table class="order-items">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($processedItems as $index => $item)
                    @php
                        $typeClass = ($item['type'] ?? '') === 'diamond' ? 'badge-diamond' : 'badge-jewelry';
                        $typeLabel = ($item['type'] ?? '') === 'diamond' ? 'Diamond' : 
                                   (($item['type'] ?? '') === 'jewelry' ? 'Jewelry' : 
                                   (($item['type'] ?? '') === 'combo' ? 'Combo' : 'Product'));
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $item['name'] ?? 'Product' }}</strong>
                            @if(($item['type'] ?? '') === 'diamond' && isset($item['certificate_number']))
                            <br><small>Cert: {{ $item['certificate_number'] }}</small>
                            @endif
                        </td>
                        <td><span class="{{ $typeClass }}">{{ $typeLabel }}</span></td>
                        <td>{{ $item['quantity'] ?? 1 }}</td>
                        <td>${{ number_format($item['price'] ?? 0, 2) }}</td>
                        <td>${{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
            
            <div class="total-section">
                <h3>Order Summary</h3>
                <p><strong>Items Total:</strong> ${{ number_format($order->total_price, 2) }}</p>
                @if($order->shipping_cost > 0)
                <p><strong>Shipping:</strong> ${{ number_format($order->shipping_cost, 2) }}</p>
                @endif
                @if($order->coupon_discount > 0)
                <p><strong>Coupon Discount ({{ $order->coupon_code }}):</strong> -${{ number_format($order->coupon_discount, 2) }}</p>
                @endif
                <p><strong style="font-size: 18px;">Grand Total:</strong> 
                   <strong style="font-size: 18px; color: #8B4513;">${{ number_format($order->grand_total, 2) }}</strong>
                </p>
            </div>
            
            <p><strong>Shipping Address:</strong></p>
            <p>{!! nl2br(e($order->formatted_address)) !!}</p>
            
            <p style="margin-top: 30px;">
                <strong>Order Status Updates:</strong><br>
                You will receive email updates as your order progresses. You can also track your order status by logging into your account.
            </p>
            
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ $downloadUrl }}" class="btn" target="_blank">
                    <i class="fas fa-download"></i> Download Invoice
                </a>
            </p>
            
            <p>If you have any questions about your order, please contact our customer service at <strong>support@thecaratcasa.com</strong> or call us at <strong>+1-800-CARAT-CASA</strong>.</p>
            
            <p>Thank you for choosing <strong>The Carat Casa</strong> for your luxury jewelry needs!</p>
            
            <p>Best regards,<br>
            <strong>The Carat Casa Team</strong></p>
        </div>
        
        <div class="footer">
            <p>The Carat Casa<br>
            Luxury Diamonds & Fine Jewelry<br>
            © {{ date('Y') }} All rights reserved</p>
            <p style="font-size: 12px; color: #666;">
                This is an automated email, please do not reply. For inquiries, contact: support@thecaratcasa.com
            </p>
        </div>
    </div>
</body>
</html>