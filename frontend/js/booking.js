(function(){
    if (typeof API_BASE === 'undefined') {
        window.API_BASE = 'http://localhost:8000/api/';
    }

    let currentStep = 1;
    let bookingData = {
        roomTypeId: null,
        quantity: 1,
        checkIn: '',
        checkOut: '',
        roomType: null,
        userProfile: null
    };
    let servicesList = [];
    let selectedServices = {}; 

    document.addEventListener('DOMContentLoaded', () => {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            alert('Vui lòng đăng nhập để đặt phòng');
            window.location.href = 'login.html';
            return;
        }

        const params = new URLSearchParams(window.location.search);
        bookingData.roomTypeId = params.get('typeId');
        bookingData.quantity = parseInt(params.get('quantity') || '1');
        bookingData.checkIn = params.get('checkIn') || '';
        bookingData.checkOut = params.get('checkOut') || '';

        if (!bookingData.roomTypeId) {
            alert('Không tìm thấy thông tin phòng');
            window.location.href = '../index.html';
            return;
        }

        initializeBooking();
    });

    async function initializeBooking() {
        await Promise.all([
            loadRoomType(),
            loadUserProfile(),
            loadServices() 
        ]);
        renderStep1();
        loadUserDataToForm();
    }

    async function loadRoomType() {
        try {
            const res = await fetch(API_BASE + 'room-types/' + bookingData.roomTypeId);
            const json = await res.json();
            bookingData.roomType = json.data || json;
            renderRoomDetails();
        } catch(e) {
            console.error('Load room type failed', e);
            alert('Lỗi khi tải thông tin phòng');
        }
    }

    async function loadUserProfile() {
        try {
            const token = localStorage.getItem('auth_token');
            const res = await fetch(API_BASE + 'profile', {
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                }
            });
            const json = await res.json();
            bookingData.userProfile = json.data || json;
        } catch(e) {
            console.error('Load profile failed', e);
        }
    }

    async function loadServices() {
        try {
            const res = await fetch(API_BASE + 'services');
            const json = await res.json();
            servicesList = json.data || json || [];
        } catch(e) {
            console.error('Load services failed', e);
            servicesList = [];
        }
    }

    function renderRoomDetails() {
        const roomType = bookingData.roomType;
        if (!roomType) return;

        const roomImage = document.getElementById('booking-room-image');
        if (roomImage && roomType.images && roomType.images.length > 0) {
            let imgUrl = roomType.images[0].image_url || '';
            if (imgUrl && !imgUrl.startsWith('http')) {
                if (imgUrl.startsWith('/storage/')) {
                    imgUrl = 'http://localhost:8000' + imgUrl;
                } else {
                    imgUrl = 'http://localhost:8000/storage/' + imgUrl;
                }
            }
            roomImage.src = imgUrl || '../images/background.jpg';
        }

        document.getElementById('booking-room-name').textContent = roomType.name || 'Phòng';

        const ratingEl = document.getElementById('booking-room-rating');
        if (ratingEl) {
            const rating = roomType.rating || 0;
            const reviewCount = roomType.review_count || 0;
            
            if (rating > 0) {
                ratingEl.innerHTML = `
                    <span class="rating-score">${rating.toFixed(1)}</span>
                    <span class="rating-text">${getRatingText(rating)}</span>
                    ${reviewCount > 0 ? `<span class="rating-count">- ${reviewCount} đánh giá</span>` : ''}
                `;
            } else {
                ratingEl.innerHTML = '<span class="rating-text">Chưa có đánh giá</span>';
            }
        }

        // Room amenities - từ database
        const amenitiesBrief = document.getElementById('booking-room-amenities');
        if (amenitiesBrief) {
            let amenities = '<span><i class="fa fa-wifi"></i> WiFi miễn phí</span>';
            
            if (roomType.allow_pet && roomType.allow_pet === 'được mang') {
                amenities += '<span><i class="fa fa-paw"></i> Cho phép thú cưng</span>';
            } else if (roomType.allow_pet && roomType.allow_pet === 'không được mang') {
                amenities += '<span><i class="fa fa-paw"></i> Không cho phép thú cưng</span>';
            }
            
            amenitiesBrief.innerHTML = amenities;
        }

        const paymentInfo = document.getElementById('payment-method-info');
        if (paymentInfo && roomType.payment_type) {
            paymentInfo.textContent = roomType.payment_type;
        }
    }

    function getRatingText(rating) {
        if (rating >= 9) return 'Xuất sắc';
        if (rating >= 8) return 'Tuyệt vời';
        if (rating >= 7) return 'Tốt';
        if (rating >= 6) return 'Khá tốt';
        return 'Trung bình';
    }

    function renderStep1() {
        const roomType = bookingData.roomType;
        if (!roomType) return;

        document.getElementById('confirm-room-type').textContent = roomType.name || '—';
        document.getElementById('confirm-quantity').textContent = bookingData.quantity + ' phòng';
        
        // Format dates
        const checkIn = bookingData.checkIn ? formatDate(bookingData.checkIn) : 'Chưa chọn';
        const checkOut = bookingData.checkOut ? formatDate(bookingData.checkOut) : 'Chưa chọn';
        
        document.getElementById('confirm-checkin').textContent = checkIn;
        document.getElementById('confirm-checkout').textContent = checkOut;

        // Calculate total price (room + services)
        const pricePerNight = roomType.base_price || 0;
        const nights = calculateNights(bookingData.checkIn, bookingData.checkOut);
        const roomTotalPrice = pricePerNight * bookingData.quantity * nights;
        
        let servicesTotalPrice = 0;
        for (const serviceId in selectedServices) {
            const item = selectedServices[serviceId];
            servicesTotalPrice += item.service.price * item.quantity;
        }
        
        const totalPrice = roomTotalPrice + servicesTotalPrice;
        
        document.getElementById('confirm-total-price').textContent = 
            totalPrice > 0 ? Number(totalPrice).toLocaleString('vi-VN') + ' đ' : '—';

        renderBookingDetails();
    }

    function renderBookingDetails() {
        const roomType = bookingData.roomType;
        if (!roomType) return;

        const detailsList = document.getElementById('booking-details-list');
        if (!detailsList) return;

        const pricePerNight = roomType.base_price || 0;
        const nights = calculateNights(bookingData.checkIn, bookingData.checkOut);
        const roomTotalPrice = pricePerNight * bookingData.quantity * nights;
        
        let servicesTotalPrice = 0;
        let servicesHtml = '';
        for (const serviceId in selectedServices) {
            const item = selectedServices[serviceId];
            const serviceTotal = item.service.price * item.quantity;
            servicesTotalPrice += serviceTotal;
            servicesHtml += `
                <div class="detail-item">
                    <span class="detail-label">${escapeHtml(item.service.service_name)} x ${item.quantity}:</span>
                    <span class="detail-value">${Number(serviceTotal).toLocaleString('vi-VN')} đ</span>
                </div>
            `;
        }
        
        const totalPrice = roomTotalPrice + servicesTotalPrice;

        detailsList.innerHTML = `
            <div class="detail-item">
                <span class="detail-label">Loại phòng:</span>
                <span class="detail-value">${escapeHtml(roomType.name || '—')}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Số lượng:</span>
                <span class="detail-value">${bookingData.quantity} phòng</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Check-in:</span>
                <span class="detail-value">${bookingData.checkIn ? formatDate(bookingData.checkIn) : '—'}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Check-out:</span>
                <span class="detail-value">${bookingData.checkOut ? formatDate(bookingData.checkOut) : '—'}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Số đêm:</span>
                <span class="detail-value">${nights} đêm</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Giá phòng:</span>
                <span class="detail-value">${roomTotalPrice > 0 ? Number(roomTotalPrice).toLocaleString('vi-VN') + ' đ' : '—'}</span>
            </div>
            ${servicesHtml}
            <div class="detail-item" style="border-top: 2px solid #eef1f4; padding-top: 12px; margin-top: 8px;">
                <span class="detail-label" style="font-weight: 700;">Tổng giá:</span>
                <span class="detail-value" style="font-size: 18px; color: #0d47a1;">${totalPrice > 0 ? Number(totalPrice).toLocaleString('vi-VN') + ' đ' : '—'}</span>
            </div>
        `;
    }

    function loadUserDataToForm() {
        const user = bookingData.userProfile;
        if (!user) return;

        const fullnameInput = document.getElementById('fullname');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');
        const addressInput = document.getElementById('address');

        if (fullnameInput && user.fullname) {
            fullnameInput.value = user.fullname;
        }
        if (emailInput && user.email) {
            emailInput.value = user.email;
        }
        if (phoneInput && user.phone) {
            phoneInput.value = user.phone;
        }
        if (addressInput && user.address) {
            addressInput.value = user.address;
        }
    }

    function formatDate(dateString) {
        if (!dateString) return '—';
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    }

    function calculateNights(checkIn, checkOut) {
        if (!checkIn || !checkOut) return 1;
        const start = new Date(checkIn);
        const end = new Date(checkOut);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays || 1;
    }

    function renderServices() {
        const servicesListEl = document.getElementById('services-list');
        if (!servicesListEl) return;

        if (servicesList.length === 0) {
            servicesListEl.innerHTML = '<div class="loading-services">Không có dịch vụ nào</div>';
            return;
        }

        let html = '';
        servicesList.forEach(service => {
            const isSelected = selectedServices[service.id] && selectedServices[service.id].quantity > 0;
            const quantity = isSelected ? selectedServices[service.id].quantity : 0;
            
            html += `
                <div class="service-item ${isSelected ? 'selected' : ''}" data-service-id="${service.id}">
                    <div class="service-info">
                        <div class="service-name">${escapeHtml(service.service_name || 'Dịch vụ')}</div>
                        <div class="service-description">${escapeHtml(service.description || '')}</div>
                        <div class="service-price">${Number(service.price || 0).toLocaleString('vi-VN')} VNĐ</div>
                    </div>
                    <div class="service-controls">
                        <div class="quantity-control" style="display: ${isSelected ? 'flex' : 'none'}">
                            <button class="quantity-btn" onclick="decreaseServiceQuantity(${service.id})" ${quantity <= 1 ? 'disabled' : ''}>-</button>
                            <span class="quantity-value">${quantity}</span>
                            <button class="quantity-btn" onclick="increaseServiceQuantity(${service.id})">+</button>
                        </div>
                        <button class="btn-primary" onclick="toggleService(${service.id})" style="display: ${isSelected ? 'none' : 'block'}">
                            Chọn
                        </button>
                    </div>
                </div>
            `;
        });

        servicesListEl.innerHTML = html;
        updateServicesSummary();
    }

    function toggleService(serviceId) {
        const service = servicesList.find(s => s.id === serviceId);
        if (!service) return;

        if (selectedServices[serviceId]) {
                delete selectedServices[serviceId];
        } else {
            selectedServices[serviceId] = {
                service: service,
                quantity: 1
            };
        }

        renderServices();
    }

    function increaseServiceQuantity(serviceId) {
        if (selectedServices[serviceId]) {
            selectedServices[serviceId].quantity++;
            renderServices();
        }
    }

    function decreaseServiceQuantity(serviceId) {
        if (selectedServices[serviceId] && selectedServices[serviceId].quantity >= 0) {
            selectedServices[serviceId].quantity--;
            renderServices();
        } else if (selectedServices[serviceId] && selectedServices[serviceId].quantity === 1) {
            delete selectedServices[serviceId];
            renderServices();
        }
        renderServices();
    }

    function updateServicesSummary() {
        const summaryEl = document.getElementById('selected-services-summary');
        const listEl = document.getElementById('selected-services-list');
        const totalEl = document.getElementById('services-total-price');

        if (!summaryEl || !listEl || !totalEl) return;

        let total = 0;
        let html = '';

        for (const serviceId in selectedServices) {
            const item = selectedServices[serviceId];
            const serviceTotal = item.service.price * item.quantity;
            total += serviceTotal;

            html += `
                <div class="selected-service-item">
                    <span>${escapeHtml(item.service.service_name)} x ${item.quantity}</span>
                    <strong>${Number(serviceTotal).toLocaleString('vi-VN')} VNĐ</strong>
                </div>
            `;
        }

        if (total > 0) {
            summaryEl.style.display = 'block';
            listEl.innerHTML = html;
            totalEl.textContent = Number(total).toLocaleString('vi-VN');
        } else {
            summaryEl.style.display = 'none';
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    window.toggleService = toggleService;
    window.increaseServiceQuantity = increaseServiceQuantity;
    window.decreaseServiceQuantity = decreaseServiceQuantity;

    async function checkAvailabilityAndWarn() {
        if (!bookingData.roomTypeId || !bookingData.checkIn || !bookingData.checkOut) {
            return; 
        }

        try {
            const availabilityUrl = `${API_BASE}room-types/${bookingData.roomTypeId}/availability?` +
                `check_in=${encodeURIComponent(bookingData.checkIn)}&` +
                `check_out=${encodeURIComponent(bookingData.checkOut)}&` +
                `quantity=${bookingData.quantity}`;
            
            const res = await fetch(availabilityUrl);
            const json = await res.json();
            
            if (json.success && !json.is_available) {
                const warningEl = document.getElementById('availability-warning');
                if (warningEl) {
                    warningEl.style.display = 'block';
                    warningEl.innerHTML = `
                        <div style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 15px; margin: 20px 0;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fa fa-exclamation-triangle" style="color: #ff9800; font-size: 24px;"></i>
                                <div style="flex: 1;">
                                    <strong style="color: #856404;">Cảnh báo: Phòng có thể đã hết!</strong>
                                    <p style="margin: 5px 0 0 0; color: #856404;">
                                        Hiện chỉ còn ${json.available_count} phòng trống (bạn yêu cầu ${bookingData.quantity} phòng).
                                        Vui lòng hoàn tất đặt phòng sớm để đảm bảo có phòng.
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;
                }
            } else if (json.success && json.is_available) {
                const warningEl = document.getElementById('availability-warning');
                if (warningEl) {
                    warningEl.style.display = 'none';
                }
            }
        } catch (e) {
            console.warn('Không thể kiểm tra availability:', e);
        }
    }

    function nextStep() {
        if (currentStep >= 5) {
            return;
        }

        if (currentStep === 3) { 
            const form = document.getElementById('personal-info-form');
            if (!form || !form.checkValidity()) {
                if (form) form.reportValidity();
                return;
            }
        }

        if (currentStep === 1) {
            renderServices();
        }
        
        if (currentStep === 2 || currentStep === 3 || currentStep === 4) {
            checkAvailabilityAndWarn();
        }

        const currentStepEl = document.getElementById(`step-${currentStep}`);
        if (!currentStepEl) {
            console.error(`Current step ${currentStep} not found`);
            return;
        }

        currentStepEl.classList.remove('active');
        
        const currentProgressEl = document.querySelector(`.progress-step[data-step="${currentStep}"]`);
        if (currentProgressEl) {
            currentProgressEl.classList.remove('active');
            currentProgressEl.classList.add('completed');
        }
        
        currentStep++;
        
        const nextStepEl = document.getElementById(`step-${currentStep}`);
        if (!nextStepEl) {
            console.error(`Next step ${currentStep} not found`);
            currentStep--;
            currentStepEl.classList.add('active');
            return;
        }
        
        nextStepEl.classList.add('active');
        
        const nextProgressEl = document.querySelector(`.progress-step[data-step="${currentStep}"]`);
        if (nextProgressEl) {
            nextProgressEl.classList.add('active');
        }
    }

    function prevStep() {
        if (currentStep > 1) {  
            const currentStepEl = document.getElementById(`step-${currentStep}`);
            if (!currentStepEl) return;
            
            currentStepEl.classList.remove('active');
            
            const currentProgressEl = document.querySelector(`.progress-step[data-step="${currentStep}"]`);
            if (currentProgressEl) {
                currentProgressEl.classList.remove('active');
            }
            
            currentStep--;
            
            const prevStepEl = document.getElementById(`step-${currentStep}`);
            if (!prevStepEl) return;
            
            prevStepEl.classList.add('active');
            
            const prevProgressEl = document.querySelector(`.progress-step[data-step="${currentStep}"]`);
            if (prevProgressEl) {
                prevProgressEl.classList.remove('completed');
                prevProgressEl.classList.add('active');
            }
        }
    }

    function renderFinalSummary() {
        const roomType = bookingData.roomType;
        if (!roomType) return;

        const summaryEl = document.getElementById('final-booking-summary');
        if (!summaryEl) return;

        const pricePerNight = roomType.base_price || 0;
        const nights = calculateNights(bookingData.checkIn, bookingData.checkOut);
        const totalPrice = pricePerNight * bookingData.quantity * nights;

        summaryEl.innerHTML = `
            <div class="summary-final-item">
                <span>Loại phòng:</span>
                <span>${escapeHtml(roomType.name || '—')}</span>
            </div>
            <div class="summary-final-item">
                <span>Số lượng phòng:</span>
                <span>${bookingData.quantity} phòng</span>
            </div>
            <div class="summary-final-item">
                <span>Check-in:</span>
                <span>${bookingData.checkIn ? formatDate(bookingData.checkIn) : '—'}</span>
            </div>
            <div class="summary-final-item">
                <span>Check-out:</span>
                <span>${bookingData.checkOut ? formatDate(bookingData.checkOut) : '—'}</span>
            </div>
            <div class="summary-final-item">
                <span>Số đêm:</span>
                <span>${nights} đêm</span>
            </div>
            <div class="summary-final-item">
                <span>Giá/đêm:</span>
                <span>${pricePerNight > 0 ? Number(pricePerNight).toLocaleString('vi-VN') + ' đ' : '—'}</span>
            </div>
            <div class="summary-final-item">
                <span style="font-weight: 700; font-size: 18px;">Tổng giá:</span>
                <span style="font-weight: 700; font-size: 20px; color: #0d47a1;">${totalPrice > 0 ? Number(totalPrice).toLocaleString('vi-VN') + ' đ' : '—'}</span>
            </div>
        `;
    }

    async function completeBooking() {
        const btn = document.getElementById('btn-complete-booking');
        if (!btn) return;

        const termsCheckbox = document.getElementById('agree-terms');
        if (!termsCheckbox || !termsCheckbox.checked) {
            alert('Vui lòng đồng ý với điều khoản đặt phòng');
            return;
        }

        const fullname = document.getElementById('fullname').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const address = document.getElementById('address').value;
        const specialRequests = document.getElementById('special-requests').value;
        const arrivalTime = document.getElementById('arrival-time').value;

        if (!fullname || !email || !phone) {
            alert('Vui lòng điền đầy đủ thông tin bắt buộc');
            return;
        }

        if (!bookingData.checkIn || !bookingData.checkOut) {
            alert('Vui lòng chọn ngày check-in và check-out');
            window.location.href = `room.html?id=${bookingData.roomTypeId}`;
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang kiểm tra phòng...';

        try {
            const availabilityUrl = `${API_BASE}room-types/${bookingData.roomTypeId}/availability?` +
                `check_in=${encodeURIComponent(bookingData.checkIn)}&` +
                `check_out=${encodeURIComponent(bookingData.checkOut)}&` +
                `quantity=${bookingData.quantity}`;
            
            const availabilityRes = await fetch(availabilityUrl);
            const availabilityJson = await availabilityRes.json();
            
            if (availabilityJson.success && !availabilityJson.is_available) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-lock"></i> Hoàn tất đặt phòng';
                
                const message = `Xin lỗi, hiện chỉ còn ${availabilityJson.available_count} phòng trống (bạn yêu cầu ${bookingData.quantity} phòng).\n\n` +
                    `Vui lòng:\n` +
                    `- Giảm số lượng phòng xuống ${availabilityJson.available_count} phòng, hoặc\n` +
                    `- Chọn ngày khác, hoặc\n` +
                    `- Chọn loại phòng khác`;
                
                if (confirm(message + '\n\nBạn có muốn quay lại trang chọn phòng không?')) {
                    window.location.href = `room.html?id=${bookingData.roomTypeId}`;
                }
                return;
            }
        } catch (e) {
            console.warn('Không thể kiểm tra availability:', e);
        }

        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';

        try {
            const token = localStorage.getItem('auth_token');
            
            const servicesArray = [];
            for (const serviceId in selectedServices) {
                if (selectedServices[serviceId].quantity > 0) {
                    servicesArray.push({
                        service_id: parseInt(serviceId),
                        quantity: selectedServices[serviceId].quantity
                    });
                }
            }

            const bookingPayload = {
                room_type_id: bookingData.roomTypeId,
                quantity: bookingData.quantity,
                check_in: bookingData.checkIn,
                check_out: bookingData.checkOut,
                special_requests: specialRequests,
                arrival_time: arrivalTime,
                fullname: fullname,
                email: email,
                phone: phone,
                address: address,
                services: servicesArray.length > 0 ? servicesArray : null
            };

            const res = await fetch(API_BASE + 'bookings', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(bookingPayload)
            });

            const json = await res.json();

            if (res.ok && json.success) {
                console.log('Booking created successfully:', {
                    booking_code: json.booking_code,
                    payment_type: json.payment_type,
                    need_payment: json.need_payment,
                    payment_amount: json.payment_amount
                });

                sessionStorage.setItem('booking_result', JSON.stringify({
                    booking_code: json.booking_code,
                    total_price: json.total_price,
                    payment_type: json.payment_type,
                    payment_amount: json.payment_amount,
                    need_payment: json.need_payment,
                    remaining_amount: json.remaining_amount,
                    bookings: json.data
                }));

                if (json.need_payment === true) {
                    console.log('Redirecting to payment page...');
                    window.location.href = 'payment.html?booking_id=' + json.data[0].id;
                } else {
                    console.log('No payment needed, redirecting to confirmation page...');
                    window.location.href = 'confirmation.html?booking_code=' + json.booking_code;
                }
            } else {
                let errorMsg = json.message || json.error || 'Đặt phòng thất bại';
                
                if (json.available_count !== undefined) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-lock"></i> Hoàn tất đặt phòng';
                    
                    errorMsg = `Xin lỗi, không đủ phòng trống.\n\n` +
                        `Hiện chỉ còn: ${json.available_count} phòng\n` +
                        `Bạn yêu cầu: ${json.requested_count || bookingData.quantity} phòng\n\n` +
                        `Vui lòng:\n` +
                        `- Giảm số lượng phòng, hoặc\n` +
                        `- Chọn ngày khác, hoặc\n` +
                        `- Chọn loại phòng khác`;
                    
                    if (confirm(errorMsg + '\n\nBạn có muốn quay lại trang chọn phòng không?')) {
                        window.location.href = `room.html?id=${bookingData.roomTypeId}`;
                    }
                    return; 
                }
                
                const errorDetails = json.file && json.line 
                    ? `\n\nChi tiết: ${json.file} (dòng ${json.line})`
                    : '';
                throw new Error(errorMsg + errorDetails);
            }
        } catch(e) {
            console.error('Booking failed', e);
            console.error('Full error:', e);
            
            let errorMessage = 'Lỗi khi đặt phòng: ' + e.message;
            if (e.message.includes('SQL') || e.message.includes('database')) {
                errorMessage = 'Lỗi kết nối database. Vui lòng thử lại sau.';
            } else if (e.message.includes('arrival_time') || e.message.includes('column')) {
                errorMessage = 'Lỗi dữ liệu. Vui lòng kiểm tra lại thông tin đặt phòng.';
            }
            
            alert(errorMessage);
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-lock"></i> Hoàn tất đặt phòng';
        }
    }

    function escapeHtml(str){
        return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]));
    }

    window.nextStep = nextStep;
    window.prevStep = prevStep;
    window.completeBooking = completeBooking;
})();

