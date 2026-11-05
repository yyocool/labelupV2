ALTER TABLE user_inquiries
    ADD COLUMN answer TEXT NULL AFTER admin_memo,
    ADD COLUMN answered_at DATETIME NULL AFTER answer;
