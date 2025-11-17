<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use HasFactory;

    protected $table = 'room_types';

    protected $fillable = [
        'name',
        'base_price',
        'description',
        'max_cap',
        'payment_type',
        'allow_pet',
        'single_bed',
        'double_bed',
    ];

    protected $casts = [
        'base_price' => 'float',
        'max_cap' => 'integer',
        'single_bed' => 'integer',
        'double_bed' => 'integer',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class, 'type_id');
    }

    public function images()
    {
        return $this->hasMany(RoomImage::class, 'type_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'type_id');
    }
}
