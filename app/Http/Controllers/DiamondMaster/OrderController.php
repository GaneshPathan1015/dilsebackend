<?php

namespace App\Http\Controllers\DiamondMaster;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\DiamondMaster;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Models\DiamondShape;
use App\Models\DiamondColor;
use App\Models\DiamondClarityMaster;
use App\Models\DiamondCut;
use App\Models\Address;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Models\Coupon;
use Illuminate\Support\Facades\Validator;
use App\Models\OrderItemTax;



class OrderController extends Controller
{
    public function index()
    {
        return view('admin.DiamondMaster.Orders.index');
    }

    public function searchProducts(Request $request)
    {
        $search = $request->input('search');

        $products = Product::with(['variations', 'metalType'])
            ->where(function ($query) use ($search) {
                $query->where('products_name', 'like', "%{$search}%");
            })
            ->where('products_status', 1)
            ->limit(20)
            ->get();

        $results = [];
        foreach ($products as $product) {
            if ($product->variations->isNotEmpty()) {
                foreach ($product->variations as $variation) {
                    $results[] = [
                        'id' => $variation->id,
                        'product_id' => $product->products_id,
                        'variation_id' => $variation->id,
                        'name' => $product->products_name,
                        'sku' => $variation->sku ?: $product->products_sku,
                        'price' => $variation->price ?: $product->products_price,
                        'regular_price' => $variation->regular_price ?: $product->products_price,
                        'carat' => $variation->carat,
                        'weight' => $variation->weight,
                        'metal_color' => $variation->metalColor ? $variation->metalColor->dmt_name : null,
                        'shape' => $variation->shape ? $variation->shape->shape_name : null,
                        'stock' => $variation->stock,
                        'type' => 'jewelry',
                        'images' => $variation->images
                    ];
                }
            } else {
                $results[] = [
                    'id' => $product->products_id,
                    'product_id' => $product->products_id,
                    'variation_id' => null,
                    'name' => $product->products_name,
                    'sku' => $product->products_sku,
                    'price' => $product->products_price,
                    'regular_price' => $product->products_price,
                    'carat' => null,
                    'weight' => $product->products_weight,
                    'metal_color' => $product->metalType ? $product->metalType->dmt_name : null,
                    'shape' => null,
                    'stock' => $product->products_quantity,
                    'type' => 'jewelry',
                    'images' => []
                ];
            }
        }

        return response()->json($results);
    }

    public function searchDiamonds(Request $request)
    {
        try {
            $search = $request->input('search');

            if (empty($search)) {
                return response()->json([]);
            }

            $diamonds = DiamondMaster::where(function ($query) use ($search) {
                $query->where('stock_number', 'like', "%{$search}%")
                    ->orWhere('certificate_number', 'like', "%{$search}%")
                    ->orWhere('vendor_stock_number', 'like', "%{$search}%")
                    ->orWhere('diamondid', 'like', "%{$search}%");
            })
                ->where('status', 1)
                ->limit(20)
                ->get();

            $results = [];
            foreach ($diamonds as $diamond) {
                $shapeName = DiamondShape::where('id', $diamond->shape)->value('name') ?? 'N/A';
                $colorName = DiamondColor::where('id', $diamond->color)->value('name') ?? 'N/A';
                $clarityName = DiamondClarityMaster::where('id', $diamond->clarity)->value('name') ?? 'N/A';
                $cutName = DiamondCut::where('id', $diamond->cut)->value('name') ?? 'N/A';

                $results[] = [
                    'id' => $diamond->diamondid,
                    'name' => ($diamond->diamond_type == 1) ? 'Natural Diamond' : 'CVD Diamond',
                    'certificate_number' => $diamond->certificate_number ?? 'N/A',
                    'shape' => $shapeName,
                    'carat_weight' => $diamond->carat_weight ?? 0,
                    'color' => $colorName,
                    'clarity' => $clarityName,
                    'cut' => $cutName,
                    'price' => floatval($diamond->price ?? 0),
                    'price_per_carat' => floatval($diamond->price_per_carat ?? 0),
                    'stock' => intval($diamond->on_hand ?? 0),
                    'type' => 'diamond',
                    'image' => $diamond->image_link ?? ''
                ];
            }

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Diamond search error: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    public function searchUsers(Request $request)
    {
        $search = $request->input('search');

        $users = User::with(['addresses'])
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get();

        $results = [];
        foreach ($users as $user) {
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'addresses' => []
            ];

            foreach ($user->addresses as $address) {
                $userData['addresses'][] = [
                    'id' => $address->id,
                    'first_name' => $address->first_name,
                    'last_name' => $address->last_name,
                    'phone_number' => $address->phone_number,
                    'address' => $address->address,
                    'country' => $address->country,
                    'full_address' => $this->formatAddress($address)
                ];
            }

            $results[] = $userData;
        }

        return response()->json($results);
    }

    private function formatAddress($address)
    {
        if (is_array($address->address)) {
            $addr = $address->address;
        } else {
            $addr = json_decode($address->address, true) ?? [];
        }

        $parts = [
            $address->first_name . ' ' . $address->last_name,
            $addr['street'] ?? $addr['address_line1'] ?? '',
            $addr['city'] ?? $addr['locality'] ?? '',
            $addr['state'] ?? $addr['administrative_area'] ?? '',
            $address->country,
            $addr['zip'] ?? $addr['postal_code'] ?? $addr['pincode'] ?? '',
        ];

        return implode(', ', array_filter($parts, function ($value) {
            return !empty($value) && $value !== ' ';
        }));
    }

    public function fetch(Request $request)
    {
        try {
            $orders = Order::query();

            $total = $orders->count();

            if ($search = $request->input('search.value')) {
                $orders->where(function ($query) use ($search) {
                    $query->where('order_id', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhere('coupon_code', 'like', "%{$search}%")
                        ->orWhere('payment_mode', 'like', "%{$search}%")
                        ->orWhere('order_status', 'like', "%{$search}%");
                });
            }

            $filteredCount = $orders->count();

            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $length = $length > 0 ? $length : 10;

            $data = $orders->orderBy('created_at', 'desc')
                ->skip($start)
                ->take($length)
                ->get();

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $total,
                'recordsFiltered' => $filteredCount,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Order fetch error: ' . $e->getMessage());

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Error fetching orders'
            ]);
        }
    }

