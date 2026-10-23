-- 홈 중간배너(용도별 라벨 연출 이미지)
CREATE TABLE IF NOT EXISTS home_mid_banners (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL DEFAULT '',
    alt_text VARCHAR(255) NOT NULL DEFAULT '',
    image_url VARCHAR(255) NOT NULL,
    link_url VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_home_mid_banners_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
