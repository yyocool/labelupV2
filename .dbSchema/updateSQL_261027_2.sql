-- QR 쿠폰 잉크 적립 출처(qr_coupon) 추가
ALTER TABLE credit_transactions
    MODIFY COLUMN source ENUM('reward','purchase_code','admin','order','system','ai','qr_coupon') NOT NULL DEFAULT 'system';
