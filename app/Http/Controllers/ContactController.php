<?php

namespace App\Http\Controllers;

use App\Mail\ContactMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|in:booking,cancel,payment,voucher,other',
            'message' => 'required|string|max:2000',
        ], [
            'name.required' => 'Họ và tên là bắt buộc',
            'name.max' => 'Họ và tên không được vượt quá 120 ký tự',
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không hợp lệ',
            'subject.required' => 'Chủ đề là bắt buộc',
            'message.required' => 'Nội dung tin nhắn là bắt buộc',
            'message.max' => 'Nội dung tin nhắn không được vượt quá 2000 ký tự',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $subjectLabels = [
                'booking' => 'Đặt phòng',
                'cancel' => 'Hủy đặt phòng',
                'payment' => 'Thanh toán',
                'voucher' => 'Voucher',
                'other' => 'Khác'
            ];

            $contactData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'subject' => $request->subject,
                'subject_label' => $subjectLabels[$request->subject] ?? 'Khác',
                'message' => $request->message,
            ];

            // Gửi email đến địa chỉ được cấu hình
            $toEmail = env('CONTACT_EMAIL', config('mail.from.address'));
            
            Mail::to($toEmail)->send(new ContactMail($contactData));

            Log::info('Contact form submitted', [
                'email' => $request->email,
                'subject' => $request->subject,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất có thể.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Contact form error: ' . $e->getMessage(), [
                'email' => $request->email,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại sau.'
            ], 500);
        }
    }
}

