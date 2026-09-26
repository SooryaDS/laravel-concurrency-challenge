<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'room_id',
        'start_time',
        'end_time',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}