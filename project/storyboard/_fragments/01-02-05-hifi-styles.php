.sb-hifi-simple {
    --hifi-primary: #6366f1;
    --hifi-primary-dark: #4f46e5;
    --hifi-primary-soft: #eef2ff;
    --hifi-border: #e2e8f0;
    --hifi-text: #1e293b;
    --hifi-muted: #64748b;
    --hifi-bg: #f8fafc;
    font-family: 'Inter', 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: 13px;
    color: var(--hifi-text);
    background: #fff;
    min-width: 1180px;
    min-height: 720px;
    height: 100%;
    display: flex;
    line-height: 1.45;
    box-sizing: border-box;
}
.sb-hifi-simple *, .sb-hifi-simple *::before, .sb-hifi-simple *::after { box-sizing: border-box; }

.sb-hifi-simple__sidebar {
    flex-shrink: 0;
    display: flex;
    border-right: 1px solid var(--hifi-border);
    background: #fff;
}
.sb-hifi-simple__icon-rail {
    width: 48px;
    background: #f8fafc;
    border-right: 1px solid var(--hifi-border);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px 0;
    gap: 4px;
}
.sb-hifi-simple__icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #64748b;
    cursor: default;
}
.sb-hifi-simple__icon-btn.is-active {
    background: var(--hifi-primary-soft);
    color: var(--hifi-primary-dark);
}
.sb-hifi-simple__nav-panel {
    width: 180px;
    display: flex;
    flex-direction: column;
    min-height: 720px;
    height: 100%;
}
.sb-hifi-simple__nav-scroll {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.sb-hifi-simple__nav-bottom {
    margin-top: auto;
    padding: 0 8px 8px;
}
.sb-hifi-simple__logo {
    padding: 18px 16px 14px;
    border-bottom: 1px solid var(--hifi-border);
}
.sb-hifi-simple__logo strong {
    display: block;
    font-size: 15px;
    font-weight: 800;
    letter-spacing: -.02em;
}
.sb-hifi-simple__logo small {
    display: block;
    font-size: 10px;
    color: var(--hifi-muted);
    letter-spacing: .12em;
    margin-top: 2px;
}
.sb-hifi-simple__nav {
    flex: 1;
    padding: 10px 8px;
    overflow: hidden;
}
.sb-hifi-simple__nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 13px;
    color: #475569;
    margin-bottom: 2px;
}
.sb-hifi-simple__nav-item.is-active {
    background: var(--hifi-primary-soft);
    color: var(--hifi-primary-dark);
    font-weight: 600;
}
.sb-hifi-simple__nav-icon {
    width: 22px;
    text-align: center;
    font-size: 14px;
    opacity: .85;
}
.sb-hifi-simple__points {
    padding: 14px;
    border-radius: 12px;
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1px solid #fde68a;
}
.sb-hifi-simple__points-label { font-size: 11px; color: #92400e; margin-bottom: 4px; }
.sb-hifi-simple__points-value { font-size: 18px; font-weight: 800; color: #b45309; }
.sb-hifi-simple__points-link { font-size: 11px; color: #d97706; margin-top: 6px; display: inline-block; }

.sb-hifi-simple__main { flex: 1; min-width: 0; display: flex; flex-direction: column; background: var(--hifi-bg); }
.sb-hifi-simple__topbar {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 18px;
    background: #fff;
    border-bottom: 1px solid var(--hifi-border);
}
.sb-hifi-simple__breadcrumb { font-size: 12px; color: var(--hifi-muted); white-space: nowrap; }
.sb-hifi-simple__breadcrumb strong { color: var(--hifi-text); font-weight: 600; }
.sb-hifi-simple__search {
    flex: 1;
    max-width: 380px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 14px;
    border: 1px solid var(--hifi-border);
    border-radius: 999px;
    background: #fafbfc;
    color: #94a3b8;
    font-size: 12px;
}
.sb-hifi-simple__search-kbd {
    font-size: 10px;
    padding: 2px 6px;
    border: 1px solid var(--hifi-border);
    border-radius: 4px;
    background: #fff;
}
.sb-hifi-simple__top-actions { display: flex; align-items: center; gap: 10px; margin-left: auto; }
.sb-hifi-simple__bell {
    position: relative;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1px solid var(--hifi-border);
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
}
.sb-hifi-simple__bell-dot {
    position: absolute;
    top: -3px;
    right: -3px;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    border-radius: 999px;
    background: #ef4444;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}
.sb-hifi-simple__profile {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 600;
}
.sb-hifi-simple__avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #818cf8, #6366f1);
}

.sb-hifi-simple__body { flex: 1; display: flex; gap: 0; min-height: 0; }
.sb-hifi-simple__chat {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    padding: 16px 18px;
    overflow: auto;
}
.sb-hifi-simple__mode-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}
.sb-hifi-simple__mode-tabs {
    display: flex;
    gap: 8px;
    background: #fff;
    padding: 4px;
    border-radius: 12px;
    border: 1px solid var(--hifi-border);
}
.sb-hifi-simple__mode-tab {
    padding: 10px 16px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    color: var(--hifi-muted);
    min-width: 120px;
}
.sb-hifi-simple__mode-tab.is-active {
    background: var(--hifi-primary-soft);
    color: var(--hifi-primary-dark);
    box-shadow: inset 0 0 0 1px #c7d2fe;
}
.sb-hifi-simple__mode-tab small {
    display: block;
    font-size: 10px;
    font-weight: 500;
    color: #94a3b8;
    margin-top: 2px;
}
.sb-hifi-simple__mode-tab.is-active small { color: #6366f1; }
.sb-hifi-simple__toolbar { display: flex; gap: 6px; }
.sb-hifi-simple__tool-btn {
    padding: 8px 12px;
    border: 1px solid var(--hifi-border);
    border-radius: 8px;
    background: #fff;
    font-size: 12px;
    color: #475569;
    font-weight: 500;
}

.sb-hifi-simple__intro {
    background: #fff;
    border: 1px solid var(--hifi-border);
    border-radius: 14px;
    padding: 16px 18px;
    margin-bottom: 12px;
}
.sb-hifi-simple__intro h3 {
    margin: 0 0 6px;
    font-size: 15px;
    font-weight: 700;
}
.sb-hifi-simple__intro p {
    margin: 0;
    font-size: 12px;
    color: var(--hifi-muted);
    line-height: 1.6;
}
.sb-hifi-simple__bubble {
    max-width: 88%;
    margin: 0 0 14px auto;
    padding: 12px 16px;
    border-radius: 16px 16px 4px 16px;
    background: var(--hifi-primary);
    color: #fff;
    font-size: 13px;
    line-height: 1.55;
    box-shadow: 0 4px 14px rgba(99, 102, 241, .25);
}

.sb-hifi-simple__results {
    background: #fff;
    border: 1px solid var(--hifi-border);
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 12px;
}
.sb-hifi-simple__results-head {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    font-size: 14px;
    font-weight: 700;
}
.sb-hifi-simple__results-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1.1fr;
    gap: 12px;
}
.sb-hifi-simple__result-card {
    border: 1px solid var(--hifi-border);
    border-radius: 12px;
    padding: 12px;
    background: #fafbfc;
}
.sb-hifi-simple__result-card h4 {
    margin: 0 0 10px;
    font-size: 12px;
    font-weight: 700;
    color: #334155;
}
.sb-hifi-simple__layout-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
}
.sb-hifi-simple__layout-item {
    text-align: center;
    padding: 8px 4px;
    border: 1px solid var(--hifi-border);
    border-radius: 8px;
    background: #fff;
    font-size: 9px;
    color: var(--hifi-muted);
}
.sb-hifi-simple__layout-item.is-selected {
    border-color: var(--hifi-primary);
    background: var(--hifi-primary-soft);
    color: var(--hifi-primary-dark);
    font-weight: 700;
    position: relative;
}
.sb-hifi-simple__layout-item.is-selected::after {
    content: '✓';
    position: absolute;
    top: 4px;
    right: 4px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--hifi-primary);
    color: #fff;
    font-size: 9px;
    line-height: 14px;
}
.sb-hifi-simple__layout-thumb {
    height: 36px;
    margin-bottom: 4px;
    border: 1px dashed #cbd5e1;
    border-radius: 4px;
    background: #f1f5f9;
}
.sb-hifi-simple__layout-thumb--v { width: 18px; margin: 0 auto 4px; }
.sb-hifi-simple__layout-thumb--h { width: 36px; height: 18px; margin: 9px auto 4px; }
.sb-hifi-simple__layout-thumb--sq { width: 28px; height: 28px; margin: 4px auto; }
.sb-hifi-simple__layout-thumb--ov { width: 24px; height: 32px; border-radius: 50%; margin: 2px auto 4px; }

