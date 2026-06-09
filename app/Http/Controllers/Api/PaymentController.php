<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;

class PaymentController extends Controller
{
    public function createTransaction(Request $request)
    {
        // Konfigurasi Midtrans
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION');
        Config::$isSanitized = env('MIDTRANS_IS_SANITIZED');
        Config::$is3ds = env('MIDTRANS_IS_3DS');

        // Terima data dari Android
        $orderId = 'WOOSH-' . time(); // Membuat ID unik otomatis
        $grossAmount = $request->input('total_price'); // Ambil total harga dari Android

        $transaction_details = [
            'order_id' => $orderId,
            'gross_amount' => $grossAmount,
        ];

        // Anda juga bisa menambahkan item_details atau customer_details jika diperlukan

        $payload = [
            'transaction_details' => $transaction_details,
        ];

        try {
            // Ambil redirect_url dari Midtrans Snap
            $snapResponse = Snap::createTransaction($payload);

            // Kembalikan URL pembayaran ke Android
            return response()->json([
                'success' => true,
                'redirect_url' => $snapResponse->redirect_url,
                'token' => $snapResponse->token
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
