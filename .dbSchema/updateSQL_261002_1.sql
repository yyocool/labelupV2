-- 261002_1 : 상품 옵션(단일 선택형) 도입
-- 적용 대상: dev/database/migrations/055_shop_product_options.sql 와 동일

CREATE TABLE IF NOT EXISTS shop_product_options (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    price_delta INT NOT NULL DEFAULT 0 COMMENT '기준가 대비 증감액(원). 음수 허용',
    stock_qty INT NOT NULL DEFAULT 0,
    sku_suffix VARCHAR(40) NULL COMMENT '주문 항목 SKU에 덧붙일 접미사',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_shop_product_options_product (product_id, sort_order),
    KEY idx_shop_product_options_active (product_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE shop_order_items
    ADD COLUMN option_id BIGINT UNSIGNED NULL AFTER product_id,
    ADD COLUMN option_name VARCHAR(120) NULL AFTER sku,
    ADD COLUMN option_price_delta INT NOT NULL DEFAULT 0 AFTER option_name;