    public function show(Order $order)
    {
        try {
            $processedItems = $this->processApiOrderItems($order);
            $taxDetails = $this->extractTaxFromOrder($order);

            $taxSummary = [
                'total_tax' => $taxDetails['total_gst_amount'] ?? 0,
                'gold_gst' => $taxDetails['gold_gst_amount'] ?? 0,
                'diamond_gst' => $taxDetails['diamond_gst_amount'] ?? 0,
                'making_gst' => $taxDetails['making_gst_amount'] ?? 0,
                'total_price_without_tax' => $taxDetails['price_without_gst'] ?? 0
            ];

            return view('admin.DiamondMaster.Orders.invoice', compact(
                'order',
                'processedItems',
                'taxDetails',
                'taxSummary'
            ));
        } catch (\Exception $e) {
            Log::error('Error in OrderController@show: ' . $e->getMessage());
            return response()->view('admin.DiamondMaster.Orders.invoice_error', [
                'message' => 'Unable to load order details: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processApiOrderItems($order)
    {
        try {
            Log::info('Processing API Order Items for Order ID: ' . $order->order_id);

            $processedItems = [];

            // Check if item_details is JSON string
            $itemDetails = $order->item_details;

            if (is_string($itemDetails)) {
                $itemDetails = json_decode($itemDetails, true);
            }

            Log::info('Item Details Type: ' . gettype($itemDetails));

            if (is_array($itemDetails) && isset($itemDetails[0])) {
                Log::info('Processing as API array format');

                foreach ($itemDetails as $item) {
                    if (is_array($item)) {
                        $type = $item['productType'] ?? 'gift';

                        // ✅ SPECIAL CASE FOR COMBO
                        if ($type === 'combo') {
                            $processedItems = $this->processComboItem($item, $processedItems);
                            continue;
                        }

                        $name = $item['name'] ?? ($item['product_name'] ?? 'Product');

                        $processedItem = [
                            'type' => $type,
                            'id' => $item['id'] ?? $item['product_id'] ?? null,
                            'name' => $name,
                            'quantity' => $item['itemQuantity'] ?? $item['quantity'] ?? 1,
                            'price' => $item['price'] ?? 0,
                            'price_with_tax' => $item['price_with_tax'] ?? ($item['price'] ?? 0),
                            'type_label' => ucfirst($type)
                        ];

                        // ✅ TAX DETAILS
                        $taxDetails = [
                            'total_gst_amount' => $item['total_gst_amount'] ?? 0,
                            'gold_gst_amount' => $item['gold_gst_amount'] ?? 0,
                            'diamond_gst_amount' => $item['diamond_gst_amount'] ?? 0,
                            'making_gst_amount' => $item['making_gst_amount'] ?? 0,
                            'price_without_gst' => $item['price_without_gst'] ?? 0,
                            'making_charges' => $item['making_charges'] ?? 0,
                            'total_tax_rate' => $item['total_tax_rate'] ?? 0,
                            'gst_breakdown' => $item['gst_breakdown'] ?? [],
                            'formatted_gst_details' => $item['formatted_gst_details'] ?? '',
                            'tax_rates' => $item['tax_rates'] ?? []
                        ];

                        $processedItem['tax_details'] = $taxDetails;

                        // Product specific details
                        if ($type === 'gift' || $type === 'jewelry') {
                            // Shape details
                            if (isset($item['shape']) && is_array($item['shape'])) {
                                $processedItem['shape'] = $item['shape']['name'] ?? 'N/A';
                                $processedItem['shape_image'] = $item['shape']['image'] ?? null;
                            }

                            // Metal color details
                            if (isset($item['metal_color']) && is_array($item['metal_color'])) {
                                $processedItem['metal_color'] = $item['metal_color']['name'] ?? 'N/A';
                                $processedItem['metal_quality'] = $item['metal_color']['quality'] ?? 'N/A';
                                $processedItem['metal_hex'] = $item['metal_color']['hex'] ?? null;
                            }

                            // Additional product details
                            $processedItem['weight'] = $item['weight'] ?? null;
                            $processedItem['diamond_weight'] = $item['diamond_weight'] ?? null;
                            $processedItem['diamond_quality'] = $item['diamond_quality_name'] ?? null;
                            $processedItem['sku'] = $item['sku'] ?? null;
                            $processedItem['carat'] = $item['carat'] ?? null;

                            // Images
                            $processedItem['images'] = $item['images'] ?? [];

                            // Category
                            if (isset($item['category']) && is_array($item['category'])) {
                                $processedItem['category'] = $item['category']['name'] ?? 'N/A';
                            }
                        }

                        // Diamond specific details (if any)
                        if (($item['diamond_weight'] ?? 0) > 0) {
                            $processedItem['diamond_details'] = [
                                'weight' => $item['diamond_weight'] ?? null,
                                'quality' => $item['diamond_quality_name'] ?? null,
                                'quality_id' => $item['diamond_quality_id'] ?? null
                            ];
                        }

                        // Selected plan (warranty/insurance)
                        if (isset($item['selectedPlan'])) {
                            $processedItem['selected_plan'] = $item['selectedPlan'];
                            $processedItem['warranty_info'] = $this->getWarrantyInfo($item['selectedPlan']);
                        }

                        $processedItems[] = $processedItem;
                    }
                }
            }

            if (empty($processedItems)) {
                Log::warning('No items processed, using fallback');
                $processedItems[] = [
                    'type' => 'product',
                    'id' => null,
                    'name' => 'Order Products',
                    'quantity' => $order->total_quantity ?? 1,
                    'price' => $order->total_price ?? 0,
                    'price_with_tax' => $order->total_price ?? 0,
                    'type_label' => 'Product',
                    'tax_details' => [
                        'total_gst_amount' => 0,
                        'gold_gst_amount' => 0,
                        // 'diamond_gst_amount' => 0,
                        'making_gst_amount' => 0,
                        'price_without_gst' => $order->total_price ?? 0
                    ]
                ];
            }

            Log::info('Total processed items: ' . count($processedItems));
            return $processedItems;
        } catch (\Exception $e) {
            Log::error("Error processing API order items: " . $e->getMessage());
            Log::error("Error trace: " . $e->getTraceAsString());

            return [[
                'type' => 'product',
                'id' => null,
                'name' => 'Order Products',
                'quantity' => $order->total_quantity ?? 1,
                'price' => $order->total_price ?? 0,
                'type_label' => 'Product'
            ]];
        }
    }

    private function processComboItem($item, $processedItems)
    {
        try {
            Log::info('Processing Combo Item');

            $ring = $item['ring'] ?? [];
            $diamond = $item['diamond'] ?? [];
            $size = $item['size'] ?? 'N/A';
            $quantity = $item['itemQuantity'] ?? 1;
            $totalPrice = $item['totalPrice'] ?? 0;

            // Calculate individual prices
            $ringPrice = $ring['price'] ?? 0;
            $diamondPrice = $diamond['price'] ?? 0;

            // ✅ 1. Process Ring as separate item
            if (!empty($ring)) {
                $ringItem = [
                    'type' => 'jewelry',
                    'id' => $ring['id'] ?? $ring['product_id'] ?? null,
                    'name' => $ring['name'] ?? 'Ring',
                    'quantity' => $quantity,
                    'price' => $ringPrice,
                    'price_with_tax' => $ring['price_with_tax'] ?? $ringPrice,
                    'type_label' => 'Ring',
                    'is_combo_part' => true,
                    'combo_size' => $size
                ];

                // Tax details for ring
                $ringTaxDetails = [
                    'total_gst_amount' => $ring['total_gst_amount'] ?? 0,
                    'gold_gst_amount' => $ring['gold_gst_amount'] ?? 0,
                    // 'diamond_gst_amount' => $ring['diamond_gst_amount'] ?? 0,
                    'making_gst_amount' => $ring['making_gst_amount'] ?? 0,
                    'price_without_gst' => $ring['price_without_gst'] ?? 0,
                    'making_charges' => $ring['making_without_gst'] ?? 0,
                    'total_tax_rate' => $ring['total_tax_rate'] ?? 0,
                    'gst_breakdown' => $ring['gst_breakdown'] ?? [],
                    'formatted_gst_details' => $ring['formatted_gst_details'] ?? '',
                    'tax_rates' => $ring['tax_rates'] ?? []
                ];

                $ringItem['tax_details'] = $ringTaxDetails;

                // Ring details
                if (isset($ring['metal_color']) && is_array($ring['metal_color'])) {
                    $ringItem['metal_color'] = $ring['metal_color']['name'] ?? 'N/A';
                    $ringItem['metal_quality'] = $ring['metal_color']['quality'] ?? 'N/A';
                }

                $ringItem['shape'] = $ring['shape'] ?? 'N/A';
                $ringItem['weight'] = $ring['weight'] ?? null;
                $ringItem['diamond_weight'] = $ring['diamond_weight'] ?? null;
                $ringItem['diamond_quality'] = $ring['diamond_quality_name'] ?? null;
                $ringItem['sku'] = $ring['sku'] ?? null;
                $ringItem['images'] = $ring['images'] ?? [];

                if (isset($ring['category']) && is_array($ring['category'])) {
                    $ringItem['category'] = $ring['category']['name'] ?? 'N/A';
                }

                $processedItems[] = $ringItem;
            }

            if (!empty($diamond)) {
                $diamondItem = [
                    'type' => 'diamond',
                    'id' => $diamond['diamondid'] ?? null,
                    'name' => 'Diamond',
                    'quantity' => $quantity,
                    'price' => $diamondPrice,
                    'price_with_tax' => $diamondPrice, 
                    'type_label' => 'Diamond',
                    'is_combo_part' => true,
                    'combo_size' => $size
                ];

                // Diamond details
                $diamondItem['certificate_number'] = $diamond['certificate_number'] ?? 'N/A';
                $diamondItem['carat_weight'] = $diamond['carat_weight'] ?? 0;

                if (isset($diamond['color']) && is_array($diamond['color'])) {
                    $diamondItem['color'] = $diamond['color']['name'] ?? 'N/A';
                }

                if (isset($diamond['clarity']) && is_array($diamond['clarity'])) {
                    $diamondItem['clarity'] = $diamond['clarity']['name'] ?? 'N/A';
                }

                if (isset($diamond['shape']) && is_array($diamond['shape'])) {
                    $diamondItem['shape'] = $diamond['shape']['name'] ?? 'N/A';
                }

                if (isset($diamond['cut']) && is_array($diamond['cut'])) {
                    $diamondItem['cut'] = $diamond['cut']['name'] ?? 'N/A';
                }

                if (isset($diamond['certificate_company']) && is_array($diamond['certificate_company'])) {
                    $diamondItem['certificate_company'] = $diamond['certificate_company']['dl_name'] ?? 'N/A';
                }

                $diamondItem['measurements'] = $diamond['measurements'] ?? 'N/A';
                $diamondItem['diamond_type'] = $diamond['diamond_type'] ?? 1;
                $diamondItem['diamond_type_label'] = ($diamondItem['diamond_type'] == 1) ? 'Natural' : 'CVD';

                // Diamond GST is 0.25% - calculate for combo diamond
                $diamondGstRate = 0.25;
                $diamondTaxAmount = ($diamondPrice * $diamondGstRate) / 100;

                $diamondItem['tax_details'] = [
                    'total_gst_amount' => $diamondTaxAmount,
                    'diamond_gst_amount' => $diamondTaxAmount,
                    'price_without_gst' => $diamondPrice - $diamondTaxAmount,
                    'total_tax_rate' => $diamondGstRate
                ];

                $processedItems[] = $diamondItem;
            }

            Log::info('Combo processed: ' . count($processedItems) . ' items created');
            return $processedItems;
        } catch (\Exception $e) {
            Log::error('Error processing combo item: ' . $e->getMessage());
            return $processedItems;
        }
    }
    private function extractTaxFromOrder($order)
    {
        $taxDetails = [
            'total_gst_amount' => 0,
            'gold_gst_amount' => 0,
            'diamond_gst_amount' => 0,
            'making_gst_amount' => 0,
            'price_without_gst' => 0,
            'gst_breakdown' => [],
            'total_tax_rate' => 0
        ];

        try {
            $itemDetails = $order->item_details;

            if (is_string($itemDetails)) {
                $itemDetails = json_decode($itemDetails, true);
            }

            if (is_array($itemDetails) && isset($itemDetails[0])) {
                foreach ($itemDetails as $item) {
                    // ✅ COMBO ITEM - SPECIAL HANDLING
                    if (($item['productType'] ?? '') === 'combo') {
                        $ring = $item['ring'] ?? [];
                        $diamond = $item['diamond'] ?? [];

                        // Add ring tax
                        $taxDetails['total_gst_amount'] += $ring['total_gst_amount'] ?? 0;
                        $taxDetails['gold_gst_amount'] += $ring['gold_gst_amount'] ?? 0;
                        $taxDetails['diamond_gst_amount'] += $ring['diamond_gst_amount'] ?? 0;
                        $taxDetails['making_gst_amount'] += $ring['making_gst_amount'] ?? 0;
                        $taxDetails['price_without_gst'] += $ring['price_without_gst'] ?? 0;

                        // Add diamond tax (0.25%)
                        $diamondPrice = $diamond['price'] ?? 0;
                        $diamondGstAmount = ($diamondPrice * 0.25) / 100;
                        $taxDetails['total_gst_amount'] += $diamondGstAmount;
                        $taxDetails['diamond_gst_amount'] += $diamondGstAmount;
                        $taxDetails['price_without_gst'] += ($diamondPrice - $diamondGstAmount);

                        // Store GST breakdown from ring
                        if (empty($taxDetails['gst_breakdown']) && isset($ring['gst_breakdown'])) {
                            $taxDetails['gst_breakdown'] = $ring['gst_breakdown'];
                        }

                        // Add diamond GST to breakdown
                        if ($diamondGstAmount > 0) {
                            $taxDetails['gst_breakdown'][] = [
                                'type' => 'diamond',
                                'name' => 'Diamond GST',
                                'rate' => '0.25',
                                'rate_formatted' => '0.25%',
                                'base_amount' => $diamondPrice,
                                'gst_amount' => $diamondGstAmount,
                                'formatted' => 'Diamond GST (0.25%): ₹' . number_format($diamondGstAmount, 2),
                                'calculation' => '₹' . number_format($diamondPrice, 2) . ' × 0.25% = ₹' . number_format($diamondGstAmount, 2)
                            ];
                        }

                        continue;
                    }

                    // Sum up all tax values for non-combo items
                    $taxDetails['total_gst_amount'] += $item['total_gst_amount'] ?? 0;
                    $taxDetails['gold_gst_amount'] += $item['gold_gst_amount'] ?? 0;
                    $taxDetails['diamond_gst_amount'] += $item['diamond_gst_amount'] ?? 0;
                    $taxDetails['making_gst_amount'] += $item['making_gst_amount'] ?? 0;
                    $taxDetails['price_without_gst'] += $item['price_without_gst'] ?? 0;

                    // Store GST breakdown for first item
                    if (empty($taxDetails['gst_breakdown']) && isset($item['gst_breakdown'])) {
                        $taxDetails['gst_breakdown'] = $item['gst_breakdown'];
                    }

                    // Tax rate
                    if (isset($item['total_tax_rate'])) {
                        $taxDetails['total_tax_rate'] = $item['total_tax_rate'];
                    }
                }
            }

            // If no tax found in items, calculate from order total
            if ($taxDetails['total_gst_amount'] == 0 && $order->total_price > 0) {
                // Estimate tax (6.25% for jewelry)
                $estimatedTaxRate = 6.25;
                $taxDetails['price_without_gst'] = $order->total_price / (1 + ($estimatedTaxRate / 100));
                $taxDetails['total_gst_amount'] = $order->total_price - $taxDetails['price_without_gst'];
                $taxDetails['total_tax_rate'] = $estimatedTaxRate;
            }
        } catch (\Exception $e) {
            Log::error('Error extracting tax from order: ' . $e->getMessage());
        }

        return $taxDetails;
    }
    private function getWarrantyInfo($plan)
    {
        $warrantyPlans = [
            '1-year' => ['duration' => '1 Year', 'features' => 'Basic Warranty'],
            '2-year' => ['duration' => '2 Years', 'features' => 'Extended Warranty'],
            '3-year' => ['duration' => '3 Years', 'features' => 'Premium Warranty with Insurance'],
            '5-year' => ['duration' => '5 Years', 'features' => 'Gold Package with Full Coverage']
        ];

        return $warrantyPlans[$plan] ?? ['duration' => 'Standard', 'features' => 'Basic Coverage'];
    }


    public function downloadInvoice(Order $order)
    {
        $processedItems = $this->processItemsForOrder($order);
        $pdf = Pdf::loadView('admin.DiamondMaster.Orders.invoice', compact('order', 'processedItems'));
        return $pdf->download("Invoice-{$order->order_id}.pdf");
    }


    public function sendInvoice(Request $request, Order $order)
    {
        try {
            $to = $request->query('to', 'user');
            $email = $to === 'admin'
                ? config('mail.from.address')
                : ($order->user->email ?? $order->address_email ?? $order->user->email ?? null);

            if (!$email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid email found for the recipient'
                ], 400);
            }

            $processedItems = $this->processItemsForOrder($order);
            $pdf = Pdf::loadView('admin.DiamondMaster.Orders.invoice', compact('order', 'processedItems'));
            $pdfPath = 'invoices/' . $order->order_id . '.pdf';
            Storage::disk('local')->put($pdfPath, $pdf->output());

            $pdfUrl = url('storage/' . $pdfPath);


            // Send email
            Mail::send('admin.DiamondMaster.emails.email_template_invoice', [
                'order' => $order,
                'downloadUrl' => $pdfUrl
            ], function ($message) use ($order, $email, $pdf) {
                $message->to($email)
                    ->subject("Invoice - {$order->order_id}")
                    ->attachData($pdf->output(), "Invoice-{$order->order_id}.pdf");
            });

            return response()->json([
                'success' => true,
                'message' => "Invoice sent to " . ($to === 'admin' ? 'admin' : 'customer')
            ]);
        } catch (\Exception $e) {
            Log::error('Invoice sending error: ' . $e->getMessage());
            Log::error('Invoice error trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processItemsForOrder($order)
    {
        try {
            $itemDetails = $order->item_details;

            if (is_string($itemDetails)) {
                $itemDetails = json_decode($itemDetails, true);
            }

            if (!is_array($itemDetails)) {
                $itemDetails = [];
            }

            $processedItems = [];

            $extractName = function ($value) {
                if (is_array($value)) {
                    return $value['name'] ?? $value['short_name'] ?? $value['shape_name'] ?? 'N/A';
                }
                if (is_object($value)) {
                    return $value->name ?? $value->short_name ?? $value->shape_name ?? 'N/A';
                }
                return $value ?? 'N/A';
            };

            // Helper function to create diamond name
            $createDiamondName = function ($item) use ($extractName) {
                $shape = $extractName($item['shape'] ?? null);
                $carat = $item['carat_weight'] ?? $item['carat'] ?? null;
                $color = $extractName($item['color'] ?? null);
                $clarity = $extractName($item['clarity'] ?? null);
                $cert = $item['certificate_number'] ?? null;

                // Get diamond type from item
                $diamondTypeValue = $item['diamond_type'] ?? 1; // Default to Natural
                $diamondType = ($diamondTypeValue == 1) ? 'Natural' : 'CVD';

                // Create descriptive name
                $nameParts = [];

                if ($shape !== 'N/A' && $shape !== null) {
                    $nameParts[] = $shape;
                }

                if ($carat && $carat !== 'N/A') {
                    $nameParts[] = $carat . 'ct';
                }

                if ($color !== 'N/A' && $color !== null) {
                    $nameParts[] = $color;
                }

                if ($clarity !== 'N/A' && $clarity !== null) {
                    $nameParts[] = $clarity;
                }

                // Add diamond type to name
                $nameParts[] = $diamondType . ' Diamond';

                $name = implode(' ', $nameParts);

                // Add certificate if available
                if ($cert && $cert !== 'N/A') {
                    $name .= ' (' . $cert . ')';
                }

                return $name;
            };

            Log::info('Processing items for order: ' . $order->order_id);

            if (is_array($itemDetails) && isset($itemDetails[0])) {
                Log::info('Processing as indexed array');
                foreach ($itemDetails as $item) {
                    if (is_array($item)) {
                        $type = $item['productType'] ?? 'gift';
                        $name = $item['name'] ?? ($item['product_name'] ?? 'Product');

                        $processedItem = [
                            'type' => $type,
                            'id' => $item['id'] ?? null,
                            'name' => $name,
                            'quantity' => $item['itemQuantity'] ?? $item['quantity'] ?? 1,
                            'price' => $item['price'] ?? 0,
                            'type_label' => ucfirst($type)
                        ];

                        if ($type === 'diamond') {
                            // For diamonds without proper name, create descriptive name
                            if (empty($name) || $name === 'Diamond' || $name === 'Natural Diamond' || $name === 'CVD Diamond') {
                                $processedItem['name'] = $createDiamondName($item);
                            }

                            // Store diamond type separately as well
                            $processedItem['diamond_type'] = $item['diamond_type'] ?? 1;
                            $processedItem['diamond_type_label'] = ($processedItem['diamond_type'] == 1) ? 'Natural' : 'CVD';

                            $processedItem['certificate_number'] = $item['certificate_number'] ?? 'N/A';
                            $processedItem['carat_weight'] = $item['carat_weight'] ?? $item['carat'] ?? 'N/A';
                            $processedItem['color'] = $extractName($item['color'] ?? null);
                            $processedItem['clarity'] = $extractName($item['clarity'] ?? null);
                            $processedItem['shape'] = $extractName($item['shape'] ?? null);
                            $processedItem['cut'] = $extractName($item['cut'] ?? null);
                            $processedItem['certificate_company'] = $item['certificate_company']['dl_name'] ?? ($item['certificate_company'] ?? 'N/A');
                            $processedItem['polish'] = $item['polish']['name'] ?? ($item['polish'] ?? 'N/A');
                            $processedItem['symmetry'] = $item['symmetry']['name'] ?? ($item['symmetry'] ?? 'N/A');
                            $processedItem['fluorescence'] = $item['fluorescence']['name'] ?? ($item['fluorescence'] ?? 'N/A');
                            $processedItem['measurements'] = $item['measurements'] ?? 'N/A';
                        } elseif ($type === 'jewelry') {
                            $processedItem['metal_type'] = $extractName($item['metal_type'] ?? null);
                            $processedItem['metal_color'] = $extractName($item['metal_color'] ?? null);
                            $processedItem['metal_purity'] = $extractName($item['metal_purity'] ?? null);
                            $processedItem['size'] = $item['size'] ?? ($item['ring_size'] ?? 'N/A');
                            $processedItem['carat'] = $item['carat'] ?? 'N/A';
                        } elseif ($type === 'gift') {
                            $processedItem['size'] = $item['size'] ?? 'N/A';
                            $processedItem['metal_type'] = $extractName($item['metal_type'] ?? null);
                            $processedItem['metal_color'] = $extractName($item['metal_color'] ?? null);
                            $processedItem['shape'] = $extractName($item['shape'] ?? null);
                        } elseif ($type === 'combo') {
                            $processedItem['size'] = $item['size'] ?? 'N/A';
                            $processedItem['metal_type'] = $extractName($item['metal_type'] ?? null);
                            $processedItem['diamond_certificate'] = $item['diamond_certificate'] ?? ($item['diamond']['certificate_number'] ?? 'N/A');
                            $processedItem['ring_price'] = $item['ring_price'] ?? ($item['ring']['price'] ?? 0);
                            $processedItem['diamond_price'] = $item['diamond_price'] ?? ($item['diamond']['price'] ?? 0);
                            $processedItem['diamond_type'] = $item['diamond_type'] ?? ($item['diamond']['diamond_type'] ?? 1);
                            $processedItem['diamond_type_label'] = ($processedItem['diamond_type'] == 1) ? 'Natural' : 'CVD';
                        }

                        $processedItems[] = $processedItem;
                    }
                }
            }

            if (empty($processedItems)) {
                if (isset($itemDetails['diamond'])) {
                    foreach ($itemDetails['diamond'] as $diamond) {
                        $name = $diamond['name'] ?? $diamond['diamond_name'] ?? 'Diamond';
                        if (empty($name) || $name === 'Diamond' || $name === 'Natural Diamond' || $name === 'CVD Diamond') {
                            $name = $createDiamondName($diamond);
                        }

                        $processedItems[] = [
                            'type' => 'diamond',
                            'id' => $diamond['id'] ?? null,
                            'name' => $name,
                            'quantity' => $diamond['quantity'] ?? 1,
                            'price' => $diamond['price'] ?? 0,
                            'diamond_type' => $diamond['diamond_type'] ?? 1,
                            'diamond_type_label' => ($diamond['diamond_type'] == 1) ? 'Natural' : 'CVD',
                            'certificate_number' => $diamond['certificate_number'] ?? 'N/A',
                            'carat_weight' => $diamond['carat_weight'] ?? 'N/A',
                            'color' => $extractName($diamond['color'] ?? null),
                            'clarity' => $extractName($diamond['clarity'] ?? null),
                            'shape' => $extractName($diamond['shape'] ?? null),
                            'type_label' => 'Diamond'
                        ];
                    }
                }
            }

            if (empty($processedItems)) {
                $processedItems[] = [
                    'type' => 'product',
                    'id' => null,
                    'name' => 'Order Products',
                    'quantity' => $order->total_quantity ?? 1,
                    'price' => $order->total_price ?? 0,
                    'type_label' => 'Product'
                ];
            }

            Log::info('Processed items: ' . json_encode($processedItems));
            return $processedItems;
        } catch (\Exception $e) {
            Log::error("Error processing items for order {$order->id}: " . $e->getMessage());
            Log::error("Error trace: " . $e->getTraceAsString());

            return [[
                'type' => 'product',
                'id' => null,
                'name' => 'Order Products',
                'quantity' => $order->total_quantity ?? 1,
                'price' => $order->total_price ?? 0,
                'type_label' => 'Product'
            ]];
        }
    }



    // Enhanced changeStatus method to use Order model methods
    public function changeStatus(Request $request, Order $order)
    {
        $request->validate([
            'order_status' => 'required|string',
            'reason' => 'nullable|string|max:500'
        ]);

        $newStatus = $request->order_status;
        $reason = $request->reason;

        try {
            switch ($newStatus) {
                case 'cancelled':
                    if ($order->cancel($reason)) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Order cancelled successfully'
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Order cannot be cancelled in its current status'
                        ], 400);
                    }
                    break;

                case 'delivered':
                    $order->markAsDelivered();
                    return response()->json([
                        'success' => true,
                        'message' => 'Order marked as delivered successfully'
                    ]);
                    break;

                case 'shipped':
                    if ($order->markAsShipped()) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Order marked as shipped successfully'
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Order cannot be shipped in its current status'
                        ], 400);
                    }
                    break;

                default:
                    // For other status updates
                    $order->update([
                        'order_status' => $newStatus,
                        'updated_at' => now()
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Order status updated successfully'
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Error changing order status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating order status: ' . $e->getMessage()
            ], 500);
        }
    }

