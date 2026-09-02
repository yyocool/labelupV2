<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>요금정책분석</h1>
            <p>국내 3사 · Canva · Adobe · Fotor 대비 Label-UP 요금·AI 추천안</p>
        </div>
        <div class="btn-group">
            <a href="<?= url('pricing-analysis.php?print=1') ?>" class="btn btn-primary btn-sm" target="_blank" rel="noopener">PDF로 저장</a>
            <a href="<?= url('competitive-analysis.php') ?>" class="btn btn-outline btn-sm">경쟁서비스 분석</a>
            <a href="<?= url('policies.php?category=payment') ?>" class="btn btn-outline btn-sm">결제·구독 정책</a>
            <a href="<?= url('policies.php') ?>" class="btn btn-secondary btn-sm">정책관리</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/pricing-analysis-document.inc.php'; ?>
