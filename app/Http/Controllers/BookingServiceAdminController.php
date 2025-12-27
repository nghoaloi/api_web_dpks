<?php

namespace App\Http\Controllers;
use Carbon\Carbon;

use App\Models\BookingService;
use Illuminate\Http\Request;

class BookingServiceAdminController extends Controller
{
    //  Lấy danh sách tất cả booking_service
    public function index()
    {
        // Lấy tất cả booking_service kèm thông tin booking và service
        $bookingServices = BookingService::with(['booking.user', 'service'])->get();

        return response()->json([   
            'success' => true,
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
    public function getByBookingId($booking_id)
{
    // Kiểm tra booking_id hợp lệ
    if (!$booking_id || !is_numeric($booking_id)) {
        return response()->json([
            'success' => false,
            'message' => 'booking_id không hợp lệ'
        ], 400);
    }

    // Lấy danh sách dịch vụ theo booking_id, kèm theo thông tin service
    $services = BookingService::where('booking_id', $booking_id)
        ->with('service')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $services
    ], 200);
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
    // đặt dịch vụ trong ngày
public function booking_service_today()
{
    $today = Carbon::today();

    $totalToday = BookingService::whereDate('created_at', $today)->count();

    return response()->json([
        'success' => true,
        'data' => [
            'today' => $totalToday
        ]
    ], 200);
}
// đặt dịch vụ trong tháng
public function booking_service_month()
{
    $now = Carbon::now();

    $totalThisMonth = BookingService::whereYear('created_at', $now->year)
        ->whereMonth('created_at', $now->month)
        ->count();

    return response()->json([
        'success' => true,
        'data' => [
            'this_month' => $totalThisMonth
        ]
    ], 200);
}

}
