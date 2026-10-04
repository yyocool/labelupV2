-- 261002-2 : 상품 찜하기(위시리스트)
-- 상품 상세의 찜하기 버튼과 마이페이지 "찜한 상품" 목록에서 쓴다.
-- 회원 한 명이 같은 상품을 두 번 담지 못하게 유니크 키를 둔다.

CREATE TABLE IF NOT EXISTS shop_product_wishlists (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NULL,
    UNIQUE KEY uniq_shop_wishlist_user_product (user_id, product_id),
    KEY idx_shop_wishlist_user (user_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