    // Method to process refund manually
    public function processRefund(Request $request, Order $order)
    {
        try {
            if ($order->payment_status !== 'refunded' && $order->order_status === 'cancelled') {
                $order->processRefund();

                return response()->json([
                    'success' => true,
                    'message' => 'Refund processed successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund cannot be processed for this order'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing refund: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing refund: ' . $e->getMessage()
            ], 500);
        }
    }

    // Method to check cancellation eligibility
    public function checkCancellation(Order $order)
    {
        return response()->json([
            'can_be_cancelled' => $order->canBeCancelled(),
            'cancellation_message' => $order->cancellation_message,
            'current_status' => $order->order_status
        ]);
    }


    public function store(Request $request)
    {
        if ($request->ajax()) {
            return $this->storeAjax($request);
        }

        $validator = Validator::make($request->all(), [
            'user_id'        => 'required|exists:users,id',
            'user_name'      => 'required|string',
            'contact_number' => 'required|string',
            'item_details'   => 'required|json',
            'total_price'    => 'required|numeric',
            'shipping_cost'  => 'nullable|numeric',
            'discount'       => 'nullable|numeric',
            'coupon_code'    => 'nullable|string',
            'coupon_discount' => 'nullable|numeric',
            'address'        => 'required|json',
            'billing_address' => 'nullable|json',
            'order_status'   => 'required|string',
            'payment_mode'   => 'required|string',
            'payment_status' => 'required|string',
            'transaction_id' => 'nullable|string',
            'is_gift'        => 'nullable|boolean',
            'notes'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $validated['order_id'] = 'ORD-' . Str::uuid();

        // ✅ COUPON VALIDATION
        if (!empty($validated['coupon_code'])) {
            $itemDetails = json_decode($validated['item_details'], true);
            $items = $itemDetails['items'] ?? [];
            $cartTotalWithoutDiscount = 0;

            foreach ($items as $item) {
                $quantity = $item['quantity'] ?? 1;
                $price = $item['price'] ?? 0;
                $cartTotalWithoutDiscount += ($price * $quantity);
            }

            $couponValidation = Order::validateCoupon($validated['coupon_code'], $cartTotalWithoutDiscount);

            if (!$couponValidation['valid']) {
                return redirect()->back()
                    ->with('error', $couponValidation['message'])
                    ->withInput();
            }

            $coupon = $couponValidation['coupon'];
            $calculatedDiscount = $coupon->calculateDiscount($cartTotalWithoutDiscount);

            if (abs($calculatedDiscount - ($validated['coupon_discount'] ?? 0)) > 0.01) {
                return redirect()->back()
                    ->with('error', 'Coupon discount mismatch. Please try again.')
                    ->withInput();
            }
        }

        // Parse the payload
        $itemDetails = json_decode($validated['item_details'], true);
        $payload = $itemDetails['items'] ?? [];

        // Initialize arrays for items_id and quantities
        $itemsId = [
            'diamond' => [],
            'jewelry' => [],
            'gift'    => [],
            'build'   => [],
            'combo'   => [],
        ];

        $quantities = [
            'diamond' => 0,
            'jewelry' => 0,
            'gift'    => 0,
            'build'   => 0,
            'combo'   => 0,
            'total'   => 0
        ];

        $itemsForTax = [];

        foreach ($payload as $item) {
            $type = $item['productType'] ?? $item['type'] ?? 'jewelry';
            $quantity = $item['quantity'] ?? 1;

            switch ($type) {
                case 'diamond':
                    $diamondId = $item['id'] ?? $item['diamondid'] ?? null;
                    if (!empty($diamondId)) {
                        $itemsId['diamond'][] = [
                            'id' => $diamondId,
                            'quantity' => $quantity,
                            'price' => $item['price'] ?? 0,
                            'certificate_number' => $item['certificate_number'] ?? null,
                            'carat' => $item['carat'] ?? null,
                            'shape' => $item['shape'] ?? null
                        ];
                        $quantities['diamond'] += $quantity;

                        $itemsForTax[] = [
                            'type' => 'diamond',
                            'id' => $diamondId,
                            'price' => $item['price'] ?? 0,
                            'quantity' => $quantity,
                            'name' => $item['name'] ?? 'Diamond'
                        ];
                    }
                    break;

                case 'jewelry':
                    $productId = $item['id'] ?? null;
                    if (!empty($productId)) {
                        $itemsId['jewelry'][] = [
                            'id' => $productId,
                            'variation_id' => $item['variation_id'] ?? null,
                            'quantity' => $quantity,
                            'price' => $item['price'] ?? 0,
                            'title' => $item['title'] ?? $item['name'] ?? '',
                            'type' => $item['type'] ?? 'jewelry'
                        ];
                        $quantities['jewelry'] += $quantity;

                        $itemsForTax[] = [
                            'type' => 'jewelry',
                            'product_id' => $productId,
                            'variation_id' => $item['variation_id'] ?? null,
                            'price' => $item['price'] ?? 0,
                            'quantity' => $quantity,
                            'name' => $item['name'] ?? $item['title'] ?? 'Jewelry'
                        ];
                    }
                    break;

                case 'gift':
                    if (!empty($item['id'])) {
                        $itemsId['gift'][] = [
                            'id' => $item['id'],
                            'quantity' => $quantity,
                            'price' => $item['price'] ?? 0,
                            'name' => $item['name'] ?? '',
                            'type' => $type
                        ];
                        $quantities['gift'] += $quantity;
                    }
                    break;

                case 'build':
                    if (!empty($item['id'])) {
                        $itemsId['build'][] = [
                            'id'   => $item['id'],
                            'size' => $item['size'] ?? null,
                            'quantity' => $quantity,
                            'price' => $item['price'] ?? 0,
                            'specifications' => $item['specifications'] ?? []
                        ];
                        $quantities['build'] += $quantity;
                    }
                    break;

                case 'combo':
                    $itemsId['combo'][] = [
                        'diamond_id' => $item['diamond']['diamondid'] ?? $item['diamond_id'] ?? null,
                        'product_id' => $item['ring']['id'] ?? $item['product_id'] ?? null,
                        'size'       => $item['size'] ?? null,
                        'quantity'   => $quantity,
                        'price'      => $item['price'] ?? 0,
                        'diamond_details' => $item['diamond'] ?? [],
                        'ring_details' => $item['ring'] ?? []
                    ];
                    $quantities['combo'] += $quantity;
                    break;
            }

            $quantities['total'] += $quantity;
        }

        $validated['total_quantity'] = $quantities['total'];
        $validated['quantities'] = $quantities;

        $nonEmptyTypes = collect($itemsId)->filter(fn($ids) => !empty($ids))->keys();

        if ($nonEmptyTypes->isEmpty()) {
            $productType = 'empty';
        } elseif ($nonEmptyTypes->count() === 1) {
            $productType = $nonEmptyTypes->first();
        } else {
            $productType = 'multiple';
        }

        $validated['items_id'] = $itemsId;
        $validated['product_type'] = $productType;

        // If billing address is not provided, use shipping address
        if (empty($validated['billing_address']) && !empty($validated['address'])) {
            $validated['billing_address'] = $validated['address'];
        }

        try {
            DB::beginTransaction();
            $order = Order::create($validated);
            $addressArray = json_decode($validated['address'], true);
            $isInterState = $this->checkIfInterState($addressArray);
            if (!empty($itemsForTax)) {
                $this->saveOrderTaxes($order, $itemsForTax, $isInterState);
            }

            // ✅ Send confirmation emails
            $this->sendOrderConfirmationEmail($order);

            DB::commit();

            return redirect()->route('admin.orders.index')
                ->with('success', 'Order created successfully!')
                ->with('invoice_number', $order->invoice_number)
                ->with('invoice_date', $order->invoice_date->format('d-m-Y'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Admin Order creation failed: " . $e->getMessage());
            Log::error("Error trace: " . $e->getTraceAsString());

            return redirect()->back()
                ->with('error', 'Failed to save order: ' . $e->getMessage())
                ->withInput();
        }
    }

    private function storeAjax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'        => 'required|exists:users,id',
            'user_name'      => 'required|string',
            'contact_number' => 'required|string',
            'item_details'   => 'required|json',
            'total_price'    => 'required|numeric',
            'shipping_cost'  => 'nullable|numeric',
            'discount'       => 'nullable|numeric',
            'coupon_code'    => 'nullable|string',
            'coupon_discount' => 'nullable|numeric',
            'address'        => 'required|json',
            'billing_address' => 'nullable|json',
            'order_status'   => 'required|string',
            'payment_mode'   => 'required|string',
            'payment_status' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['order_id'] = 'ORD-' . Str::uuid();
        // ✅ Generate Invoice Number (Unique)
        $validated['invoice_number'] = $this->generateInvoiceNumber();

        // ✅ Set Invoice Date (Current Date)
        $validated['invoice_date'] = now();


        // ✅ Parse item_details
        $itemDetails = json_decode($validated['item_details'], true);

        if (!isset($itemDetails['items']) || !is_array($itemDetails['items'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid item details format'
            ], 422);
        }

        $items = $itemDetails['items'];

        // ✅ COUPON VALIDATION
        if (!empty($validated['coupon_code'])) {
            $cartTotalWithoutDiscount = 0;

            foreach ($items as $item) {
                $quantity = $item['quantity'] ?? 1;
                $price = $item['price'] ?? 0;
                $cartTotalWithoutDiscount += ($price * $quantity);
            }

            $couponValidation = Order::validateCoupon($validated['coupon_code'], $cartTotalWithoutDiscount);

            if (!$couponValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $couponValidation['message']
                ], 422);
            }

            $coupon = $couponValidation['coupon'];
            $calculatedDiscount = $coupon->calculateDiscount($cartTotalWithoutDiscount);

            if (abs($calculatedDiscount - ($validated['coupon_discount'] ?? 0)) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Coupon discount mismatch. Please try again.'
                ], 422);
            }
        }

        // Initialize arrays for items_id and quantities
        $itemsId = [
            'diamond' => [],
            'jewelry' => [],
            'gift'    => [],
            'build'   => [],
            'combo'   => [],
        ];

        $quantities = [
            'diamond' => 0,
            'jewelry' => 0,
            'gift'    => 0,
            'build'   => 0,
            'combo'   => 0,
            'total'   => 0
        ];

        // ✅ Array to store items for tax calculation
        $itemsForTax = [];

        // Process each item in the payload
        foreach ($items as $item) {
            $type = $item['productType'] ?? $item['type'] ?? 'jewelry';
            $quantity = $item['quantity'] ?? 1;

            switch ($type) {
                case 'diamond':
                    $diamondId = $item['id'] ?? $item['diamondid'] ?? null;
                    if (!empty($diamondId)) {
                        $itemsId['diamond'][] = [
                            'id' => $diamondId,
                            'quantity' => $quantity,
                            'price' => $item['price'] ?? 0,
                            'certificate_number' => $item['certificate_number'] ?? null,
                            'carat' => $item['carat'] ?? null,
                            'shape' => $item['shape'] ?? null
                        ];
                        $quantities['diamond'] += $quantity;

                        $itemsForTax[] = [
                            'type' => 'diamond',
                            'id' => $diamondId,
                            'price' => $item['price'] ?? 0,
                            'quantity' => $quantity,
                            'name' => $item['name'] ?? 'Diamond'
                        ];
                    }
                    break;

                case 'jewelry':
                    $productId = $item['id'] ?? null;
                    if (!empty($productId)) {
                        $itemsId['jewelry'][] = [
                            'id' => $productId,
                            'variation_id' => $item['variation_id'] ?? null,
                            'quantity' => $quantity,
                            'price' => $item['price'] ?? 0,
                            'title' => $item['title'] ?? $item['name'] ?? '',
                            'type' => $item['type'] ?? 'jewelry'
                        ];
                        $quantities['jewelry'] += $quantity;

                        $itemsForTax[] = [
                            'type' => 'jewelry',
                            'product_id' => $productId,
                            'variation_id' => $item['variation_id'] ?? null,
                            'price' => $item['price'] ?? 0,
                            'quantity' => $quantity,
                            'name' => $item['name'] ?? $item['title'] ?? 'Jewelry'
                        ];
                    }
                    break;
            }

            $quantities['total'] += $quantity;
        }

        // Calculate total quantities
        $validated['total_quantity'] = $quantities['total'];
        $validated['quantities'] = $quantities;

        // Decide product type for DB
        $nonEmptyTypes = collect($itemsId)->filter(fn($ids) => !empty($ids))->keys();

        if ($nonEmptyTypes->isEmpty()) {
            $productType = 'empty';
        } elseif ($nonEmptyTypes->count() === 1) {
            $productType = $nonEmptyTypes->first();
        } else {
            $productType = 'multiple';
        }

        // Add both product type and items_id into validated array
        $validated['items_id'] = $itemsId;
        $validated['product_type'] = $productType;

        // If billing address is not provided, use shipping address
        if (empty($validated['billing_address']) && !empty($validated['address'])) {
            $validated['billing_address'] = $validated['address'];
        }

        try {
            DB::beginTransaction();
            $order = Order::create($validated);

            $addressArray = json_decode($validated['address'], true);
            $isInterState = $this->checkIfInterState($addressArray);

            if (!empty($itemsForTax)) {
                $this->saveOrderTaxes($order, $itemsForTax, $isInterState);
            }

            $this->sendOrderConfirmationEmail($order);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully!',
                'order_id' => $order->order_id,
                'invoice_number' => $order->invoice_number,
                'invoice_date' => $order->invoice_date
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Admin Order creation failed: " . $e->getMessage());
            Log::error("Error trace: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save order: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateInvoiceNumber()
    {
        try {
            $yearMonth = date('Y-m');
            $currentMonth = date('Y-m');
            $invoiceCount = Order::whereYear('created_at', date('Y'))
                ->whereMonth('created_at', date('m'))
                ->count();
            $sequentialNumber = str_pad($invoiceCount + 1, 5, '0', STR_PAD_LEFT);

            // Create invoice number
            $invoiceNumber = "INV-{$yearMonth}-{$sequentialNumber}";

            // Check if invoice number already exists
            $counter = 1;
            while (Order::where('invoice_number', $invoiceNumber)->exists()) {
                $sequentialNumber = str_pad($invoiceCount + 1 + $counter, 5, '0', STR_PAD_LEFT);
                $invoiceNumber = "INV-{$yearMonth}-{$sequentialNumber}";
                $counter++;

                if ($counter > 10) {
                    $randomSuffix = strtoupper(Str::random(3));
                    $invoiceNumber = "INV-{$yearMonth}-{$sequentialNumber}-{$randomSuffix}";
                    break;
                }
            }

            Log::info("Generated Invoice Number: {$invoiceNumber}");
            return $invoiceNumber;
        } catch (\Exception $e) {
            Log::error('Error generating invoice number: ' . $e->getMessage());

            // Fallback invoice number
            $timestamp = time();
            $random = strtoupper(Str::random(4));
            return "INV-{$timestamp}-{$random}";
        }
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'user_name' => 'sometimes|string',
            'contact_number' => 'sometimes|string',
            'total_price' => 'sometimes|numeric',
            'shipping_cost' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'coupon_code' => 'nullable|string',
            'coupon_discount' => 'nullable|numeric',
            'order_status' => 'sometimes|string',
            'payment_status' => 'sometimes|string',
        ]);

        $order->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully'
        ]);
    }

    public function destroy(Order $order)
    {
        try {
            // Check if order can be deleted (only allow deletion of cancelled or very old orders)
            if (
                !in_array($order->order_status, ['cancelled', 'pending']) &&
                $order->created_at->gt(now()->subDays(30))
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only cancelled or old pending orders can be deleted'
                ], 400);
            }

            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting order: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ NEW: Add email confirmation method in Admin Controller
    private function sendOrderConfirmationEmail($order)
    {
        try {
            $userEmail = $order->user->email ?? $order->address_email ?? null;
            $adminEmail = config('mail.from.address');

            Log::info('=== ADMIN ORDER EMAIL SENDING STARTED ===');
            Log::info('Order ID: ' . $order->order_id);
            Log::info('User Email: ' . $userEmail);
            Log::info('Admin Email: ' . $adminEmail);
            Log::info('Mail From Address: ' . config('mail.from.address'));

            if (!$userEmail) {
                Log::warning('No user email found for order confirmation: ' . $order->order_id);
            }

            // Build processedItems from order
            $processedItems = [];
            if ($order->item_details) {
                $itemDetails = json_decode($order->item_details, true);
                $items = $itemDetails['items'] ?? [];

                foreach ($items as $item) {
                    $processedItems[] = [
                        'name' => $item['name'] ?? $item['title'] ?? 'Product',
                        'quantity' => $item['quantity'] ?? $item['itemQuantity'] ?? 1,
                        'price' => $item['price'] ?? 0
                    ];
                }
            }

            // 1. Send to User
            if ($userEmail) {
                Log::info('Sending user email to: ' . $userEmail);
                Mail::send('admin.DiamondMaster.emails.order_confirmation_user', [
                    'order' => $order,
                    'processedItems' => $processedItems
                ], function ($message) use ($order, $userEmail) {
                    $message->to($userEmail)
                        ->subject('Order Confirmation - ' . $order->order_id);
                });
                Log::info('User email sent successfully');
            }

            // 2. Send to Admin
            Log::info('Sending admin email to: ' . $adminEmail);
            Mail::send('admin.DiamondMaster.emails.order_confirmation_admin', [
                'order' => $order,
                'processedItems' => $processedItems
            ], function ($message) use ($order, $adminEmail) {
                $message->to($adminEmail)
                    ->subject('New Order Received - ' . $order->order_id);
            });
            Log::info('Admin email sent successfully');

            Log::info('=== ADMIN ORDER EMAIL SENDING COMPLETED ===');
        } catch (\Exception $e) {
            Log::error('Error sending order confirmation emails from Admin: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());
            // Don't throw error, just log it so order creation doesn't fail
        }
    }

    private function saveOrderTaxes($order, $items, $isInterState)
    {
        try {
            foreach ($items as $item) {
                if ($item['type'] === 'jewelry') {
                    $this->saveJewelryTaxes($order, $item, $isInterState);
                } elseif ($item['type'] === 'diamond') {
                    $this->saveDiamondTaxes($order, $item, $isInterState);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error saving order taxes: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    private function saveJewelryTaxes($order, $item, $isInterState)
    {
        try {
            if (isset($item['variation_id']) && $item['variation_id']) {
                $variation = ProductVariation::find($item['variation_id']);

                if ($variation) {
                    $product = $variation->product;
                    $quantity = $item['quantity'];
                    $unitPrice = $item['price'];
                    $totalPrice = $unitPrice * $quantity;
                    $makingCharges = ($variation->making_charges ?? 0) * $quantity;
                    $goldGstRate = $variation->gold_gst_rate ?? 0;
                    $diamondGstRate = $variation->diamond_gst_rate ?? 0;
                    $makingGstRate = $variation->making_gst_rate ?? 0;

                    if ($goldGstRate > 0) {
                        OrderItemTax::create([
                            'order_id' => $order->id,
                            'product_id' => $product->products_id,
                            'product_variation_id' => $variation->id,
                            'component_type' => 'gold_tax',
                            'hsn_code' => '7113',
                            'tax_type' => 'GST',
                            'taxable_value' => $totalPrice,
                            'tax_rate' => $goldGstRate,
                            'tax_amount' => ($totalPrice * $goldGstRate) / 100,
                            'item_name' => $product->products_name,
                            'item_type' => 'jewelry',
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'description' => 'Gold GST'
                        ]);
                    }
                    if ($diamondGstRate > 0 && ($variation->diamond_weight ?? 0) > 0) {
                        OrderItemTax::create([
                            'order_id' => $order->id,
                            'product_id' => $product->products_id,
                            'product_variation_id' => $variation->id,
                            'component_type' => 'diamond_tax',
                            'hsn_code' => '7102',
                            'tax_type' => 'GST',
                            'taxable_value' => $totalPrice,
                            'tax_rate' => $diamondGstRate,
                            'tax_amount' => ($totalPrice * $diamondGstRate) / 100,
                            'item_name' => $product->products_name . ' (Diamond)',
                            'item_type' => 'jewelry',
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'description' => 'Diamond GST'
                        ]);
                    }
                    if ($makingGstRate > 0 && $makingCharges > 0) {
                        OrderItemTax::create([
                            'order_id' => $order->id,
                            'product_id' => $product->products_id,
                            'product_variation_id' => $variation->id,
                            'component_type' => 'making_tax',
                            'hsn_code' => '7113',
                            'tax_type' => 'GST',
                            'taxable_value' => $makingCharges,
                            'tax_rate' => $makingGstRate,
                            'tax_amount' => ($makingCharges * $makingGstRate) / 100,
                            'item_name' => $product->products_name . ' - Making Charges',
                            'item_type' => 'jewelry',
                            'quantity' => $quantity,
                            'unit_price' => $variation->making_charges ?? 0,
                            'description' => 'Making Charges GST'
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error saving jewelry taxes: ' . $e->getMessage());
        }
    }

    private function saveDiamondTaxes($order, $item, $isInterState)
    {
        try {
            $diamond = DiamondMaster::find($item['id']);

            if ($diamond) {
                $quantity = $item['quantity'];
                $unitPrice = $item['price'];
                $totalPrice = $unitPrice * $quantity;
                $diamondGstRate = 0.25;
                $diamondName = 'Diamond - ' .
                    ($diamond->certificate_number ?? $diamond->stock_number ?? 'N/A') . ' - ' .
                    ($diamond->carat_weight ?? '') . 'ct ' .
                    ($diamond->shape->name ?? '');
                OrderItemTax::create([
                    'order_id' => $order->id,
                    'diamond_id' => $diamond->diamondid,
                    'component_type' => 'diamond_tax',
                    'hsn_code' => '7102',
                    'tax_type' => $isInterState ? 'IGST' : 'CGST',
                    'taxable_value' => $totalPrice,
                    'tax_rate' => $diamondGstRate,
                    'tax_amount' => ($totalPrice * $diamondGstRate) / 100,
                    'item_name' => $diamondName,
                    'item_type' => 'diamond',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'description' => 'Diamond GST'
                ]);
                if (!$isInterState) {
                    OrderItemTax::create([
                        'order_id' => $order->id,
                        'diamond_id' => $diamond->diamondid,
                        'component_type' => 'diamond_tax',
                        'hsn_code' => '7102',
                        'tax_type' => 'SGST',
                        'taxable_value' => $totalPrice,
                        'tax_rate' => $diamondGstRate,
                        'tax_amount' => ($totalPrice * $diamondGstRate) / 100,
                        'item_name' => $diamondName,
                        'item_type' => 'diamond',
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'description' => 'Diamond SGST'
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error saving diamond taxes: ' . $e->getMessage());
        }
    }

    private function createTaxRecord($order, $data, $isInterState)
    {
        // Calculate tax amount
        $taxAmount = ($data['taxable_value'] * $data['tax_rate']) / 100;

        // Create CGST/IGST record
        OrderItemTax::create([
            'order_id' => $order->id,
            'product_id' => $data['product_id'] ?? null,
            'product_variation_id' => $data['product_variation_id'] ?? null,
            'diamond_id' => $data['diamond_id'] ?? null,
            'component_type' => $data['component_type'],
            'hsn_code' => $data['hsn_code'],
            'tax_type' => $data['tax_type'],
            'taxable_value' => $data['taxable_value'],
            'tax_rate' => $data['tax_rate'],
            'tax_amount' => $taxAmount,
            'item_name' => $data['item_name'],
            'item_type' => $data['item_type'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'description' => $data['description']
        ]);

        // Create SGST record if intrastate
        if (!$isInterState && $data['tax_type'] === 'CGST') {
            OrderItemTax::create([
                'order_id' => $order->id,
                'product_id' => $data['product_id'] ?? null,
                'product_variation_id' => $data['product_variation_id'] ?? null,
                'diamond_id' => $data['diamond_id'] ?? null,
                'component_type' => $data['component_type'],
                'hsn_code' => $data['hsn_code'],
                'tax_type' => 'SGST',
                'taxable_value' => $data['taxable_value'],
                'tax_rate' => $data['tax_rate'],
                'tax_amount' => $taxAmount,
                'item_name' => $data['item_name'],
                'item_type' => $data['item_type'],
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'description' => $data['description'] . ' (SGST)'
            ]);
        }
    }

    private function checkIfInterState($address)
    {
        if (!is_array($address)) {
            return false;
        }
        $businessState = 'Maharashtra';
        $shippingState = $address['state'] ??
            $address['administrative_area'] ??
            $address['province'] ?? null;
        if (!$shippingState || $shippingState === $businessState) {
            return false; 
        }

        return true; 
    }
}
