ALTER TABLE shop_products
    ADD COLUMN header_image VARCHAR(255) NULL COMMENT '상세 헤더 이미지' AFTER spec_sheet_image;

UPDATE shop_products
SET header_image = CONCAT('/assets/product-headers/', sku, '.png')
WHERE sku IS NOT NULL AND sku <> '';
