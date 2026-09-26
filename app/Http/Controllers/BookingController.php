<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function store(Request $request, Room $room)
    {
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $booking = DB::transaction(function () use ($room, $validated) {

            // Lock the room so concurrent booking requests
            // cannot check availability at the same time.
            $room = Room::where('id', $room->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Check for an overlapping booking.
            $overlap = Booking::where('room_id', $room->id)
                ->where('start_time', '<', $validated['end_time'])
                ->where('end_time', '>', $validated['start_time'])
                ->exists();

            if ($overlap) {
                return null;
            }

            return Booking::create([
                'room_id' => $room->id,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
            ]);
        });

        if (!$booking) {
            return response()->json([
                'message' => 'Room is already booked for this time.',
            ], 409);
        }

        return response()->json([
            'message' => 'Room booked successfully.',
            'booking' => $booking,
        ], 201);
    }
}