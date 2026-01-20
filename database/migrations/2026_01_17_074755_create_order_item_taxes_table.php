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

            $table->foreignId('order_item_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('product_variation_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Tax breakup
            $table->enum('component_type', ['diamond', 'gold', 'making']);
            $table->string('hsn_code', 20);

            $table->enum('tax_type', ['CGST', 'SGST', 'IGST']);
            $table->decimal('taxable_value', 15, 2);
            $table->decimal('tax_rate', 5, 2);
            $table->decimal('tax_amount', 15, 2);

            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['order_id', 'component_type']);
            $table->index(['order_item_id']);
        });
    }

    public function down()
    { 
        Schema::dropIfExists('order_item_taxes');
    }
};
