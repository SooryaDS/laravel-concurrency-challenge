<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained('rooms')
                ->cascadeOnDelete();

            $table->dateTime('start_time');
            $table->dateTime('end_time');

            $table->timestamps();

            $table->index([
                'room_id',
                'start_time',
                'end_time'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};