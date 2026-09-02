<?php
/**
 * Backoffice(02) 관리자 스토리보드 공통 목업 스타일
 * - sb-front-doc(문서 레이아웃)은 wf-shared-styles.php 에서 제공
 * - 아래는 관리자 화면 목업(admin chrome + 데이터 테이블/KPI/차트 등) 전용
 */
?>
/* ── 관리자 목업 프레임 ── */
.sb-adm { font-family: 'Inter', 'Pretendard', -apple-system, sans-serif; color: #1e293b; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; display: flex; min-height: 560px; box-shadow: 0 1px 3px rgba(15,23,42,.06); }
.sb-adm * { box-sizing: border-box; }

/* 좌측 LNB */
.sb-adm-lnb { width: 210px; flex-shrink: 0; background: #0f172a; color: #cbd5e1; display: flex; flex-direction: column; }
.sb-adm-lnb-brand { display: flex; align-items: center; gap: 8px; padding: 16px 16px 14px; border-bottom: 1px solid rgba(148,163,184,.18); }
.sb-adm-lnb-brand b { font-size: 14px; font-weight: 800; color: #fff; }
.sb-adm-lnb-brand small { font-size: 9px; font-weight: 700; letter-spacing: .12em; color: #6366f1; }
.sb-adm-lnb-nav { flex: 1; padding: 10px 0; overflow: auto; }
.sb-adm-lnb-item { display: flex; align-items: center; gap: 10px; padding: 9px 16px; font-size: 12px; color: #94a3b8; border-left: 3px solid transparent; cursor: default; }
.sb-adm-lnb-item .ic { width: 18px; text-align: center; font-size: 13px; }
.sb-adm-lnb-item.is-active { background: rgba(99,102,241,.16); border-left-color: #6366f1; color: #fff; font-weight: 700; }
.sb-adm-lnb-item.is-active .ic { color: #a5b4fc; }
.sb-adm-lnb-foot { padding: 12px 16px; border-top: 1px solid rgba(148,163,184,.18); font-size: 10px; color: #64748b; }

/* 우측 본문 영역 */
.sb-adm-main { flex: 1; min-width: 0; display: flex; flex-direction: column; background: #f8fafc; }
.sb-adm-topbar { display: flex; align-items: center; gap: 12px; padding: 12px 20px; background: #fff; border-bottom: 1px solid #e2e8f0; }
.sb-adm-crumb { font-size: 11px; color: #94a3b8; }
.sb-adm-crumb b { color: #475569; }
.sb-adm-topsearch { margin-left: auto; display: flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 999px; background: #f8fafc; font-size: 11px; color: #94a3b8; min-width: 200px; }
.sb-adm-topbtn { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #64748b; position: relative; }
.sb-adm-topbtn .dot { position: absolute; top: -3px; right: -3px; min-width: 14px; height: 14px; padding: 0 3px; border-radius: 999px; background: #ef4444; color: #fff; font-size: 8px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.sb-adm-topuser { display: flex; align-items: center; gap: 8px; font-size: 11px; color: #334155; }
.sb-adm-topuser .av { width: 28px; height: 28px; border-radius: 50%; background: #6366f1; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; }

.sb-adm-body { flex: 1; padding: 18px 20px 24px; overflow: auto; }
.sb-adm-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.sb-adm-head h3 { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; }
.sb-adm-head p { margin: 3px 0 0; font-size: 12px; color: #64748b; }
.sb-adm-head-actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* 버튼 */
.sb-adm-btn { display: inline-flex; align-items: center; gap: 5px; padding: 7px 13px; border-radius: 8px; font-size: 12px; font-weight: 600; border: 1px solid #cbd5e1; background: #fff; color: #334155; cursor: default; white-space: nowrap; }
.sb-adm-btn--primary { background: #6366f1; border-color: #6366f1; color: #fff; }
.sb-adm-btn--danger { background: #fff; border-color: #fecaca; color: #dc2626; }
.sb-adm-btn--ghost { background: #f1f5f9; border-color: #f1f5f9; color: #475569; }
.sb-adm-btn--sm { padding: 4px 9px; font-size: 11px; }

/* KPI 카드 */
.sb-adm-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
.sb-adm-kpi { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; }
.sb-adm-kpi .lbl { font-size: 11px; color: #94a3b8; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.sb-adm-kpi .val { font-size: 22px; font-weight: 800; color: #0f172a; margin: 6px 0 2px; }
.sb-adm-kpi .delta { font-size: 11px; font-weight: 700; }
.sb-adm-kpi .delta.up { color: #16a34a; }
.sb-adm-kpi .delta.down { color: #dc2626; }
.sb-adm-kpi .ic-badge { margin-left: auto; width: 30px; height: 30px; border-radius: 9px; background: #eef2ff; color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 15px; }
.sb-adm-kpi .lbl-row { display: flex; align-items: center; }

/* 패널/카드 */
.sb-adm-grid { display: grid; gap: 14px; margin-bottom: 16px; }
.sb-adm-grid--2 { grid-template-columns: 1fr 1fr; }
.sb-adm-grid--3 { grid-template-columns: repeat(3, 1fr); }
.sb-adm-grid--2-1 { grid-template-columns: 2fr 1fr; }
.sb-adm-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; }
.sb-adm-panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.sb-adm-panel-head h4 { margin: 0; font-size: 13px; font-weight: 700; color: #1e293b; }
.sb-adm-panel-head .more { font-size: 11px; color: #6366f1; font-weight: 600; }

/* 툴바(필터) */
.sb-adm-toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
.sb-adm-field { display: flex; flex-direction: column; gap: 3px; }
.sb-adm-input { display: inline-flex; align-items: center; gap: 6px; padding: 7px 11px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 11px; color: #64748b; min-height: 32px; }
.sb-adm-input.sel::after { content: '▾'; margin-left: auto; color: #94a3b8; }
.sb-adm-chip { padding: 5px 11px; border: 1px solid #e2e8f0; border-radius: 999px; font-size: 11px; color: #64748b; background: #fff; white-space: nowrap; }
.sb-adm-chip.is-active { background: #6366f1; border-color: #6366f1; color: #fff; font-weight: 600; }
.sb-adm-spacer { margin-left: auto; }

/* 데이터 테이블 */
.sb-adm-table-wrap { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; }
.sb-adm-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.sb-adm-table th, .sb-adm-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #f1f5f9; white-space: nowrap; }
.sb-adm-table thead th { background: #f8fafc; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .02em; }
.sb-adm-table tbody tr:hover { background: #fafbff; }
.sb-adm-table td.num { text-align: right; font-variant-numeric: tabular-nums; }
.sb-adm-table .muted { color: #94a3b8; }
.sb-adm-table .strong { font-weight: 700; color: #0f172a; }
.sb-adm-checkbox { width: 14px; height: 14px; border: 1px solid #cbd5e1; border-radius: 4px; display: inline-block; }

/* 상태 배지 */
.sb-adm-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 999px; font-size: 10px; font-weight: 700; }
.sb-adm-badge--green { background: #dcfce7; color: #15803d; }
.sb-adm-badge--blue { background: #dbeafe; color: #1d4ed8; }
.sb-adm-badge--amber { background: #fef3c7; color: #b45309; }
.sb-adm-badge--red { background: #fee2e2; color: #b91c1c; }
.sb-adm-badge--gray { background: #f1f5f9; color: #64748b; }
.sb-adm-badge--purple { background: #f3e8ff; color: #7e22ce; }

.sb-adm-avatar-name { display: flex; align-items: center; gap: 8px; }
.sb-adm-avatar-name .av { width: 26px; height: 26px; border-radius: 50%; background: #e0e7ff; color: #4f46e5; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sb-adm-avatar-name small { color: #94a3b8; font-size: 10px; }

.sb-adm-thumb { width: 40px; height: 40px; border-radius: 8px; background: #f1f5f9; border: 1px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #94a3b8; }

/* 페이지네이션 */
.sb-adm-pager { display: flex; align-items: center; justify-content: space-between; padding: 12px 4px 0; font-size: 11px; color: #64748b; }
.sb-adm-pager-nums { display: flex; gap: 4px; }
.sb-adm-pager-nums span { min-width: 26px; height: 26px; padding: 0 6px; border: 1px solid #e2e8f0; border-radius: 7px; display: flex; align-items: center; justify-content: center; background: #fff; }
.sb-adm-pager-nums span.is-active { background: #6366f1; border-color: #6366f1; color: #fff; font-weight: 700; }

/* 차트 플레이스홀더 */
.sb-adm-chart { height: 200px; border-radius: 10px; background: linear-gradient(180deg, #fff, #f8fafc); border: 1px solid #eef2f7; display: flex; align-items: flex-end; gap: 8px; padding: 16px; }
.sb-adm-bar { flex: 1; background: linear-gradient(180deg, #a5b4fc, #6366f1); border-radius: 6px 6px 0 0; opacity: .9; }
.sb-adm-chart--line { position: relative; align-items: stretch; }
.sb-adm-chart--line::after { content: '📈 라인 차트 영역'; margin: auto; color: #94a3b8; font-size: 12px; font-weight: 600; }
.sb-adm-chart--donut { align-items: center; justify-content: center; }
.sb-adm-donut { width: 130px; height: 130px; border-radius: 50%; background: conic-gradient(#6366f1 0 45%, #22c55e 45% 70%, #f59e0b 70% 88%, #cbd5e1 88% 100%); position: relative; }
.sb-adm-donut::after { content: ''; position: absolute; inset: 28px; background: #fff; border-radius: 50%; }
.sb-adm-legend { display: flex; flex-direction: column; gap: 8px; font-size: 11px; color: #475569; }
.sb-adm-legend i { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 7px; vertical-align: middle; }

/* 리스트(랭킹/활동) */
.sb-adm-list { list-style: none; margin: 0; padding: 0; }
.sb-adm-list li { display: flex; align-items: center; gap: 10px; padding: 9px 2px; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
.sb-adm-list li:last-child { border-bottom: none; }
.sb-adm-list .rank { width: 20px; height: 20px; border-radius: 6px; background: #eef2ff; color: #4f46e5; font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
.sb-adm-list .grow { flex: 1; min-width: 0; }
.sb-adm-list .val { font-weight: 700; color: #0f172a; }

/* 정의 리스트/폼 미리보기 */
.sb-adm-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 18px; }
.sb-adm-form-row { display: flex; flex-direction: column; gap: 4px; }
.sb-adm-form-row.full { grid-column: 1 / -1; }
.sb-adm-form-row label { font-size: 11px; font-weight: 600; color: #64748b; }
.sb-adm-form-row .box { min-height: 34px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; padding: 8px 11px; font-size: 12px; color: #334155; display: flex; align-items: center; }
.sb-adm-form-row .box.area { min-height: 72px; align-items: flex-start; }

/* 탭 */
.sb-adm-tabs { display: flex; gap: 4px; border-bottom: 1px solid #e2e8f0; margin-bottom: 14px; }
.sb-adm-tab { padding: 8px 14px; font-size: 12px; font-weight: 600; color: #94a3b8; border-bottom: 2px solid transparent; }
.sb-adm-tab.is-active { color: #4f46e5; border-bottom-color: #6366f1; }

.sb-adm-note { margin-top: 10px; font-size: 11px; color: #94a3b8; }

/* ── 모달/팝업 목업 ── */
.sb-adm-modal-stage { position: relative; margin-top: 18px; border-radius: 12px; background: repeating-linear-gradient(135deg, #eef2f7, #eef2f7 10px, #e9eef4 10px, #e9eef4 20px); padding: 26px 18px; display: flex; justify-content: center; }
.sb-adm-modal-stage::before { content: 'POPUP · 상품 목록 화면 위에 오버레이'; position: absolute; top: 8px; left: 14px; font-size: 10px; font-weight: 700; letter-spacing: .04em; color: #94a3b8; }
.sb-adm-modal { width: 100%; max-width: 720px; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 24px 60px rgba(15,23,42,.24); overflow: hidden; }
.sb-adm-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f7; }
.sb-adm-modal-head h4 { margin: 0; font-size: 15px; font-weight: 800; color: #0f172a; }
.sb-adm-modal-head p { margin: 3px 0 0; font-size: 11px; color: #94a3b8; }
.sb-adm-modal-close { width: 26px; height: 26px; border-radius: 7px; border: 1px solid #e2e8f0; background: #f8fafc; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
.sb-adm-modal-body { padding: 16px 20px 20px; max-height: 520px; overflow: auto; }
.sb-adm-modal-foot { display: flex; align-items: center; gap: 8px; padding: 13px 20px; border-top: 1px solid #eef2f7; background: #fafbff; }
.sb-adm-modal-foot .sb-adm-spacer { margin-left: auto; }

/* 모달 내부 폼 섹션 */
.sb-adm-fsec { border: 1px solid #eef2f7; border-radius: 10px; padding: 13px 14px; margin-bottom: 12px; }
.sb-adm-fsec:last-child { margin-bottom: 0; }
.sb-adm-fsec-head { display: flex; align-items: center; gap: 8px; margin-bottom: 11px; }
.sb-adm-fsec-head .n { width: 20px; height: 20px; border-radius: 6px; background: #eef2ff; color: #4f46e5; font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
.sb-adm-fsec-head h5 { margin: 0; font-size: 12px; font-weight: 700; color: #1e293b; }
.sb-adm-fsec-head .hint { margin-left: auto; font-size: 10px; color: #94a3b8; }

/* 폼 필드 */
.sb-adm-f { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; }
.sb-adm-f .row { display: flex; flex-direction: column; gap: 4px; }
.sb-adm-f .row.full { grid-column: 1 / -1; }
.sb-adm-f label { font-size: 11px; font-weight: 600; color: #475569; }
.sb-adm-f label .req { color: #dc2626; margin-left: 2px; }
.sb-adm-f .ctl { min-height: 34px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; padding: 8px 11px; font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px; }
.sb-adm-f .ctl.filled { color: #334155; }
.sb-adm-f .ctl.sel::after { content: '▾'; margin-left: auto; color: #94a3b8; }
.sb-adm-f .ctl.area { min-height: 70px; align-items: flex-start; }
.sb-adm-f .ctl.pre { color: #cbd5e1; }
.sb-adm-f .suffix { margin-left: auto; font-size: 11px; color: #94a3b8; }
.sb-adm-f .help { font-size: 10px; color: #94a3b8; }

/* 토글/세그먼트 */
.sb-adm-seg { display: inline-flex; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.sb-adm-seg span { padding: 7px 12px; font-size: 11px; color: #64748b; border-right: 1px solid #e2e8f0; background: #fff; }
.sb-adm-seg span:last-child { border-right: none; }
.sb-adm-seg span.is-active { background: #6366f1; color: #fff; font-weight: 700; }
.sb-adm-switch { display: inline-flex; align-items: center; gap: 7px; font-size: 11px; color: #475569; }
.sb-adm-switch i { width: 30px; height: 17px; border-radius: 999px; background: #6366f1; position: relative; flex-shrink: 0; }
.sb-adm-switch i::after { content: ''; position: absolute; top: 2px; right: 2px; width: 13px; height: 13px; border-radius: 50%; background: #fff; }
.sb-adm-switch.off i { background: #cbd5e1; }
.sb-adm-switch.off i::after { right: auto; left: 2px; }

/* 이미지 업로더 목업 */
.sb-adm-uploader { display: flex; gap: 8px; flex-wrap: wrap; }
.sb-adm-upcell { width: 66px; height: 66px; border-radius: 9px; border: 1px solid #e2e8f0; background: #f8fafc; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; font-size: 9px; color: #94a3b8; }
.sb-adm-upcell.main { border-color: #a5b4fc; background: #eef2ff; color: #4f46e5; font-weight: 700; }
.sb-adm-upcell.add { border-style: dashed; }

/* 옵션 조합 미니 테이블 */
.sb-adm-opt-table { width: 100%; border-collapse: collapse; font-size: 11px; border: 1px solid #eef2f7; border-radius: 8px; overflow: hidden; }
.sb-adm-opt-table th, .sb-adm-opt-table td { padding: 7px 9px; text-align: left; border-bottom: 1px solid #f1f5f9; }
.sb-adm-opt-table thead th { background: #f8fafc; font-size: 10px; font-weight: 700; color: #64748b; }
.sb-adm-opt-table td.num { text-align: right; }

@media (max-width: 900px) {
  .sb-adm-kpis, .sb-adm-grid--2, .sb-adm-grid--3, .sb-adm-grid--2-1 { grid-template-columns: 1fr 1fr; }
  .sb-adm-f { grid-template-columns: 1fr; }
}
