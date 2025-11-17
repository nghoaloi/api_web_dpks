(function() {
    const API_BASE = "http://localhost:8000/api/";

    function loadBookingInfo() {
        const bookingResult = sessionStorage.getItem('booking_result');
        
        if (!bookingResult) {
            const urlParams = new URLSearchParams(window.location.search);
            const bookingCode = urlParams.get('booking_code');
            
            if (bookingCode) {
                fetchBookingByCode(bookingCode);
                return;
            }
            
            alert('Không tìm thấy thông tin đặt phòng');
            window.location.href = '../index.html';
            return;
        }

        const data = JSON.parse(bookingResult);
        
        document.getElementById('booking-code').textContent = data.booking_code || '-';
        
        if (data.total_price) {
            document.getElementById('total-price').textContent = 
                formatCurrency(data.total_price) + ' VNĐ';
        }
        
        if (data.payment_type) {
            const paymentTypeText = {
                'Không cần thanh toán trước': 'Thanh toán tại khách sạn khi nhận phòng',
                'Thanh toán trước': 'Thanh toán trước (Đã thanh toán)',
                'Trả trước một phần': 'Trả trước một phần'
            };
            document.getElementById('payment-type').textContent = 
                paymentTypeText[data.payment_type] || data.payment_type;
        }
        
        if (data.bookings && data.bookings.length > 0) {
            const firstBooking = data.bookings[0];
            if (firstBooking.check_in) {
                const checkInDate = new Date(firstBooking.check_in);
                document.getElementById('check-in-date').textContent = 
                    formatDate(checkInDate);
            }
            if (firstBooking.check_out) {
                const checkOutDate = new Date(firstBooking.check_out);
                document.getElementById('check-out-date').textContent = 
                    formatDate(checkOutDate);
            }
        }
        
        // sessionStorage.removeItem('booking_result');
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount);
    }

    function formatDate(date) {
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return date.toLocaleDateString('vi-VN', options);
    }

    function fetchBookingByCode(bookingCode) {
        console.log('Fetch booking by code:', bookingCode);
    }

    document.addEventListener('DOMContentLoaded', loadBookingInfo);
})();

