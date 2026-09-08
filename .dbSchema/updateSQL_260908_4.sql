-- 상품 상세 공통 헤더/푸터 설정
CREATE TABLE IF NOT EXISTS shop_product_page_settings (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
    header_html LONGTEXT NULL,
    footer_html LONGTEXT NULL,
    header_image VARCHAR(500) NULL,
    footer_image VARCHAR(500) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO shop_product_page_settings (id, header_html, footer_html, header_image, footer_image, created_at, updated_at)
VALUES (1, NULL, NULL, NULL, NULL, NOW(), NOW());
