<?php

namespace App\Http\Controllers;

use App\Models\BookingService;
use Illuminate\Http\Request;

class BookingServiceController extends Controller
{
    //  Lấy danh sách tất cả booking_service
    public function index()
    {
        $bookingServices = BookingService::with(['booking', 'service'])->get();

        return response()->json([
             
            'data' => $bookingServices
        ]);
    }

    // Lấy thông tin chi tiết 1 booking_service
    public function show($id)
    {
        $bookingService = BookingService::with(['booking', 'service'])->find($id);

        if (!$bookingService) {
            return response()->json([
                  
                'message' => 'Không tìm thấy dịch vụ đặt phòng'
            ], 404);
        }

        return response()->json([
             
            'data' => $bookingService
        ]);
    }

    //  Thêm mới booking_service
    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'service_id' => 'required|exists:services,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0'
        ]);

        $bookingService = BookingService::create($validated);

        return response()->json([
             
            'message' => 'Thêm dịch vụ cho đặt phòng thành công!',
            'data' => $bookingService
        ], 201);
    }

    //  Cập nhật booking_service
    public function update(Request $request, $id)
    {
        $bookingService = BookingService::find($id);

        if (!$bookingService) {
            return response()->json([
                  
                'message' => 'Không tìm thấy dịch vụ đặt phòng'
            ], 404);
        }

        $validated = $request->validate([
            'booking_id' => 'sometimes|exists:bookings,id',
            'service_id' => 'sometimes|exists:services,id',
            'quantity' => 'sometimes|integer|min:1',
            'price' => 'sometimes|numeric|min:0'
        ]);

        $bookingService->update($validated);

        return response()->json([
             
            'message' => 'Cập nhật dịch vụ đặt phòng thành công!',
            'data' => $bookingService
        ]);
    }

    //  Xóa booking_service
    public function destroy($id)
    {
        $bookingService = BookingService::find($id);

        if (!$bookingService) {
            return response()->json([
                  
                'message' => 'Không tìm thấy dịch vụ đặt phòng'
            ], 404);
        }

        $bookingService->delete();

        return response()->json([
             
            'message' => 'Xóa dịch vụ đặt phòng thành công!'
        ]);
    }
}
