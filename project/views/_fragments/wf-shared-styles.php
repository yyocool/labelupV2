<?php
/** 공통 와이어프레임 CSS (01 / 01-02 등) */
?>
/* ── 스토리보드 문서 ── */
.sb-front-doc { font-family: 'Inter', 'Pretendard', -apple-system, sans-serif; color: #1e293b; }
.sb-front-doc * { box-sizing: border-box; }
.sb-front-doc-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #e2e8f0; }
.sb-front-doc-title { margin: 0; font-size: 18px; font-weight: 700; }
.sb-front-doc-sub { margin: 4px 0 0; font-size: 13px; color: #64748b; }
.sb-front-doc-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.sb-front-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid #cbd5e1; background: #fff; color: #334155; }
.sb-front-btn:hover { border-color: #6366f1; color: #6366f1; }
.sb-front-btn--primary { background: #6366f1; border-color: #6366f1; color: #fff; }
.sb-front-btn--primary:hover { background: #4f46e5; color: #fff; }
.sb-front-meta-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-bottom: 24px; }
.sb-front-meta-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; }
.sb-front-meta-card dt { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
.sb-front-meta-card dd { margin: 0; font-size: 13px; font-weight: 500; }
.sb-front-spec { margin-bottom: 28px; }
.sb-front-spec h3 { font-size: 15px; font-weight: 700; margin: 0 0 12px; display: flex; align-items: center; gap: 8px; }
.sb-front-spec h3::before { content: ''; width: 4px; height: 16px; background: #6366f1; border-radius: 2px; }
.sb-front-spec-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.sb-front-spec-table th, .sb-front-spec-table td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; vertical-align: top; }
.sb-front-spec-table th { background: #f1f5f9; font-weight: 600; white-space: nowrap; }
.sb-front-spec-table code { background: #f1f5f9; padding: 1px 5px; border-radius: 4px; font-size: 11px; }
.sb-front-spec-table .tag { display: inline-block; padding: 2px 7px; border-radius: 999px; font-size: 10px; font-weight: 600; }
.sb-front-spec-table .tag--ui { background: #dbeafe; color: #1d4ed8; }
.sb-front-spec-table .tag--cta { background: #fef3c7; color: #b45309; }
.sb-front-spec-table .tag--nav { background: #dcfce7; color: #15803d; }
.sb-front-spec-table .tag--layout { background: #f3e8ff; color: #7e22ce; }
.sb-front-preview-label { font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
.sb-front-preview-label span { background: #64748b; color: #fff; font-size: 10px; padding: 2px 8px; border-radius: 999px; }
.sb-wf-wrap { position: relative; border: 1px solid #e2e8f0; border-radius: 12px; overflow: auto; background: #f1f5f9; box-shadow: 0 1px 3px rgba(15,23,42,.06); }
.sb-wf-wrap:fullscreen { border: none; border-radius: 0; background: #e2e8f0; overflow: hidden; }
.sb-wf-wrap:fullscreen .sb-wf-viewport { position: absolute; top: 44px; left: 0; right: 0; bottom: 0; overflow: auto; }
.sb-wf-wrap:fullscreen .sb-wf-viewport.is-loading { opacity: .55; pointer-events: none; }
.sb-wf-fragment-stub { padding: 24px; min-height: 200px; }
.sb-wf-fragment-empty { padding: 48px 24px; text-align: center; color: #64748b; font-size: 14px; }
.sb-wf-fs-bar { display: none; position: fixed; top: 0; left: 0; right: 0; z-index: 9999; padding: 10px 16px; background: rgba(15,23,42,.9); justify-content: space-between; align-items: center; gap: 12px; }
.sb-wf-wrap:fullscreen .sb-wf-fs-bar { display: flex; }
.sb-wf-fs-bar-left { display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1; }
.sb-wf-fs-menu-btn { flex-shrink: 0; padding: 6px 12px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.25); border-radius: 6px; color: #fff; font-size: 12px; font-weight: 600; cursor: pointer; }
.sb-wf-fs-bar-title { color: #e2e8f0; font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sb-wf-fs-bar-hint { font-size: 11px; color: #94a3b8; font-weight: 400; margin-left: 8px; }
.sb-wf-fs-exit { flex-shrink: 0; padding: 6px 14px; background: #fff; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; }
.sb-wf-fs-menu-overlay { display: none; position: fixed; inset: 0; z-index: 10000; background: rgba(0,0,0,.5); }
.sb-wf-fs-menu-overlay.is-open { display: block; }
.sb-wf-fs-menu-panel { position: absolute; top: 0; left: 0; bottom: 0; width: min(340px, 90vw); background: #fff; box-shadow: 4px 0 24px rgba(0,0,0,.15); display: flex; flex-direction: column; font-family: 'Inter', sans-serif; }
.sb-wf-fs-menu-head { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }
.sb-wf-fs-menu-head h3 { margin: 0; font-size: 15px; font-weight: 700; }
.sb-wf-fs-menu-close { width: 28px; height: 28px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc; cursor: pointer; }
.sb-wf-fs-menu-legend { display: flex; flex-wrap: wrap; gap: 8px 14px; padding: 10px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 11px; color: #64748b; }
.sb-wf-fs-menu-legend i { display: inline-block; width: 8px; height: 8px; border-radius: 50%; }
.sb-wf-fs-menu-legend i.ready { background: #22c55e; }
.sb-wf-fs-menu-legend i.stub { background: #f59e0b; }
.sb-wf-fs-menu-legend i.none { background: #cbd5e1; }
.sb-wf-fs-menu-tree { flex: 1; overflow-y: auto; padding: 8px 0 16px; }
.sb-wf-fs-menu-list { list-style: none; margin: 0; padding: 0; }
.sb-wf-fs-menu-list ul { list-style: none; margin: 0; padding: 0 0 0 14px; }
.sb-wf-fs-menu-link { display: flex; align-items: center; gap: 8px; padding: 8px 16px; font-size: 13px; color: #334155; text-decoration: none; border-left: 3px solid transparent; }
.sb-wf-fs-menu-link.is-active { background: #eef2ff; border-left-color: #6366f1; color: #4338ca; font-weight: 600; }
.sb-wf-fs-menu-code { font-family: ui-monospace, monospace; font-size: 10px; font-weight: 700; color: #6366f1; background: rgba(99,102,241,.1); padding: 2px 6px; border-radius: 4px; }
.sb-wf-fs-menu-label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sb-wf-fs-menu-badge { flex-shrink: 0; font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 999px; }
.sb-wf-fs-menu-badge--ready { background: #dcfce7; color: #15803d; }
.sb-wf-fs-menu-badge--stub { background: #fef3c7; color: #b45309; }
.sb-wf-fs-menu-badge--none { background: #f1f5f9; color: #94a3b8; border: 1px dashed #cbd5e1; }
.sb-wf-wrap:fullscreen .sb-wf-zone-label { cursor: pointer; }
.sb-wf-wrap:fullscreen .sb-wf-zone.is-selected { outline: 3px solid #6366f1; outline-offset: 1px; z-index: 3; }
.sb-wf-info-panel { display: none; position: fixed; top: 44px; right: 0; width: 300px; bottom: 0; background: #fff; border-left: 1px solid #e2e8f0; z-index: 9998; overflow-y: auto; }
.sb-wf-wrap:fullscreen .sb-wf-info-panel.is-open { display: block; }
.sb-wf-info-head { display: flex; justify-content: space-between; padding: 16px; border-bottom: 1px solid #e2e8f0; }
.sb-wf-info-id { font-size: 18px; font-weight: 800; color: #6366f1; margin: 0; }
.sb-wf-info-type { display: inline-block; margin-top: 4px; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; }
.sb-wf-info-type--layout { background: #f3e8ff; color: #7e22ce; }
.sb-wf-info-type--nav { background: #dcfce7; color: #15803d; }
.sb-wf-info-type--ui { background: #dbeafe; color: #1d4ed8; }
.sb-wf-info-type--cta { background: #fef3c7; color: #b45309; }
.sb-wf-info-close { width: 28px; height: 28px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc; cursor: pointer; }
.sb-wf-info-body { padding: 14px 16px 20px; }
.sb-wf-info-row { margin-bottom: 14px; }
.sb-wf-info-row dt { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px; }
.sb-wf-info-row dd { margin: 0; font-size: 13px; line-height: 1.55; }
.sb-wf-info-ux { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; font-size: 12px; color: #64748b; }
.sb-wf { font-family: 'Inter', sans-serif; font-size: 11px; color: #475569; min-width: 1100px; display: flex; background: #fff; line-height: 1.4; min-height: 100%; height: 100%; }
.sb-wf-zone { border: 1px solid transparent; background: #fff; position: relative; border-radius: 8px; }
.sb-wf-annotate .sb-wf-zone { border: 2px dashed #c7d2fe; background: #fff; }
.sb-wf-zone-label { position: absolute; top: -1px; left: -1px; background: #64748b; color: #fff; font-size: 9px; font-weight: 700; padding: 2px 6px; z-index: 2; border-radius: 4px 0 6px 0; display: none; }
.sb-wf-annotate .sb-wf-zone-label { display: inline-block; }
.sb-wf-zone-label--purple { background: #6366f1; }
.sb-wf-block { background: #f1f5f9; border: none; border-radius: 6px; }
.sb-wf-btn { display: inline-flex; align-items: center; justify-content: center; padding: 6px 14px; border-radius: 8px; background: #fff; border: 1px solid #e2e8f0; font-size: 10px; font-weight: 600; color: #334155; white-space: nowrap; cursor: default; font-family: inherit; }
.sb-wf-btn--dark { background: #6366f1; border-color: #6366f1; color: #fff; }
.sb-wf-btn--outline { background: #fff; border-color: #e2e8f0; color: #475569; }
.sb-wf-btn--block { display: flex; width: 100%; }
.sb-wf-btn--badge { background: #f8fafc; border-color: #e2e8f0; color: #475569; font-weight: 500; }
.sb-wf-text { font-size: 11px; color: #334155; line-height: 1.5; margin: 0; }
.sb-wf-text--h1 { font-size: 16px; font-weight: 800; line-height: 1.35; color: #1e293b; margin: 0 0 8px; }
.sb-wf-text--desc { font-size: 10px; color: #64748b; line-height: 1.6; margin: 0 0 10px; }
.sb-wf-block--icon { width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #64748b; background: #f1f5f9; }
.sb-wf-nav-item { display: flex; align-items: center; gap: 8px; padding: 7px 10px; border-radius: 8px; font-size: 11px; color: #64748b; }
.sb-wf-nav-item.active { background: #eef2ff; border: none; font-weight: 600; color: #4f46e5; }
.sb-wf-nav-item .sb-wf-block--icon { width: 22px; height: 22px; font-size: 10px; }
.sb-wf-pad-sm { padding: 8px; }
.sb-wf-subtitle { font-size: 10px; font-weight: 600; color: #64748b; margin: 0 0 6px; text-transform: uppercase; letter-spacing: .04em; }
.sb-wf-annotate .sb-wf-zone:hover { outline: 2px solid #6366f1; outline-offset: 2px; }
.sb-wf-sidebar { display: flex; flex-shrink: 0; border-right: 1px solid #e2e8f0; }
.sb-wf-icon-rail { width: 48px; background: #f8fafc; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; align-items: center; padding: 12px 0; gap: 4px; }
.sb-wf-icon-rail .sb-wf-block--icon { width: 32px; height: 32px; border-radius: 8px; }
.sb-wf-icon-rail .sb-wf-block--icon.active { background: #eef2ff; color: #4f46e5; }
.sb-wf-nav-panel { width: 180px; display: flex; flex-direction: column; background: #fff; }
.sb-wf-nav-panel-top { padding: 14px 12px 10px; border-bottom: 1px solid #e2e8f0; }
.sb-wf-logo { font-size: 13px; font-weight: 800; color: #334155; }
.sb-wf-logo small { display: block; font-size: 8px; font-weight: 600; color: #94a3b8; letter-spacing: .1em; }
.sb-wf-nav-scroll { flex: 1; padding: 10px 8px; overflow: hidden; }
.sb-wf--hifi { display: block; min-width: 1180px; background: #fff; }
.sb-wf--hifi.sb-wf-annotate .sb-hifi-simple .sb-wf-zone-label { display: inline-block; }
<?php include __DIR__ . '/wf-fullscreen-fill.php'; ?>
