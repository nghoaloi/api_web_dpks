<x-mail::message>
<div style="text-align:center; margin-bottom: 20px;">
    <h1 style="margin:0; font-size: 24px; color: #2563eb;">Hotel Booking</h1>
    <p style="margin: 8px 0 0; font-size: 16px; color: #4b5563;">Xác nhận đặt phòng thành công</p>
</div>

## Xin chào {{ $bookingData['customer_name'] ?? 'Quý khách' }},

Chúng tôi rất vui được xác nhận rằng yêu cầu đặt phòng của bạn đã được ghi nhận.

@component('mail::panel')
**Mã đặt phòng:** {{ $bookingData['booking_code'] ?? '—' }}  
**Loại phòng:** {{ $bookingData['room_type'] ?? '—' }}  
**Số lượng phòng:** {{ $bookingData['quantity'] ?? 1 }}  
**Phòng:** {{ !empty($bookingData['room_numbers']) ? implode(', ', $bookingData['room_numbers']) : 'Đang chuẩn bị' }}  
**Ngày nhận phòng:** {{ $bookingData['check_in'] ?? '—' }}  
**Ngày trả phòng:** {{ $bookingData['check_out'] ?? '—' }}  
**Tổng tiền dự kiến:** {{ isset($bookingData['total_price']) ? number_format($bookingData['total_price']) . ' VNĐ' : '—' }}  
@if(isset($bookingData['payment_amount']) && $bookingData['need_payment'])
**Đã thanh toán:** {{ number_format($bookingData['payment_amount']) }} VNĐ  
@endif
@if(isset($bookingData['remaining_amount']) && $bookingData['remaining_amount'] > 0)
**Thanh toán khi nhận phòng:** {{ number_format($bookingData['remaining_amount']) }} VNĐ  
@endif
@endcomponent

@if(!empty($bookingData['services']))
### Dịch vụ đã chọn
@component('mail::table')
| Dịch vụ | SL | Đơn giá | Thành tiền |
| :-- | :--: | --: | --: |
@foreach($bookingData['services'] as $service)
| {{ $service['service_name'] ?? ('Dịch vụ #' . ($service['service_id'] ?? '')) }} | {{ $service['quantity'] ?? 1 }} | {{ isset($service['price']) ? number_format($service['price']) . ' VNĐ' : '—' }} | {{ isset($service['total']) ? number_format($service['total']) . ' VNĐ' : '—' }} |
@endforeach
@endcomponent
@endif

@component('mail::button', ['url' => $bookingData['booking_url'] ?? url('/')])
Xem chi tiết đặt phòng
@endcomponent

### Thông tin quan trọng
- Vui lòng mang theo giấy tờ tùy thân khi nhận phòng.  
- Nếu có thay đổi về thời gian nhận phòng, hãy liên hệ sớm để chúng tôi hỗ trợ.  
- Mọi thắc mắc xin liên hệ qua email này hoặc hotline {{ $bookingData['support_hotline'] ?? '1900 636 527' }}.

Cảm ơn bạn đã tin tưởng Hotel Booking. Chúc bạn có kỳ nghỉ tuyệt vời!

Trân trọng,<br>
**Đội ngũ Hotel Booking**
</x-mail::message>
