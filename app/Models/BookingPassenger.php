<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPassenger extends Model
{
    // Sesuaikan dengan nama tabel dan primary key Anda
    protected $table = 'booking_passengers';
    protected $primaryKey = 'passenger_id';

    // Matikan timestamps jika di tabel ini tidak ada kolom created_at & updated_at
    public $timestamps = false;

    // WAJIB ADA: Relasi balik ke tabel Booking
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }
}
