<?php
/**
 * 마이페이지 — 디자인 관리 하이파이 와이어프레임 바디 (01-06-04)
 */
?>
<div class="sb-hifi-mypage">
    <?php $mpActive = 'designs'; include __DIR__ . '/01-06-sub-shell.php'; ?>
        <div class="sb-hifi-mypage__card">
            <div class="sb-hifi-mypage__section-head">
                <h3>디자인 관리</h3>
                <span class="sb-hifi-mypage__section-link">＋ 새 디자인</span>
            </div>
            <div class="sb-hifi-mypage__filter-tabs sb-wf-zone" data-zone-id="M-02">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-02</span>
                <span class="sb-hifi-mypage__filter-tab is-active">전체</span>
                <span class="sb-hifi-mypage__filter-tab">작업중</span>
                <span class="sb-hifi-mypage__filter-tab">완료</span>
                <span class="sb-hifi-mypage__filter-tab">공유</span>
            </div>
            <div class="sb-hifi-mypage__design-grid sb-wf-zone" data-zone-id="M-03">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-03</span>
                <div class="sb-hifi-mypage__design-card">
                    <span class="sb-hifi-mypage__design-card-check"></span>
                    <div class="sb-hifi-mypage__design-thumb">🫒</div>
                    <div class="sb-hifi-mypage__design-meta">
                        <div class="sb-hifi-mypage__design-title">올리브 오일 라벨</div>
                        <div class="sb-hifi-mypage__design-date">수정 2분 전</div>
                        <span class="sb-hifi-mypage__tag sb-hifi-mypage__tag--edit">편집중</span>
                    </div>
                </div>
                <div class="sb-hifi-mypage__design-card">
                    <span class="sb-hifi-mypage__design-card-check"></span>
                    <div class="sb-hifi-mypage__design-thumb">🍯</div>
                    <div class="sb-hifi-mypage__design-meta">
                        <div class="sb-hifi-mypage__design-title">꿀 라벨</div>
                        <div class="sb-hifi-mypage__design-date">수정 1시간 전</div>
                        <span class="sb-hifi-mypage__tag sb-hifi-mypage__tag--done">완료</span>
                    </div>
                </div>
                <div class="sb-hifi-mypage__design-card">
                    <span class="sb-hifi-mypage__design-card-check"></span>
                    <div class="sb-hifi-mypage__design-thumb">🧴</div>
                    <div class="sb-hifi-mypage__design-meta">
                        <div class="sb-hifi-mypage__design-title">핸드워시 라벨</div>
                        <div class="sb-hifi-mypage__design-date">어제</div>
                        <span class="sb-hifi-mypage__tag sb-hifi-mypage__tag--done">완료</span>
                    </div>
                </div>
                <div class="sb-hifi-mypage__design-card">
                    <span class="sb-hifi-mypage__design-card-check"></span>
                    <div class="sb-hifi-mypage__design-thumb">☕</div>
                    <div class="sb-hifi-mypage__design-meta">
                        <div class="sb-hifi-mypage__design-title">커피 라벨</div>
                        <div class="sb-hifi-mypage__design-date">3일 전</div>
                        <span class="sb-hifi-mypage__tag sb-hifi-mypage__tag--edit">편집중</span>
                    </div>
                </div>
                <div class="sb-hifi-mypage__design-new"><span>＋</span>새 디자인</div>
            </div>
            <div class="sb-hifi-mypage__bulk-bar sb-wf-zone" data-zone-id="M-04">
                <span class="sb-wf-zone-label">M-04</span>
                <span class="left">2개 선택됨</span>
                <div class="sb-hifi-mypage__bulk-actions">
                    <span class="sb-hifi-mypage__btn">공유</span>
                    <span class="sb-hifi-mypage__btn">복제</span>
                    <span class="sb-hifi-mypage__btn sb-hifi-mypage__btn--danger">삭제</span>
                </div>
            </div>
        </div>
    </div><!-- /.sb-hifi-mypage__content -->
    </div><!-- /.sb-hifi-mypage__center -->
</div>
