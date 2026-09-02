<?php
/** 좌측 에셋 슬라이드 패널 */
?>
.sb-ed-asset-slide {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 300px;
    z-index: 75;
    background: #fff;
    border-right: 1px solid #e2e8f0;
    box-shadow: 4px 0 28px rgba(15, 23, 42, .1);
    display: flex;
    flex-direction: column;
    transform: translateX(-100%);
    transition: transform .28s cubic-bezier(.4, 0, .2, 1);
    pointer-events: none;
    overflow: hidden;
}
.sb-ed-asset-slide.is-open {
    transform: translateX(0);
    pointer-events: auto;
}
.sb-hifi-editor__workspace.is-asset-slide-open .sb-hifi-editor__canvas-wrap,
.sb-hifi-editor__workspace.is-asset-slide-open .sb-hifi-editor__float-bar {
    margin-left: 300px;
    transition: margin-left .28s cubic-bezier(.4, 0, .2, 1);
}
.sb-ed-asset-slide__view {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    height: 100%;
}
.sb-ed-asset-slide__view[hidden] { display: none !important; }

.sb-ed-asset-slide__head {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 12px 12px 8px;
    flex-shrink: 0;
    border-bottom: 1px solid #f1f5f9;
}
.sb-ed-asset-slide__head--bg {
    flex-wrap: wrap;
}
.sb-ed-asset-slide__head-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-left: auto;
    flex-shrink: 0;
}
.sb-ed-asset-slide__search {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 0 10px;
    height: 36px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
}
.sb-ed-asset-slide__search--full {
    flex: 1;
    width: 100%;
}
.sb-ed-asset-slide__head--bg .sb-ed-asset-slide__search {
    flex: 1 1 100%;
    max-width: calc(100% - 0px);
}
.sb-ed-asset-slide__head--bg .sb-ed-asset-slide__head-actions {
    flex: 1 1 100%;
    justify-content: flex-end;
}
.sb-ed-asset-slide__search-icon {
    color: #94a3b8;
    font-size: 14px;
    flex-shrink: 0;
}
.sb-ed-asset-slide__search-input {
    flex: 1;
    min-width: 0;
    border: none;
    background: transparent;
    font-size: 12px;
    font-family: inherit;
    color: #334155;
    outline: none;
}
.sb-ed-asset-slide__search-input::placeholder { color: #94a3b8; }
.sb-ed-asset-slide__search-clear {
    border: none;
    background: transparent;
    color: #94a3b8;
    font-size: 16px;
    line-height: 1;
    cursor: pointer;
    padding: 0 2px;
}
.sb-ed-asset-slide__action-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 32px;
    padding: 0 10px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    font-size: 11px;
    font-weight: 600;
    font-family: inherit;
    color: #64748b;
    cursor: pointer;
    white-space: nowrap;
}
.sb-ed-asset-slide__action-btn:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #e8505b;
}
.sb-ed-asset-slide__close {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 20px;
    line-height: 1;
    cursor: pointer;
    border-radius: 6px;
    flex-shrink: 0;
}
.sb-ed-asset-slide__close:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.sb-ed-asset-slide__tags {
    display: flex;
    flex-wrap: nowrap;
    gap: 6px;
    padding: 8px 12px;
    overflow-x: auto;
    flex-shrink: 0;
    border-bottom: 1px solid #f8fafc;
    scrollbar-width: none;
}
.sb-ed-asset-slide__tags::-webkit-scrollbar { display: none; }
.sb-ed-asset-slide__tag {
    flex-shrink: 0;
    height: 28px;
    padding: 0 10px;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    background: #fff;
    font-size: 11px;
    font-weight: 600;
    font-family: inherit;
    color: #64748b;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s, border-color .15s, color .15s;
}
.sb-ed-asset-slide__tag em {
    font-style: normal;
    font-weight: 500;
    color: #94a3b8;
    margin-left: 3px;
}
.sb-ed-asset-slide__tag.is-active {
    background: #e8505b;
    border-color: #e8505b;
    color: #fff;
}
.sb-ed-asset-slide__tag.is-active em { color: rgba(255,255,255,.85); }

