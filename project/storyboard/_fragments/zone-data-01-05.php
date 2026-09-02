<?php
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '도구 레일', 'elements' => '편집 도구(추가·템플릿·텍스트 등) · 하단 네비(라벨·태그·템플릿·나의디자인·AI) · 도움말', 'menu' => '01-05', 'ux' => '상단: 편집 도구 · 하단: 라벨/태그/템플릿/나의디자인/AI 네비 · ? 도움말'),
        'L-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '레이어 팝업', 'elements' => 'L-01 하단 네비 클릭 시 전체 레이어 오버레이', 'menu' => '01-05', 'ux' => '라벨·태그·템플릿·나의디자인·AI · X·ESC·바깥 클릭 닫기'),
        'Q-01' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '라벨 선택', 'elements' => 'A4/제브라/이름/A3 탭 · 빈/디자인 · 규격 그리드', 'menu' => '01-05', 'ux' => '규격 카드 선택 → 캔버스 적용'),
        'Q-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '태그 선택', 'elements' => 'A4/제트/머플 태그 탭 · FOLD/HANG 규격 그리드', 'menu' => '01-05', 'ux' => '태그 규격 선택 → 캔버스 적용'),
        'Q-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '템플릿 갤러리', 'elements' => '키워드 검색 · 해시태그 필터 · 디자인 썸네일 그리드', 'menu' => '01-05', 'ux' => '템플릿 선택 → 캔버스 적용'),
        'Q-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '나의 디자인', 'elements' => '쿠팡 바코드 · 검색 · 최근 규격 · 저장 목록', 'menu' => '01-05', 'ux' => '저장 디자인 불러오기'),
        'Q-05' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'AI 라벨 생성', 'elements' => '프롬프트 입력 · 이미지/엑셀/예시/파일 시작', 'menu' => '01-05', 'ux' => 'AI 디자인 제안 생성'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 바', 'elements' => '제목(편집) · 저장됨 · 실행취소/다시 · 줌 · 저장하기 · 미리보기 · 편집기에서 출력', 'menu' => '01-05', 'ux' => '제목 편집 · 저장하기 · 미리보기 → 인쇄 프리뷰 모달'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '캔버스', 'elements' => '눈금자 · 라벨 아트보드 · 선택 핸들', 'menu' => '01-05', 'ux' => '드래그·리사이즈 · 더블클릭 텍스트 편집'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '플로팅 툴바', 'elements' => '텍스트·이미지·배경·템플릿·클립아트·아이콘 등 · 드래그·모서리 스냅', 'menu' => '01-05', 'ux' => '배경·템플릿·클립아트·아이콘 → S-00 좌측 슬라이드'),
        'S-00' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '에셋 슬라이드', 'elements' => '검색 · 태그 필터 · 정렬 · 배경/템플릿/클립아트/아이콘 그리드', 'menu' => '01-05', 'ux' => '툴바 클릭 시 좌측 슬라이드 · 재클릭·X·›·ESC 닫기'),
        'R-01' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '속성 패널', 'elements' => '속성/레이어 탭 · 사이즈·텍스트·채우기·테두리·그림자', 'menu' => '01-05', 'ux' => '선택 객체에 따라 폼 변경 · 드래그·접기'),
        'R-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '미리보기 패널', 'elements' => '규격코드 · 2×3 시트 프리뷰 · 페이지 · 라벨복사 메뉴 · 인쇄수량 · 미리보기 토글', 'menu' => '01-05', 'ux' => '속성 패널 아래 배치 · 드래그·접기 · 라벨복사 → 복사 옵션 메뉴'),
        'P-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '프리뷰 상단', 'elements' => '뒤로 · 제목 · 편집/미리보기 토글 · 편집기에서 계속 · 닫기', 'menu' => '01-05', 'ux' => '모달 오버레이 · ESC 닫기'),
        'P-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '용지 설정', 'elements' => 'A4 · 3×4 · 여백 · 간격 · 재단선 · 단위', 'menu' => '01-05', 'ux' => '값 변경 → 그리드 즉시 반영'),
        'P-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '인쇄 미리보기', 'elements' => '페이지 네비 · 3×4 라벨 그리드 · 줌', 'menu' => '01-05', 'ux' => '실제 크기 · 화면 맞춤'),
        'P-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '프리뷰 우측', 'elements' => '레이어 목록 · 프리뷰 옵션 · 인쇄 도움말', 'menu' => '01-05', 'ux' => '배경색·그리드·안전영역 토글'),
    );
}
