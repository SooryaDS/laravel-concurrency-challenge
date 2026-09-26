<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LoginCodeController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\AuthController;


Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
Route::put('/tickets/{ticket}', [TicketController::class, 'update']);
Route::post('/payments', [PaymentController::class, 'store']);
Route::post('/products/{product}/reserve', [ProductController::class, 'reserve']);
Route::post('/rooms/{room}/book', [BookingController::class, 'store']);
Route::post('/transfer', [TransferController::class, 'transfer']);
Route::post('/invoice', [InvoiceController::class, 'generate']);
Route::post('/login-code', [LoginCodeController::class, 'sendCode'])
    ->middleware('throttle:login-code');
Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
    ->middleware('auth:sanctum');
Route::post('/coupons/{coupon}/redeem', [CouponController::class, 'redeem']);
Route::post('/login', [AuthController::class, 'login']);