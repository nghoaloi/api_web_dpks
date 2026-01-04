(function () {
    if (typeof API_BASE === 'undefined') {
        window.API_BASE = 'http://localhost:8000/api/';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            alert('Vui lòng đăng nhập để xem voucher');
            window.location.href = 'login.html';
            return;
        }

        loadVouchers();
    });

    async function loadVouchers() {
        const loadingEl = document.getElementById('vouchers-loading');
        const emptyEl = document.getElementById('vouchers-empty');
        const listEl = document.getElementById('vouchers-list');

        if (!loadingEl || !emptyEl || !listEl) return;

        loadingEl.style.display = 'flex';
        emptyEl.style.display = 'none';
        listEl.style.display = 'none';
        listEl.innerHTML = '';

        try {
            const token = localStorage.getItem('auth_token');
            const res = await fetch(API_BASE + 'my-vouchers', {
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                }
            });

            const json = await res.json();
            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Không thể tải danh sách voucher');
            }

            const vouchers = json.data || [];
            if (vouchers.length === 0) {
                loadingEl.style.display = 'none';
                emptyEl.style.display = 'flex';
                return;
            }

            const html = vouchers.map(renderVoucherCard).join('');
            listEl.innerHTML = html;

            loadingEl.style.display = 'none';
            listEl.style.display = 'grid';
        } catch (e) {
            console.error('Load vouchers failed', e);
            alert(e.message || 'Không thể tải danh sách voucher');
            loadingEl.style.display = 'none';
        }
    }

    function renderVoucherCard(item) {
        const voucher = item.voucher || {};
        const code = item.code || voucher.code || 'VOUCHER';
        const isUsed = !!item.is_used;
        const isExpired = !!item.is_expired;
        const statusLabel = isUsed ? 'Đã sử dụng' : (isExpired ? 'Hết hạn' : 'Có thể dùng');
        const badgeClass = isUsed ? 'used' : (isExpired ? 'expired' : 'active');

        let benefitText = '';
        if (voucher.type === 'percent') {
            benefitText = `Giảm ${voucher.value || 0}%`;
            if (voucher.max_discount_amount) {
                benefitText += ` (tối đa ${formatCurrency(voucher.max_discount_amount)}đ)`;
            }
        } else {
            benefitText = `Giảm ${formatCurrency(voucher.value || 0)}đ`;
        }

        if (voucher.min_order_amount) {
            benefitText += ` cho đơn từ ${formatCurrency(voucher.min_order_amount)}đ`;
        }

        const expiredAt = item.expired_at;
        const expireText = expiredAt
            ? `Hết hạn: ${formatDate(expiredAt)}`
            : (voucher.end_date ? `Hết hạn: ${formatDate(voucher.end_date)}` : 'Không thời hạn cụ thể');

        const sourceText = item.source === 'reward_review'
            ? 'Thưởng khi đánh giá'
            : (item.source || 'Khác');

        return `
            <article class="voucher-card">
                <div class="voucher-main">
                    <span class="voucher-code">${escapeHtml(code)}</span>
                    <span class="voucher-badge ${badgeClass}">${statusLabel}</span>
                </div>
                <p class="voucher-desc">${escapeHtml(voucher.description || benefitText)}</p>
                <div class="voucher-meta">
                    <span><i class="fa fa-clock"></i>${expireText}</span>
                    <span><i class="fa fa-gift"></i>${escapeHtml(sourceText)}</span>
                </div>
            </article>
        `;
    }

    function formatDate(dateString) {
        if (!dateString) return '';
        try {
            return new Date(dateString).toLocaleDateString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        } catch {
            return dateString;
        }
    }

    function formatCurrency(value) {
        return Number(value || 0).toLocaleString('vi-VN');
    }

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', '\'': '&#39;' }[m]));
    }
})();


   


