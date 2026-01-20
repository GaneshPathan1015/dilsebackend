<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('product_variations', function (Blueprint $table) {

            // Gold component
            $table->decimal('gold_weight', 10, 3)->nullable()->after('weight');
            $table->enum('gold_purity', ['24K', '22K', '18K', '14K'])->nullable()->after('gold_weight');
            $table->decimal('gold_rate_per_gm', 10, 2)->nullable()->after('gold_purity');
            $table->decimal('gold_value', 15, 2)->nullable()->after('gold_rate_per_gm');

            // Diamond component
            $table->decimal('diamond_rate_per_carat', 15, 2)->nullable()->after('gold_value');
            $table->decimal('diamond_value', 15, 2)->nullable()->after('diamond_rate_per_carat');

            // Making charges
            $table->decimal('making_charges', 15, 2)->nullable()->after('diamond_value');

            // GST totals
            $table->decimal('total_gst', 15, 2)->nullable()->after('making_charges');
            $table->decimal('cgst', 15, 2)->nullable()->after('total_gst');
            $table->decimal('sgst', 15, 2)->nullable()->after('cgst');
            $table->decimal('igst', 15, 2)->nullable()->after('sgst');

            // Tax breakup JSON
            $table->json('tax_breakdown')->nullable()->after('igst');

            // HSN codes
            $table->string('hsn_code_gold')->nullable()->after('tax_breakdown');
            $table->string('hsn_code_diamond')->nullable()->after('hsn_code_gold');
            $table->string('hsn_code_making')->nullable()->after('hsn_code_diamond');
        });
    }

    public function down()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn([
                'gold_weight',
                'gold_purity',
                'gold_rate_per_gm',
                'gold_value',
                'diamond_rate_per_carat',
                'diamond_value',
                'making_charges',
                'total_gst',
                'cgst',
                'sgst',
                'igst',
                'tax_breakdown',
                'hsn_code_gold',
                'hsn_code_diamond',
                'hsn_code_making',
            ]);
        }); 
    }
};
