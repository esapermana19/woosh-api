<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function search(Request $request)
    {
        // Validasi input parameter query
        $request->validate([
            'departure_station' => 'required|integer',
            'arrival_station' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
        ]);

        // Cari jadwal berdasarkan stasiun asal, tujuan, dan tanggal keberangkatan
        $schedules = Schedule::where('departure_station', $request->departure_station)
            ->where('arrival_station', $request->arrival_station)
            ->whereDate('departure_time', $request->date)
            ->with(['train', 'departureStation', 'arrivalStation']) // Eager loading relasi
            ->get();

        if ($schedules->isEmpty()) {
            return response()->json([
                'message' => 'Jadwal tidak ditemukan untuk rute dan tanggal tersebut.'
            ], 404);
        }

        return response()->json([
            'message' => 'Jadwal berhasil ditemukan',
            'data' => $schedules
        ]);
    }
}
