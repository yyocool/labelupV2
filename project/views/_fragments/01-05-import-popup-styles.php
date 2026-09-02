<?php
/** 가져오기 레이어 · 플로팅 버튼 스타일 */
?>
.sb-hifi-editor__import-fab {
    position: absolute;
    right: 24px;
    bottom: 24px;
    z-index: 320;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0 18px 0 14px;
    height: 46px;
    border: none;
    border-radius: 999px;
    background: linear-gradient(135deg, #6366f1 0%, #7c3aed 100%);
    color: #fff;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 8px 28px rgba(99,102,241,.45), 0 2px 8px rgba(15,23,42,.12);
    transition: transform .2s ease, box-shadow .2s ease;
}
.sb-hifi-editor__import-fab:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 36px rgba(99,102,241,.5), 0 4px 12px rgba(15,23,42,.15);
}
.sb-hifi-editor__import-fab__icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(255,255,255,.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    line-height: 1;
}

.sb-hifi-editor__import-overlay {
    position: absolute;
    inset: 0;
    z-index: 290;
    background: rgba(15,23,42,.4);
    display: none;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity .2s ease, visibility .2s ease;
}
.sb-hifi-editor__import-overlay.is-open {
    display: flex;
    align-items: stretch;
    justify-content: stretch;
    padding: 8px;
    box-sizing: border-box;
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}
.sb-hifi-editor__import-dialog {
    flex: 1;
    width: 100%;
    margin: 0;
    height: 100%;
    max-height: none;
    min-height: 0;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 24px 80px rgba(15,23,42,.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform: translateY(8px) scale(.99);
    transition: transform .22s ease;
    --import-accent: var(--ed-primary, #6366f1);
    --import-accent-soft: var(--ed-primary-soft, #eef2ff);
    --import-accent-deep: #7c3aed;
}
.sb-hifi-editor__import-overlay.is-open .sb-hifi-editor__import-dialog {
    transform: translateY(0) scale(1);
}

.sb-ed-import__head {
    position: relative;
    padding: 16px 52px 14px 22px;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
}
.sb-ed-import__head h3 {
    margin: 0 0 6px;
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -.02em;
}
.sb-ed-import__head p {
    margin: 0;
    font-size: 13px;
    color: #64748b;
}
.sb-hifi-editor__import-dialog .sb-hifi-editor__layer-close {
    top: 16px;
    right: 16px;
}

.sb-ed-import__tabs {
    display: flex;
    gap: 0;
    padding: 0 16px;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
    background: #fff;
}
.sb-ed-import__tab {
    flex: 1;
    min-width: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 14px 8px;
    border: none;
    background: transparent;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -1px;
    white-space: nowrap;
    transition: color .15s, border-color .15s, background .15s;
}
.sb-ed-import__tab-icon {
    font-size: 16px;
    line-height: 1;
    flex-shrink: 0;
}
.sb-ed-import__tab:hover {
    color: var(--import-accent);
    background: var(--import-accent-soft);
}
.sb-ed-import__tab.is-active {
    color: var(--import-accent);
    background: #fff;
    border-bottom-color: var(--import-accent);
}

.sb-ed-import__panel.sb-ed-layer--catalog,
.sb-ed-import__panel.sb-ed-layer--template {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.sb-ed-import__panel.sb-ed-layer--catalog .sb-ed-layer__tabs,
.sb-ed-import__panel.sb-ed-layer--template .sb-ed-layer__searchbar {
    padding-right: 20px;
}
.sb-ed-import__panel.sb-ed-layer--catalog .sb-ed-layer__toolbar {
    padding-right: 20px;
}
.sb-ed-import__panel.sb-ed-layer--catalog .sb-ed-layer__scroll,
.sb-ed-import__catalog-scroll {
    flex: 1;
    min-height: 0;
    overflow: auto;
}
.sb-ed-import__panel.sb-ed-layer--template .sb-ed-layer__tagbar {
    flex-shrink: 0;
}
.sb-ed-import__panel.sb-ed-layer--template .sb-ed-import__catalog-scroll {
    flex: 1;
    min-height: 0;
}
.sb-ed-import__panel--smart,
.sb-ed-import__panel[data-sb-import-panel="external"],
.sb-ed-import__panel[data-sb-import-panel="mydesign"] {
    flex: 1;
    min-height: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.sb-ed-import__panel--smart .sb-ed-import__scroll,
.sb-ed-import__panel[data-sb-import-panel="external"] .sb-ed-import__scroll,
.sb-ed-import__panel[data-sb-import-panel="mydesign"] .sb-ed-import__scroll {
    flex: 1;
    min-height: 0;
    overflow: auto;
}
.sb-ed-import__panel.sb-ed-layer--template .sb-ed-layer__search--wide {
    margin: 12px 20px 0;
}
.sb-ed-import__reset {
    width: 32px;
    height: 32px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    color: #64748b;
    font-size: 14px;
    cursor: pointer;
    flex-shrink: 0;
    line-height: 1;
    font-family: inherit;
}
.sb-ed-import__reset:hover {
    border-color: #c7d2fe;
    color: var(--import-accent);
    background: var(--import-accent-soft);
}
.sb-ed-spec-card__thumb--rect::after {
    content: '';
    width: 52px;
    height: 18px;
    border: 1px solid #cbd5e1;
    border-radius: 2px;
    position: relative;
    z-index: 1;
}
.sb-ed-spec-card__thumb--circle::after {
    content: '';
    width: 36px;
    height: 36px;
    border: 1px solid #cbd5e1;
    border-radius: 50%;
    position: relative;
    z-index: 1;
}

/* ── 가져오기 카탈로그 (Label-UP 컨셉) ── */
.sb-hifi-editor__import-dialog .sb-ed-layer__tabs {
    gap: 8px;
    padding: 14px 20px 0;
    border-bottom: none;
}
.sb-hifi-editor__import-dialog .sb-ed-layer__tab {
    flex: 1;
    padding: 14px 12px;
    border-radius: 10px 10px 0 0;
    border: 1px solid #e2e8f0;
    border-bottom: none;
    background: #f8fafc;
    font-size: 14px;
    font-weight: 700;
    color: #64748b;
}
.sb-hifi-editor__import-dialog .sb-ed-layer__tab em {
    font-size: 12px;
    color: #94a3b8;
    margin-left: 6px;
}
.sb-hifi-editor__import-dialog .sb-ed-layer__tab.is-active {
    color: #fff;
    background: linear-gradient(135deg, var(--import-accent) 0%, var(--import-accent-deep) 100%);
    border-color: transparent;
    box-shadow: 0 4px 14px rgba(99, 102, 241, .28);
}
.sb-hifi-editor__import-dialog .sb-ed-layer__tab.is-active em {
    color: rgba(255, 255, 255, .88);
}
.sb-hifi-editor__import-dialog .sb-ed-layer__toolbar {
    padding: 12px 20px;
    border-bottom: 1px solid #f1f5f9;
    background: #fff;
}
.sb-hifi-editor__import-dialog .sb-ed-layer__subtabs {
    gap: 24px;
}
.sb-hifi-editor__import-dialog .sb-ed-layer__subtab {
    font-size: 14px;
    padding-bottom: 8px;
}
.sb-hifi-editor__import-dialog .sb-ed-layer__subtab.is-active {
    color: #0f172a;
    border-bottom-color: var(--import-accent);
}
.sb-hifi-editor__import-dialog .sb-ed-layer__search {
    min-width: 220px;
    border-radius: 8px;
    border-color: #e2e8f0;
    transition: border-color .15s, box-shadow .15s;
}
.sb-hifi-editor__import-dialog .sb-ed-layer__search:focus-within {
    border-color: var(--import-accent);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
}
.sb-hifi-editor__import-dialog .sb-ed-layer__select {
    border-radius: 8px;
    cursor: pointer;
    transition: border-color .15s;
}
.sb-hifi-editor__import-dialog .sb-ed-layer__select:hover {
    border-color: #c7d2fe;
}
.sb-hifi-editor__import-dialog .sb-ed-import__catalog-scroll {
    padding: 16px 20px 24px;
    background: #fafbfc;
}
.sb-hifi-editor__import-dialog .sb-ed-layer__grid--6 {
    grid-template-columns: repeat(auto-fill, minmax(148px, 1fr));
    gap: 16px;
}
.sb-hifi-editor__import-dialog .sb-ed-import__spec-card {
    border-radius: 8px;
    border-color: #e2e8f0;
    overflow: hidden;
    transition: border-color .18s, box-shadow .18s, transform .18s;
}
.sb-hifi-editor__import-dialog .sb-ed-import__spec-card:hover {
    border-color: #c7d2fe;
    box-shadow: 0 8px 24px rgba(99, 102, 241, .14);
    transform: translateY(-2px);
}
.sb-hifi-editor__import-dialog .sb-ed-spec-card__thumb--sheet {
    background: #fff;
}
.sb-hifi-editor__import-dialog .sb-ed-spec-card__thumb--sheet::before {
    inset: 10% 12%;
    border: 1px dashed #94a3b8;
    border-radius: 3px;
    background-image:
        linear-gradient(#e2e8f0 1px, transparent 1px),
        linear-gradient(90deg, #e2e8f0 1px, transparent 1px);
    background-size: 100% 22%, 33.33% 100%;
    background-position: 0 0, 0 0;
    opacity: .55;
}
.sb-hifi-editor__import-dialog .sb-ed-spec-card__thumb--tag.sb-ed-spec-card__thumb--sheet::before {
    background-image: repeating-linear-gradient(
        0deg,
        transparent,
        transparent 17px,
        #cbd5e1 17px,
        #cbd5e1 18px
    );
    background-size: auto;
    opacity: .65;
}
.sb-hifi-editor__import-dialog .sb-ed-spec-card__thumb--heart::after,
.sb-hifi-editor__import-dialog .sb-ed-spec-card__thumb--star::after,
.sb-hifi-editor__import-dialog .sb-ed-spec-card__thumb--clover::after,
.sb-hifi-editor__import-dialog .sb-ed-spec-card__thumb--speech::after,
.sb-hifi-editor__import-dialog .sb-ed-spec-card__thumb--bone::after,
.sb-hifi-editor__import-dialog .sb-ed-spec-card__thumb--arrow::after,
.sb-hifi-editor__import-dialog .sb-ed-spec-card__thumb--cloud::after,
.sb-hifi-editor__import-dialog .sb-ed-spec-card__thumb--hex::after,
.sb-hifi-editor__import-dialog .sb-ed-spec-card__thumb--rect::after,
.sb-hifi-editor__import-dialog .sb-ed-spec-card__thumb--circle::after {
    opacity: .35;
    transform: scale(.85);
}
.sb-hifi-editor__import-dialog .sb-ed-spec-card__qty {
    font-size: 48px;
    font-weight: 800;
    color: rgba(148, 163, 184, .32);
}
.sb-hifi-editor__import-dialog .sb-ed-spec-card__brand {
    top: 8px;
    right: 8px;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .02em;
    color: var(--import-accent);
    background: var(--import-accent-soft);
    padding: 3px 7px;
    border-radius: 4px;
    border: 1px solid rgba(99, 102, 241, .15);
}
.sb-hifi-editor__import-dialog .sb-ed-spec-card__meta {
    padding: 10px 12px;
    background: #fff;
    font-size: 12px;
}
.sb-hifi-editor__import-dialog .sb-ed-spec-card__meta strong {
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: .01em;
}
.sb-hifi-editor__import-dialog .sb-ed-tpl-card {
    border-radius: 10px;
    border-color: #e2e8f0;
    transition: border-color .18s, box-shadow .18s, transform .18s;
}
.sb-hifi-editor__import-dialog .sb-ed-tpl-card:hover {
    border-color: #c7d2fe;
    box-shadow: 0 8px 24px rgba(99, 102, 241, .12);
    transform: translateY(-2px);
}

.sb-ed-import__panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.sb-ed-import__panel[hidden] { display: none !important; }
.sb-ed-import__toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 24px;
    border-bottom: 1px solid #f8fafc;
    flex-shrink: 0;
}
.sb-ed-import__search { flex: 1; min-width: 0; max-width: 360px; }
.sb-ed-import__scroll {
    flex: 1;
    overflow: auto;
    padding: 20px 24px 28px;
    min-height: 0;
}
.sb-ed-import__section-label {
    margin: 0 0 14px;
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.sb-ed-import__design-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 16px;
}
.sb-ed-import__design-card {
    border: 1px solid #f1f5f9;
    border-radius: 12px;
    background: #fff;
    padding: 0;
    cursor: pointer;
    text-align: left;
    font-family: inherit;
    overflow: hidden;
    transition: border-color .15s, box-shadow .15s, transform .15s;
}
.sb-ed-import__design-card:hover {
    border-color: #c7d2fe;
    box-shadow: 0 8px 24px rgba(99,102,241,.12);
    transform: translateY(-2px);
}
.sb-ed-import__design-thumb {
    position: relative;
    aspect-ratio: 1.2;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid #f1f5f9;
}
.sb-ed-import__design-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    padding: 2px 8px;
    border-radius: 999px;
    background: #6366f1;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
}
.sb-ed-import__design-preview {
    font-size: 13px;
    font-weight: 800;
    color: rgba(30,41,59,.55);
}
.sb-ed-import__design-meta {
    padding: 10px 12px 12px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.sb-ed-import__design-meta strong {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.3;
}
.sb-ed-import__design-meta span {
    font-size: 11px;
    color: #64748b;
}
.sb-ed-import__design-meta em {
    font-size: 10px;
    color: #94a3b8;
    font-style: normal;
    margin-top: 2px;
}

.sb-ed-import__panel--smart {
    background: linear-gradient(180deg, #faf5ff 0%, #fff 160px);
}
.sb-ed-import__scroll--smart {
    padding: 20px 28px 32px;
}
.sb-ed-import__ai-wrap {
    max-width: 920px;
    margin: 0 auto;
    width: 100%;
}
.sb-ed-import__ai-box {
    border: 2px dashed #c4b5fd;
    border-radius: 16px;
    padding: 28px 28px 24px;
    background: rgba(255,255,255,.92);
}
.sb-ed-import__ai-title {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 6px;
}
.sb-ed-import__ai-sub {
    margin: 0 0 16px;
    font-size: 13px;
    color: #64748b;
}
.sb-ed-import__ai-input {
    border: 1px solid var(--ed-border, #e2e8f0);
    border-radius: 12px;
    background: #fff;
    overflow: hidden;
}
.sb-ed-import__ai-input textarea {
    width: 100%;
    border: none;
    padding: 14px 16px;
    font-size: 13px;
    font-family: inherit;
    resize: none;
    outline: none;
    color: #334155;
    background: transparent;
    line-height: 1.55;
}
.sb-ed-import__ai-input textarea::placeholder {
    color: #94a3b8;
}
.sb-ed-import__ai-input-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    border-top: 1px solid #f1f5f9;
    font-size: 11px;
    color: #94a3b8;
}
.sb-ed-import__ai-send {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 10px;
    background: var(--ed-primary, #6366f1);
    color: #fff;
    font-size: 16px;
    cursor: pointer;
    flex-shrink: 0;
    transition: filter .15s;
}
.sb-ed-import__ai-send:hover { filter: brightness(1.05); }
.sb-ed-import__ai-or {
    text-align: center;
    color: #94a3b8;
    font-size: 12px;
    margin: 20px 0 18px;
}
.sb-ed-import__ai-cards {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}
.sb-ed-import__ai-card {
    border: 1px solid var(--ed-border, #e2e8f0);
    border-radius: 12px;
    background: #fff;
    padding: 16px 12px;
    cursor: pointer;
    text-align: center;
    font-family: inherit;
    transition: border-color .15s, box-shadow .15s;
}
.sb-ed-import__ai-card:hover {
    border-color: #c4b5fd;
    box-shadow: 0 4px 16px rgba(99,102,241,.1);
}
.sb-ed-import__ai-card-icon {
    display: block;
    font-size: 28px;
    margin-bottom: 8px;
    line-height: 1;
}
.sb-ed-import__ai-card-icon--green { color: #22c55e; }
.sb-ed-import__ai-card-icon--purple { color: var(--ed-primary, #6366f1); font-weight: 700; }
.sb-ed-import__ai-card strong {
    display: block;
    font-size: 12px;
    color: #1e293b;
    margin-bottom: 4px;
    font-weight: 700;
}
.sb-ed-import__ai-card small {
    display: block;
    font-size: 10px;
    color: #94a3b8;
    line-height: 1.45;
}
.sb-ed-import__ai-tip {
    margin-top: 18px;
    font-size: 12px;
    color: #64748b;
}
.sb-ed-import__ai-examples {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
}
.sb-ed-import__ai-chip {
    padding: 6px 12px;
    border-radius: 999px;
    background: #f1f5f9;
    font-size: 11px;
    color: #475569;
    border: none;
    cursor: pointer;
    font-family: inherit;
    transition: background .15s, color .15s;
}
.sb-ed-import__ai-chip:hover {
    background: #eef2ff;
    color: #4338ca;
}
.sb-ed-import__ai-more {
    margin-left: auto;
    font-size: 11px;
    color: var(--ed-primary, #6366f1);
    background: none;
    border: none;
    cursor: pointer;
    font-family: inherit;
    white-space: nowrap;
    padding: 6px 0;
}
.sb-ed-import__ai-more:hover { text-decoration: underline; }

.sb-ed-import__format-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 640px;
}
.sb-ed-import__format-block {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
}
.sb-ed-import__format-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}
.sb-ed-import__format-head > div {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}
.sb-ed-import__format-head strong {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
}
.sb-ed-import__format-head span {
    font-size: 11px;
    color: #64748b;
}
.sb-ed-import__format-tag {
    display: inline-block;
    margin-right: 6px;
    padding: 1px 6px;
    border-radius: 4px;
    background: #fef3c7;
    color: #b45309;
    font-size: 10px !important;
    font-weight: 700;
}
.sb-ed-import__format-logo {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 800;
    color: #fff;
    flex-shrink: 0;
}
.sb-ed-import__format-logo--ilabel { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
.sb-ed-import__format-logo--formtec { background: linear-gradient(135deg, #f59e0b, #d97706); }

.sb-ed-import__dropzone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin: 16px;
    padding: 28px 20px;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    background: #fafbfc;
    cursor: pointer;
    transition: border-color .15s, background .15s;
    text-align: center;
}
.sb-ed-import__dropzone:hover,
.sb-ed-import__dropzone.is-dragover {
    border-color: #6366f1;
    background: #eef2ff;
}
.sb-ed-import__dropzone.has-file {
    border-color: #22c55e;
    border-style: solid;
    background: #f0fdf4;
}
.sb-ed-import__file-input {
    position: absolute;
    width: 0;
    height: 0;
    opacity: 0;
    overflow: hidden;
}
.sb-ed-import__drop-icon { font-size: 28px; opacity: .7; }
.sb-ed-import__drop-title {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
}
.sb-ed-import__drop-hint {
    font-size: 11px;
    color: #94a3b8;
}
.sb-ed-import__drop-name {
    margin-top: 4px;
    padding: 4px 10px;
    border-radius: 6px;
    background: #dcfce7;
    color: #166534;
    font-size: 11px;
    font-weight: 600;
}

.sb-wf-annotate .sb-hifi-editor .sb-hifi-editor__import-overlay:not(.is-open) { display: none !important; }
.sb-wf-annotate .sb-hifi-editor .sb-hifi-editor__import-overlay.is-open {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    pointer-events: auto !important;
}
.sb-wf-annotate .sb-hifi-editor .sb-ed-spec-detail-overlay:not(.is-open) { display: none !important; }
.sb-wf-annotate .sb-hifi-editor .sb-ed-spec-detail-overlay.is-open {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    pointer-events: auto !important;
}

@media (max-width: 900px) {
    .sb-ed-import__tab { font-size: 11px; padding: 10px 4px; gap: 3px; }
    .sb-ed-import__tab-icon { font-size: 13px; }
    .sb-ed-import__ai-cards { grid-template-columns: repeat(2, 1fr); }
    .sb-ed-import__ai-more { margin-left: 0; width: 100%; text-align: right; }
}
