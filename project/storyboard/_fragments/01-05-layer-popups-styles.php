<?php
/** L-01 레이어 팝업 스타일 */
?>
.sb-hifi-editor {
    --ed-red: #e8505b;
    --ed-red-soft: #fff1f2;
}
.sb-hifi-editor__layer-overlay {
    position: absolute; left: 0; right: 0; bottom: 0; top: 49px;
    z-index: 200; background: rgba(15,23,42,.35);
    display: none; opacity: 0; visibility: hidden; pointer-events: none;
    transition: opacity .2s ease, visibility .2s ease;
}
.sb-hifi-editor__layer-overlay.is-open {
    display: block; opacity: 1; visibility: visible; pointer-events: auto;
}
.sb-hifi-editor__layer-overlay:not(.is-open) .sb-hifi-editor__layer-dialog,
.sb-hifi-editor__layer-overlay:not(.is-open) .sb-ed-layer {
    pointer-events: none !important;
}
.sb-hifi-editor__layer-dialog {
    position: absolute; left: 0; right: 0; top: 0; bottom: 0;
    background: #fff; display: flex; flex-direction: column; overflow: hidden;
    box-shadow: -6px 0 32px rgba(15,23,42,.12);
    transform: translateY(8px); transition: transform .22s ease;
}
.sb-hifi-editor__layer-overlay.is-open .sb-hifi-editor__layer-dialog { transform: translateY(0); }
.sb-hifi-editor__layer-close {
    position: absolute; top: 10px; right: 12px; z-index: 5;
    width: 32px; height: 32px; border: 1px solid var(--ed-border); border-radius: 8px;
    background: #fff; color: #64748b; font-size: 18px; cursor: pointer; line-height: 1;
}
.sb-ed-layer { display: flex; flex-direction: column; min-height: 0; flex: 1; height: 100%; }
.sb-ed-layer[hidden] { display: none !important; }

