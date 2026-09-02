<?php
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '홈·라벨디자인(active)·템플릿·규격·인쇄·맞춤·자료실·고객센터', 'menu' => '01 하위 전역', 'ux' => '라벨 디자인 섹션 진입점'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '로고 · 홈 · 라벨디자인 · 템플릿 · 규격검색 · 인쇄 · 맞춤 · 자료실 · 고객센터', 'menu' => '01 전역', 'ux' => 'Desktop: 고정 · Mobile: 드로어'),
        'L-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '보유 포인트', 'elements' => '12,450 P · 내역 보기 · 코인 일러스트', 'menu' => '01-04', 'ux' => '클릭 → 포인트 내역'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 바', 'elements' => '브레드크럼 ‹ AI 라벨 디자인 › 심플 모드 · 검색(⌘K) · 알림(3) · 프로필', 'menu' => '01-04-01', 'ux' => '브레드크럼 클릭 → 상위 · 검색 통합'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '페이지 히어로', 'elements' => '심플 모드 · "간편하게 빠른 디자인" 배지 · 설명 · AI 로봇 정적 이미지', 'menu' => '01-04-01-02', 'ux' => '정적 이미지 · 클릭 없음'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'AI 프롬프트 입력', 'elements' => '질문 헤드 · textarea · 0/2000 · 전송 버튼', 'menu' => '01-04-01-02', 'ux' => 'Enter+Shift 줄바꿈 · 전송 → AI 생성 API'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '대체 입력 4카드', 'elements' => '이미지 붙여넣기 · 엑셀 업로드 · 프롬프트 예시 · 내 파일', 'menu' => '01-04-01-02', 'ux' => '각 카드 클릭 → 해당 입력 모드'),
        'M-05' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '팁 + 예시 태그', 'elements' => '구체적 입력 팁 · 5개 예시 태그 · 더 많은 예시 보기', 'menu' => '—', 'ux' => '태그 클릭 → 프롬프트 자동 입력'),
        'R-01' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '최근 디자인 히스토리', 'elements' => '썸네일·제목·규격·재질·수정일 3건 · 전체 보기', 'menu' => '01-04', 'ux' => '항목 클릭 → 편집기 · 전체 보기 → 목록'),
        'R-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'AI 디자인 팁', 'elements' => '상세 설명 · 참고 이미지 · 엑셀 · 편집 자유 (4항목) · 더 알아보기', 'menu' => '—', 'ux' => '정적 안내'),
        'B-01' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '프로세스 스텝퍼', 'elements' => '입력/업로드 · AI 생성 · 편집 · 다운로드/인쇄 (4단계)', 'menu' => '01-04-01-02', 'ux' => '현재 단계 highlight · Desktop: 하단 고정'),
    );
}
