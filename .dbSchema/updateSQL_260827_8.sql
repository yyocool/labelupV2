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
('라벨 디자인 · 템플릿 · 바코드', '라벨 디자인, 템플릿, 바코드 QR 기능 소개', '/assets/hero-tall-1.webp', '/', 1, 1, NOW(), NOW()),
('인기 템플릿 모음', '라벨업 템플릿 소개', '/assets/hero-tall-2.webp', '/', 2, 1, NOW(), NOW()),
('바코드 · QR 생성', '바코드 QR 생성 소개', '/assets/hero-tall-3.webp', '/', 3, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
