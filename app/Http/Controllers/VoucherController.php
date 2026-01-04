<?php

namespace App\Http\Controllers;

use App\Models\UserVoucher;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VoucherController extends Controller
{
    public function myVouchers(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Sử dụng timezone Việt Nam để so sánh
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        $vouchers = UserVoucher::with('voucher')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (UserVoucher $userVoucher) use ($now) {
                $voucher = $userVoucher->voucher;

                $isExpired = false;
                if ($userVoucher->expired_at && $userVoucher->expired_at->lt($now)) {
                    $isExpired = true;
                } elseif ($voucher && $voucher->end_date && $voucher->end_date->lt($now)) {
                    $isExpired = true;
                }

                return [
                    'id' => $userVoucher->id,
                    'code' => $userVoucher->code ?? ($voucher->code ?? null),
                    'is_used' => $userVoucher->is_used,
                    'used_at' => $userVoucher->used_at,
                    'expired_at' => $userVoucher->expired_at ?? $voucher->end_date ?? null,
                    'source' => $userVoucher->source,
                    'voucher' => $voucher,
                    'is_expired' => $isExpired,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $vouchers,
        ]);
    }

    public function apply(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'total_amount' => 'required|numeric|min:0',
        ]);

        $code = trim($validated['code']);
        $totalAmount = (float) $validated['total_amount'];
        // Lấy thời gian hiện tại theo giờ VN
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        // Tìm voucher không phân biệt hoa thường - chỉ tìm theo vouchers.code (vì user_vouchers.code thường là NULL)
        $userVoucher = UserVoucher::with('voucher')
            ->where('user_id', $user->id)
            ->whereHas('voucher', function ($q) use ($code) {
                $q->whereRaw('LOWER(code) = ?', [strtolower($code)]);
            })
            ->first();

        $voucher = null;

        if ($userVoucher) {
            $voucher = $userVoucher->voucher;
            if (!$voucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã giảm giá không hợp lệ.',
                ], 404);
            }
            
            if ($userVoucher->is_used) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã giảm giá này đã được sử dụng.',
                ], 422);
            }

            $expiredAt = $userVoucher->expired_at ?? ($voucher ? $voucher->end_date : null);
            if ($expiredAt) {
                // Parse datetime và coi nó đã là giờ VN (không convert)
                $expiredDateStr = Carbon::parse($expiredAt)->format('Y-m-d H:i:s');
                $expiredDate = Carbon::createFromFormat('Y-m-d H:i:s', $expiredDateStr, 'Asia/Ho_Chi_Minh');
                
                if ($expiredDate->lt($now)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mã giảm giá đã hết hạn. (Hết hạn: ' . $expiredDate->format('d/m/Y H:i') . ')',
                    ], 422);
                }
            }
        } else {
            // Nếu user không có voucher trong user_vouchers, không cho phép sử dụng
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không hợp lệ.',
            ], 404);
        }

        if ($voucher->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá hiện không khả dụng.',
            ], 422);
        }

        // Kiểm tra start_date: nếu NULL hoặc không có thì coi như đã có hiệu lực
        if ($voucher->start_date !== null) {
            // Parse datetime từ database và coi nó đã là giờ VN (không convert)
            // Lấy string từ database và parse với timezone VN
            $startDateStr = $voucher->start_date->format('Y-m-d H:i:s');
            $startDate = Carbon::createFromFormat('Y-m-d H:i:s', $startDateStr, 'Asia/Ho_Chi_Minh');
            
            if ($startDate->gt($now)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã giảm giá chưa bắt đầu có hiệu lực. (Bắt đầu từ: ' . $startDate->format('d/m/Y H:i') . ')',
                ], 422);
            }
        }

        // Kiểm tra end_date: nếu NULL thì không giới hạn thời gian
        if ($voucher->end_date !== null) {
            // Parse datetime từ database và coi nó đã là giờ VN (không convert)
            $endDateStr = $voucher->end_date->format('Y-m-d H:i:s');
            $endDate = Carbon::createFromFormat('Y-m-d H:i:s', $endDateStr, 'Asia/Ho_Chi_Minh');
            
            if ($endDate->lt($now)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã giảm giá đã hết hạn. (Hết hạn: ' . $endDate->format('d/m/Y H:i') . ')',
                ], 422);
            }
        }

        if (!is_null($voucher->usage_limit) && $voucher->used_count >= $voucher->usage_limit) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá đã được sử dụng tối đa số lần cho phép.',
            ], 422);
        }

        if (!is_null($voucher->min_order_amount) && $totalAmount < $voucher->min_order_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng chưa đạt giá trị tối thiểu để sử dụng mã giảm giá.',
            ], 422);
        }

        $discount = 0.0;
        if ($voucher->type === 'percent') {
            $discount = $totalAmount * ($voucher->value / 100);
            if (!is_null($voucher->max_discount_amount) && $discount > $voucher->max_discount_amount) {
                $discount = $voucher->max_discount_amount;
            }
        } else {
            $discount = $voucher->value;
        }

        if ($discount > $totalAmount) {
            $discount = $totalAmount;
        }

        $finalAmount = $totalAmount - $discount;

        // Trả về code gốc từ database (không phải code user nhập)
        $originalCode = $voucher->code;

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $originalCode,
                'voucher_id' => $voucher->id,
                'discount' => round($discount, 2),
                'final_amount' => round($finalAmount, 2),
                'description' => $voucher->description,
                'type' => $voucher->type,
                'value' => $voucher->value,
            ],
        ]);
    }
}



