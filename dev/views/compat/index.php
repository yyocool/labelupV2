<?php
$page = $page ?? ['groups' => [], 'items' => [], 'stats' => ['total' => 0, 'formtec' => 0, 'anylabel' => 0, 'ilabel' => 0], 'example' => null];
$groups = $page['groups'] ?? [];
$items = $page['items'] ?? [];
$stats = $page['stats'] ?? ['total' => 0, 'formtec' => 0, 'anylabel' => 0, 'ilabel' => 0];
$example = $page['example'] ?? null;
$payload = [
    'groups' => $groups,
    'items' => $items,
    'stats' => $stats,
];
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="color-scheme" content="light">
  <meta name="description" content="라벨업 라벨용지의 폼텍·애니라벨·아이라벨 규격 호환코드 대조표">
  <title><?= e($pageTitle ?? '라벨업 호환코드표') ?></title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('compat-codes.css') ?>">
</head>
<body>
  <div class="topbar">
    <div class="wrap">
      <a class="logo-link" href="<?= url('/') ?>" aria-label="LABEL UP 홈">
        <img class="logo-img" src="<?= asset('logo.png') ?>" alt="LABEL UP" width="140" height="38">
      </a>
      <span class="tnote">타사 규격 호환 코드표</span>
    </div>
  </div>

  <section class="hero">
    <div class="wrap">
      <svg class="check fade fade-1" viewBox="0 0 40 40" aria-hidden="true">
        <path d="M8 21l8 8L32 11" fill="none" stroke="var(--brand)" stroke-width="4.6"
              stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <p class="kicker fade fade-2">쓰던 규격 그대로 쓰세요</p>
      <h1 class="fade fade-3">폼텍·애니라벨·아이라벨<em>호환 코드 한눈에</em></h1>
      <p class="sub fade fade-4">라벨업 라벨용지는 국내 주요 3사 규격과 <b>칸 수·라벨 크기·배치가 동일</b>합니다.
        포장의 품번만 검색하면 바로 찾을 수 있습니다.</p>
    </div>
  </section>

  <section class="example">
    <div class="wrap">
      <h2 class="ex-title">쓰던 품번이 <em>이렇게</em> 바뀝니다</h2>
      <p class="ex-sub">같은 규격이라 서식을 다시 만들 필요가 없습니다</p>
      <div class="ex-grid">
        <div class="ex-others">
          <div class="ex-o"><span>폼텍</span><b class="num"><?= e((string) ($example['f'] ?? '3114')) ?></b></div>
          <div class="ex-o"><span>애니라벨</span><b class="num"><?= e((string) ($example['a'] ?? 'V3220')) ?></b></div>
          <div class="ex-o"><span>아이라벨</span><b class="num"><?= e((string) ($example['i'] ?? '224')) ?></b></div>
        </div>
        <div class="ex-eq" aria-hidden="true">
          <svg viewBox="0 0 32 32" fill="none"><path d="M6 16h16M16 8l8 8-8 8" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="ex-up">
          <div class="lbl">LABEL UP</div>
          <div class="pnum num"><?= e((string) ($example['pn'] ?? 'A204-100')) ?></div>
          <div class="dim">
            <?php if ($example): ?>
              <?= e((string) ($example['sub'] ?? '')) ?>
              <?php if (!empty($example['cn']) && $example['cn'] !== '-'): ?> · <?= e((string) $example['cn']) ?>칸<?php endif; ?>
              <?php if (!empty($example['dt'])): ?> · <?= e(str_replace('x', ' × ', (string) $example['dt'])) ?> mm<?php endif; ?>
            <?php else: ?>
              주소용 8칸 · 99.1 × 67.7 mm
            <?php endif; ?>
          </div>
        </div>
      </div>
      <p class="ex-foot">아래에서 쓰시던 품번을 검색해 보세요.</p>
      <div class="stats" style="margin-top:36px">
        <div><strong class="n num"><?= number_format((int) ($stats['total'] ?? 0)) ?></strong><span>라벨업 품목</span></div>
        <div><strong class="num"><?= number_format((int) ($stats['formtec'] ?? 0)) ?></strong><span>폼텍 대응</span></div>
        <div><strong class="num"><?= number_format((int) ($stats['anylabel'] ?? 0)) ?></strong><span>애니라벨 대응</span></div>
        <div><strong class="num"><?= number_format((int) ($stats['ilabel'] ?? 0)) ?></strong><span>아이라벨 대응</span></div>
      </div>
    </div>
  </section>

  <div class="tools" id="tools">
    <div class="wrap">
      <div class="searchbox">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2.2"/>
          <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
        </svg>
        <input id="q" type="search" autocomplete="off" spellcheck="false"
               placeholder="폼텍·애니라벨·아이라벨·라벨업 품번 검색" inputmode="search">
        <button id="clear" type="button" aria-label="검색어 지우기" hidden>&times;</button>
      </div>
      <div class="chips" id="chips" role="group" aria-label="제품군 필터">
        <button type="button" class="chip is-on" data-gid="all">전체</button>
        <?php foreach ($groups as $g): ?>
        <button type="button" class="chip" data-gid="<?= e((string) $g['id']) ?>" style="color:<?= e((string) $g['color']) ?>">
          <span class="dot"></span><?= e((string) $g['name']) ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="wrap">
    <div class="answer" id="answer" hidden>
      <div class="from" id="ans-from"></div>
      <div class="body">
        <div class="to" id="ans-to"></div>
        <div class="meta" id="ans-meta"></div>
      </div>
    </div>
    <p class="count" id="count">전체 <b class="num"><?= number_format((int) ($stats['total'] ?? 0)) ?></b>개 품목 · 3사 규격 대조</p>
  </div>

  <main class="wrap" id="list"></main>

  <section class="how">
    <div class="wrap">
      <h3>이렇게 찾아보세요</h3>
      <ol class="steps">
        <li>쓰시던 라벨지 포장에서 <b>품번</b>을 확인합니다. 폼텍은 <span class="num">3114</span>,
          애니라벨은 <span class="num">V3220</span>, 아이라벨은 <span class="num">224</span>처럼 적혀 있습니다.</li>
        <li>위 검색창에 그 번호를 넣으면 <b>같은 규격의 라벨업 품번</b>이 바로 나옵니다.
          로그인 없이 바로 조회할 수 있습니다.</li>
        <li>품번 뒤 숫자는 <b>낱장 수</b>입니다. <span class="num">A204-20</span>은 20매,
          <span class="num">A204-100</span>은 100매 — 규격은 같습니다.</li>
      </ol>
    </div>
  </section>

  <div class="wrap">
    <footer>
      <b>호환 표기 안내</b> — 위 코드는 타사 제품과 라벨업 제품의 규격이 서로 같음을 안내하기 위한 대조 정보입니다.
      폼텍, 애니라벨, 아이라벨은 각 사의 상표이며 라벨업과 제휴 관계가 없습니다.<br>
      © <?= (int) ($year ?? date('Y')) ?> LABEL UP · <a href="<?= url('/') ?>" style="color:inherit">홈</a>
      · <a href="<?= url('shop') ?>" style="color:inherit">라벨쇼핑</a>
    </footer>
  </div>

  <script>
    window.COMPAT_PAGE = <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  </script>
  <script src="<?= js('compat-codes.js') ?>" defer></script>
</body>
</html>
