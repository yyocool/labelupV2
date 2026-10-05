<?php
/** @var array<string, bool> $oauthEnabled */
/** @var bool $snsHold */
/** @var bool $socialCompact */
/** @var string $socialVerb */
/** @var string $socialRedirect */
$oauthEnabled = $oauthEnabled ?? ['naver' => false, 'kakao' => false, 'google' => false];
$snsHold = !empty($snsHold);
$snsHoldMessage = \App\Services\SiteModeService::SNS_HOLD_MESSAGE;
$socialCompact = !empty($socialCompact);
$socialVerb = (string) ($socialVerb ?? '로그인');
$socialRedirect = (string) ($socialRedirect ?? '');
$providers = [
    'naver' => '네이버',
    'kakao' => '카카오',
    'google' => '구글',
];
$btnClass = 'login-social-btn' . ($socialCompact ? ' login-social-btn--compact' : '');
foreach ($providers as $key => $label):
    $text = $label . '로 ' . $socialVerb;
    $icon = asset('icon-' . $key . '.svg');
    if ($snsHold):
?>
        <button type="button" class="<?= e($btnClass) ?> js-sns-hold" data-sns-message="<?= e($snsHoldMessage) ?>">
          <img src="<?= e($icon) ?>" alt="">
          <span><?= e($text) ?></span>
        </button>
<?php elseif (!empty($oauthEnabled[$key])):
        $href = url('auth/' . $key);
        if ($socialRedirect !== '') {
            $href .= '?redirect=' . $socialRedirect;
        }
?>
        <a class="<?= e($btnClass) ?>" href="<?= e($href) ?>">
          <img src="<?= e($icon) ?>" alt="">
          <span><?= e($text) ?></span>
        </a>
<?php else: ?>
        <button type="button" class="<?= e($btnClass) ?>" disabled title="키 설정 후 이용 가능">
          <img src="<?= e($icon) ?>" alt="">
          <span><?= e($text) ?></span>
        </button>
<?php endif;
endforeach;
?>
