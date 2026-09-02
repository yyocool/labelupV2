<?php
/** 쇼핑몰 메인 — hi-fi 와이어프레임 */
?>
.sb-hifi-shop {
    --shop-primary: #6b46ff;
    --shop-primary-soft: #ede9fe;
    --shop-border: #e2e8f0;
    --shop-text: #1e293b;
    --shop-muted: #64748b;
    --shop-bg: #f8fafc;
    font-family: 'Inter', 'Pretendard', sans-serif;
    font-size: 13px;
    color: var(--shop-text);
    background: #fff;
    min-width: 1320px;
    min-height: 820px;
    height: 100%;
    display: flex;
    line-height: 1.45;
    box-sizing: border-box;
}
.sb-hifi-shop *, .sb-hifi-shop *::before, .sb-hifi-shop *::after { box-sizing: border-box; }

.sb-hifi-shop__sidebar {
    width: 200px; flex-shrink: 0; border-right: 1px solid var(--shop-border);
    display: flex; flex-direction: column; background: #fff; min-height: 820px; height: 100%;
}
.sb-hifi-shop__logo { padding: 16px 14px 12px; border-bottom: 1px solid var(--shop-border); }
.sb-hifi-shop__logo strong { display: block; font-size: 15px; font-weight: 800; }
.sb-hifi-shop__logo small { display: block; font-size: 9px; color: var(--shop-muted); letter-spacing: .12em; margin-top: 2px; }
.sb-hifi-shop__nav { flex: 1; padding: 8px 6px; display: flex; flex-direction: column; }
.sb-hifi-shop__nav-item {
    display: flex; align-items: center; gap: 8px; padding: 9px 10px; border-radius: 8px;
    font-size: 12px; color: #475569; margin-bottom: 2px;
}
.sb-hifi-shop__nav-item.is-active { background: var(--shop-primary); color: #fff; font-weight: 600; }
.sb-hifi-shop__nav-icon { width: 18px; text-align: center; font-size: 13px; }
.sb-hifi-shop__nav-badge {
    margin-left: auto; font-size: 8px; font-weight: 700; padding: 2px 6px; border-radius: 999px;
    background: var(--shop-primary-soft); color: var(--shop-primary);
}
.sb-hifi-shop__nav-item.is-active .sb-hifi-shop__nav-badge { background: rgba(255,255,255,.25); color: #fff; }
.sb-hifi-shop__nav-bottom { margin-top: auto; padding: 8px 6px 0; border-top: 1px solid var(--shop-border); }
.sb-hifi-shop__nav-sm { font-size: 11px; padding: 7px 10px; color: #94a3b8; display: flex; align-items: center; gap: 8px; }
.sb-hifi-shop__points {
    margin: 8px 6px; padding: 12px; border-radius: 12px; background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1px solid #fde68a; display: flex; align-items: center; gap: 8px;
}
.sb-hifi-shop__points-coin { font-size: 24px; }
.sb-hifi-shop__points-label { font-size: 10px; color: #92400e; }
.sb-hifi-shop__points-value { font-size: 15px; font-weight: 800; color: #b45309; }
.sb-hifi-shop__points-link { font-size: 10px; color: #d97706; }

.sb-hifi-shop__center { flex: 1; min-width: 0; display: flex; flex-direction: column; background: var(--shop-bg); }
.sb-hifi-shop__topbar {
    display: flex; align-items: center; gap: 12px; padding: 10px 18px;
    background: #fff; border-bottom: 1px solid var(--shop-border);
}
.sb-hifi-shop__search {
    flex: 1; max-width: 520px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between;
    padding: 10px 16px; border: 1px solid var(--shop-border); border-radius: 999px; background: #fafbfc;
    color: #94a3b8; font-size: 12px; box-shadow: 0 1px 2px rgba(15,23,42,.04);
}
.sb-hifi-shop__top-actions { display: flex; align-items: center; gap: 10px; margin-left: auto; }
.sb-hifi-shop__cart {
    position: relative; width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--shop-border);
    display: flex; align-items: center; justify-content: center; background: #fff; font-size: 16px;
}
.sb-hifi-shop__cart-badge {
    position: absolute; top: -4px; right: -4px; min-width: 16px; height: 16px; border-radius: 999px;
    background: var(--shop-primary); color: #fff; font-size: 9px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
.sb-hifi-shop__profile { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; }
.sb-hifi-shop__avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #a78bfa, #6b46ff); }

.sb-hifi-shop__content { flex: 1; padding: 16px 18px; overflow: auto; display: flex; flex-direction: column; gap: 18px; }

.sb-hifi-shop__hero {
    display: grid; grid-template-columns: 1fr 1fr; gap: 0; border-radius: 16px; overflow: hidden;
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 50%, #e0e7ff 100%);
    border: 1px solid #ddd6fe; min-height: 200px; position: relative;
}
.sb-hifi-shop__hero-text { padding: 28px 24px; display: flex; flex-direction: column; justify-content: center; }
.sb-hifi-shop__hero-text h2 { margin: 0 0 8px; font-size: 22px; font-weight: 800; line-height: 1.3; letter-spacing: -.02em; }
.sb-hifi-shop__hero-text p { margin: 0 0 16px; font-size: 13px; color: var(--shop-muted); line-height: 1.5; }
.sb-hifi-shop__hero-btns { display: flex; gap: 8px; flex-wrap: wrap; }
.sb-hifi-shop__btn {
    padding: 10px 16px; border-radius: 10px; font-size: 12px; font-weight: 600; border: 1px solid var(--shop-border);
    background: #fff; color: #334155; cursor: default;
}
.sb-hifi-shop__btn--primary { background: var(--shop-primary); border-color: var(--shop-primary); color: #fff; }
.sb-hifi-shop__hero-visual {
    position: relative; display: flex; align-items: center; justify-content: center; padding: 20px;
    background: linear-gradient(135deg, rgba(255,255,255,.4), transparent);
}
.sb-hifi-shop__hero-products {
    display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 36px;
}
.sb-hifi-shop__hero-badge {
    position: absolute; bottom: 16px; right: 16px; padding: 8px 12px; border-radius: 10px;
    background: rgba(255,255,255,.9); font-size: 10px; font-weight: 600; color: #475569;
    border: 1px solid var(--shop-border); box-shadow: 0 2px 8px rgba(15,23,42,.08);
}
.sb-hifi-shop__hero-dots {
    position: absolute; bottom: 12px; left: 24px; display: flex; gap: 6px; align-items: center;
}
.sb-hifi-shop__hero-dot { width: 8px; height: 8px; border-radius: 50%; background: #c4b5fd; }
.sb-hifi-shop__hero-dot.is-active { background: var(--shop-primary); width: 20px; border-radius: 999px; }

.sb-hifi-shop__categories {
    display: flex; gap: 12px; overflow-x: auto; padding: 4px 0;
}
.sb-hifi-shop__cat {
    flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; gap: 6px; width: 72px;
}
.sb-hifi-shop__cat-icon {
    width: 52px; height: 52px; border-radius: 50%; background: #fff; border: 1px solid var(--shop-border);
    display: flex; align-items: center; justify-content: center; font-size: 20px;
    box-shadow: 0 1px 3px rgba(15,23,42,.06);
}
.sb-hifi-shop__cat span { font-size: 10px; color: #475569; text-align: center; line-height: 1.3; font-weight: 500; }

.sb-hifi-shop__section-head {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;
}
.sb-hifi-shop__section-head h3 { margin: 0; font-size: 15px; font-weight: 700; }
.sb-hifi-shop__section-link { font-size: 11px; color: var(--shop-primary); font-weight: 600; }

.sb-hifi-shop__scroll-row { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 4px; }
.sb-hifi-shop__size-card {
    flex: 0 0 140px; background: #fff; border: 1px solid var(--shop-border); border-radius: 12px;
    padding: 12px; box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.sb-hifi-shop__size-wire {
    height: 64px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px;
    margin-bottom: 8px; display: flex; align-items: center; justify-content: center;
    font-size: 9px; color: #94a3b8;
}
.sb-hifi-shop__size-name { font-size: 11px; font-weight: 700; color: #334155; margin-bottom: 2px; }
.sb-hifi-shop__size-dim { font-size: 10px; color: #94a3b8; }

.sb-hifi-shop__mat-card {
    flex: 0 0 160px; background: #fff; border: 1px solid var(--shop-border); border-radius: 12px;
    overflow: hidden; box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.sb-hifi-shop__mat-thumb {
    height: 90px; display: flex; align-items: center; justify-content: center; font-size: 32px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
}
.sb-hifi-shop__mat-meta { padding: 10px 12px; }
.sb-hifi-shop__mat-name { font-size: 11px; font-weight: 700; margin-bottom: 2px; }
.sb-hifi-shop__mat-desc { font-size: 10px; color: #94a3b8; line-height: 1.35; }

.sb-hifi-shop__aside {
    width: 220px; flex-shrink: 0; border-left: 1px solid var(--shop-border); background: #fff;
    padding: 14px 12px; display: flex; flex-direction: column; gap: 16px; overflow: auto;
}
.sb-hifi-shop__aside-title { font-size: 12px; font-weight: 700; margin: 0 0 8px; color: #334155; }
.sb-hifi-shop__use-list { list-style: none; margin: 0; padding: 0; }
.sb-hifi-shop__use-list li {
    display: flex; align-items: flex-start; gap: 8px; padding: 8px 0; border-bottom: 1px solid #f1f5f9;
    font-size: 11px; color: #475569; line-height: 1.4;
}
.sb-hifi-shop__use-list li:last-child { border-bottom: none; }
.sb-hifi-shop__use-icon { font-size: 16px; flex-shrink: 0; }
.sb-hifi-shop__help-list { list-style: none; margin: 0; padding: 0; }
.sb-hifi-shop__help-list li {
    display: flex; align-items: center; gap: 8px; padding: 7px 0; font-size: 11px; color: #64748b;
    border-bottom: 1px solid #f1f5f9;
}
.sb-hifi-shop__help-list li:last-child { border-bottom: none; }
.sb-hifi-shop__editor-promo {
    margin-top: auto; padding: 14px; border-radius: 14px;
    background: linear-gradient(135deg, #6b46ff 0%, #818cf8 100%); color: #fff;
    position: relative; overflow: hidden;
}
.sb-hifi-shop__editor-promo p { margin: 0 0 10px; font-size: 11px; line-height: 1.45; font-weight: 500; opacity: .95; }
.sb-hifi-shop__editor-promo .sb-hifi-shop__btn { background: #fff; color: var(--shop-primary); border: none; font-size: 11px; }
.sb-hifi-shop__editor-illus {
    position: absolute; right: -8px; bottom: -8px; font-size: 48px; opacity: .25;
}

.sb-hifi-shop .sb-wf-zone { position: relative; }
.sb-hifi-shop .sb-wf-zone-label { display: none; z-index: 5; }
.sb-wf-annotate .sb-hifi-shop .sb-wf-zone-label { display: inline-block; }
.sb-wf-annotate .sb-hifi-shop .sb-wf-zone { outline: 2px dashed rgba(107,70,255,.35); outline-offset: 1px; }
.sb-wf-annotate .sb-hifi-shop .sb-wf-zone:hover { outline-color: #6b46ff; }
