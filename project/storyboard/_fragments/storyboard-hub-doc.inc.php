<?php
/**
 * 허브형 스토리보드 문서 셸
 * 사전 정의: $pageTitle, $menuCode, $pageSubtitle, $metaCards, $layoutRows, $uxRows,
 *            $sbWfRootClass, $sbWfBodyFragment, $styleFragments (array of paths)
 */
if (!isset($styleFragments) || !is_array($styleFragments)) {
    $styleFragments = array(__DIR__ . '/01-05-hub-shared-styles.php');
}
if (!isset($sbWfRootClass)) {
    $sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--hub sb-wf-annotate';
}
if (!isset($pageSubtitle)) {
    $pageSubtitle = '';
}
?>
<style>
<?php include __DIR__ . '/wf-shared-styles.php'; ?>
<?php foreach ($styleFragments as $sf) { if (is_file($sf)) { include $sf; } } ?>
</style>

<div class="sb-page sb-front-doc">
    <header class="sb-front-doc-header">
        <div>
            <h2 class="sb-front-doc-title"><?= e($pageTitle) ?></h2>
            <p class="sb-front-doc-sub">
                메뉴코드 <strong><?= e($menuCode) ?></strong>
                <?php if ($pageSubtitle !== ''): ?> · <?= e($pageSubtitle) ?><?php endif; ?>
            </p>
        </div>
        <div class="sb-front-doc-actions">
            <button type="button" class="sb-front-btn" id="sbWfToggleAnnotate">📌 영역 표시</button>
            <button type="button" class="sb-front-btn sb-front-btn--primary" id="sbWfFullscreen">⛶ 전체화면 보기</button>
        </div>
    </header>

    <?php if (!empty($metaCards) && is_array($metaCards)): ?>
    <dl class="sb-front-meta-grid">
        <?php foreach ($metaCards as $card): ?>
        <div class="sb-front-meta-card"><dt><?= e($card[0]) ?></dt><dd><?= e($card[1]) ?></dd></div>
        <?php endforeach; ?>
    </dl>
    <?php endif; ?>

    <?php if (!empty($layoutRows) && is_array($layoutRows)): ?>
    <section class="sb-front-spec">
        <h3>레이아웃 구조 (정보 구조)</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>영역 ID</th><th>구분</th><th>블록</th><th>포함 요소</th><th>연결 메뉴</th></tr></thead>
            <tbody>
            <?php foreach ($layoutRows as $row): ?>
                <tr>
                    <td><code><?= e($row[0]) ?></code></td>
                    <td><span class="tag tag--<?= e($row[1]) ?>"><?= e($row[2]) ?></span></td>
                    <td><?= e($row[3]) ?></td>
                    <td><?= e($row[4]) ?></td>
                    <td><?= e($row[5]) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <?php if (!empty($uxRows) && is_array($uxRows)): ?>
    <section class="sb-front-spec">
        <h3>UX / 인터랙션 가이드</h3>
        <table class="sb-front-spec-table">
            <thead><tr><th>항목</th><th>내용</th></tr></thead>
            <tbody>
            <?php foreach ($uxRows as $row): ?>
                <tr><td><?= e($row[0]) ?></td><td><?= e($row[1]) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <p class="sb-front-preview-label"><span>WIREFRAME</span> 아래는 실제 UI에 가까운 고해상도 와이어프레임입니다</p>
    <?php include __DIR__ . '/storyboard-wf-wrap-shell.php'; ?>
    <p style="margin-top:12px;font-size:12px;color:#94a3b8;text-align:center">
        <strong>전체화면 보기</strong>에서 ☰ 메뉴로 전환 · 영역 ID 클릭 → 상세 정보
    </p>
</div>
<?php include __DIR__ . '/storyboard-wf-runtime.js.php'; ?>
