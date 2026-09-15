<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 현재 개발된 사용자/관리자 기능 검수 목록 (페이지 + 세부 기능).
 */
final class QaReviewCatalog
{
    /**
     * @return list<array{
     *   key:string,area:string,group:string,label:string,path:string,point:string,open:bool
     * }>
     */
    public static function items(): array
    {
        return array_merge(self::userItems(), self::adminItems());
    }

    /** @return list<array{key:string,area:string,group:string,label:string,path:string,point:string,open:bool}> */
    private static function userItems(): array
    {
        $u = static fn (string $key, string $group, string $label, string $path, string $point, bool $open = true): array => [
            'key' => $key,
            'area' => 'user',
            'group' => $group,
            'label' => $label,
            'path' => $path,
            'point' => $point,
            'open' => $open,
        ];

        return [
            // 홈·공통
            $u('user-home-load', '홈·공통', '홈 로드', '/', '히어로·인트로·사이드바·주요 CTA 정상 노출'),
            $u('user-home-hero', '홈·공통', '히어로 슬라이드', '/', '슬라이드 전환·링크·반응형'),
            $u('user-home-intro', '홈·공통', '사이트 인트로', '/', '인트로 미디어 재생·닫기·재노출 규칙'),
            $u('user-faq', '홈·공통', 'FAQ', '/faq', '목록·검색·아코디언'),
            $u('user-compat', '홈·공통', '호환 코드 조회', '/compat', '폼텍/아이라벨/애니라벨 코드 검색'),
            $u('user-compat-qr', '홈·공통', '호환 코드 QR 샘플', '/compat/qr', 'QR 샘플 페이지 표시'),
            $u('user-qr-coupon-info', '홈·공통', 'QR 쿠폰 안내', '/qr-coupon', '안내 문구·사용 방법'),
            $u('user-qr-coupon-redeem', '홈·공통', 'QR 쿠폰 사용', '/qr-coupon/{code}', '코드 인식·로그인·크레딧 지급', false),

            // 홈 AI (라비)
            $u('user-home-ai-open', '홈 AI(라비)', '채팅 패널 열기', '/', '홈 라비 채팅 UI 오픈·예시 칩'),
            $u('user-home-ai-chat', '홈 AI(라비)', '일반 대화/추천', '/', '텍스트 질의 응답·상품/용지 추천'),
            $u('user-home-ai-clipart', '홈 AI(라비)', '클립아트 생성', '/', '클립아트 생성·미리보기·편집기 이동'),
            $u('user-home-ai-template', '홈 AI(라비)', '템플릿 생성', '/', '완성 템플릿 생성·편집기 열기'),
            $u('user-home-ai-attach', '홈 AI(라비)', '파일 첨부', '/', '이미지·엑셀·타사포맷 첨부 인식'),
            $u('user-home-ai-credit', '홈 AI(라비)', '크레딧 차감', '/', '의도별 크레딧 차감·잔액 부족 안내'),

            // 편집기 기본
            $u('ed-boot', '편집기 기본', '편집기 로드', '/editor/', 'Blazor 로드·초기 캔버스·상태바'),
            $u('ed-paper-pick', '편집기 기본', '용지 선택', '/editor/', '규격 검색·호환코드·선택 반영'),
            $u('ed-cloud-save', '편집기 기본', '클라우드 저장', '/editor/', '로그인 후 저장·덮어쓰기'),
            $u('ed-cloud-load', '편집기 기본', '프로젝트 불러오기', '/editor/', '저장 목록·열기·삭제'),
            $u('ed-undo-redo', '편집기 기본', '실행취소/다시실행', '/editor/', '히스토리 Undo/Redo'),
            $u('ed-zoom-pan', '편집기 기본', '확대/이동', '/editor/', '줌·패닝·맞춤보기'),
            $u('ed-tutorial', '편집기 기본', '튜토리얼', '/editor/', '첫 사용자 가이드·단계 이동'),
            $u('ed-credit-chip', '편집기 기본', '크레딧 표시', '/editor/', '잔여 크레딧 칩·이력 팝업'),

            // 편집기 객체·도구
            $u('ed-tool-text', '편집기 도구', '일반텍스트', '/editor/', '추가·편집·서식(글꼴/크기/색/정렬)'),
            $u('ed-tool-wordart', '편집기 도구', '워드아트', '/editor/', '스타일·각도·편집'),
            $u('ed-tool-custom-text', '편집기 도구', '사용자정의문자열', '/editor/', '서식·데이터 연결'),
            $u('ed-tool-barcode', '편집기 도구', '바코드', '/editor/', '유형 선택·값 입력·미리보기'),
            $u('ed-tool-qr', '편집기 도구', 'QR코드', '/editor/', '생성·크기·오류정정'),
            $u('ed-tool-image', '편집기 도구', '이미지', '/editor/', '업로드·배치·리사이즈·크롭'),
            $u('ed-tool-table', '편집기 도구', '표', '/editor/', '행열·셀 편집'),
            $u('ed-tool-shape', '편집기 도구', '도형', '/editor/', '도형 추가·선/채우기'),
            $u('ed-clipart-panel', '편집기 도구', '클립아트 패널', '/editor/', '공용/내 클립아트 검색·삽입'),
            $u('ed-template-panel', '편집기 도구', '템플릿 불러오기', '/editor/', '템플릿 목록·적용'),
            $u('ed-datasheet', '편집기 도구', '데이터시트 연결', '/editor/', '엑셀/데이터 행 연결·필드 매핑'),
            $u('ed-align-transform', '편집기 도구', '정렬·변형', '/editor/', '정렬/회전/앞뒤순서/복사'),

            // 타사포맷 가져오기
            $u('ed-vendor-entry', '타사포맷 가져오기', '가져오기 UI', '/editor/', '타사포맷 버튼·드롭존·확장자 안내'),
            $u('ed-vendor-formtec', '타사포맷 가져오기', '폼텍 변환', '/editor/', '.dgz/.dgf/.fmt/.fdx 변환·객체/용지 매핑'),
            $u('ed-vendor-ilabel', '타사포맷 가져오기', '아이라벨 변환', '/editor/', '.idf/.xml/.zip 변환·엑셀 연결'),
            $u('ed-vendor-anylabel', '타사포맷 가져오기', '애니라벨 변환', '/editor/', '.lbl 변환·바코드/이미지/텍스트'),
            $u('ed-vendor-report', '타사포맷 가져오기', '변환 결과 리포트', '/editor/', '벤더/용지/데이터/디자인 요약·경고'),
            $u('ed-vendor-apply', '타사포맷 가져오기', '변환 적용', '/editor/', '캔버스 반영·편집 가능 여부'),

            // 편집기 AI (라비)
            $u('ed-labi-open', '편집기 AI(라비)', '라비 패널', '/editor/', '편집기 내 라비 다이얼로그 오픈'),
            $u('ed-labi-recommend', '편집기 AI(라비)', '상품/용지 추천', '/editor/', '추천 결과·용지/상품 적용'),
            $u('ed-labi-clipart', '편집기 AI(라비)', '클립아트 생성·삽입', '/editor/', '생성 후 캔버스 삽입·크레딧 차감'),
            $u('ed-labi-template', '편집기 AI(라비)', '템플릿 생성·적용', '/editor/', '완성 디자인 생성·편집 시작'),
            $u('ed-labi-attach', '편집기 AI(라비)', '첨부 기반 작업', '/editor/', '이미지·시트·타사포맷 첨부 처리'),
            $u('ed-labi-vendor', '편집기 AI(라비)', '타사포맷→라비 변환', '/editor/', '라비에서 타사파일 받아 변환 적용'),

            // 편집기 쇼핑·출력
            $u('ed-shop-dialog', '편집기 쇼핑·출력', '라벨 구매 다이얼로그', '/editor/', '호환상품·수량·장바구니/주문'),
            $u('ed-shop-checkout', '편집기 쇼핑·출력', '편집기 결제 연동', '/editor/', '주문 생성·토스 결제창'),
            $u('ed-print-preview', '편집기 쇼핑·출력', '인쇄/미리보기', '/editor/', '미리보기·인쇄 레이아웃'),

            // 쇼핑몰
            $u('user-shop-home', '쇼핑몰', '쇼핑몰 홈', '/shop', '배너·카테고리·추천'),
            $u('user-shop-list', '쇼핑몰', '상품 목록', '/shop/products', '필터·정렬·페이징'),
            $u('user-shop-detail', '쇼핑몰', '상품 상세', '/shop/products/{id}', '상세페이지·옵션·장바구니', false),
            $u('user-shop-cart', '쇼핑몰', '장바구니', '/shop/cart', '수량변경·삭제·합계·배송비'),
            $u('user-shop-order', '쇼핑몰', '주문 접수', '/shop/cart', '배송지·주문자·주문 생성'),
            $u('user-shop-toss', '쇼핑몰', '토스 결제', '/shop/cart', '결제위젯·수단선택·승인'),
            $u('user-shop-complete', '쇼핑몰', '주문 완료', '/shop/complete', '주문번호·결제상태·재주문'),
            $u('user-shop-pay-fail', '쇼핑몰', '결제 실패', '/shop/pay/fail', '실패 안내·재시도'),

            // 회원
            $u('user-login-email', '회원', '이메일 로그인', '/login', '이메일/비밀번호 로그인'),
            $u('user-login-oauth', '회원', '소셜 로그인', '/login', '카카오/구글 OAuth'),
            $u('user-register', '회원', '회원가입', '/register', '가입·약관·이메일 중복검사'),
            $u('user-reset', '회원', '비밀번호 재설정', '/reset-password', '재설정 메일·토큰 처리'),
            $u('user-account-profile', '회원', '마이페이지 프로필', '/account', '이름·연락처 수정'),
            $u('user-account-orders', '회원', '주문 내역', '/account', '주문 목록·상태 확인'),
            $u('user-account-credits', '회원', '크레딧/알림', '/account', '잔액·이력·알림 설정'),
            $u('user-account-address', '회원', '배송지 관리', '/account', '주소 추가·수정·삭제'),
        ];
    }

