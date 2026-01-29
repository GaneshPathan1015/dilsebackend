<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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
                'certificate_number',
                'metal_type',
                'metal_color',
                'metal_purity',
                'stone_details',
                'size',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            // ✅ Rollback ke liye columns wapas add (optional)
            $table->decimal('total_gst', 15, 2)->default(0.00);
            $table->decimal('total_cgst', 15, 2)->default(0.00);
            $table->decimal('total_sgst', 15, 2)->default(0.00);
            $table->decimal('total_igst', 15, 2)->default(0.00);

            $table->decimal('gold_value', 15, 2)->default(0.00);
            $table->decimal('diamond_value', 15, 2)->default(0.00);
            $table->decimal('making_charges', 15, 2)->default(0.00);

            $table->longText('gst_breakdown')->nullable();

            $table->string('certificate_number')->nullable();
            $table->string('metal_type')->nullable();
            $table->string('metal_color')->nullable();
            $table->string('metal_purity')->nullable();

            $table->text('stone_details')->nullable();
            $table->string('size')->nullable();
        });
    }
};
