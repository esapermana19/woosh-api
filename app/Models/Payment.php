<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
