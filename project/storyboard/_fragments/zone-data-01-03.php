<?php
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '홈·라벨디자인·템플릿·규격검색·쇼핑몰·맞춤서비스·자료실·고객센터 (8개 아이콘)', 'menu' => '01 하위 전역', 'ux' => '로그인 전에도 노출 · 클릭 시 해당 섹션(로그인 유도)'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '로고 · 홈 · AI추천받기 · 라벨디자인 · 템플릿 · 규격검색 · 쇼핑몰 · 이벤트 · 프로젝트 · 마이페이지 · 고객센터', 'menu' => '01 전역', 'ux' => 'Desktop: 항상 노출 · Mobile: 햄버거 드로어'),
        'M-01' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '페이지 헤더', 'elements' => 'H1 "아이디 / 비밀번호 찾기" · 설명 문구 · 우측 정적 일러스트(자물쇠+스마트폰)', 'menu' => '01-03', 'ux' => '정적 이미지 · 클릭 동작 없음'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '탭', 'elements' => '아이디 찾기 · 비밀번호 찾기 (2탭)', 'menu' => '01-03', 'ux' => '탭 전환 시 하단 스텝 UI 동일(플로우만 문구 차이) · URL hash 또는 query'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'Step 01 — 정보 입력', 'elements' => '이름 · 휴대전화(010 prefix + 번호) · 다음 버튼', 'menu' => '01-03', 'ux' => '유효성 검사 후 Step 02로 · API: POST /auth/find/send-code'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'Step 02 — 인증번호 발송', 'elements' => '마스킹된 번호 표시 · 3분 유효 안내 · 재발송(59s 쿨다운)', 'menu' => '01-03', 'ux' => '자동 Step 03 전환 · 재발송 API'),
        'M-05' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'Step 03 — 인증번호 입력', 'elements' => '6자리 OTP 박스 · 타이머 02:45 · 확인 버튼', 'menu' => '01-03', 'ux' => '6자리 입력 시 자동 포커스 이동 · 만료 시 재발송 유도'),
        'M-06' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'Step 04 — 본인 확인 완료', 'elements' => '완료 메시지 · 아이디 확인 버튼(아이디 찾기) / 비밀번호 재설정 진입(비밀번호 찾기)', 'menu' => '01-03', 'ux' => '아이디 찾기: 마스킹 ID 표시 모달 · 비밀번호: Step 05로'),
        'M-07' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'Step 05 — 새 비밀번호', 'elements' => '새 비밀번호 · 확인 · 복잡도 규칙 · 비밀번호 변경 버튼', 'menu' => '01-03', 'ux' => '👁 표시 토글 · 실시간 규칙 검증'),
        'M-08' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'Step 06 — 변경 완료', 'elements' => '완료 메시지 · 로그인 하러 가기 · 홈으로 이동', 'menu' => '01-03', 'ux' => '로그인 → 01-02 · 홈 → 01-01'),
        'R-01' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '보안 안내', 'elements' => '휴대폰 본인인증 · 인증번호 보안 · 비밀번호 보안 · 정기 변경 권장 (4항목)', 'menu' => '—', 'ux' => '정적 안내 · Desktop: 우측 고정'),
        'R-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '이용 방법 + CS', 'elements' => '아이디 찾기 요약 · 비밀번호 찾기 요약 · 고객센터 바로가기', 'menu' => '01-12 (고객센터)', 'ux' => '고객센터 버튼 → CS 페이지'),
        'B-01' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '하단 TIP', 'elements' => '휴대전화 번호 변경 시 마이페이지 > 회원정보 안내', 'menu' => '01-04', 'ux' => '전체 너비 배너 · Mobile: 줄바꿈'),
    );
}
