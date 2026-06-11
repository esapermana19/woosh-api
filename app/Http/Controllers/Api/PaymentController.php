<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

// Pastikan namespace Model di bawah ini sesuai dengan nama file & folder Model di Laravel Anda
use App\Models\Payment;
use App\Models\Ticket;

class PaymentController extends Controller
{
    /**
     * 1. MEMBUAT TRANSAKSI DAN MENYIMPAN DATA AWAL (PENDING)
     */
    public function createTransaction(Request $request)
    {
        // Validasi request dari Android Studio
        // booking_id bisa integer, string, atau null (jika belum ada booking sebelumnya)
        $validated = $request->validate([
            'booking_id'       => 'nullable',
            'total_price'      => 'required|numeric|min:1',
            'payment_method'   => 'required|in:bank_transfer,ewallet,credit_card',
        ]);

        // Validasi Midtrans Server Key
        $serverKey = env('MIDTRANS_SERVER_KEY');
        if (!$serverKey) {
            Log::error('MIDTRANS_SERVER_KEY tidak dikonfigurasi di file .env');
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi server pembayaran tidak valid'
            ], 500);
        }

        // Konfigurasi internal Midtrans SDK
        Config::$serverKey = $serverKey;
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = env('MIDTRANS_IS_SANITIZED', true);
        Config::$is3ds = env('MIDTRANS_IS_3DS', true);

        // Membuat ID Order Unik otomatis
        $orderId = 'WOOSH-' . time();
        $grossAmount = (int) $validated['total_price'];
        $bookingId = $validated['booking_id'] ?? null;  // Handle missing key
        $paymentMethod = $validated['payment_method'];

        // Process booking_id: konversi dari string ke integer jika numeric
        $finalBookingId = null;
        if ($bookingId) {
            // Jika booking_id adalah string numeric, konversi ke integer
            if (is_numeric($bookingId)) {
                $finalBookingId = (int) $bookingId;

                // Validasi apakah booking_id benar-benar ada di database
                // (Foreign key constraint membutuhkan ini)
                $bookingExists = DB::table('bookings')->where('booking_id', $finalBookingId)->exists();
                if (!$bookingExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Booking ID tidak ditemukan di sistem'
                    ], 422);
                }
            } else {
                // booking_id bukan numeric (misalnya: "WOOSH-20260611...")
                // Abaikan dan set null (akan diisi setelah booking dibuat)
                $finalBookingId = null;
            }
        }

        // Gunakan DB Transaction agar jika salah satu insert gagal, database dibersihkan kembali
        DB::beginTransaction();

        try {
            // ===============================================================
            // LOGIKA MENYIMPAN DATA AWAL KE DATABASE DENGAN STATUS PENDING
            // ===============================================================

            // Simpan ke table payments
            $payment = Payment::create([
                'booking_id'      => $finalBookingId,  // Bisa null jika booking belum dibuat
                'payment_method'  => $paymentMethod,
                'payment_date'    => now(),
                'amount'          => $grossAmount,
                'payment_status'  => 'pending', // Status awal wajib pending
            ]);

            // Siapkan payload data yang akan dikirimkan ke Midtrans Snap
            // Gunakan payment_id sebagai unique identifier untuk tracking
            $midtransOrderId = 'WOOSH-' . $payment->payment_id;
            $transaction_details = [
                'order_id'     => $midtransOrderId,
                'gross_amount' => $grossAmount,
            ];

            $payload = [
                'transaction_details' => $transaction_details,
            ];

            // Kirim data ke Midtrans untuk mendapatkan URL kasir pembayaran
            $snapResponse = Snap::createTransaction($payload);

            // Jika semua proses database dan Midtrans sukses, kunci/commit transaksinya
            DB::commit();

            // Kembalikan URL pembayaran ke aplikasi Android
            return response()->json([
                'success'      => true,
                'redirect_url' => $snapResponse->redirect_url,
                'token'        => $snapResponse->token,
                'order_id'     => $midtransOrderId
            ]);
        } catch (\Throwable $e) {
            // Batalkan semua perubahan database jika terjadi error di tengah jalan
            DB::rollBack();

            Log::error('Payment creation failed', [
                'booking_id' => $bookingId,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat sesi pembayaran server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 2. WEBHOOK / NOTIFICATION HANDLER (OTOMATISASI STATUS SUCCESS/FAILED)
     */
    public function notificationHandler(Request $request)
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);

        try {
            // Membaca data notifikasi instan secara aman dari server Midtrans
            $notif = new Notification();

            $transactionStatus = $notif->transaction_status;
            $type = $notif->payment_type;
            $orderId = $notif->order_id;
            $fraudStatus = $notif->fraud_status;

            Log::info("Midtrans Webhook Received. Order ID: $orderId, Status: $transactionStatus");

            // Extract payment_id dari order_id format "WOOSH-{paymentId}"
            $paymentId = str_replace('WOOSH-', '', $orderId);

            // Cari data transaksi payment di database berdasarkan payment_id
            $payment = Payment::find($paymentId);

            if (!$payment) {
                Log::warning("Payment not found for order_id: $orderId");
                return response()->json([
                    'success' => false,
                    'message' => 'Data transaksi tidak ditemukan di database'
                ], 404);
            }

            // Jalankan pencocokan status dari Midtrans untuk diubah di database lokal
            if ($transactionStatus == 'capture') {
                if ($type == 'credit_card') {
                    if ($fraudStatus == 'challenge') {
                        // Pembayaran perlu ditinjau manual
                        $payment->update(['payment_status' => 'pending']);
                    } else {
                        // Sukses lewat kartu kredit
                        $payment->update(['payment_status' => 'success']);
                    }
                }
            } elseif ($transactionStatus == 'settlement') {
                // PEMBAYARAN SUKSES BERHASIL (Gopay, QRIS, Transfer Bank, dll)
                $payment->update(['payment_status' => 'success']);
            } elseif ($transactionStatus == 'pending') {
                // User belum bayar di kasir / token masih menunggu transferan
                $payment->update(['payment_status' => 'pending']);
            } elseif ($transactionStatus == 'deny' || $transactionStatus == 'expire' || $transactionStatus == 'cancel') {
                // TRANSAKSI GAGAL / EXPIRED / DIBATALKAN
                $payment->update(['payment_status' => 'failed']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Status database berhasil diperbarui otomatis'
            ]);
        } catch (\Throwable $e) {
            Log::error('Webhook Handler Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error memproses webhook: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 3. MENGAMBIL DATA TIKET UNTUK DITAMPILKAN DI ANDROID
     */
    public function getTicketDetails($order_id)
    {
        try {
            // Cari data payment beserta tiket-tiketnya (Gunakan relasi jika ada, atau query manual)
            $payment = Payment::where('order_id', $order_id)->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tiket tidak ditemukan'
                ], 404);
            }

            // Mengambil daftar kursi/tiket yang terikat dengan order_id ini
            $tickets = Ticket::where('order_id', $order_id)->get();

            // Kumpulkan nomor kursi menjadi string dipisah koma (misal: "1A, 1B")
            $seatNumbers = $tickets->pluck('seat_number')->implode(', ');

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id'     => $payment->order_id,
                    'status'       => $payment->status,
                    'total_amount' => $payment->total_amount,
                    'seat_number'  => $seatNumbers,
                    // Anda juga bisa menambahkan detail stasiun atau jadwal di sini jika tabelnya berelasi
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
