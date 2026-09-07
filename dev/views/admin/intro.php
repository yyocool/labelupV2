<?php
/** @var array<string, mixed> $intro */
$intro = $intro ?? [];
$v = static fn (string $key, string $default = ''): string => (string) ($intro[$key] ?? $default);
$enabled = (int) ($intro['is_enabled'] ?? 0) === 1;
$type = $v('source_type', 'youtube');
?>
<div class="admin-head">
  <div>
    <h1>인트로설정</h1>
    <p>사이트 최초 접속 시 전체화면으로 보여줄 유튜브·업로드 영상 또는 라벨업 웹 애니메이션을 등록합니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn" id="introPreviewBtn">전체화면 미리보기</button>
    <button type="button" class="admin-btn admin-btn--primary" id="introSaveBtn">저장</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>

<form id="introForm" class="admin-card admin-seo-form" enctype="multipart/form-data">
  <div class="admin-form-grid">
    <label class="admin-field admin-field--full">
      <span>사용 여부</span>
      <label class="admin-check">
        <input type="checkbox" name="is_enabled" value="1"<?= $enabled ? ' checked' : '' ?>>
        인트로 사용 (브라우저당 최초 1회 자동 재생)
      </label>
    </label>

    <label class="admin-field">
      <span>인트로 유형</span>
      <select class="admin-input" name="source_type" id="introSourceType">
        <option value="animation"<?= $type === 'animation' ? ' selected' : '' ?>>라벨업 웹 애니메이션</option>
        <option value="youtube"<?= $type === 'youtube' ? ' selected' : '' ?>>유튜브 URL</option>
        <option value="upload"<?= $type === 'upload' ? ' selected' : '' ?>>동영상 직접 업로드</option>
      </select>
    </label>

    <label class="admin-field">
      <span>건너뛰기 버튼 문구</span>
      <input class="admin-input" name="skip_label" value="<?= e($v('skip_label', '건너뛰기')) ?>" maxlength="80">
    </label>

    <div class="admin-field admin-field--full" data-intro-panel="animation"<?= $type !== 'animation' ? ' hidden' : '' ?>>
      <div class="admin-intro-anim-card">
        <strong>라벨업 소개 웹 애니메이션</strong>
        <p>브랜드 소개 → AI 디자인 → 편집·출력 → 라벨 구매까지 핵심만 약 14초로 보여줍니다. 별도 영상 파일 없이 바로 사용할 수 있습니다.</p>
        <ul>
          <li>LABEL UP / 라비 브랜드 오프닝</li>
          <li>말로 만드는 AI 라벨 디자인</li>
          <li>용지 선택 · 편집 · 출력</li>
          <li>라벨지 구매까지 한곳에서</li>
        </ul>
      </div>
    </div>

    <div class="admin-field admin-field--full" data-intro-panel="youtube"<?= $type !== 'youtube' ? ' hidden' : '' ?>>
      <label>
        <span>유튜브 URL</span>
        <input class="admin-input" name="youtube_url" id="introYoutubeUrl" value="<?= e($v('youtube_url')) ?>" placeholder="https://www.youtube.com/watch?v=... 또는 https://youtu.be/...">
      </label>
      <small>공개/일부공개 영상만 재생됩니다. Shorts·라이브 URL도 지원합니다.</small>
    </div>

    <div class="admin-field admin-field--full" data-intro-panel="upload"<?= $type !== 'upload' ? ' hidden' : '' ?>>
      <span>동영상 파일</span>
      <div class="admin-intro-upload">
        <input type="hidden" name="video_path" id="introVideoPath" value="<?= e($v('video_path')) ?>">
        <input type="file" id="introVideoFile" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov,.m4v">
        <button type="button" class="admin-btn" id="introUploadBtn">파일 업로드</button>
        <span class="admin-intro-upload-status" id="introUploadStatus">
          <?php if ($v('video_path') !== ''): ?>
            등록됨: <?= e($v('video_path')) ?>
          <?php else: ?>
            mp4 / webm / mov (최대 120MB)
          <?php endif; ?>
        </span>
      </div>
      <?php if ($v('video_url') !== ''): ?>
      <video class="admin-intro-thumb" id="introThumb" src="<?= e($v('video_url')) ?>" controls playsinline></video>
      <?php else: ?>
      <video class="admin-intro-thumb" id="introThumb" hidden controls playsinline></video>
      <?php endif; ?>
    </div>
  </div>
</form>

<script>
window.LABELUP_INTRO_ADMIN = <?= json_encode([
    'intro' => [
        'source_type' => $type,
        'youtube_id' => $v('youtube_id'),
        'youtube_url' => $v('youtube_url'),
        'video_url' => $v('video_url'),
        'video_path' => $v('video_path'),
        'skip_label' => $v('skip_label', '건너뛰기'),
        'is_enabled' => $enabled ? 1 : 0,
    ],
    'labiIconUrl' => asset('labi-icon.png'),
    'logoUrl' => asset('logo.png'),
    'saveUrl' => url('api/admin/intro/save'),
    'uploadUrl' => url('api/admin/intro/upload'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<link rel="stylesheet" href="<?= css('site-intro.css') ?>">
<script src="<?= js('site-intro.js') ?>"></script>
<script src="<?= js('admin-intro.js') ?>"></script>
