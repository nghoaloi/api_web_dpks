<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Booking;
use App\Models\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function update(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $validated = $request->validate([
                'booking_id' => 'required|integer|exists:bookings,id',
                'method' => 'nullable|string|max:255',
                'status' => 'nullable|string|in:Chờ thanh toán,Đã thanh toán,Thanh toán thất bại,Đã hoàn tiền',
                'trans_code' => 'nullable|string|max:255',
            ]);

            $booking = Booking::find($validated['booking_id']);
            
            if ($booking->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền cập nhật thanh toán này'
                ], 403);
            }

            DB::beginTransaction();

            $relatedBookings = Booking::where('user_id', $user->id)
                ->where('check_in', $booking->check_in)
                ->where('check_out', $booking->check_out)
                ->orderBy('id', 'asc')
                ->pluck('id')
                ->toArray();
            
            $payment = Payment::whereIn('booking_id', $relatedBookings)->first();

            Log::info('Payment update request', [
                'booking_id' => $validated['booking_id'],
                'related_bookings' => $relatedBookings,
                'payment_found' => $payment ? $payment->id : null,
                'user_id' => $user->id
            ]);

            if ($payment) {
                $payment->update([
                    'method' => $validated['method'] ?? $payment->method,
                    'status' => $validated['status'] ?? $payment->status,
                    'trans_code' => $validated['trans_code'] ?? $payment->trans_code,
                ]);
                
                Log::info('Payment updated', [
                    'payment_id' => $payment->id,
                    'status' => $payment->status,
                    'method' => $payment->method,
                    'trans_code' => $payment->trans_code
                ]);
            } else {
                $roomType = $booking->room->roomType;
                
                $totalRoomPrice = Booking::whereIn('id', $relatedBookings)->sum('total_price');
                
                $totalServicesPrice = BookingService::whereIn('booking_id', $relatedBookings)
                    ->sum(DB::raw('price * quantity'));
                
                $totalPrice = $totalRoomPrice + $totalServicesPrice;
                
                $paymentAmount = $totalPrice;
                if ($roomType->payment_type === 'trả trước một phần') {
                    $paymentAmount = $totalPrice * 0.3;
                } elseif ($roomType->payment_type === 'Không cần thanh toán trước') {
                    $paymentAmount = 0;
                }

                $firstBookingId = !empty($relatedBookings) ? $relatedBookings[0] : $booking->id;
                $payment = Payment::create([
                    'booking_id' => $firstBookingId,
                    'total_amount' => $paymentAmount,
                    'method' => $validated['method'] ?? null,
                    'status' => $validated['status'] ?? 'Chờ thanh toán',
                    'trans_code' => $validated['trans_code'] ?? null,
                ]);
                
                Log::info('Payment created', [
                    'payment_id' => $payment->id,
                    'booking_id' => $firstBookingId,
                    'total_amount' => $paymentAmount,
                    'total_room_price' => $totalRoomPrice,
                    'total_services_price' => $totalServicesPrice,
                    'total_price' => $totalPrice,
                    'status' => $payment->status,
                    'method' => $payment->method
                ]);
            }

            if ($payment->status === 'Đã thanh toán') {
                $booking->update(['status' => 'Đã thanh toán']);
                
                Booking::where('user_id', $user->id)
                    ->where('check_in', $booking->check_in)
                    ->where('check_out', $booking->check_out)
                    ->update(['status' => 'Đã thanh toán']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thanh toán thành công!',
                'data' => $payment
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực dữ liệu',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật thanh toán',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

