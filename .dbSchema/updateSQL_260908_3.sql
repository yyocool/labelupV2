-- 호환 상품코드 복수 등록 (폼텍/아이라벨/애니라벨)
ALTER TABLE shop_products
    MODIFY COLUMN compat_formtec TEXT NULL,
    MODIFY COLUMN compat_ilabel TEXT NULL,
    MODIFY COLUMN compat_anylabel TEXT NULL;

UPDATE shop_products
SET compat_formtec = REPLACE(REPLACE(REPLACE(compat_formtec, ', ', ','), ' ,', ','), ';;', ',')
WHERE compat_formtec IS NOT NULL AND compat_formtec <> '';

UPDATE shop_products
SET compat_ilabel = REPLACE(REPLACE(REPLACE(compat_ilabel, ', ', ','), ' ,', ','), ';;', ',')
WHERE compat_ilabel IS NOT NULL AND compat_ilabel <> '';

UPDATE shop_products
SET compat_anylabel = REPLACE(REPLACE(REPLACE(compat_anylabel, ', ', ','), ' ,', ','), ';;', ',')
WHERE compat_anylabel IS NOT NULL AND compat_anylabel <> '';
