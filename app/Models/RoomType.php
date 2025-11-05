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
        'payment_type' => 'string',
        'allow_pet' => 'string',
    ];

    public $timestamps = true;

    public function rooms()
    {
        // Khóa ngoại trong bảng rooms là "type_id"
        return $this->hasMany(Room::class, 'type_id');
    }
}
