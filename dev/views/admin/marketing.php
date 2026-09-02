<?php
$m = $marketing ?? [];
$files = $files ?? [];
$v = static fn (string $key, string $default = ''): string => (string) ($m[$key] ?? $default);
?>
<div class="admin-head">
  <div>
    <h1>광고 스크립트</h1>
    <p>구글·네이버·메타 등 광고·분석 태그와 검색엔진 인증 파일, 공통 삽입 코드를 관리합니다.</p>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>

<div class="admin-legal-tabs" id="mktTabs">
  <button type="button" class="admin-legal-tab is-active" data-tab="google">구글</button>
  <button type="button" class="admin-legal-tab" data-tab="naver">네이버</button>
  <button type="button" class="admin-legal-tab" data-tab="meta">메타 · 카카오 · 빙</button>
  <button type="button" class="admin-legal-tab" data-tab="custom">공통 스크립트</button>
  <button type="button" class="admin-legal-tab" data-tab="files">인증 · 광고 파일</button>
</div>

<form id="mktForm" class="admin-card admin-seo-form">
  <label class="admin-field admin-check" style="margin-bottom:16px">
    <input type="checkbox" name="enabled" value="1"<?= $v('enabled', '1') === '1' ? ' checked' : '' ?>>
    <span>사이트에 광고·추적 코드 출력</span>
  </label>

  <section class="admin-legal-panel is-active" data-panel="google">
    <div class="admin-form-grid">
      <label class="admin-field"><span>Google 태그 관리자 (GTM-…)</span>
        <input class="admin-input" name="gtm_id" value="<?= e($v('gtm_id')) ?>" placeholder="GTM-XXXX">
      </label>
      <label class="admin-field"><span>GA4 측정 ID (G-…)</span>
        <input class="admin-input" name="ga4_id" value="<?= e($v('ga4_id')) ?>" placeholder="G-XXXXXXXX">
        <small>GTM에 이미 GA4가 있으면 비워 두세요.</small>
      </label>
      <label class="admin-field"><span>Google Ads ID (AW-…)</span>
        <input class="admin-input" name="google_ads_id" value="<?= e($v('google_ads_id')) ?>" placeholder="AW-XXXX">
      </label>
      <label class="admin-field"><span>Search Console 인증 코드</span>
        <input class="admin-input" name="google_site_verification" value="<?= e($v('google_site_verification')) ?>">
        <small>메타 태그의 content 값만 입력</small>
      </label>
    </div>
  </section>

  <section class="admin-legal-panel" data-panel="naver">
    <div class="admin-form-grid">
      <label class="admin-field"><span>네이버 서치어드바이저 인증</span>
        <input class="admin-input" name="naver_site_verification" value="<?= e($v('naver_site_verification')) ?>">
        <small>naver-site-verification content 값</small>
      </label>
      <label class="admin-field"><span>네이버 애널리틱스 ID</span>
        <input class="admin-input" name="naver_analytics_id" value="<?= e($v('naver_analytics_id')) ?>">
      </label>
      <label class="admin-field"><span>네이버 검색광고 / WCS ID</span>
        <input class="admin-input" name="naver_wcs_id" value="<?= e($v('naver_wcs_id')) ?>">
        <small>애널리틱스 ID가 있으면 그쪽을 우선 사용합니다.</small>
      </label>
    </div>
  </section>

  <section class="admin-legal-panel" data-panel="meta">
    <div class="admin-form-grid">
      <label class="admin-field"><span>메타(페이스북) 픽셀 ID</span>
        <input class="admin-input" name="meta_pixel_id" value="<?= e($v('meta_pixel_id')) ?>" placeholder="숫자 ID">
      </label>
      <label class="admin-field"><span>카카오 픽셀 ID</span>
        <input class="admin-input" name="kakao_pixel_id" value="<?= e($v('kakao_pixel_id')) ?>">
      </label>
      <label class="admin-field"><span>Bing Webmaster 인증</span>
        <input class="admin-input" name="bing_verification" value="<?= e($v('bing_verification')) ?>">
      </label>
    </div>
  </section>

  <section class="admin-legal-panel" data-panel="custom">
    <p class="admin-meta-line">HTML/JS를 그대로 출력합니다. 신뢰할 수 있는 광고·분석 코드만 넣어 주세요.</p>
    <div class="admin-form-grid">
      <label class="admin-field admin-field--full"><span>&lt;head&gt; 삽입</span>
        <textarea class="admin-input admin-input--code" name="custom_head" rows="6"><?= e($v('custom_head')) ?></textarea>
      </label>
      <label class="admin-field admin-field--full"><span>&lt;body&gt; 시작 직후</span>
        <textarea class="admin-input admin-input--code" name="custom_body_start" rows="5"><?= e($v('custom_body_start')) ?></textarea>
      </label>
      <label class="admin-field admin-field--full"><span>&lt;/body&gt; 직전</span>
        <textarea class="admin-input admin-input--code" name="custom_body_end" rows="5"><?= e($v('custom_body_end')) ?></textarea>
      </label>
    </div>
  </section>

  <div class="admin-head-actions" data-mkt-actions>
    <button type="submit" class="admin-btn admin-btn--primary">광고 스크립트 저장</button>
  </div>
