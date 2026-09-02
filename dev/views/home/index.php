<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title><?= e($pageTitle ?? '라벨업 LABEL UP') ?></title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('home.css') ?>">
</head>
<body>
<div class="app" id="userApp">
<script>try{if(localStorage.getItem('labelup_sidebar_collapsed')==='1')document.getElementById('userApp').classList.add('is-sidebar-collapsed')}catch(e){}</script>
<?php require view_path('home/partials/sidebar.php'); ?>
<?php require view_path('home/partials/sidebar-toggle.php'); ?>
<main class="main">
  <header class="topbar">
    <div class="search"><span>라벨, 규격, 템플릿 검색</span><span class="mag">⌕</span></div>
    <button class="bell" type="button" aria-label="알림"><?php require view_path('home/partials/bell-icon.php'); ?><span class="badge">3</span></button>
    <?php require view_path('home/partials/credit-display.php'); ?>
    <?php require view_path('home/partials/profile-menu.php'); ?>
  </header>

  <div class="content">
    <div class="hero-stage">
      <section class="hero" id="heroSlider">
        <div class="hero-track">
          <?php foreach (($heroSlides ?? []) as $slide): ?>
          <article class="hero-slide">
            <?php if (!empty($slide['link_url'])): ?>
            <a href="<?= e($slide['link_url']) ?>" class="hero-slide-link">
              <img src="<?= e($slide['image_src'] ?? '') ?>" alt="<?= e($slide['alt_text'] ?? '') ?>">
            </a>
            <?php else: ?>
            <img src="<?= e($slide['image_src'] ?? '') ?>" alt="<?= e($slide['alt_text'] ?? '') ?>">
            <?php endif; ?>
          </article>
          <?php endforeach; ?>
        </div>
        <button class="hero-nav hero-prev" type="button" aria-label="이전">‹</button>
        <button class="hero-nav hero-next" type="button" aria-label="다음">›</button>
        <div class="hero-dots">
          <?php foreach (($heroSlides ?? []) as $i => $_): ?>
          <button<?= $i === 0 ? ' class="active"' : '' ?> type="button" aria-label="<?= $i + 1 ?>"></button>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="prompt" id="aiPromptPanel">
        <div class="prompt-head">
          <div class="prompt-title">
            <b>라비와 라벨 만들기</b>
            <span>원하는 라벨을 말로 설명하면 디자인을 바로 제안해요</span>
          </div>
          <span class="prompt-badge">AI DESIGN</span>
        </div>

        <div class="ai-chat-log" id="aiChatLog" hidden aria-live="polite"></div>

        <div class="prompt-composer">
          <div class="ai-attach-preview" id="aiAttachPreview" hidden></div>
          <div class="prompt-row">
            <div class="spark" aria-hidden="true">✦</div>
            <div class="prompt-input">
              <textarea id="promptInput" rows="2" placeholder="어떤 라벨을 만들고 싶으신가요? 예) 카페용 원두 라벨, 친환경 화장품 성분표"<?= empty($authUser) ? ' readonly' : '' ?>></textarea>
              <div class="prompt-helper" id="promptHelper">제품명 · 용도 · 규격 · 분위기 · 넣고 싶은 정보를 자유롭게 입력하세요<?= empty($authUser) ? ' · 이용하려면 로그인이 필요합니다' : '' ?></div>
            </div>
            <div class="prompt-tools">
              <input type="file" id="aiFileInput" hidden multiple accept="image/*,.txt,.csv,.json,.md,.pdf">
              <button class="prompt-tool-btn" id="aiAttachBtn" type="button" title="파일 첨부" aria-label="파일 첨부">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21.44 11.05 12.25 20.24a5.5 5.5 0 0 1-7.78-7.78l9.19-9.19a3.5 3.5 0 1 1 4.95 4.95l-9.2 9.19a1.5 1.5 0 1 1-2.12-2.12l8.49-8.48" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </button>
              <button class="send" id="sendBtn" type="button" disabled title="전송">➤</button>
            </div>
          </div>
        </div>

        <div class="prompt-actions">
          <button class="chip active" type="button" data-text="용도에 맞는 라벨 상품을 하나 추천해줘.">☆ 라벨 추천</button>
          <button class="chip" type="button" data-text="주소 라벨용 용지를 추천해줘.">◎ 주소 라벨</button>
          <button class="chip" type="button" data-text="바코드 라벨 상품을 추천해줘.">▦ 바코드</button>
          <button class="chip" type="button" data-text="원형 네임 스티커 용지를 추천해줘.">○ 원형 스티커</button>
          <button class="chip" type="button" data-text="가격표 라벨 상품을 추천해줘.">◇ 가격표</button>
          <button class="chip" type="button" data-text="카페 원두 라벨에 넣을 커피콩 클립아트를 그려줘.">✦ 클립아트</button>
          <button class="chip" type="button" data-text="핸드메이드 라벨용 하트와 리본 일러스트를 그려줘.">♡ 일러스트</button>
        </div>
      </section>
    </div>

    <section class="features">
      <div class="feature"><div class="fi p">✦</div><div><b>디자인 추천</b><span>용도에 맞는 디자인을 자동으로 추천해드려요</span></div></div>
      <div class="feature"><div class="fi g">⌕</div><div><b>규격 검색</b><span>원하는 용지 규격을 빠르게 찾아보세요</span></div></div>
      <div class="feature"><a class="feature-link" href="<?= url('shop') ?>"><div class="fi b">🛒</div><div><b>라벨지 쇼핑몰</b><span>다양한 규격 라벨지를 바로 구매하세요</span></div></a></div>
      <div class="feature"><div class="fi o">◇</div><div><b>맞춤 제작</b><span>특별한 라벨을 맞춤 제작해보세요</span></div></div>
      <div class="feature"><div class="fi c">⌘</div><div><b>데이터 연동</b><span>엑셀 데이터로 라벨을 자동 생성하세요</span></div></div>
      <div class="feature"><div class="fi r">▶</div><div><b>사용 가이드</b><span>처음이신가요? 가이드로 시작해보세요</span></div></div>
    </section>

    <section class="section">
      <div class="section-head">
        <h2>인기 템플릿</h2>
        <div class="tabs"><button class="active" type="button">전체</button><button type="button">식품</button><button type="button">화장품</button><button type="button">물류</button><button type="button">네임스티커</button><button type="button">가격표</button><button type="button">바코드</button><button type="button">QR</button></div>
        <a class="more" href="#">더보기 →</a>
      </div>
      <div class="cards-wrap">
        <div class="cards">
          <article class="card"><div class="card-img"><img src="<?= asset('tpl-handmade.webp') ?>" alt="핸드메이드 라벨"></div><div class="meta"><span>핸드메이드 라벨</span><small>♡ 1.2k</small></div></article>
          <article class="card"><div class="card-img"><img src="<?= asset('tpl-thanks.webp') ?>" alt="감사 스티커"></div><div class="meta"><span>감사 스티커</span><small>♡ 936</small></div></article>
          <article class="card"><div class="card-img"><img src="<?= asset('tpl-shipping.webp') ?>" alt="배송 라벨"></div><div class="meta"><span>배송 라벨(택배)</span><small>♡ 2.1k</small></div></article>
          <article class="card"><div class="card-img"><img src="<?= asset('tpl-olive.webp') ?>" alt="올리브 오일 라벨"></div><div class="meta"><span>올리브 오일 라벨</span><small>♡ 1.4k</small></div></article>
          <article class="card"><div class="card-img"><img src="<?= asset('tpl-price.webp') ?>" alt="가격표 라벨"></div><div class="meta"><span>가격표 라벨</span><small>♡ 812</small></div></article>
          <article class="card"><div class="card-img"><img src="<?= asset('tpl-coffee.webp') ?>" alt="커피 원두 라벨"></div><div class="meta"><span>커피 원두 라벨</span><small>♡ 1.1k</small></div></article>
        </div>
        <button class="card-arrow" type="button">›</button>
      </div>
    </section>

    <footer class="page-footer">
      <div class="copy">© <?= (int) ($year ?? date('Y')) ?> LABEL UP. All rights reserved.</div>
    </footer>
  </div>
</main>
</div>
<script>
window.LABELUP_HOME = {
  isLoggedIn: <?= !empty($authUser) ? 'true' : 'false' ?>,
  loginUrl: <?= json_encode(url('login') . '?redirect=' . rawurlencode(url('')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  chatApiUrl: <?= json_encode(url('api/ai/chat'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  labiIconUrl: <?= json_encode(asset('labi-icon.png'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
};
</script>
<script src="<?= js('home.js') ?>"></script>
<script src="<?= js('home-ai-chat.js') ?>"></script>
<script src="<?= asset('labi-assistant.js') ?>"></script>
<?php require view_path('home/partials/event-popup.php'); ?>
</body>
</html>
