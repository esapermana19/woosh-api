<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'departure_station' => 'required|integer',
            'arrival_station'   => 'required|integer',
            'date'              => 'required|date_format:Y-m-d',
        ]);

        $schedules = Schedule::where('departure_station', $request->departure_station)
            ->where('arrival_station', $request->arrival_station)
            ->whereDate('departure_time', $request->date)
            ->with(['train', 'departureStation', 'arrivalStation'])
            ->get();

        if ($schedules->isEmpty()) {
            return response()->json([
                'message' => 'Jadwal tidak ditemukan untuk rute dan tanggal tersebut.'
            ], 404);
        }

        $schedules = $schedules->map(function ($schedule) {

            // Total kursi fisik kereta ini (dari tabel seats)
            $totalSeats = DB::table('seats')->where('train_id', $schedule->train_id)->count();

            // Kursi yang sudah terisi = jumlah penumpang di booking yg pending/paid
            // (cancelled tidak dihitung karena kursinya sudah bebas kembali)
            $bookedSeats = DB::table('booking_passengers')
                ->join('bookings', 'bookings.booking_id', '=', 'booking_passengers.booking_id')
                ->where('bookings.schedule_id', $schedule->schedule_id)
                ->whereIn('bookings.status', ['pending', 'paid'])
                ->count();

            $schedule->available_seats = max(0, $totalSeats - $bookedSeats);

            return $schedule;
        });

        return response()->json([
            'message' => 'Jadwal berhasil ditemukan',
            'data'    => $schedules
        ]);
    }

    public function getSeats(Request $request)
    {
        $request->validate([
            'train_id' => 'required|integer',
            'schedule_id' => 'required|integer',
        ]);

        $seats = DB::table('seats')
            ->where('train_id', $request->train_id)
            ->get();

        // Ambil seat_id yang sudah dibooking untuk jadwal ini
        $bookedSeatIds = DB::table('booking_passengers')
            ->join('bookings', 'bookings.booking_id', '=', 'booking_passengers.booking_id')
            ->where('bookings.schedule_id', $request->schedule_id)
            ->whereIn('bookings.status', ['pending', 'paid', 'success', 'completed'])
            ->pluck('booking_passengers.seat_id')
            ->toArray();

        $seatsMapped = $seats->map(function ($seat) use ($bookedSeatIds) {
            return [
                'seat_id' => $seat->seat_id,
                'train_id' => $seat->train_id,
                'seat_number' => $seat->seat_number,
                'class' => $seat->class,
                'is_booked' => in_array($seat->seat_id, $bookedSeatIds)
            ];
        });

        return response()->json([
            'message' => 'Seats fetched successfully',
            'seats' => $seatsMapped
        ]);
    }
}
