-- R 원형 라벨(A304R, A406R, LN101R) 상품규격 이미지를 원형으로 교체하고
-- 상세페이지 HTML을 헤더·규격·촬영 이미지로 다시 생성한다.
-- LN101R 스펙 shape 도 round 로 맞춘다.

UPDATE label_specs s
INNER JOIN shop_products p ON p.spec_id = s.id
SET s.shape = 'round',
    s.updated_at = NOW()
WHERE p.sku IN ('A304R', 'A406R', 'LN101R');

UPDATE shop_products
SET spec_sheet_image = CONCAT('/assets/spec-sheets/', sku, '-261012.jpg'),
    updated_at = NOW()
WHERE sku IN ('A304R', 'A406R', 'LN101R');

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
WHERE p.sku IN ('A304R', 'A406R', 'LN101R');

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
  AND p.sku IN ('A304R', 'A406R', 'LN101R');
