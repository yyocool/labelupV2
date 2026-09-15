-- project DB: 검수 페이지 URL
ALTER TABLE `dev_scope_items`
    ADD COLUMN `page_url` VARCHAR(1000) NULL COMMENT '페이지 URL' AFTER `review_status`;
