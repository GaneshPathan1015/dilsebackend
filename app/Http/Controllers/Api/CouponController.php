<?php

// namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use App\Models\Coupon;

// class CouponController extends Controller
// {
    // public function applyCoupon(Request $request)
    // {
    //     $request->validate([
    //         'code' => 'required|string',
    //         'cart_total' => 'required|numeric|min:0'
    //     ]);

    //     $coupon = Coupon::where('code', $request->code)
    //         ->where('is_active', 1)
    //         ->whereDate('valid_from', '<=', now())
    //         ->whereDate('valid_until', '>=', now())
    //         ->first();

    //     if (!$coupon) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Invalid or expired coupon.'
    //         ], 400);
    //     }

    //     // Cart minimum check
    //     if ($coupon->min_cart_value && $request->cart_total < $coupon->min_cart_value) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Cart total must be at least ₹{$coupon->min_cart_value}."
    //         ], 400);
    //     }

    //     if ($coupon->usage_limit <= 0) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Coupon usage limit exceeded.'
    //         ], 400);
    //     }

    //     $discount = 0;
    //     if ($coupon->type === 'fixed') {
    //         $discount = $coupon->value;
    //     } elseif ($coupon->type === 'percent') {
    //         $discount = ($request->cart_total * $coupon->value) / 100;

    //         // Max discount check
    //         if ($coupon->max_discount && $discount > $coupon->max_discount) {
    //             $discount = $coupon->max_discount;
    //         }
    //     }

    //     $finalAmount = max(0, $request->cart_total - $discount);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Coupon applied successfully.',
    //         'data' => [
    //             'coupon_code' => $coupon->code,
    //             'discount' => $discount,
    //             'final_amount' => $finalAmount
    //         ]
    //     ]);
    // }
//     public function applyCoupon(Request $request)
//     {
//         $request->validate([
//             'code' => 'required|string',
//             'cart_total' => 'required|numeric|min:0'
//         ]);

//         $coupon = Coupon::where('code', $request->code)->first();

//         if (!$coupon) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Invalid coupon code.'
//             ], 400);
//         }

//         if (!$coupon->isValid()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Coupon is either inactive, expired, or usage limit exceeded.'
//             ], 400);
//         }

//         // ✅ Cart minimum check
//         if ($coupon->min_cart_value && $request->cart_total < $coupon->min_cart_value) {
//             return response()->json([
//                 'success' => false,
//                 'message' => "Cart total must be at least ₹{$coupon->min_cart_value}."
//             ], 400);
//         }

//         $discount = $coupon->calculateDiscount($request->cart_total);
//         $finalAmount = max(0, $request->cart_total - $discount);

//         return response()->json([
//             'success' => true,
//             'message' => 'Coupon applied successfully.',
//             'data' => [
//                 'coupon_code' => $coupon->code,
//                 'discount' => $discount,
//                 'final_amount' => $finalAmount
//             ]
//         ]);
//     }
// }


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

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code.'
            ], 400);
        }

        // ✅ Coupon active/date/usage_limit check
        if (!$coupon->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon is either inactive, expired, or usage limit exceeded.'
            ], 400);
        }

        // ✅ Check per-user usage **only if user is logged in**
        $userId = auth()->id();
        if ($userId) {
            $alreadyUsed = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $userId)
                ->exists();

            if ($alreadyUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already used this coupon.'
                ], 400);
            }
        }

        // ✅ Cart minimum check
        if ($coupon->min_cart_value && $request->cart_total < $coupon->min_cart_value) {
            return response()->json([
                'success' => false,
                'message' => "Cart total must be at least ₹{$coupon->min_cart_value}."
            ], 400);
        }

        // ✅ Calculate discount
        $discount = $coupon->calculateDiscount($request->cart_total);

        if ($discount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not applicable on this cart.'
            ], 400);
        }

        $finalAmount = max(0, $request->cart_total - $discount);

        return response()->json([
            'success' => true,
            'message' => 'Coupon applied successfully.',
            'data' => [
                'coupon_id' => $coupon->id,
                'coupon_code' => $coupon->code,
                'discount' => round($discount, 2),
                'final_amount' => round($finalAmount, 2),
                'valid_until' => $coupon->valid_until,
            ]
        ]);
    }
}


