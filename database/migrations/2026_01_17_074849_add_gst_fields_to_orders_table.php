<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {

            // GST Summary
            $table->decimal('total_gst', 15, 2)->default(0)->after('total_price');
            $table->decimal('total_cgst', 15, 2)->default(0)->after('total_gst');
            $table->decimal('total_sgst', 15, 2)->default(0)->after('total_cgst');
            $table->decimal('total_igst', 15, 2)->default(0)->after('total_sgst');

            // Component totals
            $table->decimal('gold_value', 15, 2)->default(0)->after('total_igst');
            $table->decimal('diamond_value', 15, 2)->default(0)->after('gold_value');
            $table->decimal('making_charges', 15, 2)->default(0)->after('diamond_value');
 
            // GST breakup JSON
            $table->json('gst_breakdown')->nullable()->after('making_charges');

            // Invoice fields
            $table->string('invoice_number')->nullable()->after('id');
            $table->date('invoice_date')->nullable()->after('invoice_number');
        });

        // Auto-generate invoice number for existing orders
        DB::statement("
            UPDATE orders 
            SET 
                invoice_number = CONCAT('INV-', LPAD(id, 6, '0')),
                invoice_date = DATE(created_at)
        ");
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'total_gst',
                'total_cgst',
                'total_sgst',
                'total_igst',
                'gold_value',
                'diamond_value',
                'making_charges',
                'gst_breakdown',
                'invoice_number',
                'invoice_date',
            ]);
        });
    }
};
