-- ============================================================
-- Migration: جدول work_logs
-- این جدول توسط public/log_work_time.php و public/work_reports.php استفاده می‌شود
-- و در اسکیمای قبلی وجود نداشت.
-- اجرا: این فایل را در phpMyAdmin روی دیتابیس kar_db اجرا کنید.
-- ============================================================

CREATE TABLE IF NOT EXISTS `work_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `worker_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `status` enum('active','completed') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `worker_id` (`worker_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `work_logs_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
