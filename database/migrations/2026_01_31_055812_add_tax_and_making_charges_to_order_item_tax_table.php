<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_item_taxes', function (Blueprint $table) {
            //
            // 1. Remove the old row-based logic columns
            $table->dropColumn(['component_type', 'tax_type']);

            // 2. Add specific Tax columns after the existing tax_amount
            $table->decimal('cgst', 15, 2)->default(0.00)->after('tax_amount');
            $table->decimal('sgst', 15, 2)->default(0.00)->after('cgst');
            $table->decimal('igst', 15, 2)->default(0.00)->after('sgst');

            // 3. Add Making Charges and their specific GST breakdown
            $table->decimal('making_charges', 15, 2)->default(0.00)->after('igst');
            $table->decimal('cgst_on_making', 15, 2)->default(0.00)->after('making_charges');
            $table->decimal('sgst_on_making', 15, 2)->default(0.00)->after('cgst_on_making');
            $table->decimal('igst_on_making', 15, 2)->default(0.00)->after('sgst_on_making');
            $table->decimal('tax_on_making', 15, 2)->default(0.00)->after('igst_on_making');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_item_taxes', function (Blueprint $table) {
            //
            // Restore the original columns if rolled back
            $table->enum('component_type', ['diamond_tax', 'gold_tax', 'making_tax'])->after('product_variation_id');
            $table->enum('tax_type', ['CGST', 'SGST', 'IGST'])->after('hsn_code');


            // Remove the newly added columns
            $table->dropColumn([
                'cgst',
                'sgst',
                'igst',
                'making_charges',
                'cgst_on_making',
                'sgst_on_making',
                'igst_on_making',
                'tax_on_making',
            ]);
        });
    }
};
