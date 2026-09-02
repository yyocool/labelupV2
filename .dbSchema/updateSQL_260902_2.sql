ALTER TABLE shop_products
    ADD COLUMN compat_formtec VARCHAR(80) NULL AFTER meta_json,
    ADD COLUMN compat_ilabel VARCHAR(80) NULL AFTER compat_formtec,
    ADD COLUMN compat_anylabel VARCHAR(80) NULL AFTER compat_ilabel;
