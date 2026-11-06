<?php
/**
 * 편집기 Shadow DOM 안에 그대로 넣는 메인 사이드바 조각.
 * Shadow 안에서는 :root 가 맞지 않으므로 brand.css 의 변수 블록만 :host 로 옮겨 싣는다.
 */
$brandVars = '';
$brandCss = @file_get_contents(public_path('css/brand.css'));
if (is_string($brandCss) && preg_match('/:root\s*\{[^}]*\}/', $brandCss, $m)) {
    $brandVars = preg_replace('/^:root/', ':host', $m[0]) ?? '';
}
?>
<style><?= $brandVars ?></style>
<link rel="stylesheet" href="<?= css('home.css') ?>">
<style>
:host{all:initial;position:fixed;inset:0;z-index:2000;display:block}
.app{min-height:0;background:none;font-family:Pretendard,"Noto Sans KR","Apple SD Gothic Neo","Malgun Gothic",Arial,sans-serif;font-size:15px;line-height:1.55;color:var(--ink)}
</style>
<div class="app" id="userApp">
<?php require view_path('home/partials/sidebar.php'); ?>
<?php require view_path('home/partials/sidebar-toggle.php'); ?>
</div>
