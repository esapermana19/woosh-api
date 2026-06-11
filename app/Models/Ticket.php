<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $primaryKey = 'ticket_id';
    protected $fillable = [
        'order_id',
        'schedule_id',
        'seat_number',
        'status',
    ];
    public $timestamps = false;
}
