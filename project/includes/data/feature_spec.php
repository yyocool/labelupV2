<?php
/**
 * Label-UP 기능 명세표 데이터
 * 기준 화면: 스토리보드 01-05 (라벨 디자인 편집기) + Backoffice 전체화면
 */
return array(
    'meta' => array(
        'version' => '1.2',
        'updated' => '2026-07-15',
        'basis' => '스토리보드 01-05 프로토타입 · 와이어프레임 기준',
        'owner' => '기획/UX',
        'status' => '프로토타입 명세 (개발 확정 전)',
        'primary_screen' => '01-05 라벨 디자인 편집기',
        'related' => array(
            array('label' => '스토리보드', 'url' => 'storyboard.php'),
            array('label' => '경쟁서비스 분석', 'url' => 'competitive-analysis.php'),
            array('label' => '요금정책분석', 'url' => 'pricing-analysis.php'),
        ),
    ),

    'summary' => array(
        'title' => '문서 개요',
        'text' => '본 명세는 Label-UP 라벨 디자인 편집기(스토리보드 01-05)와 Backoffice 스토리보드 전체화면 기능까지, 프로토타입으로 확정된 UX/UI를 기능 단위로 정리한 문서입니다. 영역 ID(Zone) · 행동 · 입출력 · 프로토타입 상태를 표로 기술하며, 실제 API/DB 설계는 개발 단계에서 세분화합니다.',
        'goals' => array(
            '편집기 핵심 인터랙션(도구·캔버스·속성·미리보기·가져오기)을 단일 문서에서 추적',
            '기획·디자인·개발이 동일한 Zone ID / 기능 ID로 커뮤니케이션',
            '프로토타입 완성 범위와 개발 이관 시 우선순위를 명확화',
        ),
    ),

    'glossary' => array(
        array('term' => 'Zone ID', 'desc' => '와이어프레임 영역 식별자 (M-01, R-02, I-01 등). 전체화면에서 영역 라벨 클릭 시 상세 표시.'),
        array('term' => '프로토타입 모드', 'desc' => '편집기 내 ▶ 프로토타입 시작 후 버튼·토스트·모달 인터랙션이 동작하는 모드.'),
        array('term' => '아트보드', 'desc' => '실제 라벨 한 장의 편집 영역. 객체(텍스트·이미지·도형 등)를 배치.'),
        array('term' => '라벨복사', 'desc' => '선택 라벨의 디자인을 시트 내 다른 칸·행·열·페이지로 복제하는 기능.'),
        array('term' => '마스터', 'desc' => '시트 전체 라벨의 공통 디자인을 관리하는 레이어/모드.'),
    ),

    'screens' => array(
        array(
            'id' => 'SCR-01',
            'name' => '라벨 디자인 편집기',
            'code' => '01-05',
            'url' => '/editor/design/{id}',
            'auth' => '로그인 사용자 · 내 디자인 또는 새 디자인',
            'layout' => '상단바 + 캔버스(플로팅 도구) + 속성/미리보기 플로팅 패널 + 레이어·가져오기·데이터 팝업',
            'purpose' => '라벨 시안 편집 · 템플릿/에셋 적용 · 데이터 연동 · 인쇄 미리보기',
        ),
        array(
            'id' => 'SCR-02',
            'name' => '인쇄 미리보기 모달',
            'code' => '01-05 / P-01~04',
            'url' => '(모달)',
            'auth' => '편집기 동일',
            'layout' => '상단바 · 좌측 용지설정 · 중앙 시트 그리드 · 우측 레이어/옵션',
            'purpose' => '실제 인쇄 시트 배치 확인 · 용지/레이아웃 조정 · 출력 진입',
        ),
        array(
            'id' => 'SCR-03',
            'name' => 'Backoffice 스토리보드',
            'code' => '02 ~ 02-09-*',
            'url' => '/admin (스토리보드 문서)',
            'auth' => '관리자·운영자 롤',
            'layout' => 'LNB + 상단바 + 모듈별 목업 · 전체화면 와이어프레임',
            'purpose' => '관리자 모듈 UI 명세 · 전체화면에서 메뉴 전환',
        ),
    ),

    /* ── 영역(Zone) 총괄 ── */
    'zones' => array(
        array('id' => 'M-01', 'type' => 'Nav', 'block' => '상단 바', 'elements' => '제목(편집) · 저장 상태 · 실행취소/다시 · 줌 · 그리드 · 저장하기 · 미리보기 · 편집기에서 출력', 'priority' => 'P0', 'proto' => '완료'),
        array('id' => 'M-02', 'type' => 'UI', 'block' => '캔버스', 'elements' => '눈금자 · 아트보드 · 캔버스 객체 레이어 · 선택/드래그', 'priority' => 'P0', 'proto' => '완료'),
        array('id' => 'M-03', 'type' => 'UI', 'block' => '플로팅 툴바', 'elements' => '텍스트~바코드 · 마스터·데이터 · 레이어 · 드래그·모서리 스냅·가로/세로', 'priority' => 'P0', 'proto' => '완료'),
        array('id' => 'M-04', 'type' => 'UI', 'block' => '선택 플로트 바', 'elements' => '정렬 · 간격 · 크기 · 그룹 · 잠금 · 복제 · 삭제', 'priority' => 'P1', 'proto' => '프로토타입 토스트'),
        array('id' => 'S-00', 'type' => 'UI', 'block' => '에셋 슬라이드', 'elements' => '배경·템플릿·클립아트·아이콘 · 검색·태그·그리드', 'priority' => 'P0', 'proto' => '완료'),
        array('id' => 'R-01', 'type' => 'UI', 'block' => '속성 패널', 'elements' => '속성/레이어 탭 · 드래그·접기·자석 스냅', 'priority' => 'P0', 'proto' => '완료'),
        array('id' => 'R-02', 'type' => 'UI', 'block' => '미리보기 패널', 'elements' => '규격코드 · 2×3 시트 · 페이지 · 라벨복사 메뉴 · 인쇄수량 · 토글', 'priority' => 'P0', 'proto' => '완료'),
        array('id' => 'I-01', 'type' => 'UI', 'block' => '가져오기 팝업', 'elements' => '라벨·태그·템플릿·스마트·외부포맷·내디자인', 'priority' => 'P0', 'proto' => '완료'),
        array('id' => 'D-01', 'type' => 'UI', 'block' => '데이터 가져오기', 'elements' => '엑셀·CSV·아이라벨·폼택 · 파일 첨부', 'priority' => 'P0', 'proto' => '완료'),
        array('id' => 'Q-01~05', 'type' => 'UI', 'block' => '레이어 팝업', 'elements' => '라벨·태그·템플릿·나의디자인·AI', 'priority' => 'P1', 'proto' => '완료'),
        array('id' => 'P-01~04', 'type' => 'UI', 'block' => '인쇄 프리뷰', 'elements' => '용지설정 · 시트 그리드 · 레이어/옵션', 'priority' => 'P0', 'proto' => '완료'),
        array('id' => 'L-01/T-01', 'type' => 'Nav', 'block' => 'Backoffice 크롬', 'elements' => '관리자 LNB · 상단바 · 전체화면 보기', 'priority' => 'P1', 'proto' => '완료'),
    ),

    /* ── 기능 명세 모듈 ── */
    'modules' => array(

        array(
            'id' => 'MOD-TOP',
            'title' => '1. 상단 바 (M-01)',
            'desc' => '디자인 파일 식별·저장·줌·미리보기·출력 진입점.',
            'features' => array(
                array('fid' => 'F-TOP-01', 'name' => '제목 편집', 'desc' => '좌측 제목을 인라인 input으로 수정. Enter 또는 blur로 확정.', 'input' => '텍스트(최대 80자)', 'output' => '제목 반영 · 미리보기 모달 제목 동기화', 'ux' => '변경 시 저장 상태 → 「저장 필요」', 'status' => '프로토타입'),
                array('fid' => 'F-TOP-02', 'name' => '저장 상태 표시', 'desc' => '저장됨(녹) / 저장 필요(주황) / 저장 중…(보라) 3상태.', 'input' => '편집·저장 이벤트', 'output' => '상태 문구·색상', 'ux' => '시각적 피드백', 'status' => '프로토타입'),
                array('fid' => 'F-TOP-03', 'name' => '저장하기', 'desc' => '미리보기 버튼 좌측에 배치. 클릭 시 저장 시뮬레이션.', 'input' => '버튼 클릭', 'output' => '토스트 + 저장됨 상태', 'ux' => '미리보기 좌측', 'status' => '프로토타입'),
                array('fid' => 'F-TOP-04', 'name' => '실행 취소 / 다시 실행', 'desc' => '프로토타입 히스토리 스택 기반 undo/redo.', 'input' => '버튼', 'output' => '토스트', 'ux' => '히스토리 없을 때 안내', 'status' => '프로토타입'),
                array('fid' => 'F-TOP-05', 'name' => '줌 · 화면 맞춤 · 그리드', 'desc' => '줌 ±10% (50~200%) · 맞춤 100% · 캔버스 그리드 토글.', 'input' => '버튼', 'output' => '줌 라벨 · 그리드 오버레이', 'ux' => '그리드 활성 시 툴칩 강조', 'status' => '프로토타입'),
                array('fid' => 'F-TOP-06', 'name' => '미리보기', 'desc' => '인쇄 미리보기 모달(P-01~04) 오픈.', 'input' => '버튼', 'output' => '모달 오버레이', 'ux' => 'ESC·배경·X 닫기', 'status' => '프로토타입'),
                array('fid' => 'F-TOP-07', 'name' => '편집기에서 출력', 'desc' => '인쇄 주문·PDF 등 출력 플로우 진입(모달 안내).', 'input' => '버튼', 'output' => '프로토타입 모달', 'ux' => 'Primary CTA', 'status' => '프로토타입'),
            ),
        ),

        array(
            'id' => 'MOD-CANVAS',
            'title' => '2. 캔버스 · 객체 (M-02)',
            'desc' => '라벨 아트보드 위 객체를 추가·선택·이동·편집.',
            'features' => array(
                array('fid' => 'F-CV-01', 'name' => '아트보드 표시', 'desc' => '라벨 베이스 디자인(브랜드·타이틀 등) + 객체 레이어.', 'input' => '—', 'output' => '아트보드 DOM', 'ux' => '눈금자 H/V', 'status' => '프로토타입'),
                array('fid' => 'F-CV-02', 'name' => '객체 즉시 추가', 'desc' => '텍스트·이미지·도형·표·바코드를 툴바 클릭 즉시 캔버스에 추가.', 'input' => '툴바 proto', 'output' => '캔버스 객체', 'ux' => '선택 상태·드래그 가능', 'status' => '프로토타입'),
                array('fid' => 'F-CV-03', 'name' => '객체 선택·드래그', 'desc' => '클릭 선택, 드래그로 위치 이동.', 'input' => '포인터', 'output' => '선택 핸들', 'ux' => '텍스트는 더블클릭 편집', 'status' => '프로토타입'),
                array('fid' => 'F-CV-04', 'name' => '레이어 목록 연동', 'desc' => '속성 패널 레이어 탭에 객체 목록 표시(프로토타입).', 'input' => '객체 추가/선택', 'output' => '레이어 리스트', 'ux' => 'R-01 레이어 탭', 'status' => '프로토타입'),
            ),
        ),

        array(
            'id' => 'MOD-FLOAT',
            'title' => '3. 플로팅 툴바 (M-03)',
            'desc' => '캔버스 위에 떠 있는 편집 도구. 드래그·모서리 스냅·가로/세로 배치.',
            'features' => array(
                array('fid' => 'F-FT-01', 'name' => '도구 그룹 A — 요소', 'desc' => '텍스트 · 이미지 · 배경 · 템플릿 · 클립아트 · 아이콘 · 도형 · 표 · 바·QR코드', 'input' => '클릭', 'output' => '즉시 추가 또는 S-00 오픈', 'ux' => '배경/템플릿/클립아트/아이콘 → 슬라이드', 'status' => '프로토타입'),
                array('fid' => 'F-FT-02', 'name' => '도구 그룹 B — 데이터', 'desc' => '마스터 · 데이터', 'input' => '클릭', 'output' => '마스터 토스트 / D-01 팝업', 'ux' => '데이터 → 가져오기 팝업', 'status' => '프로토타입'),
                array('fid' => 'F-FT-03', 'name' => '도구 그룹 C — 레이어', 'desc' => '레이어 버튼 → 속성 패널 레이어 탭 전환', 'input' => '클릭', 'output' => 'R-01 탭 전환', 'ux' => '—', 'status' => '프로토타입'),
                array('fid' => 'F-FT-04', 'name' => '드래그 이동', 'desc' => '그립(⋮⋮)으로 툴바 이동. 드래그 중/놓을 때 자석 스냅.', 'input' => '드래그', 'output' => '위치 저장(localStorage)', 'ux' => '민감도: 드래그 14px · 해제 22px', 'status' => '프로토타입'),
                array('fid' => 'F-FT-05', 'name' => '모서리·배치 설정', 'desc' => '⌖ 메뉴에서 좌상/우상/좌하/우하 · 가로/세로 선택.', 'input' => '메뉴', 'output' => '코너·오리엔테이션 적용', 'ux' => 'localStorage 유지', 'status' => '프로토타입'),
            ),
            'tools' => array(
                array('tool' => '텍스트', 'action' => '캔버스에 텍스트 객체 추가', 'type' => '즉시'),
                array('tool' => '이미지', 'action' => '이미지 플레이스홀더 추가', 'type' => '즉시'),
                array('tool' => '배경', 'action' => '좌측 에셋 슬라이드(배경) 토글', 'type' => '슬라이드'),
                array('tool' => '템플릿', 'action' => '좌측 에셋 슬라이드(템플릿) 토글', 'type' => '슬라이드'),
                array('tool' => '클립아트', 'action' => '좌측 에셋 슬라이드(클립아트) 토글', 'type' => '슬라이드'),
                array('tool' => '아이콘', 'action' => '좌측 에셋 슬라이드(아이콘) 토글', 'type' => '슬라이드'),
                array('tool' => '도형', 'action' => '도형 객체 추가', 'type' => '즉시'),
                array('tool' => '표', 'action' => '표 객체 추가', 'type' => '즉시'),
                array('tool' => '바·QR코드', 'action' => '바코드/QR 객체 추가', 'type' => '즉시'),
                array('tool' => '마스터', 'action' => '마스터 레이어 편집 안내', 'type' => '토스트'),
                array('tool' => '데이터', 'action' => '데이터 가져오기 팝업(D-01)', 'type' => '팝업'),
                array('tool' => '레이어', 'action' => '속성 패널 → 레이어 탭', 'type' => '패널'),
            ),
        ),

        array(
            'id' => 'MOD-ASSET',
            'title' => '4. 에셋 슬라이드 패널 (S-00)',
            'desc' => '배경·템플릿·클립아트·아이콘을 좌측 300px 슬라이드에서 검색·필터·선택.',
            'features' => array(
                array('fid' => 'F-AS-01', 'name' => '슬라이드 오픈/닫기', 'desc' => '해당 툴바 버튼 토글. X · › · ESC · 재클릭으로 닫기.', 'input' => '툴바/버튼', 'output' => '슬라이드 is-open', 'ux' => '활성 툴 빨간 강조', 'status' => '프로토타입'),
                array('fid' => 'F-AS-02', 'name' => '검색 · 태그 필터', 'desc' => '키워드 검색 및 태그 칩으로 목록 필터.', 'input' => '검색어·태그', 'output' => '필터된 그리드', 'ux' => '카테고리별 데이터', 'status' => '프로토타입'),
                array('fid' => 'F-AS-03', 'name' => '에셋 선택', 'desc' => '카드 클릭 시 적용 토스트(프로토타입).', 'input' => '카드 클릭', 'output' => '토스트', 'ux' => '실서비스: 캔버스 반영', 'status' => '프로토타입'),
            ),
        ),

        array(
            'id' => 'MOD-PROPS',
            'title' => '5. 속성 · 레이어 패널 (R-01)',
            'desc' => '선택 객체 속성 편집 및 레이어 목록. 플로팅 카드 · 드래그·접기·모서리 스냅.',
            'features' => array(
                array('fid' => 'F-PR-01', 'name' => '속성 / 레이어 탭', 'desc' => '탭 전환으로 속성 폼 또는 레이어 목록 표시.', 'input' => '탭', 'output' => '패널 전환', 'ux' => '—', 'status' => '프로토타입'),
                array('fid' => 'F-PR-02', 'name' => '속성 필드', 'desc' => '사이즈 · 텍스트 · 채우기 · 테두리 · 그림자 등 폼 목업.', 'input' => '폼', 'output' => '토스트(프로토)', 'ux' => '선택 객체 연동 예정', 'status' => '프로토타입'),
                array('fid' => 'F-PR-03', 'name' => '드래그 · 접기 · 스냅', 'desc' => '헤더 드래그 · 최소화 · 4모서리 자석 스냅.', 'input' => '포인터', 'output' => '위치 localStorage', 'ux' => '미리보기 패널 스택 연동', 'status' => '프로토타입'),
            ),
        ),

        array(
            'id' => 'MOD-PREVIEW',
            'title' => '6. 미리보기 카드 (R-02)',
            'desc' => '인쇄 시트 미니 프리뷰 · 페이지 · 라벨복사 · 인쇄수량 · 미리보기 토글.',
            'features' => array(
                array('fid' => 'F-PV-01', 'name' => '규격코드 표시', 'desc' => '예: TLF0061 (86.5×86.5 mm)', 'input' => '규격 선택', 'output' => '헤더 텍스트', 'ux' => '중앙 정렬', 'status' => '프로토타입'),
                array('fid' => 'F-PV-02', 'name' => '2×3 시트 그리드', 'desc' => '6칸 라벨 미니뷰. 선택 칸 빨간 테두리.', 'input' => '셀 클릭', 'output' => '선택 상태', 'ux' => 'QR·Label Space 목업', 'status' => '프로토타입'),
                array('fid' => 'F-PV-03', 'name' => '페이지 네비', 'desc' => '« ‹ 페이지 n / N › »', 'input' => '버튼', 'output' => '페이지 라벨', 'ux' => '끝에서 비활성', 'status' => '프로토타입'),
                array('fid' => 'F-PV-04', 'name' => '라벨복사 메뉴', 'desc' => '라벨복사 클릭 시 드롭다운 메뉴.', 'input' => '버튼', 'output' => '메뉴 · 항목별 토스트', 'ux' => '바깥/ESC 닫기', 'status' => '프로토타입'),
                array('fid' => 'F-PV-05', 'name' => '삭제 · 추가', 'desc' => '페이지/라벨 삭제·추가(프로토타입 토스트).', 'input' => '버튼', 'output' => '토스트', 'ux' => '—', 'status' => '프로토타입'),
                array('fid' => 'F-PV-06', 'name' => '인쇄수량 · 미리보기 토글', 'desc' => '수량 number input · 미리보기 ON/OFF 토글.', 'input' => 'input · toggle', 'output' => '토스트', 'ux' => '하단 구분선', 'status' => '프로토타입'),
                array('fid' => 'F-PV-07', 'name' => '패널 드래그·접기', 'desc' => '속성 패널과 동일 패턴. 기본 위치는 속성 아래(stack-props).', 'input' => '드래그', 'output' => 'localStorage', 'ux' => '자석 스냅', 'status' => '프로토타입'),
            ),
            'copy_menu' => array(
                array('action' => '마스터로 전체 적용', 'desc' => '현재 라벨을 마스터로 전체 시트에 적용'),
                array('action' => '전체로 복사', 'desc' => '시트의 모든 라벨 칸에 복사'),
                array('action' => '페이지 복제', 'desc' => '현재 페이지를 복제하여 새 페이지 생성'),
                array('action' => '다음으로 복사', 'desc' => '다음 칸(우측)에 복사'),
                array('action' => '나머지로 복사', 'desc' => '현재 칸 이후 나머지 칸에 복사'),
                array('action' => '행으로 복사', 'desc' => '같은 행의 칸에 복사'),
                array('action' => '열로 복사', 'desc' => '같은 열의 칸에 복사'),
                array('action' => '페이지로 복사', 'desc' => '다른 페이지로 복사(대상 선택)'),
            ),
        ),

        array(
            'id' => 'MOD-IMPORT',
            'title' => '7. 가져오기 팝업 (I-01)',
            'desc' => '하단 FAB 「가져오기」로 진입. 라벨·태그·템플릿·스마트라벨·외부포맷·내 디자인.',
            'features' => array(
                array('fid' => 'F-IM-01', 'name' => '탭 전환', 'desc' => '라벨 / 태그 / 템플릿 / 스마트라벨 / 외부라벨포맷 / 내 디자인', 'input' => '탭', 'output' => '패널 전환', 'ux' => '기본: 라벨', 'status' => '프로토타입'),
                array('fid' => 'F-IM-02', 'name' => '라벨·태그 카탈로그', 'desc' => '카테고리 · 빈/디자인 · 규격 카드 그리드. 규격 상세 사양 팝업 연동.', 'input' => '카드', 'output' => '상세 팝업 또는 편집 시작', 'ux' => '코드·사이즈 표시', 'status' => '프로토타입'),
                array('fid' => 'F-IM-03', 'name' => '규격 상세 사양', 'desc' => '용지/라벨 치수 · 열×행 · 여백 다이어그램 · 용지 구매 / 디자인 편집', 'input' => '규격 카드', 'output' => '상세 오버레이', 'ux' => '편집 → 편집기 적용', 'status' => '프로토타입'),
                array('fid' => 'F-IM-04', 'name' => '템플릿 · 내 디자인', 'desc' => '썸네일 그리드에서 선택 시 불러오기 토스트.', 'input' => '카드', 'output' => '제목 반영·토스트', 'ux' => '—', 'status' => '프로토타입'),
                array('fid' => 'F-IM-05', 'name' => '스마트라벨(AI)', 'desc' => '프롬프트 · 이미지/엑셀/예시/파일 시작 옵션.', 'input' => '텍스트·파일', 'output' => '토스트/모달', 'ux' => '글자 수 카운트', 'status' => '프로토타입'),
                array('fid' => 'F-IM-06', 'name' => '외부 라벨 포맷', 'desc' => 'iLabel2(.lbl/.xml) · 폼텍(.fmt/.fdx) 드롭존.', 'input' => '파일', 'output' => '파일명 표시·토스트', 'ux' => 'DnD 지원', 'status' => '프로토타입'),
            ),
        ),

        array(
            'id' => 'MOD-DATA',
            'title' => '8. 데이터 가져오기 (D-01)',
            'desc' => '플로팅 툴바 「데이터」 클릭. 가변 데이터(엑셀·CSV·외부포맷) 연동용 파일 첨부 UI.',
            'features' => array(
                array('fid' => 'F-DT-01', 'name' => '포맷 탭', 'desc' => '엑셀 · CSV · 아이라벨 · 폼택 4종 선택.', 'input' => '탭', 'output' => '패널 전환', 'ux' => '기본: 엑셀', 'status' => '프로토타입'),
                array('fid' => 'F-DT-02', 'name' => '파일 첨부', 'desc' => '드래그앤드롭 또는 클릭 선택. 포맷별 accept 확장자.', 'input' => '파일', 'output' => '파일명·has-file', 'ux' => '미선택 시 가져오기 경고', 'status' => '프로토타입'),
                array('fid' => 'F-DT-03', 'name' => '가져오기 실행', 'desc' => '가져오기 버튼 → 연동 시뮬레이션 후 닫기.', 'input' => '버튼', 'output' => '토스트 · 히스토리', 'ux' => '취소/X/ESC/배경', 'status' => '프로토타입'),
            ),
            'formats' => array(
                array('format' => '엑셀', 'ext' => '.xlsx · .xls', 'hint' => '첫 행을 헤더(필드명)로 사용 권장'),
                array('format' => 'CSV', 'ext' => '.csv · .txt', 'hint' => 'UTF-8 · 쉼표 구분자 권장'),
                array('format' => '아이라벨', 'ext' => '.lbl · .xml · .zip', 'hint' => 'iLabel2 데이터 내보내기'),
                array('format' => '폼택', 'ext' => '.fmt · .fdx · .zip', 'hint' => '폼텍 디자인프로9(고도화)'),
            ),
        ),

        array(
            'id' => 'MOD-PRINT',
            'title' => '9. 인쇄 미리보기 모달 (P-01~04)',
            'desc' => '상단 미리보기 또는 편집기 CTA로 진입하는 전체 시트 프리뷰.',
            'features' => array(
                array('fid' => 'F-PT-01', 'name' => '프리뷰 상단', 'desc' => '뒤로 · 제목 · 편집/미리보기 모드 · 편집기에서 계속 · 닫기', 'input' => '버튼', 'output' => '모달 상태', 'ux' => 'ESC 닫기', 'status' => '프로토타입'),
                array('fid' => 'F-PT-02', 'name' => '용지 설정', 'desc' => '용지 크기 · 레이아웃(3×4 등) · 라벨 크기', 'input' => '설정 UI', 'output' => '모달(프로토)', 'ux' => '값 반영 예정', 'status' => '프로토타입'),
                array('fid' => 'F-PT-03', 'name' => '시트 그리드 · 줌', 'desc' => '페이지 네비 · 라벨 배열 · 줌/맞춤', 'input' => '버튼', 'output' => '줌 라벨', 'ux' => '—', 'status' => '프로토타입'),
                array('fid' => 'F-PT-04', 'name' => '우측 옵션', 'desc' => '레이어 · 프리뷰 옵션 · 인쇄 도움말 · 출력', 'input' => '토글·버튼', 'output' => '토스트/모달', 'ux' => '—', 'status' => '프로토타입'),
            ),
        ),

        array(
            'id' => 'MOD-ADMIN',
            'title' => '10. Backoffice 스토리보드 · 전체화면',
            'desc' => '02~02-09 관리자 목업 공용 셸. Front와 동일하게 전체화면 와이어프레임 지원.',
            'features' => array(
                array('fid' => 'F-AD-01', 'name' => '전체화면 보기', 'desc' => '문서 헤더의 ⛶ 전체화면 보기 · ESC / 종료 바.', 'input' => '버튼', 'output' => 'Fullscreen API', 'ux' => 'Alt+F 단축키', 'status' => '프로토타입'),
                array('fid' => 'F-AD-02', 'name' => '영역 표시', 'desc' => '📌 영역 표시 토글 · Zone 라벨(L-01, T-01 등).', 'input' => '버튼', 'output' => 'annotate 클래스', 'ux' => '전체화면에서 상세 패널', 'status' => '프로토타입'),
                array('fid' => 'F-AD-03', 'name' => '☰ 메뉴 전환', 'desc' => '전체화면에서 다른 스토리보드 메뉴로 AJAX 전환.', 'input' => '메뉴 트리', 'output' => 'fragment HTML', 'ux' => '전체화면 유지', 'status' => '프로토타입'),
                array('fid' => 'F-AD-04', 'name' => '모듈 목업', 'desc' => '대시보드·회원·상품·주문·디자인·규격·AI·콘텐츠·통계 등 화면별 KPI/테이블 목업.', 'input' => '—', 'output' => '정적 목업', 'ux' => 'admin-doc-shell', 'status' => '프로토타입'),
            ),
        ),
    ),

    /* ── 공통 UX 규칙 ── */
    'ux_rules' => array(
        array('item' => '프로토타입 게이트', 'desc' => '편집기 최초 진입 시 중앙 ▶ 프로토타입으로 모드 활성화. 이후 상단/툴바 액션이 살아남.'),
        array('item' => '팝업 닫기', 'desc' => '공통: ESC · 오버레이 배경 클릭 · X/닫기 버튼. 우선순위: 레이어 > 프리뷰 > 규격상세 > 가져오기 > 데이터가져오기 > 에셋슬라이드 > 프로토모달.'),
        array('item' => '토스트', 'desc' => '우하단(또는 지정 wrap)에 단기 토스트로 액션 결과 피드백.'),
        array('item' => '자석 스냅', 'desc' => '플로팅 패널(툴바·속성·미리보기)은 모서리에 가까울 때만 스냅. 과도한 끌어당김 방지.'),
        array('item' => '위치 기억', 'desc' => '툴바 코너/방향 · 속성 스냅 · 미리보기 스냅을 localStorage에 저장.'),
        array('item' => '전체화면', 'desc' => 'Front·Backoffice 스토리보드 공통. ☰ 메뉴로 화면 전환 시 전체화면 유지.'),
        array('item' => 'PHP 호환', 'desc' => '스토리보드 프래그먼트는 PHP 5.x 호환(?? 연산자 미사용).'),
    ),

    /* ── 우선순위 / 개발 이관 ── */
    'backlog' => array(
        array('prio' => 'P0', 'item' => '객체 속성 실연동', 'desc' => '선택 객체 ↔ R-01 폼 양방향 바인딩 · 히스토리 실데이터'),
        array('prio' => 'P0', 'item' => '데이터 필드 매핑', 'desc' => '엑셀/CSV 헤더 → 라벨 변수 매핑 UI · 미리보기 반영'),
        array('prio' => 'P0', 'item' => '저장/불러오기 API', 'desc' => '디자인 JSON 저장 · 충돌·버전 · 자동저장'),
        array('prio' => 'P1', 'item' => '라벨복사 실동작', 'desc' => '시트/페이지 단위 복제 엔진 · 마스터 동기화'),
        array('prio' => 'P1', 'item' => '외부 포맷 파서', 'desc' => 'iLabel2 · 폼텍 · IDF 등 Import 파이프라인'),
        array('prio' => 'P1', 'item' => '인쇄 주문 연동', 'desc' => '편집기에서 출력 → 장바구니/견적 플로우'),
        array('prio' => 'P2', 'item' => '협업·권한', 'desc' => '공유 링크 · 역할별 편집 잠금'),
        array('prio' => 'P2', 'item' => '모바일 편집', 'desc' => '반응형 툴바·터치 제스처 (Desktop 우선)'),
    ),

    'file_map' => array(
        array('area' => '편집기 본문', 'path' => 'storyboard/_fragments/01-05-hifi-wireframe-body.php'),
        array('area' => '편집기 JS', 'path' => 'storyboard/_fragments/01-05-editor-init.inline.php'),
        array('area' => '플로팅 툴바', 'path' => 'storyboard/_fragments/01-05-floating-tools.php'),
        array('area' => '에셋 슬라이드', 'path' => 'storyboard/_fragments/01-05-asset-slide-panel.php'),
        array('area' => '가져오기', 'path' => 'storyboard/_fragments/01-05-import-popup.php'),
        array('area' => '데이터 가져오기', 'path' => 'storyboard/_fragments/01-05-data-import-popup.php'),
        array('area' => '규격 상세', 'path' => 'storyboard/_fragments/01-05-spec-detail-popup.php'),
        array('area' => 'Zone 데이터', 'path' => 'storyboard/_fragments/zone-data-01-05.php'),
        array('area' => 'Admin 문서 셸', 'path' => 'storyboard/_fragments/admin-doc-shell.php'),
        array('area' => '전체화면 런타임', 'path' => 'storyboard/_fragments/storyboard-wf-runtime.js.php'),
    ),
);
