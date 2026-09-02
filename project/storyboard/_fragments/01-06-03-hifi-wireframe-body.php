<?php
/**
 * 마이페이지 — 결제·구독 하이파이 와이어프레임 바디 (01-06-03)
 */
?>
<div class="sb-hifi-mypage">
    <?php $mpActive = 'billing'; include __DIR__ . '/01-06-sub-shell.php'; ?>
        <div class="sb-hifi-mypage__card sb-hifi-mypage__form-card sb-wf-zone" data-zone-id="M-02">
            <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-02</span>
            <div class="sb-hifi-mypage__section-head" style="padding:0 0 12px">
                <h3>현재 이용 플랜 <span class="sb-hifi-mypage__plan-badge">Pro Plan</span></h3>
                <span class="sb-hifi-mypage__section-link">플랜 변경 ›</span>
            </div>
            <div class="sb-hifi-mypage__plan-box">
                <div class="sb-hifi-mypage__plan-row"><strong>이용 기간</strong><span>2025.06.01 ~ 2026.05.31</span></div>
                <div class="sb-hifi-mypage__plan-row"><span>잔여 364일</span><span>월 200건 중 68건 사용</span></div>
                <div class="sb-hifi-mypage__progress"><div class="sb-hifi-mypage__progress-bar"></div></div>
            </div>
            <div class="sb-hifi-mypage__form-actions" style="margin-top:0;border-top:0;padding-top:0">
                <span class="sb-hifi-mypage__btn">플랜 비교</span>
                <span class="sb-hifi-mypage__btn sb-hifi-mypage__btn--primary">업그레이드</span>
            </div>
        </div>

        <div class="sb-hifi-mypage__card sb-hifi-mypage__mini-panel sb-wf-zone" data-zone-id="M-03">
            <span class="sb-wf-zone-label">M-03</span>
            <h4>결제 수단</h4>
            <div class="sb-hifi-mypage__pay-method">
                <span class="sb-hifi-mypage__pay-icon">VISA</span>
                <div class="sb-hifi-mypage__pay-info"><strong>신한카드</strong>**** **** **** 4521 · 매월 1일 자동결제</div>
                <span class="sb-hifi-mypage__btn">변경</span>
            </div>
            <span class="sb-hifi-mypage__add-btn">＋ 결제 수단 추가</span>
        </div>

        <div class="sb-hifi-mypage__card sb-hifi-mypage__mini-panel sb-wf-zone" data-zone-id="M-04">
            <span class="sb-wf-zone-label">M-04</span>
            <h4>결제 내역</h4>
            <div class="sb-hifi-mypage__invoice-item"><span>2025.06.01 · Pro Plan 월 구독</span><span class="sb-hifi-mypage__invoice-amount">29,000원</span></div>
            <div class="sb-hifi-mypage__invoice-item"><span>2025.05.01 · Pro Plan 월 구독</span><span class="sb-hifi-mypage__invoice-amount">29,000원</span></div>
            <div class="sb-hifi-mypage__invoice-item"><span>2025.04.01 · Pro Plan 월 구독</span><span class="sb-hifi-mypage__invoice-amount">29,000원</span></div>
            <span class="sb-hifi-mypage__invoice-link">영수증 전체 보기 ›</span>
        </div>

        <div class="sb-hifi-mypage__upgrade-banner sb-wf-zone" data-zone-id="M-05">
            <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-05</span>
            <div>
                <p>더 많은 라벨을 제작하고 싶으신가요?</p>
                <span class="desc">Business 플랜으로 업그레이드하면 월 무제한 제작·전담 매니저가 제공됩니다.</span>
            </div>
            <span class="sb-hifi-mypage__btn sb-hifi-mypage__btn--primary">Business로 업그레이드</span>
        </div>
    </div><!-- /.sb-hifi-mypage__content -->
    </div><!-- /.sb-hifi-mypage__center -->
</div>
