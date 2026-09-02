<?php
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · HOME active', 'menu' => '01 전역', 'ux' => '1depth 이동 · Mobile: 드로어'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => 'HOME · 메인 · 서비스 소개(active)', 'menu' => '01-04', 'ux' => 'HOME 그룹 · 서비스 소개 강조'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 바', 'elements' => '브레드크럼 · 검색 · 로그인/시작하기', 'menu' => '01-04-02', 'ux' => '비로그인 CTA · 로그인 시 프로필'),
        'M-02' => array('type' => 'CTA', 'typeKey' => 'cta', 'block' => '히어로', 'elements' => '카피 · 서브 · 시작하기 · 데모보기', 'menu' => '01-04-02', 'ux' => '→ 회원가입/편집기 · 데모 스크롤'),
        'M-03' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '섹션 앵커', 'elements' => '특징 · 사용방법 · 요금 · 기업', 'menu' => '01-04-02', 'ux' => '앵커 스크롤 · 하위 상세(01-04-02-*)'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '서비스 특징', 'elements' => '카드 5종 · 편집기·AI·템플릿·쇼핑·인쇄', 'menu' => '01-04-02-01', 'ux' => '카드 클릭 → 상세/관련 화면'),
        'M-05' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '사용 방법', 'elements' => '4단계 스텝 · 규격→디자인→출력→인쇄', 'menu' => '01-04-02-02', 'ux' => '단계 번호 · 자세히 보기'),
        'M-06' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '요금 안내', 'elements' => 'Free / Pro 비교 · 업그레이드', 'menu' => '01-04-02-03', 'ux' => '→ 결제/구독 · 요금 상세'),
        'M-07' => array('type' => 'CTA', 'typeKey' => 'cta', 'block' => '기업 고객', 'elements' => 'B2B 카피 · 문의하기 · 원격지원', 'menu' => '01-04-02-04', 'ux' => '→ 문의 · 기업 안내 상세'),
        'M-08' => array('type' => 'CTA', 'typeKey' => 'cta', 'block' => '하단 CTA', 'elements' => '무료로 시작 · 템플릿 둘러보기', 'menu' => '01-01 / 01-05', 'ux' => '가입 · 템플릿 갤러리'),
    );
}
