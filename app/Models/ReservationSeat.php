<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationSeat extends Model
{
    protected $table = 'reservation_seats';

    public $timestamps = false;

    protected $fillable = [
        'reservation_id',
        'seat_id',
    ];
}
