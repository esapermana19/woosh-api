<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// ROUTE PUBLIK (Tanpa Login / Tanpa Token)
// =========================================================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/schedules/search', [ScheduleController::class, 'search']);
Route::get('/seats', [ScheduleController::class, 'getSeats']);

// Mengirim request checkout awal dari Android Studio
Route::post('/payment/checkout', [PaymentController::class, 'createTransaction']);

// WEBHOOK CALLBACK MIDTRANS (Wajib Publik karena diakses langsung oleh server Midtrans)
Route::post('/payment/callback', [PaymentController::class, 'notificationHandler']);


// =========================================================================
// ROUTE PROTECTED (Harus Login & Membawa Bearer Token)
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // Fitur booking stage 3 nanti akan ditaruh di sini
    Route::post('/checkout', [BookingController::class, 'checkout']);
    // Jalur untuk Android mengambil detail info tiket berdasarkan Order ID
    Route::get('/payment/ticket/{order_id}', [PaymentController::class, 'getTicketDetails']);
});
