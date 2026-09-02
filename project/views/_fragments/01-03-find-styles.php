.sb-wf--find { min-width: 1200px; height: 100%; }
.sb-wf--find .sb-wf-nav-panel { width: 200px; display: flex; flex-direction: column; min-height: 640px; height: 100%; }
.sb-wf--find .sb-wf-nav-scroll { flex: 1; display: flex; flex-direction: column; }
.sb-wf-find-main { flex: 1; display: flex; flex-direction: column; min-width: 0; background: #fff; height: 100%; }
.sb-wf-find-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 24px 28px 16px; border-bottom: 1px solid #e2e8f0; }
.sb-wf-find-header-text { flex: 1; min-width: 0; }
.sb-wf-find-header-text .sb-wf-text--h1 { font-size: 18px; margin-bottom: 6px; }
.sb-wf-find-header-visual { flex: 0 0 120px; min-height: 90px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 10px; color: #94a3b8; padding: 8px; }
.sb-wf-find-header-visual::before { content: '✕ VISUAL'; display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px; }
.sb-wf-find-body { flex: 1; display: flex; gap: 16px; padding: 16px 20px 12px; align-items: flex-start; }
.sb-wf-find-center { flex: 1; min-width: 0; }
.sb-wf-find-side { flex: 0 0 220px; display: flex; flex-direction: column; gap: 12px; }
.sb-wf-find-tabs { display: flex; gap: 0; border-bottom: 2px solid #e2e8f0; margin-bottom: 16px; }
.sb-wf-find-tab { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 12px; font-size: 11px; font-weight: 600; color: #64748b; border-bottom: 2px solid transparent; margin-bottom: -2px; }
.sb-wf-find-tab.active { color: #6366f1; border-bottom-color: #6366f1; background: #fafbff; }
.sb-wf-step-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.sb-wf-step-card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 12px; background: #fafbfc; min-height: 168px; display: flex; flex-direction: column; }
.sb-wf-step-num { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: #6366f1; color: #fff; font-size: 10px; font-weight: 800; margin-bottom: 8px; }
.sb-wf-step-card .sb-wf-block--icon { width: 32px; height: 32px; margin-bottom: 8px; font-size: 14px; }
.sb-wf-step-title { font-size: 11px; font-weight: 700; color: #334155; margin: 0 0 4px; }
.sb-wf-step-desc { font-size: 9px; color: #64748b; line-height: 1.45; margin: 0 0 10px; flex: 1; }
.sb-wf-step-fields { display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px; }
.sb-wf-step-fields .sb-wf-input { font-size: 10px; min-height: 30px; padding: 6px 10px; }
.sb-wf-input { display: flex; align-items: center; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 11px; color: #94a3b8; min-height: 34px; }
.sb-wf-input-row { display: flex; gap: 6px; }
.sb-wf-input-row .sb-wf-input { flex: 1; }
.sb-wf-input--sm { flex: 0 0 52px; justify-content: center; padding: 6px; }
.sb-wf-otp-row { display: flex; gap: 4px; justify-content: center; margin: 8px 0; }
.sb-wf-otp-box { width: 28px; height: 32px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: #334155; }
.sb-wf-step-meta { font-size: 9px; color: #64748b; margin: 4px 0 8px; }
.sb-wf-step-meta--timer { color: #6366f1; font-weight: 700; }
.sb-wf-step-link { font-size: 9px; color: #6366f1; font-weight: 600; }
.sb-wf-step-check { display: flex; align-items: center; gap: 4px; font-size: 9px; color: #15803d; margin: 6px 0 8px; }
.sb-wf-btn--primary { background: #6366f1; border-color: #6366f1; color: #fff; }
.sb-wf-btn--sm-block { display: flex; width: 100%; padding: 8px; border-radius: 8px; font-size: 10px; margin-top: auto; }
.sb-wf-side-card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; background: #fafbfc; }
.sb-wf-side-card .sb-wf-title { font-size: 11px; margin-bottom: 10px; }
.sb-wf-guide-list { list-style: none; margin: 0; padding: 0; }
.sb-wf-guide-list li { display: flex; gap: 8px; align-items: flex-start; padding: 6px 0; font-size: 9px; color: #64748b; border-bottom: 1px dotted #e2e8f0; line-height: 1.4; }
.sb-wf-guide-list li:last-child { border-bottom: none; }
.sb-wf-guide-list .sb-wf-block--icon { width: 20px; height: 20px; font-size: 9px; flex-shrink: 0; }
.sb-wf-how-block { margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0; }
.sb-wf-how-block:last-of-type { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.sb-wf-how-block .sb-wf-subtitle { margin-bottom: 4px; font-size: 9px; }
.sb-wf-how-block p { margin: 0; font-size: 9px; color: #64748b; line-height: 1.45; }
.sb-wf-cs-box { margin-top: 10px; padding: 10px; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 8px; text-align: center; }
.sb-wf-cs-box p { margin: 0 0 8px; font-size: 9px; color: #64748b; }
.sb-wf-find-tip { margin: 0 20px 16px; padding: 10px 14px; background: #fefce8; border: 1px solid #fde68a; border-radius: 8px; font-size: 10px; color: #854d0e; line-height: 1.5; }
.sb-wf-find-tip strong { color: #b45309; }
.sb-wf-field label { display: block; font-size: 9px; font-weight: 600; color: #475569; margin-bottom: 3px; }
.sb-wf-input--pw { justify-content: space-between; }
.sb-wf-nav-bottom-item { margin-top: auto; padding-top: 12px; border-top: 1px solid #e2e8f0; }
