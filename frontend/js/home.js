(function(){
    if (typeof API_BASE === 'undefined') {
        window.API_BASE = 'http://localhost:8000/api/';
    }

    const state = {
        rooms: [],
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

        fetchRooms();
        personalizeHero();
    });

    async function fetchRooms(){
        try{
            const res = await fetch(API_BASE + 'rooms');
            const json = await res.json();
            if (json && json.data) {
                state.rooms = Array.isArray(json.data) ? json.data : [];
            } else if (Array.isArray(json)) {
                state.rooms = json;
            } else {
                state.rooms = [];
            }
            
            renderRooms();
        } catch(e){
            console.error('Fetch rooms error', e);
            state.rooms = [];
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
        const filtered = state.rooms.filter(r => {
            const rt = r.room_type || r.roomType || {};
            const name = String(rt.name || '').toLowerCase();
            const number = String(r.room_number || '').toLowerCase();
            const keywordOk = !state.keyword || name.includes(state.keyword) || number.includes(state.keyword);
            const guestsOk = !state.guests || (parseInt(rt.max_cap || 0) >= parseInt(state.guests));
            return keywordOk && guestsOk;
        });

        grid.innerHTML = filtered.map(room => cardHtml(room)).join('');
    }

    function cardHtml(room){
        const rt = room.room_type || room.roomType || {};
        const price = (rt.base_price ?? undefined) !== undefined ? Number(rt.base_price).toLocaleString('vi-VN') : '—';
        const statusClass = room.status === 'Còn phòng' ? 'available' : (room.status === 'Đã có người' ? 'occupied' : 'maintenance');
        const statusText = room.status || '—';
        const title = rt.name ? `${rt.name} • #${room.room_number}` : `Phòng #${room.room_number || ''}`;
        const href = 'page/room.html?id=' + encodeURIComponent(room.id);
        

        let imageUrl = 'images/background.jpg'; 
        const images = rt.images || [];
        
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
          +       '<div class="room-meta">Tối đa: '+ ((rt.max_cap ?? '—')) +' người</div>'
          +   '</div>'
          +   '<div class="room-footer">'
          +       '<div class="price">'+ (price !== '—' ? (price + ' đ') : 'Liên hệ') +'</div>'
          +       '<div class="status '+statusClass+'">'+ escapeHtml(statusText) +'</div>'
          +   '</div>'
          + '</a>'
        );
    }

    function escapeHtml(str){
        return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]));
    }
})();


