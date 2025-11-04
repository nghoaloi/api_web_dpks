<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $table = 'bookings';

    protected $fillable = [
        'user_id',
        'room_id',
        'thoi_gian_den_du_kien',
        'check_in',
        'check_out',
        'total_price',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');

    }
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function services()
    {
        return $this->hasMany(BookingService::class, 'booking_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'booking_id');
    }
}
