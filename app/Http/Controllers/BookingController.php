<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Payment;
use App\Models\Service;
use App\Models\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmationMail;
use Carbon\Carbon;

class BookingController extends Controller
{
    
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        $bookings = Booking::where('user_id', $user->id)
            ->with([
                'room.roomType',
                'services',
                'payment'
            ])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $booking = Booking::where('id', $id)
            ->where('user_id', $user->id)
            ->with('room.roomType')
            ->with('payment')
            ->with('services.service') 
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đặt phòng'
            ], 404);
        }

        $relatedBookings = Booking::where('user_id', $user->id)
            ->where('check_in', $booking->check_in)
            ->where('check_out', $booking->check_out)
            ->with('room.roomType')
            ->get();

        $roomType = $booking->room->roomType;
        $roomTotalPrice = $relatedBookings->sum('total_price');
        
        $servicesTotalPrice = 0;
        $servicesList = [];
        if ($booking->services && $booking->services->count() > 0) {
            foreach ($booking->services as $bookingService) {
                $servicePrice = $bookingService->price * $bookingService->quantity;
                $servicesTotalPrice += $servicePrice;
                $servicesList[] = [
                    'service_id' => $bookingService->service_id,
                    'service_name' => $bookingService->service->service_name ?? '',
                    'quantity' => $bookingService->quantity,
                    'price' => $bookingService->price,
                    'total' => $servicePrice
                ];
            }
        }
        
        $totalPrice = $roomTotalPrice + $servicesTotalPrice;
        $paymentType = $roomType->payment_type ?? 'Không cần thanh toán trước';
        
        $payment = $booking->payment;
        $paymentAmount = null;
        $needPayment = false;
        $remainingAmount = null;
        
        if ($payment) {
            $paymentAmount = $payment->total_amount;
            $needPayment = true;

            if ($paymentType === 'Thanh toán trước' && $payment->status === 'Đã thanh toán') {
                $paymentAmount = $totalPrice;
            } elseif ($paymentType === 'Trả trước một phần') {
                $remainingAmount = $totalPrice - $paymentAmount;
            }
        } else {
            if ($paymentType === 'Thanh toán trước') {
                $paymentAmount = $totalPrice;
                $needPayment = true;
            } elseif ($paymentType === 'Trả trước một phần') {
                $paymentAmount = $totalPrice * 0.3; 
                $needPayment = true;
                $remainingAmount = $totalPrice - $paymentAmount;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $booking,
            'bookings' => $relatedBookings,
            'room_total_price' => $roomTotalPrice,
            'services_total_price' => $servicesTotalPrice,
            'total_price' => $totalPrice,
            'services' => $servicesList,
            'booking_code' => 'BK' . str_pad($booking->id, 6, '0', STR_PAD_LEFT),
            'payment_type' => $paymentType,
            'payment_amount' => $paymentAmount,
            'need_payment' => $needPayment,
            'remaining_amount' => $remainingAmount,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $validated = $request->validate([
                'room_type_id' => 'required|integer|exists:room_types,id',
                'quantity' => 'required|integer|min:1|max:10',
                'check_in' => 'required|date',
                'check_out' => 'required|date|after_or_equal:check_in',
                'special_requests' => 'nullable|string|max:1000',
                'arrival_time' => 'nullable|string|max:6',
                'fullname' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'address' => 'nullable|string|max:255',
                'services' => 'nullable|array', 
                'services.*.service_id' => 'required_with:services|integer|exists:services,id',
                'services.*.quantity' => 'required_with:services|integer|min:1|max:100',
            ]);
            
            if (!empty($validated['arrival_time'])) {
                $timePattern = '/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/';
                if (!preg_match($timePattern, $validated['arrival_time'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Định dạng thời gian đến không hợp lệ. Vui lòng nhập theo định dạng HH:mm (ví dụ: 14:00)',
                        'errors' => ['arrival_time' => ['Định dạng thời gian không hợp lệ']]
                    ], 422);
                }
            }

            $roomTypeId = $validated['room_type_id'];
            $quantity = $validated['quantity'];
            $checkIn = Carbon::parse($validated['check_in']);
            $checkOut = Carbon::parse($validated['check_out']);

            $roomType = RoomType::find($roomTypeId);
            $pricePerNight = $roomType->base_price ?? 0;
            $paymentType = $roomType->payment_type ?? 'Không cần thanh toán trước';
            $nights = $checkIn->diffInDays($checkOut);
            if ($nights <= 0) {
                $nights = 1; 
            }
            $roomTotalPrice = $pricePerNight * $quantity * $nights;
            
            $servicesTotalPrice = 0;
            $selectedServices = [];
            if (!empty($validated['services']) && is_array($validated['services'])) {
                foreach ($validated['services'] as $serviceData) {
                    $serviceId = $serviceData['service_id'];
                    $serviceQuantity = $serviceData['quantity'];
                    
                    $service = Service::find($serviceId);
                    if ($service) {
                        $servicePrice = $service->price * $serviceQuantity;
                        $servicesTotalPrice += $servicePrice;
                        
                        $selectedServices[] = [
                            'service_id' => $serviceId,
                            'service_name' => $service->service_name ?? $service->name ?? null,
                            'quantity' => $serviceQuantity,
                            'price' => $service->price,
                            'total' => $servicePrice
                        ];
                    }
                }
            }
            
            $totalPrice = $roomTotalPrice + $servicesTotalPrice;

            DB::beginTransaction();
            
            try {
                $availableRooms = Room::where('type_id', $roomTypeId)
                    ->where('status', 'Còn phòng')
                    ->lockForUpdate() 
                    ->limit($quantity)
                    ->get();

                if ($availableRooms->count() < $quantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Xin lỗi, không đủ phòng trống. Hiện chỉ còn ' . $availableRooms->count() . ' phòng. Vui lòng thử lại hoặc chọn ngày khác.',
                        'available_count' => $availableRooms->count(),
                        'requested_count' => $quantity
                    ], 400);
                }

                $bookings = [];
                $pricePerRoom = $roomTotalPrice / $quantity;
                $paymentAmount = null; 
                $needPayment = false; 
                
                $paymentTypeNormalized = trim($paymentType);
                
                if ($paymentTypeNormalized === 'Thanh toán trước') {
                    $paymentAmount = $totalPrice; 
                    $needPayment = true;
                    Log::info('Payment required: Full payment', ['payment_type' => $paymentType, 'payment_amount' => $paymentAmount]);
                } elseif ($paymentTypeNormalized === 'Trả trước một phần') {
                    $paymentAmount = $totalPrice * 0.3; 
                    $needPayment = true;
                    Log::info('Payment required: Partial payment (30%)', ['payment_type' => $paymentType, 'payment_amount' => $paymentAmount]);
                } else {
                    $needPayment = false;
                    $paymentAmount = null;
                    Log::info('No payment required', ['payment_type' => $paymentType]);
                }
                
                foreach ($availableRooms as $room) {
                    $checkInDateTime = $checkIn->copy()->setTime(14, 0, 0); 
                    $checkOutDateTime = $checkOut->copy()->setTime(12, 0, 0); 
                    
                    $arrivalTime = !empty($validated['arrival_time']) ? $validated['arrival_time'] : null;
                    
                    $specialRequests = !empty($validated['special_requests']) ? $validated['special_requests'] : null;
                    
                    $booking = Booking::create([
                        'user_id' => $user->id,
                        'room_id' => $room->id,
                        'check_in' => $checkInDateTime->format('Y-m-d H:i:s'),
                        'check_out' => $checkOutDateTime->format('Y-m-d H:i:s'),
                        'arrival_time' => $arrivalTime,
                        'special_requests' => $specialRequests,
                        'total_price' => $pricePerRoom, 
                        'status' => 'Chờ xử lý',
                    ]);

                    $room->update(['status' => 'Đã có người']);

                    $bookings[] = $booking;
                }

                if (!empty($selectedServices)) {
                    foreach ($bookings as $booking) {
                        foreach ($selectedServices as $serviceData) {
                            BookingService::create([
                                'booking_id' => $booking->id,
                                'service_id' => $serviceData['service_id'],
                                'quantity' => $serviceData['quantity'],
                                'price' => $serviceData['price'], 
                            ]);
                        }
                    }
                }

                if ($needPayment && $paymentAmount !== null && count($bookings) > 0) {
                    try {
                        Payment::create([
                            'booking_id' => $bookings[0]->id, 
                            'total_amount' => $paymentAmount,
                            'method' => null, 
                            'status' => 'Chờ thanh toán',
                            'trans_code' => null,
                        ]);
                    } catch (\Exception $paymentError) {
                        Log::warning('Payment creation failed: ' . $paymentError->getMessage(), [
                            'booking_id' => $bookings[0]->id,
                            'payment_amount' => $paymentAmount
                        ]);
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e; 
            }

            $bookingCode = 'BK' . str_pad($bookings[0]->id, 6, '0', STR_PAD_LEFT);

            Log::info('Booking created - Payment info', [
                'payment_type' => $paymentType,
                'payment_type_normalized' => $paymentTypeNormalized ?? 'N/A',
                'need_payment' => $needPayment,
                'payment_amount' => $paymentAmount,
                'booking_code' => $bookingCode
            ]);

            try {
                $roomNumbers = isset($availableRooms) ? $availableRooms->pluck('room_number')->filter()->values()->all() : [];
                $remainingAmountEmail = null;
                if (($paymentTypeNormalized ?? '') === 'Trả trước một phần' && $paymentAmount !== null) {
                    $remainingAmountEmail = $totalPrice - $paymentAmount;
                } elseif (!$needPayment) {
                    $remainingAmountEmail = $totalPrice;
                }

                $bookingMailData = [
                    'booking_code' => $bookingCode,
                    'customer_name' => $validated['fullname'] ?? ($user->name ?? 'Quý khách'),
                    'room_type' => $roomType->name ?? '',
                    'quantity' => $quantity,
                    'room_numbers' => $roomNumbers,
                    'check_in' => $checkIn->format('d/m/Y'),
                    'check_out' => $checkOut->format('d/m/Y'),
                    'room_total_price' => $roomTotalPrice,
                    'services_total_price' => $servicesTotalPrice,
                    'total_price' => $totalPrice,
                    'services' => $selectedServices,
                    'payment_type' => $paymentType,
                    'payment_amount' => $paymentAmount,
                    'need_payment' => $needPayment,
                    'remaining_amount' => $remainingAmountEmail,
                    'booking_url' => url('/frontend/page/confirmation.html?booking_code=' . $bookingCode),
                    'support_hotline' => config('app.support_hotline', '1900 113 114 115'),
                ];

                if (!$needPayment) {
                    Mail::to($validated['email'])->send(new BookingConfirmationMail($bookingMailData));
                }
            } catch (\Exception $mailException) {
                Log::error('Booking confirmation email failed: ' . $mailException->getMessage(), [
                    'booking_code' => $bookingCode,
                    'email' => $validated['email'] ?? null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Đặt phòng thành công!',
                'data' => $bookings,
                'booking_code' => $bookingCode,
                'room_total_price' => $roomTotalPrice,
                'services_total_price' => $servicesTotalPrice,
                'total_price' => $totalPrice,
                'services' => $selectedServices,
                'payment_type' => $paymentType,
                'payment_amount' => $paymentAmount,
                'need_payment' => $needPayment,
                'remaining_amount' => $needPayment && ($paymentTypeNormalized ?? '') === 'Trả trước một phần'
                    ? ($totalPrice - $paymentAmount) 
                    : null,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực dữ liệu',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Booking error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đặt phòng',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
