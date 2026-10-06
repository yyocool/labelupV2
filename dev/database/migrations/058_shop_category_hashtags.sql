ALTER TABLE shop_product_page_category_settings
    ADD COLUMN hashtags VARCHAR(1000) NULL COMMENT '카테고리 해시태그 JSON 배열' AFTER footer_image;

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["물류관리","주소용","바코드용","인덱스용","정부문서"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'logistics-label'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["물류관리","박스표기","출고관리","포장라벨","재고정리"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'logistics-use'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["주소표기","배송라벨","수신인","발신인","우편봉투"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'address-label'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["바코드","상품식별","재고관리","포장부착","보관함"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'barcode-label'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["문서정리","파일분류","인덱스","수납함","이름표"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'index-label'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["정부문서","문서철","제목표기","분류정리","보관용"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'government-doc'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["광택라벨","브랜드","상품명","로고","제품포장"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'gloss-label'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["방수","습기차단","용기표기","필름소재","외부포장"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'waterproof-label'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["반투명","배경비침","용기라벨","포장분위기","정보표시"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'translucent-label'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["투명라벨","잉크젯","브랜드","용기디자인","배경비침"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'inkjet-clear-label'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["투명라벨","레이저","브랜드","용기디자인","배경비침"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'laser-clear-label'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["보호필름","오염방지","표면보호","덧붙임","손상방지"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'protective-film'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["형광색","시인성","안내문구","분류표시","강조표기"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'color-label'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["파스텔","색상분류","문서정리","포장포인트","수납구분"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'pastel-color-label'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);

INSERT INTO shop_product_page_category_settings (category_id, hashtags, created_at, updated_at)
SELECT id, '["크라프트","선물포장","자연색감","브랜드","소품포장"]', NOW(), NOW()
FROM shop_categories WHERE slug = 'kraft-label'
ON DUPLICATE KEY UPDATE
    hashtags = IF(hashtags IS NULL OR hashtags = '', VALUES(hashtags), hashtags);
