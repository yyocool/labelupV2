<?php
/**
 * Label-UP 도움말 하이파이 스타일 (01-05-06)
 * 01-05-07과 동일한 화면 스타일을 복제 — 클래스명(sb-hifi-help__*)은 그대로 유지
 */
?>
.sb-hifi-help {
    display: flex;
    height: 100%;
    min-height: 640px;
    background: #f1f5f9;
    font-family: 'Pretendard', 'Noto Sans KR', sans-serif;
    color: #0f172a;
}
.sb-hifi-help__sidebar {
    display: flex;
    flex-shrink: 0;
    background: #0f172a;
    color: #e2e8f0;
}
.sb-hifi-help__icon-rail {
    width: 52px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 12px 0;
    border-right: 1px solid #1e293b;
    position: relative;
}
.sb-hifi-help__icon-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #94a3b8;
    background: transparent;
}
.sb-hifi-help__icon-btn.is-active { background: #1d4ed8; color: #fff; }
.sb-hifi-help__nav-panel {
    width: 200px;
    padding: 16px 12px;
    position: relative;
    background: #111827;
}
.sb-hifi-help__logo { margin-bottom: 16px; }
.sb-hifi-help__logo strong { display: block; font-size: 15px; }
.sb-hifi-help__logo small { color: #64748b; font-size: 10px; letter-spacing: .08em; }
.sb-hifi-help__nav-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 4px;
}
.sb-hifi-help__nav-item.is-active { background: #1e293b; color: #fff; }
.sb-hifi-help__main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: #fff;
}
.sb-hifi-help__top {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px 20px;
    border-bottom: 1px solid #e2e8f0;
    position: relative;
}
.sb-hifi-help__title { margin: 0; font-size: 18px; font-weight: 700; }
.sb-hifi-help__sub { margin: 2px 0 0; font-size: 12px; color: #64748b; }
.sb-hifi-help__search {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 12px;
    min-width: 280px;
}
.sb-hifi-help__search input {
    border: 0;
    background: transparent;
    outline: none;
    width: 100%;
    font-size: 13px;
}
.sb-hifi-help__close {
    width: 36px;
    height: 36px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    cursor: pointer;
    font-size: 16px;
}
.sb-hifi-help__tabs {
    display: flex;
    gap: 6px;
    padding: 10px 20px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    position: relative;
    flex-wrap: wrap;
}
.sb-hifi-help__tab {
    border: 1px solid transparent;
    background: transparent;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 12px;
    color: #64748b;
    cursor: pointer;
}
.sb-hifi-help__tab.is-active {
    background: #fff;
    border-color: #cbd5e1;
    color: #0f172a;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(15,23,42,.06);
}
.sb-hifi-help__body {
    flex: 1;
    display: grid;
    grid-template-columns: 1fr 260px;
    gap: 16px;
    padding: 16px 20px 24px;
    overflow: auto;
    background: #f8fafc;
}
.sb-hifi-help__section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 18px;
    margin-bottom: 14px;
    position: relative;
}
.sb-hifi-help__section h2 {
    margin: 0 0 14px;
    font-size: 15px;
}
.sb-hifi-help__cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}
.sb-hifi-help__card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px;
    background: #f8fafc;
}
.sb-hifi-help__card strong { display: block; font-size: 13px; margin-bottom: 6px; }
.sb-hifi-help__card p { margin: 0 0 8px; font-size: 12px; color: #475569; line-height: 1.45; }
.sb-hifi-help__link { font-size: 11px; color: #2563eb; }
.sb-hifi-help__keys {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.sb-hifi-help__keys th,
.sb-hifi-help__keys td {
    border-bottom: 1px solid #e2e8f0;
    padding: 8px 6px;
    text-align: left;
}
.sb-hifi-help__keys th { color: #64748b; font-weight: 600; }
.sb-hifi-help__aside { display: flex; flex-direction: column; gap: 12px; }
.sb-hifi-help__aside-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px;
    position: relative;
}
.sb-hifi-help__aside-card h3 { margin: 0 0 10px; font-size: 13px; }
.sb-hifi-help__aside-card ul { margin: 0; padding-left: 16px; font-size: 12px; color: #334155; line-height: 1.7; }
.sb-hifi-help__aside-card--cta { background: linear-gradient(160deg, #eff6ff, #fff); }
.sb-hifi-help__aside-card--cta p { margin: 0 0 10px; font-size: 12px; color: #64748b; }
.sb-hifi-help__cta {
    display: block;
    width: 100%;
    margin-bottom: 6px;
    border: 0;
    border-radius: 10px;
    padding: 9px 12px;
    background: #2563eb;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}
.sb-hifi-help__cta--ghost {
    background: #fff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}
@media (max-width: 1100px) {
    .sb-hifi-help__body { grid-template-columns: 1fr; }
    .sb-hifi-help__cards { grid-template-columns: 1fr; }
    .sb-hifi-help__nav-panel { display: none; }
}
