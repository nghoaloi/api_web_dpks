<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    use HasFactory;

    protected $table = 'amenity';

    protected $fillable = [
        'name',
        'icon',
        'description',
    ];

    public function roomTypes()
    {
        return $this->belongsToMany(RoomType::class, 'room_type_amenity');
    }
}

