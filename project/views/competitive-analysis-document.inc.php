<?php if (!empty($analysis['meta'])): ?>
<div class="pricing-meta-bar">
    <span>버전 <?= e($analysis['meta']['version']) ?></span>
    <span>기준일 <?= e($analysis['meta']['updated']) ?></span>
    <span><?= e($analysis['meta']['basis']) ?></span>
</div>
<?php endif; ?>

<?php if (!empty($analysis['vision'])): ?>
<div class="card comp-vision-card" style="margin-bottom:20px">
    <div class="card-header">
        <h3><?= e($analysis['vision']['title']) ?></h3>
    </div>
    <p class="comp-vision-statement"><?= e($analysis['vision']['statement']) ?></p>
    <?php if (!empty($analysis['vision']['keywords'])): ?>
    <div class="comp-vision-tags">
        <?php foreach ($analysis['vision']['keywords'] as $kw): ?>
        <span class="badge badge-primary"><?= e($kw) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($analysis['label_gpt'])): ?>
<?php $lg = $analysis['label_gpt']; ?>
<section class="label-gpt-hero">
    <div class="label-gpt-hero-glow"></div>
    <div class="label-gpt-hero-inner">
        <div class="label-gpt-hero-head">
            <span class="label-gpt-brand"><?= e($lg['brand']) ?></span>
            <span class="label-gpt-service"><?= e($lg['service_name']) ?></span>
            <span class="label-gpt-badge-new">핵심 차별 AI</span>
        </div>
        <h2 class="label-gpt-headline"><?= e($lg['headline']) ?></h2>
        <p class="label-gpt-tagline"><?= e($lg['tagline']) ?></p>
        <p class="label-gpt-positioning"><?= e($lg['positioning']) ?></p>
    </div>

    <div class="label-gpt-feature-grid">
        <?php foreach ($lg['features'] as $feat): ?>
        <article class="label-gpt-feature-card label-gpt-feature-card--<?= e(strtolower($feat['badge'])) ?>">
            <div class="label-gpt-feature-top">
                <span class="label-gpt-feature-icon"><?= e($feat['icon']) ?></span>
                <div class="label-gpt-feature-badges">
                    <span class="label-gpt-fbadge label-gpt-fbadge--<?= e(strtolower($feat['badge'])) ?>"><?= e($feat['badge']) ?></span>
                    <span class="label-gpt-fphase"><?= e($feat['phase']) ?></span>
                </div>
            </div>
            <h3 class="label-gpt-feature-name"><?= e($feat['name']) ?></h3>
            <p class="label-gpt-feature-tagline"><?= e($feat['tagline']) ?></p>
            <p class="label-gpt-feature-desc"><?= e($feat['desc']) ?></p>
            <div class="label-gpt-scenario">
                <span class="label-gpt-scenario-label">사용 시나리오</span>
                <p><?= e($feat['scenario']) ?></p>
            </div>
            <div class="label-gpt-vs">
                <span>vs 경쟁사</span> <?= e($feat['vs']) ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($lg['moat'])): ?>
    <div class="label-gpt-moat">
        <h3><?= e($lg['moat']['title']) ?></h3>
        <div class="label-gpt-moat-grid">
            <?php foreach ($lg['moat']['items'] as $item): ?>
            <div class="label-gpt-moat-item">
                <strong><?= e($item['label']) ?></strong>
                <p><?= e($item['text']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($lg['pricing_note'])): ?>
    <p class="label-gpt-pricing-note">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <?= e($lg['pricing_note']) ?>
        · <a href="<?= url('pricing-analysis.php') ?>">요금정책분석</a>
    </p>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if (!empty($analysis['market_landscape'])): ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3><?= e($analysis['market_landscape']['title']) ?></h3></div>
    <p style="padding:0 16px 12px;margin:0;font-size:14px;line-height:1.65;color:var(--text-secondary)"><?= e($analysis['market_landscape']['summary']) ?></p>
    <div class="comp-segment-grid">
        <?php foreach ($analysis['market_landscape']['segments'] as $seg): ?>
        <div class="comp-segment-item">
            <strong><?= e($seg['label']) ?></strong>
            <p><?= e($seg['desc']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php
$compGroups = array(
    'domestic' => array('label' => '국내 라벨 경쟁사', 'badge' => '아이라벨 · 폼텍 · 애니라벨'),
    'ai_global' => array('label' => 'AI·글로벌 디자인 SaaS', 'badge' => 'Canva · Adobe Express · Fotor'),
);
?>

<?php foreach ($compGroups as $groupKey => $groupMeta): ?>
<?php
$groupItems = array_values(array_filter($analysis['competitors'], function ($c) use ($groupKey) {
    return (isset($c['group']) ? $c['group'] : 'domestic') === $groupKey;
}));
if (empty($groupItems)) {
    continue;
}
?>
<div class="comp-section-head">
    <h2><?= e($groupMeta['label']) ?></h2>
    <span class="badge badge-gray"><?= e($groupMeta['badge']) ?></span>
</div>

<?php foreach ($groupItems as $comp): ?>
<div class="card comp-detail-card" style="margin-bottom:20px" id="comp-<?= e($comp['id']) ?>">
    <div class="card-header comp-detail-header">
        <div>
            <h3><?= e($comp['name']) ?></h3>
            <small><?= e($comp['type']) ?> · <?= e($comp['site']) ?></small>
        </div>
    </div>

    <div class="comp-detail-body">
        <p class="comp-overview"><?= e($comp['overview']) ?></p>

        <div class="grid-2 comp-meta-grid">
            <div>
                <strong class="comp-label">핵심 기능</strong>
                <ul class="comp-feature-list">
                    <?php foreach ($comp['features'] as $f): ?>
                    <li><?= e($f) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <strong class="comp-label">타깃 · 수익 모델</strong>
                <p class="comp-text"><?= e($comp['target_users']) ?></p>
                <p class="comp-text comp-text-muted"><?= e($comp['business_model']) ?></p>
            </div>
        </div>

        <div class="grid-2 comp-pros-cons">
            <div class="comp-pros">
                <strong>장점</strong>
                <ul>
                    <?php foreach ($comp['pros'] as $p): ?>
                    <li><?= e($p) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="comp-cons">
                <strong>단점</strong>
                <ul>
                    <?php foreach ($comp['cons'] as $c): ?>
                    <li><?= e($c) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <?php if (!empty($comp['swot'])): ?>
        <div class="comp-swot-wrap">
            <strong class="comp-label">SWOT 분석</strong>
            <div class="comp-swot-grid">
                <?php
                $swotLabels = array(
                    'strengths' => array('label' => 'Strengths', 'class' => 'comp-swot--s'),
                    'weaknesses' => array('label' => 'Weaknesses', 'class' => 'comp-swot--w'),
                    'opportunities' => array('label' => 'Opportunities', 'class' => 'comp-swot--o'),
                    'threats' => array('label' => 'Threats', 'class' => 'comp-swot--t'),
                );
                foreach ($swotLabels as $key => $meta):
                    if (empty($comp['swot'][$key])) continue;
                ?>
                <div class="comp-swot-cell <?= e($meta['class']) ?>">
                    <span class="comp-swot-title"><?= e($meta['label']) ?></span>
                    <ul>
                        <?php foreach ($comp['swot'][$key] as $item): ?>
                        <li><?= e($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="comp-vs-labelup">
            <strong>vs Label-UP</strong>
            <p><?= e($comp['vs_labelup']) ?></p>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endforeach; ?>

<?php if (!empty($analysis['comparison_matrix'])): ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3><?= e($analysis['comparison_matrix']['title']) ?></h3></div>
    <div class="table-wrap">
        <table class="comp-matrix-table">
            <thead>
                <tr>
                    <?php foreach ($analysis['comparison_matrix']['headers'] as $h): ?>
                    <th><?= e($h) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($analysis['comparison_matrix']['rows'] as $row): ?>
                <tr>
                    <?php foreach ($row as $i => $cell): ?>
                    <td class="<?= $i === count($row) - 1 ? 'comp-matrix-highlight' : '' ?>"><?= e($cell) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($analysis['labelup_swot'])): ?>
<div class="card comp-labelup-swot" style="margin-bottom:20px">
    <div class="card-header"><h3><?= e($analysis['labelup_swot']['title']) ?></h3></div>
    <div class="comp-swot-grid comp-swot-grid--labelup">
        <?php
        $luSwot = array(
            'strengths' => array('label' => 'Strengths', 'class' => 'comp-swot--s'),
            'weaknesses' => array('label' => 'Weaknesses', 'class' => 'comp-swot--w'),
            'opportunities' => array('label' => 'Opportunities', 'class' => 'comp-swot--o'),
            'threats' => array('label' => 'Threats', 'class' => 'comp-swot--t'),
        );
        foreach ($luSwot as $key => $meta):
        ?>
        <div class="comp-swot-cell <?= e($meta['class']) ?>">
            <span class="comp-swot-title"><?= e($meta['label']) ?></span>
            <ul>
                <?php foreach ($analysis['labelup_swot'][$key] as $item): ?>
                <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($analysis['strategic_direction'])): ?>
