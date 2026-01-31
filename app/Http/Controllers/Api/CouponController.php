<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\CouponUsage;

class CouponController extends Controller
{


public function applyCoupon(Request $request)
{
    $request->validate([
        'code' => 'required|string',
        'cart_total' => 'required|numeric|min:0'
    ]);

    $userId = auth()->id();

    if (!$userId) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please login first.'
        ], 401);
    }

    $coupon = Coupon::where('code', $request->code)->first();

    if (!$coupon) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid coupon code.'
        ], 400);
    }

    if (!$coupon->isValid()) {
        return response()->json([
            'success' => false,
            'message' => 'Coupon is either inactive, expired, or usage limit exceeded.'
        ], 400);
    }

    // ✅ user already used coupon?
    $alreadyUsed = CouponUsage::where('coupon_id', $coupon->id)
        ->where('user_id', $userId)
        ->exists();

    if ($alreadyUsed) {
        return response()->json([
            'success' => false,
            'message' => 'You have already used this coupon.'
        ], 400);
    }

    $discount = $coupon->calculateDiscount($request->cart_total);
    $finalAmount = max(0, $request->cart_total - $discount);

    CouponUsage::create([
        'coupon_id' => $coupon->id,
        'user_id' => $userId,
        'order_id' => null,
        'discount_amount' => $discount,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Coupon applied successfully.',
        'data' => [
            'coupon_code' => $coupon->code,
            'discount' => $discount,
            'final_amount' => $finalAmount
        ]
    ]);
}

}
