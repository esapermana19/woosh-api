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

// SIMULASI PEMBAYARAN LOKAL (Hanya untuk testing localhost)
Route::get('/payment/test-success/{payment_id}', [PaymentController::class, 'simulateSuccess']);

// =========================================================================
// ROUTE PROTECTED (Harus Login & Membawa Bearer Token)
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // Fitur booking stage 3 nanti akan ditaruh di sini
    Route::post('/checkout', [BookingController::class, 'checkout']);
    // Jalur untuk Android mengambil detail info tiket berdasarkan Order ID
    Route::get('/payment/ticket/{order_id}', [PaymentController::class, 'getTicketDetails']);

    // ===== FITUR NAVIGASI MENU: RIWAYAT TIKET =====
    // GET /api/tickets/history - Dapatkan semua riwayat tiket
    Route::get('/tickets/history', [BookingController::class, 'getTicketHistory']);
    // GET /api/tickets/history?filter=pending|paid|failed|completed - Filter riwayat berdasarkan status
    Route::get('/tickets/history-filtered', [BookingController::class, 'getTicketHistoryFiltered']);

    // ===== FITUR PROFIL =====
    Route::get('/profile', [AuthController::class, 'getProfile']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);
});
