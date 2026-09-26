<?php

namespace Tests\Feature;

use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_can_be_redeemed(): void
    {
        $coupon = Coupon::create([
            'code' => 'SAVE50',
            'max_uses' => 1,
        ]);

        $response = $this->postJson("/api/coupons/{$coupon->id}/redeem");

        $response->assertStatus(200);

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'uses' => 1,
        ]);

        $secondResponse = $this->postJson("/api/coupons/{$coupon->id}/redeem");

        $secondResponse->assertStatus(400);
    }
}