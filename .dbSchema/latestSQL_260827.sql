-- latestSQL_260827.sql (MySQL 5.5 compatible) ‚Äî Phase 1 + Phase 2

CREATE TABLE IF NOT EXISTS migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uk_migrations_name (migration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS app_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uk_app_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('member','admin') NOT NULL DEFAULT 'member',
    status ENUM('active','inactive','withdrawn') NOT NULL DEFAULT 'active',
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uk_users_email (email),
    KEY idx_users_role (role),
    KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS user_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL DEFAULT '',
    phone VARCHAR(30) NULL,
    company VARCHAR(150) NULL,
    avatar VARCHAR(255) NULL,
    locale VARCHAR(10) NOT NULL DEFAULT 'ko',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uk_user_profiles_user_id (user_id),
    KEY idx_user_profiles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS user_login_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    email VARCHAR(190) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    message VARCHAR(255) NULL,
    created_at DATETIME NULL,
    KEY idx_login_logs_user_id (user_id),
    KEY idx_login_logs_email (email),
    KEY idx_login_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS user_oauth_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    provider ENUM('kakao','naver','google') NOT NULL,
    provider_user_id VARCHAR(190) NOT NULL,
    provider_email VARCHAR(190) NULL,
    access_token TEXT NULL,
    refresh_token TEXT NULL,
    token_expires_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uk_oauth_provider_user (provider, provider_user_id),
    KEY idx_oauth_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS user_remember_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    context VARCHAR(10) NOT NULL DEFAULT 'user',
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NULL,
    UNIQUE KEY uk_remember_token_hash (token_hash),
    KEY idx_remember_user_id (user_id),
    KEY idx_remember_user_context (user_id, context),
    KEY idx_remember_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS legal_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doc_key VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    content MEDIUMTEXT NOT NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    is_required TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_legal_documents_key (doc_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
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
('?ºÎ≤®ÏßÄ', 'label-paper', 1, 1, NOW(), NOW()),
('Í∞êÏó¥ÏßÄ', 'thermal-paper', 2, 1, NOW(), NOW()),
('Î¥âÌà¨¬∑?¨Ïû•', 'packaging', 3, 1, NOW(), NOW()),
('?ÑÎ¶∞???åÎ™®??, 'supplies', 4, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO label_specs (name, width_mm, height_mm, material, shape, labels_per_sheet, description, is_active, created_at, updated_at) VALUES
('50x30mm ?†Ìè¨ÏßÄ', 50.00, 30.00, '?†Ìè¨ÏßÄ', 'rect', 40, '?ùÌíà¬∑?îÏû•???åÌòï ?ºÎ≤®', 1, NOW(), NOW()),
('100x50mm ?ÑÌä∏ÏßÄ', 100.00, 50.00, '?ÑÌä∏ÏßÄ', 'rect', 21, 'Î∞∞ÏÜ°¬∑?ÅÌíà ?ºÎ≤®', 1, NOW(), NOW()),
('40mm ?êÌòï', 40.00, 40.00, 'PP', 'round', 35, '?§ÏûÑ?§Ìã∞Ïª§¬∑Î¥â??, 1, NOW(), NOW()),
('80x80mm Í∞êÏó¥ÏßÄ', 80.00, 80.00, 'Í∞êÏó¥ÏßÄ', 'rect', 12, 'Î¨ºÎ•ò¬∑?ùÎ∞∞ ?ºÎ≤®', 1, NOW(), NOW()),
('A4 ?ÑÏ? 210x297', 210.00, 297.00, '?†Ìè¨ÏßÄ', 'rect', 1, 'ÎßûÏ∂§ ?∏ÏáÑ??, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO shop_products (category_id, spec_id, name, sku, price, sale_price, stock_qty, status, description, sort_order, created_at, updated_at) VALUES
(1, 1, '50x30 ?†Ìè¨ÏßÄ 500Îß?, 'LBL-5030-500', 18500, 16900, 120, 'active', 'A4 40Ïπ?¬∑ ?ëÏ∞© ?†Ìè¨ÏßÄ', 1, NOW(), NOW()),
(1, 2, '100x50 ?ÑÌä∏ÏßÄ 250Îß?, 'LBL-10050-250', 22000, NULL, 85, 'active', 'Î∞∞ÏÜ°¬∑?ÅÌíà??Í≥†Í∏â ?ÑÌä∏ÏßÄ', 2, NOW(), NOW()),
(1, 3, '40mm ?êÌòï PP 700Îß?, 'LBL-R40-700', 15800, 14200, 200, 'active', '?¥Ïàò¬∑?¥Ïú† PP ?êÌòï', 3, NOW(), NOW()),
(2, 4, '80x80 Í∞êÏó¥?ùÎ∞∞ 500Îß?, 'THM-8080-500', 32000, 29500, 45, 'active', '?ùÎ∞∞¬∑Î¨ºÎ•ò Í∞êÏó¥ ?ºÎ≤®', 4, NOW(), NOW()),
(3, NULL, '?ºÎ≤®??Î∏åÎûú???®ÌÇ§Ïß??§Ìä∏', 'PKG-START-01', 12000, NULL, 30, 'active', '?òÌîå ?ºÎ≤®ÏßÄ + ?¨Ïû•???∏Ìä∏', 5, NOW(), NOW()),
(4, 5, 'A4 ÎßûÏ∂§ ?∏ÏáÑ 100Îß?, 'CUS-A4-100', 45000, NULL, 0, 'soldout', 'ÎßûÏ∂§ Í∑úÍ≤© ?∏ÏáÑ ?òÎ¢∞', 6, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO shop_coupons (code, name, discount_type, discount_value, min_order_amount, max_uses, used_count, starts_at, ends_at, is_active, created_at, updated_at) VALUES
('WELCOME10', '?†Í∑ú Í∞Ä??10%', 'percent', 10, 10000, 500, 12, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1, NOW(), NOW()),
('LABEL3000', '3,000???†Ïù∏', 'fixed', 3000, 30000, 200, 8, '2026-06-01 00:00:00', '2026-09-30 23:59:59', 1, NOW(), NOW()),
('VIP15', 'VIP 15% ?†Ïù∏', 'percent', 15, 50000, 50, 3, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO shop_banners (title, subtitle, image_url, link_url, sort_order, is_active, created_at, updated_at) VALUES
('?¨Î¶Ñ ?ºÎ≤® Í∏∞Ìöç??, '?∏Í∏∞ ?úÌîåÎ¶?+ ?ºÎ≤®ÏßÄ 15% ?†Ïù∏', '/assets/hero-tall-1.webp', '/', 1, 1, NOW(), NOW()),
('?†Í∑ú ?åÏõê ?∞Ïª¥ Ïø†Ìè∞', 'WELCOME10 ÏΩîÎìúÎ°?10% ?†Ïù∏', '/assets/hero-tall-2.webp', '/register', 2, 1, NOW(), NOW()),
('ÎßûÏ∂§ ?úÏûë ?ÅÎã¥', '?Ä?â¬∑Ìäπ??Í∑úÍ≤© Í≤¨Ï†Å Î¨∏Ïùò', '/assets/hero-tall-3.webp', '/', 3, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO shop_orders (order_no, user_id, customer_name, customer_email, customer_phone, status, payment_status, subtotal, shipping_fee, discount_amount, total_amount, shipping_name, shipping_phone, shipping_address, shipping_memo, carrier, tracking_no, created_at, updated_at) VALUES
('LU202608270001', 1, 'ÍπÄ?ºÎ≤®', 'label.sample1@example.com', '010-1234-5678', 'delivered', 'paid', 33700, 3000, 3000, 33700, 'ÍπÄ?ºÎ≤®', '010-1234-5678', '?úÏö∏?πÎ≥Ñ??Í∞ïÎÇ®Íµ??åÌó§?ÄÎ°?123 4Ï∏?, 'Î∂Ä?¨Ïãú Î¨∏Ïïû', 'CJ?Ä?úÌÜµ??, '123456789012', DATE_SUB(NOW(), INTERVAL 5 DAY), NOW()),
('LU202608270002', NULL, '?¥Ïä§?∞Ïª§', 'sticker@example.com', '010-9876-5432', 'shipping', 'paid', 22000, 3000, 0, 25000, '?¥Ïä§?∞Ïª§', '010-9876-5432', 'Í≤ΩÍ∏∞???±ÎÇ®??Î∂ÑÎãπÍµ??ïÏûêÎ°?45', NULL, '?∞Ï≤¥Íµ?ÉùÎ∞?, '987654321098', DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
('LU202608270003', NULL, 'Î∞ïÌÉùÎ∞?, 'parcel@example.com', '010-5555-7777', 'preparing', 'paid', 47400, 0, 4740, 42660, 'Î∞ïÌÉùÎ∞?, '010-5555-7777', 'Î∂Ä?∞Í¥ë??ãú ?¥Ïö¥?ÄÍµ??ºÌ?Î°?99', 'Îπ†Î•∏ Î∞∞ÏÜ° Î∂Ä??, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
('LU202608270004', NULL, 'ÏµúÏπ¥??, 'cafe@example.com', '010-2222-3333', 'pending', 'pending', 15800, 3000, 0, 18800, 'ÏµúÏπ¥??, '010-2222-3333', '?ÄÍµ¨Í¥ë??ãú ?òÏÑ±Íµ??ôÎ?Íµ¨Î°ú 200', NULL, NULL, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO shop_order_items (order_id, product_id, product_name, sku, qty, unit_price, line_total) VALUES
(1, 1, '50x30 ?†Ìè¨ÏßÄ 500Îß?, 'LBL-5030-500', 2, 16900, 33800),
(2, 2, '100x50 ?ÑÌä∏ÏßÄ 250Îß?, 'LBL-10050-250', 1, 22000, 22000),
(3, 4, '80x80 Í∞êÏó¥?ùÎ∞∞ 500Îß?, 'THM-8080-500', 1, 29500, 29500),
(3, 3, '40mm ?êÌòï PP 700Îß?, 'LBL-R40-700', 1, 14200, 14200),
(4, 3, '40mm ?êÌòï PP 700Îß?, 'LBL-R40-700', 1, 14200, 14200);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NULL,
    UNIQUE KEY uk_password_reset_tokens_token (token),
    KEY idx_password_reset_tokens_user_id (user_id),
    KEY idx_password_reset_tokens_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
CREATE TABLE IF NOT EXISTS credit_reward_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(500) NULL,
    credit_amount INT NOT NULL DEFAULT 0,
    trigger_type ENUM('signup','daily_login','design_complete','referral','purchase_code','event','manual') NOT NULL DEFAULT 'event',
    daily_limit INT UNSIGNED NULL,
    max_total_per_user INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_credit_reward_rules_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS user_credits (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    balance INT NOT NULL DEFAULT 0,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS credit_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    amount INT NOT NULL,
    balance_after INT NOT NULL DEFAULT 0,
    tx_type ENUM('earn','spend','adjust','refund') NOT NULL DEFAULT 'earn',
    source ENUM('reward','purchase_code','admin','order','system') NOT NULL DEFAULT 'system',
    source_ref VARCHAR(100) NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    admin_id BIGINT UNSIGNED NULL,
    created_at DATETIME NULL,
    KEY idx_credit_tx_user_id (user_id),
    KEY idx_credit_tx_created_at (created_at),
    KEY idx_credit_tx_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS purchase_credit_products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    sku VARCHAR(80) NOT NULL,
    credit_amount INT UNSIGNED NOT NULL DEFAULT 0,
    description VARCHAR(500) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_purchase_credit_products_sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS purchase_credit_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(32) NOT NULL,
    batch_no VARCHAR(50) NULL,
    is_redeemed TINYINT(1) NOT NULL DEFAULT 0,
    redeemed_by_user_id BIGINT UNSIGNED NULL,
    redeemed_at DATETIME NULL,
    created_at DATETIME NULL,
    UNIQUE KEY uk_purchase_credit_codes_code (code),
    KEY idx_purchase_credit_codes_product (product_id),
    KEY idx_purchase_credit_codes_redeemed (is_redeemed, redeemed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS user_cs_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NULL,
    category ENUM('inquiry','complaint','refund','account','technical','other') NOT NULL DEFAULT 'inquiry',
    subject VARCHAR(200) NOT NULL,
    content TEXT NULL,
    status ENUM('open','in_progress','resolved') NOT NULL DEFAULT 'open',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_user_cs_logs_user_id (user_id),
    KEY idx_user_cs_logs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO credit_reward_rules (code, name, description, credit_amount, trigger_type, daily_limit, max_total_per_user, is_active, sort_order, created_at, updated_at) VALUES
('SIGNUP_WELCOME', '?åÏõêÍ∞Ä???∞Ïª¥ ?¨Î†à??, '?†Í∑ú Í∞Ä????1??ÏßÄÍ∏?, 500, 'signup', NULL, 1, 1, 1, NOW(), NOW()),
('DAILY_LOGIN', '?ºÏùº ?ëÏÜç Î≥¥ÏÉÅ', '?òÎ£® 1??Î°úÍ∑∏????ÏßÄÍ∏?, 10, 'daily_login', 1, NULL, 1, 2, NOW(), NOW()),
('DESIGN_COMPLETE', '?îÏûê???ÑÎ£å Î≥¥ÏÉÅ', '?ºÎ≤® ?îÏûê???Ä???ÑÎ£å ??, 50, 'design_complete', 5, NULL, 1, 3, NOW(), NOW()),
('REFERRAL', 'ÏπúÍµ¨ Ï∂îÏ≤ú Î≥¥ÏÉÅ', 'Ï∂îÏ≤ú??Í∞Ä???ÑÎ£å ??, 300, 'referral', NULL, 10, 1, 4, NOW(), NOW()),
('EVENT_BONUS', '?¥Î≤§??Î≥¥ÎÑà??, '?¥ÏòÅ ?¥Î≤§???òÎèô ÏßÄÍ∏âÏö©', 100, 'event', NULL, NULL, 1, 5, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO purchase_credit_products (name, sku, credit_amount, description, is_active, created_at, updated_at) VALUES
('?ºÎ≤®???§Ì????§Ìä∏', 'PKG-CREDIT-START', 1000, '?ÖÎ¨∏???ºÎ≤® ?§Ìä∏ Íµ¨Îß§ ???¨Ìï®', 1, NOW(), NOW()),
('?ÑÎ¶¨ÎØ∏ÏóÑ ?ºÎ≤®ÏßÄ ?∏Ìä∏', 'PKG-CREDIT-PREM', 2500, '?ÑÎ¶¨ÎØ∏ÏóÑ ?ºÎ≤®ÏßÄ ?®ÌÇ§ÏßÄ', 1, NOW(), NOW()),
('A4 ÎßûÏ∂§ ?∏ÏáÑ 100Îß?, 'PKG-CREDIT-A4', 5000, 'ÎßûÏ∂§ ?∏ÏáÑ ?ÅÌíà Íµ¨Îß§ ?¨Î†à??, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO purchase_credit_codes (product_id, code, batch_no, is_redeemed, created_at) VALUES
(1, 'LU-START-2026-A001', 'BATCH-202608', 0, NOW()),
(1, 'LU-START-2026-A002', 'BATCH-202608', 0, NOW()),
(2, 'LU-PREM-2026-B001', 'BATCH-202608', 0, NOW()),
(3, 'LU-A4-2026-C001', 'BATCH-202608', 1, NOW())
ON DUPLICATE KEY UPDATE batch_no = VALUES(batch_no);

UPDATE purchase_credit_codes SET redeemed_by_user_id = 1, redeemed_at = DATE_SUB(NOW(), INTERVAL 3 DAY) WHERE code = 'LU-A4-2026-C001';

INSERT INTO credit_transactions (user_id, amount, balance_after, tx_type, source, source_ref, description, created_at) VALUES
(1, 500, 500, 'earn', 'reward', 'SIGNUP_WELCOME', '?åÏõêÍ∞Ä???∞Ïª¥ ?¨Î†à??, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(1, 5000, 5500, 'earn', 'purchase_code', 'LU-A4-2026-C001', 'A4 ÎßûÏ∂§ ?∏ÏáÑ 100Îß?Íµ¨Îß§ ÏΩîÎìú ?±Î°ù', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, -200, 5300, 'spend', 'order', 'design-export', 'AI ?ºÎ≤® Í≥†ÌôîÏß?Ï∂úÎ†•', DATE_SUB(NOW(), INTERVAL 1 DAY))
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT INTO user_credits (user_id, balance, updated_at) VALUES (1, 5300, NOW())
ON DUPLICATE KEY UPDATE balance = VALUES(balance), updated_at = VALUES(updated_at);

INSERT INTO user_cs_logs (user_id, admin_id, category, subject, content, status, created_at, updated_at) VALUES
(1, 1, 'inquiry', 'Î∞∞ÏÜ° ?ºÏ†ï Î¨∏Ïùò', 'Ï£ºÎ¨∏???ºÎ≤®ÏßÄ Î∞∞ÏÜ° ?àÏ†ï???ïÏù∏ ?îÏ≤≠', 'resolved', DATE_SUB(NOW(), INTERVAL 7 DAY), NOW()),
(1, 1, 'technical', '?êÎîî???Ä???§Î•ò', '?îÏûê???Ä????Í∞ÑÌóê???§Î•ò Î∞úÏÉù', 'in_progress', DATE_SUB(NOW(), INTERVAL 2 DAY), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
CREATE TABLE IF NOT EXISTS home_hero_slides (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL DEFAULT '',
    alt_text VARCHAR(255) NOT NULL DEFAULT '',
    image_url VARCHAR(255) NOT NULL,
    link_url VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_home_hero_slides_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO home_hero_slides (title, alt_text, image_url, link_url, sort_order, is_active, created_at, updated_at) VALUES
('?ºÎ≤® ?îÏûê??¬∑ ?úÌîåÎ¶?¬∑ Î∞îÏΩî??, '?ºÎ≤® ?îÏûê?? ?úÌîåÎ¶? Î∞îÏΩî??QR Í∏∞Îä• ?åÍ∞ú', '/assets/hero-tall-1.webp', '/', 1, 1, NOW(), NOW()),
('?∏Í∏∞ ?úÌîåÎ¶?Î™®Ïùå', '?ºÎ≤®???úÌîåÎ¶??åÍ∞ú', '/assets/hero-tall-2.webp', '/', 2, 1, NOW(), NOW()),
('Î∞îÏΩî??¬∑ QR ?ùÏÑ±', 'Î∞îÏΩî??QR ?ùÏÑ± ?åÍ∞ú', '/assets/hero-tall-3.webp', '/', 3, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
