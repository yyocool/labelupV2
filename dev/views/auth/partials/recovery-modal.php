<div class="recovery-modal" id="recoveryModal" hidden aria-hidden="true">
  <div class="recovery-modal-backdrop" data-close="recovery"></div>
  <div class="recovery-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="recoveryModalTitle">
    <div class="recovery-modal-head">
      <h3 id="recoveryModalTitle">아이디 · 비밀번호 찾기</h3>
      <button type="button" class="recovery-modal-close" data-close="recovery" aria-label="닫기">&times;</button>
    </div>

    <div class="recovery-tabs" role="tablist">
      <button type="button" class="recovery-tab is-active" data-tab="find-id" role="tab" aria-selected="true">아이디 찾기</button>
      <button type="button" class="recovery-tab" data-tab="find-password" role="tab" aria-selected="false">비밀번호 찾기</button>
    </div>

    <div class="recovery-modal-body">
      <div id="recoveryAlert" class="login-alert"></div>

      <div class="recovery-panel is-active" data-panel="find-id" role="tabpanel">
        <p class="recovery-desc">가입 시 등록한 <strong>이름</strong>과 <strong>휴대폰 번호</strong>로 아이디(이메일)를 확인할 수 있습니다.</p>
        <form id="findIdForm" class="recovery-form">
          <div class="login-field">
            <label for="findIdName">이름</label>
            <input id="findIdName" type="text" name="name" required autocomplete="name" placeholder="가입 시 입력한 이름">
          </div>
          <div class="login-field">
            <label for="findIdPhone">휴대폰 번호</label>
            <input id="findIdPhone" type="tel" name="phone" required autocomplete="tel" placeholder="01012345678">
          </div>
          <button class="login-submit" type="submit">아이디 찾기</button>
        </form>
        <div id="findIdResult" class="recovery-result" hidden>
          <p class="recovery-result-label">회원님의 아이디(이메일)</p>
          <p class="recovery-result-value" id="findIdEmail"></p>
          <a class="login-submit recovery-result-login" href="<?= url('login') ?>">로그인하기</a>
        </div>
      </div>

      <div class="recovery-panel" data-panel="find-password" role="tabpanel" hidden>
        <p class="recovery-desc">가입 시 등록한 <strong>이메일</strong>과 <strong>이름</strong>을 입력하면 비밀번호 재설정 링크를 발급합니다.</p>
        <form id="findPasswordForm" class="recovery-form">
          <div class="login-field">
            <label for="findPwEmail">이메일 (아이디)</label>
            <input id="findPwEmail" type="email" name="email" required autocomplete="email" placeholder="가입 시 사용한 이메일">
          </div>
          <div class="login-field">
            <label for="findPwName">이름</label>
            <input id="findPwName" type="text" name="name" required autocomplete="name" placeholder="가입 시 입력한 이름">
          </div>
          <button class="login-submit" type="submit">재설정 링크 발급</button>
        </form>
        <div id="findPasswordResult" class="recovery-result" hidden>
          <p class="recovery-result-msg" id="findPasswordMsg"></p>
          <a class="login-submit recovery-result-login" id="findPasswordLink" href="#" hidden>비밀번호 재설정하기</a>
        </div>
      </div>
    </div>
  </div>
</div>
