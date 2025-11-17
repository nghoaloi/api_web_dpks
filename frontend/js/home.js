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

        fetchRoomTypes();
        personalizeHero();
    });

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


