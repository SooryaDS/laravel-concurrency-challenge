<?php

namespace Tests\Feature;

use App\Models\Coupon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class CouponConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

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

    public function test_coupon_redemption_is_safe_under_concurrent_requests(): void
    {
        /*
         * The coupon is allowed to be redeemed exactly 100 times.
         *
         * We will send 120 simultaneous requests.
         *
         * Expected result:
         *
         * 100 requests -> 200 successful
         * 20 requests  -> 400 rejected
         * final uses   -> 100
         */
        $coupon = Coupon::create([
            'code' => 'CONCURRENT-RACE',
            'max_uses' => 100,
            'uses' => 0,
        ]);

        /*
         * Each child process boots Laravel independently and calls
         * CouponController::redeem() against the same MySQL test database.
         *
         * DatabaseMigrations is used instead of RefreshDatabase so
         * the child processes can see the coupon created by this test.
         */
        $scriptPath = base_path('coupon_concurrency_test.php');

        $database = config('database.connections.mysql');

        $script = <<<'PHP'
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$couponId = (int) $argv[1];

$coupon = \App\Models\Coupon::findOrFail($couponId);

$controller = new \App\Http\Controllers\CouponController();

$response = $controller->redeem($coupon);

echo $response->getStatusCode();
PHP;

        file_put_contents($scriptPath, $script);

        $processes = [];

        /*
         * Start 120 simultaneous redemption requests.
         *
         * Every request targets the same coupon.
         */
        for ($i = 0; $i < 120; $i++) {
            $process = new Process([
                PHP_BINARY,
                $scriptPath,
                (string) $coupon->id,
            ]);

            $process->setEnv([
                'APP_ENV' => 'testing',

                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $database['host'],
                'DB_PORT' => $database['port'],
                'DB_DATABASE' => $database['database'],
                'DB_USERNAME' => $database['username'],
                'DB_PASSWORD' => $database['password'] ?? '',

                'CACHE_STORE' => 'array',
                'QUEUE_CONNECTION' => 'sync',
            ]);

            $process->start();

            $processes[] = $process;
        }

        $successful = 0;
        $failed = 0;

        foreach ($processes as $index => $process) {
            $process->wait();

            $output = trim($process->getOutput());
            $errorOutput = trim($process->getErrorOutput());

            if (!$process->isSuccessful()) {
                dump([
                    'process' => $index + 1,
                    'exit_code' => $process->getExitCode(),
                    'output' => $output,
                    'error' => $errorOutput,
                ]);
            }

            if ($output === '200') {
                $successful++;
            } elseif ($output === '400') {
                $failed++;
            } else {
                dump([
                    'process' => $index + 1,
                    'unexpected_output' => $output,
                    'error' => $errorOutput,
                ]);
            }
        }

        /*
         * Exactly 100 requests should succeed because the coupon
         * has a maximum of 100 uses.
         */
        $this->assertEquals(
            100,
            $successful,
            'Exactly 100 concurrent requests should successfully redeem the coupon.'
        );

        /*
         * The remaining 20 requests must be rejected because
         * the coupon has reached its maximum usage limit.
         */
        $this->assertEquals(
            20,
            $failed,
            'The remaining 20 concurrent requests should be rejected.'
        );

        /*
         * Refresh the coupon from the database after all
         * concurrent processes have completed.
         */
        $coupon->refresh();

        /*
         * Most important assertion:
         *
         * The coupon must NEVER exceed its maximum usage.
         */
        $this->assertEquals(
            100,
            $coupon->uses
        );

        $this->assertLessThanOrEqual(
            $coupon->max_uses,
            $coupon->uses
        );

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'uses' => 100,
        ]);

        /*
         * Make sure exactly one coupon record exists.
         */
        $this->assertDatabaseCount('coupons', 1);

        /*
         * Remove the temporary child-process script.
         */
        if (file_exists($scriptPath)) {
            unlink($scriptPath);
        }
    }
}