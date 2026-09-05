CREATE TABLE IF NOT EXISTS shop_product_detail_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    title VARCHAR(200) NULL,
    html_content LONGTEXT NULL,
    generated_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_shop_product_detail_pages_product (product_id),
    KEY idx_shop_product_detail_pages_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
