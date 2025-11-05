(function(){
    if (typeof API_BASE === 'undefined') {
        window.API_BASE = 'http://localhost:8000/api/';
    }

    let currentImages = [];
    let currentMainIndex = 0;

    document.addEventListener('DOMContentLoaded', () => {
        const id = new URLSearchParams(location.search).get('id');
        if (!id) {
            document.querySelector('.room-container').innerHTML = '<div style="text-align:center;padding:40px;">Không tìm thấy thông tin loại phòng</div>';
            return;
        }
        fetchRoomType(id);
        
        const btnBook = document.getElementById('btn-book');
        if (btnBook) {
            btnBook.addEventListener('click', () => {
                const token = localStorage.getItem('auth_token');
                if (!token) {
                    alert('Vui lòng đăng nhập để đặt phòng');
                    window.location.href = 'login.html';
                    return;
                }
                // TODO: Navigate to booking page with room type id
                alert('Chức năng đặt phòng đang được phát triển');
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
        // Title
        document.getElementById('room-title').textContent = roomType.name || 'Loại phòng';
        
        // Ẩn số phòng và status vì đây là loại phòng, không phải phòng cụ thể
        const roomNumberEl = document.getElementById('room-number');
        if (roomNumberEl) roomNumberEl.style.display = 'none';
        
        const statusEl = document.getElementById('room-status');
        if (statusEl) statusEl.style.display = 'none';
        
        // Price
        const price = (roomType.base_price ?? undefined) !== undefined ? Number(roomType.base_price).toLocaleString('vi-VN') : '—';
        document.getElementById('room-price').textContent = price;
        
        //Số phòng trống
        const availableCount = roomType.available_rooms_count ?? 0;
        const availabilityEl = document.getElementById('room-availability');
        if (availabilityEl) {
            availabilityEl.innerHTML = `<div class="availability-simple">Còn <strong>${availableCount}</strong> phòng trống</div>`;
        }
        
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
        
        // Disable book button if no available rooms
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

    function renderGallery(images){
        currentImages = [];
        
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
                return url || '../images/background.jpg';
            });
        } else {
            currentImages = ['../images/background.jpg'];
        }
        
        // Main image
        const mainImg = document.getElementById('main-image');
        if (mainImg && currentImages[0]) {
            mainImg.src = currentImages[0];
        }
        

        const thumbsContainer = document.getElementById('gallery-thumbs');
        if (thumbsContainer) {
            if (currentImages.length > 1) {
                const thumbs = currentImages.slice(1, 5).map((url, idx) => `
                    <div class="gallery-thumb ${idx === 0 ? 'active' : ''}" data-index="${idx + 1}">
                        <img src="${escapeHtml(url)}" alt="room ${idx + 2}">
                    </div>
                `).join('');
                thumbsContainer.innerHTML = thumbs;
                
                // Thumb click handlers
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
                document.querySelector('.gallery-main').style.gridColumn = '1 / -1';
            }
        }
    }

    function escapeHtml(str){
        return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]));
    }
})();


