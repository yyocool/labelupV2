-- data/HEADER 교체본(2026-10-08)을 상품 헤더에 연결하고
-- 상품 상세 WYSIWYG 본문을 헤더·규격·촬영 이미지로 다시 생성한다.

UPDATE shop_products
SET header_image = CONCAT('/assets/product-headers/', sku, '-261008.jpg'),
    updated_at = NOW()
WHERE sku IS NOT NULL
  AND sku <> ''
  AND header_image LIKE '/assets/product-headers/%';

UPDATE shop_product_detail_pages d
INNER JOIN shop_products p ON p.id = d.product_id
SET d.html_content = CONCAT(
      IF(p.header_image IS NOT NULL AND p.header_image LIKE '/assets/%',
         CONCAT('<p><img src="', p.header_image, '" alt="헤더 이미지"></p>'), ''),
      IF(p.spec_sheet_image IS NOT NULL AND p.spec_sheet_image LIKE '/assets/%',
         CONCAT('<p><img src="', p.spec_sheet_image, '" alt="상품규격"></p>'), ''),
      IF(p.shoot_image IS NOT NULL AND p.shoot_image LIKE '/assets/%',
         CONCAT('<p><img src="', p.shoot_image, '" alt="촬영"></p>'), '')
    ),
    d.status = 'published',
    d.generated_at = NOW(),
    d.updated_at = NOW()
WHERE p.header_image LIKE '/assets/product-headers/%-261008.jpg';

INSERT INTO shop_product_detail_pages
    (product_id, status, title, html_content, generated_at, created_at, updated_at)
SELECT
    p.id,
    'published',
    NULL,
    CONCAT(
      IF(p.header_image IS NOT NULL AND p.header_image LIKE '/assets/%',
         CONCAT('<p><img src="', p.header_image, '" alt="헤더 이미지"></p>'), ''),
      IF(p.spec_sheet_image IS NOT NULL AND p.spec_sheet_image LIKE '/assets/%',
         CONCAT('<p><img src="', p.spec_sheet_image, '" alt="상품규격"></p>'), ''),
      IF(p.shoot_image IS NOT NULL AND p.shoot_image LIKE '/assets/%',
         CONCAT('<p><img src="', p.shoot_image, '" alt="촬영"></p>'), '')
    ),
    NOW(),
    NOW(),
    NOW()
FROM shop_products p
LEFT JOIN shop_product_detail_pages d ON d.product_id = p.id
WHERE d.id IS NULL
  AND p.header_image LIKE '/assets/product-headers/%-261008.jpg';
