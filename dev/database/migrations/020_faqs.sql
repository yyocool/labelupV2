CREATE TABLE IF NOT EXISTS faq_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_faq_categories_slug (slug),
    KEY idx_faq_categories_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS faqs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    question VARCHAR(300) NOT NULL,
    answer MEDIUMTEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    KEY idx_faqs_category (category_id, is_active, sort_order),
    KEY idx_faqs_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO faq_categories (name, slug, sort_order, is_active, created_at, updated_at)
SELECT '시작하기', 'start', 10, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faq_categories WHERE slug = 'start');

INSERT INTO faq_categories (name, slug, sort_order, is_active, created_at, updated_at)
SELECT '회원·계정', 'account', 20, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faq_categories WHERE slug = 'account');

INSERT INTO faq_categories (name, slug, sort_order, is_active, created_at, updated_at)
SELECT '편집기', 'editor', 30, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faq_categories WHERE slug = 'editor');

INSERT INTO faq_categories (name, slug, sort_order, is_active, created_at, updated_at)
SELECT '라벨쇼핑', 'shop', 40, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faq_categories WHERE slug = 'shop');

INSERT INTO faq_categories (name, slug, sort_order, is_active, created_at, updated_at)
SELECT '크레딧·AI', 'credit', 50, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faq_categories WHERE slug = 'credit');

