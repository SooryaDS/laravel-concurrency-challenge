<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function show(Ticket $ticket)
    {
        return response()->json($ticket);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'version' => ['required', 'integer', 'min:1'],
        ]);

        $updated = Ticket::where('id', $ticket->id)
            ->where('version', $validated['version'])
            ->update([
                'title' => $validated['title'],
                'status' => $validated['status'],
                'version' => $validated['version'] + 1,
            ]);

        if ($updated === 0) {
            return response()->json([
                'message' => 'The ticket has been modified by another request.',
            ], 409);
        }

        return response()->json(
            Ticket::findOrFail($ticket->id)
        );
    }
}