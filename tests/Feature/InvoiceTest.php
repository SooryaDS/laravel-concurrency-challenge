<?php

namespace Tests\Feature;

use App\Jobs\GenerateInvoice;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_invoice_generation_job_can_be_dispatched_successfully(): void
    {
        $response = $this->postJson('/api/invoice', [
            'order_id' => 1001,
            'amount' => 250.00,
        ]);

        $response
            ->assertStatus(202)
            ->assertJson([
                'message' => 'Invoice generation job dispatched.',
            ]);

        /*
         * The test environment uses the sync queue, so the job
         * executes immediately.
         */
        $this->assertDatabaseHas('invoices', [
            'order_id' => 1001,
            'amount' => 250.00,
        ]);
    }

    public function test_duplicate_invoice_generation_does_not_create_duplicate_invoice(): void
    {
        $firstResponse = $this->postJson('/api/invoice', [
            'order_id' => 1002,
            'amount' => 500.00,
        ]);

        $firstResponse->assertStatus(202);

        $secondResponse = $this->postJson('/api/invoice', [
            'order_id' => 1002,
            'amount' => 500.00,
        ]);

        $secondResponse->assertStatus(202);

        /*
         * Both jobs were dispatched, but only one invoice should
         * exist for the same order.
         */
        $this->assertDatabaseCount('invoices', 1);

        $this->assertDatabaseHas('invoices', [
            'order_id' => 1002,
            'amount' => 500.00,
        ]);
    }

    public function test_same_invoice_job_can_run_twice_without_creating_duplicate(): void
    {
        $job = new GenerateInvoice(
            orderId: 1003,
            amount: 750.00
        );

        /*
         * Simulate the same queue job being executed twice.
         */
        $job->handle();
        $job->handle();

        /*
         * firstOrCreate() must make the operation idempotent.
         */
        $this->assertDatabaseCount('invoices', 1);

        $this->assertDatabaseHas('invoices', [
            'order_id' => 1003,
            'amount' => 750.00,
        ]);
    }

    public function test_invoice_generation_requires_valid_data(): void
    {
        $response = $this->postJson('/api/invoice', [
            'order_id' => null,
            'amount' => 0,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('invoices', 0);
    }
}