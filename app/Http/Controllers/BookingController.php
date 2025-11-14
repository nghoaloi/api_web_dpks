<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    // Lấy toàn bộ danh sách booking
    public function index()
    {
       
        $bookings = Booking::all();

        return response()->json($bookings);
    }
    // public function index()
    // {
    //     $bookings = Booking::with(['user', 'room', 'services', 'payment'])->get();

    //     return response()->json([
    //         'success' => true,
    //         'data' => $bookings
    //     ], 200);
    // }
    // Lấy booking theo ID
    // public function show($id)
    // {
    //     $booking = Booking::with(['user', 'room', 'services', 'payment'])->find($id);

    //     if (!$booking) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Không tìm thấy booking'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data' => $booking
    //     ], 200);
    // }
        public function show($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy booking'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $booking
        ], 200);
    }
    // Thêm booking mới
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'thoi_gian_den_du_kien' => 'nullable|date_format:H:i:s',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after_or_equal:check_in',
            'total_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:Chờ xử lý,Đã thanh toán,Đã hủy'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $booking = Booking::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Thêm booking thành công',
            'data' => $booking
        ], 201);
    }

    // Cập nhật booking
    public function update(Request $request, $id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy booking'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|required|exists:users,id',
            'room_id' => 'sometimes|required|exists:rooms,id',
            'thoi_gian_den_du_kien' => 'nullable|date_format:H:i:s',
            'check_in' => 'sometimes|required|date',
            'check_out' => 'sometimes|required|date|after_or_equal:check_in',
            'total_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:Chờ xử lý,Đã thanh toán,Đã hủy'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $booking->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật booking thành công',
            'data' => $booking
        ], 200);
    }

    // Xoá booking
    public function destroy($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy booking'
            ], 404);
        }

        $booking->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoá booking thành công'
        ], 200);
    }
}
