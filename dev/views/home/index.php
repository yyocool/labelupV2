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
        <div class="hero-pager">
          <button class="hero-nav hero-prev" type="button" aria-label="이전 슬라이드">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"></path></svg>
          </button>
          <span class="hero-pager-count" data-hero-count>1 / <?= max(1, count($heroSlides ?? [])) ?></span>
          <button class="hero-nav hero-next" type="button" aria-label="다음 슬라이드">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M9 6l6 6-6 6"></path></svg>
          </button>
        </div>
        <div class="hero-dots">
          <?php foreach (($heroSlides ?? []) as $i => $_): ?>
          <button<?= $i === 0 ? ' class="active"' : '' ?> type="button" aria-label="<?= $i + 1 ?>"></button>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="prompt" id="aiPromptPanel">
        <div class="prompt-head">
          <span class="prompt-mark" aria-hidden="true">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"></path></svg>
          </span>
          <div class="prompt-title">
            <b>라비와 라벨 만들기</b>
            <span class="prompt-sub" data-labi-sub data-idle-text="원하는 라벨을 말로 설명해 보세요">원하는 라벨을 말로 설명해 보세요</span>
          </div>
          <div class="prompt-head-actions">
            <?php if (!empty($authUser)): ?>
            <button type="button" class="prompt-reuse-btn" data-labi-prompt-history>내 프롬프트</button>
            <?php endif; ?>
            <button type="button" class="prompt-close" data-labi-close hidden>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>
              대화 닫기
            </button>
          </div>
        </div>

        <div class="ai-chat-wrap">
          <div class="ai-chat-log" id="aiChatLog" hidden aria-live="polite"></div>
        </div>

        <div class="prompt-composer">
          <div class="ai-attach-preview" id="aiAttachPreview" hidden></div>
          <div class="prompt-row">
            <div class="prompt-input">
              <label class="sr-only" for="promptInput">원하는 라벨 설명</label>
              <textarea id="promptInput" rows="1" placeholder="만들고 싶은 라벨을 설명해 주세요"<?= empty($authUser) ? ' readonly' : '' ?>></textarea>
              <div class="prompt-helper" id="promptHelper" hidden><?= empty($authUser) ? '이용하려면 로그인이 필요합니다' : '' ?></div>
            </div>
            <label class="prompt-mode">
              <span class="sr-only">만들기 방식</span>
              <select id="labiModeSelect" data-labi-mode-select>
                <option value="auto">자동</option>
                <option value="chat">대화</option>
                <option value="clipart">클립아트</option>
                <option value="template">템플릿</option>
              </select>
            </label>
            <div class="prompt-tools">
              <input type="file" id="aiFileInput" hidden multiple accept="image/*,.xlsx,.xls,.csv,.tsv,.docx,.doc,.txt,.json,.md,.pdf,.lbl,.idf,.xml,.dgz,.dgf,.fmt,.fdx,.zip">
              <button class="prompt-tool-btn" id="aiAttachBtn" type="button" title="파일 첨부" aria-label="파일 첨부">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M21 11l-8.5 8.5a5 5 0 0 1-7-7L14 4a3.5 3.5 0 0 1 5 5l-8.5 8.5a2 2 0 0 1-3-3L15 7"></path></svg>
              </button>
              <button class="send" id="sendBtn" type="button" disabled title="전송" aria-label="라비에게 보내기">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.5 4.2l17 7.8-17 7.8 2.8-7.8z"></path></svg>
              </button>
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

    <div class="home-split">
      <section class="section home-split__papers" data-filter-cards="papers">
        <div class="section-head">
          <h2>인기 용지</h2>
          <a class="more" href="<?= url('shop/products') ?>">더보기 →</a>
        </div>
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
        <div class="cards-wrap home-paper-grid">
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
        </div>
      </section>

      <section class="section home-split__uses">
        <?php $midBanners = $midBanners ?? []; ?>
        <?php if ($midBanners === []): ?>
        <div class="mid-banner mid-banner--empty">
          <header class="mid-banner-head">
            <h2>용도별 라벨</h2>
          </header>
          <div class="mid-banner-visual mid-banner-visual--empty">등록된 연출 이미지가 없습니다.</div>
        </div>
        <?php else: ?>
        <section class="mid-banner" id="midBannerSlider" aria-label="용도별 라벨 연출 이미지">
          <header class="mid-banner-head">
            <h2>용도별 라벨</h2>
            <div class="mid-banner-badges" role="tablist" aria-label="용도별 라벨 목록">
              <?php foreach ($midBanners as $i => $slide):
                $badge = trim((string) ($slide['title'] ?? ''));
                if ($badge === '') {
                    $badge = '이미지 ' . ($i + 1);
                }
              ?>
              <button type="button" class="mid-banner-badge<?= $i === 0 ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" data-mid-go="<?= (int) $i ?>">
                <?= e($badge) ?>
              </button>
              <?php endforeach; ?>
            </div>
          </header>
          <div class="mid-banner-viewport">
            <div class="mid-banner-track">
              <?php foreach ($midBanners as $slide): ?>
              <article class="mid-banner-slide">
                <div class="mid-banner-visual">
                  <?php if (!empty($slide['link_url'])): ?>
                  <a href="<?= e((string) $slide['link_url']) ?>" class="mid-banner-link">
                    <img src="<?= e((string) ($slide['image_src'] ?? '')) ?>" alt="<?= e((string) ($slide['title'] ?? '용도별 라벨')) ?>">
                  </a>
                  <?php else: ?>
                  <img src="<?= e((string) ($slide['image_src'] ?? '')) ?>" alt="<?= e((string) ($slide['title'] ?? '용도별 라벨')) ?>">
                  <?php endif; ?>
                  <?php
                    $mainText = trim((string) ($slide['main_text'] ?? ''));
                    $subText = trim((string) ($slide['sub_text'] ?? ''));
                  ?>
                  <?php if ($mainText !== '' || $subText !== ''): ?>
                  <?php
                    $alignH = (string) ($slide['text_align_h'] ?? 'left');
                    $alignV = (string) ($slide['text_align_v'] ?? 'middle');
                  ?>
                  <div class="mid-banner-copy mid-banner-copy--h-<?= e($alignH) ?> mid-banner-copy--v-<?= e($alignV) ?>">
                    <?php if ($mainText !== ''): ?>
                    <p class="mid-banner-copy-main" style="<?= e((string) ($slide['main_style'] ?? '')) ?>"><?= e($mainText) ?></p>
                    <?php endif; ?>
                    <?php if ($subText !== ''): ?>
                    <p class="mid-banner-copy-sub" style="<?= e((string) ($slide['sub_style'] ?? '')) ?>"><?= e($subText) ?></p>
                    <?php endif; ?>
                  </div>
                  <?php endif; ?>
                </div>
              </article>
              <?php endforeach; ?>
            </div>
            <?php if (count($midBanners) > 1): ?>
            <div class="mid-banner-overlay">
              <button class="mid-banner-nav mid-banner-prev" type="button" aria-label="이전 이미지">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"></path></svg>
              </button>
              <span class="mid-banner-count" data-mid-count>1 / <?= count($midBanners) ?></span>
              <button class="mid-banner-nav mid-banner-next" type="button" aria-label="다음 이미지">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M9 6l6 6-6 6"></path></svg>
              </button>
            </div>
            <?php endif; ?>
          </div>
        </section>
        <?php endif; ?>
      </section>
    </div>


  </div>
  <?php render_site_footer(['year' => $year ?? date('Y')]); ?>
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
