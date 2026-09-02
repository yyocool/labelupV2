CREATE TABLE IF NOT EXISTS event_popups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL DEFAULT '',
    image_url VARCHAR(255) NOT NULL DEFAULT '',
    link_url VARCHAR(255) NULL,
    content TEXT NULL,
    start_at DATETIME NULL,
    end_at DATETIME NULL,
    hide_days INT UNSIGNED NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_event_popups_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO event_popups (title, image_url, link_url, content, start_at, end_at, hide_days, sort_order, is_active, created_at, updated_at)
SELECT '라벨업 오픈 이벤트', '/assets/hero-tall-1.webp', '/shop', '지금 회원가입하고 웰컴 크레딧을 받아보세요.', NULL, NULL, 1, 10, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM event_popups LIMIT 1);
