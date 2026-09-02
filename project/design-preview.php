<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/design_drafts.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();

$draftId = isset($_GET['id']) ? preg_replace('/[^a-z0-9_-]/i', '', $_GET['id']) : 'a';
$draft = design_draft_by_id($draftId);
if (!$draft) {
    $draftId = 'a';
    $draft = design_draft_by_id('a');
}
$drafts = design_draft_list();
$viewMode = (isset($_GET['view']) && $_GET['view'] === 'mobile') ? 'mobile' : 'desktop';

?><!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($draft['title']) ?> — 시안 보기</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/design-preview.css') ?>">
</head>
<body class="dp-body" data-view="<?= e($viewMode) ?>" data-draft="<?= e($draftId) ?>">
    <div class="dp-stage">
        <div class="dp-viewport" id="dpViewport">
            <div class="dp-frame" id="dpFrame">
                <?php if ($draftId === 'b'): ?>
                    <?php include __DIR__ . '/views/design-draft-b.php'; ?>
                <?php else: ?>
                    <?php include __DIR__ . '/views/design-draft-a.php'; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="dp-float" id="dpFloat">
        <div class="dp-picker" id="dpPicker" hidden>
            <div class="dp-picker-head">시안 선택</div>
            <?php foreach ($drafts as $d): ?>
            <a class="dp-picker-item<?= $d['id'] === $draftId ? ' is-active' : '' ?>"
               href="<?= e(url('design-preview.php?id=' . rawurlencode($d['id']) . '&view=' . rawurlencode($viewMode))) ?>">
                <strong><?= e($d['badge']) ?></strong>
                <span><?= e($d['title']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <button type="button" class="dp-fab dp-fab--select" id="dpSelectBtn" aria-expanded="false" aria-controls="dpPicker">
            <i class="dp-fab__ico" aria-hidden="true">▦</i>
            시안선택
        </button>
        <button type="button" class="dp-fab dp-fab--view" id="dpViewToggle" data-view="<?= e($viewMode) ?>">
            <i class="dp-fab__ico" aria-hidden="true"><?= $viewMode === 'mobile' ? '🖥' : '📱' ?></i>
            <?= $viewMode === 'mobile' ? '데스크탑보기' : '모바일보기' ?>
        </button>
    </div>

    <script>
    (function () {
        var body = document.body;
        var picker = document.getElementById('dpPicker');
        var selectBtn = document.getElementById('dpSelectBtn');
        var viewBtn = document.getElementById('dpViewToggle');

        selectBtn.addEventListener('click', function () {
            var open = picker.hasAttribute('hidden');
            if (open) {
                picker.removeAttribute('hidden');
                selectBtn.setAttribute('aria-expanded', 'true');
            } else {
                picker.setAttribute('hidden', '');
                selectBtn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('click', function (e) {
            if (!picker.hasAttribute('hidden') && !document.getElementById('dpFloat').contains(e.target)) {
                picker.setAttribute('hidden', '');
                selectBtn.setAttribute('aria-expanded', 'false');
            }
        });

        viewBtn.addEventListener('click', function () {
            var next = body.getAttribute('data-view') === 'mobile' ? 'desktop' : 'mobile';
            var url = new URL(window.location.href);
            url.searchParams.set('view', next);
            window.location.href = url.toString();
        });
    })();
    </script>
</body>
</html>
