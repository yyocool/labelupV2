<?php
/**
 * Label-UP 메뉴 구성도 시드 데이터 (v2 — 간소화 IA)
 * 1) 사용자 페이지: 라벨편집기 · 쇼핑몰 · 마이페이지 중심
 * 2) Backoffice: 관리자 업무 흐름 중심
 */
return array(
    array(
        'title' => '사용자 페이지 (Front)',
        'children' => array(
            array('title' => 'HOME', 'children' => array(
                array('title' => '메인'),
                array('title' => '서비스 소개'),
            )),
            array('title' => '라벨 편집기', 'children' => array(
                array('title' => '디자인', 'children' => array(
                    array('title' => '새 디자인 만들기'),
                    array('title' => '내 디자인'),
                )),
                array('title' => '템플릿', 'children' => array(
                    array('title' => '무료 템플릿'),
                    array('title' => '프리미엄 템플릿'),
                    array('title' => '공유 템플릿'),
                )),
                array('title' => '규격 검색'),
                array('title' => '편집 도구', 'children' => array(
                    array('title' => '디자인 편집'),
                    array('title' => '바코드·QR'),
                    array('title' => '데이터 연동'),
                    array('title' => '출력·저장'),
                )),
                array('title' => 'AI 디자인 생성'),
                array('title' => '자료실', 'children' => array(
                    array('title' => '바코드 생성기'),
                    array('title' => '디자인 자료'),
                    array('title' => '가이드'),
                )),
            )),
            array('title' => '쇼핑몰', 'children' => array(
                array('title' => '라벨·스티커'),
                array('title' => '프린터·소모품'),
                array('title' => '인쇄 의뢰'),
            )),
            array('title' => '맞춤 제작', 'children' => array(
                array('title' => '맞춤 견적'),
                array('title' => '디자인 의뢰'),
                array('title' => '제작 진행현황'),
            )),
            array('title' => '고객센터', 'children' => array(
                array('title' => '공지사항'),
                array('title' => 'FAQ'),
                array('title' => '문의하기'),
                array('title' => '원격지원'),
            )),
            array('title' => '마이페이지', 'children' => array(
                array('title' => '내 정보'),
                array('title' => '주문·배송'),
                array('title' => '결제·구독'),
                array('title' => '디자인 관리'),
            )),
        ),
    ),
    array(
        'title' => 'Backoffice (관리자)',
        'children' => array(
            array('title' => '대시보드', 'children' => array(
                array('title' => '실시간 현황'),
                array('title' => '매출 통계'),
                array('title' => '포인트 관리'),
            )),
            array('title' => '경쟁서비스 분석', 'children' => array(
                array('title' => '경쟁사 상세·SWOT'),
                array('title' => 'Label-UP 전략 방향'),
            )),
            array('title' => '정책관리', 'children' => array(
                array('title' => '정책 목록'),
                array('title' => '정책 등록'),
                array('title' => '버전·상태 관리'),
            )),
            array('title' => '회원관리', 'children' => array(
                array('title' => '회원목록'),
                array('title' => '회원등급'),
            )),
            array('title' => '쇼핑몰 관리', 'children' => array(
                array('title' => '상품관리'),
                array('title' => '카테고리 관리'),
                array('title' => '재고관리'),
            )),
            array('title' => '주문관리', 'children' => array(
                array('title' => '주문목록'),
                array('title' => '환불·취소'),
            )),
            array('title' => '디자인 관리', 'children' => array(
                array('title' => '템플릿 관리'),
                array('title' => '사용자 디자인'),
            )),
            array('title' => '규격 관리'),
            array('title' => 'AI 관리', 'children' => array(
                array('title' => '프롬프트 관리'),
                array('title' => '사용량 통계'),
            )),
            array('title' => '콘텐츠 관리', 'children' => array(
                array('title' => '공지사항'),
                array('title' => 'FAQ'),
                array('title' => '배너관리'),
            )),
            array('title' => '통계·정산', 'children' => array(
                array('title' => '매출통계'),
                array('title' => 'PG 정산'),
            )),
        ),
    ),
);
