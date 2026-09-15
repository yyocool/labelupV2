-- project DB: 개발범위 고객사 확인 컬럼
ALTER TABLE `dev_scope_items`
    ADD COLUMN `client_confirmed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '고객사 확인' AFTER `status`,
    ADD COLUMN `client_confirmed_at` DATETIME NULL AFTER `client_confirmed`,
    ADD COLUMN `client_confirmed_by` INT UNSIGNED DEFAULT NULL AFTER `client_confirmed_at`;
