<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Booking;
use App\Models\BookingService;
use App\Models\UserVoucher;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmationMail;
use Carbon\Carbon;

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
                
                // Tính tổng voucher discount từ các booking
                $totalVoucherDiscount = Booking::whereIn('id', $relatedBookings)
                    ->sum('voucher_discount');
                
                // Tổng giá sau khi trừ voucher discount
                $totalPrice = ($totalRoomPrice + $totalServicesPrice) - $totalVoucherDiscount;
                
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

                try {
                    $bookingsCollection = Booking::whereIn('id', $relatedBookings)
                        ->with('room.roomType')
                        ->get();

                    $firstBookingRecord = $bookingsCollection->first();
                    $roomType = $firstBookingRecord->room->roomType ?? $booking->room->roomType;
                    $roomNumbers = $bookingsCollection->map(function ($bookingItem) {
                        return $bookingItem->room->room_number ?? null;
                    })->filter()->values()->all();
                    $quantity = $bookingsCollection->count();
                    $roomTotalPrice = $bookingsCollection->sum('total_price');
                    $checkInFormatted = null;
                    $checkOutFormatted = null;
                    if ($firstBookingRecord && $firstBookingRecord->check_in) {
                        $checkInFormatted = Carbon::parse($firstBookingRecord->check_in)->format('d/m/Y');
                    }
                    if ($firstBookingRecord && $firstBookingRecord->check_out) {
                        $checkOutFormatted = Carbon::parse($firstBookingRecord->check_out)->format('d/m/Y');
                    }

                    $bookingServices = BookingService::whereIn('booking_id', $relatedBookings)
                        ->with('service')
                        ->get();

                    $servicesList = $bookingServices->map(function ($bookingService) {
                        $serviceName = $bookingService->service->service_name ?? $bookingService->service->name ?? '';
                        $total = $bookingService->price * $bookingService->quantity;
                        return [
                            'service_id' => $bookingService->service_id,
                            'service_name' => $serviceName,
                            'quantity' => $bookingService->quantity,
                            'price' => $bookingService->price,
                            'total' => $total,
                        ];
                    })->toArray();

                    $servicesTotalPrice = $bookingServices->sum(function ($bookingService) {
                        return $bookingService->price * $bookingService->quantity;
                    });

                    // Tính tổng voucher discount từ các booking
                    $totalVoucherDiscount = Booking::whereIn('id', $relatedBookings)
                        ->sum('voucher_discount');
                    
                    // Tổng giá sau khi trừ voucher discount
                    $totalPrice = ($roomTotalPrice + $servicesTotalPrice) - $totalVoucherDiscount;
                    // Lấy voucher_code từ booking đầu tiên có voucher
                    $voucherCode = null;
                    $voucherDiscount = 0;
                    foreach ($relatedBookings as $bid) {
                        $b = Booking::find($bid);
                        if ($b && $b->voucher_code) {
                            $voucherCode = $b->voucher_code;
                            $voucherDiscount = $b->voucher_discount ?? 0;
                            break;
                        }
                    }
                    $paymentType = $roomType->payment_type ?? 'Không cần thanh toán trước';

                    $remainingAmount = null;
                    if ($paymentType === 'Trả trước một phần') {
                        $remainingAmount = $totalPrice - ($payment->total_amount ?? 0);
                    } elseif ($paymentType === 'Không cần thanh toán trước') {
                        $remainingAmount = $totalPrice;
                    }

                    $bookingIdForCode = $bookingsCollection->first()->id ?? $booking->id;
                    $bookingCode = 'BK' . str_pad($bookingIdForCode, 6, '0', STR_PAD_LEFT);

                    $mailData = [
                        'booking_code' => $bookingCode,
                        'customer_name' => $user->name ?? 'Quý khách',
                        'room_type' => $roomType->name ?? '',
                        'quantity' => $quantity,
                        'room_numbers' => $roomNumbers,
                        'check_in' => $checkInFormatted,
                        'check_out' => $checkOutFormatted,
                        'room_total_price' => $roomTotalPrice,
                        'services_total_price' => $servicesTotalPrice,
                        'total_price' => $totalPrice - $voucherDiscount,
                        'services' => $servicesList,
                        'payment_type' => $paymentType,
                        'payment_amount' => $payment->total_amount,
                        'need_payment' => true,
                        'remaining_amount' => $remainingAmount,
                        'booking_url' => url('/frontend/page/confirmation.html?booking_code=' . $bookingCode),
                        'support_hotline' => config('app.support_hotline', '1900 113 114 115'),
                    ];

                    $recipientEmail = $booking->user->email ?? $user->email;
                    if ($recipientEmail) {
                        Mail::to($recipientEmail)->send(new BookingConfirmationMail($mailData));
                    }

                    // Đánh dấu voucher đã sử dụng và tăng used_count
                    if (!empty($voucherCode)) {
                        try {
                            // Lấy voucher_id từ booking đầu tiên có voucher
                            $voucherId = null;
                            foreach ($relatedBookings as $bid) {
                                $b = Booking::find($bid);
                                if ($b && $b->voucher_id) {
                                    $voucherId = $b->voucher_id;
                                    break;
                                }
                            }

                            // Tìm voucher theo code hoặc voucher_id
                            $voucher = null;
                            if ($voucherId) {
                                $voucher = Voucher::find($voucherId);
                            }
                            if (!$voucher && $voucherCode) {
                                $voucher = Voucher::whereRaw('LOWER(code) = ?', [strtolower($voucherCode)])->first();
                            }

                            if ($voucher) {
                                // Tăng used_count trong bảng vouchers
                                $voucher->increment('used_count');
                                // Reload lại voucher để lấy giá trị used_count mới
                                $voucher->refresh();
                                Log::info('Voucher used_count incremented', [
                                    'voucher_id' => $voucher->id,
                                    'voucher_code' => $voucher->code,
                                    'new_used_count' => $voucher->used_count,
                                ]);
                            } else {
                                Log::warning('Voucher not found when trying to increment used_count', [
                                    'voucher_code' => $voucherCode,
                                    'voucher_id' => $voucherId,
                                ]);
                            }

                            // Đánh dấu user_voucher đã sử dụng - tìm theo voucher_id (chính xác hơn)
                            $updatedCount = 0;
                            if ($voucherId) {
                                // Tìm theo voucher_id (chính xác nhất) - ưu tiên cách này
                                $updatedCount = UserVoucher::where('user_id', $user->id)
                                    ->where('voucher_id', $voucherId)
                                    ->where('is_used', false)
                                    ->update([
                                        'is_used' => true,
                                        'used_at' => Carbon::now(),
                                    ]);
                                
                                Log::info('Tried to mark voucher as used by voucher_id', [
                                    'user_id' => $user->id,
                                    'voucher_id' => $voucherId,
                                    'updated_count' => $updatedCount,
                                ]);
                            }
                            
                            // Nếu không tìm thấy theo voucher_id, thử tìm theo code (từ vouchers.code)
                            if ($updatedCount === 0 && $voucherCode) {
                                $updatedCount = UserVoucher::where('user_id', $user->id)
                                    ->whereHas('voucher', function ($q) use ($voucherCode) {
                                        $q->whereRaw('LOWER(code) = ?', [strtolower($voucherCode)]);
                                    })
                                    ->where('is_used', false)
                                    ->update([
                                        'is_used' => true,
                                        'used_at' => Carbon::now(),
                                    ]);
                                
                                Log::info('Tried to mark voucher as used by code', [
                                    'user_id' => $user->id,
                                    'voucher_code' => $voucherCode,
                                    'updated_count' => $updatedCount,
                                ]);
                            }
                            
                            Log::info('Voucher marked as used', [
                                'user_id' => $user->id,
                                'voucher_code' => $voucherCode,
                                'voucher_id' => $voucherId,
                                'updated_count' => $updatedCount,
                            ]);
                        } catch (\Exception $voucherEx) {
                            Log::error('Mark voucher used failed: ' . $voucherEx->getMessage(), [
                                'user_id' => $user->id,
                                'booking_id' => $booking->id,
                                'voucher_code' => $voucherCode,
                                'trace' => $voucherEx->getTraceAsString(),
                            ]);
                        }
                    }
                } catch (\Exception $mailException) {
                    Log::error('Payment confirmation email failed: ' . $mailException->getMessage(), [
                        'booking_id' => $booking->id,
                        'payment_id' => $payment->id,
                    ]);
                }
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

