-- QR 쿠폰: 무료배포 QR 그룹 체계 (제품분류 11 · QR그룹 19)
CREATE TABLE IF NOT EXISTS `qr_coupon_groups` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `group_no` SMALLINT UNSIGNED NOT NULL COMMENT 'QR 그룹No. 1-19',
    `category_no` SMALLINT UNSIGNED NOT NULL COMMENT '제품 분류No. 1-11',
    `category_slug` VARCHAR(100) NOT NULL,
    `category_name` VARCHAR(150) NOT NULL,
    `sheets_per_pack` INT UNSIGNED NOT NULL COMMENT '매수/팩',
    `list_price` INT UNSIGNED NOT NULL COMMENT '정상 소비자가(원)',
    `credit_amount` INT UNSIGNED NULL COMMENT '지급 크레딧(추후 확정)',
    `color_hex` CHAR(7) NOT NULL DEFAULT '#9b1c1c' COMMENT '분류No. 셀 배경색',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    UNIQUE KEY `uk_qr_coupon_groups_no` (`group_no`),
    KEY `idx_qr_coupon_groups_category` (`category_no`),
    KEY `idx_qr_coupon_groups_slug_sheets` (`category_slug`, `sheets_per_pack`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `qr_coupon_groups`
    (`group_no`, `category_no`, `category_slug`, `category_name`, `sheets_per_pack`, `list_price`, `credit_amount`, `color_hex`, `is_active`, `created_at`, `updated_at`)
VALUES
(1,  1, 'logistics-label',      '물류관리용/주소용/바코드용/인덱스용', 20,  8000,  NULL, '#9b1c1c', 1, NOW(), NOW()),
(2,  1, 'logistics-label',      '물류관리용/주소용/바코드용/인덱스용', 100, 29000, NULL, '#9b1c1c', 1, NOW(), NOW()),
(3,  1, 'logistics-label',      '물류관리용/주소용/바코드용/인덱스용', 200, 50000, NULL, '#9b1c1c', 1, NOW(), NOW()),
(4,  2, 'government-doc',       '정부문서화일 라벨',                   20,  8000,  NULL, '#c62828', 1, NOW(), NOW()),
(5,  2, 'government-doc',       '정부문서화일 라벨',                   50, 14000, NULL, '#c62828', 1, NOW(), NOW()),
(6,  3, 'gloss-label',          '광택 라벨',                           100, 32000, NULL, '#7b1fa2', 1, NOW(), NOW()),
(7,  4, 'waterproof-label',     '방수 라벨',                           10, 10000, NULL, '#00897b', 1, NOW(), NOW()),
(8,  4, 'waterproof-label',     '방수 라벨',                           50, 47000, NULL, '#00897b', 1, NOW(), NOW()),
(9,  5, 'translucent-label',    '반투명 라벨',                         10, 10000, NULL, '#90a4ae', 1, NOW(), NOW()),
(10, 5, 'translucent-label',    '반투명 라벨',                         50, 42000, NULL, '#90a4ae', 1, NOW(), NOW()),
(11, 6, 'inkjet-clear-label',   '잉크젯 투명 라벨',                    5,   8000, NULL, '#6d4c41', 1, NOW(), NOW()),
(12, 6, 'inkjet-clear-label',   '잉크젯 투명 라벨',                    50, 52000, NULL, '#6d4c41', 1, NOW(), NOW()),
(13, 7, 'laser-clear-label',    '레이저 투명 라벨',                    10,  9000, NULL, '#78909c', 1, NOW(), NOW()),
(14, 7, 'laser-clear-label',    '레이저 투명 라벨',                    50, 40000, NULL, '#78909c', 1, NOW(), NOW()),
(15, 8, 'protective-film',      '보호용 필름',                         10,  5000, NULL, '#9e9d24', 1, NOW(), NOW()),
(16, 9, 'color-label',          '컬러 라벨(형광)',                     10,  8000, NULL, '#ef6c00', 1, NOW(), NOW()),
(17, 10,'pastel-color-label',   '파스텔 컬러 라벨',                    20,  8000, NULL, '#ffab91', 1, NOW(), NOW()),
(18, 11,'kraft-label',          '크라프트 라벨',                       10,  6000, NULL, '#33691e', 1, NOW(), NOW()),
(19, 11,'kraft-label',          '크라프트 라벨',                       100,42000, NULL, '#33691e', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `category_no` = VALUES(`category_no`),
    `category_slug` = VALUES(`category_slug`),
    `category_name` = VALUES(`category_name`),
    `sheets_per_pack` = VALUES(`sheets_per_pack`),
    `list_price` = VALUES(`list_price`),
    `color_hex` = VALUES(`color_hex`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = NOW();
