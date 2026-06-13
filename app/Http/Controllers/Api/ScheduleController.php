<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
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

    public function search(Request $request)
    {
        $this->autoCancelExpiredBookings();

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

        $schedulesExpanded = collect();

        foreach ($schedules as $schedule) {
            // Get all seat classes and total capacity for this train
            $classes = DB::table('seats')
                ->select('class', DB::raw('COUNT(seat_id) as total_seats'))
                ->where('train_id', $schedule->train_id)
                ->groupBy('class')
                ->get();

            foreach ($classes as $cls) {
                // Count booked seats specifically for this class on this schedule
                $bookedSeats = DB::table('booking_passengers')
                    ->join('bookings', 'bookings.booking_id', '=', 'booking_passengers.booking_id')
                    ->join('seats', 'seats.seat_id', '=', 'booking_passengers.seat_id')
                    ->where('bookings.schedule_id', $schedule->schedule_id)
                    ->where('seats.class', $cls->class)
                    ->whereIn('bookings.status', ['pending', 'paid', 'success', 'completed'])
                    ->count();

                $availableSeats = max(0, $cls->total_seats - $bookedSeats);

                // Determine dynamic price based on class
                $extraPrice = 0;
                if ($cls->class === 'business') {
                    $extraPrice = 100000;
                } elseif ($cls->class === 'vip') {
                    $extraPrice = 250000;
                }

                $scheduleClone = clone $schedule;
                // Add new dynamic attributes
                $scheduleClone->train_class = strtoupper($cls->class) . ' CLASS';
                $scheduleClone->price = $schedule->price + $extraPrice;
                $scheduleClone->available_seats = $availableSeats;

                $schedulesExpanded->push($scheduleClone);
            }
        }

        return response()->json([
            'message' => 'Jadwal berhasil ditemukan',
            'data'    => $schedulesExpanded
        ]);
    }

    public function getSeats(Request $request)
    {
        $this->autoCancelExpiredBookings();

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
