<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use DatabaseMigrations;

    public function test_product_can_be_reserved_successfully(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'stock' => 10,
        ]);

        $response = $this->postJson(
            "/api/products/{$product->id}/reserve",
            [
                'quantity' => 3,
            ]
        );

        $response
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Product reserved successfully.',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 7,
        ]);
    }

    public function test_product_reservation_fails_when_stock_is_insufficient(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'stock' => 2,
        ]);

        $response = $this->postJson(
            "/api/products/{$product->id}/reserve",
            [
                'quantity' => 5,
            ]
        );

        $response
            ->assertStatus(409)
            ->assertJson([
                'message' => 'Insufficient stock.',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 2,
        ]);
    }

    public function test_product_reservation_requires_valid_quantity(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'stock' => 10,
        ]);

        $response = $this->postJson(
            "/api/products/{$product->id}/reserve",
            [
                'quantity' => 0,
            ]
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);
    }

    public function test_product_cannot_be_oversold_under_concurrent_requests(): void
    {
        /*
         * The challenge specifically requires stock = 1.
         *
         * Multiple customers will simultaneously attempt
         * to reserve the final item.
         */
        $product = Product::create([
            'name' => 'Final Stock Product',
            'stock' => 1,
        ]);

        $scriptPath = base_path('product_concurrency_test.php');

        $database = config('database.connections.mysql');

        $script = <<<'PHP'
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$productId = (int) $argv[1];

$product = \App\Models\Product::findOrFail($productId);

$controller = new \App\Http\Controllers\ProductController();

$request = \Illuminate\Http\Request::create(
    "/api/products/{$productId}/reserve",
    'POST',
    [
        'quantity' => 1,
    ]
);

$response = $controller->reserve($request, $product);

echo $response->getStatusCode();
PHP;

        file_put_contents($scriptPath, $script);

        $processes = [];

        /*
         * 20 customers simultaneously attempt to reserve
         * the final unit.
         *
         * Exactly ONE should receive 200.
         * The other 19 should receive 409.
         */
        for ($i = 0; $i < 20; $i++) {
            $process = new Process([
                PHP_BINARY,
                $scriptPath,
                (string) $product->id,
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
            } elseif ($output === '409') {
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
         * Exactly ONE customer must successfully reserve
         * the final item.
         */
        $this->assertEquals(
            1,
            $successful,
            'Exactly one concurrent reservation should succeed.'
        );

        /*
         * The other 19 customers must be rejected because
         * there is no stock remaining.
         */
        $this->assertEquals(
            19,
            $failed,
            'The remaining reservations should fail with insufficient stock.'
        );

        $product->refresh();

        /*
         * The final stock must be exactly zero.
         *
         * It must never become negative.
         */
        $this->assertEquals(0, $product->stock);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 0,
        ]);

        /*
         * Make sure no unexpected stock value was created.
         */
        $this->assertGreaterThanOrEqual(0, $product->stock);

        if (file_exists($scriptPath)) {
            unlink($scriptPath);
        }
    }
}