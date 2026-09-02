<?php /* 시안 B · 워크스페이스 대시보드 — HTML 퍼블리싱 */ ?>
<div class="lu-b">
  <aside class="lub-rail" aria-label="1차 메뉴">
    <a class="lub-rail-logo" href="#" title="라벨업">
      <strong>라벨업</strong>
      <small>LABEL UP</small>
    </a>
    <nav>
      <?php
      $rail = array(
        array('홈', 'home', true),
        array('라벨 디자인', 'pen', false),
        array('템플릿', 'tpl', false),
        array('규격 검색', 'search', false),
        array('인쇄 서비스', 'cart', false),
        array('맞춤 제작', 'print', false),
        array('자료실', 'folder', false),
        array('고객센터', 'cs', false),
      );
      foreach ($rail as $r): ?>
      <a class="<?= $r[2] ? 'is-active' : '' ?>" href="#" title="<?= e($r[0]) ?>">
        <span class="lub-ico lub-ico--<?= e($r[1]) ?>" aria-hidden="true"></span>
        <em><?= e($r[0]) ?></em>
      </a>
      <?php endforeach; ?>
    </nav>
  </aside>

  <aside class="lub-side" aria-label="홈 메뉴">
    <div class="lub-side-head">
      <strong>홈</strong>
      <button type="button" aria-label="접기">‹</button>
    </div>
    <nav class="lub-side-nav">
      <a class="is-active" href="#"><span class="lub-ico lub-ico--home"></span> 대시보드</a>
      <a href="#"><span class="lub-ico lub-ico--spark"></span> AI 분석 시작하기 <span class="lub-new">NEW</span></a>
      <a href="#"><span class="lub-ico lub-ico--clock"></span> 최근 작업</a>
      <a href="#"><span class="lub-ico lub-ico--star"></span> 즐겨찾기</a>
      <a href="#"><span class="lub-ico lub-ico--megaphone"></span> 공지사항</a>
      <a href="#"><span class="lub-ico lub-ico--gift"></span> 이벤트</a>
    </nav>
    <div class="lub-side-group">바로가기</div>
    <nav class="lub-side-nav lub-side-nav--sub">
      <a href="#"><span class="lub-ico lub-ico--plus"></span> 새 디자인 만들기</a>
      <a href="#"><span class="lub-ico lub-ico--search"></span> 템플릿 검색</a>
      <a href="#"><span class="lub-ico lub-ico--search"></span> 규격 검색</a>
      <a href="#"><span class="lub-ico lub-ico--doc"></span> 주문 내역</a>
      <a href="#"><span class="lub-ico lub-ico--help"></span> 이용 가이드</a>
    </nav>
  </aside>

  <div class="lub-main">
    <header class="lub-top">
      <label class="lub-search">
        <input type="search" placeholder="라벨, 규격, 템플릿 검색" readonly>
        <span class="lub-search-ico" aria-hidden="true"></span>
      </label>
      <div class="lub-top-right">
        <button type="button" class="lub-ai-pill"><span class="lub-ico lub-ico--spark"></span> AI 분석 시작하기</button>
        <button type="button" class="lub-bell" aria-label="알림">🔔<em>3</em></button>
        <button type="button" class="lub-user">
          <span class="lub-avatar"></span>
          김라벨님
          <span class="lub-caret">▾</span>
        </button>
      </div>
    </header>

    <div class="lub-content">
      <section class="lub-hero">
        <div class="lub-hero-copy">
          <p class="lub-eyebrow">AI가 디자인부터 출력까지 한 번에 ✨</p>
          <h1>상상한 라벨,<br>바로 디자인하고 출력까지</h1>
          <p class="lub-lead">AI가 도와주는 라벨 디자인부터 다양한 템플릿, 규격 검색,<br>프리미엄 라벨지 쇼핑, 인쇄 주문까지 올인원으로 해결하세요.</p>
          <div class="lub-hero-btns">
            <button type="button" class="lub-btn lub-btn--dark">새 디자인 만들기 <span class="lub-plus">+</span></button>
            <button type="button" class="lub-btn lub-btn--line">템플릿 둘러보기 <span class="lub-grid-ico" aria-hidden="true"></span></button>
          </div>
          <div class="lub-social-proof">
            <img class="lub-avatars-img" src="<?= asset('img/design/pub/b-avatars.png') ?>" alt="" width="96" height="32">
            <em>10,000+ 사용자가 라벨업을 선택했어요!</em>
          </div>
        </div>
        <div class="lub-hero-visual">
          <img src="<?= asset('img/design/pub/b-hero-visual.png') ?>" alt="라벨 편집기 미리보기" width="350" height="262">
        </div>
      </section>

      <section class="lub-feats">
        <?php
        $feats = array(
          array('AI 라벨 생성', '제품 정보를 입력하면 AI가 라벨을 제안해요', 'spark'),
          array('다양한 템플릿', '10,000+ 개의 전문 템플릿 제공', 'tpl'),
          array('정확한 규격 검색', '폼텍, 아이라벨 등 호환 규격 검색', 'search'),
          array('프리미엄 라벨지', '다양한 재질과 규격의 고품질 라벨지', 'roll'),
          array('빠른 인쇄 서비스', '디자인 업로드 후 바로 인쇄 주문', 'box'),
        );
        foreach ($feats as $f): ?>
        <article class="lub-feat">
          <span class="lub-feat-ico lub-feat-ico--<?= e($f[2]) ?>" aria-hidden="true"></span>
          <div>
            <strong><?= e($f[0]) ?></strong>
            <small><?= e($f[1]) ?></small>
          </div>
        </article>
        <?php endforeach; ?>
      </section>

      <section class="lub-tpl-sec">
        <div class="lub-sec-head">
          <h2>AI 추천 템플릿</h2>
          <div class="lub-tabs">
            <?php foreach (array('전체', '식품', '화장품', '물류', '네임스티커', '가격표', '바코드', 'QR') as $i => $tab): ?>
            <button type="button" class="<?= $i === 0 ? 'is-active' : '' ?>"><?= e($tab) ?></button>
            <?php endforeach; ?>
          </div>
          <a class="lub-more" href="#">더보기 →</a>
        </div>
        <div class="lub-tpl-row">
          <?php
          $cards = array(
            array('핸드메이드 라벨', '1.2k', 1),
            array('감사 스티커', '936', 2),
            array('배송 라벨(택배)', '2.1k', 3),
            array('올리브 오일 라벨', '1.4k', 4),
            array('가격표 라벨', '1.1k', 5),
            array('커피빈 라벨', '980', 6),
          );
          foreach ($cards as $c): ?>
          <article class="lub-tpl-card">
            <div class="lub-tpl-thumb">
              <img src="<?= asset('img/design/pub/b-tpl-' . $c[2] . '.png') ?>" alt="<?= e($c[0]) ?>">
            </div>
            <div class="lub-tpl-meta">
              <strong><?= e($c[0]) ?></strong>
              <span>♡ <?= e($c[1]) ?></span>
            </div>
          </article>
          <?php endforeach; ?>
          <button type="button" class="lub-tpl-next" aria-label="다음">›</button>
        </div>
      </section>

      <section class="lub-stats">
        <?php
        $stats = array(
          array('규격 DB', '10,000+', 'db'),
          array('템플릿', '5,000+', 'tpl'),
          array('인쇄 서비스', '고품질·빠른 배송', 'truck'),
          array('맞춤 제작', '특수 규격 OK', 'box'),
          array('1:1 전문가 상담', '맞춤 가이드', 'cs'),
        );
        foreach ($stats as $s): ?>
        <div class="lub-stat">
          <span class="lub-stat-ico lub-stat-ico--<?= e($s[2]) ?>" aria-hidden="true"></span>
          <div>
            <strong><?= e($s[0]) ?> <?= e($s[1]) ?></strong>
          </div>
        </div>
        <?php endforeach; ?>
      </section>
    </div>
  </div>

  <aside class="lub-right">
    <div class="lub-right-card">
      <h3>바로가기</h3>
      <nav class="lub-right-nav">
        <a href="#"><i class="lub-ico lub-ico--plus"></i> 새 디자인 만들기</a>
        <a href="#"><i class="lub-ico lub-ico--spark"></i> AI 디자인 생성</a>
        <a href="#"><i class="lub-ico lub-ico--search"></i> 규격 검색</a>
        <a href="#"><i class="lub-ico lub-ico--barcode"></i> 바코드 생성</a>
        <a href="#"><i class="lub-ico lub-ico--qr"></i> QR코드 생성</a>
        <a href="#"><i class="lub-ico lub-ico--excel"></i> 엑셀 데이터 연동</a>
      </nav>
    </div>
    <div class="lub-ai-card">
      <div class="lub-ai-card-copy">
        <strong>AI 디자인 생성</strong>
        <p>간단한 정보만 입력하면 AI가 멋진 라벨을 만들어드려요!</p>
        <button type="button">지금 시작하기 →</button>
      </div>
      <img class="lub-ai-bot" src="<?= asset('img/design/pub/b-ai-bot.png') ?>" alt="" width="80" height="80">
    </div>
  </aside>
</div>
