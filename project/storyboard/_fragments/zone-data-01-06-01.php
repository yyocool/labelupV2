<?php
/**
 * Zone 데이터: 마이페이지 — 내 정보 (01-06-01)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '글로벌 사이드바', 'elements' => '홈 · 템플릿 · 용지/라벨 · AI디자인 · 내디자인 · 주문내역 · 브랜드관리 · 하단: 가이드·마이페이지(active)·고객센터·설정', 'menu' => '01-06', 'ux' => '마이페이지 active · Mobile: 드로어'),
        'L-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '보유 포인트', 'elements' => '12,450 P · 포인트 충전', 'menu' => '01-06', 'ux' => '사이드바 하단 고정'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 바 · 서브탭', 'elements' => '검색·장바구니·알림·프로필 + 내 정보(active)/주문·배송/결제·구독/디자인 관리 탭', 'menu' => '01-06-01', 'ux' => '탭 전환 → 각 마이페이지 하위 화면'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '기본 정보 폼', 'elements' => '이름 · 이메일 · 연락처 · 회사명 · 저장/취소', 'menu' => '01-06-01', 'ux' => '인라인 검증 · 저장 시 토스트'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '비밀번호 변경', 'elements' => '현재 비밀번호 · 새 비밀번호 · 확인 · 변경 CTA', 'menu' => '01-06-01', 'ux' => '소셜 로그인 계정은 비활성 처리'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '회원 탈퇴 안내', 'elements' => '탈퇴 시 유의사항 · 회원 탈퇴 신청 버튼', 'menu' => '01-06-01', 'ux' => '탈퇴 확인 모달 연계'),
    );
}
