-- 호환 상품코드: 벤더당 복수 코드(쉼표 구분) 저장을 위해 TEXT로 확장
ALTER TABLE shop_products
    MODIFY COLUMN compat_formtec TEXT NULL,
    MODIFY COLUMN compat_ilabel TEXT NULL,
    MODIFY COLUMN compat_anylabel TEXT NULL;

-- 기존 단일 코드의 ", " 공백을 정리해 FIND_IN_SET 매칭이 안정되도록 함
UPDATE shop_products
SET compat_formtec = REPLACE(REPLACE(REPLACE(compat_formtec, ', ', ','), ' ,', ','), ';;', ',')
WHERE compat_formtec IS NOT NULL AND compat_formtec <> '';

UPDATE shop_products
SET compat_ilabel = REPLACE(REPLACE(REPLACE(compat_ilabel, ', ', ','), ' ,', ','), ';;', ',')
WHERE compat_ilabel IS NOT NULL AND compat_ilabel <> '';

UPDATE shop_products
SET compat_anylabel = REPLACE(REPLACE(REPLACE(compat_anylabel, ', ', ','), ' ,', ','), ';;', ',')
WHERE compat_anylabel IS NOT NULL AND compat_anylabel <> '';
