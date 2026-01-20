<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxRatesTableSeeder extends Seeder
{
    public function run()
    {
        $taxRates = [
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
        ];

        DB::table('tax_rates')->insert($taxRates);
    }
}