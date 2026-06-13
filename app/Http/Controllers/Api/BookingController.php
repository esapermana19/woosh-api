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
    private function autoCancelExpiredBookings()
    {
        $expiredBookings = \App\Models\Booking::where('status', 'pending')
            ->where('booking_date', '<', now()->subMinutes(10))
            ->get();

        foreach ($expiredBookings as $booking) {
            $booking->status = 'cancelled';
            $booking->save();

            \App\Models\Payment::where('booking_id', $booking->booking_id)
                ->where('payment_status', 'pending')
                ->update(['payment_status' => 'failed']);
        }
    }

    public function checkout(Request $request)
    {
        $this->autoCancelExpiredBookings();

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
                'booking_id' => $booking->booking_id,
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

    /**
     * Dapatkan riwayat tiket/booking user dengan berbagai status pembayaran
     * GET /api/tickets/history
     */
    public function getTicketHistory(Request $request)
    {
        $this->autoCancelExpiredBookings();

        $user = $request->user();

        // Query semua booking milik user dengan relasi yang diperlukan
        $bookings = Booking::where('user_id', $user->user_id)
            ->with([
                'schedule' => function ($query) {
                    $query->with(['departureStation', 'arrivalStation', 'train']);
                },
                'payment',
                'passengers.seat',
                'ticket'
            ])
            ->orderBy('booking_id', 'desc') // Booking terbaru di atas
            ->get();

        // Transform data untuk response Android
        $ticketHistory = $bookings->map(function ($booking) {
            return [
                'booking_id' => $booking->booking_id,
                'booking_code' => $booking->booking_code,
                'status' => $booking->status, // pending, success, failed
                'schedule' => [
                    'schedule_id' => $booking->schedule->schedule_id,
                    'train_name' => $booking->schedule->train->train_name ?? 'Unknown',
                    'departure' => [
                        'station_name' => $booking->schedule->departureStation->station_name ?? 'Unknown',
                        'time' => $booking->schedule->departure_time,
                    ],
                    'arrival' => [
                        'station_name' => $booking->schedule->arrivalStation->station_name ?? 'Unknown',
                        'time' => $booking->schedule->arrival_time,
                    ],
                    'price_per_seat' => (int) $booking->schedule->price,
                ],
                'payment' => [
                    'payment_id' => $booking->payment?->payment_id,
                    'method' => $booking->payment?->payment_method,
                    'status' => $booking->payment?->payment_status ?? 'pending', // pending, success, failed
                    'amount' => (int) $booking->payment?->amount ?? 0,
                    'date' => $booking->payment?->payment_date,
                ],
                'passengers' => $booking->passengers->map(function ($passenger) {
                    return [
                        'name' => $passenger->full_name,
                        'id_number' => $passenger->id_number,
                        'seat' => $passenger->seat->seat_number ?? 'Unknown',
                    ];
                })->toArray(),
                'ticket' => [
                    'ticket_id' => $booking->ticket?->ticket_id,
                    'qr_code' => $booking->ticket?->qr_code,
                    'status' => $booking->ticket?->status ?? 'not_issued',
                ],
                'is_completed' => $booking->status === 'success',
                'is_paid' => $booking->payment?->payment_status === 'success',
                'is_pending' => $booking->payment?->payment_status === 'pending',
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Riwayat tiket berhasil diambil',
            'total' => $ticketHistory->count(),
            'data' => $ticketHistory,
            'summary' => [
                'total_bookings' => $ticketHistory->count(),
                'completed' => $ticketHistory->where('is_completed', true)->count(),
                'paid' => $ticketHistory->where('is_paid', true)->count(),
                'pending' => $ticketHistory->where('is_pending', true)->count(),
                'failed' => $ticketHistory->where('status', 'failed')->count(),
            ]
        ], 200);
    }

    /**
     * Filter riwayat tiket berdasarkan status pembayaran
     * GET /api/tickets/history?filter=pending|paid|failed|completed
     */
    public function getTicketHistoryFiltered(Request $request)
    {
        $this->autoCancelExpiredBookings();

        $user = $request->user();
        $filter = $request->query('filter', 'all'); // all, pending, paid, failed, completed

        $query = Booking::where('user_id', $user->user_id)
            ->with([
                'schedule' => function ($query) {
                    $query->with(['departureStation', 'arrivalStation', 'train']);
                },
                'payment',
                'passengers.seat',
                'ticket'
            ]);

        // Apply filter berdasarkan status pembayaran
        switch ($filter) {
            case 'pending':
                $query->whereHas('payment', function ($q) {
                    $q->where('payment_status', 'pending');
                });
                break;
            case 'paid':
                $query->whereHas('payment', function ($q) {
                    $q->where('payment_status', 'success');
                });
                break;
            case 'failed':
                $query->whereHas('payment', function ($q) {
                    $q->where('payment_status', 'failed');
                });
                break;
            case 'completed':
                $query->where('status', 'success');
                break;
            // 'all' tidak perlu filter tambahan
        }

        $bookings = $query->orderBy('booking_id', 'desc')->get();

        // Transform data untuk response Android
        $ticketHistory = $bookings->map(function ($booking) {
            return [
                'booking_id' => $booking->booking_id,
                'booking_code' => $booking->booking_code,
                'status' => $booking->status,
                'schedule' => [
                    'schedule_id' => $booking->schedule->schedule_id,
                    'train_name' => $booking->schedule->train->train_name ?? 'Unknown',
                    'departure' => [
                        'station_name' => $booking->schedule->departureStation->station_name ?? 'Unknown',
                        'time' => $booking->schedule->departure_time,
                    ],
                    'arrival' => [
                        'station_name' => $booking->schedule->arrivalStation->station_name ?? 'Unknown',
                        'time' => $booking->schedule->arrival_time,
                    ],
                    'price_per_seat' => (int) $booking->schedule->price,
                ],
                'payment' => [
                    'payment_id' => $booking->payment?->payment_id,
                    'method' => $booking->payment?->payment_method,
                    'status' => $booking->payment?->payment_status ?? 'pending',
                    'amount' => (int) $booking->payment?->amount ?? 0,
                    'date' => $booking->payment?->payment_date,
                ],
                'passengers' => $booking->passengers->map(function ($passenger) {
                    return [
                        'name' => $passenger->full_name,
                        'id_number' => $passenger->id_number,
                        'seat' => $passenger->seat->seat_number ?? 'Unknown',
                    ];
                })->toArray(),
                'ticket' => [
                    'ticket_id' => $booking->ticket?->ticket_id,
                    'qr_code' => $booking->ticket?->qr_code,
                    'status' => $booking->ticket?->status ?? 'not_issued',
                ],
                'is_completed' => $booking->status === 'success',
                'is_paid' => $booking->payment?->payment_status === 'success',
                'is_pending' => $booking->payment?->payment_status === 'pending',
            ];
        });

        return response()->json([
            'success' => true,
            'message' => "Riwayat tiket dengan filter '$filter' berhasil diambil",
            'filter' => $filter,
            'total' => $ticketHistory->count(),
            'data' => $ticketHistory,
        ], 200);
    }
}
