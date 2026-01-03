<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Voucher extends Model
{
    use HasFactory;

    protected $table = 'vouchers'; // nếu bảng tên là voucher thì đổi lại

    protected $primaryKey = 'id';

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'start_date',
        'end_date',
        'usage_limit',
        'used_count',
        'status',
        'description',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
    ];


// check trạn thái
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
// lọc hợp lệ của voucher
    public function scopeValid($query)
    {
        return $query
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                  ->orWhereColumn('used_count', '<', 'usage_limit');
            });
    }

// check hết hạn
    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }
// check số lần dùng
    public function isOutOfUsage(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }
// tổng hợp 2 cái trên có thể dùng không
    public function canUse(): bool
    {
        return $this->status === 'active'
            && !$this->isExpired()
            && !$this->isOutOfUsage();
    }
// cách tính số tiền giảm 
    public function calculateDiscount(float $orderAmount): float
    {   

        if ($this->min_order_amount && $orderAmount < $this->min_order_amount) {
            return 0;
        }

        if ($this->type === 'percent') {
            $discount = $orderAmount * ($this->value / 100);
        } else {
            $discount = $this->value;
        }

        if ($this->max_discount_amount) {
            $discount = min($discount, $this->max_discount_amount);
        }

        return max(0, $discount);
    }
}
