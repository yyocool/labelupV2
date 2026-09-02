<?php
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · 라벨디자인 active', 'menu' => '01 전역', 'ux' => ''),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '로고 · 홈~고객센터', 'menu' => '01 전역', 'ux' => ''),
        'L-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '보유 포인트', 'elements' => '12,450 P · 충전/내역 보기', 'menu' => '01-04', 'ux' => ''),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 바', 'elements' => '브레드크럼 › 일반 모드 · 검색 · 알림 · 프로필', 'menu' => '01-04-01', 'ux' => ''),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '페이지 헤더', 'elements' => '일반 모드 · "단계별로 꼼꼼하게" 배지 · 설명', 'menu' => '01-04-01-01', 'ux' => ''),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '단계 스텝퍼', 'elements' => '라벨 정보 입력(active) · 디자인 설정 · 미리보기 · 다운로드/인쇄', 'menu' => '01-04-01-01', 'ux' => '단계 클릭 → 해당 단계(완료된 경우)'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'Step1 — 라벨 정보 폼', 'elements' => '용도 · 규격 · 텍스트 · 소재 · 색상 · 추가정보 · 참고이미지 업로드', 'menu' => '01-04-01-01', 'ux' => '유효성 검사 · 규격 검색 모달 · 이미지 드래그앤드롭'),
        'M-05' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '실시간 미리보기', 'elements' => '라벨 프리뷰 · 초기화 · 줌 ± · 가이드 보기', 'menu' => '01-04-01-01', 'ux' => '입력 변경 시 실시간 반영'),
        'M-06' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'Step1 — 디자인 톤', 'elements' => '모던/심플 · 내추럴 · 빈티지 · 고급 · 귀여운 · 강렬/팝 (6카드)', 'menu' => '01-04-01-01', 'ux' => '단일 선택 · 더보기 → 전체 톤 목록'),
        'M-07' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '다음 단계 CTA', 'elements' => '"다음 단계: 디자인 설정 →" 버튼', 'menu' => '01-04-01-01', 'ux' => '필수 입력 완료 시 활성 · Step 2 이동'),
        'R-01' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '최근 히스토리', 'elements' => '썸네일·제목·규격·재질·날짜 · 전체 보기', 'menu' => '01-04', 'ux' => ''),
        'R-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'AI 디자인 팁', 'elements' => '정확한 정보 · 참고 이미지 · 톤 선택 · 미리보기 확인 (4항목)', 'menu' => '—', 'ux' => ''),
    );
}
