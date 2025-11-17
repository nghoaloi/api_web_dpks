-- Thêm cột special_requests vào bảng bookings
ALTER TABLE `bookings` 
ADD COLUMN `special_requests` TEXT NULL DEFAULT NULL COMMENT 'Yêu cầu đặc biệt của khách hàng' 
AFTER `thoi_gian_den_du_kien`;

