<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

#[Signature('app:cancel-expired-bookings')]
#[Description('Command description')]
class CancelExpiredBookings extends Command
{
    // Nama perintah yang akan dijalankan di terminal
    protected $signature = 'bookings:cancel-expired';

    // Deskripsi perintah
    protected $description = 'Membatalkan booking yang tidak dibayar dalam 15 menit';

    public function handle()
    {
        // Cari booking dengan status pending yang dibuat lebih dari 15 menit yang lalu
        $expiredBookings = Booking::where('status', 'pending')
            ->where('created_at', '<', Carbon::now()->subMinutes(15))
            ->get();

        if ($expiredBookings->isEmpty()) {
            $this->info('Tidak ada booking yang kedaluwarsa.');
            return;
        }

        foreach ($expiredBookings as $booking) {
            DB::transaction(function () use ($booking) {
                // 1. Ubah status booking menjadi failed/cancelled
                $booking->status = 'failed';
                $booking->save();

                // 2. Ubah status payment terkait menjadi failed
                Payment::where('booking_id', $booking->booking_id)
                    ->update(['payment_status' => 'failed']);
            });

            $this->info("Booking {$booking->booking_code} telah dibatalkan otomatis.");
        }

        $this->info('Proses pembersihan selesai.');
    }
}
