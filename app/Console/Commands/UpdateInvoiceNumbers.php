<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class UpdateInvoiceNumbers extends Command
{
    protected $signature = 'orders:update-invoices';
    protected $description = 'Update invoice numbers for existing orders';

    public function handle()
    {
        $orders = Order::whereNull('invoice_number')->get();
        
        $this->info("Found {$orders->count()} orders without invoice numbers.");
        
        $progressBar = $this->output->createProgressBar($orders->count());
        $progressBar->start();
        
        foreach ($orders as $order) {
            try {
                // Generate invoice number based on created date
                $yearMonth = $order->created_at->format('Y-m');
                
                // Count orders in that month with invoice numbers
                $invoiceCount = Order::whereYear('created_at', $order->created_at->year)
                                   ->whereMonth('created_at', $order->created_at->month)
                                   ->whereNotNull('invoice_number')
                                   ->count();
                
                $sequentialNumber = str_pad($invoiceCount + 1, 5, '0', STR_PAD_LEFT);
                $invoiceNumber = "TCC-INV-{$yearMonth}-{$sequentialNumber}";
                
                // Check if exists
                $counter = 1;
                while (Order::where('invoice_number', $invoiceNumber)->exists()) {
                    $sequentialNumber = str_pad($invoiceCount + 1 + $counter, 5, '0', STR_PAD_LEFT);
                    $invoiceNumber = "TCC-INV-{$yearMonth}-{$sequentialNumber}";
                    $counter++;
                    
                    if ($counter > 10) {
                        $randomSuffix = strtoupper(\Illuminate\Support\Str::random(3));
                        $invoiceNumber = "TCC-INV-{$yearMonth}-{$sequentialNumber}-{$randomSuffix}";
                        break;
                    }
                }
                
                // Update order
                $order->invoice_number = $invoiceNumber;
                $order->invoice_date = $order->created_at;
                $order->save();
                
                Log::info("Updated Order {$order->id} with Invoice: {$invoiceNumber}");
                
            } catch (\Exception $e) {
                Log::error("Error updating order {$order->id}: " . $e->getMessage());
                $this->error("Error updating order {$order->id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->info("Invoice details updated for {$orders->count()} orders.");
        
        return 0;
    }
}