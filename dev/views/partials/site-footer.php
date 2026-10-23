<?php
/** @var int $year */
$year = (int) ($year ?? date('Y'));
$company = company_info();
$name = trim((string) ($company['name'] ?? '라벨업'));
$ceo = trim((string) ($company['ceo'] ?? ''));
$bizNo = trim((string) ($company['biz_no'] ?? ''));
$mailOrderNo = trim((string) ($company['mail_order_no'] ?? ''));
$address = trim((string) ($company['address'] ?? ''));
$phone = trim((string) ($company['phone'] ?? ''));
$email = trim((string) ($company['email'] ?? ''));
$privacyOfficer = trim((string) ($company['privacy_officer'] ?? ''));
$hasCompany = $name !== '' || $ceo !== '' || $bizNo !== '' || $mailOrderNo !== '' || $address !== '' || $email !== '' || $privacyOfficer !== '';

$phoneHref = $phone !== '' ? ('tel:' . preg_replace('/[^0-9+]/', '', $phone)) : '';
$socialLinks = [];
try {
    $socialLinks = (new \App\Services\CompanyInfoService())->socialLinks();
} catch (\Throwable) {
    $socialLinks = [];
}

$defaultLinks = [
    ['label' => '회사소개', 'href' => url('about')],
    ['label' => '이용약관', 'href' => url('terms')],
    ['label' => '개인정보처리방침', 'href' => url('privacy')],
    ['label' => '자주 묻는 질문', 'href' => url('faq')],
    ['label' => '라벨쇼핑', 'href' => url('shop')],
];
$links = is_array($links ?? null) ? $links : $defaultLinks;

$companyLine = $name !== '' ? $name : '라벨업';
if ($ceo !== '') {
    $companyLine .= ' (대표: ' . $ceo . ')';
}
$legalBits = [];
if ($bizNo !== '') {
    $legalBits[] = '사업자등록번호 : ' . $bizNo;
}
if ($mailOrderNo !== '') {
    $legalBits[] = '통신판매업 신고번호 : ' . $mailOrderNo;
}
$contactBits = [];
if ($email !== '') {
    $contactBits[] = '이메일 : ' . $email;
}
if ($privacyOfficer !== '') {
    $contactBits[] = '개인정보보호책임자 : ' . $privacyOfficer;
}
?>
<footer class="site-footer page-footer" aria-label="사이트 정보">
  <div class="site-footer-inner">
    <?php if ($links !== []): ?>
    <nav class="site-footer-nav" aria-label="푸터 메뉴">
      <?php foreach ($links as $link): ?>
      <a href="<?= e((string) ($link['href'] ?? '#')) ?>"><?= e((string) ($link['label'] ?? '')) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <div class="site-footer-grid">
      <div class="site-footer-company">
        <h2 class="site-footer-company-title">회사정보</h2>
        <?php if ($hasCompany): ?>
        <p class="site-footer-company-name"><?= e($companyLine) ?></p>
        <?php if ($address !== ''): ?><p><?= e($address) ?></p><?php endif; ?>
        <?php if ($legalBits !== []): ?><p><?= e(implode(' | ', $legalBits)) ?></p><?php endif; ?>
        <?php if ($contactBits !== []): ?><p><?= e(implode(' | ', $contactBits)) ?></p><?php endif; ?>
        <?php else: ?>
        <p>라벨 디자인부터 인쇄·구매까지 한곳에서 제공하는 라벨업 서비스입니다.</p>
        <?php endif; ?>
        <p class="site-footer-copy">©<?= $year ?> LABEL UP. All Rights Reserved.</p>
        <?php if ($socialLinks !== []): ?>
        <div class="site-footer-social">
          <?php foreach ($socialLinks as $social): ?>
          <a href="<?= e((string) $social['href']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e((string) $social['label']) ?>">
            <?php if ($social['kind'] === 'youtube'): ?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31.5 31.5 0 0 0 0 12a31.5 31.5 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.5 31.5 0 0 0 24 12a31.5 31.5 0 0 0-.5-5.8zM9.8 15.5v-7l6.3 3.5-6.3 3.5z"/></svg>
            <?php elseif ($social['kind'] === 'instagram'): ?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm10 2H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm-5 3.5A4.5 4.5 0 1 1 7.5 12 4.5 4.5 0 0 1 12 7.5zm0 2A2.5 2.5 0 1 0 14.5 12 2.5 2.5 0 0 0 12 9.5zm5.2-3.1a1.1 1.1 0 1 1-1.1 1.1 1.1 1.1 0 0 1 1.1-1.1z"/></svg>
            <?php elseif ($social['kind'] === 'facebook'): ?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h2.6l.4-3H13v-2c0-.6.4-1 1-1z"/></svg>
            <?php else: ?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 15h-2v-6h2zm0-8h-2V7h2z"/></svg>
            <?php endif; ?>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="site-footer-cs">
        <h2 class="site-footer-cs-title">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 1a9 9 0 0 0-9 9v4a3 3 0 0 0 3 3h1v-6H5v-1a7 7 0 0 1 14 0v1h-2v6h1a3 3 0 0 0 3-3v-4a9 9 0 0 0-9-9zm-4 18a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-1H8z"/></svg>
          고객센터
        </h2>
        <div class="site-footer-cs-links">
          <a href="<?= url('faq') ?>">FAQ</a>
          <?php if ($email !== ''): ?>
          <a href="mailto:<?= e($email) ?>">1:1 문의</a>
          <?php endif; ?>
        </div>
        <?php if ($phone !== ''): ?>
        <p class="site-footer-cs-phone">
          <?php if ($phoneHref !== 'tel:'): ?><a href="<?= e($phoneHref) ?>"><?= e($phone) ?></a><?php else: ?><?= e($phone) ?><?php endif; ?>
        </p>
        <?php endif; ?>
        <?php if ($email !== ''): ?>
        <p class="site-footer-cs-meta"><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</footer>
