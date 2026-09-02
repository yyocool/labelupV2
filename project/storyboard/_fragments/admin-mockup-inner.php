<?php
/**
 * Backoffice 목업 본문 — sbWfRoot 내부 (admin-doc-shell에서 include)
 *
 * @var callable|null $adminMockup
 * @var array $adminLnb
 * @var string $adminActive
 * @var array $adminCrumbParts
 */
?>
<div class="sb-adm">
    <aside class="sb-adm-lnb sb-wf-zone" data-zone-id="L-01">
        <span class="sb-wf-zone-label sb-wf-zone-label--purple">L-01</span>
        <div class="sb-adm-lnb-brand">
            <b>Label-UP</b><small>ADMIN</small>
        </div>
        <nav class="sb-adm-lnb-nav">
            <?php foreach ($adminLnb as $it): ?>
            <div class="sb-adm-lnb-item<?= $it['code'] === $adminActive ? ' is-active' : '' ?>">
                <span class="ic"><?= $it['ic'] ?></span><span><?= e($it['title']) ?></span>
            </div>
            <?php endforeach; ?>
        </nav>
        <div class="sb-adm-lnb-foot">v1.0 · 관리자 콘솔</div>
    </aside>

    <div class="sb-adm-main">
        <div class="sb-adm-topbar sb-wf-zone" data-zone-id="T-01">
            <span class="sb-wf-zone-label sb-wf-zone-label--purple">T-01</span>
            <div class="sb-adm-crumb">
                <?php $lastIdx = count($adminCrumbParts) - 1; ?>
                <?php foreach ($adminCrumbParts as $i => $cp): ?>
                    <?= $i === $lastIdx ? '<b>' . e($cp) . '</b>' : e($cp) . ' › ' ?>
                <?php endforeach; ?>
            </div>
            <div class="sb-adm-topsearch">🔍 통합 검색 (회원·주문·상품)</div>
            <div class="sb-adm-topbtn">🔔<span class="dot">5</span></div>
            <div class="sb-adm-topuser"><span class="av">관</span><span>관리자님 ▾</span></div>
        </div>
        <div class="sb-adm-body">
            <?php if ($adminMockup) { $adminMockup(); } ?>
        </div>
    </div>
</div>
