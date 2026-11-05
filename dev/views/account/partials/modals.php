<div id="profileModal" class="account-modal" hidden aria-hidden="true">
  <div class="account-modal-backdrop" data-close-modal></div>
  <div class="account-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle">
    <div class="account-modal-head">
      <h3 id="profileModalTitle">회원정보 수정</h3>
      <button type="button" class="account-modal-close" data-close-modal aria-label="닫기">&times;</button>
    </div>
    <div class="account-modal-body">
      <div id="profileAlert" class="account-alert"></div>
      <?php
        $pfUser = $dash['user'] ?? [];
        $pfSocial = $dash['social_providers'] ?? [];
        $pfSms = !empty($dash['sms_required']);
        $pfProviderLabels = ['naver' => '네이버', 'kakao' => '카카오', 'google' => '구글'];
      ?>
      <form id="profileForm" data-has-social="<?= $pfSocial ? '1' : '0' ?>" data-sms-required="<?= $pfSms ? '1' : '0' ?>">
        <dl class="account-profile-info">
          <div><dt>로그인 방식</dt><dd>이메일<?php foreach ($pfSocial as $p): ?> · <?= e($pfProviderLabels[$p] ?? $p) ?> 연결<?php endforeach; ?></dd></div>
          <div><dt>회원등급</dt><dd><?= e((string) ($dash['grade']['name'] ?? $pfUser['grade_name'] ?? '일반')) ?></dd></div>
          <div><dt>가입일</dt><dd><?= e(substr((string) ($pfUser['created_at'] ?? ''), 0, 10)) ?></dd></div>
        </dl>
        <div class="account-field">
          <label for="pfEmail">이메일 (아이디)</label>
          <input id="pfEmail" type="email" name="email" value="<?= e($pfUser['email'] ?? '') ?>" data-original="<?= e($pfUser['email'] ?? '') ?>" required autocomplete="email" maxlength="190">
          <p class="account-field-hint">로그인할 때 쓰는 이메일입니다.</p>
        </div>
        <div class="account-field" id="pfPasswordField" hidden>
          <label for="pfCurrentPassword">현재 비밀번호</label>
          <input id="pfCurrentPassword" type="password" name="current_password" autocomplete="current-password">
          <p class="account-field-hint">이메일을 바꾸려면 현재 비밀번호를 입력해 주세요.</p>
        </div>
        <div class="account-field">
          <label for="pfName">이름</label>
          <input id="pfName" type="text" name="name" value="<?= e($pfUser['name'] ?? '') ?>" required maxlength="100" autocomplete="name">
        </div>
        <div class="account-field">
          <label for="pfPhone">휴대폰 번호</label>
          <div class="account-phone-row">
            <input id="pfPhone" type="tel" name="phone" value="<?= e($pfUser['phone'] ?? '') ?>" data-original="<?= e($pfUser['phone'] ?? '') ?>" inputmode="tel" autocomplete="tel" maxlength="30" placeholder="010-0000-0000">
            <?php if ($pfSms): ?>
            <button type="button" class="account-btn account-btn--outline" id="pfSendSms" hidden>인증번호 받기</button>
            <?php endif; ?>
          </div>
          <p class="account-field-hint" id="pfPhoneHint"><?= $pfSms ? '번호를 바꾸면 문자 인증이 필요합니다.' : '' ?></p>
        </div>
        <?php if ($pfSms): ?>
        <div class="account-field" id="pfSmsField" hidden>
          <label for="pfSmsCode">인증번호</label>
          <div class="account-phone-row">
            <input id="pfSmsCode" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="6자리 숫자">
            <button type="button" class="account-btn account-btn--outline" id="pfVerifySms">확인</button>
          </div>
          <p class="account-field-hint" id="pfSmsHint"></p>
          <input type="hidden" name="phone_verify_token" id="pfPhoneToken" value="">
        </div>
        <?php endif; ?>
        <div class="account-field">
          <label for="pfCompany">회사/상호</label>
          <input id="pfCompany" type="text" name="company" value="<?= e($pfUser['company'] ?? '') ?>" maxlength="150" autocomplete="organization">
        </div>
        <p class="account-field-hint account-profile-note">비밀번호 변경·회원탈퇴는 <button type="button" class="account-link-btn" data-switch-modal="passwordModal">보안 설정</button>에서 할 수 있습니다.</p>
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
      <p class="account-inquiry-switch"><button type="button" class="account-link-btn" data-switch-modal="inquiriesModal">내 문의 내역 보기</button></p>
    </div>
  </div>
</div>

<div id="inquiriesModal" class="account-modal account-history-modal" hidden aria-hidden="true">
  <div class="account-modal-backdrop" data-close-modal></div>
  <div class="account-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="inquiriesModalTitle">
    <div class="account-modal-head">
      <h3 id="inquiriesModalTitle">내 문의 내역</h3>
      <button type="button" class="account-modal-close" data-close-modal aria-label="닫기">&times;</button>
    </div>
    <div class="account-modal-body">
      <ul class="account-inquiry-list js-inquiry-list"></ul>
      <p class="account-empty js-inquiry-empty" hidden></p>
    </div>
    <div class="account-history-foot"><button type="button" class="account-btn account-btn--primary account-btn--sm" data-switch-modal="inquiryModal">새 문의 작성</button></div>
  </div>
</div>

<div id="notifPrefsModal" class="account-modal" hidden aria-hidden="true">
  <div class="account-modal-backdrop" data-close-modal></div>
  <div class="account-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="notifPrefsModalTitle">
    <div class="account-modal-head">
      <h3 id="notifPrefsModalTitle">알림 설정</h3>
      <button type="button" class="account-modal-close" data-close-modal aria-label="닫기">&times;</button>
    </div>
    <div class="account-modal-body">
      <div id="notifPrefsAlert" class="account-alert"></div>
      <form id="notifPrefsForm">
        <div class="account-notif-prefs" id="notifPrefsFields"></div>
        <div class="account-field">
          <label>잉크 부족 경고 기준 (C)</label>
          <input type="number" name="low_credit_threshold" id="notifLowThreshold" min="0" max="100000" step="10" value="100">
        </div>
        <button class="account-btn account-btn--primary account-btn--block" type="submit">설정 저장</button>
      </form>
    </div>
  </div>
</div>

<div id="historyModal" class="account-modal account-history-modal" hidden aria-hidden="true">
  <div class="account-modal-backdrop" data-close-modal></div>
  <div class="account-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="historyModalTitle">
    <div class="account-modal-head">
      <h3 id="historyModalTitle">잉크 내역</h3>
      <button type="button" class="account-modal-close" data-close-modal aria-label="닫기">&times;</button>
    </div>
    <div class="account-history-summary js-history-summary"></div>
    <div class="account-modal-body">
      <ul class="account-history-list js-history-list"></ul>
      <p class="account-empty js-history-empty" hidden></p>
      <button type="button" class="account-btn account-btn--outline account-btn--block account-history-more js-history-more" hidden>더보기</button>
    </div>
    <div class="account-history-foot js-history-total"></div>
  </div>
</div>
