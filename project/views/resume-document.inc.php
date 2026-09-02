<?php
/**
 * 이력서 문서 본문 (보기/인쇄 공용)
 * @var array $vp person
 * @var array $ve grouped entries
 * @var array $categories
 */
if (!isset($categories)) {
    $categories = ResumeService::getCategories();
}
$skillsRaw = isset($vp['skills']) ? trim((string) $vp['skills']) : '';
$skills = array();
if ($skillsRaw !== '') {
    $parts = preg_split('/[,;\n]+/u', $skillsRaw);
    foreach ($parts as $s) {
        $s = trim($s);
        if ($s !== '') {
            $skills[] = $s;
        }
    }
}
$secIndex = 0;
?>
<article class="resume-doc">
    <header class="resume-doc-head">
        <div class="resume-doc-brand">
            <p class="resume-doc-eyebrow">이력서</p>
        </div>
        <h2 class="resume-doc-name"><?= e($vp['name']) ?></h2>
        <?php if (!empty($vp['job_title']) || !empty($vp['organization'])): ?>
        <p class="resume-doc-role">
            <?php if (!empty($vp['job_title'])): ?><span class="resume-doc-role-title"><?= e($vp['job_title']) ?></span><?php endif; ?>
            <?php if (!empty($vp['job_title']) && !empty($vp['organization'])): ?><span class="resume-doc-role-sep">·</span><?php endif; ?>
            <?php if (!empty($vp['organization'])): ?><span class="resume-doc-role-org"><?= e($vp['organization']) ?></span><?php endif; ?>
        </p>
        <?php endif; ?>
        <?php if (!empty($vp['email']) || !empty($vp['phone'])): ?>
        <ul class="resume-doc-contact">
            <?php if (!empty($vp['email'])): ?>
            <li><span class="resume-doc-contact-label">Email</span><?= e($vp['email']) ?></li>
            <?php endif; ?>
            <?php if (!empty($vp['phone'])): ?>
            <li><span class="resume-doc-contact-label">Tel</span><?= e($vp['phone']) ?></li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>
        <?php if (!empty($vp['summary'])): ?>
        <div class="resume-doc-summary">
            <span class="resume-doc-summary-label">소개</span>
            <p><?= nl2br(e($vp['summary'])) ?></p>
        </div>
        <?php endif; ?>
    </header>

    <?php if (!empty($skills)): ?>
    <?php $secIndex++; ?>
    <section class="resume-doc-section">
        <div class="resume-doc-section-head">
            <span class="resume-doc-section-num"><?= str_pad((string) $secIndex, 2, '0', STR_PAD_LEFT) ?></span>
            <h3>보유 기술</h3>
        </div>
        <div class="resume-doc-skills">
            <?php foreach ($skills as $sk): ?>
            <span class="resume-doc-skill"><?= e($sk) ?></span>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php foreach ($categories as $catKey => $catMeta): ?>
    <?php $rows = isset($ve[$catKey]) ? $ve[$catKey] : array(); if (empty($rows)) continue; ?>
    <?php $secIndex++; ?>
    <section class="resume-doc-section">
        <div class="resume-doc-section-head">
            <span class="resume-doc-section-num"><?= str_pad((string) $secIndex, 2, '0', STR_PAD_LEFT) ?></span>
            <h3><?= e($catMeta['label']) ?></h3>
            <span class="resume-doc-section-count"><?= count($rows) ?></span>
        </div>
        <div class="resume-doc-items">
            <?php foreach ($rows as $row): ?>
            <?php $period = ResumeService::formatPeriod($row); ?>
            <div class="resume-doc-item">
                <div class="resume-doc-item-head">
                    <div class="resume-doc-item-main">
                        <div class="resume-doc-item-title"><?= e($row['title']) ?></div>
                        <?php if (!empty($row['organization'])): ?>
                        <div class="resume-doc-item-org"><?= e($row['organization']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($period !== ''): ?>
                    <div class="resume-doc-item-period"><?= e($period) ?></div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($row['description'])): ?>
                <p class="resume-doc-item-desc"><?= nl2br(e($row['description'])) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</article>
