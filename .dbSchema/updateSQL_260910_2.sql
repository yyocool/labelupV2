-- project DB: 개발범위 검수 확인·코멘트 컬럼
ALTER TABLE `dev_scope_items`
    ADD COLUMN `review_confirmed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '검수 확인' AFTER `client_confirmed_by`,
    ADD COLUMN `review_confirmed_at` DATETIME NULL AFTER `review_confirmed`,
    ADD COLUMN `review_confirmed_by` INT UNSIGNED DEFAULT NULL AFTER `review_confirmed_at`,
    ADD COLUMN `review_comment` TEXT NULL COMMENT '검수 코멘트' AFTER `review_confirmed_by`;
