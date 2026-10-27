ALTER TABLE credit_transactions
    MODIFY COLUMN source ENUM('reward','purchase_code','admin','order','system','ai','qr_coupon') NOT NULL DEFAULT 'system';
