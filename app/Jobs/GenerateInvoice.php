<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateInvoice implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public float $amount
    ) {
    }

    public function handle(): void
    {
        Invoice::firstOrCreate(
            ['order_id' => $this->orderId],
            ['amount' => $this->amount]
        );
    }
}