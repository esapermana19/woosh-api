<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Station extends Model
{
     // Tentukan primary key jika bukan 'id' (di struktur Anda: station_id)
    protected $primaryKey = 'station_id';

    // Relasi ke Stasiun Asal
    public function departureStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'departure_station', 'station_id');
    }

    // Relasi ke Stasiun Tujuan
    public function arrivalStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'arrival_station', 'station_id');
    }

    // Relasi ke Kereta
    public function train(): BelongsTo
    {
        return $this->belongsTo(Train::class, 'train_id', 'train_id');
    }
}
