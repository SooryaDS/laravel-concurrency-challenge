<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'from_account_id' => 'required|integer|exists:accounts,id',
            'to_account_id' => 'required|integer|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $result = DB::transaction(function () use ($validated) {

            // Always lock accounts in ID order.
            // This also helps prevent deadlocks when transfers happen
            // in opposite directions at the same time.
            $accounts = Account::whereIn('id', [
                $validated['from_account_id'],
                $validated['to_account_id'],
            ])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $fromAccount = $accounts[$validated['from_account_id']];
            $toAccount = $accounts[$validated['to_account_id']];

            if ($fromAccount->balance < $validated['amount']) {
                return null;
            }

            $fromAccount->balance -= $validated['amount'];
            $toAccount->balance += $validated['amount'];

            $fromAccount->save();
            $toAccount->save();

            return [
                'from' => $fromAccount,
                'to' => $toAccount,
            ];
        });

        if (!$result) {
            return response()->json([
                'message' => 'Insufficient funds.',
            ], 422);
        }

        return response()->json([
            'message' => 'Transfer successful.',
            'from_account' => $result['from'],
            'to_account' => $result['to'],
        ], 200);
    }
}