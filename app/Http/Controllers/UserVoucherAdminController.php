<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserVoucher;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserVoucherAdminController extends Controller
{
    /**
     * Danh sách voucher của user
     */
    public function index(Request $request)
    {
        $query = UserVoucher::with(['user', 'voucher']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('created_at', 'desc')->get()
        ]);
    }

    /**
     * Lấy user_voucher theo ID
     */
    public function show($id)
    {
        $userVoucher = UserVoucher::with(['user', 'voucher'])->find($id);

        if (!$userVoucher) {
            return response()->json([
                'success' => false,
                'message' => 'User voucher không tồn tại'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $userVoucher
        ]);
    }

    /**
     * Gán voucher cho user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'voucher_id' => 'required|exists:vouchers,id',
            'expired_at' => 'nullable|date',
            'source' => 'nullable|string|max:50',
        ]);

        // Không cho gán trùng
        $exists = UserVoucher::where('user_id', $validated['user_id'])
            ->where('voucher_id', $validated['voucher_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'User đã có voucher này'
            ], 422);
        }

        // Nếu không truyền expired_at → mặc định +7 ngày
        $expiredAt = $validated['expired_at']
            ? \Carbon\Carbon::parse($validated['expired_at'])
            : now()->addDays(7);

        $userVoucher = UserVoucher::create([
            'user_id' => $validated['user_id'],
            'voucher_id' => $validated['voucher_id'],
            'expired_at' => $expiredAt,
            'source' => $validated['source'] ?? 'system',
            'is_used' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gán voucher cho user thành công',
            'data' => $userVoucher
        ], 201);
    }

    /**
     * Cập nhật user_voucher
     */
    public function update(Request $request, $id)
    {
        $userVoucher = UserVoucher::find($id);

        if (!$userVoucher) {
            return response()->json([
                'success' => false,
                'message' => 'User voucher không tồn tại'
            ], 404);
        }

        $validated = $request->validate([
            'is_used' => 'nullable|boolean',
            'expired_at' => 'nullable|date',
            'source' => 'nullable|string|max:50',
        ]);

        // Nếu đánh dấu đã dùng
        if (
            isset($validated['is_used']) &&
            $validated['is_used'] == true &&
            !$userVoucher->is_used
        ) {
            $userVoucher->markAsUsed();
        } else {
            $userVoucher->update($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật user voucher thành công',
            'data' => $userVoucher->fresh()
        ]);
    }

    /**
     * Xoá user_voucher
     */
    public function destroy($id)
    {
        $userVoucher = UserVoucher::find($id);

        if (!$userVoucher) {
            return response()->json([
                'success' => false,
                'message' => 'User voucher không tồn tại'
            ], 404);
        }

        // Không cho xoá nếu đã dùng
        if ($userVoucher->is_used) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xoá voucher đã sử dụng'
            ], 422);
        }

        $userVoucher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoá user voucher thành công'
        ]);
    }
}
