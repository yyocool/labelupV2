CREATE TABLE IF NOT EXISTS shop_product_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    author_name VARCHAR(100) NOT NULL DEFAULT '',
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
    content MEDIUMTEXT NOT NULL,
    review_type ENUM('text','photo') NOT NULL DEFAULT 'text',
    photo_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    thumb_url VARCHAR(255) NULL,
    status ENUM('pending','approved','hidden') NOT NULL DEFAULT 'approved',
    is_admin_created TINYINT(1) NOT NULL DEFAULT 0,
    is_purchased TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_shop_product_reviews_product (product_id, status, created_at),
    KEY idx_shop_product_reviews_user (user_id),
    KEY idx_shop_product_reviews_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO site_settings (setting_key, setting_value, updated_at)
SELECT 'shop_review_approval', 'auto', NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = 'shop_review_approval');
