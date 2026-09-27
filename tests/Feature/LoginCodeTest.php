<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginCodeTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Clear any rate-limit state left over from another test.
         */
        RateLimiter::clear('127.0.0.1');
    }

    public function test_login_code_request_succeeds_within_rate_limit(): void
    {
        $response = $this->postJson('/api/login-code');

        $response
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Login code sent successfully.',
            ]);
    }

    public function test_login_code_allows_three_requests_within_ten_minutes(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/login-code');

            $response
                ->assertStatus(200)
                ->assertJson([
                    'message' => 'Login code sent successfully.',
                ]);
        }
    }

    public function test_fourth_login_code_request_is_rate_limited(): void
    {
        /*
         * First three requests should succeed.
         */
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/login-code')
                ->assertStatus(200);
        }

        /*
         * Fourth request should be rejected.
         */
        $response = $this->postJson('/api/login-code');

        $response->assertStatus(429);
    }

    public function test_rate_limit_is_based_on_client_ip(): void
    {
        /*
         * Client 1 uses its three allowed requests.
         */
        for ($i = 0; $i < 3; $i++) {
            $this->withServerVariables([
                'REMOTE_ADDR' => '192.168.1.10',
            ])
                ->postJson('/api/login-code')
                ->assertStatus(200);
        }

        /*
         * Client 1 is now rate limited.
         */
        $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.1.10',
        ])
            ->postJson('/api/login-code')
            ->assertStatus(429);

        /*
         * Client 2 has a different IP, so it gets its own
         * rate-limit allowance.
         */
        $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.1.20',
        ])
            ->postJson('/api/login-code')
            ->assertStatus(200);
    }
}