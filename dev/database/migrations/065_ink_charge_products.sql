-- 잉크충전 카테고리와 단품 상품. 지급 잉크는 shop_products.ink_amount.

ALTER TABLE shop_products
    ADD COLUMN ink_amount INT UNSIGNED NULL COMMENT '지급 잉크. 값이 있으면 잉크 충전 상품' AFTER sale_price;

INSERT INTO shop_categories (parent_id, name, slug, image_path, sort_order, is_active, created_at, updated_at)
VALUES (NULL, '잉크충전', 'ink-charge', NULL, 90, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_active = 1,
    updated_at = NOW();

INSERT INTO shop_products
    (category_id, spec_id, name, sku, price, sale_price, ink_amount, stock_qty, status, description, sort_order, created_at, updated_at)
SELECT c.id, NULL, '50,000 잉크', 'INK-50000', 5000, 3500, 50000, 999999, 'active',
       '결제 후 계정에 50,000 잉크가 지급됩니다. 구독이 아닌 1회 충전 상품입니다.', 1, NOW(), NOW()
FROM shop_categories c
WHERE c.slug = 'ink-charge'
  AND NOT EXISTS (SELECT 1 FROM shop_products p WHERE p.sku = 'INK-50000')
LIMIT 1;

INSERT INTO shop_products
    (category_id, spec_id, name, sku, price, sale_price, ink_amount, stock_qty, status, description, sort_order, created_at, updated_at)
SELECT c.id, NULL, '80,000 잉크', 'INK-80000', 8000, 5600, 80000, 999999, 'active',
       '결제 후 계정에 80,000 잉크가 지급됩니다. 구독이 아닌 1회 충전 상품입니다.', 2, NOW(), NOW()
FROM shop_categories c
WHERE c.slug = 'ink-charge'
  AND NOT EXISTS (SELECT 1 FROM shop_products p WHERE p.sku = 'INK-80000')
LIMIT 1;

INSERT INTO shop_products
    (category_id, spec_id, name, sku, price, sale_price, ink_amount, stock_qty, status, description, sort_order, created_at, updated_at)
SELECT c.id, NULL, '100,000 잉크', 'INK-100000', 10000, 7000, 100000, 999999, 'active',
       '결제 후 계정에 100,000 잉크가 지급됩니다. 구독이 아닌 1회 충전 상품입니다.', 3, NOW(), NOW()
FROM shop_categories c
WHERE c.slug = 'ink-charge'
  AND NOT EXISTS (SELECT 1 FROM shop_products p WHERE p.sku = 'INK-100000')
LIMIT 1;

INSERT INTO shop_products
    (category_id, spec_id, name, sku, price, sale_price, ink_amount, stock_qty, status, description, sort_order, created_at, updated_at)
SELECT c.id, NULL, '150,000 잉크', 'INK-150000', 15000, 10500, 150000, 999999, 'active',
       '결제 후 계정에 150,000 잉크가 지급됩니다. 구독이 아닌 1회 충전 상품입니다.', 4, NOW(), NOW()
FROM shop_categories c
WHERE c.slug = 'ink-charge'
  AND NOT EXISTS (SELECT 1 FROM shop_products p WHERE p.sku = 'INK-150000')
LIMIT 1;

INSERT INTO shop_products
    (category_id, spec_id, name, sku, price, sale_price, ink_amount, stock_qty, status, description, sort_order, created_at, updated_at)
SELECT c.id, NULL, '200,000 잉크', 'INK-200000', 20000, 14000, 200000, 999999, 'active',
       '결제 후 계정에 200,000 잉크가 지급됩니다. 구독이 아닌 1회 충전 상품입니다.', 5, NOW(), NOW()
FROM shop_categories c
WHERE c.slug = 'ink-charge'
  AND NOT EXISTS (SELECT 1 FROM shop_products p WHERE p.sku = 'INK-200000')
LIMIT 1;
