CREATE TABLE IF NOT EXISTS shop_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_shop_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS label_specs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    width_mm DECIMAL(8,2) NOT NULL,
    height_mm DECIMAL(8,2) NOT NULL,
    material VARCHAR(80) NOT NULL DEFAULT '',
    shape ENUM('rect','round','custom') NOT NULL DEFAULT 'rect',
    labels_per_sheet INT UNSIGNED NULL,
    description VARCHAR(500) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS shop_products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    spec_id BIGINT UNSIGNED NULL,
    name VARCHAR(200) NOT NULL,
    sku VARCHAR(80) NOT NULL,
    price INT UNSIGNED NOT NULL DEFAULT 0,
    sale_price INT UNSIGNED NULL,
    stock_qty INT NOT NULL DEFAULT 0,
    status ENUM('draft','active','soldout','hidden') NOT NULL DEFAULT 'draft',
    thumbnail VARCHAR(255) NULL,
    description TEXT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_shop_products_sku (sku),
    KEY idx_shop_products_category (category_id),
    KEY idx_shop_products_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS shop_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_no VARCHAR(30) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(190) NOT NULL,
    customer_phone VARCHAR(30) NULL,
    status ENUM('pending','paid','preparing','shipping','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
    payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    subtotal INT UNSIGNED NOT NULL DEFAULT 0,
    shipping_fee INT UNSIGNED NOT NULL DEFAULT 0,
    discount_amount INT UNSIGNED NOT NULL DEFAULT 0,
    total_amount INT UNSIGNED NOT NULL DEFAULT 0,
    shipping_name VARCHAR(100) NULL,
    shipping_phone VARCHAR(30) NULL,
    shipping_address VARCHAR(500) NULL,
    shipping_memo VARCHAR(255) NULL,
    carrier VARCHAR(50) NULL,
    tracking_no VARCHAR(80) NULL,
    admin_memo TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_shop_orders_no (order_no),
    KEY idx_shop_orders_status (status),
    KEY idx_shop_orders_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS shop_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    product_name VARCHAR(200) NOT NULL,
    sku VARCHAR(80) NOT NULL,
    qty INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price INT UNSIGNED NOT NULL DEFAULT 0,
    line_total INT UNSIGNED NOT NULL DEFAULT 0,
    KEY idx_shop_order_items_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS shop_coupons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'fixed',
    discount_value INT UNSIGNED NOT NULL DEFAULT 0,
    min_order_amount INT UNSIGNED NOT NULL DEFAULT 0,
    max_uses INT UNSIGNED NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_shop_coupons_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS shop_banners (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    subtitle VARCHAR(255) NULL,
    image_url VARCHAR(255) NULL,
    link_url VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO shop_categories (name, slug, sort_order, is_active, created_at, updated_at) VALUES
('라벨지', 'label-paper', 1, 1, NOW(), NOW()),
('감열지', 'thermal-paper', 2, 1, NOW(), NOW()),
('봉투·포장', 'packaging', 3, 1, NOW(), NOW()),
('프린터 소모품', 'supplies', 4, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO label_specs (name, width_mm, height_mm, material, shape, labels_per_sheet, description, is_active, created_at, updated_at) VALUES
('50x30mm 유포지', 50.00, 30.00, '유포지', 'rect', 40, '식품·화장품 소형 라벨', 1, NOW(), NOW()),
('100x50mm 아트지', 100.00, 50.00, '아트지', 'rect', 21, '배송·상품 라벨', 1, NOW(), NOW()),
('40mm 원형', 40.00, 40.00, 'PP', 'round', 35, '네임스티커·봉인', 1, NOW(), NOW()),
('80x80mm 감열지', 80.00, 80.00, '감열지', 'rect', 12, '물류·택배 라벨', 1, NOW(), NOW()),
('A4 전지 210x297', 210.00, 297.00, '유포지', 'rect', 1, '맞춤 인쇄용', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO shop_products (category_id, spec_id, name, sku, price, sale_price, stock_qty, status, description, sort_order, created_at, updated_at) VALUES
(1, 1, '50x30 유포지 500매', 'LBL-5030-500', 18500, 16900, 120, 'active', 'A4 40칸 · 접착 유포지', 1, NOW(), NOW()),
(1, 2, '100x50 아트지 250매', 'LBL-10050-250', 22000, NULL, 85, 'active', '배송·상품용 고급 아트지', 2, NOW(), NOW()),
(1, 3, '40mm 원형 PP 700매', 'LBL-R40-700', 15800, 14200, 200, 'active', '내수·내유 PP 원형', 3, NOW(), NOW()),
(2, 4, '80x80 감열택배 500매', 'THM-8080-500', 32000, 29500, 45, 'active', '택배·물류 감열 라벨', 4, NOW(), NOW()),
(3, NULL, '라벨업 브랜드 패키징 키트', 'PKG-START-01', 12000, NULL, 30, 'active', '샘플 라벨지 + 포장재 세트', 5, NOW(), NOW()),
(4, 5, 'A4 맞춤 인쇄 100매', 'CUS-A4-100', 45000, NULL, 0, 'soldout', '맞춤 규격 인쇄 의뢰', 6, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO shop_coupons (code, name, discount_type, discount_value, min_order_amount, max_uses, used_count, starts_at, ends_at, is_active, created_at, updated_at) VALUES
('WELCOME10', '신규 가입 10%', 'percent', 10, 10000, 500, 12, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1, NOW(), NOW()),
('LABEL3000', '3,000원 할인', 'fixed', 3000, 30000, 200, 8, '2026-06-01 00:00:00', '2026-09-30 23:59:59', 1, NOW(), NOW()),
('VIP15', 'VIP 15% 할인', 'percent', 15, 50000, 50, 3, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO shop_banners (title, subtitle, image_url, link_url, sort_order, is_active, created_at, updated_at) VALUES
('여름 라벨 기획전', '인기 템플릿 + 라벨지 15% 할인', '/assets/hero-tall-1.webp', '/', 1, 1, NOW(), NOW()),
('신규 회원 웰컴 쿠폰', 'WELCOME10 코드로 10% 할인', '/assets/hero-tall-2.webp', '/register', 2, 1, NOW(), NOW()),
('맞춤 제작 상담', '대량·특수 규격 견적 문의', '/assets/hero-tall-3.webp', '/', 3, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO shop_orders (order_no, user_id, customer_name, customer_email, customer_phone, status, payment_status, subtotal, shipping_fee, discount_amount, total_amount, shipping_name, shipping_phone, shipping_address, shipping_memo, carrier, tracking_no, created_at, updated_at) VALUES
('LU202608270001', 1, '김라벨', 'label.sample1@example.com', '010-1234-5678', 'delivered', 'paid', 33700, 3000, 3000, 33700, '김라벨', '010-1234-5678', '서울특별시 강남구 테헤란로 123 4층', '부재시 문앞', 'CJ대한통운', '123456789012', DATE_SUB(NOW(), INTERVAL 5 DAY), NOW()),
('LU202608270002', NULL, '이스티커', 'sticker@example.com', '010-9876-5432', 'shipping', 'paid', 22000, 3000, 0, 25000, '이스티커', '010-9876-5432', '경기도 성남시 분당구 정자로 45', NULL, '우체국택배', '987654321098', DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
('LU202608270003', NULL, '박택배', 'parcel@example.com', '010-5555-7777', 'preparing', 'paid', 47400, 0, 4740, 42660, '박택배', '010-5555-7777', '부산광역시 해운대구 센텀로 99', '빠른 배송 부탁', NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
('LU202608270004', NULL, '최카페', 'cafe@example.com', '010-2222-3333', 'pending', 'pending', 15800, 3000, 0, 18800, '최카페', '010-2222-3333', '대구광역시 수성구 동대구로 200', NULL, NULL, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO shop_order_items (order_id, product_id, product_name, sku, qty, unit_price, line_total) VALUES
(1, 1, '50x30 유포지 500매', 'LBL-5030-500', 2, 16900, 33800),
(2, 2, '100x50 아트지 250매', 'LBL-10050-250', 1, 22000, 22000),
(3, 4, '80x80 감열택배 500매', 'THM-8080-500', 1, 29500, 29500),
(3, 3, '40mm 원형 PP 700매', 'LBL-R40-700', 1, 14200, 14200),
(4, 3, '40mm 원형 PP 700매', 'LBL-R40-700', 1, 14200, 14200);
