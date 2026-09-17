<?php
/** @var int $year */
$year = (int) ($year ?? date('Y'));
$company = company_info();
$rows = [];
try {
    $rows = (new \App\Services\CompanyInfoService())->publicRows();
} catch (\Throwable) {
    $rows = array_values(array_filter([
        ['label' => '상호', 'value' => (string) ($company['name'] ?? '라벨업')],
    ], static fn (array $r): bool => trim($r['value']) !== ''));
}
$defaultLinks = [
    ['label' => '서비스 소개', 'href' => url('about')],
    ['label' => '이용약관', 'href' => url('terms')],
    ['label' => '개인정보 처리방침', 'href' => url('privacy')],
    ['label' => '자주 묻는 질문', 'href' => url('faq')],
    ['label' => '라벨쇼핑', 'href' => url('shop')],
];
$links = is_array($links ?? null) ? $links : $defaultLinks;
?>
<footer class="site-footer page-footer" aria-label="사업자 정보">
  <?php if ($links !== []): ?>
  <div class="site-footer-links faq-foot-links">
    <?php foreach ($links as $link): ?>
    <a href="<?= e((string) ($link['href'] ?? '#')) ?>"><?= e((string) ($link['label'] ?? '')) ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="site-footer-biz">
    <strong class="site-footer-brand"><?= e((string) ($company['name'] ?? '라벨업')) ?></strong>
    <?php if ($rows !== []): ?>
    <ul class="site-footer-biz-list">
      <?php foreach ($rows as $row): ?>
      <li><span><?= e((string) $row['label']) ?></span><em><?= e((string) $row['value']) ?></em></li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="site-footer-biz-empty">라벨 디자인부터 인쇄·구매까지 한곳에서 제공하는 라벨업 서비스입니다. 사업자정보는 관리자 SEO › 사업자정보에서 등록하면 이곳에 표시됩니다.</p>
    <?php endif; ?>
  </div>

  <div class="copy">© <?= $year ?> LABEL UP. All rights reserved.</div>
</footer>
