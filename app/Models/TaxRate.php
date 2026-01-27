<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'code', 'name', 'rate', 'hsn_code', 'status'
    ];
    
    protected $casts = [
        'rate' => 'decimal:2',
        'status' => 'string'
    ];
    
    // GST Types
    const TYPE_DIAMOND = 'GST_DIAMOND';
    const TYPE_GOLD = 'GST_GOLD';
    const TYPE_MAKING = 'GST_MAKING';
    
    // Tax Types
    const TAX_CGST = 'CGST';
    const TAX_SGST = 'SGST';
    const TAX_IGST = 'IGST';
    
    // HSN Codes
    const HSN_DIAMOND = '7102';
    const HSN_GOLD = '7113';
    
    // Get rate percentage
    public function getRatePercentageAttribute()
    {
        return $this->rate . '%';
    }
    
    // Get formatted rate
    public function getFormattedRateAttribute()
    {
        return number_format($this->rate, 2) . '%';
    }
    
    // Static methods to get specific tax rates
    public static function getDiamondRate()
    {
        return self::where('code', self::TYPE_DIAMOND)->active()->first() ?? new self([
            'code' => self::TYPE_DIAMOND,
            'name' => 'Diamond GST',
            'rate' => 0.25,
            'hsn_code' => self::HSN_DIAMOND,
            'status' => 'active'
        ]);
    }
    
    public static function getGoldRate()
    {
        return self::where('code', self::TYPE_GOLD)->active()->first() ?? new self([
            'code' => self::TYPE_GOLD,
            'name' => 'Gold GST',
            'rate' => 3.00,
            'hsn_code' => self::HSN_GOLD,
            'status' => 'active'
        ]);
    }
    
    public static function getMakingRate()
    {
        return self::where('code', self::TYPE_MAKING)->active()->first() ?? new self([
            'code' => self::TYPE_MAKING,
            'name' => 'Making Charges GST',
            'rate' => 3.00,
            'hsn_code' => self::HSN_GOLD,
            'status' => 'active'
        ]);
    }
    
    // Calculate GST for a component
    public function calculateGST($taxableValue, $isInterState = false)
    {
        if ($taxableValue <= 0) {
            return [
                'igst' => 0,
                'cgst' => 0,
                'sgst' => 0,
                'total' => 0
            ];
        }
        
        $gstAmount = ($taxableValue * $this->rate) / 100;
        
        if ($isInterState) {
            // IGST (for inter-state)
            return [
                'igst' => round($gstAmount, 2),
                'cgst' => 0,
                'sgst' => 0,
                'total' => round($gstAmount, 2)
            ];
        } else {
            // CGST + SGST (for intra-state)
            $halfGst = $gstAmount / 2;
            return [
                'igst' => 0,
                'cgst' => round($halfGst, 2),
                'sgst' => round($halfGst, 2),
                'total' => round($gstAmount, 2)
            ];
        }
    }
    
    // Scope for active tax rates
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    // Get all tax rates as array for dropdown
    public static function getDropdown()
    {
        return self::active()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend('Select Tax Rate', '');
    }

    public function productVariations()
    {
        return $this->belongsToMany(ProductVariation::class, 'product_variation_tax_rate');
    }
    
    // ✅ Keep diamonds relationship
    public function diamonds()
    {
        return $this->hasMany(DiamondMaster::class, 'tax_rate_id');
    }

    
    
}