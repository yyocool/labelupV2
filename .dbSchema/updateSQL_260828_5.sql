-- 컨텐츠관리: 클립아트 라이브러리
CREATE TABLE IF NOT EXISTS clipart_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_clipart_categories_slug (slug),
    KEY idx_clipart_categories_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS cliparts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NULL,
    title VARCHAR(150) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    hashtags TEXT NULL,
    description VARCHAR(500) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    source VARCHAR(20) NOT NULL DEFAULT 'upload',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_cliparts_category (category_id),
    KEY idx_cliparts_active (is_active, sort_order),
    KEY idx_cliparts_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS clipart_tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    created_at DATETIME NULL,
    UNIQUE KEY uk_clipart_tags_slug (slug),
    UNIQUE KEY uk_clipart_tags_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS clipart_tag_map (
    clipart_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (clipart_id, tag_id),
    KEY idx_clipart_tag_map_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
