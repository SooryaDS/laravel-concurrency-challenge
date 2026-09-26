<?php

namespace Tests\Feature;

use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_cannot_be_redeemed_more_than_100_times(): void
    {
        $coupon = Coupon::create([
            'code' => 'CONCURRENT',
            'max_uses' => 100,
            'uses' => 0,
        ]);

        $successful = 0;
        $failed = 0;

        for ($i = 0; $i < 101; $i++) {
            $response = $this->postJson(
                "/api/coupons/{$coupon->id}/redeem"
            );

            if ($response->status() === 200) {
                $successful++;
            } else {
                $failed++;
            }
        }

        $this->assertEquals(100, $successful);
        $this->assertEquals(1, $failed);

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'uses' => 100,
        ]);
    }
}