<?php
/** @var array<int, array<string, mixed>> $trashItems */
$trashItems = $trashItems ?? [];
?>
<section class="lib-hero card">
  <div>
    <p class="lib-kicker">마이라벨업</p>
    <h1>휴지통</h1>
    <p>프로젝트와 보관함에서 삭제한 항목입니다. 복원하거나 여기서만 완전히 지울 수 있습니다.</p>
  </div>
  <a class="account-icon-btn lib-hero-icon" href="<?= url('projects') ?>" title="프로젝트로" aria-label="프로젝트로 이동">
    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" d="M3 6.5A1.5 1.5 0 0 1 4.5 5h4.6l2 2.2h8.4A1.5 1.5 0 0 1 21 8.7v9.8a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5z"/></svg>
  </a>
</section>

<section class="card lib-panel">
  <div class="account-panel-head">
    <h2>삭제된 항목 <?= count($trashItems) > 0 ? '<em>' . number_format(count($trashItems)) . '</em>' : '' ?></h2>
  </div>
  <?php if ($trashItems === []): ?>
  <p class="account-empty">휴지통이 비어 있습니다.</p>
  <?php else: ?>
  <div class="lib-grid">
    <?php foreach ($trashItems as $item): ?>
    <?php
      $type = (string) ($item['type'] ?? 'workspace');
      $thumb = (string) ($item['preview_url'] ?? $item['image_url'] ?? '');
      $title = (string) ($item['title'] ?? '항목');
    ?>
    <article class="lib-card is-trashed">
      <div class="lib-card__thumb">
        <?php if ($thumb !== ''): ?>
        <img src="<?= e($thumb) ?>" alt="<?= e($title) ?>">
        <?php else: ?>
        <span><?= $type === 'clipart' ? '그림' : '라벨' ?></span>
        <?php endif; ?>
      </div>
      <span class="lib-card__kind"><?= e((string) ($item['kind_label'] ?? ($type === 'clipart' ? '보관함' : '프로젝트'))) ?></span>
      <strong><?= e($title) ?></strong>
      <em>삭제 <?= e((string) ($item['trashed_label'] ?? '')) ?></em>
      <div class="lib-card__actions">
        <button type="button" class="account-btn account-btn--primary js-lib-act" data-act="restore" data-type="<?= e($type) ?>" data-id="<?= (int) ($item['id'] ?? 0) ?>">복원</button>
        <button type="button" class="account-btn account-btn--danger js-lib-act" data-act="purge" data-type="<?= e($type) ?>" data-id="<?= (int) ($item['id'] ?? 0) ?>" data-confirm="완전히 삭제하면 되돌릴 수 없습니다. 삭제할까요?">완전 삭제</button>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
