<?php

namespace App\Services;

use App\Models\ProductVariation;
use App\Models\MetalPrice;
use Illuminate\Support\Facades\Log;

class ProductPriceUpdateService
{
    public function updateAllVariationPrices()
    {
        $variations = ProductVariation::with('metalColor')->get();
        $updatedCount = 0;
        $failedCount = 0;

        foreach ($variations as $variation) {
            try {
                if ($variation->updatePriceFromMetal()) {
                    $updatedCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Price update failed for variation {$variation->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        return [
            'updated' => $updatedCount,
            'failed' => $failedCount,
            'total' => $variations->count()
        ];
    }

    public function updatePricesByMetalType($metalType, $metalQuality = null)
    {
        $query = ProductVariation::with('metalColor')
                ->whereHas('metalColor', function($q) use ($metalType, $metalQuality) {
                    $q->where('dmt_name', $metalType);
                    if ($metalQuality) {
                        $q->where('metal_quality', $metalQuality);
                    }
                });

        $variations = $query->get();
        $updatedCount = 0;

        foreach ($variations as $variation) {
            if ($variation->updatePriceFromMetal()) {
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    public function updatePricesWithLatestMetalData()
    {
        $latestMetalPrices = MetalPrice::latestPrices()->get();
        
        $totalUpdated = 0;

        foreach ($latestMetalPrices as $metalPrice) {
            $updated = $this->updatePricesByMetalType(
                $metalPrice->metal_type, 
                $metalPrice->metal_quality
            );
            $totalUpdated += $updated;
        }

        return $totalUpdated;
    }
}