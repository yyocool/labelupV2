-- 회원등급 설정
CREATE TABLE IF NOT EXISTS member_grades (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    color VARCHAR(20) NOT NULL DEFAULT '#7B2D3E',
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_member_grades_slug (slug),
    KEY idx_member_grades_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO member_grades (name, slug, description, color, sort_order, is_default, is_active, created_at, updated_at)
SELECT '일반', 'general', '기본 회원등급입니다.', '#6B7280', 10, 1, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM member_grades WHERE slug = 'general');

INSERT INTO member_grades (name, slug, description, color, sort_order, is_default, is_active, created_at, updated_at)
SELECT '실버', 'silver', '꾸준히 이용하는 회원에게 적용되는 등급입니다.', '#8A94A6', 20, 0, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM member_grades WHERE slug = 'silver');

INSERT INTO member_grades (name, slug, description, color, sort_order, is_default, is_active, created_at, updated_at)
SELECT '골드', 'gold', '우수 회원에게 적용되는 등급입니다.', '#C9A227', 30, 0, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM member_grades WHERE slug = 'gold');

INSERT INTO member_grades (name, slug, description, color, sort_order, is_default, is_active, created_at, updated_at)
SELECT 'VIP', 'vip', '최상위 회원등급입니다.', '#7B2D3E', 40, 0, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM member_grades WHERE slug = 'vip');

ALTER TABLE users ADD COLUMN grade_id BIGINT UNSIGNED NULL AFTER role;

CREATE INDEX idx_users_grade_id ON users (grade_id);

UPDATE users u
LEFT JOIN member_grades g ON g.is_default = 1
SET u.grade_id = g.id
WHERE u.grade_id IS NULL AND u.deleted_at IS NULL;
