<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <?php marketing_render_head(); ?>
  <?php seo_render_head($seoPage ?? 'home', array_merge($seoOverride ?? [], ['fallback_title' => $pageTitle ?? '라벨업 LABEL UP'])); ?>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('home.css') ?>">
</head>
<body>
<?php marketing_render_body_start(); ?>
<div class="app" id="userApp">
<script>(function(){var app=document.getElementById('userApp');if(!app)return;var mobile=window.matchMedia('(max-width:1080px)').matches;var collapsed=false;try{collapsed=localStorage.getItem('labelup_sidebar_collapsed')==='1'}catch(e){}if(mobile||collapsed)app.classList.add('is-sidebar-collapsed')})();</script>
<?php require view_path('home/partials/sidebar.php'); ?>
<?php require view_path('home/partials/sidebar-toggle.php'); ?>
<main class="main">
  <header class="topbar">
    <form class="search" action="<?= url('shop/products') ?>" method="get" role="search">
      <input type="search" name="q" value="<?= e(trim((string) ($_GET['q'] ?? ''))) ?>" placeholder="라벨, 규격, 템플릿 검색" aria-label="상품 검색" autocomplete="off">
      <button type="submit" class="mag" aria-label="검색">⌕</button>
    </form>
    <?php require view_path('home/partials/notification-bell.php'); ?>
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
          <div class="prompt-head-actions">
            <?php if (!empty($authUser)): ?>
            <button type="button" class="prompt-reuse-btn" data-labi-prompt-history>내 프롬프트</button>
            <?php endif; ?>
            <span class="prompt-badge">AI DESIGN</span>
          </div>
        </div>

        <div class="ai-chat-log" id="aiChatLog" hidden aria-live="polite"></div>

        <div class="prompt-composer">
          <div class="ai-attach-preview" id="aiAttachPreview" hidden></div>
          <div class="prompt-row">
            <div class="spark" aria-hidden="true">✦</div>
            <div class="prompt-input">
              <textarea id="promptInput" rows="2" placeholder="어떤 라벨을 만들고 싶으신가요? 이미지·엑셀·워드·타사포맷도 첨부할 수 있어요"<?= empty($authUser) ? ' readonly' : '' ?>></textarea>
              <div class="prompt-helper" id="promptHelper">제품명 · 용도 · 규격, 또는 이미지·엑셀·워드·폼텍/아이라벨/애니라벨 파일을 올리면 변환·템플릿까지 만들어 드려요<?= empty($authUser) ? ' · 이용하려면 로그인이 필요합니다' : '' ?></div>
            </div>
            <div class="prompt-tools">
              <input type="file" id="aiFileInput" hidden multiple accept="image/*,.xlsx,.xls,.csv,.tsv,.docx,.doc,.txt,.json,.md,.pdf,.lbl,.idf,.xml,.dgz,.dgf,.fmt,.fdx,.zip">
              <button class="prompt-tool-btn" id="aiAttachBtn" type="button" title="파일 첨부" aria-label="파일 첨부">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21.44 11.05 12.25 20.24a5.5 5.5 0 0 1-7.78-7.78l9.19-9.19a3.5 3.5 0 1 1 4.95 4.95l-9.2 9.19a1.5 1.5 0 1 1-2.12-2.12l8.49-8.48" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </button>
              <button class="send" id="sendBtn" type="button" disabled title="전송">➤</button>
            </div>
          </div>
        </div>

        <div class="prompt-actions">
          <?php
            $examplePrompts = $examplePrompts ?? [];
            if ($examplePrompts === []) {
              $examplePrompts = [
                ['label' => '☆ 라벨 추천', 'prompt_text' => '용도에 맞는 라벨 상품을 하나 추천해줘.'],
                ['label' => '◎ 주소 라벨', 'prompt_text' => '주소 라벨용 용지를 추천해줘.'],
                ['label' => '▦ 바코드', 'prompt_text' => '바코드 라벨 상품을 추천해줘.'],
                ['label' => '○ 원형 스티커', 'prompt_text' => '원형 네임 스티커 용지를 추천해줘.'],
                ['label' => '◇ 가격표', 'prompt_text' => '가격표 라벨 상품을 추천해줘.'],
                ['label' => '✦ 클립아트', 'prompt_text' => '카페 원두 라벨에 넣을 커피콩 클립아트를 그려줘.'],
                ['label' => '♡ 일러스트', 'prompt_text' => '핸드메이드 라벨용 하트와 리본 일러스트를 그려줘.'],
              ];
            }
          ?>
          <?php foreach ($examplePrompts as $i => $prompt): ?>
          <button class="chip<?= $i === 0 ? ' active' : '' ?>" type="button" data-text="<?= e((string) ($prompt['prompt_text'] ?? '')) ?>"><?= e((string) ($prompt['label'] ?? '예시')) ?></button>
          <?php endforeach; ?>
        </div>
      </section>
    </div>

    <section class="features">
      <div class="feature"><div class="fi p">✦</div><div><b>디자인 추천</b><span>용도에 맞는 디자인을 자동으로 추천해드려요</span></div></div>
      <div class="feature"><div class="fi g">⌕</div><div><b>규격 검색</b><span>원하는 용지 규격을 빠르게 찾아보세요</span></div></div>
      <div class="feature"><a class="feature-link" href="<?= url('shop') ?>"><div class="fi b">🛒</div><div><b>라벨지 쇼핑몰</b><span>다양한 규격 라벨지를 바로 구매하세요</span></div></a></div>
      <div class="feature"><a class="feature-link" href="<?= url('about') ?>"><div class="fi o">◇</div><div><b>서비스 소개</b><span>이용 흐름과 사업자정보를 확인해보세요</span></div></a></div>
      <div class="feature"><div class="fi c">⌘</div><div><b>데이터 연동</b><span>엑셀 데이터로 라벨을 자동 생성하세요</span></div></div>
      <div class="feature"><a class="feature-link" href="<?= url('faq') ?>"><div class="fi r">▶</div><div><b>사용 가이드</b><span>처음이신가요? FAQ로 시작해보세요</span></div></a></div>
    </section>

    <?php if (!empty($recentWorks)): ?>
    <section class="section recent-works">
      <div class="section-head">
        <h2>최근 작업</h2>
        <a class="more" href="<?= url('editor/') ?>">편집기 열기 →</a>
      </div>
      <div class="cards-wrap">
        <div class="cards">
          <?php foreach ($recentWorks as $work): ?>
          <article class="card recent-work-card">
            <a class="recent-work-link" href="<?= e((string) $work['editor_url']) ?>">
              <div class="card-img tpl-img recent-work-img">
                <?php if (!empty($work['preview_svg'])): ?>
                <span class="tpl-preview"><?= $work['preview_svg'] ?></span>
                <?php elseif (!empty($work['preview_url'])): ?>
                <img src="<?= e((string) $work['preview_url']) ?>" alt="" loading="lazy">
                <?php else: ?>
                <span class="tpl-fallback"><?= e(mb_substr((string) ($work['title'] ?? '라벨'), 0, 10)) ?></span>
                <?php endif; ?>
              </div>
              <div class="meta">
                <span><?= e((string) $work['title']) ?></span>
                <small><?= e((string) ($work['updated_label'] ?? '')) ?></small>
              </div>
            </a>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <section class="section" data-filter-cards="papers">
      <div class="section-head">
        <h2>인기 용지</h2>
        <?php
          $paperCats = [];
          foreach ($popularPapers ?? [] as $paper) {
              $ck = (string) ($paper['category'] ?? '');
              $cn = (string) ($paper['categoryName'] ?? $ck);
              if ($ck !== '' && !isset($paperCats[$ck])) {
                  $paperCats[$ck] = $cn;
              }
          }
        ?>
        <?php if ($paperCats !== []): ?>
        <div class="tabs" role="tablist" aria-label="용지 분류">
          <button type="button" class="active" data-cat="">전체</button>
          <?php foreach ($paperCats as $ck => $cn): ?>
          <button type="button" data-cat="<?= e($ck) ?>"><?= e($cn) ?></button>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <a class="more" href="<?= url('shop/products') ?>">더보기 →</a>
      </div>
      <div class="cards-wrap cards-slide">
        <?php if (count($popularPapers ?? []) > 1): ?>
        <button class="card-arrow card-arrow-prev" type="button" aria-label="이전">‹</button>
        <?php endif; ?>
        <div class="cards">
          <?php if (empty($popularPapers)): ?>
          <p class="cards-empty">등록된 인기 용지가 없습니다.</p>
          <?php else: ?>
            <?php foreach ($popularPapers as $paper): ?>
            <article class="card" data-category="<?= e((string) ($paper['category'] ?? '')) ?>">
              <a class="card-link" href="<?= e((string) ($paper['url'] ?? url('shop/products'))) ?>">
                <div class="card-img paper-img">
                  <?php if (!empty($paper['thumbUrl'])): ?>
                  <img src="<?= e((string) $paper['thumbUrl']) ?>" alt="<?= e((string) ($paper['name'] ?? '')) ?>" loading="lazy">
                  <?php else: ?>
                  <span class="tpl-fallback"><?= e(mb_substr((string) ($paper['name'] ?? '용지'), 0, 8)) ?></span>
                  <?php endif; ?>
                </div>
                <div class="meta">
                  <span><?= e((string) ($paper['name'] ?? '')) ?></span>
                  <small><?= e((string) (($paper['specLabel'] ?? '') !== '' ? $paper['specLabel'] : ($paper['priceLabel'] ?? ''))) ?></small>
                </div>
              </a>
            </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <?php if (count($popularPapers ?? []) > 1): ?>
        <button class="card-arrow card-arrow-next" type="button" aria-label="다음">›</button>
        <?php endif; ?>
      </div>
    </section>


    <?php render_site_footer(['year' => $year ?? date('Y')]); ?>
  </div>
