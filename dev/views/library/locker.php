<?php
/** @var array<int, array<string, mixed>> $cliparts */
$cliparts = $cliparts ?? [];
?>
<section class="lib-hero card">
  <div>
    <p class="lib-kicker">마이라벨업</p>
    <h1>내 보관함</h1>
    <p>라비가 그린 클립아트와 내 그림을 보관합니다. 삭제하면 휴지통으로 이동합니다.</p>
  </div>
  <a class="account-btn account-btn--primary" href="<?= url('/') ?>">라비에게 요청하기</a>
</section>

<section class="card lib-panel">
  <div class="account-panel-head">
    <h2>보관 중인 클립아트 <?= count($cliparts) > 0 ? '<em>' . number_format(count($cliparts)) . '</em>' : '' ?></h2>
    <a href="<?= url('trash') ?>">휴지통 보기 →</a>
  </div>
  <?php if ($cliparts === []): ?>
  <p class="account-empty">아직 보관한 클립아트가 없습니다. 홈에서 라비에게 그림을 요청하면 여기에 모아집니다.</p>
  <?php else: ?>
  <div class="lib-grid">
    <?php foreach ($cliparts as $item): ?>
    <?php
      $url = (string) ($item['image_url'] ?? $item['preview_url'] ?? '');
      $title = (string) ($item['title'] ?? '클립아트');
      $edit = (string) ($item['editor_url'] ?? url('editor/'));
    ?>
    <article class="lib-card">
      <button type="button" class="lib-card__thumb js-clip-preview" data-src="<?= e($url) ?>" data-title="<?= e($title) ?>" data-edit="<?= e($edit) ?>">
        <?php if ($url !== ''): ?>
        <img src="<?= e($url) ?>" alt="<?= e($title) ?>">
        <?php else: ?>
        <span>그림</span>
        <?php endif; ?>
      </button>
      <strong><?= e($title) ?></strong>
      <em><?= e(substr((string) ($item['created_at'] ?? ''), 0, 16)) ?></em>
      <div class="lib-card__actions">
        <a class="account-btn account-btn--primary" href="<?= e($edit) ?>">바로편집</a>
        <button type="button" class="account-btn account-btn--outline js-lib-act" data-act="trash" data-type="clipart" data-id="<?= (int) ($item['id'] ?? 0) ?>">삭제</button>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
