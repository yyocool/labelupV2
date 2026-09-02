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
('SIGNUP_WELCOME', '회원가입 웰컴 크레딧', '신규 가입 시 1회 지급', 500, 'signup', NULL, 1, 1, 1, NOW(), NOW()),
('DAILY_LOGIN', '일일 접속 보상', '하루 1회 로그인 시 지급', 10, 'daily_login', 1, NULL, 1, 2, NOW(), NOW()),
('DESIGN_COMPLETE', '디자인 완료 보상', '라벨 디자인 저장/완료 시', 50, 'design_complete', 5, NULL, 1, 3, NOW(), NOW()),
('REFERRAL', '친구 추천 보상', '추천인 가입 완료 시', 300, 'referral', NULL, 10, 1, 4, NOW(), NOW()),
('EVENT_BONUS', '이벤트 보너스', '운영 이벤트 수동 지급용', 100, 'event', NULL, NULL, 1, 5, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO purchase_credit_products (name, sku, credit_amount, description, is_active, created_at, updated_at) VALUES
('라벨업 스타터 키트', 'PKG-CREDIT-START', 1000, '입문용 라벨 키트 구매 시 포함', 1, NOW(), NOW()),
('프리미엄 라벨지 세트', 'PKG-CREDIT-PREM', 2500, '프리미엄 라벨지 패키지', 1, NOW(), NOW()),
('A4 맞춤 인쇄 100매', 'PKG-CREDIT-A4', 5000, '맞춤 인쇄 상품 구매 크레딧', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO purchase_credit_codes (product_id, code, batch_no, is_redeemed, created_at) VALUES
(1, 'LU-START-2026-A001', 'BATCH-202608', 0, NOW()),
(1, 'LU-START-2026-A002', 'BATCH-202608', 0, NOW()),
(2, 'LU-PREM-2026-B001', 'BATCH-202608', 0, NOW()),
(3, 'LU-A4-2026-C001', 'BATCH-202608', 1, NOW())
ON DUPLICATE KEY UPDATE batch_no = VALUES(batch_no);

UPDATE purchase_credit_codes SET redeemed_by_user_id = 1, redeemed_at = DATE_SUB(NOW(), INTERVAL 3 DAY) WHERE code = 'LU-A4-2026-C001';

INSERT INTO credit_transactions (user_id, amount, balance_after, tx_type, source, source_ref, description, created_at) VALUES
(1, 500, 500, 'earn', 'reward', 'SIGNUP_WELCOME', '회원가입 웰컴 크레딧', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(1, 5000, 5500, 'earn', 'purchase_code', 'LU-A4-2026-C001', 'A4 맞춤 인쇄 100매 구매 코드 등록', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, -200, 5300, 'spend', 'order', 'design-export', 'AI 라벨 고화질 출력', DATE_SUB(NOW(), INTERVAL 1 DAY))
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT INTO user_credits (user_id, balance, updated_at) VALUES (1, 5300, NOW())
ON DUPLICATE KEY UPDATE balance = VALUES(balance), updated_at = VALUES(updated_at);

INSERT INTO user_cs_logs (user_id, admin_id, category, subject, content, status, created_at, updated_at) VALUES
(1, 1, 'inquiry', '배송 일정 문의', '주문한 라벨지 배송 예정일 확인 요청', 'resolved', DATE_SUB(NOW(), INTERVAL 7 DAY), NOW()),
(1, 1, 'technical', '에디터 저장 오류', '디자인 저장 시 간헐적 오류 발생', 'in_progress', DATE_SUB(NOW(), INTERVAL 2 DAY), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
