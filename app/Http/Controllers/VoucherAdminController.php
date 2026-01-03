<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherAdminController extends Controller
{
    /**
     * Lấy danh sách voucher
     */
    public function index()
    {
        $vouchers = Voucher::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $vouchers
        ]);
    }

    /**
     * Lấy voucher theo ID
     */
    public function show($id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher không tồn tại'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $voucher
        ]);
    }

    /**
     * Thêm voucher
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code',
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'description' => 'nullable|string|max:255',
        ]);

        $voucher = Voucher::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo voucher thành công',
            'data' => $voucher
        ], 201);
    }

    /**
     * Cập nhật voucher
     */
    public function update(Request $request, $id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher không tồn tại'
            ], 404);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vouchers', 'code')->ignore($voucher->id)
            ],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'description' => 'nullable|string|max:255',
        ]);

        $voucher->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật voucher thành công',
            'data' => $voucher
        ]);
    }

    /**
     * Xoá voucher
     */
    public function destroy($id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher không tồn tại'
            ], 404);
        }

        // Nếu muốn soft delete thì đổi sang SoftDeletes
        $voucher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoá voucher thành công'
        ]);
    }
}
