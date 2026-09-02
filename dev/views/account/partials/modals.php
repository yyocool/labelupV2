<div id="profileModal" class="account-modal" hidden aria-hidden="true">
  <div class="account-modal-backdrop" data-close-modal></div>
  <div class="account-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle">
    <div class="account-modal-head">
      <h3 id="profileModalTitle">회원정보 수정</h3>
      <button type="button" class="account-modal-close" data-close-modal aria-label="닫기">&times;</button>
    </div>
    <div class="account-modal-body">
      <div id="profileAlert" class="account-alert"></div>
      <form id="profileForm">
        <div class="account-field">
          <label>이름</label>
          <input type="text" name="name" value="<?= e($dash['user']['name'] ?? '') ?>" required>
        </div>
        <div class="account-field">
          <label>연락처</label>
          <input type="text" name="phone" value="<?= e($dash['user']['phone'] ?? '') ?>" placeholder="010-0000-0000">
        </div>
        <div class="account-field">
          <label>회사/상호</label>
          <input type="text" name="company" value="<?= e($dash['user']['company'] ?? '') ?>">
        </div>
        <button class="account-btn account-btn--primary account-btn--block" type="submit">정보 저장</button>
      </form>
    </div>
  </div>
</div>

<div id="passwordModal" class="account-modal" hidden aria-hidden="true">
  <div class="account-modal-backdrop" data-close-modal></div>
  <div class="account-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="passwordModalTitle">
    <div class="account-modal-head">
      <h3 id="passwordModalTitle">비밀번호 변경</h3>
      <button type="button" class="account-modal-close" data-close-modal aria-label="닫기">&times;</button>
    </div>
    <div class="account-modal-body">
      <div id="passwordAlert" class="account-alert"></div>
      <form id="passwordForm">
        <div class="account-field">
          <label>현재 비밀번호</label>
          <input type="password" name="current_password" required autocomplete="current-password">
        </div>
        <div class="account-field">
          <label>새 비밀번호</label>
          <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
        </div>
        <button class="account-btn account-btn--primary account-btn--block" type="submit">비밀번호 변경</button>
      </form>
      <hr class="account-modal-divider">
      <div id="withdrawAlert" class="account-alert"></div>
      <form id="withdrawForm">
        <p class="account-withdraw-note">회원탈퇴 시 모든 데이터가 삭제되며 복구할 수 없습니다.</p>
        <div class="account-field">
          <label>비밀번호 확인</label>
          <input type="password" name="password" required autocomplete="current-password">
        </div>
        <button class="account-btn account-btn--danger account-btn--block" type="submit">회원탈퇴</button>
      </form>
    </div>
  </div>
</div>

<div id="inquiryModal" class="account-modal" hidden aria-hidden="true">
  <div class="account-modal-backdrop" data-close-modal></div>
  <div class="account-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="inquiryModalTitle">
    <div class="account-modal-head">
      <h3 id="inquiryModalTitle">1:1 문의</h3>
      <button type="button" class="account-modal-close" data-close-modal aria-label="닫기">&times;</button>
    </div>
    <div class="account-modal-body">
      <div id="inquiryAlert" class="account-alert"></div>
      <form id="inquiryForm">
        <div class="account-field">
          <label>이름</label>
          <input type="text" name="name" value="<?= e($dash['user']['name'] ?? '') ?>" required>
        </div>
        <div class="account-field">
          <label>이메일</label>
          <input type="email" name="email" value="<?= e($dash['user']['email'] ?? '') ?>" required>
        </div>
        <div class="account-field">
          <label>제목</label>
          <input type="text" name="subject" maxlength="200" required placeholder="문의 제목을 입력해 주세요">
        </div>
        <div class="account-field">
          <label>내용</label>
          <textarea name="content" rows="6" required placeholder="문의 내용을 자세히 적어 주세요"></textarea>
        </div>
        <button class="account-btn account-btn--primary account-btn--block" type="submit">문의 보내기</button>
      </form>
    </div>
  </div>
</div>
