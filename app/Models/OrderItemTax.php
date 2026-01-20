<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemTax extends Model
{
    protected $fillable = [
        'order_id', 'order_item_id', 'product_variation_id', 
        'component_type', 'hsn_code', 'tax_type', 
        'taxable_value', 'tax_rate', 'tax_amount', 'description',
        'tax_rate_id' // ✅ Add this line for foreign key relationship
    ];
    
    protected $casts = [
        'taxable_value' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2'
    ];
    
    // Component types
    const COMPONENT_DIAMOND = 'diamond';
    const COMPONENT_GOLD = 'gold';
    const COMPONENT_MAKING = 'making';
    
    // Tax types
    const TYPE_CGST = 'CGST';
    const TYPE_SGST = 'SGST';
    const TYPE_IGST = 'IGST';
    
    // Get component name
    public function getComponentNameAttribute()
    {
        return ucfirst($this->component_type);
    }
    
    // Get formatted tax rate
    public function getFormattedTaxRateAttribute()
    {
        return $this->tax_rate . '%';
    }
    
    // ✅ Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
    
    // ✅ Relationship with TaxRate
    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }
    
    // ✅ Get tax rate details from TaxRate model
    public function getTaxRateDetails()
    {
        if ($this->taxRate) {
            return [
                'code' => $this->taxRate->code,
                'name' => $this->taxRate->name,
                'rate' => $this->taxRate->rate,
                'hsn_code' => $this->taxRate->hsn_code,
                'status' => $this->taxRate->status
            ];
        }
        
        return null;
    }
    
    // ✅ Static method to calculate GST based on component type
    public static function calculateTaxForComponent($componentType, $taxableValue, $isInterState = false)
    {
        $taxRate = null;
        
        // Get appropriate tax rate based on component type
        switch ($componentType) {
            case self::COMPONENT_DIAMOND:
                $taxRate = TaxRate::getDiamondRate();
                break;
            case self::COMPONENT_GOLD:
                $taxRate = TaxRate::getGoldRate();
                break;
            case self::COMPONENT_MAKING:
                $taxRate = TaxRate::getMakingRate();
                break;
            default:
                return null;
        }
        
        // Calculate GST using TaxRate model's method
        $gstCalculation = $taxRate->calculateGST($taxableValue, $isInterState);
        
        return [
            'tax_rate_id' => $taxRate->id,
            'component_type' => $componentType,
            'hsn_code' => $taxRate->hsn_code,
            'taxable_value' => $taxableValue,
            'tax_rate' => $taxRate->rate,
            'igst' => $gstCalculation['igst'],
            'cgst' => $gstCalculation['cgst'],
            'sgst' => $gstCalculation['sgst'],
            'total_tax' => $gstCalculation['total'],
            'is_inter_state' => $isInterState
        ];
    }
    
    // ✅ Create tax records for an order item
    public static function createForOrderItem($orderId, $orderItemId, $productVariationId, $itemTotal, $isInterState = false)
    {
        $taxRecords = [];
        
        // Get product variation
        $variation = ProductVariation::with('taxRates')->find($productVariationId);
        
        if (!$variation || $variation->taxRates->isEmpty()) {
            return $taxRecords;
        }
        
        // For each tax rate attached to the variation
        foreach ($variation->taxRates as $taxRate) {
            // Determine component type based on tax rate code
            $componentType = self::COMPONENT_MAKING; // Default
            
            if (strpos($taxRate->code, 'DIAMOND') !== false) {
                $componentType = self::COMPONENT_DIAMOND;
            } elseif (strpos($taxRate->code, 'GOLD') !== false) {
                $componentType = self::COMPONENT_GOLD;
            } elseif (strpos($taxRate->code, 'MAKING') !== false) {
                $componentType = self::COMPONENT_MAKING;
            }
            
            // Calculate taxable value for this component
            // Note: You might need to adjust this logic based on your pricing structure
            $taxableValue = $itemTotal; // This is simplified - adjust as needed
            
            // Calculate GST
            $gstCalculation = $taxRate->calculateGST($taxableValue, $isInterState);
            
            // Create tax records for CGST/SGST or IGST
            if ($isInterState) {
                // IGST
                $taxRecords[] = [
                    'order_id' => $orderId,
                    'order_item_id' => $orderItemId,
                    'product_variation_id' => $productVariationId,
                    'tax_rate_id' => $taxRate->id,
                    'component_type' => $componentType,
                    'hsn_code' => $taxRate->hsn_code,
                    'tax_type' => self::TYPE_IGST,
                    'taxable_value' => $taxableValue,
                    'tax_rate' => $taxRate->rate,
                    'tax_amount' => $gstCalculation['igst'],
                    'description' => "{$taxRate->name} - IGST"
                ];
            } else {
                // CGST
                $taxRecords[] = [
                    'order_id' => $orderId,
                    'order_item_id' => $orderItemId,
                    'product_variation_id' => $productVariationId,
                    'tax_rate_id' => $taxRate->id,
                    'component_type' => $componentType,
                    'hsn_code' => $taxRate->hsn_code,
                    'tax_type' => self::TYPE_CGST,
                    'taxable_value' => $taxableValue,
                    'tax_rate' => $taxRate->rate / 2, // Half rate for CGST
                    'tax_amount' => $gstCalculation['cgst'],
                    'description' => "{$taxRate->name} - CGST"
                ];
                
                // SGST
                $taxRecords[] = [
                    'order_id' => $orderId,
                    'order_item_id' => $orderItemId,
                    'product_variation_id' => $productVariationId,
                    'tax_rate_id' => $taxRate->id,
                    'component_type' => $componentType,
                    'hsn_code' => $taxRate->hsn_code,
                    'tax_type' => self::TYPE_SGST,
                    'taxable_value' => $taxableValue,
                    'tax_rate' => $taxRate->rate / 2, // Half rate for SGST
                    'tax_amount' => $gstCalculation['sgst'],
                    'description' => "{$taxRate->name} - SGST"
                ];
            }
        }
        
        return $taxRecords;
    }
    
    // ✅ Get total tax for an order
    public static function getOrderTotalTax($orderId)
    {
        return self::where('order_id', $orderId)->sum('tax_amount');
    }
    
    // ✅ Get tax breakdown for an order
    public static function getOrderTaxBreakdown($orderId)
    {
        $taxes = self::where('order_id', $orderId)->get();
        
        $breakdown = [
            'cgst' => 0,
            'sgst' => 0,
            'igst' => 0,
            'total' => 0,
            'by_component' => []
        ];
        
        foreach ($taxes as $tax) {
            if ($tax->tax_type === self::TYPE_CGST) {
                $breakdown['cgst'] += $tax->tax_amount;
            } elseif ($tax->tax_type === self::TYPE_SGST) {
                $breakdown['sgst'] += $tax->tax_amount;
            } elseif ($tax->tax_type === self::TYPE_IGST) {
                $breakdown['igst'] += $tax->tax_amount;
            }
            
            $breakdown['total'] += $tax->tax_amount;
            
            // Group by component type
            if (!isset($breakdown['by_component'][$tax->component_type])) {
                $breakdown['by_component'][$tax->component_type] = 0;
            }
            $breakdown['by_component'][$tax->component_type] += $tax->tax_amount;
        }
        
        return $breakdown;
    }
}