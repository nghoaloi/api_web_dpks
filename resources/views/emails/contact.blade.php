<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ từ website</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #0d47a1, #1565c0); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
        <h1 style="margin: 0; font-size: 24px;">Liên hệ từ website Hotel Booking</h1>
    </div>
    
    <div style="background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e9ecef;">
        <h2 style="color: #2c3e50; margin-top: 0;">Thông tin khách hàng</h2>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; background: #fff; border: 1px solid #e9ecef; font-weight: 600; width: 150px;">Họ và tên:</td>
                <td style="padding: 10px; background: #fff; border: 1px solid #e9ecef;">{{ $contactData['name'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; background: #f8f9fa; border: 1px solid #e9ecef; font-weight: 600;">Email:</td>
                <td style="padding: 10px; background: #f8f9fa; border: 1px solid #e9ecef;">
                    <a href="mailto:{{ $contactData['email'] ?? '' }}" style="color: #0d47a1; text-decoration: none;">{{ $contactData['email'] ?? 'N/A' }}</a>
                </td>
            </tr>
            @if(!empty($contactData['phone']))
            <tr>
                <td style="padding: 10px; background: #fff; border: 1px solid #e9ecef; font-weight: 600;">Số điện thoại:</td>
                <td style="padding: 10px; background: #fff; border: 1px solid #e9ecef;">
                    <a href="tel:{{ $contactData['phone'] }}" style="color: #0d47a1; text-decoration: none;">{{ $contactData['phone'] }}</a>
                </td>
            </tr>
            @endif
            <tr>
                <td style="padding: 10px; background: #f8f9fa; border: 1px solid #e9ecef; font-weight: 600;">Chủ đề:</td>
                <td style="padding: 10px; background: #f8f9fa; border: 1px solid #e9ecef;">{{ $contactData['subject_label'] ?? 'Khác' }}</td>
            </tr>
        </table>
        
        <h3 style="color: #2c3e50; margin-top: 30px;">Nội dung tin nhắn:</h3>
        <div style="background: #fff; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px; white-space: pre-wrap;">{{ $contactData['message'] ?? 'N/A' }}</div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e9ecef; text-align: center; color: #6b7b8c; font-size: 12px;">
            <p>Email này được gửi tự động từ hệ thống Hotel Booking</p>
            <p>Thời gian: {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>

