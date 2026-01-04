(() => {
    const DETAIL_API_BASE = (typeof window !== 'undefined' && window.API_BASE)
        ? window.API_BASE
        : 'http://localhost:8000/api/';

    const dom = {
        loading: document.getElementById('detail-loading'),
        error: document.getElementById('detail-error'),
        errorMessage: document.getElementById('detail-error-message'),
        content: document.getElementById('detail-content')
    };

    document.addEventListener('DOMContentLoaded', () => {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            window.location.href = 'login.html';
            return;
        }

        const params = new URLSearchParams(window.location.search);
        const bookingId = params.get('booking_id');
        if (!bookingId) {
            showError('Không tìm thấy mã đơn đặt phòng.');
            return;
        }

        loadDetail(token, bookingId);
    });

    async function loadDetail(token, bookingId) {
        try {
            setLoading(true);
            const res = await fetch(`${DETAIL_API_BASE}bookings/${bookingId}`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (res.status === 401) {
                window.location.href = 'login.html';
                return;
            }

            if (!res.ok) {
                throw new Error('Không tải được thông tin đặt phòng.');
            }

            const payload = await res.json();
            renderDetail(payload);
        } catch (error) {
            console.error('Detail error:', error);
            showError(error.message || 'Không thể tải dữ liệu');
        } finally {
            setLoading(false);
        }
    }

    function renderDetail(payload) {
        if (!payload || !payload.data) {
            showError('Không tìm thấy đơn đặt phòng.');
            return;
        }

        if (dom.error) {
            dom.error.style.display = 'none';
        }

        const booking = payload.data;
        const relatedBookings = payload.bookings || [];
        const services = payload.services || [];

        const bookingCode = payload.booking_code || formatBookingCode(booking.id);
        const status = normalizeStatus(booking.status);
        const statusClass = statusClassName(status);
        const statusNote = statusMessage(status);

        const roomType =
            booking.room?.room_type?.name ||
            booking.room?.roomType?.name ||
            'Loại phòng';

        const paymentType =
            payload.payment_type ||
            booking.room?.room_type?.payment_type ||
            booking.room?.roomType?.payment_type ||
            'Không cần thanh toán trước';

        const checkIn = booking.check_in;
        const checkOut = booking.check_out;
        const nights = calculateNights(checkIn, checkOut);
        const roomCount = Math.max(relatedBookings.length, 1);

        const arrivalTime = booking.arrival_time || '—';
        const specialRequests = booking.special_requests
            ? escapeHtml(booking.special_requests)
            : 'Không có';

        const roomTotalPrice = payload.room_total_price ?? booking.total_price ?? 0;
        const servicesTotalPrice = payload.services_total_price ?? 0;
        const totalPrice = payload.total_price ?? (roomTotalPrice + servicesTotalPrice);
        const paymentAmount = payload.payment_amount;
        const remainingAmount = payload.remaining_amount;
        const needPayment = Boolean(payload.need_payment);
        const paymentStatus = booking.payment?.status
            || (needPayment ? 'Chờ thanh toán' : 'Thanh toán tại khách sạn');

        const roomsHtml = buildRoomsHtml(relatedBookings, roomType);
        const servicesHtml = buildServicesHtml(services);

        const paymentRows = [
            infoRow('Phương thức', paymentType),
            infoRow('Trạng thái', paymentStatus),
            infoRow('Tổng tiền phòng', `${formatCurrency(roomTotalPrice)} VNĐ`),
            infoRow('Tổng tiền dịch vụ', `${formatCurrency(servicesTotalPrice)} VNĐ`),
            infoRow('Tổng thanh toán', `<span class="text-strong">${formatCurrency(totalPrice)} VNĐ</span>`, true),
        ];

        if (needPayment && paymentAmount != null) {
            paymentRows.push(infoRow('Đã thanh toán', `${formatCurrency(paymentAmount)} VNĐ`));
        }

        if (needPayment && remainingAmount != null) {
            paymentRows.push(infoRow('Còn lại', `${formatCurrency(remainingAmount)} VNĐ`));
        }

        const summaryHtml = `
            <section class="detail-summary">
                <div>
                    <p class="eyebrow">Mã đặt phòng</p>
                    <h1>${bookingCode}</h1>
                    <p class="meta">
                        ${formatDateRange(checkIn, checkOut)} • ${nights} đêm • ${roomCount} phòng
                    </p>
                </div>
                <div class="summary-right">
                    <span class="status-chip ${statusClass}">
                        <i class="fas ${statusIcon(status)}"></i>
                        ${status}
                    </span>
                    ${statusNote ? `<p class="meta">${statusNote}</p>` : ''}
                    ${needPayment && status !== 'Đã thanh toán'
                        ? `<a class="btn-pay" href="payment.html?booking_id=${booking.id}">Thanh toán ngay</a>`
                        : ''}
                </div>
            </section>
        `;

        const cardsHtml = `
            <section class="detail-grid">
                <article class="detail-card">
                    <h3>Thông tin lưu trú</h3>
                    ${infoRow('Loại phòng', roomType)}
                    ${infoRow('Nhận phòng', formatDateTime(checkIn))}
                    ${infoRow('Trả phòng', formatDateTime(checkOut))}
                    ${infoRow('Giờ đến dự kiến', arrivalTime)}
                    ${infoRow('Yêu cầu đặc biệt', specialRequests, true)}
                    <p class="section-title">Phòng đã đặt</p>
                    ${roomsHtml}
                </article>

                <article class="detail-card">
                    <h3>Thanh toán</h3>
                    ${paymentRows.join('')}
                </article>

                <article class="detail-card detail-wide">
                    <h3>Dịch vụ đi kèm</h3>
                    ${servicesHtml}
                </article>
            </section>

            <div class="detail-footer">
                <div>
                    <p class="muted">Tổng thanh toán</p>
                    <p class="total">${formatCurrency(totalPrice)} VNĐ</p>
                </div>
                <div class="detail-actions">
                    <a class="btn-secondary" href="history.html">Quay lại lịch sử</a>
                    ${needPayment && status !== 'Đã thanh toán'
                        ? `<a class="btn-pay" href="payment.html?booking_id=${booking.id}">Thanh toán ngay</a>`
                        : ''}
                </div>
            </div>
        `;

        dom.content.innerHTML = summaryHtml + cardsHtml;
        dom.content.style.display = 'block';
    }

    function showError(message) {
        if (!dom.error || !dom.errorMessage) return;
        dom.errorMessage.textContent = message || 'Đã xảy ra lỗi.';
        dom.error.style.display = 'flex';
        if (dom.content) {
            dom.content.style.display = 'none';
        }
    }

    function setLoading(isLoading) {
        if (dom.loading) {
            dom.loading.style.display = isLoading ? 'flex' : 'none';
        }
    }

    function infoRow(label, value, isRaw = false) {
        const safeLabel = escapeHtml(label);
        const safeValue = isRaw ? value : escapeHtml(value ?? '—');
        return `
            <div class="info-row">
                <span>${safeLabel}</span>
                <p>${safeValue || '—'}</p>
            </div>
        `;
    }

    function buildRoomsHtml(bookings, fallbackRoomType) {
        if (!bookings.length) {
            return `<p class="empty-note">Hệ thống đang gán phòng. Chúng tôi sẽ thông báo sớm nhất.</p>`;
        }

        const items = bookings.map((b, index) => {
            const roomNumber = b.room?.room_number || `Phòng #${index + 1}`;
            const type = b.room?.room_type?.name || b.room?.roomType?.name || fallbackRoomType;
            const room = b.room;
            
            // Thông tin chi tiết phòng
            const details = [];
            if (room?.floor) details.push(`Lầu: ${escapeHtml(room.floor)}`);
            if (room?.direction) details.push(`Hướng: ${escapeHtml(room.direction)}`);
            if (room?.area) details.push(`Diện tích: ${Number(room.area).toLocaleString('vi-VN')} m²`);
            if (room?.has_balcony !== undefined && room?.has_balcony !== null) {
                details.push(room.has_balcony ? 'Có ban công' : 'Không có ban công');
            }
            
            const detailsText = details.length > 0 ? `<br><small style="color: #6b7b8c; font-size: 12px;">${details.join(' • ')}</small>` : '';
            
            return `
                <li>
                    <span>${escapeHtml(roomNumber)}${detailsText}</span>
                    <small>${escapeHtml(type)}</small>
                </li>
            `;
        }).join('');

        return `<ul class="detail-list">${items}</ul>`;
    }

    function buildServicesHtml(services) {
        if (!services || !services.length) {
            return `<p class="empty-note">Không sử dụng dịch vụ bổ sung.</p>`;
        }

        const rows = services.map(service => `
            <tr>
                <td>${escapeHtml(service.service_name || 'Dịch vụ')}</td>
                <td>${service.quantity || 1}</td>
                <td>${formatCurrency(service.price)} VNĐ</td>
                <td>${formatCurrency(service.total)} VNĐ</td>
            </tr>
        `).join('');

        return `
            <div class="detail-table-wrapper">
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th>Dịch vụ</th>
                            <th>Số lượng</th>
                            <th>Đơn giá</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
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

    function statusMessage(status) {
        switch (status) {
            case 'Đã thanh toán':
                return 'Đơn đặt phòng đã được xác nhận hoàn tất.';
            case 'Đã hủy':
                return 'Đơn đặt phòng này đã bị hủy. Vui lòng liên hệ để được hỗ trợ.';
            default:
                return 'Chúng tôi đang xác nhận đơn đặt phòng của bạn.';
        }
    }

    function formatBookingCode(id) {
        return 'BK' + String(id || 0).padStart(6, '0');
    }

    function formatDate(dateString) {
        if (!dateString) return '—';
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

    function formatDateTime(dateString) {
        if (!dateString) return '—';
        try {
            return new Date(dateString).toLocaleString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch {
            return dateString;
        }
    }

    function formatDateRange(start, end) {
        const startDate = formatDate(start);
        const endDate = formatDate(end);
        return `${startDate} - ${endDate}`;
    }

    function calculateNights(checkIn, checkOut) {
        const start = new Date(checkIn);
        const end = new Date(checkOut);
        if (isNaN(start) || isNaN(end)) return 1;
        const diff = Math.round((end - start) / (1000 * 60 * 60 * 24));
        return diff > 0 ? diff : 1;
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('vi-VN').format(value || 0);
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, m => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[m]));
    }
})();

