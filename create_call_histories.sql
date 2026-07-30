-- Create call_histories table for Dialer feature
CREATE TABLE `call_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `caller_id` varchar(255) NOT NULL,
  `callee_number` varchar(255) NOT NULL,
  `direction` varchar(255) NOT NULL COMMENT 'inbound or outbound',
  `status` varchar(255) NOT NULL COMMENT 'pending, ringing, connected, completed, failed',
  `route_id` bigint unsigned NULL,
  `duration` int NULL COMMENT 'Duration in seconds',
  `start_time` timestamp NULL,
  `end_time` timestamp NULL,
  `recording_url` varchar(255) NULL,
  `notes` longtext NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  KEY `idx_call_histories_direction` (`direction`),
  KEY `idx_call_histories_status` (`status`),
  KEY `idx_call_histories_route_id` (`route_id`),
  KEY `idx_call_histories_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Record migration in migrations table
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_07_27_163830_create_call_histories_table', 1)
ON DUPLICATE KEY UPDATE `batch`=VALUES(`batch`);
