<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;


    protected $table = 'users';


    protected $fillable = [
        'id',
        'email',
        'password',
        'fullname',
        'phone',
        'role',
        'created_at',
        'updated_at',
        'gender',
        'address',
        'avartar',
        'status'
    ];

    //  Ẩn các trường khi trả về JSON
    protected $hidden = [
        'password',
        'remember_token',
    ];

    //  Ép kiểu dữ liệu (casting)
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function bookings()
{
    //1 user có nhiều lần đặt phòng
    return $this->hasMany(Booking::class, 'user_id', 'id');
}

}
