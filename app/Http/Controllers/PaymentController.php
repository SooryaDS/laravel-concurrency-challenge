<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'idempotency_key' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
        ]);

        // Check if this idempotency key has already been used
        $existingPayment = Payment::where(
            'idempotency_key',
            $validated['idempotency_key']
        )->first();

        if ($existingPayment) {

            // Same key, different amount = conflict
            if ((float) $existingPayment->amount !== (float) $validated['amount']) {
                return response()->json([
                    'message' => 'Idempotency key has already been used with a different amount.'
                ], 409);
            }

            // Same request = return the original payment
            return response()->json($existingPayment, 200);
        }

        try {
            $payment = Payment::create([
                'idempotency_key' => $validated['idempotency_key'],
                'amount' => $validated['amount'],
                'status' => 'completed',
            ]);

            return response()->json($payment, 201);

        } catch (QueryException $e) {

            // Handles a race condition where another request
            // created the same idempotency key at the same time.
            if ($e->getCode() === '23000') {

                $existingPayment = Payment::where(
                    'idempotency_key',
                    $validated['idempotency_key']
                )->first();

                if ($existingPayment) {
                    return response()->json($existingPayment, 200);
                }
            }

            throw $e;
        }
    }
}