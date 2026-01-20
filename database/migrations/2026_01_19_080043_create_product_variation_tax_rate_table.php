<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_variation_tax_rate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variation_id')->constrained('product_variations')->onDelete('cascade');
            $table->foreignId('tax_rate_id')->constrained('tax_rates')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['product_variation_id', 'tax_rate_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_variation_tax_rate');
    }
};