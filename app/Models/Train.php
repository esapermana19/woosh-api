<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Train extends Model
{
    // Tentukan primary key jika bukan 'id' (di struktur Anda: train_id)
    protected $primaryKey = 'train_id';

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
