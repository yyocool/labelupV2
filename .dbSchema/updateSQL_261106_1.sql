-- 용지 규격 노출 순서(작을수록 먼저). 기존 쇼핑몰 노출 순서(칸수 많은 순)로 초기화
ALTER TABLE label_specs
    ADD COLUMN sort_order INT NOT NULL DEFAULT 0 COMMENT '노출 순서(작을수록 먼저)' AFTER is_active,
    ADD KEY idx_label_specs_sort (is_active, sort_order);

SET @spec_rank := 0;

UPDATE label_specs
SET sort_order = (@spec_rank := @spec_rank + 10)
ORDER BY labels_per_sheet IS NULL, labels_per_sheet DESC, id ASC;
