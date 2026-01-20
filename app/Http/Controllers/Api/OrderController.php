<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Coupon;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Mail;



class OrderController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $orders = Order::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($orders);
    }

    public function show($id)
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $order = Order::where('user_id', $userId)->where('id', $id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Calculate current quantities from items_id
        $currentQuantities = [
            'diamond' => 0,
            'jewelry' => 0,
            'gift'    => 0,
            'build' => 0,
            'combo' => 0,
            'total' => 0
        ];

        if ($order->items_id && is_array($order->items_id)) {
            foreach ($order->items_id as $type => $items) {
                if (is_array($items)) {
                    foreach ($items as $item) {
                        $itemQuantity = $item['quantity'] ?? 1;
                        $currentQuantities[$type] += $itemQuantity;
                        $currentQuantities['total'] += $itemQuantity;
                    }
                }
            }
        }

        // If items_id se total 0 aaya hai, to old method use karen
        if ($currentQuantities['total'] === 0) {
            try {
                $itemDetails = json_decode($order->item_details, true);
                $items = $itemDetails['items'] ?? [];

                foreach ($items as $item) {
                    $currentQuantities['total'] += $item['itemQuantity'] ?? 1;

                    // Type-wise quantity bhi calculate karen
                    $productType = $item['productType'] ?? 'jewelry';
                    if (isset($currentQuantities[$productType])) {
                        $currentQuantities[$productType] += $item['itemQuantity'] ?? 1;
                    }
                }
            } catch (\Exception $e) {
                // Fallback: agar parse nahi ho paya to 1 consider karen
                $currentQuantities['total'] = 1;
                $currentQuantities['jewelry'] = 1;
            }
        }

        $order->current_quantities = $currentQuantities;

        return response()->json($order);
    }

    public function cancel(Request $request, $id)
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $order = Order::where('user_id', $userId)->where('id', $id)->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        // Validate cancellation reason
        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string|min:10|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if order can be cancelled
        if (!$order->canBeCancelled()) {
            return response()->json([
                'status' => 'error',
                'message' => $order->cancellation_message
            ], 400);
        }

        try {
            if (!empty($order->coupon_code)) {
                $coupon = \App\Models\Coupon::where('code', $order->coupon_code)->first();
                if ($coupon && $coupon->used_count > 0) {
                    \DB::transaction(function () use ($coupon) {
                        $coupon->decrement('used_count');
                    });
                }
            }

            // Cancel the order
            $success = $order->cancel($request->cancellation_reason);

            if ($success) {
                // Reload the order with fresh data
                $order->refresh();

                $response = [
                    'status' => 'success',
                    'message' => 'Order cancelled successfully',
                    'order' => $order
                ];

                // Add refund information for online payments
                if ($order->payment_mode !== 'cod') {
                    $response['refund_processed'] = true;
                    $response['message'] .= '. Refund has been initiated for your payment.';
                }

                return response()->json($response);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to cancel order'
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error("Order cancellation failed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error cancelling order: ' . $e->getMessage()
            ], 500);
        }
    }

    // Mark order as delivered (for admin use)
    public function markDelivered($id)
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $order = Order::where('user_id', $userId)->where('id', $id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        try {
            $order->markAsDelivered();

            return response()->json([
                'status' => 'success',
                'message' => 'Order marked as delivered',
                'order' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error marking order as delivered: ' . $e->getMessage()
            ], 500);
        }
    }

    // Mark order as shipped (for admin use)
    public function markShipped($id)
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $order = Order::where('user_id', $userId)->where('id', $id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        try {
            $success = $order->markAsShipped();

            if ($success) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Order marked as shipped',
                    'order' => $order
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot mark order as shipped. Current status: ' . $order->order_status
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error marking order as shipped: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get cancellation eligibility
    public function getCancellationInfo($id)
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $order = Order::where('user_id', $userId)->where('id', $id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'can_be_cancelled' => $order->canBeCancelled(),
                'cancellation_message' => $order->cancellation_message,
                'order_status' => $order->order_status,
                'payment_mode' => $order->payment_mode,
                'payment_status' => $order->payment_status
            ]
        ]);
    }

    // New method to get quantity summary for all orders
    public function getQuantitySummary(Request $request)
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $orders = Order::where('user_id', $userId)->get();

        $summary = [
            'total_orders' => $orders->count(),
            'total_items' => 0,
            'by_type' => [
                'diamond' => 0,
                'jewelry' => 0,
                'gift' => 0,
                'build' => 0,
                'combo' => 0
            ],
            'by_status' => []
        ];

        foreach ($orders as $order) {
            $summary['total_items'] += $order->total_quantity ?? 0;

            // Count by product type
            if ($order->quantities && is_array($order->quantities)) {
                foreach ($order->quantities as $type => $qty) {
                    if (in_array($type, ['diamond', 'jewelry', 'gift', 'build', 'combo'])) {
                        $summary['by_type'][$type] += $qty;
                    }
                }
            }

            // Count by order status
            $status = $order->order_status ?? 'unknown';
            if (!isset($summary['by_status'][$status])) {
                $summary['by_status'][$status] = 0;
            }
            $summary['by_status'][$status]++;
        }

        return response()->json($summary);
    }

    public function store(Request $request)
    {
        Log::info('🔄 ======= API ORDER CREATION STARTED =======');
        Log::info('📝 Request Data:', $request->all());

        try {
            // Step 1: Validate Request
            $validator = Validator::make($request->all(), [
                'user_id'        => 'required|exists:users,id',
                'user_name'      => 'required|string|max:255',
                'contact_number' => 'required|string|max:20',
                'item_details'   => 'required|json',
                'total_price'    => 'required|numeric|min:0',
                'address'        => 'required|json',
                'billing_address' => 'nullable|json',
                'order_status'   => 'required|string|in:pending,processing,confirmed',
                'payment_mode'   => 'required|string|in:cod,online,card,wallet',
                'payment_status' => 'required|string|in:pending,paid,failed',
                'transaction_id' => 'nullable|string',
                'razorpay_payment_id' => 'nullable|string',
                'razorpay_order_id' => 'nullable|string',
                'is_gift'        => 'nullable|boolean',
                'notes'          => 'nullable|string|max:500',
                'coupon_discount' => 'nullable|numeric|min:0',
                'coupon_code'    => 'nullable|string|max:50',
                'product_type'   => 'required|string',
                'total_quantity' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                Log::error('❌ Validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Generate unique Order ID
            $validated['order_id'] = 'TCC' . date('YmdHis') . strtoupper(Str::random(4));

            // Set default values
            if (empty($validated['billing_address'])) {
                $validated['billing_address'] = $validated['address'];
            }

            // Set items_id and quantities (can be empty arrays)
            $validated['items_id'] = json_encode([]);
            $validated['quantities'] = json_encode(['total' => $validated['total_quantity']]);

            Log::info('✅ Validation passed, preparing order data...');
            Log::info('📦 Order ID generated: ' . $validated['order_id']);

            // ✅ IMPORTANT: CREATE ORDER WITHOUT DB TRANSACTION FIRST
            $order = Order::create($validated);

            Log::info('🎉 Order created successfully in database: ' . $order->id);
            Log::info('📊 Order Details:', [
                'order_id' => $order->order_id,
                'user_id' => $order->user_id,
                'total_price' => $order->total_price,
                'payment_mode' => $order->payment_mode
            ]);

            // ✅ NOW SEND EMAILS AFTER SUCCESSFUL ORDER CREATION
            $emailResult = $this->sendOrderEmailsImmediately($order);

            Log::info('📧 Email sending result: ' . ($emailResult ? 'SUCCESS' : 'FAILED'));
            Log::info('✅ ======= ORDER CREATION COMPLETED =======');

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully!',
                'order_id' => $order->order_id,
                'order' => $order,
                'email_sent' => $emailResult
            ], 201);
        } catch (\Exception $e) {
            Log::error('❌ ORDER CREATION FAILED: ' . $e->getMessage());
            Log::error('🔍 Error Trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create order',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    private function sendOrderEmailsImmediately(Order $order)
    {
        Log::info('📧 ======= SENDING ORDER EMAILS STARTED =======');

        try {

            $customerEmail = $this->getCustomerEmail($order);

            $adminEmail = $this->getAdminEmail();

            Log::info('👤 Customer Email: ' . ($customerEmail ?? 'NOT FOUND'));
            Log::info('👨‍💼 Admin Email: ' . $adminEmail);

            $emailSentCount = 0;

            if ($adminEmail) {
                $adminSent = $this->sendSingleEmail(
                    $adminEmail,
                    'admin.DiamondMaster.emails.order_notification',
                    '🆕 New Order Recive - ' . $order->order_id . ' - The Carat Casa',
                    $order,
                    'admin'
                );

                if ($adminSent) {
                    $emailSentCount++;
                    Log::info('✅ Admin email sent successfully');
                }
            }

            if ($customerEmail) {
                $customerSent = $this->sendSingleEmail(
                    $customerEmail,
                    'admin.DiamondMaster.emails.order_confirmation',
                    '✅ Your Confirm Order - #' . $order->order_id . ' - The Carat Casa',
                    $order,
                    'customer'
                );

                if ($customerSent) {
                    $emailSentCount++;
                    Log::info('✅ Customer email sent successfully');
                }
            }

            Log::info('📧 Total emails sent: ' . $emailSentCount);
            Log::info('✅ ======= EMAIL SENDING COMPLETED =======');

            return $emailSentCount > 0;
        } catch (\Exception $e) {
            Log::error('❌ Email sending failed: ' . $e->getMessage());
            Log::error('🔍 Email Error Trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    private function sendSingleEmail($toEmail, $view, $subject, $order, $type)
    {
        try {
            // Prepare email data
            $emailData = $this->prepareEmailData($order, $type);

            // Send email
            Mail::send($view, $emailData, function ($message) use ($toEmail, $subject, $order, $type) {
                $message->to($toEmail)
                    ->subject($subject);

                if ($type === 'admin') {
                    $message->cc(env('SALES_EMAIL', 'sales@thecaratcasa.com'));
                }
            });

            // Check for failures
            if (Mail::failures()) {
                Log::warning('⚠️ Mail failures for ' . $type . ' email: ' . json_encode(Mail::failures()));
                return false;
            }

            Log::info('📨 ' . ucfirst($type) . ' email sent to: ' . $toEmail);
            return true;
        } catch (\Exception $e) {
            Log::error('❌ Error sending ' . $type . ' email: ' . $e->getMessage());
            return false;
        }
    }

    private function getCustomerEmail(Order $order)
    {
        try {
            $user = User::find($order->user_id);
            if ($user && !empty($user->email)) {
                Log::info('👤 Found customer email in users table: ' . $user->email);
                return $user->email;
            }

            if ($order->address) {
                $address = json_decode($order->address, true);
                if (is_array($address) && isset($address['email']) && !empty($address['email'])) {
                    Log::info('📭 Found customer email in address: ' . $address['email']);
                    return $address['email'];
                }
            }

            $contact = $order->contact_number ?? '';
            if (!empty($contact) && strlen($contact) >= 10) {
                $fallbackEmail = 'customer' . substr($contact, -10) . '@thecaratcasa.com';
                Log::info('📞 Created fallback email: ' . $fallbackEmail);
                return $fallbackEmail;
            }

            Log::warning('⚠️ No customer email found for order: ' . $order->order_id);
            return null;
        } catch (\Exception $e) {
            Log::error('❌ Error getting customer email: ' . $e->getMessage());
            return null;
        }
    }

    private function getAdminEmail()
    {
        // Priority: ADMIN_EMAIL → MAIL_FROM_ADDRESS → default
        $adminEmail = env('ADMIN_EMAIL', env('MAIL_FROM_ADDRESS', 'admin@thecaratcasa.com'));

        // Validate email
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            Log::error('❌ Invalid admin email: ' . $adminEmail);
            return 'admin@thecaratcasa.com'; // Fallback
        }

        return $adminEmail;
    }

    private function prepareEmailData(Order $order, $type = 'customer')
    {
        $data = [
            'order' => $order,
            'order_id' => $order->order_id,
            'customer_name' => $order->user_name,
            'customer_phone' => $order->contact_number,
            'order_date' => $order->created_at->format('d F Y, h:i A'),
            'total_amount' => number_format($order->total_price, 2),
            'payment_method' => strtoupper($order->payment_mode),
            'payment_status' => ucfirst($order->payment_status),
            'order_status' => ucfirst($order->order_status),
            'email_type' => $type
        ];

        if ($order->item_details) {
            $items = json_decode($order->item_details, true);
            $data['items'] = $items['items'] ?? [];

            // Log items for debugging
            if (!empty($data['items'])) {
                Log::info('📦 Order items for email:', $data['items']);
            }
        }

        if ($order->address) {
            $address = json_decode($order->address, true);
            $data['shipping_address'] = is_array($address) ? $address : [];
        }

        if ($order->coupon_discount > 0) {
            $data['coupon_discount'] = number_format($order->coupon_discount, 2);
            $data['coupon_code'] = $order->coupon_code;
        }


        if ($type === 'admin') {
            $user = User::find($order->user_id);
            $data['user'] = $user;
            $data['user_email'] = $user->email ?? 'Not available';
        }

        return $data;
    }

    public function testEmailSystem(Request $request)
    {
        try {
            Log::info('🧪 ======= EMAIL SYSTEM TEST STARTED =======');

            // Get order ID from request or use latest
            $orderId = $request->get('order_id');

            if ($orderId) {
                $order = Order::where('order_id', $orderId)->first();
                if (!$order) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Order not found with ID: ' . $orderId
                    ], 404);
                }
            } else {
                $order = Order::latest()->first();
                if (!$order) {
                    // Create test order
                    $order = Order::create([
                        'order_id' => 'TCC-TEST-' . date('YmdHis'),
                        'user_id' => 1,
                        'user_name' => 'Test Customer',
                        'contact_number' => '9876543210',
                        'item_details' => json_encode(['items' => [
                            ['name' => 'Gold Ring Test', 'price' => 15000, 'quantity' => 1],
                            ['name' => 'Diamond Earrings Test', 'price' => 25000, 'quantity' => 2]
                        ]]),
                        'total_price' => 65000,
                        'address' => json_encode([
                            'address_line1' => 'Test Address',
                            'city' => 'Mumbai',
                            'state' => 'Maharashtra',
                            'pincode' => '400001',
                            'email' => 'test@example.com'
                        ]),
                        'order_status' => 'pending',
                        'payment_mode' => 'cod',
                        'payment_status' => 'pending',
                        'product_type' => 'jewelry',
                        'total_quantity' => 3
                    ]);
                    Log::info('📝 Test order created: ' . $order->order_id);
                }
            }

            Log::info('📧 Testing email for order: ' . $order->order_id);

            // Send emails
            $result = $this->sendOrderEmailsImmediately($order);

            return response()->json([
                'status' => 'success',
                'message' => $result ? 'Test emails sent successfully' : 'Email sending failed',
                'order_id' => $order->order_id,
                'emails_sent' => $result,
                'admin_view' => 'admin.DiamondMaster.emails.order_notification',
                'customer_view' => 'admin.DiamondMaster.emails.order_confirmation',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Email test failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkEmailConfig(Request $request)
    {
        try {
            Log::info('🔧 Checking email configuration...');

            $config = [
                'MAIL_MAILER' => env('MAIL_MAILER'),
                'MAIL_HOST' => env('MAIL_HOST'),
                'MAIL_PORT' => env('MAIL_PORT'),
                'MAIL_USERNAME' => substr(env('MAIL_USERNAME', ''), 0, 3) . '...', // Hide full email
                'MAIL_ENCRYPTION' => env('MAIL_ENCRYPTION'),
                'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS'),
                'MAIL_FROM_NAME' => env('MAIL_FROM_NAME'),
                'ADMIN_EMAIL' => env('ADMIN_EMAIL'),
                'APP_ENV' => env('APP_ENV'),
                'APP_DEBUG' => env('APP_DEBUG')
            ];

            // Test email sending
            $testEmail = 'test@example.com';
            try {
                Mail::raw(
                    'Test email from The Carat Casa - ' . now()->format('Y-m-d H:i:s'),
                    function ($message) use ($testEmail) {
                        $message->to($testEmail)->subject('Test Email Config');
                    }
                );

                $config['mail_test'] = 'SUCCESS';
                Log::info('✅ Email configuration test passed');
            } catch (\Exception $e) {
                $config['mail_test'] = 'FAILED: ' . $e->getMessage();
                Log::error('❌ Email configuration test failed: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Email configuration check',
                'config' => $config,
                'views_exist' => [
                    'admin_notification' => view()->exists('admin.DiamondMaster.emails.order_notification'),
                    'customer_confirmation' => view()->exists('admin.DiamondMaster.emails.order_confirmation')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Config check failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
