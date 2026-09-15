-- project DB: 검수 상태(검수중/검수완료/보완필요)
ALTER TABLE `dev_scope_items`
    ADD COLUMN `review_status` VARCHAR(20) NOT NULL DEFAULT 'in_review' COMMENT '검수중/검수완료/보완필요' AFTER `review_comment`;

UPDATE `dev_scope_items` SET `review_status` = 'done' WHERE `review_confirmed` = 1;
UPDATE `dev_scope_items` SET `review_status` = 'in_review' WHERE (`review_confirmed` = 0 OR `review_confirmed` IS NULL) AND (`review_status` = '' OR `review_status` IS NULL OR `review_status` = 'in_review');
