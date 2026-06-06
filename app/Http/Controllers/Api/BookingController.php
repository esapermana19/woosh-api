<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function checkout(Request $request)
    {
        // 1. Validasi Input Request
        $request->validate([
            'schedule_id' => 'required|integer',
            'payment_method' => 'required|in:bank_transfer,ewallet,credit_card',
            'passengers' => 'required|array|min:1',
            'passengers.*.full_name' => 'required|string|max:100',
            'passengers.*.id_number' => 'required|string|max:30',
            'passengers.*.seat_id' => 'required|integer',
        ]);

        $user = $request->user(); // Ambil data user yang sedang login
        $schedule = Schedule::find($request->schedule_id);

        if (!$schedule) {
            return response()->json(['message' => 'Jadwal tidak ditemukan'], 404);
        }

        // Hitung total harga berdasarkan jumlah penumpang
        $totalAmount = $schedule->price * count($request->passengers);

        // 2. Gunakan DB Transaction untuk mencegah ketidaksinkronan data (Race Condition)
        DB::beginTransaction();

        try {
            // 3. Validasi Seat Locking (Cek apakah kursi sudah dipesan orang lain pada jadwal tersebut)
            $seatIds = collect($request->passengers)->pluck('seat_id')->toArray();

            $isSeatTaken = BookingPassenger::whereIn('seat_id', $seatIds)
                ->whereHas('booking', function ($query) use ($request) {
                    $query->where('schedule_id', $request->schedule_id)
                        ->whereIn('status', ['pending', 'success']); // Jika pending (sedang dibayar) atau success, tidak boleh diambil
                })->exists();

            if ($isSeatTaken) {
                DB::rollBack();
                return response()->json(['message' => 'Salah satu kursi yang Anda pilih sudah dipesan atau sedang dalam proses pembayaran.'], 422);
            }

            // 4. Buat data Booking Utama
            $bookingCode = 'WOOSH-' . strtoupper(Str::random(8));
            $booking = new Booking();
            $booking->user_id = $user->user_id; // Sesuaikan dengan PK tabel user Anda
            $booking->schedule_id = $schedule->schedule_id;
            $booking->booking_code = $bookingCode;
            $booking->status = 'pending';
            $booking->save();

            // 5. Masukkan Data Penumpang (Manifest)
            foreach ($request->passengers as $passengerData) {
                $passenger = new BookingPassenger();
                $passenger->booking_id = $booking->booking_id;
                $passenger->full_name = $passengerData['full_name'];
                $passenger->id_number = $passengerData['id_number'];
                $passenger->seat_id = $passengerData['seat_id'];
                $passenger->save();
            }

            // 6. Buat Rekaman Log Pembayaran Awal
            $payment = new Payment();
            $payment->booking_id = $booking->booking_id;
            $payment->payment_method = $request->payment_method;
            $payment->amount = $totalAmount;
            $payment->payment_status = 'pending';
            $payment->save();

            DB::commit();

            // Skenario Integrasi Nyata: Di sini Anda harusnya memanggil API Midtrans/Xendit untuk mendapatkan Snap Token atau VA Number.
            // Untuk tahap ini, kita buat respons sukses lokal terlebih dahulu.

            return response()->json([
                'message' => 'Booking berhasil dibuat. Silakan lakukan pembayaran.',
                'booking_code' => $bookingCode,
                'total_bayar' => $totalAmount,
                'metode' => $request->payment_method,
                'batas_waktu' => now()->addMinutes(15)->toDateTimeString() // Batas bayar 15 menit
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan sistem saat memproses booking.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Endpoint ini akan dipanggil oleh Payment Gateway (Simulasi) setelah proses pembayaran selesai
    public function paymentCallback(Request $request)
    {
        // Validasi input kiriman dari Payment Gateway (Simulasi)
        $request->validate([
            'booking_code' => 'required|string',
            'status' => 'required|in:success,failed'
        ]);

        $booking = Booking::where('booking_code', $request->booking_code)->first();

        if (!$booking) {
            return response()->json(['message' => 'Data booking tidak ditemukan'], 404);
        }

        // Jika status booking sudah sukses/bukan pending lagi, abaikan
        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Transaksi sudah diproses sebelumnya']);
        }

        DB::beginTransaction();
        try {
            if ($request->status === 'success') {
                // Update status booking dan payment menjadi sukses
                $booking->status = 'success';
                $booking->save();

                Payment::where('booking_id', $booking->booking_id)
                    ->update(['payment_status' => 'success', 'payment_date' => now()]);

                // OTOMATIS GENERATE TIKET & QR CODE
                $ticket = new Ticket();
                $ticket->booking_id = $booking->booking_id;
                // Generate QR string acak terenkripsi yang nanti dibaca oleh scan gate stasiun
                $ticket->qr_code = 'BOARDING-' . Crypt::encryptString($booking->booking_code . '|' . now()->timestamp);
                $ticket->save();

                DB::commit();
                return response()->json(['message' => 'Pembayaran berhasil diverifikasi, tiket elektronik diterbitkan.']);
            } else {
                // Jika pembayaran gagal atau kedaluwarsa
                $booking->status = 'failed';
                $booking->save();

                Payment::where('booking_id', $booking->booking_id)
                    ->update(['payment_status' => 'failed']);

                DB::commit();
                return response()->json(['message' => 'Pembayaran gagal diverifikasi.']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memperbarui status', 'error' => $e->getMessage()], 500);
        }
    }
}
