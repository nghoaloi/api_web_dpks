<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $table = 'rooms';

    protected $fillable = [
        'type_id',
        'room_number',
        'status',
        'tang',
        'toa',
        'huong_ban_cong',
    ];

    public function roomType()
    {
        // Liên kết tới bảng room_types (khóa ngoại là type_id)
        return $this->belongsTo(RoomType::class, 'type_id')->select (['id', 'name',
        'base_price',
        'description',
        'max_cap',
        'payment_type',
        'allow_pet',
        'single_bed',
        'double_bed',]);
    }

    public function images()
    {
        return $this->hasMany(RoomImage::class, 'room_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'room_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'room_id');
    }
}
