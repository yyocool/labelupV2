-- 261003-1 : 상품 규격 안내 이미지
-- 상세페이지관리의 상품규격 항목에서 상품마다 따로 보여 준다.

ALTER TABLE shop_products
    ADD COLUMN spec_sheet_image VARCHAR(255) NULL COMMENT '상품규격 안내 이미지' AFTER sku;

UPDATE shop_products
SET spec_sheet_image = CONCAT('/assets/spec-sheets/', sku, '.png')
WHERE sku IS NOT NULL AND sku <> '';
