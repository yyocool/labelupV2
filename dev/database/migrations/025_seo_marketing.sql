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

INSERT IGNORE INTO seo_settings (setting_key, setting_value, updated_at) VALUES
('seo.site_name', '라벨업 LABEL UP', NOW()),
('seo.default_title', '라벨업 LABEL UP', NOW()),
('seo.title_suffix', ' — 라벨업', NOW()),
('seo.default_description', '라벨 디자인부터 출력·구매까지 한곳에서. 라벨업에서 원하는 규격의 라벨을 만들고 주문하세요.', NOW()),
('seo.default_keywords', '라벨,라벨지,라벨 인쇄,라벨 디자인,라벨업,LABEL UP', NOW()),
('seo.default_og_image', '', NOW()),
('seo.og_site_name', '라벨업 LABEL UP', NOW()),
('seo.twitter_card', 'summary_large_image', NOW()),
('seo.twitter_site', '', NOW()),
('seo.canonical_base', '', NOW()),
('seo.robots_default', 'index,follow', NOW()),
('seo.robots_txt', 'User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /admin/\nDisallow: /account\nDisallow: /api/\nDisallow: /login\nDisallow: /register\nDisallow: /reset-password\n\nSitemap: /sitemap.xml\n', NOW()),
('seo.sitemap_enabled', '1', NOW()),
('seo.jsonld_enabled', '1', NOW()),
('seo.locale', 'ko_KR', NOW()),
('seo.favicon_url', '', NOW()),
('seo.org_name', '라벨업', NOW()),
('seo.org_url', '', NOW()),
('seo.org_logo', '', NOW()),
('seo.org_phone', '', NOW()),
('seo.org_email', '', NOW()),
('seo.org_same_as', '', NOW()),
('mkt.enabled', '1', NOW()),
('mkt.gtm_id', '', NOW()),
('mkt.ga4_id', '', NOW()),
('mkt.google_ads_id', '', NOW()),
('mkt.google_site_verification', '', NOW()),
('mkt.naver_site_verification', '', NOW()),
('mkt.naver_analytics_id', '', NOW()),
('mkt.naver_wcs_id', '', NOW()),
('mkt.meta_pixel_id', '', NOW()),
('mkt.kakao_pixel_id', '', NOW()),
('mkt.bing_verification', '', NOW()),
('mkt.ads_txt', '', NOW()),
('mkt.app_ads_txt', '', NOW()),
('mkt.custom_head', '', NOW()),
('mkt.custom_body_start', '', NOW()),
('mkt.custom_body_end', '', NOW());

INSERT IGNORE INTO seo_pages
(page_key, label, path_pattern, title, description, robots, noindex, sitemap_include, sitemap_changefreq, sitemap_priority, og_type, sort_order, updated_at)
VALUES
('home', '홈', '/', '라벨업 LABEL UP', '라벨 디자인부터 출력·구매까지 한곳에서.', 'index,follow', 0, 1, 'daily', 1.0, 'website', 10, NOW()),
('shop', '쇼핑몰 홈', '/shop', '쇼핑몰 — 라벨업', '라벨지·규격 상품을 둘러보고 바로 주문하세요.', 'index,follow', 0, 1, 'daily', 0.9, 'website', 20, NOW()),
('shop-products', '상품 목록', '/shop/products', '상품 목록 — 라벨업 쇼핑몰', '라벨업 쇼핑몰의 라벨 상품을 검색하고 비교하세요.', 'index,follow', 0, 1, 'daily', 0.8, 'website', 30, NOW()),
('shop-product', '상품 상세', '/shop/products/{id}', '', '상품 상세 페이지. 상품명이 제목에 자동 반영됩니다.', 'index,follow', 0, 1, 'weekly', 0.7, 'product', 40, NOW()),
('shop-cart', '장바구니', '/shop/cart', '장바구니 — 라벨업', '', 'noindex,nofollow', 1, 0, 'weekly', 0.1, 'website', 50, NOW()),
('faq', '자주 묻는 질문', '/faq', '자주 묻는 질문 — 라벨업', '라벨업 이용, 주문, 편집기에 대한 자주 묻는 질문.', 'index,follow', 0, 1, 'weekly', 0.6, 'website', 60, NOW()),
('login', '로그인', '/login', '로그인 — 라벨업', '', 'noindex,nofollow', 1, 0, 'yearly', 0.1, 'website', 70, NOW()),
('register', '회원가입', '/register', '회원가입 — 라벨업', '', 'noindex,nofollow', 1, 0, 'yearly', 0.1, 'website', 80, NOW()),
('account', '마이페이지', '/account', '마이페이지 — 라벨업', '', 'noindex,nofollow', 1, 0, 'yearly', 0.1, 'website', 90, NOW()),
('reset-password', '비밀번호 재설정', '/reset-password', '비밀번호 재설정 — 라벨업', '', 'noindex,nofollow', 1, 0, 'yearly', 0.1, 'website', 100, NOW()),
('editor', '편집기', '/editor/', '라벨 디자인 편집기 — 라벨업', '브라우저에서 라벨을 디자인하고 저장하세요.', 'index,follow', 0, 1, 'weekly', 0.7, 'website', 110, NOW());
