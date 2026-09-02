-- SEO 설정 + 광고 스크립트 (025)
CREATE TABLE IF NOT EXISTS seo_settings (
    setting_key VARCHAR(80) NOT NULL,
    setting_value MEDIUMTEXT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS seo_pages (
    page_key VARCHAR(80) NOT NULL,
    label VARCHAR(80) NOT NULL,
    path_pattern VARCHAR(180) NOT NULL,
    title VARCHAR(180) NULL,
    description TEXT NULL,
    keywords VARCHAR(500) NULL,
    og_title VARCHAR(180) NULL,
    og_description TEXT NULL,
    og_image VARCHAR(500) NULL,
    og_type VARCHAR(40) NULL,
    robots VARCHAR(80) NULL,
    canonical_path VARCHAR(255) NULL,
    noindex TINYINT(1) NOT NULL DEFAULT 0,
    sitemap_include TINYINT(1) NOT NULL DEFAULT 1,
    sitemap_changefreq VARCHAR(20) NOT NULL DEFAULT 'weekly',
    sitemap_priority DECIMAL(2,1) NOT NULL DEFAULT 0.5,
    extra_head TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    updated_at DATETIME NULL,
    PRIMARY KEY (page_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS marketing_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(180) NOT NULL,
    content MEDIUMTEXT NOT NULL,
    file_kind VARCHAR(20) NOT NULL DEFAULT 'html',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_marketing_files_name (filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
