<?php
/** 데이터 가져오기 팝업 스타일 */
?>
.sb-ed-data-import-overlay {
    position: absolute;
    inset: 0;
    z-index: 305;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
    background: rgba(15, 23, 42, .52);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity .22s ease, visibility .22s ease;
}
.sb-ed-data-import-overlay.is-open {
    display: flex;
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}
.sb-ed-data-import-dialog {
    width: min(560px, 100%);
    max-height: calc(100% - 24px);
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 28px 80px rgba(15, 23, 42, .22);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform: translateY(10px) scale(.98);
    transition: transform .24s ease;
    --data-accent: var(--ed-primary, #6366f1);
    --data-accent-soft: var(--ed-primary-soft, #eef2ff);
}
.sb-ed-data-import-overlay.is-open .sb-ed-data-import-dialog {
    transform: translateY(0) scale(1);
}

.sb-ed-data-import__head {
    position: relative;
    padding: 18px 52px 14px 22px;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
}
.sb-ed-data-import__head h3 {
    margin: 0 0 6px;
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -.02em;
}
.sb-ed-data-import__head p {
    margin: 0;
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
}
.sb-ed-data-import__close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 34px;
    height: 34px;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: #64748b;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    transition: background .15s, color .15s;
}
.sb-ed-data-import__close:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.sb-ed-data-import__tabs {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
    padding: 0 12px;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
    background: #fff;
}
.sb-ed-data-import__tab {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 12px 6px;
    border: none;
    background: transparent;
    font-family: inherit;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -1px;
    transition: color .15s, border-color .15s, background .15s;
}
.sb-ed-data-import__tab-icon {
    font-size: 18px;
    line-height: 1;
}
.sb-ed-data-import__tab-logo {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
    color: #fff;
}
.sb-ed-data-import__tab-logo--ilabel { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
.sb-ed-data-import__tab-logo--formtec { background: linear-gradient(135deg, #f59e0b, #d97706); }
.sb-ed-data-import__tab:hover {
    color: var(--data-accent);
    background: var(--data-accent-soft);
}
.sb-ed-data-import__tab.is-active {
    color: var(--data-accent);
    background: #fff;
    border-bottom-color: var(--data-accent);
}

.sb-ed-data-import__body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 18px 22px;
}
.sb-ed-data-import__panel[hidden] { display: none !important; }

.sb-ed-data-import__format-head {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}
.sb-ed-data-import__format-icon {
    font-size: 28px;
    line-height: 1;
    flex-shrink: 0;
}
.sb-ed-data-import__format-logo {
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
.sb-ed-data-import__format-logo--ilabel { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
.sb-ed-data-import__format-logo--formtec { background: linear-gradient(135deg, #f59e0b, #d97706); }
.sb-ed-data-import__format-head > div {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}
.sb-ed-data-import__format-head strong {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
}
.sb-ed-data-import__format-head span {
    font-size: 11px;
    color: #64748b;
}

.sb-ed-data-import__dropzone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 32px 20px;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    background: #f8fafc;
    cursor: pointer;
    text-align: center;
    transition: border-color .18s, background .18s, box-shadow .18s;
}
.sb-ed-data-import__dropzone:hover,
.sb-ed-data-import__dropzone.is-dragover {
    border-color: var(--data-accent);
    background: var(--data-accent-soft);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
}
.sb-ed-data-import__dropzone.has-file {
    border-style: solid;
    border-color: #86efac;
    background: #f0fdf4;
}
.sb-ed-data-import__file-input {
    position: absolute;
    width: 0;
    height: 0;
    opacity: 0;
    overflow: hidden;
    pointer-events: none;
}
.sb-ed-data-import__drop-icon {
    font-size: 32px;
    line-height: 1;
    opacity: .85;
}
.sb-ed-data-import__drop-title {
    font-size: 14px;
    font-weight: 700;
    color: #334155;
}
.sb-ed-data-import__drop-hint {
    font-size: 12px;
    color: #94a3b8;
}
.sb-ed-data-import__drop-name {
    margin-top: 4px;
    padding: 4px 12px;
    border-radius: 999px;
    background: #fff;
    border: 1px solid #bbf7d0;
    font-size: 12px;
    font-weight: 600;
    color: #15803d;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sb-ed-data-import__tip {
    margin: 14px 0 0;
    padding: 10px 12px;
    border-radius: 8px;
    background: #fffbeb;
    border: 1px solid #fef3c7;
    font-size: 12px;
    color: #92400e;
    line-height: 1.5;
}

.sb-ed-data-import__foot {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 14px 22px;
    border-top: 1px solid #f1f5f9;
    flex-shrink: 0;
    background: #fafbfc;
}
.sb-ed-data-import__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 88px;
    height: 38px;
    padding: 0 18px;
    border-radius: 8px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s, border-color .15s, color .15s;
}
.sb-ed-data-import__btn--outline {
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
}
.sb-ed-data-import__btn--outline:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}
.sb-ed-data-import__btn--primary {
    border: none;
    background: linear-gradient(135deg, #6366f1 0%, #7c3aed 100%);
    color: #fff;
    box-shadow: 0 4px 14px rgba(99, 102, 241, .35);
}
.sb-ed-data-import__btn--primary:hover {
    box-shadow: 0 6px 20px rgba(99, 102, 241, .45);
}
.sb-ed-data-import__btn--primary:disabled {
    opacity: .45;
    cursor: not-allowed;
    box-shadow: none;
}
