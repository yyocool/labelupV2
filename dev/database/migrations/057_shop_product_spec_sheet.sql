-- 상품 규격 안내 이미지를 상세 본문과 따로 보관한다.
ALTER TABLE shop_products
    ADD COLUMN spec_sheet_image VARCHAR(255) NULL COMMENT '상품규격 안내 이미지' AFTER sku;

UPDATE shop_products
SET spec_sheet_image = CONCAT('/assets/spec-sheets/', sku, '.png')
WHERE sku IS NOT NULL AND sku <> '';
