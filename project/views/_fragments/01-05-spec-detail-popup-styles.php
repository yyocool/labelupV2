<?php
/** 라벨·태그 상세 사양 팝업 스타일 */
?>
.sb-ed-spec-detail-overlay {
    position: absolute;
    inset: 0;
    z-index: 310;
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
.sb-ed-spec-detail-overlay.is-open {
    display: flex;
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}
.sb-ed-spec-detail-dialog {
    width: min(720px, 100%);
    max-height: calc(100% - 24px);
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 28px 80px rgba(15, 23, 42, .22);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform: translateY(10px) scale(.98);
    transition: transform .24s ease;
}
.sb-ed-spec-detail-overlay.is-open .sb-ed-spec-detail-dialog {
    transform: translateY(0) scale(1);
}
.sb-ed-spec-detail__head {
    position: relative;
    padding: 18px 52px 14px 22px;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
}
.sb-ed-spec-detail__title {
    margin: 0;
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -.02em;
}
.sb-ed-spec-detail__close {
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
.sb-ed-spec-detail__close:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.sb-ed-spec-detail__body {
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr);
    gap: 0;
    min-height: 0;
    flex: 1;
    overflow: auto;
}
.sb-ed-spec-detail__diagram-wrap {
    padding: 20px 16px 20px 22px;
    border-right: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(180deg, #fafbff 0%, #fff 100%);
    min-height: 280px;
}
.sb-ed-spec-detail__diagram {
    width: 100%;
    max-width: 300px;
}
.sb-ed-spec-detail__diagram .sb-ed-spec-detail__svg {
    width: 100%;
    height: auto;
    display: block;
}
.sb-ed-spec-detail__specs {
    margin: 0;
    padding: 18px 22px 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 0;
}
.sb-ed-spec-detail__row {
    display: grid;
    grid-template-columns: 118px 1fr;
    gap: 10px;
    align-items: center;
    padding: 11px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px;
}
.sb-ed-spec-detail__row:last-child {
    border-bottom: none;
}
.sb-ed-spec-detail__row dt {
    margin: 0;
    color: #64748b;
    font-weight: 500;
}
.sb-ed-spec-detail__row dd {
    margin: 0;
    color: #0f172a;
    font-weight: 700;
}
.sb-ed-spec-detail__select {
    width: 100%;
    max-width: 140px;
    height: 34px;
    padding: 0 28px 0 10px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%2364748b' d='M1 1l4 4 4-4'/%3E%3C/svg%3E") no-repeat right 10px center;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    appearance: none;
    cursor: pointer;
}
.sb-ed-spec-detail__select:focus {
    outline: none;
    border-color: var(--ed-primary, #6366f1);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, .15);
}
.sb-ed-spec-detail__foot {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 22px 20px;
    border-top: 1px solid #f1f5f9;
    flex-shrink: 0;
    background: #fafbfc;
}
.sb-ed-spec-detail__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 44px;
    padding: 0 18px;
    border-radius: 10px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s, background .15s, border-color .15s;
    white-space: nowrap;
}
.sb-ed-spec-detail__btn--outline {
    flex: 0 1 auto;
    min-width: 148px;
    border: 1.5px solid var(--ed-primary, #6366f1);
    background: #fff;
    color: var(--ed-primary, #6366f1);
}
.sb-ed-spec-detail__btn--outline:hover {
    background: var(--ed-primary-soft, #eef2ff);
}
.sb-ed-spec-detail__btn--primary {
    flex: 1 1 auto;
    max-width: 320px;
    border: none;
    background: linear-gradient(135deg, #6366f1 0%, #7c3aed 100%);
    color: #fff;
    box-shadow: 0 6px 20px rgba(99, 102, 241, .35);
}
.sb-ed-spec-detail__btn--primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 28px rgba(99, 102, 241, .42);
}
.sb-ed-spec-detail__btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.sb-ed-spec-detail__btn-icon--info {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 1.5px solid currentColor;
    font-size: 11px;
    font-weight: 800;
    font-style: normal;
    line-height: 1;
}
.sb-ed-spec-detail__btn-icon--edit {
    font-size: 15px;
    opacity: .92;
}
.sb-ed-spec-detail__btn--primary .sb-ed-spec-detail__btn-icon--edit {
    margin-left: auto;
}

@media (max-width: 640px) {
    .sb-ed-spec-detail__body {
        grid-template-columns: 1fr;
    }
    .sb-ed-spec-detail__diagram-wrap {
        border-right: none;
        border-bottom: 1px solid #f1f5f9;
        min-height: 220px;
    }
    .sb-ed-spec-detail__foot {
        flex-direction: column;
    }
    .sb-ed-spec-detail__btn--outline,
    .sb-ed-spec-detail__btn--primary {
        width: 100%;
        max-width: none;
    }
}
