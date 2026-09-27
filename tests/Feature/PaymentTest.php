<?php

namespace Tests\Feature;

use App\Models\Payment;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use DatabaseMigrations;

    public function test_payment_can_be_created_successfully(): void
    {
        $response = $this->postJson('/api/payments', [
            'idempotency_key' => 'payment-test-001',
            'amount' => 100,
        ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'idempotency_key' => 'payment-test-001',
                'amount' => '100.00',
                'status' => 'completed',
            ]);

        $this->assertDatabaseHas('payments', [
            'idempotency_key' => 'payment-test-001',
            'amount' => 100,
            'status' => 'completed',
        ]);
    }

    public function test_duplicate_payment_with_same_key_returns_original_payment(): void
    {
        $firstResponse = $this->postJson('/api/payments', [
            'idempotency_key' => 'payment-test-002',
            'amount' => 100,
        ]);

        $firstResponse->assertStatus(201);

        $paymentId = $firstResponse->json('id');

        $secondResponse = $this->postJson('/api/payments', [
            'idempotency_key' => 'payment-test-002',
            'amount' => 100,
        ]);

        $secondResponse
            ->assertStatus(200)
            ->assertJson([
                'id' => $paymentId,
                'idempotency_key' => 'payment-test-002',
                'amount' => '100.00',
                'status' => 'completed',
            ]);

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_same_idempotency_key_with_different_amount_returns_conflict(): void
    {
        $this->postJson('/api/payments', [
            'idempotency_key' => 'payment-test-003',
            'amount' => 100,
        ])->assertStatus(201);

        $response = $this->postJson('/api/payments', [
            'idempotency_key' => 'payment-test-003',
            'amount' => 200,
        ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'message' => 'Idempotency key has already been used with a different amount.',
            ]);

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_payment_requires_valid_data(): void
    {
        $response = $this->postJson('/api/payments', [
            'idempotency_key' => '',
            'amount' => 0,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_duplicate_payment_requests_are_safe_under_concurrent_requests(): void
    {
        $scriptPath = base_path('payment_concurrency_test.php');

        $database = config('database.connections.mysql');

        $script = <<<'PHP'
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$idempotencyKey = $argv[1];
$amount = (float) $argv[2];

$controller = new \App\Http\Controllers\PaymentController();

$request = \Illuminate\Http\Request::create(
    '/api/payments',
    'POST',
    [
        'idempotency_key' => $idempotencyKey,
        'amount' => $amount,
    ]
);

$response = $controller->store($request);

echo $response->getStatusCode();
PHP;

        file_put_contents($scriptPath, $script);

        $processes = [];

        /*
         * Send 20 simultaneous requests using the exact same
         * idempotency key and amount.
         */
        for ($i = 0; $i < 20; $i++) {
            $process = new Process([
                PHP_BINARY,
                $scriptPath,
                'concurrent-payment-001',
                '250',
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

        $created = 0;
        $duplicates = 0;

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

            if ($output === '201') {
                $created++;
            } elseif ($output === '200') {
                $duplicates++;
            } else {
                dump([
                    'process' => $index + 1,
                    'unexpected_output' => $output,
                    'error' => $errorOutput,
                ]);
            }
        }

        /*
         * Exactly one request should create the payment.
         */
        $this->assertEquals(
            1,
            $created,
            'Exactly one concurrent request should create the payment.'
        );

        /*
         * All remaining requests should receive the existing
         * payment rather than creating another one.
         */
        $this->assertEquals(
            19,
            $duplicates,
            'The remaining concurrent requests should return the existing payment.'
        );

        /*
         * Most important assertion:
         *
         * Only one payment record must exist.
         */
        $this->assertDatabaseCount('payments', 1);

        $this->assertDatabaseHas('payments', [
            'idempotency_key' => 'concurrent-payment-001',
            'amount' => 250,
            'status' => 'completed',
        ]);

        if (file_exists($scriptPath)) {
            unlink($scriptPath);
        }
    }
}