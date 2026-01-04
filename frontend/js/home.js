(function(){
    if (typeof API_BASE === 'undefined') {
        window.API_BASE = 'http://localhost:8000/api/';
    }

    const state = {
        roomTypes: [],
        keyword: '',
        guests: '',
        checkIn: '',
        checkOut: ''
    };

    document.addEventListener('DOMContentLoaded', () => {
        const searchBtn = document.getElementById('btn-search');
        if (searchBtn) searchBtn.addEventListener('click', onSearch);

        ['keyword','guests','checkin','checkout'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', onFilterChange);
        });

        // Contact modal handlers - đợi header load xong
        setTimeout(() => {
            initContactModal();
        }, 500);

        // Hoặc dùng event delegation để đảm bảo hoạt động
        document.addEventListener('click', function(e) {
            if (e.target && (e.target.id === 'contact-btn' || e.target.closest('#contact-btn'))) {
                e.preventDefault();
                const contactModal = document.getElementById('contact-modal');
                if (contactModal) {
                    contactModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
            }
            if (e.target && (e.target.id === 'mobile-contact-btn' || e.target.closest('#mobile-contact-btn'))) {
                e.preventDefault();
                const contactModal = document.getElementById('contact-modal');
                if (contactModal) {
                    contactModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    // Close mobile menu if open
                    const mobileMenu = document.getElementById('mobile-menu');
                    if (mobileMenu) {
                        mobileMenu.classList.remove('active');
                    }
                }
            }
        });

        fetchRoomTypes();
        personalizeHero();
    });

    function initContactModal() {
        const contactModal = document.getElementById('contact-modal');
        const contactModalClose = document.getElementById('contact-modal-close');
        const contactModalOverlay = contactModal?.querySelector('.contact-modal-overlay');
        const contactForm = document.getElementById('contact-form');

        // Close modal function
        function closeModal() {
            if (contactModal) {
                contactModal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        if (contactModalClose) {
            contactModalClose.addEventListener('click', closeModal);
        }

        if (contactModalOverlay) {
            contactModalOverlay.addEventListener('click', closeModal);
        }

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && contactModal && contactModal.style.display === 'flex') {
                closeModal();
            }
        });

        // Contact form submit
        if (contactForm) {
            contactForm.addEventListener('submit', handleContactSubmit);
        }
    }

    async function fetchRoomTypes(){
        try{
            const res = await fetch(API_BASE + 'room-types');
            const json = await res.json();
            if (json && json.data) {
                state.roomTypes = Array.isArray(json.data) ? json.data : [];
            } else if (Array.isArray(json)) {
                state.roomTypes = json;
            } else {
                state.roomTypes = [];
            }
            
            renderRooms();
        } catch(e){
            console.error('Fetch room types error', e);
            state.roomTypes = [];
            renderRooms();
        }
    }

    function personalizeHero(){
        try{
            const el = document.getElementById('hero-name');
            if (!el) return;
            const user = JSON.parse(localStorage.getItem('userData') || '{}');
            const fullname = user.fullname || '';
            const firstName = fullname ? String(fullname).trim().split(/\s+/).slice(-1)[0] : 'Bạn';
            el.textContent = firstName || 'Bạn';
        }catch(_){
      
        }
    }

    function onFilterChange(){
        state.keyword = (document.getElementById('keyword')?.value || '').trim().toLowerCase();
        state.guests = document.getElementById('guests')?.value || '';
        state.checkIn = document.getElementById('checkin')?.value || '';
        state.checkOut = document.getElementById('checkout')?.value || '';
    }

    function onSearch(){
        onFilterChange();
        renderRooms();
    }

    async function handleContactSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = {
            name: document.getElementById('contact-name').value.trim(),
            email: document.getElementById('contact-email').value.trim(),
            phone: document.getElementById('contact-phone').value.trim(),
            subject: document.getElementById('contact-subject').value,
            message: document.getElementById('contact-message').value.trim()
        };

        // Validation
        if (!formData.name || !formData.email || !formData.subject || !formData.message) {
            alert('Vui lòng điền đầy đủ các trường bắt buộc');
            return;
        }

        if (!formData.email.includes('@')) {
            alert('Email không hợp lệ');
            return;
        }

        const submitBtn = form.querySelector('.btn-submit');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';

        try {
            const response = await fetch(API_BASE + 'contact', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                alert(result.message || 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất có thể.');
                form.reset();
                
                // Close modal after 1 second
                setTimeout(() => {
                    const contactModal = document.getElementById('contact-modal');
                    if (contactModal) {
                        contactModal.style.display = 'none';
                        document.body.style.overflow = '';
                    }
                }, 1000);
            } else {
                const errorMsg = result.errors 
                    ? Object.values(result.errors).flat().join('\n')
                    : (result.message || 'Có lỗi xảy ra. Vui lòng thử lại sau.');
                alert(errorMsg);
            }
        } catch (error) {
            console.error('Contact form error:', error);
            alert('Có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại sau.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    function renderRooms(){
        const grid = document.getElementById('rooms-grid');
        if (!grid) return;
        const filtered = state.roomTypes.filter(rt => {
            const name = String(rt.name || '').toLowerCase();
            const keywordOk = !state.keyword || name.includes(state.keyword);
            const guestsOk = !state.guests || (parseInt(rt.max_cap || 0) >= parseInt(state.guests));
            return keywordOk && guestsOk;
        });

        grid.innerHTML = filtered.map(roomType => cardHtml(roomType)).join('');
    }

    function cardHtml(roomType){
        const price = (roomType.base_price ?? undefined) !== undefined ? Number(roomType.base_price).toLocaleString('vi-VN') : '—';
        const title = roomType.name || 'Loại phòng';
        const href = 'page/room.html?id=' + encodeURIComponent(roomType.id);

        let imageUrl = 'images/background.jpg'; 
        const images = roomType.images || [];
        
        if (images && Array.isArray(images) && images.length > 0) {
            const firstImage = images[0];
            let imgPath = firstImage.image_url || firstImage.imageUrl || firstImage.url || '';
            
            if (imgPath) {
                if (!imgPath.startsWith('http')) {
                    if (imgPath.startsWith('/storage/')) {
                        imageUrl = 'http://localhost:8000' + imgPath;
                    } else if (imgPath.startsWith('storage/')) {
                        imageUrl = 'http://localhost:8000/' + imgPath;
                    } else {
                        imageUrl = 'http://localhost:8000/storage/' + imgPath;
                    }
                } else {
                    imageUrl = imgPath;
                }
            }
        }
        
        return (
            '<a class="room-card" href="'+ href +'">'
          +   '<img class="room-thumb" src="'+ escapeHtml(imageUrl) +'" alt="room" />'
          +   '<div class="room-body">'
          +       '<div class="room-title">'+ escapeHtml(title) +'</div>'
          +       '<div class="room-meta">Tối đa: '+ ((roomType.max_cap ?? '—')) +' người</div>'
          +   '</div>'
          +   '<div class="room-footer">'
          +       '<div class="price">'+ (price !== '—' ? (price + ' đ') : 'Liên hệ') +'</div>'
          +   '</div>'
          + '</a>'
        );
    }

    function escapeHtml(str){
        return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]));
    }
})();


