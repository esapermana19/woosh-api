<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $primaryKey = 'payment_id';
    protected $fillable = [
        'booking_id',
        'payment_method',
        'payment_date',
        'amount',
        'payment_status',
    ];
    public $timestamps = false;

    // Relasi ke Booking
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }
}
