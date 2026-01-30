<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_item_taxes', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            // ✅ Product (Jewelry)
            $table->unsignedInteger('product_id')->nullable();
            $table->foreign('product_id')
                  ->references('products_id')
                  ->on('products')
                  ->onDelete('set null');

            // ✅ Diamond
            $table->unsignedInteger('diamond_id')->nullable();
            $table->foreign('diamond_id')
                  ->references('diamondid')
                  ->on('diamond_master')
                  ->onDelete('set null');

            // ✅ Product Variation
            $table->foreignId('product_variation_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            // Tax breakup
            $table->enum('component_type', ['diamond_tax', 'gold_tax', 'making_tax']);
            $table->string('hsn_code', 20);

            $table->enum('tax_type', ['CGST', 'SGST', 'IGST']);
            $table->decimal('taxable_value', 15, 2);
            $table->decimal('tax_rate', 5, 2);
            $table->decimal('tax_amount', 15, 2);

            // Item details
            $table->string('item_name')->nullable();
            $table->string('item_type')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);

            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['order_id', 'component_type']);
            $table->index(['product_id']);
            $table->index(['diamond_id']);
        });
    }

    public function down()
    { 
        Schema::dropIfExists('order_item_taxes');
    }
};
