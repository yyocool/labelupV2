<?php
/** 전체화면 와이어프레임 — 뷰포트 세로 100% 채움 (모든 스토리보드 페이지 공통) */
?>
/* ── 전체화면: 와이어프레임 세로 꽉 채움 ── */
.sb-wf-wrap:fullscreen .sb-wf-viewport {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.sb-wf-wrap:fullscreen .sb-wf-viewport > .sb-wf {
    flex: 1;
    min-height: 0;
    height: 100%;
    align-items: stretch;
}

/* hi-fi 래퍼 */
.sb-wf-wrap:fullscreen .sb-wf--hifi {
    display: flex;
    flex-direction: column;
    min-height: 0;
    height: 100%;
}

.sb-wf-wrap:fullscreen .sb-wf--hifi.sb-wf--editor {
    display: flex;
    flex-direction: column;
}

/* hi-fi 루트 레이아웃 */
.sb-wf-wrap:fullscreen .sb-hifi-home,
.sb-wf-wrap:fullscreen .sb-hifi-shop,
.sb-wf-wrap:fullscreen .sb-hifi-mypage,
.sb-wf-wrap:fullscreen .sb-hifi-simple,
.sb-wf-wrap:fullscreen .sb-hifi-editor {
    flex: 1;
    min-height: 0;
    height: 100%;
}

/* hi-fi 사이드바·패널 세로 늘림 */
.sb-wf-wrap:fullscreen .sb-hifi-home__sidebar,
.sb-wf-wrap:fullscreen .sb-hifi-shop__sidebar,
.sb-wf-wrap:fullscreen .sb-hifi-mypage__sidebar,
.sb-wf-wrap:fullscreen .sb-hifi-simple__sidebar,
.sb-wf-wrap:fullscreen .sb-hifi-shop__aside,
.sb-wf-wrap:fullscreen .sb-hifi-home__nav-panel,
.sb-wf-wrap:fullscreen .sb-hifi-simple__nav-panel {
    align-self: stretch;
    min-height: 100%;
}

.sb-wf-wrap:fullscreen .sb-hifi-home__icon-rail,
.sb-wf-wrap:fullscreen .sb-hifi-simple__icon-rail {
    align-self: stretch;
    min-height: 100%;
}

/* hi-fi 메인 컬럼 */
.sb-wf-wrap:fullscreen .sb-hifi-home__main,
.sb-wf-wrap:fullscreen .sb-hifi-shop__center,
.sb-wf-wrap:fullscreen .sb-hifi-mypage__center,
.sb-wf-wrap:fullscreen .sb-hifi-simple__main {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

.sb-wf-wrap:fullscreen .sb-hifi-home__content,
.sb-wf-wrap:fullscreen .sb-hifi-shop__content,
.sb-wf-wrap:fullscreen .sb-hifi-mypage__content {
    flex: 1;
    min-height: 0;
    overflow: auto;
}

.sb-wf-wrap:fullscreen .sb-hifi-simple__body {
    flex: 1;
    min-height: 0;
}

.sb-wf-wrap:fullscreen .sb-hifi-simple__chat,
.sb-wf-wrap:fullscreen .sb-hifi-simple__aside {
    min-height: 0;
}

.sb-wf-wrap:fullscreen .sb-hifi-editor__body {
    flex: 1;
    min-height: 0;
    width: 100%;
}

.sb-wf-wrap:fullscreen .sb-hifi-editor__workspace {
    flex: 1 1 0;
    width: 0;
    min-width: 0;
    min-height: 0;
}

.sb-wf-wrap:fullscreen .sb-hifi-editor .sb-hifi-editor__props.sb-wf-zone,
.sb-wf-wrap:fullscreen .sb-hifi-editor__props {
    position: absolute !important;
    flex: none !important;
    width: 280px !important;
}

.sb-wf-wrap:fullscreen .sb-hifi-editor__canvas-wrap {
    flex: 1;
    width: 100%;
    min-height: 0;
}

/* 레거시 와이어프레임 (01, 01-01~03, 01-04-01-01) */
.sb-wf-wrap:fullscreen .sb-wf-sidebar {
    align-self: stretch;
}

.sb-wf-wrap:fullscreen .sb-wf-nav-panel,
.sb-wf-wrap:fullscreen .sb-wf--signup .sb-wf-nav-panel,
.sb-wf-wrap:fullscreen .sb-wf--login .sb-wf-nav-panel,
.sb-wf-wrap:fullscreen .sb-wf--find .sb-wf-nav-panel,
.sb-wf-wrap:fullscreen .sb-wf--normal .sb-wf-nav-panel {
    min-height: 0;
    height: 100%;
    align-self: stretch;
}

.sb-wf-wrap:fullscreen .sb-wf-main,
.sb-wf-wrap:fullscreen .sb-wf-login-main,
.sb-wf-wrap:fullscreen .sb-wf-signup-main,
.sb-wf-wrap:fullscreen .sb-wf-find-main,
.sb-wf-wrap:fullscreen .sb-wf-normal-main {
    flex: 1;
    min-height: 0;
    min-width: 0;
}

.sb-wf-wrap:fullscreen .sb-wf-main,
.sb-wf-wrap:fullscreen .sb-wf-signup-main,
.sb-wf-wrap:fullscreen .sb-wf-find-main,
.sb-wf-wrap:fullscreen .sb-wf-normal-main {
    display: flex;
    flex-direction: column;
}

.sb-wf-wrap:fullscreen .sb-wf-login-main {
    display: flex;
    flex-direction: row;
    align-items: stretch;
    min-height: 0;
}

.sb-wf-wrap:fullscreen .sb-wf-login-promo,
.sb-wf-wrap:fullscreen .sb-wf-login-form-col {
    min-height: 0;
}

.sb-wf-wrap:fullscreen .sb-wf-signup-body,
.sb-wf-wrap:fullscreen .sb-wf-find-body,
.sb-wf-wrap:fullscreen .sb-wf-normal-body {
    flex: 1;
    min-height: 0;
}

.sb-wf-wrap:fullscreen .sb-wf-signup-body {
    align-items: stretch;
}

.sb-wf-wrap:fullscreen .sb-wf-body {
    flex: 1;
    min-height: 0;
    overflow: auto;
}

.sb-wf-wrap:fullscreen .sb-wf-find-body,
.sb-wf-wrap:fullscreen .sb-wf-normal-body {
    overflow: auto;
}

.sb-wf-wrap:fullscreen .sb-wf-signup-promo,
.sb-wf-wrap:fullscreen .sb-wf-signup-form-col,
.sb-wf-wrap:fullscreen .sb-wf-signup-benefits {
    min-height: 0;
    overflow: auto;
}

/* Backoffice(02) 관리자 목업 */
.sb-wf-wrap:fullscreen .sb-wf--admin {
    display: flex;
    flex-direction: column;
    min-height: 100%;
    height: 100%;
}

.sb-wf-wrap:fullscreen .sb-wf--admin .sb-adm {
    flex: 1;
    min-height: 0;
    height: 100%;
    border: none;
    border-radius: 0;
    box-shadow: none;
}

.sb-wf-wrap:fullscreen .sb-wf--admin .sb-adm-main {
    min-height: 0;
}

.sb-wf-wrap:fullscreen .sb-wf--admin .sb-adm-body {
    min-height: 0;
    overflow: auto;
}

.sb-wf-wrap:fullscreen .sb-wf--admin .sb-adm-lnb {
    align-self: stretch;
    min-height: 100%;
}
