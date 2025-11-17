(function(){
    if (typeof API_BASE === 'undefined') {
        window.API_BASE = 'http://localhost:8000/api/';
    }

    let currentImages = [];
    let currentMainIndex = 0;
    let currentRoomType = null;
    let roomQuantity = 1;
    let checkInDate = '';
    let checkOutDate = '';

    document.addEventListener('DOMContentLoaded', () => {
        const id = new URLSearchParams(location.search).get('id');
        if (!id) {
            document.querySelector('.room-container').innerHTML = '<div style="text-align:center;padding:40px;">Không tìm thấy thông tin loại phòng</div>';
            return;
        }
        fetchRoomType(id);
        
     
        const today = new Date().toISOString().split('T')[0];
        const checkInInput = document.getElementById('check-in-date');
        const checkOutInput = document.getElementById('check-out-date');
        
        if (checkInInput) {
            checkInInput.min = today;
            checkInInput.addEventListener('change', (e) => {
                checkInDate = e.target.value;
                if (checkOutInput && checkInDate) {
                    const minCheckOut = new Date(checkInDate);
                    minCheckOut.setDate(minCheckOut.getDate() + 1);
                    checkOutInput.min = minCheckOut.toISOString().split('T')[0];
                    
    
                    if (checkOutDate && checkOutDate < checkOutInput.min) {
                        checkOutDate = '';
                        checkOutInput.value = '';
                    }
                }
                updateBookingSummary();
            });
        }
        
        if (checkOutInput) {
            checkOutInput.min = today;
            checkOutInput.addEventListener('change', (e) => {
                checkOutDate = e.target.value;
                updateBookingSummary();
            });
        }
        
  
        const quantitySelect = document.getElementById('room-quantity');
        if (quantitySelect) {
            quantitySelect.addEventListener('change', (e) => {
                roomQuantity = parseInt(e.target.value);
                updateBookingSummary();
            });
        }
        
        const btnBook = document.getElementById('btn-book');
        if (btnBook) {
            btnBook.addEventListener('click', () => {
                const token = localStorage.getItem('auth_token');
                if (!token) {
                    alert('Vui lòng đăng nhập để đặt phòng');
                    window.location.href = 'login.html';
                    return;
                }
                

                if (!checkInDate || !checkOutDate) {
                    alert('Vui lòng chọn ngày nhận phòng và ngày trả phòng');
                    return;
                }
                
                if (checkOutDate <= checkInDate) {
                    alert('Ngày trả phòng phải sau ngày nhận phòng');
                    return;
                }

                const bookingUrl = `booking.html?typeId=${encodeURIComponent(currentRoomType.id)}&quantity=${roomQuantity}&checkIn=${encodeURIComponent(checkInDate)}&checkOut=${encodeURIComponent(checkOutDate)}`;
                window.location.href = bookingUrl;
            });
        }
    });

    async function fetchRoomType(id){
        try{
            const res = await fetch(API_BASE + 'room-types/' + encodeURIComponent(id));
            const json = await res.json();
            const roomType = json.data || json;
            if (!roomType) {
                document.querySelector('.room-container').innerHTML = '<div style="text-align:center;padding:40px;">Không tìm thấy thông tin loại phòng</div>';
                return;
            }
            render(roomType);
        }catch(e){
            console.error('Load room type failed', e);
            document.querySelector('.room-container').innerHTML = '<div style="text-align:center;padding:40px;">Lỗi khi tải thông tin loại phòng</div>';
        }
    }

    function render(roomType){
        currentRoomType = roomType;
        
        // Title
        document.getElementById('room-title').textContent = roomType.name || 'Loại phòng';
        
        const roomNumberEl = document.getElementById('room-number');
        if (roomNumberEl) roomNumberEl.style.display = 'none';
        
        const statusEl = document.getElementById('room-status');
        if (statusEl) statusEl.style.display = 'none';
        
    
        const ratingEl = document.getElementById('room-rating');
        if (ratingEl) {
            const rating = roomType.rating || 0;
            const reviewCount = roomType.review_count || 0;
            
            if (rating > 0) {
                ratingEl.innerHTML = `
                    <span class="rating-score">${rating.toFixed(1)}</span>
                    <span class="rating-text">${getRatingText(rating)}</span>
                    ${reviewCount > 0 ? `<span class="rating-count">- ${reviewCount} đánh giá</span>` : ''}
                `;
                ratingEl.style.display = 'flex';
            } else {
                ratingEl.style.display = 'none';
            }
        }
        

        const price = (roomType.base_price ?? undefined) !== undefined ? Number(roomType.base_price).toLocaleString('vi-VN') : '—';
        document.getElementById('room-price').textContent = price;
        
 
        const availableCount = roomType.available_rooms_count ?? 0;
        const availabilityEl = document.getElementById('room-availability');
        if (availabilityEl) {
            availabilityEl.innerHTML = `<div class="availability-simple">Còn <strong>${availableCount}</strong> phòng trống</div>`;
        }
        

        const warningEl = document.getElementById('booking-warning');
        if (warningEl && availableCount > 0 && availableCount <= 3) {
            warningEl.style.display = 'block';
            warningEl.innerHTML = `<i class="fa fa-exclamation-triangle"></i> Chỉ còn ${availableCount} phòng!`;
            warningEl.className = 'booking-warning warning-low';
        } else if (warningEl) {
            warningEl.style.display = 'none';
        }
        

        updateQuantitySelector(availableCount);
        
        // Images
        renderGallery(roomType.images || []);
        
        // Features
        const features = [
            { icon: 'fa-users', label: 'Tối đa', value: (roomType.max_cap ?? '—') + ' người' },
            { icon: 'fa-bed', label: 'Giường đơn', value: (roomType.single_bed ?? 0) + ' giường' },
            { icon: 'fa-bed', label: 'Giường đôi', value: (roomType.double_pet ?? 0) + ' giường' },
        ];
        document.getElementById('room-features').innerHTML = features
            .map(f => `
                <div class="feature-item">
                    <div class="feature-icon"><i class="fa ${f.icon}"></i></div>
                    <div class="feature-text">
                        <div class="feature-label">${escapeHtml(f.label)}</div>
                        <div class="feature-value">${escapeHtml(f.value)}</div>
                    </div>
                </div>
            `).join('');
        
        // Badges
        const badges = [];
        if (roomType.payment_type) {
            badges.push({ text: roomType.payment_type, class: 'info' });
        }
        if (roomType.allow_pet) {
            const petClass = roomType.allow_pet === 'được mang' ? 'success' : 'warning';
            badges.push({ text: roomType.allow_pet === 'được mang' ? 'Cho phép thú cưng' : 'Không cho thú cưng', class: petClass });
        }
        document.getElementById('room-badges').innerHTML = badges
            .map(b => `<span class="badge ${b.class}">${escapeHtml(b.text)}</span>`)
            .join('');
        
        // Description
        document.getElementById('room-description').textContent = roomType.description || 'Chưa có mô tả chi tiết.';
        

        renderAmenities(roomType);
        

        const paymentInfoEl = document.getElementById('payment-info');
        if (paymentInfoEl && roomType.payment_type) {
            paymentInfoEl.textContent = roomType.payment_type;
        }
        
     
        updateBookingSummary();
        
   
        const btnBook = document.getElementById('btn-book');
        if (btnBook) {
            if (availableCount === 0) {
                btnBook.disabled = true;
                btnBook.textContent = 'Đã hết phòng';
                btnBook.style.opacity = '0.6';
                btnBook.style.cursor = 'not-allowed';
            } else {
                btnBook.disabled = false;
                btnBook.textContent = 'Đặt phòng';
                btnBook.style.opacity = '1';
                btnBook.style.cursor = 'pointer';
            }
        }
    }

    function updateQuantitySelector(maxQuantity) {
        const quantitySelect = document.getElementById('room-quantity');
        if (!quantitySelect) return;
        
        quantitySelect.innerHTML = '';
        const max = Math.min(maxQuantity, 10); // Giới hạn tối đa 10 phòng
        
        for (let i = 1; i <= max; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = `${i} phòng${i > 1 ? '' : ''}`;
            quantitySelect.appendChild(option);
        }
        
        if (maxQuantity === 0) {
            quantitySelect.disabled = true;
        }
    }

    function updateBookingSummary() {
        if (!currentRoomType) return;
        
        const pricePerNight = currentRoomType.base_price ?? 0;
        const nights = calculateNights(checkInDate, checkOutDate);
        const totalPrice = pricePerNight * roomQuantity * nights;
        
        const summaryNights = document.getElementById('summary-nights');
        const summaryPricePerNight = document.getElementById('summary-price-per-night');
        const summaryRooms = document.getElementById('summary-rooms');
        const summaryRoomsPrice = document.getElementById('summary-rooms-price');
        const summaryTotalPrice = document.getElementById('summary-total-price');
        
        if (summaryNights) {
            summaryNights.textContent = nights > 0 ? `${nights} đêm` : '— đêm';
        }
        
        if (summaryPricePerNight) {
            summaryPricePerNight.textContent = pricePerNight > 0 ? `${Number(pricePerNight).toLocaleString('vi-VN')} đ/đêm` : '—';
        }
        
        if (summaryRooms) {
            summaryRooms.textContent = `${roomQuantity} phòng`;
        }
        
        if (summaryRoomsPrice) {
            const roomsTotal = pricePerNight * roomQuantity * (nights > 0 ? nights : 1);
            summaryRoomsPrice.textContent = roomsTotal > 0 ? `${Number(roomsTotal).toLocaleString('vi-VN')} đ` : '—';
        }
        
        if (summaryTotalPrice) {
            summaryTotalPrice.textContent = totalPrice > 0 ? `${Number(totalPrice).toLocaleString('vi-VN')} đ` : '—';
        }
    }

    function calculateNights(checkIn, checkOut) {
        if (!checkIn || !checkOut) return 0;
        const start = new Date(checkIn);
        const end = new Date(checkOut);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays || 0;
    }

    function renderAmenities(roomType) {
        const amenitiesList = document.getElementById('room-amenities-list');
        if (!amenitiesList) return;
        

        const amenities = [
            { icon: 'fa-wifi', name: 'WiFi miễn phí' },
            { icon: 'fa-tv', name: 'TV màn hình phẳng' },
            { icon: 'fa-snowflake', name: 'Điều hòa không khí' },
            { icon: 'fa-shower', name: 'Phòng tắm riêng' },
            { icon: 'fa-bed', name: 'Giường thoải mái' },
            { icon: 'fa-bath', name: 'Đồ vệ sinh cá nhân miễn phí' },
        ];
        
  
        if (roomType.allow_pet === 'được mang') {
            amenities.push({ icon: 'fa-paw', name: 'Cho phép thú cưng' });
        }
        
        amenitiesList.innerHTML = amenities.map(amenity => `
            <div class="amenity-item">
                <i class="fa ${amenity.icon}"></i>
                <span>${escapeHtml(amenity.name)}</span>
            </div>
        `).join('');
    }

    function renderGallery(images){
        currentImages = [];
        const galleryEl = document.getElementById('room-gallery');
        const mainImg = document.getElementById('main-image');
        
        if (images && images.length > 0) {
            currentImages = images.map(img => {
                let url = img.image_url || '';
                if (url && !url.startsWith('http')) {
                    if (url.startsWith('/storage/')) {
                        url = 'http://localhost:8000' + url;
                    } else if (url.startsWith('storage/')) {
                        url = 'http://localhost:8000/' + url;
                    } else {
                        url = 'http://localhost:8000/storage/' + url;
                    }
                }
                return url;
            }).filter(url => url); 
        }
        

        if (currentImages.length === 0) {
            currentImages = ['../images/background.jpg'];
        }
        
        
        if (mainImg && currentImages[0]) {
            let imageLoaded = false;
            
    
            const showGallery = () => {
                if (!imageLoaded && galleryEl) {
                    imageLoaded = true;
                    galleryEl.style.display = '';
                }
            };
            
  
            mainImg.onload = showGallery;
            
            mainImg.onerror = function() {  
                if (!currentImages[0].includes('background.jpg')) {
                    mainImg.src = '../images/background.jpg';
                    currentImages[0] = '../images/background.jpg';
                } else {
                    showGallery(); 
                }
            };
            
            mainImg.src = currentImages[0];
            
            if (mainImg.complete && mainImg.naturalWidth > 0) {
                showGallery();
            }
            
            setTimeout(showGallery, 50);
        } else if (galleryEl) {
            galleryEl.style.display = '';
        }
        
        const thumbsContainer = document.getElementById('gallery-thumbs');
        if (thumbsContainer) {
            if (currentImages.length > 1) {
                thumbsContainer.style.display = '';
                const thumbs = currentImages.slice(1, 5).map((url, idx) => `
                    <div class="gallery-thumb ${idx === 0 ? 'active' : ''}" data-index="${idx + 1}">
                        <img src="${escapeHtml(url)}" alt="room ${idx + 2}">
                    </div>
                `).join('');
                thumbsContainer.innerHTML = thumbs;
                
                thumbsContainer.querySelectorAll('.gallery-thumb').forEach(thumb => {
                    thumb.addEventListener('click', function() {
                        const index = parseInt(this.dataset.index);
                        currentMainIndex = index;
                        mainImg.src = currentImages[index];
                        thumbsContainer.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
                        this.classList.add('active');
                    });
                });
            } else {
                thumbsContainer.innerHTML = '';
                thumbsContainer.style.display = 'none';
                const galleryMain = document.querySelector('.gallery-main');
                if (galleryMain) {
                    galleryMain.style.gridColumn = '1 / -1';
                }
            }
        }
    }

    function getRatingText(rating) {
        if (rating >= 9) return 'Xuất sắc';
        if (rating >= 8) return 'Tuyệt vời';
        if (rating >= 7) return 'Tốt';
        if (rating >= 6) return 'Khá tốt';
        return 'Trung bình';
    }

    function escapeHtml(str){
        return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]));
    }
})();


