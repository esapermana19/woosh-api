<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Route Publik (Tanpa Login)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/schedules/search', [ScheduleController::class, 'search']);
Route::get('/seats', [ScheduleController::class, 'getSeats']);
Route::post('/payment/callback', [BookingController::class, 'paymentCallback']);
Route::post('/payment/checkout', [PaymentController::class, 'createTransaction']);

// Contoh Route Protected (Harus Login bawa token)
Route::middleware('auth:sanctum')->group(function () {
    // Fitur booking stage 3 nanti akan ditaruh di sini
    Route::post('/checkout', [BookingController::class, 'checkout']);
    Route::post('/payment/checkout', [PaymentController::class, 'createTransaction']);
});
