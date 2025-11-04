<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomImage extends Model
{
    use HasFactory;

    protected $table = 'room_images';

    protected $fillable = [
        'type_id',
        'image_url',
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'type_id');
    }
}
