<?php
/**
 * 라벨 편집기 허브 공통 스타일 (템플릿·규격·내디자인 등)
 */
?>
.sb-hifi-hub {
    display: flex;
    height: 100%;
    min-height: 680px;
    background: #f1f5f9;
    font-family: 'Pretendard', 'Noto Sans KR', sans-serif;
    color: #0f172a;
}
.sb-hifi-hub__sidebar { display: flex; flex-shrink: 0; background: #0f172a; color: #e2e8f0; }
.sb-hifi-hub__icon-rail {
    width: 52px; display: flex; flex-direction: column; align-items: center; gap: 8px;
    padding: 12px 0; border-right: 1px solid #1e293b; position: relative;
}
.sb-hifi-hub__icon-btn {
    width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center;
    justify-content: center; font-size: 14px; color: #94a3b8;
}
.sb-hifi-hub__icon-btn.is-active { background: #1d4ed8; color: #fff; }
.sb-hifi-hub__nav-panel {
    width: 200px; padding: 16px 12px; position: relative; background: #111827;
    display: flex; flex-direction: column;
}
.sb-hifi-hub__logo { margin-bottom: 14px; }
.sb-hifi-hub__logo strong { display: block; font-size: 15px; }
.sb-hifi-hub__logo small { color: #64748b; font-size: 10px; letter-spacing: .08em; }
.sb-hifi-hub__nav-section {
    font-size: 10px; color: #64748b; letter-spacing: .06em; margin: 10px 8px 6px; text-transform: uppercase;
}
.sb-hifi-hub__nav-item {
    display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 8px;
    font-size: 13px; color: #94a3b8; margin-bottom: 2px;
}
.sb-hifi-hub__nav-item.is-active { background: #1e293b; color: #fff; }
.sb-hifi-hub__nav-item.is-child { padding-left: 28px; font-size: 12px; }
.sb-hifi-hub__main { flex: 1; display: flex; flex-direction: column; min-width: 0; background: #fff; overflow: hidden; }
.sb-hifi-hub__top {
    display: flex; align-items: center; gap: 12px; padding: 12px 20px;
    border-bottom: 1px solid #e2e8f0; position: relative; flex-shrink: 0;
}
.sb-hifi-hub__title-wrap h1 { margin: 0; font-size: 18px; font-weight: 800; }
.sb-hifi-hub__title-wrap p { margin: 2px 0 0; font-size: 12px; color: #64748b; }
.sb-hifi-hub__top-actions { margin-left: auto; display: flex; gap: 8px; align-items: center; }
.sb-hifi-hub__btn {
    display: inline-flex; align-items: center; height: 32px; padding: 0 12px; border-radius: 8px;
    font-size: 12px; font-weight: 600; border: 1px solid #e2e8f0; background: #fff; color: #334155;
}
.sb-hifi-hub__btn--primary { background: #2563eb; border-color: #2563eb; color: #fff; }
.sb-hifi-hub__btn--accent { background: #0f172a; border-color: #0f172a; color: #fff; }
.sb-hifi-hub__scroll { flex: 1; overflow: auto; padding: 16px 20px 28px; }
.sb-hifi-hub__toolbar {
    display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 14px; position: relative;
}
.sb-hifi-hub__search {
    display: flex; align-items: center; gap: 8px; flex: 1; min-width: 200px; max-width: 360px;
    padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; color: #94a3b8; font-size: 12px;
}
.sb-hifi-hub__chips { display: flex; flex-wrap: wrap; gap: 6px; }
.sb-hifi-hub__chip {
    padding: 5px 10px; border-radius: 999px; font-size: 11px; font-weight: 600;
    border: 1px solid #e2e8f0; background: #fff; color: #64748b;
}
.sb-hifi-hub__chip.is-active { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.sb-hifi-hub__chip--pro { background: #fff7ed; border-color: #fed7aa; color: #c2410c; }
.sb-hifi-hub__grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; position: relative;
}
.sb-hifi-hub__grid--4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.sb-hifi-hub__grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.sb-hifi-hub__grid--2 { grid-template-columns: 1fr 1fr; }
.sb-hifi-hub__card {
    border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; position: relative;
}
.sb-hifi-hub__thumb {
    height: 110px; background: linear-gradient(135deg, #e2e8f0, #f8fafc);
    display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 11px; border-bottom: 1px solid #e2e8f0;
}
.sb-hifi-hub__thumb--pro { background: linear-gradient(135deg, #ffedd5, #fff7ed); }
.sb-hifi-hub__thumb--share { background: linear-gradient(135deg, #dbeafe, #eff6ff); }
.sb-hifi-hub__meta { padding: 10px; }
.sb-hifi-hub__meta strong { display: block; font-size: 12px; margin-bottom: 2px; }
.sb-hifi-hub__meta span { font-size: 11px; color: #64748b; }
.sb-hifi-hub__badge {
    position: absolute; top: 8px; left: 8px; font-size: 10px; font-weight: 700;
    padding: 2px 7px; border-radius: 999px; background: #2563eb; color: #fff;
}
.sb-hifi-hub__badge--pro { background: #ea580c; }
.sb-hifi-hub__badge--free { background: #16a34a; }
.sb-hifi-hub__badge--share { background: #0284c7; }
.sb-hifi-hub__panel {
    border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; background: #fff; margin-bottom: 12px; position: relative;
}
.sb-hifi-hub__panel h2 { margin: 0 0 8px; font-size: 15px; font-weight: 800; }
.sb-hifi-hub__panel p.lead { margin: 0 0 12px; font-size: 12px; color: #64748b; }
.sb-hifi-hub__options {
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px;
}
.sb-hifi-hub__option {
    border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 12px; text-align: center; background: #f8fafc;
}
.sb-hifi-hub__option strong { display: block; font-size: 13px; margin: 8px 0 4px; }
.sb-hifi-hub__option span { font-size: 11px; color: #64748b; }
.sb-hifi-hub__option-icon {
    width: 44px; height: 44px; margin: 0 auto; border-radius: 12px; background: #eff6ff; color: #1d4ed8;
    display: flex; align-items: center; justify-content: center; font-size: 18px;
}
.sb-hifi-hub__table { width: 100%; border-collapse: collapse; font-size: 12px; }
.sb-hifi-hub__table th, .sb-hifi-hub__table td {
    border-bottom: 1px solid #e2e8f0; padding: 10px 8px; text-align: left; vertical-align: middle;
}
.sb-hifi-hub__table th { font-size: 11px; color: #64748b; font-weight: 600; }
.sb-hifi-hub__status {
    display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700;
    background: #f1f5f9; color: #475569;
}
.sb-hifi-hub__status--ok { background: #dcfce7; color: #166534; }
.sb-hifi-hub__status--warn { background: #fef9c3; color: #854d0e; }
.sb-hifi-hub__status--ship { background: #dbeafe; color: #1e40af; }
.sb-hifi-hub__form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.sb-hifi-hub__field label { display: block; font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600; }
.sb-hifi-hub__field .box {
    height: 36px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc;
    display: flex; align-items: center; padding: 0 10px; font-size: 12px; color: #334155;
}
.sb-hifi-hub__split { display: grid; grid-template-columns: 280px 1fr; gap: 14px; }
.sb-hifi-hub__filters {
    border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; background: #fff; position: relative;
}
.sb-hifi-hub__filters h3 { margin: 0 0 10px; font-size: 13px; }
.sb-hifi-hub__filter-group { margin-bottom: 12px; }
.sb-hifi-hub__filter-group strong { display: block; font-size: 11px; margin-bottom: 6px; color: #475569; }
.sb-hifi-hub__check { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #334155; margin-bottom: 4px; }
.sb-hifi-hub__check i {
    width: 14px; height: 14px; border: 1px solid #cbd5e1; border-radius: 3px; display: inline-block; background: #fff;
}
.sb-hifi-hub__check.is-on i { background: #2563eb; border-color: #2563eb; }
.sb-hifi-hub__tools {
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px;
}
.sb-hifi-hub__tool {
    border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 14px; background: #fff; min-height: 140px;
}
.sb-hifi-hub__tool strong { display: block; font-size: 14px; margin: 10px 0 6px; }
.sb-hifi-hub__tool p { margin: 0; font-size: 12px; color: #64748b; line-height: 1.45; }
.sb-hifi-hub__banner {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 16px 18px; border-radius: 12px; background: #0f172a; color: #e2e8f0; margin-bottom: 14px; position: relative;
}
.sb-hifi-hub__banner strong { display: block; font-size: 14px; color: #fff; margin-bottom: 4px; }
.sb-hifi-hub__banner p { margin: 0; font-size: 12px; color: #94a3b8; }
.sb-hifi-hub__tabs {
    display: flex; gap: 4px; margin-bottom: 14px; border-bottom: 1px solid #e2e8f0; position: relative;
}
.sb-hifi-hub__tab {
    padding: 10px 14px; font-size: 12px; font-weight: 600; color: #64748b; border-bottom: 2px solid transparent;
}
.sb-hifi-hub__tab.is-active { color: #1d4ed8; border-bottom-color: #2563eb; }
.sb-hifi-hub__list-row {
    display: flex; align-items: center; gap: 12px; padding: 10px; border: 1px solid #e2e8f0;
    border-radius: 10px; margin-bottom: 8px; background: #fff;
}
.sb-hifi-hub__list-thumb {
    width: 56px; height: 56px; border-radius: 8px; background: #e2e8f0; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 10px; color: #94a3b8;
}
.sb-hifi-hub__list-body { flex: 1; min-width: 0; }
.sb-hifi-hub__list-body strong { display: block; font-size: 13px; }
.sb-hifi-hub__list-body span { font-size: 11px; color: #64748b; }
@media (max-width: 1000px) {
    .sb-hifi-hub__nav-panel { display: none; }
    .sb-hifi-hub__options, .sb-hifi-hub__tools, .sb-hifi-hub__grid--4 { grid-template-columns: repeat(2, 1fr); }
    .sb-hifi-hub__split { grid-template-columns: 1fr; }
    .sb-hifi-hub__form-grid { grid-template-columns: 1fr; }
}
