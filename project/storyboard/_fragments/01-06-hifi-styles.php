<?php
/** 마이페이지 대시보드 — hi-fi 와이어프레임 */
?>
.sb-hifi-mypage {
    --mp-primary: #6b46ff;
    --mp-primary-soft: #ede9fe;
    --mp-border: #e2e8f0;
    --mp-text: #1e293b;
    --mp-muted: #64748b;
    --mp-bg: #f1f5f9;
    font-family: 'Inter', 'Pretendard', sans-serif;
    font-size: 13px;
    color: var(--mp-text);
    background: #fff;
    min-width: 1280px;
    min-height: 900px;
    height: 100%;
    display: flex;
    line-height: 1.45;
    box-sizing: border-box;
}
.sb-hifi-mypage *, .sb-hifi-mypage *::before, .sb-hifi-mypage *::after { box-sizing: border-box; }

.sb-hifi-mypage__sidebar {
    width: 200px; flex-shrink: 0; border-right: 1px solid var(--mp-border);
    display: flex; flex-direction: column; background: #fff; min-height: 900px; height: 100%;
}
.sb-hifi-mypage__logo { padding: 16px 14px 12px; border-bottom: 1px solid var(--mp-border); }
.sb-hifi-mypage__logo strong { display: block; font-size: 15px; font-weight: 800; }
.sb-hifi-mypage__logo small { display: block; font-size: 9px; color: var(--mp-muted); letter-spacing: .12em; margin-top: 2px; }
.sb-hifi-mypage__nav { flex: 1; padding: 8px 6px; display: flex; flex-direction: column; }
.sb-hifi-mypage__nav-item {
    display: flex; align-items: center; gap: 8px; padding: 9px 10px; border-radius: 8px;
    font-size: 12px; color: #475569; margin-bottom: 2px;
}
.sb-hifi-mypage__nav-item.is-active { background: var(--mp-primary); color: #fff; font-weight: 600; }
.sb-hifi-mypage__nav-icon { width: 18px; text-align: center; font-size: 13px; }
.sb-hifi-mypage__nav-badge {
    margin-left: auto; font-size: 8px; font-weight: 700; padding: 2px 6px; border-radius: 999px;
    background: var(--mp-primary-soft); color: var(--mp-primary);
}
.sb-hifi-mypage__nav-bottom { margin-top: auto; padding: 8px 6px 0; border-top: 1px solid var(--mp-border); }
.sb-hifi-mypage__nav-sm { font-size: 11px; padding: 7px 10px; color: #94a3b8; display: flex; align-items: center; gap: 8px; border-radius: 8px; }
.sb-hifi-mypage__nav-sm.is-active { background: var(--mp-primary); color: #fff; font-weight: 600; }
.sb-hifi-mypage__points {
    margin: 8px 6px; padding: 12px; border-radius: 12px; background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1px solid #fde68a; display: flex; align-items: center; gap: 8px;
}
.sb-hifi-mypage__points-coin { font-size: 24px; }
.sb-hifi-mypage__points-label { font-size: 10px; color: #92400e; }
.sb-hifi-mypage__points-value { font-size: 15px; font-weight: 800; color: #b45309; }
.sb-hifi-mypage__points-link { font-size: 10px; color: #d97706; }

.sb-hifi-mypage__center { flex: 1; min-width: 0; display: flex; flex-direction: column; background: var(--mp-bg); }
.sb-hifi-mypage__topbar {
    display: flex; align-items: center; gap: 12px; padding: 10px 18px;
    background: #fff; border-bottom: 1px solid var(--mp-border);
}
.sb-hifi-mypage__search {
    flex: 1; max-width: 520px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between;
    padding: 10px 16px; border: 1px solid var(--mp-border); border-radius: 999px; background: #fafbfc;
    color: #94a3b8; font-size: 12px; box-shadow: 0 1px 2px rgba(15,23,42,.04);
}
.sb-hifi-mypage__top-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; }
.sb-hifi-mypage__icon-btn {
    position: relative; width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--mp-border);
    display: flex; align-items: center; justify-content: center; background: #fff; font-size: 16px;
}
.sb-hifi-mypage__badge {
    position: absolute; top: -4px; right: -4px; min-width: 16px; height: 16px; border-radius: 999px;
    background: var(--mp-primary); color: #fff; font-size: 9px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
.sb-hifi-mypage__profile { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; margin-left: 4px; }
.sb-hifi-mypage__avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #a78bfa, #6b46ff); }

.sb-hifi-mypage__content { flex: 1; padding: 16px 18px; overflow: auto; display: flex; flex-direction: column; gap: 14px; }

.sb-hifi-mypage__top-row { display: grid; grid-template-columns: 1fr 340px; gap: 14px; }
.sb-hifi-mypage__card {
    background: #fff; border: 1px solid var(--mp-border); border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.sb-hifi-mypage__profile-card { padding: 18px; }
.sb-hifi-mypage__profile-head { display: flex; gap: 14px; margin-bottom: 14px; }
.sb-hifi-mypage__profile-avatar {
    width: 56px; height: 56px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, #c4b5fd, #6b46ff);
}
.sb-hifi-mypage__profile-info h3 { margin: 0 0 4px; font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
.sb-hifi-mypage__plan-badge {
    font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 999px;
    background: linear-gradient(135deg, #6b46ff, #818cf8); color: #fff;
}
.sb-hifi-mypage__profile-meta { font-size: 11px; color: var(--mp-muted); line-height: 1.5; }
.sb-hifi-mypage__profile-actions { display: flex; gap: 6px; margin-bottom: 14px; flex-wrap: wrap; }
.sb-hifi-mypage__btn {
    padding: 7px 12px; border-radius: 8px; font-size: 11px; font-weight: 600;
    border: 1px solid var(--mp-border); background: #fff; color: #334155;
}
.sb-hifi-mypage__btn--primary { background: var(--mp-primary); border-color: var(--mp-primary); color: #fff; }
.sb-hifi-mypage__plan-box {
    padding: 12px; border-radius: 10px; background: #f8fafc; border: 1px solid #f1f5f9; margin-bottom: 12px;
}
.sb-hifi-mypage__plan-row { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 6px; }
.sb-hifi-mypage__plan-row strong { color: #334155; }
.sb-hifi-mypage__plan-row span { color: var(--mp-muted); }
.sb-hifi-mypage__progress { height: 6px; border-radius: 999px; background: #e2e8f0; overflow: hidden; margin-top: 4px; }
.sb-hifi-mypage__progress-bar { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #6b46ff, #818cf8); width: 34%; }
.sb-hifi-mypage__quick-icons { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 12px; }
.sb-hifi-mypage__quick-icon {
    text-align: center; font-size: 10px; color: #475569; padding: 8px 4px; border-radius: 8px;
    background: #f8fafc; border: 1px solid #f1f5f9;
}
.sb-hifi-mypage__quick-icon em { display: block; font-style: normal; font-size: 18px; margin-bottom: 4px; }

.sb-hifi-mypage__stats { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.sb-hifi-mypage__stat {
    padding: 14px; display: flex; flex-direction: column; gap: 4px;
}
.sb-hifi-mypage__stat-label { font-size: 10px; color: var(--mp-muted); }
.sb-hifi-mypage__stat-value { font-size: 18px; font-weight: 800; color: #334155; }
.sb-hifi-mypage__stat-link { font-size: 10px; color: var(--mp-primary); font-weight: 600; margin-top: auto; }

.sb-hifi-mypage__shortcuts {
    display: flex; gap: 8px; padding: 12px 14px; overflow-x: auto;
}
.sb-hifi-mypage__shortcut {
    flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; gap: 6px;
    padding: 10px 14px; border-radius: 10px; background: #fff; border: 1px solid var(--mp-border);
    font-size: 10px; color: #475569; font-weight: 500; min-width: 72px;
}
.sb-hifi-mypage__shortcut-icon { font-size: 20px; }

.sb-hifi-mypage__section-head {
    display: flex; align-items: center; justify-content: space-between; padding: 14px 14px 0;
}
.sb-hifi-mypage__section-head h3 { margin: 0; font-size: 14px; font-weight: 700; }
.sb-hifi-mypage__section-link { font-size: 11px; color: var(--mp-primary); font-weight: 600; }

.sb-hifi-mypage__design-scroll { display: flex; gap: 10px; padding: 12px 14px 14px; overflow-x: auto; }
.sb-hifi-mypage__design-card {
    flex: 0 0 130px; border: 1px solid var(--mp-border); border-radius: 12px; overflow: hidden; background: #fff;
}
.sb-hifi-mypage__design-thumb {
    height: 80px; display: flex; align-items: center; justify-content: center; font-size: 28px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
}
.sb-hifi-mypage__design-meta { padding: 8px 10px; }
.sb-hifi-mypage__design-title { font-size: 11px; font-weight: 700; margin-bottom: 2px; }
.sb-hifi-mypage__design-date { font-size: 9px; color: #94a3b8; }
.sb-hifi-mypage__tag {
    display: inline-block; font-size: 8px; font-weight: 700; padding: 2px 6px; border-radius: 4px; margin-top: 4px;
}
.sb-hifi-mypage__tag--edit { background: #fef3c7; color: #b45309; }
.sb-hifi-mypage__tag--done { background: #d1fae5; color: #047857; }
.sb-hifi-mypage__design-new {
    flex: 0 0 130px; border: 2px dashed #cbd5e1; border-radius: 12px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: #94a3b8; font-size: 11px; min-height: 130px; background: #fafbfc;
}
.sb-hifi-mypage__design-new span { font-size: 24px; margin-bottom: 4px; }

.sb-hifi-mypage__order-list { padding: 8px 14px 14px; }
.sb-hifi-mypage__order-item {
    display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9;
}
.sb-hifi-mypage__order-item:last-child { border-bottom: none; }
.sb-hifi-mypage__order-thumb {
    width: 44px; height: 44px; border-radius: 8px; background: #f1f5f9;
    display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;
}
.sb-hifi-mypage__order-info { flex: 1; min-width: 0; }
.sb-hifi-mypage__order-name { font-size: 12px; font-weight: 700; margin-bottom: 2px; }
.sb-hifi-mypage__order-spec { font-size: 10px; color: #94a3b8; }
.sb-hifi-mypage__order-date { font-size: 10px; color: #94a3b8; flex-shrink: 0; }
.sb-hifi-mypage__status {
    font-size: 9px; font-weight: 700; padding: 4px 8px; border-radius: 6px; flex-shrink: 0;
}
.sb-hifi-mypage__status--ship { background: #dbeafe; color: #1d4ed8; }
.sb-hifi-mypage__status--done { background: #d1fae5; color: #047857; }

.sb-hifi-mypage__bottom-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }
.sb-hifi-mypage__mini-panel { padding: 14px; }
.sb-hifi-mypage__mini-panel h4 { margin: 0 0 10px; font-size: 12px; font-weight: 700; }
.sb-hifi-mypage__tool-item {
    display: flex; gap: 8px; align-items: flex-start; padding: 7px 0; border-bottom: 1px solid #f1f5f9;
    font-size: 10px; color: #475569;
}
.sb-hifi-mypage__tool-item:last-child { border-bottom: none; }
.sb-hifi-mypage__tool-icon { font-size: 16px; flex-shrink: 0; }
.sb-hifi-mypage__tool-item strong { display: block; font-size: 11px; color: #334155; margin-bottom: 1px; }
.sb-hifi-mypage__tpl-item {
    display: flex; gap: 8px; align-items: center; padding: 6px 0; border-bottom: 1px solid #f1f5f9;
    font-size: 10px;
}
.sb-hifi-mypage__tpl-item:last-child { border-bottom: none; }
.sb-hifi-mypage__tpl-thumb { width: 32px; height: 32px; border-radius: 6px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 14px; }
.sb-hifi-mypage__brand-item {
    display: flex; gap: 8px; align-items: center; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 10px;
}
.sb-hifi-mypage__brand-item:last-child { border-bottom: none; }
.sb-hifi-mypage__brand-logo { width: 28px; height: 28px; border-radius: 50%; background: #ede9fe; display: flex; align-items: center; justify-content: center; font-size: 12px; }
.sb-hifi-mypage__add-btn {
    display: block; width: 100%; margin-top: 8px; padding: 7px; border-radius: 8px;
    border: 1px dashed #cbd5e1; background: #fafbfc; font-size: 10px; color: var(--mp-muted); text-align: center;
}
.sb-hifi-mypage__addr-box {
    padding: 10px; border-radius: 8px; background: #f8fafc; border: 1px solid #f1f5f9;
    font-size: 10px; color: #475569; line-height: 1.5; margin-bottom: 8px;
}
.sb-hifi-mypage__addr-box strong { display: block; font-size: 11px; color: #334155; margin-bottom: 4px; }
.sb-hifi-mypage__settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.sb-hifi-mypage__setting-item {
    text-align: center; padding: 12px 6px; border-radius: 10px; background: #f8fafc; border: 1px solid #f1f5f9;
    font-size: 10px; color: #475569;
}
.sb-hifi-mypage__setting-item em { display: block; font-style: normal; font-size: 20px; margin-bottom: 4px; }

.sb-hifi-mypage__help-banner {
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    padding: 18px 22px; border-radius: 14px;
    background: linear-gradient(135deg, #6b46ff 0%, #818cf8 100%); color: #fff;
    position: relative; overflow: hidden;
}
.sb-hifi-mypage__help-banner p { margin: 0 0 10px; font-size: 13px; font-weight: 500; line-height: 1.45; opacity: .95; }
.sb-hifi-mypage__help-btns { display: flex; gap: 8px; }
.sb-hifi-mypage__help-banner .sb-hifi-mypage__btn { background: #fff; color: var(--mp-primary); border: none; }
.sb-hifi-mypage__help-illus { font-size: 56px; opacity: .3; flex-shrink: 0; }

.sb-hifi-mypage .sb-wf-zone { position: relative; }
.sb-hifi-mypage .sb-wf-zone-label { display: none; z-index: 5; }
.sb-wf-annotate .sb-hifi-mypage .sb-wf-zone-label { display: inline-block; }
.sb-wf-annotate .sb-hifi-mypage .sb-wf-zone { outline: 2px dashed rgba(107,70,255,.35); outline-offset: 1px; }
.sb-wf-annotate .sb-hifi-mypage .sb-wf-zone:hover { outline-color: #6b46ff; }