</form>

<section class="admin-legal-panel" data-panel="files" id="mktFilesPanel">
  <div class="admin-card" style="margin-bottom:16px">
    <h2>ads.txt / app-ads.txt</h2>
    <p class="admin-meta-line">애드센스·애드매니저 등에서 요구하는 게시자 인증 파일입니다. 비우면 404를 반환합니다.</p>
    <form id="mktAdsForm" class="admin-form-grid">
      <label class="admin-field admin-field--full"><span>ads.txt <a href="<?= url('ads.txt') ?>" target="_blank" rel="noopener">/ads.txt</a></span>
        <textarea class="admin-input admin-input--code" name="ads_txt" rows="5"><?= e($v('ads_txt')) ?></textarea>
      </label>
      <label class="admin-field admin-field--full"><span>app-ads.txt <a href="<?= url('app-ads.txt') ?>" target="_blank" rel="noopener">/app-ads.txt</a></span>
        <textarea class="admin-input admin-input--code" name="app_ads_txt" rows="4"><?= e($v('app_ads_txt')) ?></textarea>
      </label>
      <div class="admin-head-actions">
        <button type="submit" class="admin-btn admin-btn--primary">광고 파일 저장</button>
      </div>
    </form>
  </div>

  <div class="admin-card">
    <h2>검색엔진 인증 HTML</h2>
    <p class="admin-meta-line">구글·네이버에서 내려주는 <code>googleXXXX.html</code>, <code>naverXXXX.html</code> 내용을 사이트 루트에 그대로 제공합니다.</p>
    <form id="mktFileForm" class="admin-form-grid">
      <label class="admin-field"><span>파일명</span>
        <input class="admin-input" name="filename" placeholder="google123456.html" required>
      </label>
      <label class="admin-field admin-field--full"><span>파일 내용</span>
        <textarea class="admin-input admin-input--code" name="content" rows="4" required placeholder="google-site-verification: ..."></textarea>
      </label>
      <div class="admin-head-actions">
        <button type="submit" class="admin-btn admin-btn--primary">파일 등록</button>
      </div>
    </form>
    <div class="admin-table-wrap" style="margin-top:16px">
      <table class="admin-table">
        <thead><tr><th>파일</th><th>종류</th><th></th></tr></thead>
        <tbody id="mktFileRows">
          <?php if ($files === []): ?>
          <tr><td colspan="3" class="empty">등록된 인증 파일이 없습니다.</td></tr>
          <?php else: ?>
          <?php foreach ($files as $file): ?>
          <tr>
            <td><a href="<?= url(ltrim((string) $file['filename'], '/')) ?>" target="_blank" rel="noopener"><?= e((string) $file['filename']) ?></a></td>
            <td><?= e((string) $file['file_kind']) ?></td>
            <td><button type="button" class="admin-btn admin-btn--sm js-mkt-file-del" data-id="<?= (int) $file['id'] ?>">삭제</button></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>