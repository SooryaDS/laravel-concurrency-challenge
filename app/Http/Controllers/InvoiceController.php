<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateInvoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
        ]);

        GenerateInvoice::dispatch(
            $validated['order_id'],
            $validated['amount']
        );

        return response()->json([
            'message' => 'Invoice generation job dispatched.',
        ], 202);
    }
}