    /** @return list<array{key:string,area:string,group:string,label:string,path:string,point:string,open:bool}> */
    private static function adminItems(): array
    {
        $a = static fn (string $key, string $group, string $label, string $path, string $point, bool $open = true): array => [
            'key' => $key,
            'area' => 'admin',
            'group' => $group,
            'label' => $label,
            'path' => $path,
            'point' => $point,
            'open' => $open,
        ];

        return [
            $a('admin-login', '공통', '관리자 로그인', '/admin/login', '로그인·세션'),
            $a('admin-dashboard', '공통', '대시보드', '/admin', 'KPI·알림·즐겨찾기'),
            $a('admin-qa-sheet', '공통', '기능 검수 시트', '/admin/qa-review', '개발자/고객사 상태 저장'),

            $a('admin-users', '운영관리', '회원 목록', '/admin/users', '검색·등급·상태'),
            $a('admin-user-credit', '운영관리', '회원 크레딧 조정', '/admin/users', '지급/차감·이력'),
            $a('admin-user-detail', '운영관리', '회원 상세', '/admin/users/{id}', '상세 정보·이력', false),
            $a('admin-settings-legal', '운영관리', '약관 운영설정', '/admin/settings', '약관 문서 저장'),
            $a('admin-hero', '운영관리', '히어로 이미지', '/admin/ops/hero-slides', '등록·정렬·공개'),
            $a('admin-popups', '운영관리', '이벤트 팝업', '/admin/ops/event-popups', '기간·노출·저장'),
            $a('admin-faq', '운영관리', 'FAQ 관리', '/admin/ops/faq', 'CRUD·정렬'),
            $a('admin-inquiries', '운영관리', '1:1 문의', '/admin/ops/inquiries', '답변·상태변경'),
            $a('admin-credit-rewards', '운영관리', '크레딧보상 규칙', '/admin/ops/credit-rewards', '규칙 저장/삭제'),
            $a('admin-credit-usage', '운영관리', '크레딧 사용 설정', '/admin/ops/credit-usage', 'AI 의도별 차감·통계'),
            $a('admin-purchase-credits', '운영관리', '구매크레딧', '/admin/ops/purchase-credits', 'QR그룹 크레딧·지급이력'),

            $a('admin-qr-generate', 'QR쿠폰', '쿠폰 생성', '/admin/qr-coupons', '그룹·수량 생성'),
            $a('admin-qr-print', 'QR쿠폰', '인쇄/출력', '/admin/qr-coupons', '인쇄 템플릿·출력상태'),
            $a('admin-qr-usage', 'QR쿠폰', '사용 이력', '/admin/qr-coupons', '사용/미사용 조회'),

            $a('admin-admins', '설정', '관리자/권한', '/admin/settings/admins', '관리자 추가·메뉴권한'),
            $a('admin-grades', '설정', '회원등급', '/admin/settings/member-grades', '등급 CRUD'),
            $a('admin-intro', '설정', '인트로설정', '/admin/settings/intro', '미디어 업로드·저장'),
            $a('admin-seo', '설정', 'SEO 설정', '/admin/settings/seo', '메타·페이지 SEO'),
            $a('admin-tracking', '설정', '광고 스크립트', '/admin/settings/tracking', '트래킹·ads.txt'),

            $a('admin-ai-credit', 'AI 관리', 'AI 크레딧 설정', '/admin/ai/credit-settings', '모델/의도 크레딧'),
            $a('admin-ai-prompts', 'AI 관리', '예시프롬프트', '/admin/ai/example-prompts', '프롬프트 CRUD'),
            $a('admin-ai-token-logs', 'AI 관리', '토큰사용로그', '/admin/ai/token-logs', '로그·내보내기'),
            $a('admin-ai-member', 'AI 관리', '회원별 사용', '/admin/ai/member-usage', '회원별 집계'),
            $a('admin-ai-usage', 'AI 관리', '사용량 통계', '/admin/ai/usage', '기간별 통계'),

            $a('admin-cliparts', '컨텐츠', '클립아트관리', '/admin/content/cliparts', '업로드·분류·공개'),
            $a('admin-user-designs', '컨텐츠', '사용자디자인 검수', '/admin/content/user-designs', '승인/반려'),
            $a('admin-templates', '컨텐츠', '템플릿관리', '/admin/content/templates', '템플릿 CRUD'),
            $a('admin-pdp', '컨텐츠', '상세페이지관리', '/admin/content/product-detail-pages', '편집·미리보기'),

            $a('admin-shop-cat', '쇼핑몰운영', '카테고리', '/admin/shop/categories', 'CRUD'),
            $a('admin-shop-specs', '쇼핑몰운영', '용지 규격', '/admin/shop/specs', 'CRUD·이미지'),
            $a('admin-shop-products', '쇼핑몰운영', '상품 관리', '/admin/shop/products', '상품·이미지·호환코드'),
            $a('admin-shop-orders', '쇼핑몰운영', '주문 관리', '/admin/shop/orders', '상태·결제·내보내기'),
            $a('admin-shop-shipping', '쇼핑몰운영', '배송 관리', '/admin/shop/shipping', '배송비·정책'),
            $a('admin-shop-coupons', '쇼핑몰운영', '쿠폰·프로모션', '/admin/shop/coupons', 'CRUD'),
            $a('admin-shop-banners', '쇼핑몰운영', '배너·전시', '/admin/shop/banners', 'CRUD'),
        ];
    }
}
