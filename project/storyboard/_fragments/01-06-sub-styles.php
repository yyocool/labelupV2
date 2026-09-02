<?php
/**
 * 마이페이지 하위 화면 (01-06-01~04) 전용 추가 스타일
 * 01-06-hifi-styles.php 의 CSS 변수(--mp-primary 등)를 재사용
 */
?>
.sb-hifi-mypage__m01 { position: relative; }
.sb-hifi-mypage__subtabs {
    display: flex; gap: 6px; padding: 10px 18px; background: #fff;
    border-bottom: 1px solid var(--mp-border); flex-wrap: wrap;
}
.sb-hifi-mypage__subtab {
    padding: 7px 14px; border-radius: 999px; font-size: 12px; color: #64748b; font-weight: 500;
}
.sb-hifi-mypage__subtab.is-active { background: var(--mp-primary-soft); color: var(--mp-primary); font-weight: 700; }

/* 폼 (내 정보) */
.sb-hifi-mypage__form-card { padding: 20px 22px; }
.sb-hifi-mypage__form-card h3 { margin: 0 0 4px; font-size: 14px; font-weight: 700; }
.sb-hifi-mypage__form-desc { margin: 0 0 16px; font-size: 11px; color: var(--mp-muted); }
.sb-hifi-mypage__form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 16px; }
.sb-hifi-mypage__form-row { display: flex; flex-direction: column; gap: 6px; }
.sb-hifi-mypage__form-row--full { grid-column: 1 / -1; }
.sb-hifi-mypage__form-row label { font-size: 11px; font-weight: 600; color: #334155; }
.sb-hifi-mypage__form-row input {
    height: 38px; border: 1px solid var(--mp-border); border-radius: 8px; padding: 0 12px;
    font-size: 12px; color: #1e293b; background: #fff; width: 100%;
}
.sb-hifi-mypage__form-row input[disabled],
.sb-hifi-mypage__form-row input[readonly] { background: #f8fafc; color: #64748b; }
.sb-hifi-mypage__form-actions {
    display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; padding-top: 16px;
    border-top: 1px solid #f1f5f9;
}
.sb-hifi-mypage__withdraw-card { padding: 18px 22px; background: #fef2f2; border-color: #fecaca; }
.sb-hifi-mypage__withdraw-card h3 { margin: 0 0 6px; font-size: 13px; color: #b91c1c; }
.sb-hifi-mypage__withdraw-card p { margin: 0 0 12px; font-size: 11px; color: #991b1b; line-height: 1.6; }
.sb-hifi-mypage__btn--danger { background: #fff; border-color: #fecaca; color: #dc2626; }

/* 필터 탭 (주문·배송 / 디자인 관리) */
.sb-hifi-mypage__filter-tabs { display: flex; gap: 8px; padding: 12px 18px 14px; flex-wrap: wrap; }
.sb-hifi-mypage__filter-tab {
    padding: 7px 16px; border-radius: 999px; font-size: 12px; font-weight: 600; color: #64748b;
    border: 1px solid var(--mp-border); background: #fff;
}
.sb-hifi-mypage__filter-tab.is-active { background: var(--mp-primary); border-color: var(--mp-primary); color: #fff; }

/* 표 (주문 내역) */
.sb-hifi-mypage__table-wrap { padding: 0 18px 18px; overflow-x: auto; }
.sb-hifi-mypage__table { width: 100%; border-collapse: collapse; font-size: 11px; min-width: 560px; }
.sb-hifi-mypage__table th, .sb-hifi-mypage__table td { padding: 10px 8px; text-align: left; border-bottom: 1px solid #f1f5f9; white-space: nowrap; }
.sb-hifi-mypage__table th { color: var(--mp-muted); font-weight: 600; font-size: 10px; }
.sb-hifi-mypage__table td { color: #334155; }
.sb-hifi-mypage__status--cancel { background: #fee2e2; color: #b91c1c; }

/* 결제·구독 */
.sb-hifi-mypage__pay-method {
    display: flex; align-items: center; gap: 12px; padding: 12px; border: 1px solid var(--mp-border);
    border-radius: 10px; margin-bottom: 8px;
}
.sb-hifi-mypage__pay-icon {
    width: 40px; height: 28px; border-radius: 6px; background: linear-gradient(135deg,#334155,#0f172a);
    display: flex; align-items: center; justify-content: center; color: #fff; font-size: 9px; font-weight: 700; flex-shrink: 0;
}
.sb-hifi-mypage__pay-info { flex: 1; font-size: 11px; color: #334155; }
.sb-hifi-mypage__pay-info strong { display: block; font-size: 12px; margin-bottom: 2px; }
.sb-hifi-mypage__invoice-item {
    display: flex; align-items: center; justify-content: space-between; padding: 9px 0;
    border-bottom: 1px solid #f1f5f9; font-size: 11px; color: #475569;
}
.sb-hifi-mypage__invoice-item:last-child { border-bottom: none; }
.sb-hifi-mypage__invoice-amount { font-weight: 700; color: #334155; }
.sb-hifi-mypage__invoice-link { display: inline-block; margin-top: 8px; font-size: 10px; color: var(--mp-primary); font-weight: 600; }
.sb-hifi-mypage__upgrade-banner {
    display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 22px; border-radius: 14px;
    background: linear-gradient(135deg, #0f172a, #334155); color: #fff; position: relative;
}
.sb-hifi-mypage__upgrade-banner p { margin: 0 0 4px; font-size: 13px; font-weight: 600; }
.sb-hifi-mypage__upgrade-banner .desc { font-size: 11px; opacity: .75; }
.sb-hifi-mypage__upgrade-banner .sb-hifi-mypage__btn--primary { background: #fff; color: #0f172a; border: none; }

/* 디자인 관리 그리드 + 일괄 작업 */
.sb-hifi-mypage__design-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; padding: 4px 18px 16px; }
.sb-hifi-mypage__design-grid .sb-hifi-mypage__design-card { position: relative; flex: none; }
.sb-hifi-mypage__design-card-check {
    position: absolute; top: 8px; left: 8px; width: 16px; height: 16px; border-radius: 4px;
    border: 1px solid #cbd5e1; background: #fff; z-index: 1;
}
.sb-hifi-mypage__bulk-bar {
    display: flex; align-items: center; justify-content: space-between; padding: 10px 18px;
    background: #f8fafc; border-top: 1px solid #f1f5f9;
}
.sb-hifi-mypage__bulk-bar .left { font-size: 11px; color: #475569; font-weight: 600; }
.sb-hifi-mypage__bulk-actions { display: flex; gap: 6px; }

@media (max-width: 1100px) {
    .sb-hifi-mypage__form-grid { grid-template-columns: 1fr; }
    .sb-hifi-mypage__design-grid { grid-template-columns: repeat(2, 1fr); }
}
