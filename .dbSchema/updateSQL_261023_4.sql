-- 주문에 적용된 쇼핑 쿠폰 코드 기록
ALTER TABLE shop_orders
    ADD COLUMN coupon_code VARCHAR(50) NULL COMMENT '적용 쿠폰 코드' AFTER discount_amount;
