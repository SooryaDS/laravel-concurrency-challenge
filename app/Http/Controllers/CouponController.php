<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    public function redeem(Coupon $coupon)
    {
        return DB::transaction(function () use ($coupon) {

            $coupon = Coupon::where('id', $coupon->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($coupon->uses >= $coupon->max_uses) {
                return response()->json([
                    'message' => 'Coupon has reached its usage limit.'
                ], 400);
            }

            $coupon->uses++;
            $coupon->save();

            return response()->json([
                'message' => 'Coupon redeemed successfully.',
                'uses' => $coupon->uses,
            ]);
        });
    }
}