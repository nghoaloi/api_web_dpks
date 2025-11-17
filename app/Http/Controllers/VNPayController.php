<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VNPayController extends Controller
{
    public function createPaymentUrl(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $validated = $request->validate([
                'booking_id' => 'required|integer|exists:bookings,id',
            ]);

            $booking = Booking::where('id', $validated['booking_id'])
                ->where('user_id', $user->id)
                ->with('room.roomType')
                ->with('payment')
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đặt phòng'
                ], 404);
            }

            $payment = $booking->payment;
            $amount = $payment ? $payment->total_amount : $booking->total_price;
            
            $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
            $vnp_TmnCode = env('VNPAY_TMN_CODE');
            $vnp_HashSecret = env('VNPAY_HASH_SECRET');
            $ngrokUrl = env('NGROK_URL', '');
            
            if (!$vnp_TmnCode || !$vnp_HashSecret || empty($ngrokUrl)) {
                return response()->json([
                    'success' => false,
                    'message' => 'VNPay chưa được cấu hình đầy đủ trong file .env'
                ], 400);
            }
            
            $vnp_Returnurl = rtrim($ngrokUrl, '/') . "/api/vnpay/return";
            
            $vnp_TxnRef = 'BK' . str_pad($booking->id, 6, '0', STR_PAD_LEFT) . '-' . time();
            $vnp_OrderInfo = 'Thanh toan dat phong ' . $vnp_TxnRef;
            $vnp_OrderType = 'other';
            $vnp_Amount = intval($amount * 100);
            $vnp_Locale = 'vn';
            $vnp_IpAddr = $request->ip();
            $vnp_CreateDate = date('YmdHis');
            
            $inputData = [
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => $vnp_CreateDate,
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => $vnp_OrderType,
                "vnp_ReturnUrl" => $vnp_Returnurl,
                "vnp_TxnRef" => $vnp_TxnRef,
            ];

            ksort($inputData);
            
            $query = "";
            $hashdata = "";
            $i = 0;
            
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                    $query .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . "=" . urlencode($value);
                    $query .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
            }

            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            
            $vnp_Url .= "?" . $query . '&vnp_SecureHash=' . $vnpSecureHash;
            
            Log::info('VNPay URL Debug', [
                'tmn_code' => $vnp_TmnCode,
                'return_url' => $vnp_Returnurl,
                'hash_secret_length' => strlen($vnp_HashSecret),
                'hash_secret_preview' => substr($vnp_HashSecret, 0, 8) . '...',
                'secure_hash' => $vnpSecureHash,
                'hashdata_length' => strlen($hashdata),
                'hashdata_preview' => substr($hashdata, 0, 100) . '...',
            ]);
            
            return response()->json([
                'success' => true,
                'payment_url' => $vnp_Url,
                'txn_ref' => $vnp_TxnRef,
                'amount' => $amount,
                'debug' => [
                    'return_url' => $vnp_Returnurl,
                    'tmn_code' => $vnp_TmnCode,
                    'hash_secret_length' => strlen($vnp_HashSecret),
                    'note' => 'Kiểm tra Return URL trong VNPay Dashboard phải khớp với return_url ở trên'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('VNPay create payment URL error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo URL thanh toán VNPay: ' . $e->getMessage()
            ], 500);
        }
    }

    public function return(Request $request)
    {
        try {
            $vnp_SecureHash = $request->input('vnp_SecureHash');
            $vnp_TxnRef = $request->input('vnp_TxnRef');
            $vnp_ResponseCode = $request->input('vnp_ResponseCode');
            $vnp_TransactionStatus = $request->input('vnp_TransactionStatus');
            
            $bookingId = (int) str_replace('BK', '', explode('-', $vnp_TxnRef)[0]);
            
            $booking = Booking::find($bookingId);
            if (!$booking) {
                return redirect('/page/payment.html?error=booking_not_found');
            }

            if ($vnp_ResponseCode == '00' && $vnp_TransactionStatus == '00') {
                $payment = Payment::firstOrCreate(
                    ['booking_id' => $bookingId],
                    [
                        'total_amount' => $booking->total_price,
                        'method' => 'VNPay',
                        'status' => 'Chờ thanh toán',
                        'trans_code' => null,
                    ]
                );
                
                $payment->update([
                    'status' => 'Đã thanh toán',
                    'trans_code' => $vnp_TxnRef,
                ]);
                
                $booking->update(['status' => 'Đã thanh toán']);
                
                return redirect('/page/confirmation.html?booking_code=BK' . str_pad($bookingId, 6, '0', STR_PAD_LEFT));
            } else {
                    return redirect('/page/payment.html?booking_id=' . $bookingId . '&error=payment_failed');
            }

        } catch (\Exception $e) {
            Log::error('VNPay return error: ' . $e->getMessage());
            return redirect('/page/payment.html?error=processing_error');
        }
    }
    
    public function ipn(Request $request)
    {
        try {
            $vnp_HashSecret = env('VNPAY_HASH_SECRET');
            $inputData = $request->except(['vnp_SecureHash']);
            
            ksort($inputData);
            $hashData = "";
            $i = 0;
            
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashData .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
            }
            
            $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
            
            $vnp_TxnRef = $request->input('vnp_TxnRef');
            $vnp_ResponseCode = $request->input('vnp_ResponseCode');
            $vnp_TransactionStatus = $request->input('vnp_TransactionStatus');
            $vnp_Amount = $request->input('vnp_Amount');
            
            $bookingId = (int) str_replace('BK', '', explode('-', $vnp_TxnRef)[0]);
            $booking = Booking::find($bookingId);
            
            if (!$booking) {
                return response()->json(['RspCode' => '01', 'Message' => 'Order not found'], 200);
            }

            if ($secureHash != $request->input('vnp_SecureHash')) {
                return response()->json(['RspCode' => '97', 'Message' => 'Checksum failed'], 200);
            }

            $payment = Payment::where('booking_id', $bookingId)->first();
            if ($payment && $payment->status === 'Đã thanh toán') {
                return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed'], 200);
            }

            if ($vnp_ResponseCode == '00' && $vnp_TransactionStatus == '00') {
                if (!$payment) {
                    Payment::create([
                        'booking_id' => $bookingId,
                        'total_amount' => $vnp_Amount / 100,
                        'method' => 'VNPay',
                        'status' => 'Đã thanh toán',
                        'trans_code' => $vnp_TxnRef,
                    ]);
                } else {
                    $payment->update([
                        'status' => 'Đã thanh toán',
                        'trans_code' => $vnp_TxnRef,
                    ]);
                }
                
                $booking->update(['status' => 'Đã thanh toán']);
                
                return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success'], 200);
            } else {
                return response()->json(['RspCode' => '07', 'Message' => 'Transaction failed'], 200);
            }

        } catch (\Exception $e) {
            Log::error('VNPay IPN error: ' . $e->getMessage());
            return response()->json(['RspCode' => '99', 'Message' => 'Unknown error'], 200);
        }
    }
}
