<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVoucher extends Model
{
    use HasFactory;

    protected $table = 'user_vouchers';

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'voucher_id',
        'is_used',
        'used_at',
        'expired_at',
        'source',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

// quan hệ
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }


    /**
     * Voucher chưa sử dụng
     */
    public function scopeUnused($query)
    {
        return $query->where('is_used', false);
    }

    /**
     * Voucher đã sử dụng
     */
    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }

    /**
     * Voucher còn hạn
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expired_at')
              ->orWhere('expired_at', '>=', now());
        });
    }

    /**
     * Voucher user còn dùng được
     */
    public function scopeValid($query)
    {
        return $query
            ->unused()
            ->notExpired()
            ->whereHas('voucher', function ($q) {
                $q->valid(); // dùng scopeValid của bảng vouchers
            });
    }

    public function markAsUsed(): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now(),
        ]);

        // tăng lượt dùng của voucher
        $this->voucher?->increment('used_count');
    }
// hết hạn chưa
    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }
// coi dùng dc không
    public function canUse(): bool
    {
        return !$this->is_used && !$this->isExpired()
            && $this->voucher
            && $this->voucher->canUse();
    }
}
