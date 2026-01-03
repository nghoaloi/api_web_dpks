<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomTypeAmenity extends Model
{
    use HasFactory;

    // Tên bảng (nếu không theo quy tắc số nhiều mặc định)
    protected $table = 'room_type_amenities';

    // Khóa chính
    protected $primaryKey = 'id';

    // Cho phép auto increment
    public $incrementing = true;

    // Kiểu khóa chính
    protected $keyType = 'int';

    // Laravel có created_at & updated_at
    public $timestamps = true;

    // Các cột được phép insert/update
    protected $fillable = [
        'room_type_id',
        'amenity_id'
    ];

    /* ======================
        RELATIONSHIPS
    ====================== */

    // Liên kết tới loại phòng
    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    // Liên kết tới tiện nghi
    public function amenity()
    {
        return $this->belongsTo(Amenity::class, 'amenity_id');
    }

}
