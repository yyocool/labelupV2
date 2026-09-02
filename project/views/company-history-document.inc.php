<?php
/**
 * 회사 연혁 문서 본문 (보기/인쇄 공용)
 * @var array $vc company
 * @var array $ve flat events
 * @var array $veByYear fallback
 * @var array $va achievements
 * @var array $eventCategories
 * @var array $achievementCategories
 */
if (!isset($eventCategories)) {
    $eventCategories = CompanyHistoryService::getEventCategories();
}
if (!isset($achievementCategories)) {
    $achievementCategories = CompanyHistoryService::getAchievementCategories();
}
if (!isset($va) || !is_array($va)) {
    $va = array();
}
$hasMeta = !empty($vc['founded_year']) || !empty($vc['industry']) || !empty($vc['website']);

$flatEvents = array();
if (!empty($ve) && is_array($ve)) {
    $flatEvents = $ve;
} elseif (!empty($veByYear) && is_array($veByYear)) {
    foreach ($veByYear as $rows) {
        foreach ($rows as $row) {
            $flatEvents[] = $row;
        }
    }
}
$secIndex = 0;
?>
<article class="ch-doc">
    <header class="ch-doc-head">
        <div class="ch-doc-brand">
            <p class="ch-doc-eyebrow">회사 연혁</p>
        </div>
        <h2 class="ch-doc-name"><?= e($vc['name']) ?></h2>
        <?php if ($hasMeta): ?>
        <ul class="ch-doc-meta">
            <?php if (!empty($vc['founded_year'])): ?>
            <li><span class="ch-doc-meta-label">설립</span><?= e($vc['founded_year']) ?></li>
            <?php endif; ?>
            <?php if (!empty($vc['industry'])): ?>
            <li><span class="ch-doc-meta-label">업종</span><?= e($vc['industry']) ?></li>
            <?php endif; ?>
            <?php if (!empty($vc['website'])): ?>
            <li><span class="ch-doc-meta-label">웹</span><?= e($vc['website']) ?></li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>
        <?php if (!empty($vc['summary'])): ?>
        <div class="ch-doc-summary">
            <span class="ch-doc-summary-label">소개</span>
            <p><?= nl2br(e($vc['summary'])) ?></p>
        </div>
        <?php endif; ?>
    </header>

    <?php if (!empty($flatEvents)): ?>
    <?php $secIndex++; ?>
    <section class="ch-doc-section">
        <div class="ch-doc-section-head">
            <span class="ch-doc-section-num"><?= str_pad((string) $secIndex, 2, '0', STR_PAD_LEFT) ?></span>
            <h3>연혁</h3>
            <span class="ch-doc-section-count"><?= count($flatEvents) ?></span>
        </div>
        <div class="ch-doc-timeline">
            <?php
            $prevYear = null;
            $i = 0;
            foreach ($flatEvents as $row):
                $i++;
                $yearLabel = !empty($row['event_year']) ? (string) $row['event_year'] : '—';
                $when = CompanyHistoryService::formatEventDate($row);
                $catLabel = isset($eventCategories[$row['category']]) ? $eventCategories[$row['category']] : $row['category'];
                $showYear = ($prevYear !== $yearLabel);
                $dateDisplay = $when !== '' ? $when : $yearLabel;
            ?>
            <div class="ch-doc-item<?= $showYear ? ' is-year-start' : '' ?>">
                <div class="ch-doc-side">
                    <?php if ($showYear): ?>
                    <div class="ch-doc-year"><?= e($yearLabel) ?></div>
                    <?php endif; ?>
                    <?php if ($when !== '' && strpos($when, '.') !== false): ?>
                    <?php $parts = explode('.', $when, 2); ?>
                    <div class="ch-doc-date"><?= e(isset($parts[1]) ? $parts[1] : '') ?>월</div>
                    <?php elseif (!$showYear && $dateDisplay !== ''): ?>
                    <div class="ch-doc-date"><?= e($dateDisplay) ?></div>
                    <?php endif; ?>
                </div>
                <div class="ch-doc-body">
                    <div class="ch-doc-item-card">
                        <div class="ch-doc-item-top">
                            <span class="ch-doc-item-index"><?= str_pad((string) $i, 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="ch-doc-item-title"><?= e($row['title']) ?></span>
                            <span class="ch-doc-cat"><?= e($catLabel) ?></span>
                        </div>
                        <?php if (!empty($row['description'])): ?>
                        <p class="ch-doc-item-desc"><?= nl2br(e($row['description'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
                $prevYear = $yearLabel;
            endforeach;
            ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($va)): ?>
    <?php $secIndex++; ?>
    <section class="ch-doc-section">
        <div class="ch-doc-section-head">
            <span class="ch-doc-section-num"><?= str_pad((string) $secIndex, 2, '0', STR_PAD_LEFT) ?></span>
            <h3>주요 실적</h3>
            <span class="ch-doc-section-count"><?= count($va) ?></span>
        </div>
        <ol class="ch-doc-ach-list">
            <?php $ai = 0; foreach ($va as $row): $ai++; ?>
            <?php
                $catLabel = isset($achievementCategories[$row['category']]) ? $achievementCategories[$row['category']] : $row['category'];
            ?>
            <li class="ch-doc-ach-item">
                <div class="ch-doc-ach-num"><?= str_pad((string) $ai, 2, '0', STR_PAD_LEFT) ?></div>
                <div class="ch-doc-ach-body">
                    <div class="ch-doc-item-top">
                        <?php if (!empty($row['achieved_year'])): ?>
                        <span class="ch-doc-ach-year"><?= e($row['achieved_year']) ?></span>
                        <?php endif; ?>
                        <span class="ch-doc-item-title"><?= e($row['title']) ?></span>
                        <span class="ch-doc-cat"><?= e($catLabel) ?></span>
                    </div>
                    <?php if (!empty($row['client']) || !empty($row['metric'])): ?>
                    <div class="ch-doc-item-sub">
                        <?php if (!empty($row['client'])): ?>
                        <span class="ch-doc-ach-client"><?= e($row['client']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($row['metric'])): ?>
                        <span class="ch-doc-item-metric"><?= e($row['metric']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($row['description'])): ?>
                    <p class="ch-doc-item-desc"><?= nl2br(e($row['description'])) ?></p>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ol>
    </section>
    <?php endif; ?>

    <?php if (empty($flatEvents) && empty($va)): ?>
    <p class="ch-doc-empty">등록된 연혁·실적이 없습니다.</p>
    <?php endif; ?>
</article>