/* 카탈로그 공통 (라벨·태그) */
.sb-ed-layer__tabs {
    display: flex; gap: 0; border-bottom: 1px solid #f1f5f9; flex-shrink: 0; padding-right: 20px;
}
.sb-ed-layer__tab {
    flex: 1; padding: 14px 8px; border: none; background: #fff; cursor: pointer;
    font-size: 13px; font-weight: 600; color: #64748b; font-family: inherit;
    border-bottom: 3px solid transparent; transition: color .15s, background .15s;
}
.sb-ed-layer__tab em { font-style: normal; font-size: 11px; color: #94a3b8; margin-left: 4px; }
.sb-ed-layer__tab.is-active { color: #fff; background: var(--ed-red); border-bottom-color: var(--ed-red); }
.sb-ed-layer__tab.is-active em { color: rgba(255,255,255,.85); }
.sb-ed-layer__toolbar {
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    padding: 12px 20px 12px 20px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0; flex-wrap: wrap;
}
.sb-ed-layer__subtabs { display: flex; gap: 20px; }
.sb-ed-layer__subtab {
    border: none; background: none; padding: 0 0 6px; font-size: 13px; font-weight: 600;
    color: #94a3b8; cursor: pointer; font-family: inherit; border-bottom: 2px solid transparent;
}
.sb-ed-layer__subtab.is-active { color: #1e293b; border-bottom-color: var(--ed-red); }
.sb-ed-layer__tools { display: flex; align-items: center; gap: 10px; margin-left: auto; flex-wrap: wrap; }
.sb-ed-layer__count { font-size: 12px; color: #64748b; white-space: nowrap; }
.sb-ed-layer__search {
    min-width: 200px; padding: 8px 12px; border: 1px solid var(--ed-border); border-radius: 6px;
    background: #fff; color: #94a3b8; font-size: 12px;
}
.sb-ed-layer__search--wide { width: 100%; max-width: none; min-width: 0; margin: 16px 20px 0 20px; }
.sb-ed-layer__select {
    padding: 8px 12px; border: 1px solid var(--ed-border); border-radius: 6px;
    font-size: 12px; color: #475569; background: #fff; white-space: nowrap;
}
.sb-ed-layer__scroll { flex: 1; overflow: auto; padding: 16px 20px 24px; min-height: 0; }
.sb-ed-layer__grid { display: grid; gap: 14px; }
.sb-ed-layer__grid--6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }

.sb-ed-spec-card {
    border: 1px solid #f1f5f9; border-radius: 4px; background: #fff; padding: 0;
    cursor: pointer; text-align: left; font-family: inherit; transition: box-shadow .15s, border-color .15s;
}
.sb-ed-spec-card:hover { border-color: #fecdd3; box-shadow: 0 4px 16px rgba(232,80,91,.12); }
.sb-ed-spec-card__thumb {
    position: relative; aspect-ratio: 1.05; background: #fafafa; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.sb-ed-spec-card__thumb::before {
    content: ''; position: absolute; inset: 12%; border: 1px dashed #cbd5e1; border-radius: 2px;
    background-image: radial-gradient(circle, #e2e8f0 1px, transparent 1px);
    background-size: 10px 10px;
}
.sb-ed-spec-card__thumb--heart::after { content: '♥'; font-size: 28px; color: #cbd5e1; position: relative; z-index: 1; }
.sb-ed-spec-card__thumb--circle::after { content: ''; width: 36px; height: 36px; border: 1px solid #cbd5e1; border-radius: 50%; position: relative; z-index: 1; }
.sb-ed-spec-card__thumb--star::after { content: '★'; font-size: 28px; color: #cbd5e1; position: relative; z-index: 1; }
.sb-ed-spec-card__thumb--clover::after { content: '✿'; font-size: 28px; color: #cbd5e1; position: relative; z-index: 1; }
.sb-ed-spec-card__thumb--speech::after { content: '◉'; font-size: 28px; color: #cbd5e1; position: relative; z-index: 1; }
.sb-ed-spec-card__thumb--bone::after { content: '⬭'; font-size: 28px; color: #cbd5e1; position: relative; z-index: 1; }
.sb-ed-spec-card__thumb--arrow::after { content: '➤'; font-size: 28px; color: #cbd5e1; position: relative; z-index: 1; }
.sb-ed-spec-card__thumb--cloud::after { content: '☁'; font-size: 28px; color: #cbd5e1; position: relative; z-index: 1; }
.sb-ed-spec-card__thumb--hex::after { content: '⬡'; font-size: 28px; color: #cbd5e1; position: relative; z-index: 1; }
.sb-ed-spec-card__thumb--tag::before {
    background: repeating-linear-gradient(0deg, transparent, transparent 18px, #e2e8f0 18px, #e2e8f0 19px);
}
.sb-ed-spec-card__qty {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    font-size: 42px; font-weight: 700; color: rgba(148,163,184,.35); z-index: 2; pointer-events: none;
}
.sb-ed-spec-card__badge {
    position: absolute; top: 6px; right: 6px; width: 16px; height: 16px; border-radius: 50%;
    background: var(--ed-red); color: #fff; font-size: 9px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; z-index: 3;
}
.sb-ed-spec-card__paper {
    position: absolute; bottom: 6px; right: 6px; font-size: 8px; font-weight: 700;
    color: #94a3b8; border: 1px solid #e2e8f0; padding: 2px 4px; border-radius: 2px; background: #fff; z-index: 3;
}
.sb-ed-spec-card__brand {
    position: absolute; top: 6px; right: 6px; font-size: 7px; font-weight: 700; color: var(--ed-red); z-index: 3;
}
.sb-ed-spec-card__tagtype {
    position: absolute; bottom: 6px; right: 6px; font-size: 7px; font-weight: 700;
    color: #64748b; border: 1px solid #e2e8f0; padding: 2px 4px; background: #fff; z-index: 3;
}
.sb-ed-spec-card__meta {
    display: flex; align-items: center; justify-content: space-between; padding: 8px 10px;
    font-size: 11px; color: #64748b;
}
.sb-ed-spec-card__meta strong { color: #1e293b; font-size: 12px; }

/* 템플릿 */
.sb-ed-layer--template .sb-ed-layer__searchbar { flex-shrink: 0; }
.sb-ed-layer__tagbar {
    display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 20px 12px 20px;
    border-bottom: 1px solid #f1f5f9; flex-shrink: 0;
}
.sb-ed-layer__htag {
    padding: 6px 12px; border-radius: 999px; border: 1px solid #e2e8f0; background: #fff;
    font-size: 12px; color: #475569; cursor: pointer; font-family: inherit; white-space: nowrap;
}
.sb-ed-layer__htag em { font-style: normal; color: #94a3b8; margin-left: 2px; }
.sb-ed-layer__htag.is-active { background: var(--ed-red); border-color: var(--ed-red); color: #fff; }
.sb-ed-layer__htag.is-active em { color: rgba(255,255,255,.85); }
.sb-ed-tpl-card {
    border: none; background: none; padding: 0; cursor: pointer; text-align: left; font-family: inherit;
}
.sb-ed-tpl-card__thumb {
    aspect-ratio: 1; border-radius: 6px; border: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: center; margin-bottom: 8px;
    transition: box-shadow .15s;
}
.sb-ed-tpl-card:hover .sb-ed-tpl-card__thumb { box-shadow: 0 6px 20px rgba(15,23,42,.1); }
.sb-ed-tpl-card__preview { font-size: 11px; font-weight: 700; color: #475569; text-align: center; padding: 8px; line-height: 1.3; }
.sb-ed-tpl-card__title { font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 4px; line-height: 1.35; }
.sb-ed-tpl-card__tags { font-size: 10px; color: #94a3b8; }

/* 나의디자인 */
.sb-ed-layer__my-head {
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
    padding: 16px 20px 12px 20px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0;
}
.sb-ed-layer__import {
    padding: 8px 14px; border: 1px solid var(--ed-red); border-radius: 6px;
    background: #fff; color: var(--ed-red); font-size: 12px; font-weight: 600; cursor: pointer; font-family: inherit;
}
.sb-ed-layer__my-tools { display: flex; align-items: center; gap: 10px; margin-left: auto; flex-wrap: wrap; }
.sb-ed-layer__scroll--my { padding: 20px; }
.sb-ed-layer__section h4 { margin: 0 0 10px; font-size: 14px; font-weight: 700; color: #1e293b; }
.sb-ed-layer__section + .sb-ed-layer__section { margin-top: 28px; }
.sb-ed-layer__recent-chip {
    display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px;
    border: 1px solid var(--ed-border); border-radius: 8px; font-size: 12px; color: #475569; background: #f8fafc;
}
.sb-ed-layer__empty {
    display: flex; align-items: center; gap: 10px; padding: 48px 20px;
    border: 1px solid #f1f5f9; border-radius: 8px; background: #fafafa;
    color: #64748b; font-size: 13px;
}
.sb-ed-layer__empty-icon {
    width: 28px; height: 28px; border-radius: 50%; background: #e2e8f0;
    display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0;
}

/* AI */
.sb-ed-layer--ai { background: linear-gradient(180deg, #faf5ff 0%, #fff 120px); }
.sb-ed-layer__ai-wrap { flex: 1; overflow: auto; padding: 24px 20px 32px 24px; max-width: 920px; margin: 0 auto; width: 100%; }
.sb-ed-layer__ai-hero {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 20px;
}
.sb-ed-layer__ai-hero p { margin: 0; font-size: 14px; color: #64748b; max-width: 520px; line-height: 1.5; }
.sb-ed-layer__ai-bot { font-size: 56px; line-height: 1; opacity: .9; }
.sb-ed-layer__ai-box {
    border: 2px dashed #c4b5fd; border-radius: 16px; padding: 24px; background: rgba(255,255,255,.85);
}
.sb-ed-layer__ai-title { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 6px; }
.sb-ed-layer__ai-sub { margin: 0 0 14px; font-size: 13px; color: #64748b; }
.sb-ed-layer__ai-input {
    border: 1px solid var(--ed-border); border-radius: 12px; background: #fff; overflow: hidden;
}
.sb-ed-layer__ai-input textarea {
    width: 100%; border: none; padding: 14px 16px; font-size: 13px; font-family: inherit;
    resize: none; outline: none; color: #334155; background: transparent;
}
.sb-ed-layer__ai-input-foot {
    display: flex; align-items: center; justify-content: space-between; padding: 8px 12px;
    border-top: 1px solid #f1f5f9; font-size: 11px; color: #94a3b8;
}
.sb-ed-layer__ai-send {
    width: 36px; height: 36px; border: none; border-radius: 10px; background: var(--ed-primary);
    color: #fff; font-size: 16px; cursor: pointer;
}
.sb-ed-layer__ai-or { text-align: center; color: #94a3b8; font-size: 12px; margin: 18px 0; }
.sb-ed-layer__ai-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.sb-ed-layer__ai-card {
    border: 1px solid var(--ed-border); border-radius: 12px; background: #fff; padding: 16px 12px;
    cursor: pointer; text-align: center; font-family: inherit; transition: border-color .15s, box-shadow .15s;
}
.sb-ed-layer__ai-card:hover { border-color: #c4b5fd; box-shadow: 0 4px 16px rgba(99,102,241,.1); }
.sb-ed-layer__ai-card-icon { display: block; font-size: 28px; margin-bottom: 8px; }
.sb-ed-layer__ai-card-icon--green { color: #22c55e; }
.sb-ed-layer__ai-card-icon--purple { color: var(--ed-primary); font-weight: 700; }
.sb-ed-layer__ai-card strong { display: block; font-size: 12px; color: #1e293b; margin-bottom: 4px; }
.sb-ed-layer__ai-card small { display: block; font-size: 10px; color: #94a3b8; line-height: 1.4; }
.sb-ed-layer__ai-tip { margin-top: 18px; font-size: 12px; color: #64748b; }
.sb-ed-layer__ai-examples {
    display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 12px;
}
.sb-ed-layer__ai-examples span {
    padding: 6px 12px; border-radius: 999px; background: #f1f5f9; font-size: 11px; color: #475569;
}
.sb-ed-layer__ai-more { margin-left: auto; font-size: 11px; color: var(--ed-primary); text-decoration: none; white-space: nowrap; }

@media (max-width: 1400px) {
    .sb-ed-layer__grid--6 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .sb-ed-layer__ai-cards { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 1100px) {
    .sb-ed-layer__grid--6 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
