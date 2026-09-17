-- 이용약관 전문 교체 (공정위 전자상거래 표준약관 구조 + AI/편집기/쇼핑 특칙)
-- 본문 HTML은 database/data/legal_terms_v2.html 에 보관하며, 아래 스크립트로 적용합니다.
--   php scripts/apply_legal_terms_v2.php
--
-- 마이그레이션 이력 기록용 (실제 content UPDATE는 PHP 스크립트가 수행)
SELECT '050_terms_full_applied_via_php' AS migration_note;
