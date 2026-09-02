<?php
/** 플로팅 편집 도구바 */
?>
.sb-ed-float-tools {
    position: absolute;
    z-index: 80;
    left: 12px;
    top: 12px;
    width: max-content;
    max-width: calc(100% - 24px);
    max-height: calc(100% - 24px);
    pointer-events: none;
}
.sb-hifi-editor .sb-ed-float-tools.sb-wf-zone {
    position: absolute !important;
}
.sb-ed-float-tools__bar {
    display: inline-flex;
    align-items: stretch;
    width: max-content;
    max-width: 100%;
    gap: 0;
    padding: 6px 6px 6px 4px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(15, 23, 42, .12), 0 2px 8px rgba(15, 23, 42, .06);
    pointer-events: auto;
    user-select: none;
    transition: box-shadow .2s ease;
}
.sb-ed-float-tools.is-dragging .sb-ed-float-tools__bar {
    box-shadow: 0 16px 48px rgba(99, 102, 241, .22);
    cursor: grabbing;
}
.sb-ed-float-tools.is-dragging.is-magnet-near .sb-ed-float-tools__bar {
    box-shadow: 0 12px 40px rgba(99, 102, 241, .18);
    outline: 2px dashed rgba(99, 102, 241, .35);
    outline-offset: 2px;
}
.sb-ed-float-tools.is-snapping .sb-ed-float-tools__bar {
    transition: transform .28s cubic-bezier(.34, 1.3, .64, 1);
}
.sb-ed-float-tools__grip {
    flex-shrink: 0;
    width: 20px;
    border: none;
    background: transparent;
    color: #cbd5e1;
    font-size: 12px;
    letter-spacing: -2px;
    cursor: grab;
    border-radius: 6px;
    padding: 0;
    margin: 2px 2px 2px 0;
    align-self: stretch;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color .15s, background .15s;
}
.sb-ed-float-tools__grip:hover {
    color: var(--ed-primary, #6366f1);
    background: var(--ed-primary-soft, #eef2ff);
}
.sb-ed-float-tools.is-dragging .sb-ed-float-tools__grip {
    cursor: grabbing;
    color: var(--ed-primary, #6366f1);
}
.sb-ed-float-tools__group {
    display: inline-flex;
    align-items: stretch;
    gap: 2px;
    flex: 0 0 auto;
}
.sb-ed-float-tools__divider {
    width: 1px;
    align-self: stretch;
    margin: 6px 6px;
    background: #e2e8f0;
    flex-shrink: 0;
}
.sb-ed-float-tools__item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    flex: 0 0 auto;
    min-width: 0;
    padding: 6px 7px 5px;
    border: none;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    font-family: inherit;
    transition: background .15s, color .15s;
}
.sb-ed-float-tools__item:hover {
    background: var(--ed-primary-soft, #eef2ff);
}
.sb-ed-float-tools__item.is-active {
    background: var(--ed-primary-soft, #eef2ff);
    color: var(--ed-primary, #6366f1);
}
.sb-ed-float-tools__icon {
    width: 22px;
    height: 22px;
    background-color: #475569;
    mask-size: contain;
    mask-repeat: no-repeat;
    mask-position: center;
    -webkit-mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    -webkit-mask-position: center;
    transition: background-color .15s;
}
.sb-ed-float-tools__item:hover .sb-ed-float-tools__icon,
.sb-ed-float-tools__item.is-active .sb-ed-float-tools__icon {
    background-color: var(--ed-primary, #6366f1);
}
.sb-ed-float-tools__label {
    font-size: 9px;
    font-weight: 600;
    color: #64748b;
    line-height: 1.1;
    white-space: nowrap;
    letter-spacing: -.02em;
}
.sb-ed-float-tools__item:hover .sb-ed-float-tools__label,
.sb-ed-float-tools__item.is-active .sb-ed-float-tools__label {
    color: var(--ed-primary, #6366f1);
}

/* SVG mask icons */
.sb-ed-float-tools__icon--text {
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Crect x='4' y='4' width='16' height='16' rx='1'/%3E%3Cpath d='M8 8h8M12 8v8' stroke-linecap='round'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Crect x='4' y='4' width='16' height='16' rx='1'/%3E%3Cpath d='M8 8h8M12 8v8' stroke-linecap='round'/%3E%3C/svg%3E");
}
.sb-ed-float-tools__icon--image {
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Crect x='3' y='5' width='18' height='14' rx='1'/%3E%3Ccircle cx='8.5' cy='10' r='1.5' fill='black' stroke='none'/%3E%3Cpath d='M3 16l5-5 4 4 3-3 6 6'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Crect x='3' y='5' width='18' height='14' rx='1'/%3E%3Ccircle cx='8.5' cy='10' r='1.5' fill='black' stroke='none'/%3E%3Cpath d='M3 16l5-5 4 4 3-3 6 6'/%3E%3C/svg%3E");
}
.sb-ed-float-tools__icon--background {
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Crect x='4' y='6' width='14' height='12' rx='1'/%3E%3Cpath d='M16 8l4-2v12l-4-2'/%3E%3Cpath d='M8 10h6M8 14h4'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Crect x='4' y='6' width='14' height='12' rx='1'/%3E%3Cpath d='M16 8l4-2v12l-4-2'/%3E%3Cpath d='M8 10h6M8 14h4'/%3E%3C/svg%3E");
}
.sb-ed-float-tools__icon--template {
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Crect x='4' y='4' width='7' height='7'/%3E%3Crect x='13' y='4' width='7' height='7'/%3E%3Crect x='4' y='13' width='7' height='7'/%3E%3Crect x='13' y='13' width='7' height='7'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Crect x='4' y='4' width='7' height='7'/%3E%3Crect x='13' y='4' width='7' height='7'/%3E%3Crect x='4' y='13' width='7' height='7'/%3E%3Crect x='13' y='13' width='7' height='7'/%3E%3C/svg%3E");
}
.sb-ed-float-tools__icon--clipart {
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Ccircle cx='12' cy='12' r='3'/%3E%3Cpath d='M12 3c-2 3-6 4-6 9a6 6 0 0012 0c0-5-4-6-6-9z'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Ccircle cx='12' cy='12' r='3'/%3E%3Cpath d='M12 3c-2 3-6 4-6 9a6 6 0 0012 0c0-5-4-6-6-9z'/%3E%3C/svg%3E");
}
.sb-ed-float-tools__icon--icon {
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3Cpath d='M8 10s1.5 2 4 2 4-2 4-2'/%3E%3Ccircle cx='9' cy='9' r='1' fill='black' stroke='none'/%3E%3Ccircle cx='15' cy='9' r='1' fill='black' stroke='none'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3Cpath d='M8 10s1.5 2 4 2 4-2 4-2'/%3E%3Ccircle cx='9' cy='9' r='1' fill='black' stroke='none'/%3E%3Ccircle cx='15' cy='9' r='1' fill='black' stroke='none'/%3E%3C/svg%3E");
}
.sb-ed-float-tools__icon--shape {
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Ccircle cx='8' cy='10' r='4'/%3E%3Cpath d='M14 18L20 6l-6 4-4 8z'/%3E%3Crect x='13' y='13' width='6' height='6'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Ccircle cx='8' cy='10' r='4'/%3E%3Cpath d='M14 18L20 6l-6 4-4 8z'/%3E%3Crect x='13' y='13' width='6' height='6'/%3E%3C/svg%3E");
}
.sb-ed-float-tools__icon--table {
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Crect x='4' y='4' width='16' height='16' rx='1'/%3E%3Cpath d='M4 10h16M4 16h16M10 4v16M16 4v16'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Crect x='4' y='4' width='16' height='16' rx='1'/%3E%3Cpath d='M4 10h16M4 16h16M10 4v16M16 4v16'/%3E%3C/svg%3E");
}
.sb-ed-float-tools__icon--barcode {
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Cpath d='M4 7V5M8 7V5M12 7V5M16 7V5M20 7V5M4 19v-2M20 19v-2'/%3E%3Cpath d='M6 8h2v8H6zM10 8h1v8h-1zM13 8h3v8h-3zM18 8h1v8h-1z' fill='black' stroke='none'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Cpath d='M4 7V5M8 7V5M12 7V5M16 7V5M20 7V5M4 19v-2M20 19v-2'/%3E%3Cpath d='M6 8h2v8H6zM10 8h1v8h-1zM13 8h3v8h-3zM18 8h1v8h-1z' fill='black' stroke='none'/%3E%3C/svg%3E");
}
.sb-ed-float-tools__icon--master {
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6' stroke-dasharray='3 2'%3E%3Crect x='5' y='5' width='14' height='14' rx='1'/%3E%3Cpath d='M9 9h6v6H9z'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6' stroke-dasharray='3 2'%3E%3Crect x='5' y='5' width='14' height='14' rx='1'/%3E%3Cpath d='M9 9h6v6H9z'/%3E%3C/svg%3E");
}
.sb-ed-float-tools__icon--data {
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Cpath d='M6 5h12v14H6z'/%3E%3Cpath d='M9 9h6M9 12h6M9 15h4'/%3E%3Cpath d='M18 8l2-1v10l-2-1'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Cpath d='M6 5h12v14H6z'/%3E%3Cpath d='M9 9h6M9 12h6M9 15h4'/%3E%3Cpath d='M18 8l2-1v10l-2-1'/%3E%3C/svg%3E");
}
.sb-ed-float-tools__icon--layer {
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Cpath d='M12 4L4 8l8 4 8-4-8-4z'/%3E%3Cpath d='M4 12l8 4 8-4'/%3E%3Cpath d='M4 16l8 4 8-4'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6'%3E%3Cpath d='M12 4L4 8l8 4 8-4-8-4z'/%3E%3Cpath d='M4 12l8 4 8-4'/%3E%3Cpath d='M4 16l8 4 8-4'/%3E%3C/svg%3E");
}

.sb-ed-float-tools__dock-wrap {
    position: relative;
    display: flex;
    align-items: center;
    flex: 0 0 auto;
    margin-left: 0;
}
.sb-ed-float-tools__dock-btn {
    width: 28px;
    height: 28px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
    color: #64748b;
    font-size: 14px;
    cursor: pointer;
    flex-shrink: 0;
    align-self: center;
    transition: border-color .15s, color .15s, background .15s;
}
.sb-ed-float-tools__dock-btn:hover,
.sb-ed-float-tools__dock-btn.is-open {
    border-color: #c7d2fe;
    color: var(--ed-primary, #6366f1);
    background: var(--ed-primary-soft, #eef2ff);
}
.sb-ed-float-tools__dock-menu {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    min-width: 148px;
    padding: 8px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .14);
    z-index: 5;
}
.sb-ed-float-tools__dock-menu[hidden] { display: none !important; }
.sb-ed-float-tools__dock-label {
    margin: 0 0 6px;
    padding: 0 4px;
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
    letter-spacing: .02em;
}
.sb-ed-float-tools__dock-label:not(:first-child) {
    margin-top: 8px;
}
.sb-ed-float-tools__dock-grid {
    display: grid;
    gap: 4px;
}
.sb-ed-float-tools__dock-grid--corner {
    grid-template-columns: 1fr 1fr;
}
.sb-ed-float-tools__dock-grid--orient {
    grid-template-columns: 1fr 1fr;
}
.sb-ed-float-tools__dock-grid button {
    min-width: 0;
    height: 32px;
    padding: 0 8px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    font-size: 12px;
    font-family: inherit;
    font-weight: 600;
    cursor: pointer;
    color: #64748b;
    transition: background .15s, border-color .15s, color .15s;
}
.sb-ed-float-tools__dock-grid button:hover,
.sb-ed-float-tools__dock-grid button.is-active {
    background: var(--ed-primary-soft, #eef2ff);
    border-color: #c7d2fe;
    color: var(--ed-primary, #6366f1);
}

/* 세로 배치 */
.sb-ed-float-tools[data-sb-float-orient="vertical"] .sb-ed-float-tools__bar {
    flex-direction: column;
    align-items: stretch;
    padding: 4px 6px 6px;
}
.sb-ed-float-tools[data-sb-float-orient="vertical"] .sb-ed-float-tools__grip {
    width: auto;
    height: 18px;
    margin: 0 2px 2px;
    letter-spacing: 1px;
}
.sb-ed-float-tools[data-sb-float-orient="vertical"] .sb-ed-float-tools__group {
    flex-direction: column;
}
.sb-ed-float-tools[data-sb-float-orient="vertical"] .sb-ed-float-tools__divider {
    width: auto;
    height: 1px;
    margin: 4px 8px;
    align-self: stretch;
}
.sb-ed-float-tools[data-sb-float-orient="vertical"] .sb-ed-float-tools__item {
    flex-direction: row;
    justify-content: flex-start;
    gap: 8px;
    padding: 7px 10px;
    min-width: 112px;
}
.sb-ed-float-tools[data-sb-float-orient="vertical"] .sb-ed-float-tools__label {
    font-size: 11px;
}
.sb-ed-float-tools[data-sb-float-orient="vertical"] .sb-ed-float-tools__dock-wrap {
    align-self: stretch;
    justify-content: center;
    padding-top: 2px;
}

/* 모서리 위치 — JS inline 좌표 사용, 초기값만 CSS */
.sb-ed-float-tools[data-sb-float-corner="tl"] { left: 12px; top: 12px; }

@media (max-width: 1100px) {
    .sb-ed-float-tools__item { padding: 6px 5px 5px; }
    .sb-ed-float-tools__label { font-size: 8px; }
}
