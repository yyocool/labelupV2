UPDATE shop_product_detail_pages d
INNER JOIN shop_products p ON p.id = d.product_id
SET d.html_content = CONCAT(
      IF(p.header_image IS NOT NULL AND p.header_image LIKE '/assets/%',
         CONCAT('<img src="', p.header_image, '" alt="헤더 이미지">'), ''),
      IF(p.spec_sheet_image IS NOT NULL AND p.spec_sheet_image LIKE '/assets/%',
         CONCAT('<img src="', p.spec_sheet_image, '" alt="상품규격">'), ''),
      IF(p.shoot_image IS NOT NULL AND p.shoot_image LIKE '/assets/%',
         CONCAT('<img src="', p.shoot_image, '" alt="촬영">'), '')
    ),
    d.updated_at = NOW();
