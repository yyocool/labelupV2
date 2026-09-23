ALTER TABLE shop_categories
    ADD COLUMN parent_id BIGINT UNSIGNED NULL AFTER id,
    ADD KEY idx_shop_categories_parent (parent_id);

UPDATE shop_categories
SET name = '다용도라벨', updated_at = NOW()
WHERE slug = 'logistics-label';

INSERT INTO shop_categories (parent_id, name, slug, image_path, sort_order, is_active, created_at, updated_at)
SELECT p.id, '물류관리용', 'logistics-use', p.image_path, 1, 1, NOW(), NOW()
FROM shop_categories p
WHERE p.slug = 'logistics-label'
LIMIT 1
ON DUPLICATE KEY UPDATE
    parent_id = VALUES(parent_id),
    name = VALUES(name),
    sort_order = VALUES(sort_order),
    is_active = 1,
    updated_at = NOW();

INSERT INTO shop_categories (parent_id, name, slug, image_path, sort_order, is_active, created_at, updated_at)
SELECT p.id, '주소용', 'address-label', p.image_path, 2, 1, NOW(), NOW()
FROM shop_categories p
WHERE p.slug = 'logistics-label'
LIMIT 1
ON DUPLICATE KEY UPDATE
    parent_id = VALUES(parent_id),
    name = VALUES(name),
    sort_order = VALUES(sort_order),
    is_active = 1,
    updated_at = NOW();

INSERT INTO shop_categories (parent_id, name, slug, image_path, sort_order, is_active, created_at, updated_at)
SELECT p.id, '바코드용', 'barcode-label', p.image_path, 3, 1, NOW(), NOW()
FROM shop_categories p
WHERE p.slug = 'logistics-label'
LIMIT 1
ON DUPLICATE KEY UPDATE
    parent_id = VALUES(parent_id),
    name = VALUES(name),
    sort_order = VALUES(sort_order),
    is_active = 1,
    updated_at = NOW();

INSERT INTO shop_categories (parent_id, name, slug, image_path, sort_order, is_active, created_at, updated_at)
SELECT p.id, '인덱스용', 'index-label', p.image_path, 4, 1, NOW(), NOW()
FROM shop_categories p
WHERE p.slug = 'logistics-label'
LIMIT 1
ON DUPLICATE KEY UPDATE
    parent_id = VALUES(parent_id),
    name = VALUES(name),
    sort_order = VALUES(sort_order),
    is_active = 1,
    updated_at = NOW();

UPDATE shop_products p
INNER JOIN shop_categories src ON src.id = p.category_id AND src.slug = 'logistics-label'
INNER JOIN shop_categories dst ON dst.slug = 'index-label'
SET p.category_id = dst.id, p.updated_at = NOW()
WHERE p.name LIKE '%인덱스%';

UPDATE shop_products p
INNER JOIN shop_categories src ON src.id = p.category_id AND src.slug = 'logistics-label'
INNER JOIN shop_categories dst ON dst.slug = 'barcode-label'
SET p.category_id = dst.id, p.updated_at = NOW()
WHERE p.name LIKE '%바코드%';

UPDATE shop_products p
INNER JOIN shop_categories src ON src.id = p.category_id AND src.slug = 'logistics-label'
INNER JOIN shop_categories dst ON dst.slug = 'address-label'
SET p.category_id = dst.id, p.updated_at = NOW()
WHERE p.name LIKE '%주소%';

UPDATE shop_products p
INNER JOIN shop_categories src ON src.id = p.category_id AND src.slug = 'logistics-label'
INNER JOIN shop_categories dst ON dst.slug = 'logistics-use'
SET p.category_id = dst.id, p.updated_at = NOW()
WHERE p.name LIKE '%물류%';

UPDATE qr_coupon_groups
SET category_name = '다용도라벨', updated_at = NOW()
WHERE category_slug = 'logistics-label';
