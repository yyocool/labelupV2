<?php
/**
 * 서비스 소개 (01-04-02) 하이파이 스타일
 */
?>
.sb-hifi-about {
    display: flex;
    height: 100%;
    min-height: 720px;
    background: #f1f5f9;
    font-family: 'Pretendard', 'Noto Sans KR', sans-serif;
    color: #0f172a;
}
.sb-hifi-about__sidebar {
    display: flex;
    flex-shrink: 0;
    background: #0f172a;
    color: #e2e8f0;
}
.sb-hifi-about__icon-rail {
    width: 52px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 12px 0;
    border-right: 1px solid #1e293b;
    position: relative;
}
.sb-hifi-about__icon-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #94a3b8;
}
.sb-hifi-about__icon-btn.is-active { background: #1d4ed8; color: #fff; }
.sb-hifi-about__nav-panel {
    width: 200px;
    padding: 16px 12px;
    position: relative;
    background: #111827;
    display: flex;
    flex-direction: column;
}
.sb-hifi-about__logo { margin-bottom: 16px; }
.sb-hifi-about__logo strong { display: block; font-size: 15px; }
.sb-hifi-about__logo small { color: #64748b; font-size: 10px; letter-spacing: .08em; }
.sb-hifi-about__nav-section {
    font-size: 10px;
    color: #64748b;
    letter-spacing: .06em;
    margin: 10px 8px 6px;
    text-transform: uppercase;
}
.sb-hifi-about__nav-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 2px;
}
.sb-hifi-about__nav-item.is-active { background: #1e293b; color: #fff; }
.sb-hifi-about__nav-item.is-child { padding-left: 28px; font-size: 12px; }
.sb-hifi-about__main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: #fff;
    overflow: hidden;
}
.sb-hifi-about__top {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid #e2e8f0;
    position: relative;
    flex-shrink: 0;
}
.sb-hifi-about__crumb {
    font-size: 12px;
    color: #64748b;
}
.sb-hifi-about__crumb strong { color: #0f172a; }
.sb-hifi-about__top-actions {
    margin-left: auto;
    display: flex;
    gap: 8px;
    align-items: center;
}
.sb-hifi-about__btn {
    display: inline-flex;
    align-items: center;
    height: 32px;
    padding: 0 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #334155;
}
.sb-hifi-about__btn--primary {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
}
.sb-hifi-about__scroll {
    flex: 1;
    overflow: auto;
    padding: 0 0 32px;
}
.sb-hifi-about__hero {
    position: relative;
    padding: 36px 28px 28px;
    background:
        linear-gradient(135deg, #eff6ff 0%, #f8fafc 45%, #ecfeff 100%);
    border-bottom: 1px solid #e2e8f0;
}
.sb-hifi-about__eyebrow {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .1em;
    color: #2563eb;
    margin-bottom: 10px;
}
.sb-hifi-about__hero h1 {
    margin: 0 0 10px;
    font-size: 28px;
    line-height: 1.3;
    font-weight: 800;
    letter-spacing: -0.02em;
}
.sb-hifi-about__hero p {
    margin: 0 0 18px;
    font-size: 14px;
    color: #475569;
    max-width: 520px;
    line-height: 1.6;
}
.sb-hifi-about__hero-cta { display: flex; gap: 8px; flex-wrap: wrap; }
.sb-hifi-about__btn--lg {
    height: 40px;
    padding: 0 18px;
    font-size: 13px;
}
.sb-hifi-about__anchors {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    padding: 12px 28px;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
    position: relative;
    position: sticky;
    top: 0;
    z-index: 2;
}
.sb-hifi-about__anchor {
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
.sb-hifi-about__anchor.is-active {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1d4ed8;
}
.sb-hifi-about__section {
    padding: 28px 28px 8px;
    position: relative;
}
.sb-hifi-about__section h2 {
    margin: 0 0 6px;
    font-size: 18px;
    font-weight: 800;
}
.sb-hifi-about__section-lead {
    margin: 0 0 16px;
    font-size: 13px;
    color: #64748b;
}
.sb-hifi-about__feat-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 10px;
}
.sb-hifi-about__feat {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 12px;
    background: #fff;
    min-height: 132px;
}
.sb-hifi-about__feat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    margin-bottom: 10px;
    background: #eff6ff;
    color: #1d4ed8;
}
.sb-hifi-about__feat strong {
    display: block;
    font-size: 13px;
    margin-bottom: 4px;
}
.sb-hifi-about__feat p {
    margin: 0;
    font-size: 11px;
    color: #64748b;
    line-height: 1.45;
}
.sb-hifi-about__steps {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}
.sb-hifi-about__step {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
    background: #f8fafc;
    position: relative;
}
.sb-hifi-about__step-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #2563eb;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
}
.sb-hifi-about__step strong { display: block; font-size: 13px; margin-bottom: 4px; }
.sb-hifi-about__step p { margin: 0; font-size: 11px; color: #64748b; line-height: 1.45; }
.sb-hifi-about__plans {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    max-width: 720px;
}
.sb-hifi-about__plan {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 18px 16px;
    background: #fff;
}
.sb-hifi-about__plan--pro {
    border-color: #93c5fd;
    background: linear-gradient(180deg, #eff6ff 0%, #fff 40%);
    box-shadow: 0 0 0 1px rgba(37, 99, 235, .08);
}
.sb-hifi-about__plan-name {
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 4px;
}
.sb-hifi-about__plan-price {
    font-size: 22px;
    font-weight: 800;
    margin-bottom: 10px;
}
.sb-hifi-about__plan-price small {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
}
.sb-hifi-about__plan ul {
    margin: 0 0 14px;
    padding: 0 0 0 16px;
    font-size: 12px;
    color: #475569;
    line-height: 1.7;
}
.sb-hifi-about__biz {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 20px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: #0f172a;
    color: #e2e8f0;
}
.sb-hifi-about__biz h3 { margin: 0 0 6px; font-size: 16px; color: #fff; }
.sb-hifi-about__biz p { margin: 0; font-size: 12px; color: #94a3b8; max-width: 420px; line-height: 1.5; }
.sb-hifi-about__biz-actions { display: flex; gap: 8px; flex-shrink: 0; }
.sb-hifi-about__btn--ghost {
    background: transparent;
    border-color: #475569;
    color: #e2e8f0;
}
.sb-hifi-about__btn--light {
    background: #fff;
    border-color: #fff;
    color: #0f172a;
}
.sb-hifi-about__footer-cta {
    margin: 20px 28px 0;
    padding: 22px;
    border-radius: 14px;
    border: 1px dashed #cbd5e1;
    background: #f8fafc;
    text-align: center;
    position: relative;
}
.sb-hifi-about__footer-cta strong {
    display: block;
    font-size: 15px;
    margin-bottom: 6px;
}
.sb-hifi-about__footer-cta p {
    margin: 0 0 12px;
    font-size: 12px;
    color: #64748b;
}
.sb-hifi-about__footer-cta .sb-hifi-about__hero-cta {
    justify-content: center;
}

@media (max-width: 1100px) {
    .sb-hifi-about__feat-grid { grid-template-columns: repeat(3, 1fr); }
    .sb-hifi-about__steps { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 800px) {
    .sb-hifi-about__nav-panel { display: none; }
    .sb-hifi-about__feat-grid,
    .sb-hifi-about__steps,
    .sb-hifi-about__plans { grid-template-columns: 1fr; }
    .sb-hifi-about__biz { flex-direction: column; align-items: flex-start; }
}
