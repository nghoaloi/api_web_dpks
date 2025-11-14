<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    //    Lấy danh sách tất cả các thanh toán
    public function index()
    {
        $payments = Payment::with('booking')->get();

        return response()->json([
              
            'data' => $payments
        ]);
    }

    //    Lấy chi tiết 1 thanh toán theo ID
    public function show($id)
    {
        $payment = Payment::with('booking')->find($id);

        if (!$payment) {
            return response()->json([
                  
                'message' => 'Không tìm thấy thanh toán'
            ], 404);
        }

        return response()->json([
              
            'data' => $payment
        ]);
    }

    //    Thêm mới thanh toán
    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:255',
            'payment_status' => 'required|string|max:255',
            'payment_date' => 'nullable|date',
        ]);

        $payment = Payment::create($validated);

        return response()->json([
              
            'message' => 'Thêm thanh toán thành công!',
            'data' => $payment
        ], 201);
    }

    //    Cập nhật thông tin thanh toán
    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                  
                'message' => 'Không tìm thấy thanh toán'
            ], 404);
        }

        $validated = $request->validate([
            'booking_id' => 'sometimes|exists:bookings,id',
            'amount' => 'sometimes|numeric|min:0',
            'payment_method' => 'sometimes|string|max:255',
            'payment_status' => 'sometimes|string|max:255',
            'payment_date' => 'nullable|date',
        ]);

        $payment->update($validated);

        return response()->json([
              
            'message' => 'Cập nhật thanh toán thành công!',
            'data' => $payment
        ]);
    }

    //    Xóa thanh toán
    public function destroy($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                  
                'message' => 'Không tìm thấy thanh toán'
            ], 404);
        }

        $payment->delete();

        return response()->json([
              
            'message' => 'Xóa thanh toán thành công!'
        ]);
    }
}
