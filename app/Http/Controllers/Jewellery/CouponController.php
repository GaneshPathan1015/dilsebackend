<?php

// namespace App\Http\Controllers\Jewellery;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use App\Models\Coupon;

// class CouponController extends Controller
// {
//     public function index()
//     {
//         $coupons = Coupon::orderBy('created_at', 'desc')->get();
//         return view('admin.Jewellery.coupons.index', compact('coupons'));
//     }

//     public function store(Request $request) 
//     {
//         $request->validate([
//             'code' => 'required|unique:coupons,code',
//             'type' => 'required|in:fixed,percent',
//             'value' => 'required|numeric|min:0',
//             'min_cart_value' => 'nullable|numeric|min:0',
//             'max_discount' => 'nullable|numeric|min:0',
//             'valid_from' => 'required|date',
//             'valid_until' => 'required|date|after:valid_from',
//             'usage_limit' => 'required|integer|min:1',
//             'is_active' => 'boolean'
//         ]);

//         Coupon::create($request->all());

//         return response()->json(['success' => 'Coupon created successfully.']);
//     }

//     public function edit(Coupon $coupon)
//     {
//         return response()->json(['coupon' => $coupon]);
//     }

//     public function update(Request $request, Coupon $coupon)
//     {
//         $request->validate([
//             'code' => 'required|unique:coupons,code,' . $coupon->id,
//             'type' => 'required|in:fixed,percent',
//             'value' => 'required|numeric|min:0',
//             'min_cart_value' => 'nullable|numeric|min:0',
//             'max_discount' => 'nullable|numeric|min:0',
//             'valid_from' => 'required|date',
//             'valid_until' => 'required|date|after:valid_from',
//             'usage_limit' => 'required|integer|min:1',
//             'is_active' => 'boolean'
//         ]);

//         $coupon->update($request->all());

//         return response()->json(['success' => 'Coupon updated successfully.']);
//     }

//     public function destroy(Coupon $coupon)
//     {
//         $coupon->delete();
//         return response()->json(['success' => 'Coupon deleted successfully.']);
//     }

//     public function updateStatus(Request $request, Coupon $coupon)
//     {
//         $coupon->is_active = $request->status;
//         $coupon->save();

//         return response()->json(['message' => 'Coupon status updated successfully.']);
//     }
// }

namespace App\Http\Controllers\Jewellery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::orderBy('created_at', 'desc')->get();
        return view('admin.Jewellery.coupons.index', compact('coupons'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'min_cart_value' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'usage_limit' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $coupon = Coupon::create([
            'code' => $validated['code'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'min_cart_value' => $validated['min_cart_value'] ?? null,
            'max_discount' => $validated['max_discount'] ?? null,
            'valid_from' => $validated['valid_from'],
            'valid_until' => $validated['valid_until'],
            'usage_limit' => $validated['usage_limit'],
            'used_count' => 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => 'Coupon created successfully.',
            'coupon' => $coupon
        ]);
    }

    public function edit(Coupon $coupon)
    {
        return response()->json(['coupon' => $coupon]);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code,' . $coupon->id,
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'min_cart_value' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'usage_limit' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $coupon->update([
            'code' => $validated['code'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'min_cart_value' => $validated['min_cart_value'] ?? null,
            'max_discount' => $validated['max_discount'] ?? null,
            'valid_from' => $validated['valid_from'],
            'valid_until' => $validated['valid_until'],
            'usage_limit' => $validated['usage_limit'],
            'is_active' => $validated['is_active'] ?? $coupon->is_active,
        ]);

        return response()->json([
            'success' => 'Coupon updated successfully.',
            'coupon' => $coupon->fresh()
        ]);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return response()->json([
            'success' => 'Coupon deleted successfully.'
        ]);
    }

    public function updateStatus(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        $coupon->update([
            'is_active' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Coupon status updated successfully.',
            'is_active' => $coupon->is_active
        ]);
    }
}
