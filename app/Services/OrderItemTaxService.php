<?php

namespace App\Services;

use App\Models\OrderItemTax;
use App\Models\Order;

class OrderItemTaxService
{
    /**
     * Store tax breakup for an order
     */
    public function store(Order $order, array $items, array $billing, array $shipping): void
    {
        $rows = [];

        $isInterState = ($billing['state'] ?? null) !== ($shipping['state'] ?? null);

        foreach ($items as $item) {

            // ---------------- DIAMOND ----------------
            if (($item['productType'] ?? '') === 'diamond') {

                $price = $item['price'];
                $rate  = 0.25;

                $rows[] = [
                    'order_id'       => $order->id,
                    'diamond_id'     => $item['diamondid'],
                    'hsn_code'       => '00',
                    'taxable_value'  => $price,
                    'tax_rate'       => $rate,
                    'tax_amount'     => ($price * $rate) / 100,
                    'item_name'      => 'Diamond',
                    'item_type'      => 'diamond',
                    'quantity'       => 1,
                    'unit_price'     => $price,
                    'description'    => 'Diamond tax',
                ];

                continue;
            }

            // ---------------- RING / JEWELLERY ----------------
            if (in_array($item['productType'], ['gift', 'build', 'combo'])) {

                $goldValue   = $item['gold_price'] ?? 0;
                $makingValue = $item['making_price'] ?? 0;

                // GOLD
                if ($goldValue > 0) {
                    $rows = array_merge(
                        $rows,
                        $this->splitGST(
                            $order->id,
                            OrderItemTax::COMPONENT_GOLD,
                            $goldValue,
                            '7113',
                            $item
                        )
                    );
                }

                // MAKING
                if ($makingValue > 0) {
                    $rows = array_merge(
                        $rows,
                        $this->splitGST(
                            $order->id,
                            OrderItemTax::COMPONENT_MAKING,
                            $makingValue,
                            '7113',
                            $item
                        )
                    );
                }
            }
        }

        OrderItemTax::insert($rows);
    }

    /**
     * Split CGST + SGST
     */
    private function splitGST(
        int $orderId,
        string $component,
        float $amount,
        string $hsn,
        array $item
    ): array {
        return [
            [
                'order_id'       => $orderId,
                'product_id'     => $item['product_id'] ?? null,
                'component_type' => $component,
                'hsn_code'       => $hsn,
                'tax_type'       => OrderItemTax::TYPE_CGST,
                'taxable_value'  => $amount / 2,
                'tax_rate'       => 1.5,
                'tax_amount'     => ($amount * 1.5) / 100,
                'item_name'      => $item['name'] ?? 'Product',
                'item_type'      => 'product',
                'quantity'       => 1,
                'unit_price'     => $amount,
            ],
            [
                'order_id'       => $orderId,
                'product_id'     => $item['product_id'] ?? null,
                'component_type' => $component,
                'hsn_code'       => $hsn,
                'tax_type'       => OrderItemTax::TYPE_SGST,
                'taxable_value'  => $amount / 2,
                'tax_rate'       => 1.5,
                'tax_amount'     => ($amount * 1.5) / 100,
                'item_name'      => $item['name'] ?? 'Product',
                'item_type'      => 'product',
                'quantity'       => 1,
                'unit_price'     => $amount,
            ],
        ];
    }
}