.sb-ed-asset-slide__sort-row {
    padding: 6px 12px 8px;
    flex-shrink: 0;
}
.sb-ed-asset-slide__sort {
    width: 100%;
    height: 32px;
    padding: 0 10px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    font-size: 12px;
    font-family: inherit;
    font-weight: 600;
    color: #475569;
    text-align: left;
    cursor: pointer;
}
.sb-ed-asset-slide__sort:hover { border-color: #cbd5e1; }

.sb-ed-asset-slide__body {
    flex: 1;
    overflow: auto;
    padding: 8px 12px 16px;
    min-height: 0;
}
.sb-ed-asset-slide__body--icon { padding-top: 12px; }

.sb-ed-asset-slide__grid {
    display: grid;
    gap: 10px;
}
.sb-ed-asset-slide__grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.sb-ed-asset-slide__grid--6 { grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 6px; }

.sb-ed-asset-slide__card {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    padding: 0;
    border: 1px solid #f1f5f9;
    border-radius: 8px;
    background: #fff;
    cursor: pointer;
    font-family: inherit;
    text-align: left;
    overflow: hidden;
    transition: border-color .15s, box-shadow .15s;
}
.sb-ed-asset-slide__card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(15, 23, 42, .08);
}
.sb-ed-asset-slide__thumb {
    display: block;
    aspect-ratio: 1;
    border-radius: 6px 6px 0 0;
    min-height: 56px;
}
.sb-ed-asset-slide__thumb--tpl { min-height: 72px; border-radius: 6px 6px 0 0; }
.sb-ed-asset-slide__card-title {
    padding: 6px 6px 2px;
    font-size: 9px;
    font-weight: 700;
    color: #334155;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.sb-ed-asset-slide__card-tags {
    padding: 0 6px 6px;
    font-size: 8px;
    color: #94a3b8;
    line-height: 1.35;
}
.sb-ed-asset-slide__card--clip {
    padding: 10px 8px 8px;
    align-items: center;
    text-align: center;
}
.sb-ed-asset-slide__clip-art {
    font-size: 36px;
    line-height: 1;
    margin-bottom: 6px;
}
.sb-ed-asset-slide__card--icon {
    align-items: center;
    padding: 8px 4px 6px;
    border: none;
    background: transparent;
    border-radius: 6px;
}
.sb-ed-asset-slide__card--icon:hover {
    background: #f8fafc;
    box-shadow: none;
}
.sb-ed-asset-slide__icon-glyph {
    font-size: 22px;
    line-height: 1;
    color: #1e293b;
    margin-bottom: 4px;
}
.sb-ed-asset-slide__icon-label {
    font-size: 8px;
    color: #64748b;
    text-align: center;
    line-height: 1.2;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sb-ed-asset-slide__rail-btn {
    position: absolute;
    right: -14px;
    top: 50%;
    transform: translateY(-50%);
    width: 14px;
    height: 48px;
    border: 1px solid #e2e8f0;
    border-left: none;
    border-radius: 0 6px 6px 0;
    background: #f8fafc;
    color: #94a3b8;
    font-size: 12px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    box-shadow: 2px 0 8px rgba(15, 23, 42, .06);
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s, background .15s;
}
.sb-ed-asset-slide.is-open .sb-ed-asset-slide__rail-btn {
    opacity: 1;
    pointer-events: auto;
}
.sb-ed-asset-slide__rail-btn:hover {
    background: #fff;
    color: #64748b;
}

/* 툴바 활성 (레퍼런스 빨간 강조) */
.sb-ed-float-tools__item.is-tool-active .sb-ed-float-tools__icon {
    background-color: #e8505b;
}
.sb-ed-float-tools__item.is-tool-active .sb-ed-float-tools__label {
    color: #e8505b;
}
.sb-ed-float-tools__item.is-tool-active {
    background: #fff1f2;
}

.sb-ed-asset-slide__empty {
    margin: 24px 8px;
    padding: 16px 12px;
    border-radius: 8px;
    background: #fff1f2;
    border: 1px solid #fecaca;
    font-size: 12px;
    color: #be123c;
    line-height: 1.5;
    text-align: center;
}

@media (max-width: 900px) {
    .sb-ed-asset-slide { width: 260px; }
    .sb-hifi-editor__workspace.is-asset-slide-open .sb-hifi-editor__canvas-wrap,
    .sb-hifi-editor__workspace.is-asset-slide-open .sb-hifi-editor__float-bar {
        margin-left: 260px;
    }
    .sb-ed-asset-slide__grid--6 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}
