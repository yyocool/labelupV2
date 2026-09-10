-- shop_categories: 무료배포 QR 제품분류(11개) 기준으로 재정리
-- 1) 활성 카테고리 명칭/정렬 업데이트 (slug 기준 upsert)
INSERT INTO `shop_categories` (`name`, `slug`, `image_path`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
('물류관리용/주소용/바코드용/인덱스용', 'logistics-label', '/assets/categories/cat_logistics-label.webp', 1, 1, NOW(), NOW()),
('정부문서화일 라벨', 'government-doc', '/assets/categories/cat_government-doc.webp', 2, 1, NOW(), NOW()),
('광택 라벨', 'gloss-label', '/assets/categories/cat_gloss-label.webp', 3, 1, NOW(), NOW()),
('방수 라벨', 'waterproof-label', '/assets/categories/cat_waterproof-label.webp', 4, 1, NOW(), NOW()),
('반투명 라벨', 'translucent-label', '/assets/categories/cat_translucent-label.webp', 5, 1, NOW(), NOW()),
('잉크젯 투명 라벨', 'inkjet-clear-label', '/assets/categories/cat_inkjet-clear-label.webp', 6, 1, NOW(), NOW()),
('레이저 투명 라벨', 'laser-clear-label', '/assets/categories/cat_laser-clear-label.webp', 7, 1, NOW(), NOW()),
('보호용 필름', 'protective-film', '/assets/categories/cat_protective-film.webp', 8, 1, NOW(), NOW()),
('컬러 라벨(형광)', 'color-label', '/assets/categories/cat_color-label.webp', 9, 1, NOW(), NOW()),
('파스텔 컬러 라벨', 'pastel-color-label', '/assets/categories/cat_pastel-color-label.webp', 10, 1, NOW(), NOW()),
('크라프트 라벨', 'kraft-label', '/assets/categories/cat_kraft-label.webp', 11, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `image_path` = COALESCE(VALUES(`image_path`), `image_path`),
    `sort_order` = VALUES(`sort_order`),
    `is_active` = 1,
    `updated_at` = NOW();

-- 2) 주소/바코드/인덱스 상품을 통합 카테고리로 이동
UPDATE `shop_products` p
INNER JOIN `shop_categories` src ON src.id = p.category_id
INNER JOIN `shop_categories` dst ON dst.slug = 'logistics-label'
SET p.category_id = dst.id, p.updated_at = NOW()
WHERE src.slug IN ('address-label', 'barcode-label', 'index-label');

-- 4) 상품 없는 병합·레거시 카테고리 삭제
DELETE c
FROM `shop_categories` c
LEFT JOIN `shop_products` p ON p.category_id = c.id
WHERE c.`slug` IN (
    'address-label',
    'barcode-label',
    'index-label',
    'label-paper',
    'thermal-paper',
    'packaging',
    'supplies'
)
AND p.id IS NULL;
