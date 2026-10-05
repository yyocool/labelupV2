UPDATE shop_products
SET thumbnail = '/assets/categories/cat_ink-charge.png',
    updated_at = NOW()
WHERE ink_amount > 0
  AND (thumbnail IS NULL OR thumbnail = '');

INSERT INTO shop_product_images (product_id, image_path, sort_order, is_primary, created_at, updated_at)
SELECT p.id, '/assets/categories/cat_ink-charge.png', 0, 1, NOW(), NOW()
FROM shop_products p
WHERE p.ink_amount > 0
  AND NOT EXISTS (
    SELECT 1 FROM shop_product_images i WHERE i.product_id = p.id
  );
