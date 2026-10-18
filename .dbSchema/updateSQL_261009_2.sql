CREATE TABLE IF NOT EXISTS error_report_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    path VARCHAR(255) NOT NULL,
    created_at DATETIME NULL,
    KEY idx_error_report_images_report (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
