<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $table = 'bookings';
    protected $primaryKey = 'booking_id';
    public $timestamps = false;

    // Relasi ke User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Relasi ke Schedule
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id', 'schedule_id');
    }

    // Relasi ke Payment
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'booking_id', 'booking_id');
    }

    // Relasi ke BookingPassenger
    public function passengers(): HasMany
    {
        return $this->hasMany(BookingPassenger::class, 'booking_id', 'booking_id');
    }

    // Relasi ke Ticket
    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class, 'booking_id', 'booking_id');
    }
}
