<?php
/**
 * Zone 데이터: Label-UP 도움말 (01-05-07)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '홈·편집·템플릿·규격·인쇄·맞춤·자료·고객센터', 'menu' => '01 전역', 'ux' => '전역 네비'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '로고 · 메뉴 · 포인트', 'menu' => '01 전역', 'ux' => '라벨 디자인 맥락'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '도움말 헤더', 'elements' => '제목 · 검색 · 닫기', 'menu' => '01-05-07', 'ux' => 'ESC / ✕ 닫기 → 편집기 복귀'),
        'M-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '카테고리 탭', 'elements' => '시작하기 · 편집 · AI · 출력 · 단축키 · FAQ', 'menu' => '01-05-07', 'ux' => '탭 전환 · 해시 딥링크'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '본문 가이드', 'elements' => '섹션 카드 · 스텝 · 스크린샷 자리 · 관련 링크', 'menu' => '01-05-07', 'ux' => '스크롤 · 앵커'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '단축키 표', 'elements' => '도구·캔버스·저장 단축키', 'menu' => '01-05-07', 'ux' => '복사 가능'),
        'R-01' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '빠른 링크', 'elements' => '편집기 복귀 · AI 디자인 · 고객센터 · 원격지원', 'menu' => '01-05 / 01-05-05 / 01-07', 'ux' => 'CTA'),
        'R-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '문의 배너', 'elements' => '1:1 문의 · FAQ · 운영시간', 'menu' => '01-07', 'ux' => '→ 고객센터'),
    );
}
