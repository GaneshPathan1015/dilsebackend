<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\DiamondMaster;
use App\Models\TaxRate;

return new class extends Migration
{
    public function up()
    {
        // First create the pivot table
        Schema::create('diamond_master_tax_rate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diamond_master_id')->constrained('diamond_master', 'diamondid')->onDelete('cascade');
            $table->foreignId('tax_rate_id')->constrained('tax_rates')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['diamond_master_id', 'tax_rate_id']);
        });

        // Migrate existing data from diamond_master.tax_rate_id to pivot table
        $diamonds = DiamondMaster::whereNotNull('tax_rate_id')->get();
        
        foreach ($diamonds as $diamond) {
            if ($diamond->tax_rate_id) {
                $diamond->taxRates()->attach($diamond->tax_rate_id);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('diamond_master_tax_rate');
    }
};