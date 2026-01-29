<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'products_quantity',
                'products_model',
                'products_sku',
                'master_sku',
                'products_weight',
                'engraving_status',
                'catelog_no',
                'vendor_stock_no',
                'vendor_price',
                'is_bestseller',
                'is_new',
                'is_superdeals',
                'diamond_pics',
                'total_carat_weight',
                'semi_mount_price',
                'center_stone_price',
                'center_stone_weight',
                'metal_weight',
                'shape_ids',
                'is_matching_set',
                'product_promotion',
                'certified_lab',
                'certificate_number',
                'products_related_items',
                'related_master_sku',
                'default_size',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
        });
    }
};
