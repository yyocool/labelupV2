-- 카테고리별 사용예(촬영) 이미지를 2026-10-06 교체본으로 다시 연결하고
-- 상품 상세 WYSIWYG 본문을 헤더·규격·촬영 이미지로 다시 등록한다.

UPDATE shop_products p
INNER JOIN shop_categories c ON c.id = p.category_id
LEFT JOIN shop_categories parent ON parent.id = c.parent_id
SET p.shoot_image = CASE
    WHEN c.slug IN ('logistics-label', 'logistics-use', 'address-label', 'barcode-label', 'index-label')
      OR parent.slug = 'logistics-label'
      THEN '/assets/product-shoots/logistics-261006.jpg'
    WHEN c.slug = 'government-doc' THEN '/assets/product-shoots/government-261006.jpg'
    WHEN c.slug = 'gloss-label' THEN '/assets/product-shoots/gloss-261006.jpg'
    WHEN c.slug = 'waterproof-label' THEN '/assets/product-shoots/waterproof-261006.jpg'
    WHEN c.slug IN ('translucent-label', 'inkjet-clear-label', 'laser-clear-label', 'protective-film')
      THEN '/assets/product-shoots/clear-261006.jpg'
    WHEN c.slug IN ('color-label', 'pastel-color-label')
      THEN '/assets/product-shoots/color-261006.jpg'
    WHEN c.slug = 'kraft-label' THEN '/assets/product-shoots/kraft-261006.jpg'
    ELSE p.shoot_image
END;

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
    d.updated_at = NOW();

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
  AND (
    (p.header_image IS NOT NULL AND p.header_image LIKE '/assets/%')
    OR (p.spec_sheet_image IS NOT NULL AND p.spec_sheet_image LIKE '/assets/%')
    OR (p.shoot_image IS NOT NULL AND p.shoot_image LIKE '/assets/%')
  );