</main>
</div>
<script>
window.LABELUP_HOME = {
  isLoggedIn: <?= !empty($authUser) ? 'true' : 'false' ?>,
  userId: <?= (int) ($authUser['id'] ?? 0) ?>,
  loginUrl: <?= json_encode(url('login') . '?redirect=' . rawurlencode(url('')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  chatApiUrl: <?= json_encode(url('api/ai/chat'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  examplePromptsUrl: <?= json_encode(url('api/ai/example-prompts') . '?surface=home', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  examplePrompts: <?= json_encode($examplePrompts ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  labiIconUrl: <?= json_encode(asset('labi-icon.png'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  editorUrl: <?= json_encode(url('editor/'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  showAiUsage: <?= !empty($siteRuntime['show_ai_usage']) ? 'true' : 'false' ?>,
  showAiDebug: <?= !empty($siteRuntime['show_ai_debug']) ? 'true' : 'false' ?>,
  siteMode: <?= json_encode((string) ($siteRuntime['mode'] ?? 'production'), JSON_UNESCAPED_UNICODE) ?>
};
</script>
<script src="<?= js('home.js') ?>"></script>
<script src="<?= js('notifications.js') ?>"></script>
<script src="<?= js('home-ai-chat.js') ?>"></script>
<script src="<?= asset('labi-assistant.js') ?>"></script>
<?php require view_path('home/partials/event-popup.php'); ?>
<?php marketing_render_body_end(); ?>
</body>
</html>