INSERT INTO faq_categories (name, slug, sort_order, is_active, created_at, updated_at)
SELECT '주문·배송', 'order', 60, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faq_categories WHERE slug = 'order');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '라벨업은 어떤 서비스인가요?',
'<p>라벨업은 라벨 디자인부터 라벨지 구매까지 한 곳에서 진행하는 서비스입니다.</p><p>웹 편집기에서 디자인을 만들고, 라비AI로 문구·이미지를 도우며, 쇼핑몰에서 규격 라벨지를 바로 주문할 수 있습니다.</p>',
10, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'start' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '라벨업은 어떤 서비스인가요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '처음인데 어떻게 시작하나요?',
'<p>홈에서 <b>새 디자인 만들기</b>를 누르거나, 라비에게 원하는 라벨을 말로 설명해 보세요.</p><p>용지 규격을 고른 뒤 텍스트·이미지·바코드를 올리고 저장하면 됩니다. 라벨지가 필요하면 라벨쇼핑에서 같은 규격으로 주문할 수 있습니다.</p>',
20, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'start' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '처음인데 어떻게 시작하나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '모바일에서도 사용할 수 있나요?',
'<p>홈·쇼핑몰·마이페이지는 모바일 브라우저에서도 이용할 수 있습니다.</p><p>라벨 편집기는 화면이 넓은 PC나 태블릿에서 사용하는 것을 권장합니다.</p>',
30, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'start' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '모바일에서도 사용할 수 있나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '회원가입은 어떻게 하나요?',
'<p>우측 상단 로그인 화면에서 <b>회원가입</b>을 선택하고 이메일과 비밀번호를 입력하면 됩니다.</p><p>가입 후 마이페이지에서 프로필과 크레딧을 확인할 수 있습니다.</p>',
10, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'account' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '회원가입은 어떻게 하나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '비밀번호를 잊어버렸어요',
'<p>로그인 화면의 <b>비밀번호 찾기</b>에서 가입 이메일을 입력하면 재설정 안내를 받을 수 있습니다.</p><p>메일이 오지 않으면 스팸함을 확인하거나, 가입 시 사용한 이메일인지 다시 확인해 주세요.</p>',
20, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'account' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '비밀번호를 잊어버렸어요');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '회원 탈퇴는 어디서 하나요?',
'<p>로그인 후 <b>마이페이지</b>에서 회원 탈퇴를 진행할 수 있습니다.</p><p>탈퇴하면 저장된 작업과 크레딧 정보가 삭제될 수 있으니 필요한 디자인은 먼저 내려받아 주세요.</p>',
30, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'account' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '회원 탈퇴는 어디서 하나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '라벨 디자인은 어디서 하나요?',
'<p>왼쪽 메뉴의 <b>새 디자인 만들기</b> 또는 <b>라벨 디자인</b>을 누르면 웹 편집기가 열립니다.</p><p>규격·템플릿을 고른 뒤 텍스트와 이미지를 배치하고, 상단의 저장하기로 계정에 보관하세요.</p>',
10, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'editor' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '라벨 디자인은 어디서 하나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '작업한 디자인은 어디에 저장되나요?',
'<p>로그인 상태에서 편집기의 <b>저장하기</b>를 누르면 계정 작업공간에 저장됩니다.</p><p>홈의 최근 작업 또는 편집기 불러오기에서 이어서 수정할 수 있습니다. 로그인하지 않으면 브라우저에만 임시 보관될 수 있습니다.</p>',
20, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'editor' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '작업한 디자인은 어디에 저장되나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '다른 규격의 라벨지로 바꿀 수 있나요?',
'<p>편집기에서 용지·테마를 다시 선택하면 다른 규격으로 작업을 이어갈 수 있습니다.</p><p>이미 배치한 내용은 규격에 맞게 위치가 달라질 수 있으니, 중요한 작업은 먼저 저장해 두세요.</p>',
30, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'editor' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '다른 규격의 라벨지로 바꿀 수 있나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '엑셀 데이터로 라벨을 만들 수 있나요?',
'<p>편집기의 <b>데이터 가져오기</b>에서 CSV 또는 엑셀 파일을 올리면 행마다 라벨을 채울 수 있습니다.</p><p>주소록, 상품명, 가격처럼 반복되는 정보를 한 번에 넣을 때 유용합니다.</p>',
40, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'editor' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '엑셀 데이터로 라벨을 만들 수 있나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '라벨지는 어떻게 구매하나요?',
'<p>왼쪽 메뉴 <b>쇼핑몰</b> 또는 홈의 라벨지 쇼핑몰에서 규격·재질을 고른 뒤 장바구니에 담고 주문하면 됩니다.</p><p>편집기 안에서도 라벨쇼핑 버튼으로 같은 상품을 볼 수 있습니다.</p>',
10, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'shop' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '라벨지는 어떻게 구매하나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '어떤 규격을 사야 할지 모르겠어요',
'<p>쇼핑몰 상품 상세와 규격 검색에서 가로·세로(mm)와 칸 수를 확인할 수 있습니다.</p><p>편집기에서 사용하는 용지 번호와 같은 상품을 고르면 인쇄 위치가 맞습니다. 라비에게 용도를 말하면 상품을 추천받을 수도 있습니다.</p>',
20, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'shop' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '어떤 규격을 사야 할지 모르겠어요');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '장바구니에 담은 상품은 얼마나 유지되나요?',
'<p>로그인한 계정의 장바구니는 서버에 저장되어 다음 방문에도 유지됩니다.</p><p>재고·가격이 바뀌면 주문 전에 다시 확인해 주세요.</p>',
30, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'shop' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '장바구니에 담은 상품은 얼마나 유지되나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '크레딧은 무엇인가요?',
'<p>크레딧은 라비AI 등 유료 기능을 이용할 때 사용하는 포인트입니다.</p><p>가입 혜택, 이벤트, 구매 크레딧으로 충전할 수 있으며 잔액과 내역은 마이페이지에서 확인합니다.</p>',
10, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'credit' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '크레딧은 무엇인가요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '라비AI는 무엇을 해주나요?',
'<p>라비는 원하는 라벨을 말로 설명하면 디자인 방향과 상품·클립아트를 제안하는 AI 도우미입니다.</p><p>홈의 채팅창이나 편집기의 라비AI 버튼에서 대화할 수 있습니다. 일부 생성 기능은 크레딧이 필요할 수 있습니다.</p>',
20, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'credit' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '라비AI는 무엇을 해주나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '클립아트는 어떻게 쓰나요?',
'<p>편집기에서 클립아트를 고르거나, 라비에게 그림을 요청한 뒤 현재 칸에 넣을 수 있습니다.</p><p>직접 만든 AI 이미지는 계정에 보관되어 다른 작업에도 다시 쓸 수 있습니다.</p>',
30, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'credit' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '클립아트는 어떻게 쓰나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '주문 후 배송은 얼마나 걸리나요?',
'<p>주문 확인 후 영업일 기준으로 출고되며, 배송 완료까지는 보통 2~4일 정도 소요됩니다.</p><p>정확한 일정은 주문 상태와 택배사 사정에 따라 달라질 수 있습니다. 마이페이지 또는 주문 내역에서 진행 상태를 확인해 주세요.</p>',
10, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'order' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '주문 후 배송은 얼마나 걸리나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '주문 취소·반품은 가능한가요?',
'<p>제작 전 주문은 취소가 가능합니다. 이미 인쇄·출고된 맞춤 라벨은 단순 변심 반품이 어려울 수 있습니다.</p><p>상품 불량이나 오배송은 고객센터로 주문번호와 사진을 남겨 주시면 빠르게 도와드립니다.</p>',
20, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'order' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '주문 취소·반품은 가능한가요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '세금계산서나 현금영수증을 받을 수 있나요?',
'<p>사업자 회원은 주문 시 또는 주문 후 증빙 요청으로 세금계산서를 받을 수 있습니다.</p><p>현금영수증은 결제 수단에 따라 자동 발급되거나 별도 신청이 필요할 수 있습니다. 자세한 안내는 주문 내역의 문의 경로를 이용해 주세요.</p>',
30, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'order' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '세금계산서나 현금영수증을 받을 수 있나요?');