.sb-hifi-simple__spec-list { list-style: none; margin: 0; padding: 0; }
.sb-hifi-simple__spec-list li {
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 11px;
    margin-bottom: 4px;
    border: 1px solid transparent;
}
.sb-hifi-simple__spec-list li.is-rec {
    background: var(--hifi-primary-soft);
    border-color: #c7d2fe;
    font-weight: 600;
    color: var(--hifi-primary-dark);
}
.sb-hifi-simple__spec-link { font-size: 10px; color: var(--hifi-primary); margin-top: 6px; display: inline-block; }

.sb-hifi-simple__preview-box {
    position: relative;
    height: 130px;
    border: 1px solid var(--hifi-border);
    border-radius: 10px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.sb-hifi-simple__preview-label {
    width: 56px;
    height: 80px;
    border-radius: 4px;
    background: linear-gradient(180deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #86efac;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 6px;
    text-align: center;
    font-size: 7px;
    font-weight: 700;
    color: #166534;
    line-height: 1.2;
}
.sb-hifi-simple__dim-h, .sb-hifi-simple__dim-v {
    position: absolute;
    font-size: 9px;
    color: #94a3b8;
    font-weight: 600;
}
.sb-hifi-simple__dim-h { bottom: 8px; left: 50%; transform: translateX(-50%); }
.sb-hifi-simple__dim-v { right: 8px; top: 50%; transform: translateY(-50%) rotate(90deg); }

.sb-hifi-simple__summary {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 16px;
    padding: 10px 14px;
    background: #fff;
    border: 1px solid var(--hifi-border);
    border-radius: 10px;
    font-size: 11px;
    color: var(--hifi-muted);
    margin-bottom: 12px;
}
.sb-hifi-simple__summary strong { color: var(--hifi-text); }

.sb-hifi-simple__input-area {
    margin-top: auto;
    background: #fff;
    border: 1px solid var(--hifi-border);
    border-radius: 14px;
    padding: 14px;
}
.sb-hifi-simple__quick-actions {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}
.sb-hifi-simple__quick-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border: 1px solid var(--hifi-border);
    border-radius: 10px;
    background: #fafbfc;
    font-size: 11px;
    color: #475569;
}
.sb-hifi-simple__input-row {
    display: flex;
    gap: 8px;
    align-items: flex-end;
}
.sb-hifi-simple__input-field {
    flex: 1;
    min-height: 44px;
    padding: 12px 14px;
    border: 1px solid var(--hifi-border);
    border-radius: 12px;
    background: #fafbfc;
    color: #94a3b8;
    font-size: 13px;
}
.sb-hifi-simple__send {
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 12px;
    background: var(--hifi-primary);
    color: #fff;
    font-size: 18px;
    box-shadow: 0 4px 12px rgba(99, 102, 241, .35);
    flex-shrink: 0;
}
.sb-hifi-simple__input-hint {
    margin: 8px 0 0;
    font-size: 11px;
    color: #94a3b8;
    text-align: center;
}

.sb-hifi-simple__aside {
    width: 280px;
    flex-shrink: 0;
    border-left: 1px solid var(--hifi-border);
    background: #fff;
    padding: 16px;
    overflow: auto;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.sb-hifi-simple__panel {
    border: 1px solid var(--hifi-border);
    border-radius: 12px;
    padding: 14px;
    background: #fafbfc;
}
.sb-hifi-simple__panel h4 {
    margin: 0 0 10px;
    font-size: 13px;
    font-weight: 700;
}
.sb-hifi-simple__label-preview {
    border-radius: 10px;
    overflow: hidden;
    background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 50%, #a7f3d0 100%);
    padding: 20px 16px;
    text-align: center;
    min-height: 220px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 1px solid #86efac;
}
.sb-hifi-simple__label-art {
    width: 120px;
    padding: 16px 12px;
    background: rgba(255,255,255,.92);
    border-radius: 6px;
    box-shadow: 0 8px 24px rgba(22, 101, 52, .15);
    font-size: 9px;
    line-height: 1.35;
    color: #14532d;
}
.sb-hifi-simple__label-art .brand { font-size: 7px; letter-spacing: .08em; color: #15803d; margin-bottom: 6px; }
.sb-hifi-simple__label-art .title { font-size: 11px; font-weight: 800; margin-bottom: 4px; line-height: 1.2; }
.sb-hifi-simple__label-art .sub { font-size: 8px; color: #166534; margin-bottom: 8px; }
.sb-hifi-simple__label-art .badge {
    display: inline-block;
    padding: 2px 6px;
    border: 1px solid #22c55e;
    border-radius: 999px;
    font-size: 7px;
    margin-bottom: 8px;
}
.sb-hifi-simple__label-art .vol { font-size: 10px; font-weight: 700; }
.sb-hifi-simple__leaf {
    width: 40px;
    height: 40px;
    margin: 0 auto 8px;
    border-radius: 50% 0 50% 0;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    opacity: .9;
}
.sb-hifi-simple__zoom-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin: 10px 0;
    font-size: 12px;
    color: var(--hifi-muted);
}
.sb-hifi-simple__cta {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 10px;
    background: var(--hifi-primary);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    box-shadow: 0 4px 14px rgba(99, 102, 241, .3);
}
.sb-hifi-simple__reasons { list-style: none; margin: 0; padding: 0; }
.sb-hifi-simple__reasons li {
    position: relative;
    padding: 6px 0 6px 16px;
    font-size: 11px;
    color: var(--hifi-muted);
    line-height: 1.45;
    border-bottom: 1px dotted var(--hifi-border);
}
.sb-hifi-simple__reasons li:last-child { border-bottom: none; }
.sb-hifi-simple__reasons li::before {
    content: '•';
    position: absolute;
    left: 0;
    color: var(--hifi-primary);
    font-weight: 700;
}
.sb-hifi-simple__history { list-style: none; margin: 0; padding: 0; }
.sb-hifi-simple__history li {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
    padding: 8px 0;
    border-bottom: 1px solid var(--hifi-border);
    font-size: 11px;
}
.sb-hifi-simple__history li:last-child { border-bottom: none; }
.sb-hifi-simple__history-title { font-weight: 600; color: #334155; margin-bottom: 2px; }
.sb-hifi-simple__history-time { font-size: 10px; color: #94a3b8; white-space: nowrap; }

/* 영역 표시 (전체화면 annotate) */
.sb-hifi-simple .sb-wf-zone { position: relative; border: none; background: transparent; }
.sb-hifi-simple .sb-wf-zone-label { display: none; z-index: 5; }
.sb-wf-annotate .sb-hifi-simple .sb-wf-zone-label { display: inline-block; }
.sb-wf-annotate .sb-hifi-simple .sb-wf-zone { outline: 2px dashed rgba(99, 102, 241, .35); outline-offset: 1px; }
.sb-wf-annotate .sb-hifi-simple .sb-wf-zone:hover { outline-color: #6366f1; }
.sb-wf-annotate .sb-hifi-simple .sb-wf-zone.is-selected { outline: 2px solid #6366f1; outline-offset: 1px; }
