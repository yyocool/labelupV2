<?php
/** HOME(01) 전용 와이어프레임 레이아웃 — hi-fi 모던 스타일 */
?>
.sb-wf-block--text { height: 10px; border-radius: 4px; background: #e2e8f0; }
.sb-wf-block--text-lg { height: 14px; }
.sb-wf-block--text-xl { height: 20px; }
.sb-wf-block--btn { height: 28px; border-radius: 8px; background: #f1f5f9; border: 1px solid #e2e8f0; }
.sb-wf-block--btn-dark { background: #6366f1; border-color: #6366f1; }
.sb-wf-block--img { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 10px; font-weight: 600; min-height: 60px; }
.sb-wf-block--img::before { content: '✕ IMAGE'; }
.sb-wf-block--avatar { width: 24px; height: 24px; border-radius: 50%; background: #cbd5e1; border: 2px solid #fff; margin-left: -6px; }
.sb-wf-block--avatar:first-child { margin-left: 0; }
.sb-wf-badge { display: inline-block; padding: 1px 5px; background: #fef3c7; border: 1px solid #fde68a; border-radius: 999px; font-size: 8px; font-weight: 700; color: #b45309; margin-left: auto; }
.sb-wf-badge--dot { width: 14px; height: 14px; background: #fca5a5; border: 1px solid #f87171; border-radius: 50%; font-size: 8px; color: #fff; display: flex; align-items: center; justify-content: center; padding: 0; margin-left: 0; }
.sb-wf-row { display: flex; gap: 8px; align-items: center; }
.sb-wf-col { display: flex; flex-direction: column; gap: 6px; }
.sb-wf-gap-sm { gap: 4px; }
.sb-wf-gap-md { gap: 10px; }
.sb-wf-gap-lg { gap: 16px; }
.sb-wf-pad { padding: 12px; }
.sb-wf-title { font-size: 12px; font-weight: 700; color: #334155; margin: 0 0 8px; }
.sb-wf-caption { font-size: 10px; color: #64748b; margin: 4px 0 0; }
.sb-wf-progress { height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; margin: 6px 0; }
.sb-wf-progress-bar { height: 100%; width: 32%; background: #6366f1; border-radius: 3px; }
.sb-wf-filter { padding: 4px 10px; border: 1px solid #e2e8f0; border-radius: 999px; font-size: 10px; color: #64748b; white-space: nowrap; background: #fff; }
.sb-wf-filter.active { background: #6366f1; color: #fff; border-color: #6366f1; }
.sb-wf-template-card { flex: 0 0 120px; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #fff; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
.sb-wf-template-card .sb-wf-block--img { min-height: 90px; border: none; border-bottom: 1px solid #e2e8f0; border-radius: 0; }
.sb-wf-template-card .sb-wf-block--img::before { content: '✕ THUMB'; font-size: 9px; }
.sb-wf-template-meta { padding: 6px 8px; }
.sb-wf-nav-section { margin-bottom: 10px; padding-top: 14px; }
.sb-wf-nav-bottom { padding: 10px 8px; border-top: 1px solid #e2e8f0; }
.sb-wf-status-card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; margin-bottom: 8px; background: #f8fafc; }
.sb-wf-status-card--premium { background: #eef2ff; border-color: #c7d2fe; }
.sb-wf-main { flex: 1; display: flex; flex-direction: column; min-width: 0; background: #f8fafc; height: 100%; }
.sb-wf-header { display: flex; align-items: center; gap: 12px; padding: 12px 20px; border-bottom: 1px solid #e2e8f0; background: #fff; }
.sb-wf-search { flex: 1; max-width: 420px; margin: 0 auto; display: flex; align-items: center; gap: 8px; padding: 8px 14px; border: 1px solid #e2e8f0; border-radius: 999px; background: #f8fafc; font-size: 11px; color: #94a3b8; }
.sb-wf-header-actions { display: flex; align-items: center; gap: 10px; margin-left: auto; }
.sb-wf-body { flex: 1; min-height: 0; padding: 16px 20px 20px; display: flex; flex-direction: column; gap: 16px; overflow: auto; }
.sb-wf-hero-row { display: grid; grid-template-columns: 1fr 1.2fr 160px; gap: 14px; align-items: start; }
.sb-wf-hero-text .sb-wf-block--text-xl { width: 90%; margin-bottom: 6px; }
.sb-wf-hero-text .sb-wf-block--text { width: 100%; margin-bottom: 4px; }
.sb-wf-hero-text .sb-wf-block--text:nth-child(3) { width: 80%; }
.sb-wf-visual { min-height: 220px; display: flex; flex-direction: column; }
.sb-wf-visual-img { flex: 1; min-height: 200px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; color: #94a3b8; font-size: 11px; font-weight: 600; text-align: center; padding: 16px; }
.sb-wf-visual-img::before { content: '✕ VISUAL'; font-size: 13px; font-weight: 700; color: #64748b; }
.sb-wf-right-col { display: flex; flex-direction: column; gap: 10px; }
.sb-wf-quick-list { list-style: none; margin: 0; padding: 0; }
.sb-wf-quick-list li { display: flex; align-items: center; gap: 6px; padding: 5px 0; font-size: 10px; color: #64748b; border-bottom: 1px solid #f1f5f9; }
.sb-wf-quick-list li:last-child { border-bottom: none; }
.sb-wf-ai-promo { min-height: 140px; background: linear-gradient(135deg, #eef2ff, #f5f3ff); border-radius: 10px; border: 1px solid #e0e7ff; }
.sb-wf-features { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; }
.sb-wf-feature-item { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; background: #fff; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
.sb-wf-feature-item .sb-wf-block--icon { margin-bottom: 6px; }
.sb-wf-gallery-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.sb-wf-filters { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px; }
.sb-wf-template-scroll { display: flex; gap: 10px; overflow: hidden; align-items: stretch; }
.sb-wf-scroll-arrow { flex: 0 0 28px; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 14px; color: #94a3b8; }
