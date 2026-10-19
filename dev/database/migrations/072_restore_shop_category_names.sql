-- shop_categories.name 인코딩 손실(????) 복구
UPDATE shop_categories SET name = '다용도라벨', updated_at = NOW() WHERE slug = 'logistics-label';
UPDATE shop_categories SET name = '물류관리용', updated_at = NOW() WHERE slug = 'logistics-use';
UPDATE shop_categories SET name = '주소용', updated_at = NOW() WHERE slug = 'address-label';
UPDATE shop_categories SET name = '바코드용', updated_at = NOW() WHERE slug = 'barcode-label';
UPDATE shop_categories SET name = '인덱스용', updated_at = NOW() WHERE slug = 'index-label';
UPDATE shop_categories SET name = '정부문서화일 라벨', updated_at = NOW() WHERE slug = 'government-doc';
UPDATE shop_categories SET name = '광택 라벨', updated_at = NOW() WHERE slug = 'gloss-label';
UPDATE shop_categories SET name = '방수 라벨', updated_at = NOW() WHERE slug = 'waterproof-label';
UPDATE shop_categories SET name = '반투명 라벨', updated_at = NOW() WHERE slug = 'translucent-label';
UPDATE shop_categories SET name = '잉크젯 투명 라벨', updated_at = NOW() WHERE slug = 'inkjet-clear-label';
UPDATE shop_categories SET name = '레이저 투명 라벨', updated_at = NOW() WHERE slug = 'laser-clear-label';
UPDATE shop_categories SET name = '보호용 필름', updated_at = NOW() WHERE slug = 'protective-film';
UPDATE shop_categories SET name = '컬러 라벨(형광)', updated_at = NOW() WHERE slug = 'color-label';
UPDATE shop_categories SET name = '파스텔 컬러 라벨', updated_at = NOW() WHERE slug = 'pastel-color-label';
UPDATE shop_categories SET name = '크라프트 라벨', updated_at = NOW() WHERE slug = 'kraft-label';
UPDATE shop_categories SET name = '잉크충전', updated_at = NOW() WHERE slug = 'ink-charge';
