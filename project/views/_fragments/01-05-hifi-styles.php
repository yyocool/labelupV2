<?php
/** 라벨 디자인 편집기 — hi-fi + 인터랙티브 와이어프레임 */
?>
.sb-hifi-editor {
    --ed-primary: #6366f1;
    --ed-primary-soft: #eef2ff;
    --ed-border: #e2e8f0;
    --ed-text: #1e293b;
    --ed-muted: #64748b;
    --ed-bg: #f1f5f9;
    font-family: 'Inter', 'Pretendard', sans-serif;
    font-size: 12px;
    color: var(--ed-text);
    background: #fff;
    min-width: 1280px;
    min-height: 760px;
    height: 100%;
    display: flex;
    flex-direction: column;
    position: relative;
    line-height: 1.4;
    box-sizing: border-box;
}
.sb-hifi-editor *, .sb-hifi-editor *::before, .sb-hifi-editor *::after { box-sizing: border-box; }

.sb-hifi-editor__topbar {
    display: flex; align-items: center; gap: 10px; padding: 8px 14px;
    border-bottom: 1px solid var(--ed-border); background: #fff; flex-shrink: 0;
}
.sb-hifi-editor__file { display: flex; align-items: center; gap: 8px; min-width: 180px; max-width: 240px; }
.sb-hifi-editor__file-icon { width: 28px; height: 28px; border-radius: 8px; background: var(--ed-primary-soft); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sb-hifi-editor__file-name-input {
    display: block;
    width: 100%;
    min-width: 140px;
    max-width: 200px;
    padding: 2px 6px;
    margin: 0 0 2px -6px;
    border: 1px solid transparent;
    border-radius: 6px;
    background: transparent;
    font-family: inherit;
    font-weight: 700;
    font-size: 13px;
    color: #0f172a;
    line-height: 1.35;
}
.sb-hifi-editor__file-name-input:hover {
    border-color: #e2e8f0;
    background: #f8fafc;
}
.sb-hifi-editor__file-name-input:focus {
    outline: none;
    border-color: #c7d2fe;
    background: #fff;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, .12);
}
.sb-hifi-editor__saved { font-size: 10px; color: #22c55e; font-weight: 600; }
.sb-hifi-editor__saved.is-unsaved { color: #f59e0b; }
.sb-hifi-editor__saved.is-saving { color: #6366f1; }
.sb-hifi-editor__center-tools { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; }
.sb-hifi-editor__tool-chip {
    width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--ed-border);
    background: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #475569; cursor: pointer;
}
.sb-hifi-editor__tool-chip.is-active {
    background: var(--ed-primary-soft);
    color: var(--ed-primary);
    border-color: #c7d2fe;
}
.sb-hifi-editor__canvas-wrap.is-grid-on::after {
    content: '';
    position: absolute;
    top: 20px;
    left: 20px;
    right: 0;
    bottom: 24px;
    pointer-events: none;
    z-index: 1;
    background-image:
        linear-gradient(rgba(99,102,241,.18) 1px, transparent 1px),
        linear-gradient(90deg, rgba(99,102,241,.18) 1px, transparent 1px);
    background-size: 20px 20px;
}
.sb-hifi-editor__sheet.is-grid-on {
    background-image:
        linear-gradient(rgba(99,102,241,.12) 1px, transparent 1px),
        linear-gradient(90deg, rgba(99,102,241,.12) 1px, transparent 1px);
    background-size: 16px 16px;
}
.sb-hifi-editor__zoom { display: flex; align-items: center; gap: 4px; padding: 4px 8px; border: 1px solid var(--ed-border); border-radius: 8px; font-size: 11px; margin-left: 8px; }
.sb-hifi-editor__top-actions { display: flex; gap: 8px; margin-left: auto; }
.sb-hifi-editor__btn {
    padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; border: 1px solid var(--ed-border);
    background: #fff; color: #334155; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
}
.sb-hifi-editor__btn--primary { background: var(--ed-primary); border-color: var(--ed-primary); color: #fff; }
.sb-hifi-editor__btn:hover { filter: brightness(.97); }

.sb-hifi-editor__body { flex: 1; display: flex; min-height: 0; position: relative; align-items: stretch; gap: 0; }

.sb-hifi-editor__rail {
    width: 48px; min-width: 48px; max-width: 48px;
    background: #f8fafc; border-right: 1px solid var(--ed-border);
    display: flex; flex-direction: column; align-items: center; padding: 10px 0; gap: 4px; flex-shrink: 0;
    position: relative; z-index: 96;
}
.sb-hifi-editor__rail-btn {
    width: 36px; height: 36px; border-radius: 10px; border: none; background: transparent;
    display: flex; align-items: center; justify-content: center; font-size: 15px; color: #64748b; cursor: pointer;
}
.sb-hifi-editor__rail-btn.is-active { background: var(--ed-primary-soft); color: var(--ed-primary); }
.sb-hifi-editor__rail-spacer { flex: 1; min-height: 8px; }
.sb-hifi-editor__rail-nav {
    display: flex; flex-direction: column; align-items: center; gap: 2px;
    width: 100%; padding: 6px 0 4px; flex-shrink: 0;
    border-top: 1px solid var(--ed-border);
}
.sb-hifi-editor__rail-nav-btn {
    width: 40px; min-height: 38px; padding: 3px 1px; margin: 0;
    border: none; border-radius: 8px; background: transparent; cursor: pointer;
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1px;
    color: #64748b; font-family: inherit; flex-shrink: 0;
}
.sb-hifi-editor__rail-nav-btn:hover { background: #eef2ff; color: #475569; }
.sb-hifi-editor__rail-nav-btn.is-active { background: var(--ed-primary-soft); color: var(--ed-primary); }
.sb-hifi-editor__rail-nav-btn--ai.is-active,
.sb-hifi-editor__rail-nav-btn--ai:hover { background: linear-gradient(145deg, #eef2ff, #f5f3ff); color: #6366f1; }
.sb-hifi-editor__rail-nav-icon { font-size: 13px; line-height: 1; display: block; }
.sb-hifi-editor__rail-nav-label {
    font-size: 7px; font-weight: 700; line-height: 1.15; text-align: center;
    letter-spacing: -0.03em; max-width: 38px; word-break: keep-all;
}
.sb-hifi-editor__rail-nav-label--2 { font-size: 6.5px; line-height: 1.1; }

.sb-hifi-editor__template-panel {
    position: absolute; left: 0; top: 0; bottom: 0; width: 260px; background: #fff;
    border-right: 1px solid var(--ed-border); box-shadow: 4px 0 20px rgba(15,23,42,.08);
    z-index: 20; display: none; flex-direction: column;
    transform: translateX(-8px); opacity: 0; pointer-events: none;
    transition: transform .2s ease, opacity .2s ease;
}
.sb-hifi-editor__template-panel.is-open {
    display: flex; transform: translateX(0); opacity: 1; pointer-events: auto;
}
.sb-hifi-editor__panel-head {
    display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-bottom: 1px solid var(--ed-border);
}
.sb-hifi-editor__panel-head h3 { margin: 0; font-size: 14px; font-weight: 700; }
.sb-hifi-editor__panel-close {
    width: 28px; height: 28px; border: 1px solid var(--ed-border); border-radius: 6px; background: #f8fafc;
    cursor: pointer; font-size: 16px; color: #64748b;
}
.sb-hifi-editor__panel-search {
    margin: 10px 12px; padding: 8px 12px; border: 1px solid var(--ed-border); border-radius: 8px;
    background: #f8fafc; color: #94a3b8; font-size: 11px;
}
.sb-hifi-editor__panel-tabs { display: flex; gap: 4px; padding: 0 12px 10px; flex-wrap: wrap; }
.sb-hifi-editor__panel-tab {
    padding: 4px 10px; border-radius: 999px; font-size: 10px; font-weight: 600; border: 1px solid var(--ed-border); color: #64748b; background: #fff;
}
.sb-hifi-editor__panel-tab.is-active { background: var(--ed-primary); border-color: var(--ed-primary); color: #fff; }
.sb-hifi-editor__panel-scroll { flex: 1; overflow: auto; padding: 0 12px 12px; }
.sb-hifi-editor__panel-sub { font-size: 10px; font-weight: 700; color: #94a3b8; margin: 8px 0 6px; text-transform: uppercase; }
.sb-hifi-editor__thumb-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.sb-hifi-editor__thumb {
    aspect-ratio: 1; border-radius: 8px; border: 1px solid var(--ed-border); background: linear-gradient(135deg, #f8fafc, #eef2ff);
    display: flex; align-items: center; justify-content: center; font-size: 9px; color: #94a3b8; text-align: center; padding: 4px;
}

.sb-hifi-editor__workspace {
    flex: 1 1 0; width: 0; min-width: 0;
    display: flex; flex-direction: column;
    background: var(--ed-bg); position: relative; align-self: stretch;
    margin: 0; padding: 0;
}
.sb-hifi-editor__canvas-wrap {
    flex: 1; display: flex; align-items: center; justify-content: center;
    width: 100%; min-width: 0; align-self: stretch;
    padding: 20px; margin: 0; position: relative; overflow: auto;
}
.sb-hifi-editor__ruler-h {
    position: absolute; top: 0; left: 0; right: 0; height: 20px;
    background: repeating-linear-gradient(90deg, #cbd5e1 0 1px, transparent 1px 20px);
    opacity: .4; border-bottom: 1px solid var(--ed-border);
}
.sb-hifi-editor__ruler-v {
    position: absolute; top: 20px; left: 0; bottom: 0; width: 20px;
    background: repeating-linear-gradient(0deg, #cbd5e1 0 1px, transparent 1px 20px);
    opacity: .4; border-right: 1px solid var(--ed-border);
}
.sb-hifi-editor__artboard {
    width: 220px; min-height: 300px; background: #fff; border-radius: 4px;
    box-shadow: 0 4px 24px rgba(15,23,42,.12); padding: 0; text-align: center; position: relative;
    border: 2px solid var(--ed-primary);
    overflow: hidden;
}
.sb-hifi-editor__artboard-base {
    padding: 16px;
    pointer-events: none;
}
.sb-hifi-editor__canvas-objects {
    position: absolute;
    inset: 0;
    z-index: 2;
}
.sb-ed-canvas-obj {
    position: absolute;
    cursor: move;
    user-select: none;
    touch-action: none;
    box-sizing: border-box;
}
.sb-ed-canvas-obj.is-selected {
    outline: 1px solid var(--ed-primary, #6366f1);
    outline-offset: 0;
    z-index: 3;
}
.sb-ed-canvas-obj__text {
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
    line-height: 1.45;
    padding: 2px 4px;
    white-space: pre-wrap;
    min-width: 24px;
    min-height: 16px;
    font-family: 'Pretendard', 'Noto Sans KR', sans-serif;
}
.sb-ed-canvas-obj--text.is-selected .sb-ed-canvas-obj__text {
    color: #475569;
}
.sb-ed-canvas-obj__text[contenteditable="true"] {
    cursor: text;
    outline: none;
    user-select: text;
}
.sb-ed-canvas-obj--image {
    width: 92px;
    height: 92px;
}
.sb-ed-canvas-obj__image-frame {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.sb-ed-canvas-obj__image-frame svg {
    width: 56px;
    height: 48px;
    display: block;
}
.sb-ed-canvas-obj__img-corner {
    position: absolute;
    width: 10px;
    height: 10px;
    border-color: #0f172a;
    border-style: solid;
    pointer-events: none;
}
.sb-ed-canvas-obj__img-corner--tl { top: 0; left: 0; border-width: 2px 0 0 2px; }
.sb-ed-canvas-obj__img-corner--tr { top: 0; right: 0; border-width: 2px 2px 0 0; }
.sb-ed-canvas-obj__img-corner--bl { bottom: 0; left: 0; border-width: 0 0 2px 2px; }
.sb-ed-canvas-obj__img-corner--br { bottom: 0; right: 0; border-width: 0 2px 2px 0; }
.sb-ed-canvas-obj.is-selected .sb-ed-canvas-obj__img-corner {
    border-color: var(--ed-primary, #6366f1);
}

/* 도형 */
.sb-ed-canvas-obj--shape {
    padding: 2px;
}
.sb-ed-canvas-obj__shape {
    width: 100%;
    height: 100%;
    border: 2px solid #334155;
    border-radius: 4px;
    background: rgba(99, 102, 241, .06);
    box-sizing: border-box;
}
.sb-ed-canvas-obj--shape.is-selected .sb-ed-canvas-obj__shape {
    border-color: var(--ed-primary, #6366f1);
}

/* 표 */
.sb-ed-canvas-obj--table {
    padding: 0;
}
.sb-ed-canvas-obj__table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9px;
    color: #334155;
    background: #fff;
    table-layout: fixed;
}
.sb-ed-canvas-obj__table td {
    border: 1px solid #94a3b8;
    padding: 4px 2px;
    text-align: center;
    vertical-align: middle;
    height: 22px;
}

/* 바코드 */
.sb-ed-canvas-obj--barcode {
    padding: 4px 6px 2px;
    background: #fff;
}
.sb-ed-canvas-obj__barcode {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}
.sb-ed-canvas-obj__barcode-bars {
    width: 100%;
    height: auto;
    display: block;
}
.sb-ed-canvas-obj__barcode-label {
    font-size: 8px;
    font-family: 'Courier New', monospace;
    letter-spacing: .08em;
    color: #334155;
    line-height: 1;
}

.sb-hifi-editor--prototype .sb-ed-canvas-obj { cursor: grab; }
.sb-hifi-editor--prototype .sb-ed-canvas-obj:active,
.sb-ed-canvas-obj.is-dragging { cursor: grabbing; }
.sb-hifi-editor__artboard::before {
    content: ''; position: absolute; inset: -6px; border: 1px dashed rgba(99,102,241,.4); border-radius: 6px; pointer-events: none;
}
.sb-hifi-editor__label-brand { font-size: 8px; letter-spacing: .1em; color: #4a7c59; margin-bottom: 6px; }
.sb-hifi-editor__label-title { font-family: Georgia, serif; font-size: 22px; font-weight: 700; color: #2e4d2b; line-height: 1.1; }
.sb-hifi-editor__label-sub { font-size: 9px; color: #6b8f71; margin: 4px 0 8px; }
.sb-hifi-editor__label-leaf { font-size: 28px; margin: 4px 0; }
.sb-hifi-editor__label-badge {
    display: inline-block; padding: 2px 8px; border-radius: 999px; border: 1px solid #ede6c8;
    font-size: 7px; color: #8b7355; margin-top: 6px;
}
.sb-hifi-editor__float-bar {
    position: absolute; bottom: 16px; left: 50%; transform: translateX(-50%);
    display: flex; gap: 4px; padding: 6px 10px; background: #fff; border: 1px solid var(--ed-border);
    border-radius: 10px; box-shadow: 0 4px 16px rgba(15,23,42,.1);
}
.sb-hifi-editor__float-bar span {
    width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center;
    font-size: 12px; color: #64748b; border: 1px solid transparent;
}

/* .sb-wf-zone { position:relative } 보다 우선해 absolute 플로팅 유지 */
.sb-hifi-editor .sb-hifi-editor__props.sb-wf-zone,
.sb-hifi-editor__props {
    position: absolute !important;
    z-index: 95;
    right: 12px;
    top: 12px;
    left: auto;
    width: 280px;
    max-width: min(280px, calc(100% - 64px));
    max-height: calc(100% - 24px);
    background: #fff;
    border: 1px solid var(--ed-border);
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(15,23,42,.14);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-height: 0;
    flex: none;
    align-self: auto;
}
.sb-hifi-editor__props.is-dragging {
    box-shadow: 0 16px 48px rgba(99,102,241,.22);
    transition: none;
}
.sb-hifi-editor__props.is-snapping {
    transition: left .28s cubic-bezier(.34, 1.2, .64, 1), top .28s cubic-bezier(.34, 1.2, .64, 1);
}
.sb-hifi-editor__props.is-minimized {
    max-height: none;
    width: auto;
    min-width: 200px;
}
.sb-hifi-editor__props-chrome {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
    border-bottom: 1px solid var(--ed-border);
    cursor: grab;
    user-select: none;
    flex-shrink: 0;
}
.sb-hifi-editor__props.is-minimized .sb-hifi-editor__props-chrome {
    border-bottom: none;
}
.sb-hifi-editor__props-chrome:active,
.sb-hifi-editor__props.is-dragging .sb-hifi-editor__props-chrome,
.sb-hifi-editor__preview-panel.is-dragging .sb-hifi-editor__props-chrome {
    cursor: grabbing;
}
.sb-hifi-editor__props.is-dragging .sb-hifi-editor__props-chrome,
.sb-hifi-editor__preview-panel.is-dragging .sb-hifi-editor__props-chrome {
    outline: 2px dashed rgba(99,102,241,.25);
    outline-offset: -2px;
}
.sb-hifi-editor__props.is-magnet-near .sb-hifi-editor__props-chrome,
.sb-hifi-editor__preview-panel.is-magnet-near .sb-hifi-editor__props-chrome {
    outline: 2px dashed rgba(99,102,241,.45);
    outline-offset: -2px;
    background: linear-gradient(180deg, #f5f3ff 0%, #fff 100%);
}
.sb-hifi-editor__props-grip {
    font-size: 12px;
    color: #94a3b8;
    letter-spacing: -2px;
    line-height: 1;
}
.sb-hifi-editor__props-title {
    flex: 1;
    font-size: 12px;
    font-weight: 700;
    color: #334155;
}
.sb-hifi-editor__props-min {
    width: 26px;
    height: 26px;
    border: 1px solid var(--ed-border);
    border-radius: 6px;
    background: #fff;
    color: #64748b;
    font-size: 14px;
    line-height: 1;
    cursor: pointer;
    flex-shrink: 0;
}
.sb-hifi-editor__props-min:hover {
    background: #f1f5f9;
    color: var(--ed-primary);
}
.sb-hifi-editor__props-inner {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}
.sb-hifi-editor__props.is-minimized .sb-hifi-editor__props-inner {
    display: none;
}

/* 미리보기 플로팅 패널 (R-02) */
.sb-hifi-editor .sb-hifi-editor__preview-panel.sb-wf-zone,
.sb-hifi-editor__preview-panel {
    position: absolute !important;
    z-index: 94;
    right: 12px;
    top: 344px;
    left: auto;
    width: 300px;
    max-width: min(300px, calc(100% - 64px));
    max-height: calc(100% - 24px);
    background: #fff;
    border: 1px solid var(--ed-border);
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(15,23,42,.14);
    display: flex;
    flex-direction: column;
    overflow: visible;
    min-height: 0;
    flex: none;
    align-self: auto;
}
.sb-hifi-editor__preview-panel.is-dragging {
    box-shadow: 0 16px 48px rgba(99,102,241,.22);
    transition: none;
}
.sb-hifi-editor__preview-panel.is-snapping {
    transition: left .28s cubic-bezier(.34, 1.2, .64, 1), top .28s cubic-bezier(.34, 1.2, .64, 1);
}
.sb-hifi-editor__preview-panel.is-minimized {
    max-height: none;
    width: auto;
    min-width: 200px;
    overflow: hidden;
}
.sb-hifi-editor__preview-panel-inner {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: visible;
}
.sb-hifi-editor__preview-panel.is-minimized .sb-hifi-editor__preview-panel-inner {
    display: none;
}
.sb-hifi-editor__preview-spec {
    margin: 0;
    padding: 10px 14px 8px;
    font-size: 11px;
    color: #64748b;
    text-align: center;
    line-height: 1.45;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
}
.sb-hifi-editor__preview-spec strong {
    color: #0f172a;
    font-weight: 800;
}
.sb-hifi-editor__preview-spec span {
    color: #94a3b8;
}
.sb-hifi-editor__preview-panel-stage {
    flex: 1;
    min-height: 220px;
    max-height: 300px;
    background: #e8ecf1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px 14px;
    overflow: hidden;
}
.sb-hifi-editor__preview-panel-sheet {
    width: 100%;
    max-width: 168px;
    aspect-ratio: 210/297;
    background: #fff;
    box-shadow: 0 4px 16px rgba(15,23,42,.14);
    padding: 8px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    grid-template-rows: repeat(3, 1fr);
    gap: 5px;
    transform-origin: center center;
}
.sb-hifi-editor__preview-panel-cell {
    border: 1px solid #e2e8f0;
    border-radius: 2px;
    padding: 3px 2px;
    font-size: 5px;
    text-align: center;
    color: #64748b;
    line-height: 1.1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    background: #fefefe;
    overflow: hidden;
    cursor: pointer;
    font-family: inherit;
    transition: border-color .15s, box-shadow .15s;
}
.sb-hifi-editor__preview-panel-cell:hover {
    border-color: #cbd5e1;
}
.sb-hifi-editor__preview-panel-cell.is-selected {
    border: 2px solid #e8505b;
    box-shadow: 0 0 0 1px rgba(232, 80, 91, .25);
    padding: 2px 1px;
}
.sb-preview-cell__hint {
    font-size: 4.5px;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}
.sb-preview-cell__qr {
    width: 14px;
    height: 14px;
    background:
        linear-gradient(90deg, #334155 2px, transparent 2px) 0 0 / 4px 4px,
        linear-gradient(#334155 2px, transparent 2px) 0 0 / 4px 4px,
        #fff;
    border: 1px solid #334155;
    flex-shrink: 0;
}
.sb-preview-cell__brand {
    font-size: 5px;
    font-weight: 700;
    color: #475569;
}

.sb-hifi-editor__preview-pager {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
    padding: 8px 10px;
    border-top: 1px solid var(--ed-border);
    background: #fff;
    flex-shrink: 0;
}
.sb-hifi-editor__preview-pager-btn {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #64748b;
    font-size: 16px;
    line-height: 1;
    cursor: pointer;
    font-family: inherit;
}
.sb-hifi-editor__preview-pager-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.sb-hifi-editor__preview-pager-btn:disabled {
    opacity: .35;
    cursor: not-allowed;
}
.sb-hifi-editor__preview-pager-label {
    flex: 1;
    text-align: center;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    white-space: nowrap;
}

.sb-hifi-editor__preview-actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 4px;
    padding: 6px 10px 10px;
    border-top: 1px solid var(--ed-border);
    background: #fafbfc;
    flex-shrink: 0;
}
.sb-hifi-editor__preview-action-wrap {
    position: relative;
}
.sb-hifi-editor__preview-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    width: 100%;
    min-height: 56px;
    padding: 6px 4px;
    border: none;
    border-radius: 8px;
    background: transparent;
    font-family: inherit;
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: background .15s, color .15s;
}
.sb-hifi-editor__preview-action:hover,
.sb-hifi-editor__preview-action.is-open {
    background: #eef2ff;
    color: var(--ed-primary);
}
.sb-hifi-editor__preview-action-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: #fff;
    border: 1px solid #e2e8f0;
    position: relative;
    flex-shrink: 0;
}
.sb-hifi-editor__preview-action-icon--copy::before,
.sb-hifi-editor__preview-action-icon--copy::after {
    content: '';
    position: absolute;
    border: 1.5px solid #64748b;
    border-radius: 2px;
    background: #fff;
}
.sb-hifi-editor__preview-action-icon--copy::before {
    width: 10px;
    height: 10px;
    top: 5px;
    left: 5px;
}
.sb-hifi-editor__preview-action-icon--copy::after {
    width: 10px;
    height: 10px;
    bottom: 5px;
    right: 5px;
    border-style: dashed;
}
.sb-hifi-editor__preview-action:hover .sb-hifi-editor__preview-action-icon--copy::before,
.sb-hifi-editor__preview-action:hover .sb-hifi-editor__preview-action-icon--copy::after,
.sb-hifi-editor__preview-action.is-open .sb-hifi-editor__preview-action-icon--copy::before,
.sb-hifi-editor__preview-action.is-open .sb-hifi-editor__preview-action-icon--copy::after {
    border-color: var(--ed-primary);
}
.sb-hifi-editor__preview-action-icon--delete::before {
    content: '';
    position: absolute;
    inset: 6px 7px 8px;
    border: 1.5px solid #64748b;
    border-radius: 2px;
    background: #fff;
}
.sb-hifi-editor__preview-action-icon--delete::after {
    content: '−';
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #64748b;
    color: #fff;
    font-size: 11px;
    line-height: 12px;
    text-align: center;
    font-weight: 700;
}
.sb-hifi-editor__preview-action-icon--add::before {
    content: '';
    position: absolute;
    inset: 6px 7px 8px;
    border: 1.5px solid #64748b;
    border-radius: 2px;
    background: #fff;
}
.sb-hifi-editor__preview-action-icon--add::after {
    content: '+';
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #64748b;
    color: #fff;
    font-size: 11px;
    line-height: 12px;
    text-align: center;
    font-weight: 700;
}
.sb-hifi-editor__preview-action:hover .sb-hifi-editor__preview-action-icon--delete::before,
.sb-hifi-editor__preview-action:hover .sb-hifi-editor__preview-action-icon--add::before {
    border-color: var(--ed-primary);
}
.sb-hifi-editor__preview-action:hover .sb-hifi-editor__preview-action-icon--delete::after,
.sb-hifi-editor__preview-action:hover .sb-hifi-editor__preview-action-icon--add::after {
    background: var(--ed-primary);
}

.sb-hifi-editor__preview-copy-menu {
    position: absolute;
    left: 0;
    bottom: calc(100% + 6px);
    z-index: 120;
    min-width: 196px;
    padding: 6px 0;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 12px 36px rgba(15, 23, 42, .16);
}
.sb-hifi-editor__preview-copy-menu[hidden] {
    display: none !important;
}
.sb-hifi-editor__preview-copy-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 9px 14px;
    border: none;
    background: transparent;
    font-family: inherit;
    font-size: 12px;
    font-weight: 500;
    color: #334155;
    text-align: left;
    cursor: pointer;
    white-space: nowrap;
}
.sb-hifi-editor__preview-copy-item:hover {
    background: #f8fafc;
    color: var(--ed-primary);
}
.sb-hifi-editor__preview-copy-sep {
    margin: 4px 0;
    border: none;
    border-top: 1px solid #f1f5f9;
}

.sb-preview-copy-icon {
    width: 22px;
    height: 22px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
    color: #64748b;
}
.sb-preview-copy-icon--master {
    border: 1.5px solid #94a3b8;
    border-radius: 4px;
    font-size: 10px;
}
.sb-preview-copy-icon--grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    grid-template-rows: repeat(2, 1fr);
    gap: 2px;
    padding: 2px;
}
.sb-preview-copy-icon--grid i {
    display: block;
    width: 7px;
    height: 7px;
    border: 1.5px dashed #94a3b8;
    border-radius: 1px;
    font-style: normal;
}
.sb-preview-copy-icon--grid i.is-solid {
    background: #64748b;
    border-color: #64748b;
}

.sb-preview-copy-icon--dup-page {
    position: relative;
}
.sb-preview-copy-icon--dup-page::before,
.sb-preview-copy-icon--dup-page::after {
    content: '';
    position: absolute;
    width: 11px;
    height: 11px;
    border: 1.5px solid #94a3b8;
    border-radius: 2px;
    background: #fff;
}
.sb-preview-copy-icon--dup-page::before { top: 4px; left: 3px; }
.sb-preview-copy-icon--dup-page::after { bottom: 4px; right: 3px; opacity: .7; }

.sb-hifi-editor__preview-foot {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 10px 14px 12px;
    border-top: 1px solid var(--ed-border);
    background: #fff;
    flex-shrink: 0;
}
.sb-hifi-editor__preview-qty {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
}
.sb-hifi-editor__preview-qty input {
    width: 64px;
    height: 32px;
    padding: 0 8px;
    border: 1px solid var(--ed-border);
    border-radius: 8px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    text-align: center;
    color: #0f172a;
    background: #fff;
}
.sb-hifi-editor__preview-qty input:focus {
    outline: none;
    border-color: var(--ed-primary);
    box-shadow: 0 0 0 2px rgba(99, 102, 241, .15);
}
.sb-hifi-editor__preview-toggle-row {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
}
.sb-hifi-editor__preview-toggle-row .sb-hifi-editor__toggle {
    cursor: pointer;
    border: none;
    padding: 0;
}

.sb-hifi-editor__props-tabs { display: flex; border-bottom: 1px solid var(--ed-border); flex-shrink: 0; }
.sb-hifi-editor__props-tab {
    flex: 1; padding: 10px; text-align: center; font-size: 12px; font-weight: 600; color: #94a3b8; border: none; background: #fff; cursor: pointer;
}
.sb-hifi-editor__props-tab.is-active { color: var(--ed-primary); box-shadow: inset 0 -2px 0 var(--ed-primary); }
.sb-hifi-editor__props-body { flex: 1; overflow: auto; padding: 12px 14px; min-height: 120px; }
.sb-hifi-editor__field-group { margin-bottom: 14px; }
.sb-hifi-editor__field-label { font-size: 10px; font-weight: 700; color: #94a3b8; margin-bottom: 6px; text-transform: uppercase; }
.sb-hifi-editor__field-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
.sb-hifi-editor__field {
    padding: 6px 8px; border: 1px solid var(--ed-border); border-radius: 6px; font-size: 11px; background: #f8fafc; color: #334155;
}
.sb-hifi-editor__toggle-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; font-size: 11px; }
.sb-hifi-editor__toggle { width: 36px; height: 20px; border-radius: 999px; background: #e2e8f0; position: relative; }
.sb-hifi-editor__toggle.is-on { background: var(--ed-primary); }
.sb-hifi-editor__toggle::after {
    content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; border-radius: 50%; background: #fff;
}
.sb-hifi-editor__toggle.is-on::after { left: 18px; }

.sb-hifi-editor__preview-overlay {
    position: absolute; inset: 0; z-index: 100; background: rgba(15,23,42,.45);
    display: flex; align-items: stretch; justify-content: center; padding: 16px;
    opacity: 0; pointer-events: none; transition: opacity .2s ease;
}
.sb-hifi-editor__preview-overlay.is-open { opacity: 1; pointer-events: auto; }
.sb-hifi-editor__preview-dialog {
    width: 100%; max-width: 1180px; background: #fff; border-radius: 12px; overflow: hidden;
    display: flex; flex-direction: column; box-shadow: 0 24px 48px rgba(15,23,42,.2);
    transform: scale(.98); transition: transform .2s ease;
}
.sb-hifi-editor__preview-overlay.is-open .sb-hifi-editor__preview-dialog { transform: scale(1); }
.sb-hifi-editor__preview-top {
    display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-bottom: 1px solid var(--ed-border); background: #fff;
}
.sb-hifi-editor__preview-back { font-size: 18px; color: #64748b; cursor: pointer; border: none; background: none; }
.sb-hifi-editor__preview-title { font-weight: 700; font-size: 14px; flex: 1; }
.sb-hifi-editor__preview-mode { display: flex; border: 1px solid var(--ed-border); border-radius: 8px; overflow: hidden; font-size: 11px; }
.sb-hifi-editor__preview-mode span { padding: 6px 14px; color: #64748b; }
.sb-hifi-editor__preview-mode span.is-active { background: var(--ed-primary-soft); color: var(--ed-primary); font-weight: 600; }
.sb-hifi-editor__preview-body { flex: 1; display: flex; min-height: 520px; }
.sb-hifi-editor__paper-panel {
    width: 240px; flex-shrink: 0; border-right: 1px solid var(--ed-border); padding: 14px; overflow: auto; background: #fafbfc;
}
.sb-hifi-editor__paper-field { margin-bottom: 10px; }
.sb-hifi-editor__paper-field label { display: block; font-size: 10px; font-weight: 600; color: #64748b; margin-bottom: 4px; }
.sb-hifi-editor__paper-select {
    width: 100%; padding: 7px 10px; border: 1px solid var(--ed-border); border-radius: 6px; font-size: 11px; background: #fff;
}
.sb-hifi-editor__paper-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.sb-hifi-editor__paper-summary { font-size: 10px; color: #64748b; margin: 12px 0; line-height: 1.5; padding: 8px; background: #fff; border-radius: 6px; border: 1px solid var(--ed-border); }
.sb-hifi-editor__preview-canvas { flex: 1; display: flex; flex-direction: column; background: #e8ecf1; min-width: 0; }
.sb-hifi-editor__page-nav { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 8px; font-size: 11px; color: #64748b; }
.sb-hifi-editor__sheet {
    flex: 1; margin: 12px auto; width: min(100%, 420px); aspect-ratio: 210/297; background: #fff;
    box-shadow: 0 4px 20px rgba(15,23,42,.15); padding: 12px; display: grid;
    grid-template-columns: repeat(3, 1fr); grid-template-rows: repeat(4, 1fr); gap: 6px;
}
.sb-hifi-editor__mini-label {
    border: 1px dashed #cbd5e1; border-radius: 2px; padding: 4px; font-size: 5px; text-align: center;
    color: #4a7c59; line-height: 1.2; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #fefefe;
}
.sb-hifi-editor__mini-label strong { font-size: 6px; color: #2e4d2b; }
.sb-hifi-editor__preview-side { width: 220px; flex-shrink: 0; border-left: 1px solid var(--ed-border); padding: 12px; overflow: auto; background: #fff; }
.sb-hifi-editor__layer-item {
    display: flex; align-items: center; gap: 6px; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 10px; color: #475569;
}
.sb-hifi-editor__help-box {
    margin-top: 12px; padding: 10px; border-radius: 8px; background: var(--ed-primary-soft); font-size: 10px; color: #4338ca; line-height: 1.45;
}

.sb-hifi-editor .sb-wf-zone { position: relative; }
.sb-hifi-editor .sb-hifi-editor__props.sb-wf-zone {
    position: absolute !important;
    flex: 0 0 auto !important;
    width: 280px !important;
    background: #fff !important;
}
.sb-hifi-editor .sb-wf-zone-label { display: none; z-index: 30; }
.sb-wf-annotate .sb-hifi-editor .sb-wf-zone-label { display: inline-block; }
.sb-hifi-editor .sb-wf-zone[data-zone-id="M-02"],
.sb-hifi-editor__workspace.sb-wf-zone {
    border-radius: 0;
    background: var(--ed-bg);
}
.sb-hifi-editor .sb-wf-zone[data-zone-id="L-01"] {
    border-radius: 0;
    background: #f8fafc;
}
.sb-wf-annotate .sb-hifi-editor .sb-wf-zone { outline: 2px dashed rgba(99,102,241,.35); outline-offset: 0; }
.sb-wf-annotate .sb-hifi-editor .sb-wf-zone[data-zone-id="M-02"] { outline-offset: 0; background: var(--ed-bg) !important; }
.sb-wf-annotate .sb-hifi-editor .sb-wf-zone[data-zone-id="L-01"] { background: #f8fafc !important; }
.sb-wf-annotate .sb-hifi-editor .sb-hifi-editor__props.sb-wf-zone {
    outline: 2px dashed rgba(99,102,241,.35);
    outline-offset: 0;
    background: #fff !important;
}
.sb-wf-annotate .sb-hifi-editor .sb-hifi-editor__preview-panel.sb-wf-zone {
    outline: 2px dashed rgba(99,102,241,.35);
    outline-offset: 0;
    background: #fff !important;
}
.sb-wf-annotate .sb-hifi-editor .sb-ed-asset-slide.sb-wf-zone {
    outline: 2px dashed rgba(99,102,241,.35);
    outline-offset: 0;
    background: #fff !important;
}
.sb-wf-annotate .sb-hifi-editor .sb-hifi-editor__layer-overlay:not(.is-open) { display: none !important; }
.sb-wf-annotate .sb-hifi-editor .sb-hifi-editor__layer-overlay.is-open { display: block !important; visibility: visible !important; opacity: 1 !important; pointer-events: auto !important; }
.sb-wf-annotate .sb-hifi-editor .sb-hifi-editor__layer-overlay.is-open .sb-hifi-editor__layer-dialog { background: #fff !important; }
.sb-wf-annotate .sb-hifi-editor .sb-wf-zone:hover { outline-color: #6366f1; }
.sb-wf--hifi.sb-wf--editor {
    display: flex;
    flex-direction: column;
    min-height: 760px;
    height: 100%;
}

/* ── 프로토타입 모드 ── */
.sb-ed-prototype-gate {
    position: absolute; inset: 0; z-index: 300;
    display: flex; align-items: center; justify-content: center;
    pointer-events: auto;
}
.sb-ed-prototype-gate.is-off {
    opacity: 0; visibility: hidden; pointer-events: none;
    transition: opacity .35s ease, visibility .35s ease;
}
.sb-ed-prototype-gate__backdrop {
    position: absolute; inset: 0;
    background: linear-gradient(145deg, rgba(15,23,42,.55), rgba(99,102,241,.35));
    backdrop-filter: blur(2px);
}
.sb-ed-prototype-gate__btn {
    position: relative; z-index: 1;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    padding: 28px 36px; border: 2px solid rgba(255,255,255,.35);
    border-radius: 20px; background: rgba(255,255,255,.96);
    box-shadow: 0 20px 60px rgba(15,23,42,.25);
    cursor: pointer; font-family: inherit; max-width: 320px; text-align: center;
    transition: transform .2s ease, box-shadow .2s ease;
}
.sb-ed-prototype-gate__btn:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 28px 70px rgba(99,102,241,.35);
}
.sb-ed-prototype-gate__icon {
    width: 52px; height: 52px; border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; font-size: 18px; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 24px rgba(99,102,241,.4);
}
.sb-ed-prototype-gate__btn strong { font-size: 16px; font-weight: 800; color: #1e293b; }

.sb-hifi-editor--prototype .sb-wf-zone-label { pointer-events: none !important; opacity: .45; }
.sb-hifi-editor--prototype .sb-hifi-editor__rail-btn,
.sb-hifi-editor--prototype .sb-hifi-editor__tool-chip,
.sb-hifi-editor--prototype .sb-ed-float-tools__item,
.sb-hifi-editor--prototype .sb-ed-float-tools__grip,
.sb-hifi-editor--prototype .sb-ed-float-tools__dock-btn,
.sb-hifi-editor--prototype .sb-hifi-editor__float-bar button,
.sb-hifi-editor--prototype .sb-hifi-editor__zoom button { cursor: pointer; }
.sb-hifi-editor--prototype .sb-hifi-editor__artboard {
    cursor: pointer;
    transform-origin: center center;
    transition: transform .15s ease, box-shadow .15s;
}
.sb-hifi-editor--prototype .sb-hifi-editor__artboard:hover { box-shadow: 0 0 0 2px rgba(99,102,241,.35); }

.sb-hifi-editor__zoom button {
    border: none; background: transparent; cursor: pointer; font-size: 11px; color: #475569; padding: 0 2px;
}
.sb-hifi-editor__float-bar button {
    border: none; background: transparent; cursor: pointer; font-size: 13px; color: #475569;
    width: 28px; height: 28px; border-radius: 6px;
}
.sb-hifi-editor__float-bar button:hover { background: rgba(99,102,241,.1); }
.sb-hifi-editor__preview-mode button {
    border: none; background: transparent; padding: 4px 10px; border-radius: 6px;
    font-size: 11px; font-weight: 600; color: #64748b; cursor: pointer; font-family: inherit;
}
.sb-hifi-editor__preview-mode button.is-active { background: var(--ed-primary); color: #fff; }
.sb-hifi-editor__paper-select {
    width: 100%; text-align: left; border: 1px solid var(--ed-border); border-radius: 8px;
    padding: 8px 10px; background: #fff; font-size: 11px; cursor: pointer; font-family: inherit;
}
.sb-hifi-editor__paper-select:hover { border-color: var(--ed-primary); }
.sb-hifi-editor__page-nav button {
    border: none; background: transparent; cursor: pointer; font-size: 12px; color: #475569; padding: 2px 6px;
}
.sb-hifi-editor__page-nav--tools { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; justify-content: center; }
.sb-hifi-editor__layer-item.is-selected { background: var(--ed-primary-soft); border-radius: 6px; }

.sb-ed-proto-toast-wrap {
    position: absolute; bottom: 72px; left: 50%; transform: translateX(-50%);
    z-index: 500; display: flex; flex-direction: column; gap: 6px; pointer-events: none;
}
.sb-ed-proto-toast {
    padding: 10px 16px; border-radius: 10px;
    background: rgba(15,23,42,.88); color: #fff; font-size: 12px; font-weight: 500;
    box-shadow: 0 8px 24px rgba(15,23,42,.25);
    opacity: 0; transform: translateY(8px); transition: opacity .25s, transform .25s;
    white-space: nowrap; max-width: 90vw; overflow: hidden; text-overflow: ellipsis;
}
.sb-ed-proto-toast.is-show { opacity: 1; transform: translateY(0); }

.sb-ed-proto-modal {
    position: absolute; inset: 0; z-index: 400;
    display: flex; align-items: center; justify-content: center;
    background: rgba(15,23,42,.45);
}
.sb-ed-proto-modal[hidden] { display: none !important; }
.sb-ed-proto-modal.is-open { display: flex !important; }
.sb-ed-proto-modal__dialog {
    width: min(400px, 92%); background: #fff; border-radius: 14px;
    padding: 20px 22px; box-shadow: 0 24px 60px rgba(15,23,42,.2); position: relative;
}
.sb-ed-proto-modal__close {
    position: absolute; top: 10px; right: 10px; width: 28px; height: 28px;
    border: 1px solid var(--ed-border); border-radius: 8px; background: #f8fafc; cursor: pointer;
}
.sb-ed-proto-modal__title { margin: 0 0 10px; font-size: 16px; font-weight: 700; }
.sb-ed-proto-modal__body { font-size: 13px; color: #475569; line-height: 1.6; margin-bottom: 14px; }
.sb-ed-proto-modal__body ul { margin: 8px 0 0; padding-left: 1.2em; }
.sb-ed-proto-chip-row { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
.sb-ed-proto-chip-row span {
    padding: 6px 10px; border-radius: 8px; background: var(--ed-primary-soft);
    font-size: 11px; font-weight: 600; color: #4338ca;
}
.sb-ed-proto-modal__actions { display: flex; justify-content: flex-end; }

.sb-front-btn--proto {
    background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
    border-color: transparent !important; color: #fff !important;
}
