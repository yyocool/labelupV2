<?php
/**
 * 마이페이지 — 내 정보 하이파이 와이어프레임 바디 (01-06-01)
 */
?>
<div class="sb-hifi-mypage">
    <?php $mpActive = 'profile'; include __DIR__ . '/01-06-sub-shell.php'; ?>
        <div class="sb-hifi-mypage__card sb-hifi-mypage__form-card sb-wf-zone" data-zone-id="M-02">
            <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-02</span>
            <h3>기본 정보</h3>
            <p class="sb-hifi-mypage__form-desc">회원가입 시 등록한 기본 정보를 확인하고 수정할 수 있습니다.</p>
            <div class="sb-hifi-mypage__form-grid">
                <div class="sb-hifi-mypage__form-row">
                    <label>이름</label>
                    <input type="text" value="김라벨" readonly>
                </div>
                <div class="sb-hifi-mypage__form-row">
                    <label>이메일</label>
                    <input type="email" value="label@example.com" disabled>
                </div>
                <div class="sb-hifi-mypage__form-row">
                    <label>연락처</label>
                    <input type="text" value="010-1234-5678" readonly>
                </div>
                <div class="sb-hifi-mypage__form-row">
                    <label>회사명</label>
                    <input type="text" value="라벨업 스튜디오" readonly>
                </div>
            </div>
            <div class="sb-hifi-mypage__form-actions">
                <span class="sb-hifi-mypage__btn">취소</span>
                <span class="sb-hifi-mypage__btn sb-hifi-mypage__btn--primary">저장</span>
            </div>
        </div>

        <div class="sb-hifi-mypage__card sb-hifi-mypage__form-card sb-wf-zone" data-zone-id="M-03">
            <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-03</span>
            <h3>비밀번호 변경</h3>
            <p class="sb-hifi-mypage__form-desc">보안을 위해 8자 이상, 영문·숫자·특수문자를 조합해주세요.</p>
            <div class="sb-hifi-mypage__form-grid">
                <div class="sb-hifi-mypage__form-row sb-hifi-mypage__form-row--full">
                    <label>현재 비밀번호</label>
                    <input type="password" placeholder="현재 비밀번호 입력">
                </div>
                <div class="sb-hifi-mypage__form-row">
                    <label>새 비밀번호</label>
                    <input type="password" placeholder="새 비밀번호 입력">
                </div>
                <div class="sb-hifi-mypage__form-row">
                    <label>새 비밀번호 확인</label>
                    <input type="password" placeholder="새 비밀번호 재입력">
                </div>
            </div>
            <div class="sb-hifi-mypage__form-actions">
                <span class="sb-hifi-mypage__btn sb-hifi-mypage__btn--primary">비밀번호 변경</span>
            </div>
        </div>

        <div class="sb-hifi-mypage__card sb-hifi-mypage__withdraw-card sb-wf-zone" data-zone-id="M-04">
            <span class="sb-wf-zone-label">M-04</span>
            <h3>⚠ 회원 탈퇴</h3>
            <p>탈퇴 시 보유 포인트·쿠폰·저장된 디자인이 모두 삭제되며 복구할 수 없습니다. 진행 중인 주문이 있다면 배송 완료 후 탈퇴해주세요.</p>
            <span class="sb-hifi-mypage__btn sb-hifi-mypage__btn--danger">회원 탈퇴 신청</span>
        </div>
    </div><!-- /.sb-hifi-mypage__content -->
    </div><!-- /.sb-hifi-mypage__center -->
</div>
