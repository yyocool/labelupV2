<?php if (!empty($analysis['meta'])): ?>
<div class="pricing-meta-bar">
    <span>버전 <?= e($analysis['meta']['version']) ?></span>
    <span>기준일 <?= e($analysis['meta']['updated']) ?></span>
    <span><?= e($analysis['meta']['basis']) ?></span>
</div>
<?php endif; ?>

<?php if (!empty($analysis['label_gpt'])): ?>
<?php $lg = $analysis['label_gpt']; ?>
<section class="label-gpt-hero label-gpt-hero--compact">
    <div class="label-gpt-hero-glow"></div>
    <div class="label-gpt-hero-inner">
        <div class="label-gpt-hero-head">
            <span class="label-gpt-brand"><?= e($lg['brand']) ?></span>
            <span class="label-gpt-service"><?= e($lg['service_name']) ?></span>
        </div>
        <h2 class="label-gpt-headline"><?= e($lg['headline']) ?></h2>
        <?php if (!empty($lg['free_pro'])): ?>
        <p class="label-gpt-pricing-inline"><?= e($lg['free_pro']) ?></p>
        <?php endif; ?>
    </div>
    <div class="label-gpt-mini-grid">
        <?php foreach ($lg['features'] as $feat): ?>
        <div class="label-gpt-mini-card">
            <span class="label-gpt-feature-icon"><?= e($feat['icon']) ?></span>
            <strong><?= e($feat['name']) ?></strong>
            <p><?= e($feat['desc']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <p class="label-gpt-pricing-note label-gpt-pricing-note--inline">
        <a href="<?= url('competitive-analysis.php') ?>">경쟁서비스 분석 → 라벨 GPT 상세</a>
    </p>
</section>
<?php endif; ?>

<?php if (!empty($analysis['our_service_summary'])): ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3><?= e($analysis['our_service_summary']['title']) ?></h3></div>
    <div class="pricing-service-grid">
        <?php foreach ($analysis['our_service_summary']['items'] as $item): ?>
        <div class="pricing-service-item">
            <span class="pricing-service-icon"><?= e($item['icon']) ?></span>
            <strong><?= e($item['label']) ?></strong>
            <p><?= e($item['desc']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php
$competitorGroups = array(
    'domestic' => array('label' => '국내 라벨 경쟁사', 'badge' => '아이라벨 · 폼텍 · 애니라벨', 'badge_class' => 'badge-primary'),
    'ai_global' => array('label' => 'AI·글로벌 디자인 SaaS', 'badge' => 'Canva · Adobe Express · Fotor', 'badge_class' => 'badge-gray'),
);
?>

<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h3>경쟁업체 요금정책 분석</h3>
    </div>
    <?php foreach ($competitorGroups as $groupKey => $groupMeta): ?>
    <?php
    $groupCompetitors = array_values(array_filter($analysis['competitors'], function ($c) use ($groupKey) {
        $g = isset($c['group']) ? $c['group'] : 'domestic';
        return $g === $groupKey;
    }));
    if (empty($groupCompetitors)) {
        continue;
    }
    ?>
    <div class="pricing-comp-group">
        <div class="pricing-comp-group-head">
            <strong><?= e($groupMeta['label']) ?></strong>
            <span class="badge <?= e($groupMeta['badge_class']) ?>"><?= e($groupMeta['badge']) ?></span>
        </div>
        <div class="table-wrap">
            <table class="pricing-comp-table">
                <thead>
                    <tr>
                        <th>서비스</th>
                        <th>유형</th>
                        <th>요금 구조</th>
                        <th>AI 정책</th>
                        <th>강점 / 약점</th>
                        <th>Label-UP 차별</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groupCompetitors as $c): ?>
                    <tr>
                        <td>
                            <strong><?= e($c['name']) ?></strong>
                            <?php if (!empty($c['site'])): ?>
                            <small style="display:block;color:var(--text-muted)"><?= e($c['site']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= e($c['type']) ?></td>
                        <td>
                            <?php foreach ($c['pricing'] as $p): ?>
                            <div class="pricing-comp-plan">
                                <strong><?= e($p['plan']) ?></strong> <?= e($p['price']) ?>
                                <small><?= e($p['note']) ?></small>
                            </div>
                            <?php endforeach; ?>
                        </td>
                        <td style="font-size:13px;line-height:1.5"><?= e($c['ai']) ?></td>
                        <td style="font-size:12px">
                            <span class="text-success">+ <?= e($c['strength']) ?></span><br>
                            <span class="text-muted">− <?= e($c['weakness']) ?></span>
                        </td>
                        <td style="font-size:12px;line-height:1.5"><?= e(isset($c['labelup_diff']) ? $c['labelup_diff'] : '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (!empty($analysis['competitor_summary'])): ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3><?= e($analysis['competitor_summary']['title']) ?></h3></div>
    <div class="grid-2" style="padding:16px;gap:16px">
        <div>
            <strong style="font-size:13px">3사 공통점</strong>
            <ul style="margin:8px 0 0;padding-left:18px;font-size:13px;line-height:1.6">
                <?php foreach ($analysis['competitor_summary']['common'] as $item): ?>
                <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <strong style="font-size:13px;color:var(--primary)">Label-UP 기회</strong>
            <ul style="margin:8px 0 0;padding-left:18px;font-size:13px;line-height:1.6">
                <?php foreach ($analysis['competitor_summary']['labelup_opportunity'] as $item): ?>
                <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($analysis['ai_global_summary'])): ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3><?= e($analysis['ai_global_summary']['title']) ?></h3></div>
    <div class="grid-2" style="padding:16px;gap:16px">
        <div>
            <strong style="font-size:13px">Canva·Adobe·Fotor 공통점</strong>
            <ul style="margin:8px 0 0;padding-left:18px;font-size:13px;line-height:1.6">
                <?php foreach ($analysis['ai_global_summary']['common'] as $item): ?>
                <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <strong style="font-size:13px;color:var(--primary)">Label-UP 기회</strong>
            <ul style="margin:8px 0 0;padding-left:18px;font-size:13px;line-height:1.6">
                <?php foreach ($analysis['ai_global_summary']['labelup_opportunity'] as $item): ?>
                <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($analysis['ai_benchmark'])): ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3><?= e($analysis['ai_benchmark']['title']) ?></h3></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>서비스</th><th>모델</th><th>한도</th><th>초과</th></tr>
            </thead>
            <tbody>
                <?php foreach ($analysis['ai_benchmark']['rows'] as $row): ?>
                <tr class="<?= $row['service'] === 'Label-UP (정책)' ? 'pricing-row-highlight' : '' ?>">
                    <td><strong><?= e($row['service']) ?></strong></td>
                    <td><?= e($row['model']) ?></td>
                    <td><?= e($row['limit']) ?></td>
                    <td><?= e($row['overage']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($analysis['ai_benchmark']['note'])): ?>
    <p class="pricing-note"><?= e($analysis['ai_benchmark']['note']) ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card-header" style="margin-bottom:12px">
    <h2 style="font-size:18px;margin:0">Label-UP 요금 추천안</h2>
    <p style="margin:4px 0 0;font-size:13px;color:var(--text-muted)">정책관리에 정의된 Free·Pro·AI·포인트·쇼핑 구조를 반영한 2가지 시나리오</p>
</div>

<div class="grid-2 pricing-rec-grid">
    <?php foreach ($analysis['recommendations'] as $rec): ?>
    <div class="card pricing-rec-card">
        <div class="pricing-rec-header">
            <span class="badge badge-primary"><?= e($rec['badge']) ?></span>
            <h3><?= e($rec['title']) ?></h3>
            <p><?= e($rec['subtitle']) ?></p>
            <small style="color:var(--text-muted)">타깃: <?= e($rec['target']) ?></small>
        </div>

        <div class="pricing-tier pricing-tier--free">
            <div class="pricing-tier-label">Free</div>
            <div class="pricing-tier-price"><?= e($rec['free']['price']) ?></div>
            <ul>
                <?php foreach ($rec['free']['features'] as $f): ?>
                <li><?= e($f) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="pricing-tier pricing-tier--pro">
            <div class="pricing-tier-label">Pro</div>
            <div class="pricing-tier-price"><?= e($rec['pro']['price_month']) ?></div>
            <div class="pricing-tier-sub"><?= e($rec['pro']['price_year']) ?></div>
            <ul>
                <?php foreach ($rec['pro']['features'] as $f): ?>
                <li><?= e($f) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="pricing-commerce-box">
            <strong>쇼핑·부가</strong>
            <?php foreach ($rec['commerce'] as $label => $val): ?>
            <div class="pricing-commerce-row"><span><?= e($label) ?></span><span><?= e($val) ?></span></div>
            <?php endforeach; ?>
        </div>

        <div class="pricing-pros-cons">
            <div><strong>장점</strong><ul><?php foreach ($rec['pros'] as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul></div>
            <div><strong>단점</strong><ul><?php foreach ($rec['cons'] as $c): ?><li><?= e($c) ?></li><?php endforeach; ?></ul></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (!empty($analysis['comparison_matrix'])): ?>
<div class="card" style="margin:20px 0">
    <div class="card-header"><h3>추천안 비교</h3></div>
    <div class="table-wrap">
        <table>
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
                    <?php foreach ($row as $cell): ?>
                    <td><?= e($cell) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($analysis['extras'])): ?>
<div class="grid-2" style="margin-bottom:20px">
    <?php foreach ($analysis['extras'] as $extra): ?>
    <div class="card">
        <div class="card-header"><h3><?= e($extra['title']) ?></h3></div>
        <div class="pricing-extra-body"><?= nl2br(e($extra['content'])) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($relatedPolicies)): ?>
<div class="card">
    <div class="card-header"><h3>연관 정책</h3></div>
    <div style="padding:12px 16px;display:flex;flex-wrap:wrap;gap:8px">
        <?php foreach ($relatedPolicies as $pol): ?>
        <a href="<?= url('policies.php?edit=' . (int) $pol['id']) ?>" class="btn btn-outline btn-sm"><?= e($pol['title']) ?></a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
