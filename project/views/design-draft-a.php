<?php /* 시안 A · 마케팅 랜딩 — HTML 퍼블리싱 */ ?>
<div class="lu-a">
  <header class="lua-header">
    <div class="lua-header-inner">
      <div class="lua-header-top">
        <a class="lua-logo" href="#">라벨업 <small>LABEL UP</small></a>
        <div class="lua-util">
          <a href="#">로그인</a>
          <a href="#">회원가입</a>
          <a href="#">마이페이지</a>
          <a href="#" class="lua-cart">장바구니 <em>0</em></a>
        </div>
      </div>
      <div class="lua-header-main">
        <nav class="lua-gnb">
          <a href="#">라벨 디자인</a>
          <a href="#">템플릿</a>
          <a href="#">규격 검색</a>
          <a href="#">쇼핑몰</a>
          <a href="#">인쇄 서비스</a>
          <a href="#">자료실</a>
          <a href="#">고객센터</a>
        </nav>
        <label class="lua-search">
          <input type="search" placeholder="제품, 규격, 템플릿 검색" readonly>
          <span class="lua-search-ico" aria-hidden="true">🔍</span>
        </label>
      </div>
    </div>
  </header>

  <section class="lua-hero">
    <div class="lua-hero-copy">
      <p class="lua-eyebrow">더 쉽고, 더 빠르게!</p>
      <h1>원하는 라벨을<br>쉽게 디자인하세요</h1>
      <p class="lua-lead">다양한 템플릿과 편집 도구로 전문가 없이도<br>멋진 라벨을 만들고 바로 출력까지!</p>
      <div class="lua-hero-btns">
        <button type="button" class="lua-btn lua-btn--primary">✏️ 새 디자인 만들기</button>
        <button type="button" class="lua-btn lua-btn--outline">▦ 템플릿 둘러보기</button>
      </div>
      <ul class="lua-hero-feats">
        <li><i class="lua-hf-ico lua-hf-ico--edit"></i>웹에서 간편 편집</li>
        <li><i class="lua-hf-ico lua-hf-ico--pdf"></i>PDF 저장 &amp; 출력</li>
        <li><i class="lua-hf-ico lua-hf-ico--size"></i>다양한 규격 지원</li>
        <li><i class="lua-hf-ico lua-hf-ico--ok"></i>상업적 사용 OK</li>
      </ul>
      <div class="lua-dots" aria-hidden="true"><span class="is-on"></span><span></span><span></span></div>
    </div>
    <div class="lua-hero-visual">
      <img src="<?= asset('img/design/pub/a-hero-visual.png') ?>" alt="라벨 편집기 미리보기" width="368" height="220">
    </div>
  </section>

  <section class="lua-quick">
    <?php
    $quick = array(
      array('새 디자인 만들기', 'pen', false),
      array('템플릿', 'tpl', false),
      array('규격 검색', 'search', false),
      array('AI 디자인 생성', 'spark', true),
      array('인쇄 서비스', 'print', false),
      array('맞춤 제작', 'custom', false),
      array('바코드/QR', 'code', false),
      array('자료실', 'folder', false),
    );
    foreach ($quick as $q): ?>
    <a class="lua-quick-item" href="#">
      <?php if ($q[2]): ?><span class="lua-new">NEW</span><?php endif; ?>
      <span class="lua-quick-ico lua-quick-ico--<?= e($q[1]) ?>" aria-hidden="true"></span>
      <strong><?= e($q[0]) ?></strong>
    </a>
    <?php endforeach; ?>
  </section>

  <section class="lua-section">
    <div class="lua-section-head">
      <h2>인기 템플릿</h2>
      <a href="#">더보기 ›</a>
    </div>
    <div class="lua-tpl-grid">
      <?php
      $tpls = array(
        array('핸드메이드 라벨', '2,345', 1),
        array('제품 라벨', '1,987', 2),
        array('배송 라벨', '2,123', 3),
        array('네임 스티커', '1,765', 4),
        array('가격표', '1,230', 5),
        array('바코드 라벨', '1,654', 6),
      );
      foreach ($tpls as $t): ?>
      <article class="lua-tpl-card">
        <div class="lua-tpl-thumb">
          <img src="<?= asset('img/design/pub/a-tpl-' . $t[2] . '.png') ?>" alt="<?= e($t[0]) ?>">
        </div>
        <div class="lua-tpl-meta">
          <strong><?= e($t[0]) ?></strong>
          <span>♡ <?= e($t[1]) ?></span>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="lua-section">
    <div class="lua-section-head">
      <h2>라벨지 추천 상품</h2>
      <div class="lua-sec-actions">
        <a href="#">더보기 ›</a>
        <button type="button" class="lua-arrow" aria-label="이전">‹</button>
        <button type="button" class="lua-arrow" aria-label="다음">›</button>
      </div>
    </div>
    <div class="lua-prod-row">
      <?php
      $prods = array(
        array('A4 24칸 라벨지', '100매 / 모조지', '6,000원', 1, true),
        array('A4 투명 라벨지', '100매 / 투명 PET', '7,500원', 2, false),
        array('감열 롤 라벨지', '40x30mm / 500매', '8,900원', 3, false),
        array('방수 스티커 라벨', '원형 40mm / 500매', '9,800원', 4, false),
        array('의류용 네임 스티커', '사각 20x40mm / 100매', '5,500원', 5, false),
        array('바코드 롤 라벨지', '100x150mm / 500매', '6,800원', 6, false),
      );
      foreach ($prods as $p): ?>
      <article class="lua-prod-card">
        <?php if ($p[4]): ?><span class="lua-best">BEST</span><?php endif; ?>
        <div class="lua-prod-img">
          <img src="<?= asset('img/design/pub/a-prod-' . $p[3] . '.png') ?>" alt="<?= e($p[0]) ?>">
        </div>
        <strong><?= e($p[0]) ?></strong>
        <small><?= e($p[1]) ?></small>
        <em><?= e($p[2]) ?></em>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="lua-banners">
    <a class="lua-banner lua-banner--ai" href="#">
      <div>
        <strong>AI로 만드는 나만의 라벨</strong>
        <p>텍스트만 입력하면 AI가 알아서 디자인을 완성해드려요!</p>
        <span>AI 디자인 시작하기 ›</span>
      </div>
      <i class="lua-banner-ico lua-banner-ico--ai" aria-hidden="true"></i>
    </a>
    <a class="lua-banner lua-banner--print" href="#">
      <div>
        <strong>빠르고 정확한 인쇄 서비스</strong>
        <p>고품질 인쇄를 합리적인 가격으로 전국 어디든 안전하게 배송!</p>
        <span>인쇄 주문하기 ›</span>
      </div>
      <i class="lua-banner-ico lua-banner-ico--truck" aria-hidden="true"></i>
    </a>
    <a class="lua-banner lua-banner--custom" href="#">
      <div>
        <strong>원하는 규격으로 맞춤 제작</strong>
        <p>특수 사이즈, 특수 재질도 OK! 전문가와 상담 후 제작해보세요.</p>
        <span>맞춤 제작 상담하기 ›</span>
      </div>
      <i class="lua-banner-ico lua-banner-ico--box" aria-hidden="true"></i>
    </a>
  </section>

  <section class="lua-info">
    <div class="lua-info-card">
      <div class="lua-section-head">
        <h3>공지사항</h3>
        <a href="#">더보기 ›</a>
      </div>
      <ul class="lua-notice-list">
        <li><span>5월 라벨지 할인 이벤트 안내</span><time>05.02</time></li>
        <li><span>신규 템플릿 1,000종 업데이트</span><time>04.28</time></li>
        <li><span>인쇄 서비스 배송비 정책 변경</span><time>04.15</time></li>
        <li><span>AI 디자인 베타 오픈 안내</span><time>04.01</time></li>
      </ul>
    </div>
    <div class="lua-info-card">
      <h3>이용 가이드</h3>
      <div class="lua-guide-grid">
        <a href="#"><i>📘</i>디자인 가이드</a>
        <a href="#"><i>🖨</i>출력 가이드</a>
        <a href="#"><i>📏</i>규격 측정 방법</a>
        <a href="#"><i>?</i>자주 묻는 질문</a>
      </div>
    </div>
    <div class="lua-info-card">
      <h3>고객센터</h3>
      <p class="lua-phone">02-1234-5678</p>
      <p class="lua-hours">평일 09:00–18:00 (점심 12:00–13:00)<br>주말·공휴일 휴무</p>
      <div class="lua-cs-btns">
        <button type="button">1:1 문의하기</button>
        <button type="button">원격지원 신청</button>
      </div>
    </div>
  </section>

  <footer class="lua-footer">
    <div class="lua-footer-top">
      <div class="lua-footer-brand">
        <strong>라벨업</strong><small>LABEL UP</small>
        <p>웹에서 쉽게 만들고, 바로 출력하는 라벨 디자인 플랫폼</p>
      </div>
      <div class="lua-footer-cols">
        <div><b>회사소개</b><a href="#">회사 소개</a><a href="#">이용약관</a><a href="#">개인정보처리방침</a></div>
        <div><b>쇼핑몰</b><a href="#">라벨지</a><a href="#">프린터</a><a href="#">액세서리</a></div>
        <div><b>인쇄 서비스</b><a href="#">디지털 인쇄</a><a href="#">맞춤 제작</a><a href="#">견적 문의</a></div>
        <div><b>고객센터</b><a href="#">공지사항</a><a href="#">FAQ</a><a href="#">1:1 문의</a></div>
      </div>
      <div>
        <div class="lua-sns"><span>N</span><span>B</span><span>IG</span><span>YT</span></div>
        <div class="lua-pay">toss payments · KG Inicis</div>
      </div>
    </div>
    <div class="lua-footer-copy">© 2024 LABEL UP. All rights reserved.</div>
  </footer>
</div>
