<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductVariation;
use App\Models\DiamondMaster;

class RecalculateGST extends Command
{
    protected $signature = 'gst:recalculate';
    protected $description = 'Recalculate GST for all products and diamonds';

    public function handle()
    {
        $this->info('Starting GST recalculation...');
        
        // Recalculate GST for all product variations
        $variations = ProductVariation::all();
        $this->info('Recalculating GST for ' . $variations->count() . ' product variations...');
        
        $variationCount = 0;
        foreach ($variations as $variation) {
            $variation->calculateAndSaveGST();
            $variationCount++;
            
            if ($variationCount % 100 === 0) {
                $this->info('Processed ' . $variationCount . ' variations...');
            }
        }
        
        // Recalculate GST for all diamonds
        $diamonds = DiamondMaster::all();
        $this->info('Recalculating GST for ' . $diamonds->count() . ' diamonds...');
        
        $diamondCount = 0;
        foreach ($diamonds as $diamond) {
            $diamond->saveWithGST();
            $diamondCount++;
            
            if ($diamondCount % 100 === 0) {
                $this->info('Processed ' . $diamondCount . ' diamonds...');
            }
        }
        
        $this->info('GST recalculation completed successfully!');
        $this->info('Total variations processed: ' . $variationCount);
        $this->info('Total diamonds processed: ' . $diamondCount);
        
        return 0;
    }
}