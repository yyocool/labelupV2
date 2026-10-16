-- 상품 상세 헤더 이미지를 2026-10-07 패키지 시안(*-261007.jpg)으로 다시 연결하고
-- WYSIWYG 본문의 헤더 경로도 맞춘다.

UPDATE shop_products
SET header_image = CONCAT('/assets/product-headers/', sku, '-261007.jpg'),
    updated_at = NOW()
WHERE sku IS NOT NULL
  AND sku <> ''
  AND (
    header_image = CONCAT('/assets/product-headers/', sku, '.jpg')
    OR header_image = CONCAT('/assets/product-headers/', sku, '.png')
  );

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
WHERE p.header_image LIKE '/assets/product-headers/%-261007.jpg';
