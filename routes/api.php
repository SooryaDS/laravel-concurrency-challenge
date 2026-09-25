<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
Route::put('/tickets/{ticket}', [TicketController::class, 'update']);