<?php
/** @var array<int, array<string, mixed>> $projects */
$projects = $projects ?? [];
?>
<section class="lib-hero card">
  <div>
    <p class="lib-kicker">마이라벨업</p>
    <h1>프로젝트</h1>
    <p>편집기에 저장한 라벨 디자인을 모아서 이어 작업합니다. 삭제하면 휴지통으로 이동합니다.</p>
  </div>
  <a class="account-btn account-btn--primary" href="<?= url('editor/?new=1') ?>">새 디자인 만들기</a>
</section>

<section class="card lib-panel">
  <div class="account-panel-head">
    <h2>내 프로젝트 <?= count($projects) > 0 ? '<em>' . number_format(count($projects)) . '</em>' : '' ?></h2>
    <a href="<?= url('trash') ?>">휴지통 보기 →</a>
  </div>
  <?php if ($projects === []): ?>
  <p class="account-empty">아직 저장된 프로젝트가 없습니다. 편집기에서 저장하면 여기에 모입니다.</p>
  <?php else: ?>
  <div class="lib-grid">
    <?php foreach ($projects as $item): ?>
    <article class="lib-card">
      <a class="lib-card__thumb" href="<?= e((string) ($item['editor_url'] ?? url('editor/'))) ?>">
        <?php if (!empty($item['preview_url'])): ?>
        <img src="<?= e((string) $item['preview_url']) ?>" alt="<?= e((string) ($item['title'] ?? '라벨')) ?>">
        <?php else: ?>
        <span>라벨</span>
        <?php endif; ?>
      </a>
      <strong><?= e((string) ($item['title'] ?? '새 라벨 디자인')) ?></strong>
      <em><?= e((string) ($item['updated_label'] ?? '')) ?></em>
      <div class="lib-card__actions">
        <a class="account-btn account-btn--primary" href="<?= e((string) ($item['editor_url'] ?? url('editor/'))) ?>">이어 편집</a>
        <button type="button" class="account-btn account-btn--outline js-lib-act" data-act="trash" data-type="workspace" data-id="<?= (int) ($item['id'] ?? 0) ?>">삭제</button>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
