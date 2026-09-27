<?php

namespace Tests\Feature;

use App\Models\Account;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use DatabaseMigrations;

    public function test_money_can_be_transferred_successfully(): void
    {
        $fromAccount = Account::create([
            'name' => 'Account A',
            'balance' => 1000,
        ]);

        $toAccount = Account::create([
            'name' => 'Account B',
            'balance' => 500,
        ]);

        $response = $this->postJson('/api/transfer', [
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'amount' => 100,
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Transfer successful.',
            ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $fromAccount->id,
            'balance' => 900,
        ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $toAccount->id,
            'balance' => 600,
        ]);
    }

    public function test_transfer_fails_when_there_are_insufficient_funds(): void
    {
        $fromAccount = Account::create([
            'name' => 'Account A',
            'balance' => 50,
        ]);

        $toAccount = Account::create([
            'name' => 'Account B',
            'balance' => 500,
        ]);

        $response = $this->postJson('/api/transfer', [
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'amount' => 100,
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Insufficient funds.',
            ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $fromAccount->id,
            'balance' => 50,
        ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $toAccount->id,
            'balance' => 500,
        ]);
    }

    public function test_transfer_requires_valid_data(): void
    {
        $fromAccount = Account::create([
            'name' => 'Account A',
            'balance' => 1000,
        ]);

        $toAccount = Account::create([
            'name' => 'Account B',
            'balance' => 500,
        ]);

        $response = $this->postJson('/api/transfer', [
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'amount' => 0,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('accounts', [
            'id' => $fromAccount->id,
            'balance' => 1000,
        ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $toAccount->id,
            'balance' => 500,
        ]);
    }

    public function test_transfer_rolls_back_if_an_exception_occurs_after_deduction(): void
    {
        $fromAccount = Account::create([
            'name' => 'Account A',
            'balance' => 1000,
        ]);

        $toAccount = Account::create([
            'name' => 'Account B',
            'balance' => 500,
        ]);

        Account::saving(function (Account $account) use ($toAccount) {
            if ($account->id === $toAccount->id) {
                throw new RuntimeException('Simulated failure during transfer.');
            }
        });

        try {
            $this->withoutExceptionHandling();

            $this->postJson('/api/transfer', [
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'amount' => 100,
            ]);
        } catch (RuntimeException $e) {
            $this->assertSame(
                'Simulated failure during transfer.',
                $e->getMessage()
            );
        }

        $this->assertDatabaseHas('accounts', [
            'id' => $fromAccount->id,
            'balance' => 1000,
        ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $toAccount->id,
            'balance' => 500,
        ]);
    }

    public function test_opposite_direction_transfers_are_handled_safely(): void
    {
        $accountA = Account::create([
            'name' => 'Account A',
            'balance' => 1000,
        ]);

        $accountB = Account::create([
            'name' => 'Account B',
            'balance' => 500,
        ]);

        // Transfer A -> B
        $firstResponse = $this->postJson('/api/transfer', [
            'from_account_id' => $accountA->id,
            'to_account_id' => $accountB->id,
            'amount' => 100,
        ]);

        $firstResponse->assertStatus(200);

        // Transfer B -> A
        $secondResponse = $this->postJson('/api/transfer', [
            'from_account_id' => $accountB->id,
            'to_account_id' => $accountA->id,
            'amount' => 100,
        ]);

        $secondResponse->assertStatus(200);

        // Balances should return to their original values.
        $this->assertDatabaseHas('accounts', [
            'id' => $accountA->id,
            'balance' => 1000,
        ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $accountB->id,
            'balance' => 500,
        ]);
    }

    public function test_concurrent_opposite_direction_transfers_are_handled_safely(): void
    {
        $accountA = Account::create([
            'name' => 'Account A',
            'balance' => 1000,
        ]);

        $accountB = Account::create([
            'name' => 'Account B',
            'balance' => 1000,
        ]);

        /*
         * Child processes will call the real TransferController.
         *
         * Half of the requests transfer A -> B.
         * The other half transfer B -> A.
         *
         * These requests run concurrently and therefore compete
         * for the same two database rows.
         */
        $scriptPath = base_path('transfer_concurrency_test.php');

        $database = config('database.connections.mysql');

        $script = <<<'PHP'
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$fromAccountId = (int) $argv[1];
$toAccountId = (int) $argv[2];
$amount = (float) $argv[3];

$controller = new \App\Http\Controllers\TransferController();

$request = \Illuminate\Http\Request::create(
    '/api/transfer',
    'POST',
    [
        'from_account_id' => $fromAccountId,
        'to_account_id' => $toAccountId,
        'amount' => $amount,
    ]
);

$response = $controller->transfer($request);

echo $response->getStatusCode();
PHP;

        file_put_contents($scriptPath, $script);

        $processes = [];

        /*
         * Create 20 concurrent transfers:
         *
         * 10 × A -> B
         * 10 × B -> A
         *
         * Each transfer is 10.
         */
        for ($i = 0; $i < 10; $i++) {
            $process = new Process([
                PHP_BINARY,
                $scriptPath,
                (string) $accountA->id,
                (string) $accountB->id,
                '10',
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

        for ($i = 0; $i < 10; $i++) {
            $process = new Process([
                PHP_BINARY,
                $scriptPath,
                (string) $accountB->id,
                (string) $accountA->id,
                '10',
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
            } else {
                dump([
                    'process' => $index + 1,
                    'unexpected_output' => $output,
                    'error' => $errorOutput,
                ]);
            }
        }

        /*
         * All 20 transfers should succeed.
         */
        $this->assertEquals(
            20,
            $successful,
            'All concurrent transfers should complete successfully.'
        );

        /*
         * Because there are equal numbers of A -> B and B -> A
         * transfers, the final balances should be exactly the
         * same as they were at the beginning.
         */
        $this->assertDatabaseHas('accounts', [
            'id' => $accountA->id,
            'balance' => 1000,
        ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $accountB->id,
            'balance' => 1000,
        ]);

        /*
         * Clean up the temporary child-process script.
         */
        if (file_exists($scriptPath)) {
            unlink($scriptPath);
        }
    }
}