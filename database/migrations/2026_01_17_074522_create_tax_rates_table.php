<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration 
{
    public function up()
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // GST_DIAMOND, GST_GOLD, GST_MAKING
            $table->string('name'); // Diamond GST, Gold GST
            $table->decimal('rate', 5, 2); // 0.25, 3.00
            $table->string('hsn_code')->nullable(); // 7102, 7113
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('tax_rates')->insert([
            [
                'code' => 'GST_DIAMOND',
                'name' => 'Diamond GST',
                'rate' => 0.25,
                'hsn_code' => '7102',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'GST_GOLD',
                'name' => 'Gold GST',
                'rate' => 3.00,
                'hsn_code' => '7113',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'GST_MAKING',
                'name' => 'Making Charges GST',
                'rate' => 3.00,
                'hsn_code' => '7113',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('tax_rates');
    }
};
