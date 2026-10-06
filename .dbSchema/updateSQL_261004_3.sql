UPDATE shop_products
SET header_image = CONCAT(SUBSTRING(header_image, 1, CHAR_LENGTH(header_image) - 4), '.jpg'),
    updated_at = NOW()
WHERE header_image LIKE '/assets/product-headers/%.png';

UPDATE shop_products
SET spec_sheet_image = CONCAT(SUBSTRING(spec_sheet_image, 1, CHAR_LENGTH(spec_sheet_image) - 4), '.jpg'),
    updated_at = NOW()
WHERE spec_sheet_image LIKE '/assets/spec-sheets/%.png';

UPDATE shop_product_page_settings
SET header_image = CONCAT(SUBSTRING(header_image, 1, CHAR_LENGTH(header_image) - 4), '.jpg'),
    updated_at = NOW()
WHERE header_image LIKE '%.png';

UPDATE shop_product_page_category_settings
SET header_image = CONCAT(SUBSTRING(header_image, 1, CHAR_LENGTH(header_image) - 4), '.jpg'),
    updated_at = NOW()
WHERE header_image LIKE '%.png';

UPDATE shop_product_detail_pages
SET html_content = REPLACE(html_content, '.png"', '.jpg"'),
    updated_at = NOW()
WHERE html_content LIKE '%/assets/product-headers/%.png%'
   OR html_content LIKE '%/assets/spec-sheets/%.png%'
   OR html_content LIKE '%/assets/shop-page/%.png%';
