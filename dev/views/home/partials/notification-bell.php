<?php
/** @var array|null $authUser */
$loggedIn = !empty($authUser);
$bellClass = trim((string) ($bellClass ?? 'bell'));
$badgeClass = trim((string) ($badgeClass ?? 'badge'));
$wrapClass = trim((string) ($bellWrapClass ?? 'notif-bell-wrap'));
?>
<?php if ($loggedIn): ?>
<div class="<?= e($wrapClass) ?>" data-notif-bell>
  <button class="<?= e($bellClass) ?>" type="button" data-notif-toggle aria-label="알림" aria-expanded="false" aria-controls="userNotifPanel">
    <?php require view_path('home/partials/bell-icon.php'); ?>
    <span class="<?= e($badgeClass) ?>" data-notif-badge hidden>0</span>
  </button>
  <div class="notif-panel" id="userNotifPanel" data-notif-panel hidden>
    <div class="notif-panel__head">
      <strong>알림</strong>
      <div class="notif-panel__actions">
        <button type="button" data-notif-read-all>모두 읽음</button>
        <a href="<?= url('account') ?>#notifications">설정</a>
      </div>
    </div>
    <div class="notif-panel__list" data-notif-list>
      <p class="notif-panel__empty">불러오는 중…</p>
    </div>
  </div>
</div>
<?php else: ?>
<a class="<?= e($bellClass) ?>" href="<?= url('login') ?>" aria-label="알림 — 로그인 필요" title="로그인 후 알림을 확인할 수 있습니다">
  <?php require view_path('home/partials/bell-icon.php'); ?>
</a>
<?php endif; ?>