<?php $sd = $analysis['strategic_direction']; ?>
<div class="card comp-strategy-card" style="margin-bottom:20px">
    <div class="card-header">
        <h3><?= e($sd['title']) ?></h3>
    </div>
    <div class="comp-north-star">
        <span class="comp-north-star-label">North Star</span>
        <p><?= e($sd['north_star']) ?></p>
    </div>

    <div class="comp-pillar-grid">
        <?php foreach ($sd['pillars'] as $pillar): ?>
        <div class="comp-pillar">
            <span class="comp-pillar-icon"><?= e($pillar['icon']) ?></span>
            <strong><?= e($pillar['title']) ?></strong>
            <ul>
                <?php foreach ($pillar['items'] as $item): ?>
                <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($sd['industry_issues'])): ?>
    <div class="comp-industry-issues">
        <h4><?= e($sd['industry_issues']['title']) ?></h4>
        <div class="comp-issue-list">
            <?php foreach ($sd['industry_issues']['items'] as $issue): ?>
            <div class="comp-issue-item">
                <span class="comp-issue-badge"><?= e($issue['issue']) ?></span>
                <p><?= e($issue['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($sd['ai_roadmap'])): ?>
    <div class="comp-roadmap">
        <h4>AI·성장 로드맵</h4>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>단계</th><th>핵심</th><th>KPI</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($sd['ai_roadmap'] as $step): ?>
                    <tr>
                        <td><strong><?= e($step['phase']) ?></strong></td>
                        <td><?= e($step['focus']) ?></td>
                        <td><?= e($step['kpi']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($sd['action_items'])): ?>
    <div class="comp-actions">
        <h4>실행 체크리스트</h4>
        <ul>
            <?php foreach ($sd['action_items'] as $action): ?>
            <li><?= e($action) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($analysis['related_links'])): ?>
<div class="card">
    <div class="card-header"><h3>연관 메뉴</h3></div>
    <div class="comp-related-links">
        <?php foreach ($analysis['related_links'] as $link): ?>
        <a href="<?= url($link['url']) ?>" class="comp-related-link">
            <strong><?= e($link['label']) ?></strong>
            <span><?= e($link['desc']) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
