CREATE TABLE IF NOT EXISTS qr_coupon_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_no SMALLINT UNSIGNED NOT NULL,
    category_slug VARCHAR(100) NOT NULL,
    category_name VARCHAR(150) NOT NULL,
    sheets_per_pack INT UNSIGNED NOT NULL,
    coupon_page_url VARCHAR(500) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    KEY idx_qr_coupon_batches_group (group_no),
    KEY idx_qr_coupon_batches_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS qr_coupon_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id BIGINT UNSIGNED NOT NULL,
    group_no SMALLINT UNSIGNED NOT NULL,
    category_slug VARCHAR(100) NOT NULL,
    sheets_per_pack INT UNSIGNED NOT NULL,
    code VARCHAR(40) NOT NULL,
    coupon_page_url VARCHAR(500) NOT NULL,
    status ENUM('unused','used','disabled') NOT NULL DEFAULT 'unused',
    used_at DATETIME NULL,
    used_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uk_qr_coupon_codes_code (code),
    KEY idx_qr_coupon_codes_group (group_no),
    KEY idx_qr_coupon_codes_batch (batch_id),
    KEY idx_qr_coupon_codes_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
