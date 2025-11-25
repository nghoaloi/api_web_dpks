(() => {
    const HISTORY_API_BASE = (typeof window !== 'undefined' && window.API_BASE)
        ? window.API_BASE
        : 'http://localhost:8000/api/';

    const dom = {
        loading: document.getElementById('history-loading'),
        empty: document.getElementById('history-empty'),
        list: document.getElementById('history-list'),
        filter: document.getElementById('status-filter')
    };

    let allBookings = [];

    document.addEventListener('DOMContentLoaded', () => {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            window.location.href = 'login.html';
            return;
        }

        loadHistory(token);
        dom.filter?.addEventListener('change', () => renderList());
    });

    async function loadHistory(token) {
        try {
            dom.loading.style.display = 'flex';
            dom.empty.style.display = 'none';
            dom.list.innerHTML = '';

            const res = await fetch(`${HISTORY_API_BASE}bookings`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) {
                throw new Error('Không tải được lịch sử đặt phòng');
            }

            const json = await res.json();
            allBookings = Array.isArray(json.data) ? json.data : [];

            renderList();
        } catch (error) {
            console.error('Load history error:', error);
            dom.empty.querySelector('p').textContent = error.message || 'Không thể tải dữ liệu';
            dom.empty.style.display = 'block';
        } finally {
            dom.loading.style.display = 'none';
        }
    }

    function renderList() {
        if (!dom.list) return;

        const filter = dom.filter?.value || 'all';
        const list = filter === 'all'
            ? allBookings
            : allBookings.filter(b => normalizeStatus(b.status) === filter);

        if (!list.length) {
            dom.list.innerHTML = '';
            dom.empty.style.display = 'block';
            return;
        }

        dom.empty.style.display = 'none';
        dom.list.innerHTML = list.map(booking => createCard(booking)).join('');
    }

    function createCard(booking) {
        const bookingCode = formatBookingCode(booking.id);
        const status = normalizeStatus(booking.status);
        const roomType = booking.room?.room_type?.name || booking.room?.roomType?.name || 'Loại phòng';
        const checkIn = formatDate(booking.check_in);
        const checkOut = formatDate(booking.check_out);
        const serviceTotal = Array.isArray(booking.services)
            ? booking.services.reduce((sum, service) => {
                const price = Number(service.price) || 0;
                const quantity = Number(service.quantity) || 1;
                return sum + price * quantity;
            }, 0)
            : 0;

        const paymentTotal = booking.payment?.total_amount;
        const totalPrice = paymentTotal != null
            ? paymentTotal
            : (Number(booking.total_price) || 0) + serviceTotal;

        const paymentType = booking.room?.room_type?.payment_type || booking.room?.roomType?.payment_type || '';

        const statusClass = statusClassName(status);
        const showPayButton = status !== 'Đã thanh toán' && paymentType && paymentType !== 'Không cần thanh toán trước';

        return `
            <article class="history-card ${statusClass}">
                <div class="card-top">
                    <p class="booking-code">${bookingCode}</p>
                    <span class="status-chip ${statusClass}">
                        <i class="fas ${statusIcon(status)}"></i>
                        ${status}
                    </span>
                </div>
                <div class="card-body">
                    <div class="info-block">
                        <span class="label">Loại phòng</span>
                        <span class="value">${escapeHtml(roomType)}</span>
                    </div>
                    <div class="info-block">
                        <span class="label">Nhận phòng</span>
                        <span class="value">${checkIn}</span>
                    </div>
                    <div class="info-block">
                        <span class="label">Trả phòng</span>
                        <span class="value">${checkOut}</span>
                    </div>
                    <div class="info-block">
                        <span class="label">Phương thức</span>
                        <span class="value">${paymentType || '—'}</span>
                    </div>
                    <div class="info-block">
                        <span class="label">Số tiền</span>
                        <span class="value">${formatCurrency(totalPrice)} VNĐ</span>
                    </div>
                </div>
                <div class="card-actions">
                    <a class="btn-secondary" href="history-detail.html?booking_id=${booking.id}">
                        Xem chi tiết
                    </a>
                    ${showPayButton ? `<a class="btn-pay" href="payment.html?booking_id=${booking.id}">Thanh toán</a>` : ''}
                </div>
            </article>
        `;
    }

    function normalizeStatus(status) {
        return status ? status.trim() : 'Chờ xử lý';
    }

    function statusClassName(status) {
        switch (status) {
            case 'Đã thanh toán':
                return 'paid';
            case 'Đã hủy':
                return 'cancelled';
            default:
                return 'pending';
        }
    }

    function statusIcon(status) {
        switch (status) {
            case 'Đã thanh toán':
                return 'fa-check-circle';
            case 'Đã hủy':
                return 'fa-times-circle';
            default:
                return 'fa-clock';
        }
    }

    function formatBookingCode(id) {
        return 'BK' + String(id).padStart(6, '0');
    }

    function formatDate(dateString) {
        if (!dateString) return '—';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        } catch {
            return dateString;
        }
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount || 0);
    }

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, m => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[m]));
    }
})();

