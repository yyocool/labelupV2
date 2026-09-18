-- LabelUp 2026-09-16 #1
-- Category-specific product detail header/footer settings

CREATE TABLE IF NOT EXISTS shop_product_page_category_settings (
    category_id BIGINT UNSIGNED NOT NULL,
    header_html LONGTEXT NULL,
    footer_html LONGTEXT NULL,
    header_image VARCHAR(500) NULL,
    footer_image VARCHAR(500) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
