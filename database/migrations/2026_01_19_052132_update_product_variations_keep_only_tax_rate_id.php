<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('product_variations', function (Blueprint $table) {

            // 🔴 Drop all unwanted columns
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

            // 🟢 Add tax_rate_id foreign key
            $table->unsignedBigInteger('tax_rate_id')->nullable()->after('weight');

            $table->foreign('tax_rate_id')
                  ->references('id')
                  ->on('tax_rates')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('product_variations', function (Blueprint $table) {

            // 🔴 Remove foreign key & column
            $table->dropForeign(['tax_rate_id']);
            $table->dropColumn('tax_rate_id');

            // 🟢 Restore old columns (rollback support)
            $table->decimal('gold_weight', 10, 3)->nullable();
            $table->enum('gold_purity', ['24K', '22K', '18K', '14K'])->nullable();
            $table->decimal('gold_rate_per_gm', 10, 2)->nullable();
            $table->decimal('gold_value', 15, 2)->nullable();
            $table->decimal('diamond_rate_per_carat', 15, 2)->nullable();
            $table->decimal('diamond_value', 15, 2)->nullable();
            $table->decimal('making_charges', 15, 2)->nullable();
            $table->decimal('total_gst', 15, 2)->nullable();
            $table->decimal('cgst', 15, 2)->nullable();
            $table->decimal('sgst', 15, 2)->nullable();
            $table->decimal('igst', 15, 2)->nullable();
            $table->json('tax_breakdown')->nullable();
            $table->string('hsn_code_gold')->nullable();
            $table->string('hsn_code_diamond')->nullable();
            $table->string('hsn_code_making')->nullable();
        });
    }
};
