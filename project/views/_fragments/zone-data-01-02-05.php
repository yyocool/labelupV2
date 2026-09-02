<?php

if (!isset($sbZoneDataMap)) {

    $sbZoneDataMap = array(

        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · 라벨디자인 active', 'menu' => '01 전역', 'ux' => '클릭 시 1depth 메뉴 이동 · Mobile: 드로어'),

        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '로고 · 홈~고객센터', 'menu' => '01 전역', 'ux' => '라벨 디자인 active · 2depth 메뉴'),

        'L-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '보유 포인트', 'elements' => '12,450 P · 충전/내역 보기', 'menu' => '01-04', 'ux' => '사이드바 하단 고정'),

        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 바', 'elements' => '브레드크럼 › 심플 모드 · 검색(⌘K) · 알림 · 프로필', 'menu' => '01-02-05', 'ux' => '브레드크럼 → AI 라벨 디자인 상위'),

        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '모드 선택', 'elements' => '일반 모드 · 심플 모드(active) · 새 대화 · 사용 예시 · 도움말', 'menu' => '01-02-05', 'ux' => '탭 전환 · 일반 모드 → 단계별 화면'),

        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'AI 대화', 'elements' => '안내 문구 · 사용자 프롬프트 말풍선', 'menu' => '01-02-05', 'ux' => '대화 히스토리 스크롤'),

        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'AI 추천 결과', 'elements' => '레이아웃 4종 · 규격 3종 · 디자인 미리보기 · 선택 체크', 'menu' => '01-02-05', 'ux' => '카드 클릭 → 옵션 변경 · 실시간 우측 프리뷰 반영'),

        'M-05' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '선택 옵션 요약', 'elements' => '레이아웃 · 규격 · 용지 · 방향', 'menu' => '01-02-05', 'ux' => '선택값 한 줄 요약'),

        'M-06' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '입력 영역', 'elements' => '이미지/엑셀/프롬프트 퀵버튼 · textarea · 전송', 'menu' => '01-02-05', 'ux' => 'Ctrl+V 이미지 · Enter 전송'),

        'R-01' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '작업 중인 라벨', 'elements' => '라벨 프리뷰 · 줌 · 편집기로 보내기 CTA', 'menu' => '01-02-05', 'ux' => 'CTA → 라벨 편집기(01-02)'),

        'R-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '추천 근거', 'elements' => 'AI 선택 이유 4항목', 'menu' => '—', 'ux' => '정적 안내'),

        'R-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '대화 히스토리', 'elements' => '최근 대화 3건 · 시간 · 더보기', 'menu' => '01-02-05', 'ux' => '항목 클릭 → 대화 복원'),

    );

}

