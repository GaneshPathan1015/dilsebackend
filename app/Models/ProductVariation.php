<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductVariation extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'product_id', 
        'carat',  
        'price', 
        'regular_price',
        'making_charges',
        'sku', 
        'images',
        'video',
        'master_sku', 
        'stock', 
        'weight', 
        'shape_id', 
        'diamond_weight',
        'diamond_quality_id', 
        'category_id', 
        'metal_color_id', 
        'vendor_id',
        'parent_category_id',
        'is_best_selling',
    ];
    
    protected $casts = [
        'images' => 'array',
        'making_charges' => 'decimal:2',
        'price' => 'decimal:2',
        'regular_price' => 'decimal:2',
        'diamond_weight' => 'decimal:2',
        'weight' => 'decimal:2',
    ];
    
    protected $appends = [
        'video_url', 
        'tax_rate_names', 
        'tax_rate_ids', 
        'total_tax_rate',
        'gold_gst_amount',
        'diamond_gst_amount',
        'making_gst_amount',
        'gold_gst_rate',
        'diamond_gst_rate',
        'making_gst_rate',
        'total_gst_amount',
        'price_without_gst',
        'making_without_gst',
        'gst_breakdown',
        'formatted_gst_details',
    ];



    public function getCurrentMetalPrice()
    {
        if (!$this->metalColor) {
            return null;
        }

        $metalType = $this->metalColor->dmt_name;
        $metalQuality = $this->metalColor->metal_quality ?? '22K';

        return MetalPrice::where('metal_type', $metalType)
            ->where('metal_quality', $metalQuality)
            ->latest('date')
            ->first();
    }

    public function calculatePriceBasedOnMetal($latestMetalPrice = null)
    {
        if (!$latestMetalPrice) {
            $latestMetalPrice = $this->getCurrentMetalPrice();
        }

        if (!$latestMetalPrice || !$this->weight) {
            return null;
        }

        $newBasePrice = $this->weight * $latestMetalPrice->price_per_gram;

        if ($this->price > 0) {
            $ratio = $this->regular_price / $this->price;
            $newRegularPrice = $newBasePrice * $ratio;
        } else {
            $newRegularPrice = $newBasePrice;
        }

        return [
            'price' => $newBasePrice,
            'regular_price' => $newRegularPrice
        ];
    }

    public function updatePriceFromMetal($latestMetalPrice = null)
    {
        $prices = $this->calculatePriceBasedOnMetal($latestMetalPrice);

        if ($prices) {
            $this->update([
                'price' => $prices['price'],
                'regular_price' => $prices['regular_price']
            ]);
            return true;
        }

        return false;
    }

    public function getImagesAttribute($value)
    {
        $images = is_array($value) ? $value : json_decode($value, true);

        // fallback if it's still not an array
        if (!is_array($images)) {
            return [];
        }

        return array_map(function ($image) {
            return asset('storage/variation_images/' . $image);
        }, $images);
    }
    
    // Accessor for video URL
    public function getVideoUrlAttribute()
    {
        if (!empty($this->video)) {
            return asset('storage/variation_videos/' . $this->video);
        }
        return null;
    }
    
    protected static function booted()
    {
        static::deleted(function ($variation) {
            // Delete associated images
            if ($variation->images) {
                foreach ($variation->images as $imagePath) {
                    $filename = basename($imagePath);
                    if (Storage::disk('public')->exists("variation_images/$filename")) {
                        Storage::disk('public')->delete("variation_images/$filename");
                    }
                }
            }
        });

        static::updating(function ($variation) {
        $originalVideo = $variation->getOriginal('video');
        $newVideo = $variation->video;
        
        // If video is being changed or removed, delete the old video
        if ($originalVideo && $originalVideo !== $newVideo) {
            $videoPath = "variation_videos/" . $originalVideo;
            if (Storage::disk('public')->exists($videoPath)) {
                Storage::disk('public')->delete($videoPath);
            }
        }
    });
    }

    public function getImageFilenames()
    {
        $images = $this->getRawOriginal('images');

        if (is_string($images)) {
            $decoded = json_decode($images, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $images = $decoded;
            } else {
                $images = [$images];
            }
        }

        $filenames = [];
        foreach ((array)$images as $path) {
            $filenames[] = basename($path);
        }
        
        return $filenames;
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'products_id');
    }

    public function shape()
    {
        return $this->belongsTo(DiamondShape::class, 'shape_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function metalColor()
    {
        return $this->belongsTo(MetalType::class, 'metal_color_id', 'dmt_id');
    }

    public function diamondQualityGroup()
    {
        return $this->belongsTo(DiamondQualityGroup::class, 'diamond_quality_id', 'dqg_id');
    }
    public function taxRates() 
    {
        return $this->belongsToMany(TaxRate::class, 'product_variation_tax_rate');
    }

    public function getTaxRateNamesAttribute()
    {
        return $this->taxRates->pluck('name')->implode(', ');
    }
    public function getTaxRateIdsAttribute()
    {
        return $this->taxRates->pluck('id')->toArray();
    }

    public function getTotalTaxRateAttribute()
    {
        return $this->taxRates->sum('rate');
    }

    public function getGstRateByType($type)
    {
        $taxRate = $this->taxRates->where('code', $type)->first();
        return $taxRate ? $taxRate->rate : 0;
    }

     // ✅ Get GST Rate by Type
    private function getGstRateByCode($code)
    {
        $taxRate = $this->taxRates->where('code', $code)->first();
        return $taxRate ? $taxRate->rate : 0;
    }

    // ✅ Gold GST Amount
    public function getGoldGstAmountAttribute()
    {
        $goldRate = $this->getGstRateByCode('GST_GOLD');
        return round(($this->price * $goldRate) / 100, 2);
    }

    // ✅ Diamond GST Amount
    public function getDiamondGstAmountAttribute()
    {
        $diamondRate = $this->getGstRateByCode('GST_DIAMOND');
        return round(($this->price * $diamondRate) / 100, 2);
    }

    // ✅ Making GST Amount
    public function getMakingGstAmountAttribute()
    {
        $makingRate = $this->getGstRateByCode('GST_MAKING');
        $makingCharges = $this->making_charges ?? 0;
        return round(($makingCharges * $makingRate) / 100, 2);
    }

    // ✅ Total GST Amount
    public function getTotalGstAmountAttribute()
    {
        return round(
            $this->gold_gst_amount + 
            $this->diamond_gst_amount + 
            $this->making_gst_amount, 
        2);
    }

    // ✅ Gold GST Rate
    public function getGoldGstRateAttribute()
    {
        return $this->getGstRateByCode('GST_GOLD');
    }

    // ✅ Diamond GST Rate
    public function getDiamondGstRateAttribute()
    {
        return $this->getGstRateByCode('GST_DIAMOND');
    }

    // ✅ Making GST Rate
    public function getMakingGstRateAttribute()
    {
        return $this->getGstRateByCode('GST_MAKING');
    }

    // ✅ Price without GST
    public function getPriceWithoutGstAttribute()
    {
        return $this->price;
    }

    // ✅ Making without GST
    public function getMakingWithoutGstAttribute()
    {
        return $this->making_charges ?? 0;
    }

    // ✅ Total without GST
    public function getTotalWithoutGstAttribute()
    {
        return $this->price + ($this->making_charges ?? 0);
    }

    // ✅ Price with Tax
    public function getPriceWithTaxAttribute()
    {
        return $this->total_without_gst + $this->total_gst_amount;
    }

    // ✅ Tax Amount (for backward compatibility)
    public function getTaxAmountAttribute()
    {
        return $this->total_gst_amount;
    }

    // ✅ नया: GST Breakdown Array
    public function getGstBreakdownAttribute()
    {
        return [
            [
                'type' => 'gold',
                'name' => 'Gold GST',
                'rate' => $this->gold_gst_rate,
                'rate_formatted' => number_format($this->gold_gst_rate, 2) . '%',
                'base_amount' => $this->price,
                'gst_amount' => $this->gold_gst_amount,
                'formatted' => "Gold GST (" . number_format($this->gold_gst_rate, 2) . "%): ₹" . number_format($this->gold_gst_amount, 2),
                'calculation' => "₹" . number_format($this->price, 2) . " × " . number_format($this->gold_gst_rate, 2) . "% = ₹" . number_format($this->gold_gst_amount, 2)
            ],
            [
                'type' => 'diamond',
                'name' => 'Diamond GST',
                'rate' => $this->diamond_gst_rate,
                'rate_formatted' => number_format($this->diamond_gst_rate, 2) . '%',
                'base_amount' => $this->price,
                'gst_amount' => $this->diamond_gst_amount,
                'formatted' => "Diamond GST (" . number_format($this->diamond_gst_rate, 2) . "%): ₹" . number_format($this->diamond_gst_amount, 2),
                'calculation' => "₹" . number_format($this->price, 2) . " × " . number_format($this->diamond_gst_rate, 2) . "% = ₹" . number_format($this->diamond_gst_amount, 2)
            ],
            [
                'type' => 'making',
                'name' => 'Making Charges GST',
                'rate' => $this->making_gst_rate,
                'rate_formatted' => number_format($this->making_gst_rate, 2) . '%',
                'base_amount' => $this->making_charges ?? 0,
                'gst_amount' => $this->making_gst_amount,
                'formatted' => "Making GST (" . number_format($this->making_gst_rate, 2) . "%): ₹" . number_format($this->making_gst_amount, 2),
                'calculation' => "₹" . number_format($this->making_charges ?? 0, 2) . " × " . number_format($this->making_gst_rate, 2) . "% = ₹" . number_format($this->making_gst_amount, 2)
            ]
        ];
    }

    // ✅ नया: Formatted GST Details String
    public function getFormattedGstDetailsAttribute()
    {
        $details = [];
        
        // Gold GST
        if ($this->gold_gst_amount > 0) {
            $details[] = "Gold GST (" . number_format($this->gold_gst_rate, 2) . "%): ₹" . number_format($this->gold_gst_amount, 2);
        }
        
        // Diamond GST
        if ($this->diamond_gst_amount > 0) {
            $details[] = "Diamond GST (" . number_format($this->diamond_gst_rate, 2) . "%): ₹" . number_format($this->diamond_gst_amount, 2);
        }
        
        // Making GST
        if ($this->making_gst_amount > 0) {
            $details[] = "Making GST (" . number_format($this->making_gst_rate, 2) . "%): ₹" . number_format($this->making_gst_amount, 2);
        }
        
        // Total GST
        $details[] = "Total GST: ₹" . number_format($this->total_gst_amount, 2);
        
        return implode(" | ", $details);
    }

}

