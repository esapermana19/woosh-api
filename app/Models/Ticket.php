<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $primaryKey = 'ticket_id';
    protected $fillable = [
        'booking_id',
        'schedule_id',
        'seat_number',
        'status',
        'qr_code',
    ];
    public $timestamps = false;

    // Relasi ke Booking
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    // Relasi ke Schedule
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id', 'schedule_id');
    }
}
