<?php
/** HOME 로그인 후 대시보드 — hi-fi 스타일 */
?>
.sb-hifi-home {
    --hifi-primary: #7c3aed;
    --hifi-primary-dark: #6d28d9;
    --hifi-primary-soft: #ede9fe;
    --hifi-border: #e2e8f0;
    --hifi-text: #1e293b;
    --hifi-muted: #64748b;
    --hifi-bg: #f8fafc;
    font-family: 'Inter', 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: 13px;
    color: var(--hifi-text);
    background: #fff;
    min-width: 1280px;
    min-height: 820px;
    height: 100%;
    display: flex;
    line-height: 1.45;
    box-sizing: border-box;
}
.sb-hifi-home *, .sb-hifi-home *::before, .sb-hifi-home *::after { box-sizing: border-box; }

/* ── 사이드바 (2단) ── */
.sb-hifi-home__sidebar { flex-shrink: 0; display: flex; border-right: 1px solid var(--hifi-border); background: #fff; min-height: 100%; align-self: stretch; }
.sb-hifi-home__icon-rail {
    width: 48px; background: #f8fafc; border-right: 1px solid var(--hifi-border);
    display: flex; flex-direction: column; align-items: center; padding: 12px 0; gap: 4px;
}
.sb-hifi-home__icon-btn {
    width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
    font-size: 14px; color: #64748b;
}
.sb-hifi-home__icon-btn.is-active { background: var(--hifi-primary-soft); color: var(--hifi-primary-dark); }
.sb-hifi-home__nav-panel { width: 200px; display: flex; flex-direction: column; min-height: 820px; height: 100%; }
.sb-hifi-home__logo { padding: 16px 14px 12px; border-bottom: 1px solid var(--hifi-border); }
.sb-hifi-home__logo strong { display: block; font-size: 15px; font-weight: 800; }
.sb-hifi-home__logo small { display: block; font-size: 9px; color: var(--hifi-muted); letter-spacing: .12em; margin-top: 2px; }
.sb-hifi-home__nav-scroll { flex: 1; display: flex; flex-direction: column; padding: 8px 6px; overflow: hidden; }
.sb-hifi-home__nav-item {
    display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 8px;
    font-size: 12px; color: #475569; margin-bottom: 1px;
}
.sb-hifi-home__nav-item.is-active { background: var(--hifi-primary-soft); color: var(--hifi-primary-dark); font-weight: 600; }
.sb-hifi-home__nav-icon { width: 18px; text-align: center; font-size: 13px; opacity: .85; }
.sb-hifi-home__nav-badge {
    margin-left: auto; font-size: 8px; font-weight: 700; padding: 2px 6px; border-radius: 999px;
    background: var(--hifi-primary); color: #fff;
}
.sb-hifi-home__nav-badge--beta { background: #dbeafe; color: #1d4ed8; }
.sb-hifi-home__nav-section { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; padding: 12px 10px 6px; }
.sb-hifi-home__nav-footer { flex-shrink: 0; padding: 8px 6px 10px; border-top: 1px solid var(--hifi-border); display: flex; flex-direction: column; gap: 8px; }
.sb-hifi-home__status-card {
    padding: 10px 12px; border-radius: 10px; border: 1px solid var(--hifi-border); background: #f8fafc;
}
.sb-hifi-home__status-card--premium { background: #eef2ff; border-color: #c7d2fe; }
.sb-hifi-home__status-title { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
.sb-hifi-home__status-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px; }
.sb-hifi-home__status-value { font-size: 11px; font-weight: 700; color: #334155; }
.sb-hifi-home__status-meta { font-size: 9px; color: #94a3b8; }
.sb-hifi-home__status-progress { height: 6px; border-radius: 999px; background: #e2e8f0; overflow: hidden; margin-bottom: 8px; }
.sb-hifi-home__status-progress-bar { height: 100%; width: 32%; border-radius: 999px; background: linear-gradient(90deg, #7c3aed, #a78bfa); }
.sb-hifi-home__status-btn {
    display: block; width: 100%; padding: 7px 10px; border-radius: 8px; border: 1px solid var(--hifi-border);
    background: #fff; font-size: 11px; font-weight: 600; color: #334155; text-align: center;
}
.sb-hifi-home__status-card--premium .sb-hifi-home__status-title { color: #5b21b6; }
.sb-hifi-home__nav-bottom { padding: 0; }
.sb-hifi-home__points {
    padding: 12px; border-radius: 12px; background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1px solid #fde68a;
    display: flex; align-items: center; gap: 10px;
}
.sb-hifi-home__points-coin { font-size: 28px; line-height: 1; }
.sb-hifi-home__points-label { font-size: 10px; color: #92400e; }
.sb-hifi-home__points-value { font-size: 16px; font-weight: 800; color: #b45309; }
.sb-hifi-home__points-link { font-size: 10px; color: #d97706; }

/* ── 메인 ── */
.sb-hifi-home__main { flex: 1; min-width: 0; display: flex; flex-direction: column; background: var(--hifi-bg); }
.sb-hifi-home__topbar {
    display: flex; align-items: center; gap: 12px; padding: 10px 20px;
    background: #fff; border-bottom: 1px solid var(--hifi-border);
}
.sb-hifi-home__search {
    flex: 1; max-width: 420px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between;
    padding: 8px 14px; border: 1px solid var(--hifi-border); border-radius: 999px; background: #fafbfc; color: #94a3b8; font-size: 12px;
}
.sb-hifi-home__search-kbd { font-size: 10px; padding: 2px 6px; border: 1px solid var(--hifi-border); border-radius: 4px; background: #fff; }
.sb-hifi-home__top-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; }
.sb-hifi-home__icon-btn-top {
    width: 34px; height: 34px; border-radius: 10px; border: 1px solid var(--hifi-border); background: #fff;
    display: flex; align-items: center; justify-content: center; position: relative; font-size: 14px;
}
.sb-hifi-home__bell-dot {
    position: absolute; top: -3px; right: -3px; min-width: 16px; height: 16px; border-radius: 999px;
    background: #ef4444; color: #fff; font-size: 9px; font-weight: 700; display: flex; align-items: center; justify-content: center;
}
.sb-hifi-home__profile { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; }
.sb-hifi-home__avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #a78bfa, #7c3aed); }
.sb-hifi-home__pro-badge { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 999px; background: var(--hifi-primary-soft); color: var(--hifi-primary-dark); }

.sb-hifi-home__content { flex: 1; padding: 20px; display: flex; flex-direction: column; gap: 16px; overflow: auto; }

/* 히어로 행 */
.sb-hifi-home__hero-row { display: grid; grid-template-columns: 1fr 340px; gap: 16px; align-items: stretch; }
.sb-hifi-home__greeting-card {
    background: #fff; border: 1px solid var(--hifi-border); border-radius: 16px; padding: 22px 24px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.sb-hifi-home__greeting-card h2 { margin: 0 0 6px; font-size: 22px; font-weight: 800; letter-spacing: -.02em; }
.sb-hifi-home__greeting-card p { margin: 0 0 16px; font-size: 13px; color: var(--hifi-muted); }
.sb-hifi-home__quick-actions { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
.sb-hifi-home__quick-btn {
    display: flex; align-items: center; gap: 8px; padding: 10px 12px; border-radius: 10px;
    border: 1px solid var(--hifi-border); background: #fff; font-size: 11px; font-weight: 600; color: #334155;
}
.sb-hifi-home__quick-btn.is-primary { background: var(--hifi-primary); border-color: var(--hifi-primary); color: #fff; }
.sb-hifi-home__ai-banner {
    border-radius: 16px; padding: 20px; color: #fff; position: relative; overflow: hidden;
    background: linear-gradient(135deg, #7c3aed 0%, #6366f1 50%, #818cf8 100%);
    box-shadow: 0 8px 24px rgba(124,58,237,.25);
}
.sb-hifi-home__ai-banner-badge { font-size: 9px; font-weight: 800; padding: 3px 8px; border-radius: 999px; background: rgba(255,255,255,.25); display: inline-block; margin-bottom: 8px; }
.sb-hifi-home__ai-banner h3 { margin: 0 0 6px; font-size: 16px; font-weight: 800; line-height: 1.35; }
.sb-hifi-home__ai-banner p { margin: 0 0 14px; font-size: 11px; opacity: .9; line-height: 1.5; }
.sb-hifi-home__ai-banner-btn {
    display: inline-flex; padding: 8px 16px; border-radius: 8px; background: #fff; color: var(--hifi-primary-dark);
    font-size: 11px; font-weight: 700;
}
.sb-hifi-home__ai-robot { position: absolute; right: 12px; bottom: 8px; font-size: 56px; opacity: .9; }

/* 중간 행 */
.sb-hifi-home__mid-row { display: grid; grid-template-columns: 1.4fr 1fr; gap: 16px; }
.sb-hifi-home__panel {
    background: #fff; border: 1px solid var(--hifi-border); border-radius: 16px; padding: 18px 20px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.sb-hifi-home__panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.sb-hifi-home__panel-head h3 { margin: 0; font-size: 14px; font-weight: 700; }
.sb-hifi-home__panel-link { font-size: 11px; color: var(--hifi-primary); font-weight: 600; }
.sb-hifi-home__recent-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.sb-hifi-home__recent-card {
    border: 1px solid var(--hifi-border); border-radius: 12px; overflow: hidden; background: #fff;
}
.sb-hifi-home__recent-thumb {
    height: 72px; display: flex; align-items: center; justify-content: center; font-size: 28px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
}
.sb-hifi-home__recent-meta { padding: 8px 10px; }
.sb-hifi-home__recent-title { font-size: 11px; font-weight: 600; color: #334155; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sb-hifi-home__recent-time { font-size: 10px; color: #94a3b8; }

.sb-hifi-home__usage-body { display: flex; gap: 16px; align-items: flex-start; }
.sb-hifi-home__donut {
    width: 88px; height: 88px; border-radius: 50%; flex-shrink: 0;
    background: conic-gradient(var(--hifi-primary) 0 270deg, #e2e8f0 270deg);
    display: flex; align-items: center; justify-content: center; position: relative;
}
.sb-hifi-home__donut::after {
    content: ''; width: 58px; height: 58px; border-radius: 50%; background: #fff; position: absolute;
}
.sb-hifi-home__donut span { position: relative; z-index: 1; font-size: 14px; font-weight: 800; color: var(--hifi-primary-dark); }
.sb-hifi-home__usage-list { flex: 1; list-style: none; margin: 0; padding: 0; font-size: 11px; }
.sb-hifi-home__usage-list li { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #f1f5f9; color: #64748b; }
.sb-hifi-home__usage-list li:last-child { border-bottom: none; }
.sb-hifi-home__usage-list strong { color: #334155; }
.sb-hifi-home__upgrade-box {
    margin-top: 12px; padding: 10px 12px; border-radius: 10px; background: var(--hifi-primary-soft);
    font-size: 10px; color: var(--hifi-primary-dark); line-height: 1.45;
}

/* 하단 행 */
.sb-hifi-home__bottom-row { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 16px; }
.sb-hifi-home__ai-tools { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
.sb-hifi-home__tool-card {
    padding: 12px; border-radius: 12px; border: 1px solid transparent; min-height: 72px;
}
.sb-hifi-home__tool-card--purple { background: linear-gradient(135deg, #ede9fe, #f5f3ff); border-color: #ddd6fe; }
.sb-hifi-home__tool-card--green { background: linear-gradient(135deg, #dcfce7, #f0fdf4); border-color: #bbf7d0; }
.sb-hifi-home__tool-card--blue { background: linear-gradient(135deg, #dbeafe, #eff6ff); border-color: #bfdbfe; }
.sb-hifi-home__tool-card--yellow { background: linear-gradient(135deg, #fef9c3, #fefce8); border-color: #fde68a; }
.sb-hifi-home__tool-card strong { display: block; font-size: 11px; font-weight: 700; margin-bottom: 4px; color: #334155; }
.sb-hifi-home__tool-card span { font-size: 10px; color: #64748b; line-height: 1.35; }

.sb-hifi-home__noti-list { list-style: none; margin: 0; padding: 0; }
.sb-hifi-home__noti-list li {
    display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 11px;
}
.sb-hifi-home__noti-list li:last-child { border-bottom: none; }
.sb-hifi-home__noti-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--hifi-primary); flex-shrink: 0; margin-top: 4px; }
.sb-hifi-home__noti-text { flex: 1; color: #334155; line-height: 1.4; }
.sb-hifi-home__noti-time { font-size: 10px; color: #94a3b8; white-space: nowrap; }

.sb-hifi-home__tip-list { list-style: none; margin: 0; padding: 0; }
.sb-hifi-home__tip-list li {
    display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f1f5f9;
    font-size: 11px; color: #475569; font-weight: 500;
}
.sb-hifi-home__tip-list li:last-child { border-bottom: none; }
.sb-hifi-home__tip-icon {
    width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9;
    display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0;
}

/* 영역 표시 */
.sb-hifi-home .sb-wf-zone { position: relative; border: none; background: transparent; }
.sb-hifi-home .sb-wf-zone-label { display: none; z-index: 5; }
.sb-wf-annotate .sb-hifi-home .sb-wf-zone-label { display: inline-block; }
.sb-wf-annotate .sb-hifi-home .sb-wf-zone { outline: 2px dashed rgba(124, 58, 237, .35); outline-offset: 1px; }
.sb-wf-annotate .sb-hifi-home .sb-wf-zone:hover { outline-color: #7c3aed; }
.sb-wf-annotate .sb-hifi-home .sb-wf-zone.is-selected { outline: 2px solid #7c3aed; outline-offset: 1px; }
.sb-wf--hifi.sb-wf-annotate .sb-hifi-home .sb-wf-zone-label { display: inline-block; }
