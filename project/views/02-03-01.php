<?php
/**
 * 스토리보드: 상품관리
 * 메뉴코드: 02-03-01
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-03-01';
$adminTitle = '상품관리';
$adminSub = '상품 등록·수정·판매상태·가격/옵션 관리';
$adminActive = '02-03';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '상품관리 (리스트 + 등록/수정)'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/shop/products'),
    array('dt' => '연관 테이블', 'dd' => 'products · product_options · categories · inventory'),
    array('dt' => '접근 권한', 'dd' => '관리자 · MD'),
    array('dt' => '화면 목적', 'dd' => '상품 CRUD·판매 상태·가격·옵션·이미지 관리'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 쇼핑몰 관리 › 상품관리'),
);

$adminZones = array(
    array('id' => 'F-01', 'kind' => 'nav', 'block' => '검색/필터', 'el' => '상품명·SKU·카테고리·판매상태·가격대', 'link' => '—'),
    array('id' => 'A-01', 'kind' => 'cta', 'block' => '일괄 작업', 'el' => '판매/중지·카테고리 이동·가격 일괄·삭제', 'link' => '—'),
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '상품 테이블', 'el' => '썸네일·상품명/SKU·카테고리·가격·재고·판매량·상태', 'link' => '02-03-03'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '등록/수정 폼', 'el' => '기본정보·가격·옵션·이미지·상세설명·SEO', 'link' => '02-03-02'),
    array('id' => 'P-01', 'kind' => 'nav', 'block' => '페이지네이션', 'el' => '페이지 이동·표시 개수', 'link' => '—'),
    array('id' => 'K-00', 'kind' => 'cta', 'block' => '＋ 상품 등록(진입)', 'el' => '헤더 CTA 클릭 → 상품등록 팝업 오픈', 'link' => 'K-01'),
    array('id' => 'K-01', 'kind' => 'layout', 'block' => '상품등록 팝업', 'el' => '오버레이 모달 · 기본정보/가격/옵션·재고/이미지/상세/배송/노출 8개 섹션', 'link' => '—'),
    array('id' => 'K-02', 'kind' => 'ui', 'block' => '팝업: 기본 정보', 'el' => '상품명·SKU(자동/수동)·카테고리·브랜드·판매상태·호환 규격 번호(폼텍/아이라벨/애니라벨)', 'link' => '—'),
    array('id' => 'K-03', 'kind' => 'ui', 'block' => '팝업: 가격', 'el' => '정가·판매가·원가·부가세·할인율(자동)·과세유형', 'link' => '—'),
    array('id' => 'K-04', 'kind' => 'ui', 'block' => '팝업: 옵션·재고', 'el' => '옵션 사용여부·조합(규격/수량/재질)·옵션별 추가금/재고·안전재고', 'link' => '—'),
    array('id' => 'K-05', 'kind' => 'ui', 'block' => '팝업: 이미지', 'el' => '대표 이미지·추가 이미지(드래그 정렬)·상세 이미지', 'link' => '—'),
    array('id' => 'K-06', 'kind' => 'ui', 'block' => '팝업: 상세/배송/노출', 'el' => '상세설명 에디터·배송비 유형/출고지·진열·검색키워드·SEO', 'link' => '—'),
    array('id' => 'K-07', 'kind' => 'cta', 'block' => '팝업 하단 액션', 'el' => '취소·임시저장·등록(유효성 검사 후 저장)', 'link' => '—'),
);

$adminUx = array(
    array('item' => '판매 상태', 'desc' => '판매중/품절/판매중지/숨김 토글. 예약 판매 일시 지정'),
    array('item' => '옵션 관리', 'desc' => '규격·수량·재질 조합 옵션 + 옵션별 추가금/재고'),
    array('item' => '이미지', 'desc' => '대표+추가 이미지 드래그 정렬. 자동 리사이즈/webp 변환'),
    array('item' => '가격 검증', 'desc' => '판매가 < 원가 경고. 할인율 자동 계산'),
    array('item' => '일괄 처리', 'desc' => '다중 선택 → 상태/카테고리/가격 일괄 반영(미리보기 확인)'),
    array('item' => '상품등록 팝업', 'desc' => '＋상품 등록 클릭 → 오버레이 모달. 목록 화면을 벗어나지 않고 등록. ESC/배경 클릭 시 입력값 보존 확인 후 닫기'),
    array('item' => 'SKU 자동생성', 'desc' => '카테고리 + 규격 조합으로 SKU 자동 제안. 수동 편집 가능, 중복 실시간 검증'),
    array('item' => '옵션 자동조합', 'desc' => '규격·수량·재질 값 입력 → 조합 자동 생성. 조합별 추가금/재고 개별 입력'),
    array('item' => '가격 자동계산', 'desc' => '정가·판매가 입력 시 할인율 자동 계산. 판매가 < 원가면 경고 배지 표시'),
    array('item' => '임시저장/유효성', 'desc' => '필수값 미입력 시 등록 비활성. 임시저장은 필수값 없이 가능(초안 상태)'),
    array('item' => '호환 규격 번호', 'desc' => '폼텍·아이라벨·애니라벨 등 타사 동일 규격 품번을 매핑. 규격 검색·"내 라벨 찾기"·호환 안내에 활용. 선택 입력'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>상품관리</h3><p>총 1,284개 · 판매중 1,247</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">⤒ 대량 등록(엑셀)</span><span class="sb-adm-btn sb-adm-btn--primary">＋ 상품 등록</span></div>
</div>

<div class="sb-adm-toolbar">
    <span class="sb-adm-input" style="min-width:220px">🔍 상품명·SKU</span>
    <span class="sb-adm-input sel">카테고리: 전체</span>
    <span class="sb-adm-input sel">판매상태: 전체</span>
    <span class="sb-adm-input sel">정렬: 판매량순</span>
    <span class="sb-adm-btn sb-adm-btn--primary sb-adm-spacer">검색</span>
</div>

<div class="sb-adm-table-wrap">
    <table class="sb-adm-table">
        <thead><tr>
            <th><span class="sb-adm-checkbox"></span></th><th>상품</th><th>카테고리</th>
            <th class="num">판매가</th><th class="num">재고</th><th class="num">판매량</th><th>상태</th><th></th>
        </tr></thead>
        <tbody>
            <tr>
                <td><span class="sb-adm-checkbox"></span></td>
                <td><span class="sb-adm-avatar-name"><span class="sb-adm-thumb">IMG</span><span>방수 유광 라벨 A4 24칸<br><small>SKU-LB-A4-24W</small></span></span></td>
                <td>라벨·스티커</td><td class="num strong">18,000</td><td class="num">1,240</td><td class="num">1,284</td>
                <td><span class="sb-adm-badge sb-adm-badge--green">판매중</span></td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td>
            </tr>
            <tr>
                <td><span class="sb-adm-checkbox"></span></td>
                <td><span class="sb-adm-avatar-name"><span class="sb-adm-thumb">IMG</span><span>투명 PET 원형 라벨 40mm<br><small>SKU-PET-R40</small></span></span></td>
                <td>라벨·스티커</td><td class="num strong">12,500</td><td class="num">860</td><td class="num">982</td>
                <td><span class="sb-adm-badge sb-adm-badge--green">판매중</span></td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td>
            </tr>
            <tr>
                <td><span class="sb-adm-checkbox"></span></td>
                <td><span class="sb-adm-avatar-name"><span class="sb-adm-thumb">IMG</span><span>방수 라벨 A4 40칸<br><small>SKU-LB-A4-40W</small></span></span></td>
                <td>라벨·스티커</td><td class="num strong">22,000</td><td class="num">0</td><td class="num">431</td>
                <td><span class="sb-adm-badge sb-adm-badge--red">품절</span></td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td>
            </tr>
            <tr>
                <td><span class="sb-adm-checkbox"></span></td>
                <td><span class="sb-adm-avatar-name"><span class="sb-adm-thumb">IMG</span><span>바코드 프린터 리본 65mm<br><small>SKU-RB-65</small></span></span></td>
                <td>프린터·소모품</td><td class="num strong">9,800</td><td class="num">8</td><td class="num">512</td>
                <td><span class="sb-adm-badge sb-adm-badge--amber">재고부족</span></td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td>
            </tr>
        </tbody>
    </table>
    <div class="sb-adm-pager"><span>총 1,284개 · 페이지당 20</span><div class="sb-adm-pager-nums"><span>‹</span><span class="is-active">1</span><span>2</span><span>3</span><span>›</span></div></div>
</div>

<!-- ▼ [K-01] 상품등록 팝업 : 헤더 '＋ 상품 등록' 클릭 시 목록 위에 오버레이로 표시 -->
<!-- 팝업 전용 스타일을 파일에 동봉해 공용 스타일 캐시와 무관하게 항상 렌더링되도록 함 -->
<style>
.sb-adm-modal-stage { position: relative; margin-top: 18px; border-radius: 12px; background: repeating-linear-gradient(135deg, #eef2f7, #eef2f7 10px, #e9eef4 10px, #e9eef4 20px); padding: 30px 18px 26px; display: flex; justify-content: center; }
.sb-adm-modal-stage::before { content: 'POPUP · 상품 목록 화면 위에 오버레이'; position: absolute; top: 9px; left: 14px; font-size: 10px; font-weight: 700; letter-spacing: .04em; color: #64748b; }
.sb-adm-modal { width: 100%; max-width: 720px; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 24px 60px rgba(15,23,42,.24); overflow: hidden; }
.sb-adm-modal * { box-sizing: border-box; }
.sb-adm-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f7; }
.sb-adm-modal-head h4 { margin: 0; font-size: 15px; font-weight: 800; color: #0f172a; }
.sb-adm-modal-head p { margin: 3px 0 0; font-size: 11px; color: #94a3b8; }
.sb-adm-modal-head code { background: #f1f5f9; border-radius: 4px; padding: 1px 5px; font-size: 10px; color: #475569; }
.sb-adm-modal-close { width: 26px; height: 26px; border-radius: 7px; border: 1px solid #e2e8f0; background: #f8fafc; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
.sb-adm-modal-body { padding: 16px 20px 20px; max-height: 520px; overflow: auto; }
.sb-adm-modal-foot { display: flex; align-items: center; gap: 8px; padding: 13px 20px; border-top: 1px solid #eef2f7; background: #fafbff; }
.sb-adm-modal-foot .sb-adm-spacer { margin-left: auto; }
.sb-adm-modal .sb-adm-btn { display: inline-flex; align-items: center; gap: 5px; padding: 7px 13px; border-radius: 8px; font-size: 12px; font-weight: 600; border: 1px solid #cbd5e1; background: #fff; color: #334155; cursor: default; white-space: nowrap; }
.sb-adm-modal .sb-adm-btn--primary { background: #6366f1; border-color: #6366f1; color: #fff; }
.sb-adm-modal .sb-adm-btn--ghost { background: #f1f5f9; border-color: #f1f5f9; color: #475569; }
.sb-adm-fsec { border: 1px solid #eef2f7; border-radius: 10px; padding: 13px 14px; margin-bottom: 12px; }
.sb-adm-fsec:last-child { margin-bottom: 0; }
.sb-adm-fsec-head { display: flex; align-items: center; gap: 8px; margin-bottom: 11px; }
.sb-adm-fsec-head .n { width: 20px; height: 20px; border-radius: 6px; background: #eef2ff; color: #4f46e5; font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sb-adm-fsec-head h5 { margin: 0; font-size: 12px; font-weight: 700; color: #1e293b; }
.sb-adm-fsec-head .hint { margin-left: auto; font-size: 10px; color: #94a3b8; }
.sb-adm-f { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; }
.sb-adm-f .row { display: flex; flex-direction: column; gap: 4px; }
.sb-adm-f .row.full { grid-column: 1 / -1; }
.sb-adm-f label { font-size: 11px; font-weight: 600; color: #475569; }
.sb-adm-f label .req { color: #dc2626; margin-left: 2px; }
.sb-adm-f label .help { color: #94a3b8; font-weight: 500; }
.sb-adm-f .ctl { min-height: 34px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; padding: 8px 11px; font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px; }
.sb-adm-f .ctl.filled { color: #334155; }
.sb-adm-f .ctl.sel::after { content: '▾'; margin-left: auto; color: #94a3b8; }
.sb-adm-f .ctl.area { min-height: 70px; align-items: flex-start; }
.sb-adm-f .ctl.pre { color: #cbd5e1; }
.sb-adm-f .suffix { margin-left: auto; font-size: 11px; color: #94a3b8; }
.sb-adm-f .help { font-size: 10px; color: #94a3b8; }
.sb-adm-seg { display: inline-flex; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.sb-adm-seg span { padding: 7px 12px; font-size: 11px; color: #64748b; border-right: 1px solid #e2e8f0; background: #fff; }
.sb-adm-seg span:last-child { border-right: none; }
.sb-adm-seg span.is-active { background: #6366f1; color: #fff; font-weight: 700; }
.sb-adm-switch { display: inline-flex; align-items: center; gap: 7px; font-size: 11px; color: #475569; }
.sb-adm-switch i { width: 30px; height: 17px; border-radius: 999px; background: #6366f1; position: relative; flex-shrink: 0; display: inline-block; }
.sb-adm-switch i::after { content: ''; position: absolute; top: 2px; right: 2px; width: 13px; height: 13px; border-radius: 50%; background: #fff; }
.sb-adm-switch.off i { background: #cbd5e1; }
.sb-adm-switch.off i::after { right: auto; left: 2px; }
.sb-adm-uploader { display: flex; gap: 8px; flex-wrap: wrap; }
.sb-adm-upcell { width: 66px; height: 66px; border-radius: 9px; border: 1px solid #e2e8f0; background: #f8fafc; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; font-size: 9px; color: #94a3b8; text-align: center; }
.sb-adm-upcell.main { border-color: #a5b4fc; background: #eef2ff; color: #4f46e5; font-weight: 700; }
.sb-adm-upcell.add { border-style: dashed; }
.sb-adm-opt-table { width: 100%; border-collapse: collapse; font-size: 11px; border: 1px solid #eef2f7; border-radius: 8px; overflow: hidden; }
.sb-adm-opt-table th, .sb-adm-opt-table td { padding: 7px 9px; text-align: left; border-bottom: 1px solid #f1f5f9; }
.sb-adm-opt-table thead th { background: #f8fafc; font-size: 10px; font-weight: 700; color: #64748b; }
.sb-adm-opt-table td.num, .sb-adm-opt-table th.num { text-align: right; }
@media (max-width: 720px) { .sb-adm-f { grid-template-columns: 1fr; } }
</style>
<div class="sb-adm-modal-stage">
    <div class="sb-adm-modal">
        <div class="sb-adm-modal-head">
            <div>
                <h4>상품 등록</h4>
                <p>기본정보부터 노출까지 한 화면에서 등록합니다 · <code>products / product_options / inventory</code></p>
            </div>
            <span class="sb-adm-modal-close">✕</span>
        </div>

        <div class="sb-adm-modal-body">

            <!-- 1. 기본 정보 -->
            <div class="sb-adm-fsec">
                <div class="sb-adm-fsec-head"><span class="n">1</span><h5>기본 정보</h5><span class="hint">필수 항목 표시</span></div>
                <div class="sb-adm-f">
                    <div class="row full">
                        <label>상품명 <span class="req">*</span></label>
                        <div class="ctl filled">방수 유광 라벨 A4 24칸</div>
                    </div>
                    <div class="row">
                        <label>상품코드 / SKU <span class="req">*</span></label>
                        <div class="ctl filled">SKU-LB-A4-24W <span class="suffix">자동생성 ↻</span></div>
                    </div>
                    <div class="row">
                        <label>카테고리 <span class="req">*</span></label>
                        <div class="ctl filled sel">라벨·스티커 › A4 시트</div>
                    </div>
                    <div class="row">
                        <label>브랜드 / 제조사</label>
                        <div class="ctl">브랜드 선택 또는 입력</div>
                    </div>
                    <div class="row">
                        <label>판매 상태 <span class="req">*</span></label>
                        <div class="sb-adm-seg">
                            <span class="is-active">판매중</span><span>품절</span><span>판매중지</span><span>숨김</span>
                        </div>
                    </div>
                    <div class="row full">
                        <label>호환 규격 번호 (타사 상품번호) <span class="help">— 동일 규격의 경쟁사 품번 매핑 · 검색/호환 안내용</span></label>
                    </div>
                    <div class="row">
                        <label>폼텍 상품번호</label>
                        <div class="ctl filled">3630</div>
                    </div>
                    <div class="row">
                        <label>아이라벨 상품번호</label>
                        <div class="ctl filled">CL-524</div>
                    </div>
                    <div class="row">
                        <label>애니라벨 상품번호</label>
                        <div class="ctl filled">AL-2400</div>
                    </div>
                </div>
            </div>

            <!-- 2. 가격 -->
            <div class="sb-adm-fsec">
                <div class="sb-adm-fsec-head"><span class="n">2</span><h5>가격</h5><span class="hint">할인율은 자동 계산</span></div>
                <div class="sb-adm-f">
                    <div class="row">
                        <label>정가</label>
                        <div class="ctl filled">22,000 <span class="suffix">원</span></div>
                    </div>
                    <div class="row">
                        <label>판매가 <span class="req">*</span></label>
                        <div class="ctl filled">18,000 <span class="suffix">원</span></div>
                    </div>
                    <div class="row">
                        <label>원가(공급가)</label>
                        <div class="ctl filled">11,500 <span class="suffix">원</span></div>
                    </div>
                    <div class="row">
                        <label>할인율 <span class="help">(자동)</span></label>
                        <div class="ctl filled">18% <span class="suffix">↓ 4,000원</span></div>
                    </div>
                    <div class="row">
                        <label>과세 유형</label>
                        <div class="sb-adm-seg"><span class="is-active">과세</span><span>면세</span><span>영세</span></div>
                    </div>
                    <div class="row">
                        <label>부가세 포함 여부</label>
                        <span class="sb-adm-switch"><i></i> 판매가에 VAT 포함</span>
                    </div>
                </div>
            </div>

            <!-- 3. 옵션 · 재고 -->
            <div class="sb-adm-fsec">
                <div class="sb-adm-fsec-head"><span class="n">3</span><h5>옵션 · 재고</h5><span class="hint"><span class="sb-adm-switch"><i></i> 옵션 사용</span></span></div>
                <div class="sb-adm-f" style="margin-bottom:10px">
                    <div class="row">
                        <label>옵션명 1</label>
                        <div class="ctl filled">규격 (예: A4 / A3)</div>
                    </div>
                    <div class="row">
                        <label>옵션명 2</label>
                        <div class="ctl filled">칸 수 (24칸 / 40칸)</div>
                    </div>
                </div>
                <table class="sb-adm-opt-table">
                    <thead><tr><th>옵션 조합</th><th class="num">추가금</th><th class="num">재고</th><th class="num">안전재고</th><th>SKU</th></tr></thead>
                    <tbody>
                        <tr><td>A4 / 24칸</td><td class="num">+0</td><td class="num">1,240</td><td class="num">100</td><td>SKU-LB-A4-24W</td></tr>
                        <tr><td>A4 / 40칸</td><td class="num">+2,000</td><td class="num">860</td><td class="num">100</td><td>SKU-LB-A4-40W</td></tr>
                        <tr><td>A3 / 24칸</td><td class="num">+3,500</td><td class="num">0</td><td class="num">50</td><td>SKU-LB-A3-24W</td></tr>
                    </tbody>
                </table>
                <p class="help" style="margin:8px 0 0">＋ 조합 추가 · 옵션값 입력 시 조합 자동 생성 · 옵션 미사용 시 단일 재고 입력란으로 전환</p>
            </div>

            <!-- 4. 이미지 -->
            <div class="sb-adm-fsec">
                <div class="sb-adm-fsec-head"><span class="n">4</span><h5>이미지</h5><span class="hint">드래그로 순서 변경 · 자동 webp 변환</span></div>
                <div class="sb-adm-uploader">
                    <div class="sb-adm-upcell main">대표<br>IMG</div>
                    <div class="sb-adm-upcell">IMG 2</div>
                    <div class="sb-adm-upcell">IMG 3</div>
                    <div class="sb-adm-upcell add">＋ 추가</div>
                </div>
                <p class="help" style="margin:8px 0 0">권장 1,000×1,000px · JPG/PNG/WEBP · 개당 5MB 이하 · 최대 10장</p>
            </div>

            <!-- 5. 상세 설명 -->
            <div class="sb-adm-fsec">
                <div class="sb-adm-fsec-head"><span class="n">5</span><h5>상세 설명</h5><span class="hint">에디터 / 상세 이미지</span></div>
                <div class="sb-adm-f">
                    <div class="row full">
                        <label>상세 설명 <span class="req">*</span></label>
                        <div class="ctl area pre">리치 텍스트 에디터 (이미지·표·동영상 삽입) — 상세 페이지 본문</div>
                    </div>
                </div>
            </div>

            <!-- 6. 배송 -->
            <div class="sb-adm-fsec">
                <div class="sb-adm-fsec-head"><span class="n">6</span><h5>배송</h5></div>
                <div class="sb-adm-f">
                    <div class="row">
                        <label>배송비 유형</label>
                        <div class="sb-adm-seg"><span class="is-active">유료</span><span>무료</span><span>조건부 무료</span></div>
                    </div>
                    <div class="row">
                        <label>기본 배송비</label>
                        <div class="ctl filled">3,000 <span class="suffix">원</span></div>
                    </div>
                    <div class="row">
                        <label>출고지</label>
                        <div class="ctl filled sel">본사 물류창고</div>
                    </div>
                    <div class="row">
                        <label>예상 출고일</label>
                        <div class="ctl filled">평일 1~2일 이내</div>
                    </div>
                </div>
            </div>

            <!-- 7. 노출 · SEO -->
            <div class="sb-adm-fsec">
                <div class="sb-adm-fsec-head"><span class="n">7</span><h5>노출 · SEO</h5></div>
                <div class="sb-adm-f">
                    <div class="row">
                        <label>진열 여부</label>
                        <span class="sb-adm-switch"><i></i> 쇼핑몰 진열</span>
                    </div>
                    <div class="row">
                        <label>노출 태그</label>
                        <div class="ctl filled">신상품 · 추천</div>
                    </div>
                    <div class="row full">
                        <label>검색 키워드</label>
                        <div class="ctl filled">방수라벨, A4라벨, 24칸, 유광스티커</div>
                    </div>
                    <div class="row full">
                        <label>메타 설명 (SEO)</label>
                        <div class="ctl area">검색 결과·SNS 공유 시 노출될 요약 설명 (권장 80자 이내)</div>
                    </div>
                </div>
            </div>

        </div>

        <div class="sb-adm-modal-foot">
            <span class="sb-adm-btn sb-adm-btn--ghost">취소</span>
            <span class="sb-adm-btn sb-adm-spacer">임시저장</span>
            <span class="sb-adm-btn sb-adm-btn--primary">상품 등록</span>
        </div>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
