<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MetalPrice;
use App\Models\ProductVariation;
use App\Models\MetalType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdateProductPricesFromMetal extends Command
{
    protected $signature = 'products:update-prices-from-metal';
    protected $description = 'Update product prices based on latest metal prices - RATIO MAINTAIN KARTE HUE';

    public function handle()
    {
        $this->info('Starting product price update from metal prices...');

        $latestMetalPrices = MetalPrice::whereIn('id', function($query) {
            $query->select(DB::raw('MAX(id)'))
                  ->from('metal_prices')
                  ->groupBy('metal_type', 'metal_quality');
        })->get();

        $totalUpdated = 0;

        foreach ($latestMetalPrices as $metalPrice) {
            $updated = $this->updateVariationsWithMetalPrice($metalPrice);
            $totalUpdated += $updated;
            
            $this->info("Updated {$updated} variations for {$metalPrice->metal_type} ({$metalPrice->metal_quality})");
        }

        $this->info("Total updated: {$totalUpdated} variations");
        $this->info('Product price update completed!');
        
        Log::info("Auto price update completed: {$totalUpdated} variations updated");
    }

    private function updateVariationsWithMetalPrice($metalPrice)
    {
        $matchingMetalTypes = MetalType::where('dmt_name', $metalPrice->metal_type)
            ->pluck('dmt_id');

        if ($matchingMetalTypes->isEmpty()) {
            return 0;
        }

        $variations = ProductVariation::whereIn('metal_color_id', $matchingMetalTypes)
            ->where('weight', '>', 0)
            ->get();

        $updatedCount = 0;

        foreach ($variations as $variation) {
            try {
                $success = $variation->updatePriceFromMetal($metalPrice);
                if ($success) {
                    $updatedCount++;
                    
                    $this->info("Variation {$variation->id}: Price = {$variation->price}, Regular Price = {$variation->regular_price}");
                }
            } catch (\Exception $e) {
                $this->error("Failed to update variation {$variation->id}: " . $e->getMessage());
            }
        }

        return $updatedCount;
    }
}