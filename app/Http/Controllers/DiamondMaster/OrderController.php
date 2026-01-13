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
                $query->where('products_name', 'like', "%{$search}%")
                    ->orWhere('products_sku', 'like', "%{$search}%")
                    ->orWhere('master_sku', 'like', "%{$search}%");
            })
            ->where('products_status', 1)
            ->limit(20)
            ->get();

        $results = [];
        foreach ($products as $product) {
            foreach ($product->variations as $variation) {
                $results[] = [
                    'id' => $variation->id,
                    'product_id' => $product->products_id,
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

            if ($product->variations->isEmpty()) {
                $results[] = [
                    'id' => $product->products_id,
                    'product_id' => $product->products_id,
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
            $processedItems = $this->processItemsForOrder($order);

            return view('admin.DiamondMaster.Orders.invoice', compact('order', 'processedItems'));
        } catch (\Exception $e) {
            Log::error('Error in OrderController@show: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());

            return response()->view('admin.DiamondMaster.Orders.invoice_error', [
                'message' => 'Unable to load order details: ' . $e->getMessage()
            ], 500);
        }
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

            // Process items for PDF
            $processedItems = $this->processItemsForOrder($order);

            // Generate PDF with processedItems
            $pdf = Pdf::loadView('admin.DiamondMaster.Orders.invoice', compact('order', 'processedItems'));

            // // Store PDF on S3
            // $pdfPath = 'invoices/' . $order->order_id . '.pdf';
            // Storage::disk('s3')->put($pdfPath, $pdf->output());
            // $pdfUrl = Storage::disk('s3')->url($pdfPath);
            // Store PDF locally
            $pdfPath = 'invoices/' . $order->order_id . '.pdf';
            Storage::disk('local')->put($pdfPath, $pdf->output());

            // Local download URL
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

            // Helper function to extract name from array or object
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

            /* -------------------------
         CASE 1: Indexed Array Items
        ----------------------------*/
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

            /* -------------------------------------
         CASE 2: Old Style Format (diamond/jewelry/gift/combo)
        ----------------------------------------*/
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

                // ... rest of the code for jewelry, gift, combo ...
            }

            /* -------------------------
         CASE 3: Default
        ----------------------------*/
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
        $validator = Validator::make($request->all(), [
            'user_id'        => 'required|exists:users,id',
            'user_name'      => 'required|string',
            'contact_number' => 'required|string',
            'item_details'   => 'required|json',
            'total_price'    => 'required|numeric',
            'address'        => 'required|json',
            'billing_address' => 'nullable|json',
            'order_status'   => 'required|string',
            'payment_mode'   => 'required|string',
            'payment_status' => 'required|string',
            'transaction_id' => 'nullable|string',
            'is_gift'        => 'nullable|boolean',
            'notes'          => 'nullable|string',
            'coupon_discount' => 'nullable|numeric',
            'coupon_code'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $validated['order_id'] = 'ORD-' . Str::uuid();

        // ✅ COUPON VALIDATION (Admin side)
        if (!empty($validated['coupon_code'])) {
            $itemDetails = json_decode($validated['item_details'], true);
            $items = $itemDetails['items'] ?? [];
            $cartTotalWithoutDiscount = 0;

            foreach ($items as $item) {
                $quantity = $item['quantity'] ?? $item['itemQuantity'] ?? 1;
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

            if (abs($calculatedDiscount - $validated['coupon_discount']) > 0.01) {
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

        // Process each item in the payload
        foreach ($payload as $item) {
            $quantity = $item['quantity'] ?? $item['itemQuantity'] ?? 1;

            switch ($item['productType'] ?? null) {
                case 'diamond':
                    if (!empty($item['diamondid'])) {
                        $itemsId['diamond'][] = [
                            'id' => $item['diamondid'],
                            'quantity' => $quantity,
                            'price' => $item['price'] ?? 0,
                            'carat' => $item['carat'] ?? null,
                            'shape' => $item['shape'] ?? null
                        ];
                        $quantities['diamond'] += $quantity;
                    }
                    break;

                case 'jewelry':
                    if (!empty($item['id'])) {
                        $itemsId['jewelry'][] = [
                            'id' => $item['id'],
                            'quantity' => $quantity,
                            'price' => $item['price'] ?? 0,
                            'title' => $item['title'] ?? '',
                            'type' => $item['type'] ?? ''
                        ];
                        $quantities['jewelry'] += $quantity;
                    }
                    break;

                case 'gift':
                    if (!empty($item['id'])) {
                        $itemsId['gift'][] = [
                            'id' => $item['id'],
                            'quantity' => $quantity,
                            'price' => $item['price'] ?? 0,
                            'title' => $item['name'] ?? '',
                            'type' => $item['productType'] ?? ''
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
                        'diamond_id' => $item['diamond']['diamondid'] ?? null,
                        'product_id' => $item['ring']['id'] ?? null,
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
            // ✅ DB TRANSACTION USE करें
            DB::beginTransaction();

            $order = Order::create($validated);

            // ✅ NEW: Send confirmation emails
            $this->sendOrderConfirmationEmail($order);

            DB::commit();

            return redirect()->route('admin.orders.index')
                ->with('success', 'Order created successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Admin Order creation failed: " . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to save order: ' . $e->getMessage())
                ->withInput();
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
}
