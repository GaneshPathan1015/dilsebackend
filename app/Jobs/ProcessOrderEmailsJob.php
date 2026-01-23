<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessOrderEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $delay = 10; // 10 seconds delay between emails

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle()
    {
        Log::info('📧 Processing email job for order: ' . $this->order->order_id);
        
        try {
            $this->sendOrderEmailsWithDelay();
        } catch (\Exception $e) {
            Log::error('❌ Email job failed: ' . $e->getMessage());
            // Retry after 30 seconds
            $this->release(30);
        }
    }

    private function sendOrderEmailsWithDelay()
    {
        Log::info('📧 ======= SENDING ORDER EMAILS STARTED =======');

        $customerEmail = $this->getCustomerEmail();
        $adminEmail = $this->getAdminEmail();

        Log::info('👤 Customer Email: ' . ($customerEmail ?? 'NOT FOUND'));
        Log::info('👨‍💼 Admin Email: ' . $adminEmail);

        $emailSentCount = 0;

        // Send admin email first
        if ($adminEmail) {
            $adminSent = $this->sendSingleEmail(
                $adminEmail,
                'admin.DiamondMaster.emails.order_notification',
                '🆕 New Order Received - ' . $this->order->order_id . ' - The Carat Casa',
                $this->order,
                'admin'
            );

            if ($adminSent) {
                $emailSentCount++;
                Log::info('✅ Admin email sent successfully');
            }
            
            // Wait 10 seconds before sending customer email (for Mailtrap rate limit)
            Log::info('⏳ Waiting 10 seconds for Mailtrap rate limit...');
            sleep(10);
        }

        // Send customer email
        if ($customerEmail) {
            $customerSent = $this->sendSingleEmail(
                $customerEmail,
                'admin.DiamondMaster.emails.order_confirmation',
                '✅ Order Confirmation - #' . $this->order->order_id . ' - The Carat Casa',
                $this->order,
                'customer'
            );

            if ($customerSent) {
                $emailSentCount++;
                Log::info('✅ Customer email sent successfully');
            }
        }

        Log::info('📧 Total emails sent: ' . $emailSentCount);
        Log::info('✅ ======= EMAIL SENDING COMPLETED =======');
    }

    private function sendSingleEmail($toEmail, $view, $subject, $order, $type)
    {
        try {
            $emailData = $this->prepareEmailData($order, $type);
            
            Mail::send($view, $emailData, function ($message) use ($toEmail, $subject, $type) {
                $message->to($toEmail)->subject($subject);
                
                if ($type === 'admin') {
                    $message->cc(env('SALES_EMAIL', 'sales@thecaratcasa.com'));
                }
            });

            Log::info('📨 ' . ucfirst($type) . ' email sent to: ' . $toEmail);
            return true;
        } catch (\Exception $e) {
            Log::error("❌ Email send failed ({$type}): " . $e->getMessage());
            return false;
        }
    }

    private function getCustomerEmail()
    {
        try {
            $user = User::find($this->order->user_id);
            if ($user && !empty($user->email)) {
                Log::info('✅ Found customer email in users table: ' . $user->email);
                return $user->email;
            }

            if ($this->order->address) {
                $address = json_decode($this->order->address, true);
                Log::info('📦 Address data for email:', $address);
                
                if (is_array($address) && isset($address['email']) && !empty($address['email'])) {
                    Log::info('✅ Found customer email in address: ' . $address['email']);
                    return $address['email'];
                }
            }

            $contact = $this->order->contact_number ?? '';
            if (!empty($contact) && strlen($contact) >= 10) {
                $fallbackEmail = 'customer' . substr($contact, -10) . '@thecaratcasa.com';
                Log::info('📞 Created fallback email: ' . $fallbackEmail);
                return $fallbackEmail;
            }

            Log::warning('⚠️ No customer email found for order: ' . $this->order->order_id);
            return null;
        } catch (\Exception $e) {
            Log::error('❌ Error getting customer email: ' . $e->getMessage());
            return null;
        }
    }

    private function getAdminEmail()
    {
        $adminEmail = env('ADMIN_EMAIL', env('MAIL_FROM_ADDRESS', 'admin@thecaratcasa.com'));
        
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            Log::error('❌ Invalid admin email: ' . $adminEmail);
            return 'admin@thecaratcasa.com';
        }

        return $adminEmail;
    }

    private function prepareEmailData($order, $type = 'customer')
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
}