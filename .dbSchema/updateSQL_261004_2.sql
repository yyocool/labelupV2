-- 상세 촬영 이미지. 배포용 촬영본을 카테고리에 맞춰 상품에 연결한다.

ALTER TABLE shop_products
    ADD COLUMN shoot_image VARCHAR(255) NULL COMMENT '상세 촬영 이미지' AFTER header_image;

UPDATE shop_products p
INNER JOIN shop_categories c ON c.id = p.category_id
LEFT JOIN shop_categories parent ON parent.id = c.parent_id
SET p.shoot_image = CASE
    WHEN c.slug IN ('logistics-label', 'logistics-use', 'address-label', 'barcode-label', 'index-label')
      OR parent.slug = 'logistics-label'
      THEN '/assets/product-shoots/logistics.jpg'
    WHEN c.slug = 'government-doc' THEN '/assets/product-shoots/government.jpg'
    WHEN c.slug = 'gloss-label' THEN '/assets/product-shoots/gloss.jpg'
    WHEN c.slug = 'waterproof-label' THEN '/assets/product-shoots/waterproof.jpg'
    WHEN c.slug IN ('translucent-label', 'inkjet-clear-label', 'laser-clear-label', 'protective-film')
      THEN '/assets/product-shoots/clear.jpg'
    WHEN c.slug IN ('color-label', 'pastel-color-label')
      THEN '/assets/product-shoots/color.jpg'
    WHEN c.slug = 'kraft-label' THEN '/assets/product-shoots/kraft.jpg'
    ELSE NULL
END;
