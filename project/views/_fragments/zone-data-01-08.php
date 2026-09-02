<?php
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '쇼핑몰 사이드바', 'elements' => '홈(active) · 템플릿 · 용지/라벨 · AI디자인 · 내디자인 · 주문내역 · 브랜드관리 · 하단 유틸', 'menu' => '01-08', 'ux' => '쇼핑몰 LNB · Mobile: 드로어'),
        'L-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '보유 포인트', 'elements' => '12,450 P · 포인트 충전', 'menu' => '01-06', 'ux' => '사이드바 하단 고정'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 바', 'elements' => '로고 · 검색 · 장바구니(2) · 프로필', 'menu' => '01-08', 'ux' => '통합 검색 · 장바구니 드롭다운'),
        'M-02' => array('type' => 'CTA', 'typeKey' => 'cta', 'block' => '히어로 배너', 'elements' => '헤드라인 · 라벨 검색/규격 찾기 CTA · 제품 콜라주 · 슬라이더', 'menu' => '01-08', 'ux' => '자동 슬라이드 · 배너 클릭 → 카테고리'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '카테고리 아이콘', 'elements' => 'A4·재질별·방수·투명·원형·사각·바코드 등 10종', 'menu' => '01-08', 'ux' => '아이콘 클릭 → 필터된 상품 목록'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '자주 찾는 라벨 규격', 'elements' => '가로 스크롤 카드 · A4 24칸 등 · 전체 보기', 'menu' => '01-08-01', 'ux' => '카드 클릭 → 규격 상세'),
        'M-05' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '재질별 추천 라벨', 'elements' => '유광/무광/투명 PET 등 · 전체 보기', 'menu' => '01-08-01', 'ux' => '카드 클릭 → 상품 상세'),
        'R-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '용도별 추천', 'elements' => '제품·포장·바코드·보안·파일 라벨 5항목', 'menu' => '01-08', 'ux' => '클릭 → 용도별 큐레이션'),
        'R-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '도움말', 'elements' => '규격 가이드 · 용지 샘플 · 인쇄 가이드', 'menu' => '01-08', 'ux' => '→ 가이드/자료실'),
        'R-03' => array('type' => 'CTA', 'typeKey' => 'cta', 'block' => '에디터 프로모', 'elements' => '라벨 편집하기 CTA · 에디터 UI 일러스트', 'menu' => '01-02', 'ux' => '→ 라벨 편집기'),
    );
}
