    (function() {
    const API_BASE = "http://localhost:8000/api/";
    let bookingData = {};
    let paymentData = {};

    async function loadBookingInfo() {
        const bookingResult = sessionStorage.getItem('booking_result');
        if (bookingResult) {
            try {
                paymentData = JSON.parse(bookingResult);
                console.log('Loaded booking data from sessionStorage:', paymentData);
                displayBookingSummary(paymentData);
                setupPaymentForm();
                return;
            } catch (e) {
                console.error('Error parsing sessionStorage data:', e);
            }
        }
        
        const urlParams = new URLSearchParams(window.location.search);
        const bookingId = urlParams.get('booking_id');
        
        if (!bookingId) {
            alert('Không tìm thấy thông tin đặt phòng');
            window.location.href = '../index.html';
            return;
        }

        try {
            const token = localStorage.getItem('auth_token');
            const res = await fetch(API_BASE + 'bookings/' + bookingId, {
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) {
                throw new Error('Không tìm thấy thông tin đặt phòng');
            }

            const json = await res.json();
            console.log('Loaded booking data from API:', json);
            displayBookingSummary(json);
            setupPaymentForm();
        } catch (e) {
            console.error('Error loading booking:', e);
            alert('Lỗi khi tải thông tin đặt phòng: ' + e.message);
        }
    }

    function displayBookingSummary(data) {
        bookingData = data.bookings ? data.bookings[0] : data.data || data;
        paymentData = data;

        if (data.booking_code) {
            document.getElementById('booking-code').textContent = data.booking_code;
        }

        if (bookingData.room && bookingData.room.room_type) {
            document.getElementById('room-type').textContent = bookingData.room.room_type.name;
        } else if (data.roomType) {
            document.getElementById('room-type').textContent = data.roomType.name;
        }

        if (data.bookings) {
            document.getElementById('quantity').textContent = data.bookings.length + ' phòng';
        }

        if (bookingData.check_in) {
            const checkIn = new Date(bookingData.check_in);
            document.getElementById('check-in').textContent = formatDate(checkIn);
        }

        if (bookingData.check_out) {
            const checkOut = new Date(bookingData.check_out);
            document.getElementById('check-out').textContent = formatDate(checkOut);
        }

        if (data.total_price) {
            document.getElementById('total-price').textContent = formatCurrency(data.total_price) + ' VNĐ';
        }

        if (data.payment_amount !== null && data.payment_amount !== undefined) {
            document.getElementById('payment-amount').textContent = formatCurrency(data.payment_amount) + ' VNĐ';
        } else if (data.total_price && data.need_payment) {
            document.getElementById('payment-amount').textContent = formatCurrency(data.total_price) + ' VNĐ';
        } else {
            document.getElementById('payment-amount').textContent = '0 VNĐ';
        }

        if (data.payment_type === 'trả trước một phần' && data.remaining_amount) {
            document.getElementById('remaining-amount-section').style.display = 'flex';
            document.getElementById('remaining-amount').textContent = formatCurrency(data.remaining_amount) + ' VNĐ';
        }
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount);
    }

    function formatDate(date) {
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric'
        };
        return date.toLocaleDateString('vi-VN', options);
    }

    function setupPaymentForm() {
        const paymentMethods = document.querySelectorAll('input[name="payment-method"]');
        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                const methodRadio = this.closest('.payment-method-radio');
                document.querySelectorAll('.payment-method-radio').forEach(radio => {
                    radio.classList.remove('selected');
                });
                methodRadio.classList.add('selected');

                document.getElementById('vnpay-form').style.display = 'none';
                document.getElementById('momo-form').style.display = 'none';

                if (this.value === 'vnpay') {
                    document.getElementById('vnpay-form').style.display = 'block';
                } else if (this.value === 'momo') {
                    document.getElementById('momo-form').style.display = 'block';
                }
            });
        });

        document.getElementById('vnpay-form').style.display = 'block';
    }

    window.processPayment = async function(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        const btnPay = document.getElementById('btn-pay');
        if (!btnPay) return;
        
        btnPay.disabled = true;
        btnPay.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';

        try {
            const token = localStorage.getItem('auth_token');
            const bookingId = bookingData.id || paymentData.bookings?.[0]?.id;
            
            if (!bookingId) {
                throw new Error('Không tìm thấy booking ID');
            }

            const selectedMethod = document.querySelector('input[name="payment-method"]:checked');
            const methodValue = selectedMethod ? selectedMethod.value : 'vnpay';
            const methodNames = {
                'vnpay': 'VNPay',
                'momo': 'MoMo'
            };
            const methodName = methodNames[methodValue] || 'VNPay';
            
            await new Promise(resolve => setTimeout(resolve, 1000));

            const res = await fetch(API_BASE + 'payments/update', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    booking_id: bookingId,
                    method: methodName,
                    status: 'Đã thanh toán',
                    trans_code: 'DEMO-' + methodValue.toUpperCase() + '-' + Date.now()
                })
            });

            if (res.ok) {
                const bookingCode = paymentData.booking_code || 'BK' + String(bookingId).padStart(6, '0');
                window.location.href = 'confirmation.html?booking_code=' + bookingCode;
            } else {
                const error = await res.json();
                throw new Error(error.message || 'Có lỗi xảy ra khi xử lý thanh toán');
            }

        } catch (e) {
            console.error('Payment error:', e);
            alert('Lỗi khi xử lý thanh toán: ' + e.message);
            btnPay.disabled = false;
            btnPay.innerHTML = '<i class="fas fa-lock"></i> Thanh toán';
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        loadBookingInfo();
    });
})();
