-- shop_products: 제품분류(11) 카테고리 재매핑 + 불량 SKU 정리
-- 상세 매핑은 php dev/scripts/remap_products_to_categories.php 로 적용

DELETE FROM `shop_products` WHERE `sku` = '127 sku';
