<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingService extends Model
{
    use HasFactory;

    protected $table = 'booking_service';

    protected $fillable = [
        'id',
        'booking_id',
        'service_id',
        'quantity',
        'price',
        'created_at',
        'updated_at'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id')->select(['id','service_name','description']);
    }
}
