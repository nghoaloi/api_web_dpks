-- Kiểm tra và thêm cột arrival_time vào bảng bookings nếu chưa tồn tại
-- Nếu cột thoi_gian_den_du_kien đã tồn tại, có thể đổi tên hoặc thêm cột mới

-- Option 1: Đổi tên cột từ thoi_gian_den_du_kien sang arrival_time (nếu chưa có arrival_time)
-- ALTER TABLE `bookings` CHANGE COLUMN `thoi_gian_den_du_kien` `arrival_time` TIME NULL DEFAULT NULL COMMENT 'Thời gian đến dự kiến';

-- Option 2: Thêm cột arrival_time mới (nếu cả 2 cột đều cần)
-- ALTER TABLE `bookings` 
-- ADD COLUMN `arrival_time` TIME NULL DEFAULT NULL COMMENT 'Thời gian đến dự kiến' 
-- AFTER `special_requests`;

-- Option 3: Nếu chưa có cả 2 cột, thêm arrival_time
-- ALTER TABLE `bookings` 
-- ADD COLUMN IF NOT EXISTS `arrival_time` TIME NULL DEFAULT NULL COMMENT 'Thời gian đến dự kiến' 
-- AFTER `special_requests`;

-- Kiểm tra cấu trúc bảng
-- DESCRIBE `bookings`;

