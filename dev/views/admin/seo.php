<?php
$seo = $seo ?? [];
$pages = $pages ?? [];
$v = static fn (string $key, string $default = ''): string => (string) ($seo[$key] ?? $default);
?>
<div class="admin-head">
  <div>
    <h1>SEO 설정</h1>
    <p>사이트 기본 메타, 소셜 공유, robots/sitemap, 페이지별 검색 노출을 관리합니다.</p>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>

<div class="admin-legal-tabs" id="seoTabs">
  <button type="button" class="admin-legal-tab is-active" data-tab="basic">기본 정보</button>
  <button type="button" class="admin-legal-tab" data-tab="social">소셜 · Open Graph</button>
  <button type="button" class="admin-legal-tab" data-tab="search">검색엔진</button>
  <button type="button" class="admin-legal-tab" data-tab="org">구조화 데이터</button>
  <button type="button" class="admin-legal-tab" data-tab="pages">페이지별 SEO</button>
</div>

<form id="seoGlobalForm" class="admin-card admin-seo-form">
  <section class="admin-legal-panel is-active" data-panel="basic">
    <div class="admin-form-grid">
      <label class="admin-field"><span>사이트명</span>
        <input class="admin-input" name="site_name" value="<?= e($v('site_name')) ?>" maxlength="120">
      </label>
      <label class="admin-field"><span>기본 제목</span>
        <input class="admin-input" name="default_title" value="<?= e($v('default_title')) ?>" maxlength="180">
      </label>
      <label class="admin-field"><span>제목 접미사</span>
        <input class="admin-input" name="title_suffix" value="<?= e($v('title_suffix')) ?>" maxlength="80">
        <small>페이지 제목 뒤에 붙는 브랜드 문구</small>
      </label>
      <label class="admin-field"><span>언어/로케일</span>
        <input class="admin-input" name="locale" value="<?= e($v('locale', 'ko_KR')) ?>" maxlength="20">
      </label>
      <label class="admin-field admin-field--full"><span>기본 설명 (meta description)</span>
        <textarea class="admin-input" name="default_description" rows="3" maxlength="500"><?= e($v('default_description')) ?></textarea>
      </label>
      <label class="admin-field admin-field--full"><span>기본 키워드</span>
        <input class="admin-input" name="default_keywords" value="<?= e($v('default_keywords')) ?>" maxlength="500">
        <small>쉼표로 구분</small>
      </label>
      <label class="admin-field admin-field--full"><span>파비콘 URL</span>
        <input class="admin-input" name="favicon_url" value="<?= e($v('favicon_url')) ?>" placeholder="https:// 또는 /assets/favicon.ico">
      </label>
    </div>
  </section>

  <section class="admin-legal-panel" data-panel="social">
    <div class="admin-form-grid">
      <label class="admin-field"><span>OG 사이트명</span>
        <input class="admin-input" name="og_site_name" value="<?= e($v('og_site_name')) ?>">
      </label>
      <label class="admin-field"><span>Twitter 카드</span>
        <select class="admin-input" name="twitter_card">
          <?php foreach (['summary_large_image' => '큰 이미지', 'summary' => '요약'] as $tv => $tl): ?>
          <option value="<?= e($tv) ?>"<?= $v('twitter_card', 'summary_large_image') === $tv ? ' selected' : '' ?>><?= e($tl) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="admin-field admin-field--full"><span>기본 OG 이미지 URL</span>
        <input class="admin-input" name="default_og_image" value="<?= e($v('default_og_image')) ?>" placeholder="https://.../og.png">
        <small>권장 1200×630px. 비우면 페이지별 이미지를 씁니다.</small>
      </label>
      <label class="admin-field"><span>X(Twitter) @계정</span>
        <input class="admin-input" name="twitter_site" value="<?= e($v('twitter_site')) ?>" placeholder="@labelup">
      </label>
      <label class="admin-field"><span>대표 URL (canonical 기준)</span>
        <input class="admin-input" name="canonical_base" value="<?= e($v('canonical_base')) ?>" placeholder="https://www.example.com">
        <small>비우면 서버 APP_URL을 사용합니다.</small>
      </label>
    </div>
  </section>

  <section class="admin-legal-panel" data-panel="search">
    <div class="admin-form-grid">
      <label class="admin-field"><span>기본 robots</span>
        <input class="admin-input" name="robots_default" value="<?= e($v('robots_default', 'index,follow')) ?>">
      </label>
      <label class="admin-field admin-check">
        <input type="checkbox" name="sitemap_enabled" value="1"<?= $v('sitemap_enabled', '1') === '1' ? ' checked' : '' ?>>
        <span>sitemap.xml 자동 생성</span>
      </label>
      <label class="admin-field admin-field--full"><span>robots.txt</span>
        <textarea class="admin-input admin-input--code" name="robots_txt" rows="10"><?= e($v('robots_txt')) ?></textarea>
        <small>공개 주소: <a href="<?= url('robots.txt') ?>" target="_blank" rel="noopener">/robots.txt</a> · 사이트맵: <a href="<?= url('sitemap.xml') ?>" target="_blank" rel="noopener">/sitemap.xml</a></small>
      </label>
    </div>
  </section>

  <section class="admin-legal-panel" data-panel="org">
    <p class="admin-meta-line">검색결과에 노출되는 Organization JSON-LD입니다. 공식 채널 URL은 줄바꿈으로 여러 개 넣을 수 있습니다.</p>
    <div class="admin-form-grid">
      <label class="admin-field admin-check">
        <input type="checkbox" name="jsonld_enabled" value="1"<?= $v('jsonld_enabled', '1') === '1' ? ' checked' : '' ?>>
        <span>구조화 데이터 출력</span>
      </label>
      <label class="admin-field"><span>조직/상호명</span>
        <input class="admin-input" name="org_name" value="<?= e($v('org_name')) ?>">
      </label>
      <label class="admin-field"><span>공식 사이트 URL</span>
        <input class="admin-input" name="org_url" value="<?= e($v('org_url')) ?>">
      </label>
      <label class="admin-field"><span>로고 URL</span>
        <input class="admin-input" name="org_logo" value="<?= e($v('org_logo')) ?>">
      </label>
      <label class="admin-field"><span>대표 전화</span>
        <input class="admin-input" name="org_phone" value="<?= e($v('org_phone')) ?>">
      </label>
      <label class="admin-field"><span>대표 이메일</span>
        <input class="admin-input" name="org_email" value="<?= e($v('org_email')) ?>">
      </label>
      <label class="admin-field admin-field--full"><span>공식 채널 (sameAs)</span>
        <textarea class="admin-input" name="org_same_as" rows="4" placeholder="https://blog.naver.com/&#10;https://www.instagram.com/"><?= e($v('org_same_as')) ?></textarea>
      </label>
    </div>
  </section>

  <div class="admin-head-actions" data-seo-global-actions>
    <button type="submit" class="admin-btn admin-btn--primary">기본 SEO 저장</button>
  </div>
</form>

<section class="admin-legal-panel" data-panel="pages" id="seoPagesPanel">
  <p class="admin-meta-line">각 화면의 제목·설명·색인을 따로 지정합니다. 상품 상세는 상품명이 제목에 자동 반영됩니다.</p>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>페이지</th>
          <th>경로</th>
          <th>제목</th>
          <th>색인</th>
          <th>사이트맵</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pages as $page): ?>
        <tr>
          <td><b><?= e((string) $page['label']) ?></b></td>
          <td><small><?= e((string) $page['path_pattern']) ?></small></td>
          <td><?= e((string) ($page['title'] ?: '—')) ?></td>
          <td><?= !empty($page['noindex']) ? '숨김' : '노출' ?></td>
          <td><?= !empty($page['sitemap_include']) ? '포함' : '제외' ?></td>
          <td><button type="button" class="admin-btn admin-btn--sm js-seo-page" data-page="<?= e(json_encode($page, JSON_UNESCAPED_UNICODE)) ?>">수정</button></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<div class="admin-modal" id="seoPageModal" hidden>
  <div class="admin-modal-backdrop" data-close="seoPageModal"></div>
  <div class="admin-modal-panel admin-modal-panel--wide" role="dialog">
    <div class="admin-modal-head">
      <h2 id="seoPageTitle">페이지 SEO</h2>
      <button type="button" class="admin-modal-close" data-close="seoPageModal" aria-label="닫기">×</button>
    </div>
    <form id="seoPageForm" class="admin-modal-body">
      <input type="hidden" name="page_key">
      <div class="admin-form-grid">
        <label class="admin-field"><span>메뉴명</span><input class="admin-input" name="label" required></label>
        <label class="admin-field"><span>경로 패턴</span><input class="admin-input" name="path_pattern" required></label>
        <label class="admin-field admin-field--full"><span>Title</span><input class="admin-input" name="title" maxlength="180"></label>
        <label class="admin-field admin-field--full"><span>Description</span><textarea class="admin-input" name="description" rows="3"></textarea></label>
        <label class="admin-field admin-field--full"><span>Keywords</span><input class="admin-input" name="keywords"></label>
        <label class="admin-field"><span>OG Title</span><input class="admin-input" name="og_title"></label>
        <label class="admin-field"><span>OG 타입</span>
          <select class="admin-input" name="og_type">
            <option value="website">website</option>
            <option value="product">product</option>
            <option value="article">article</option>
          </select>
        </label>
        <label class="admin-field admin-field--full"><span>OG Description</span><textarea class="admin-input" name="og_description" rows="2"></textarea></label>
        <label class="admin-field admin-field--full"><span>OG 이미지 URL</span><input class="admin-input" name="og_image"></label>
        <label class="admin-field"><span>robots</span><input class="admin-input" name="robots" placeholder="index,follow"></label>
        <label class="admin-field"><span>Canonical 경로</span><input class="admin-input" name="canonical_path" placeholder="/shop"></label>
        <label class="admin-field"><span>사이트맵 주기</span>
          <select class="admin-input" name="sitemap_changefreq">
            <?php foreach (['always','hourly','daily','weekly','monthly','yearly','never'] as $f): ?>
            <option value="<?= $f ?>"><?= $f ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="admin-field"><span>우선순위 (0.0~1.0)</span><input class="admin-input" name="sitemap_priority" type="number" step="0.1" min="0" max="1"></label>
        <label class="admin-field admin-check"><input type="checkbox" name="noindex" value="1"><span>검색 제외 (noindex)</span></label>
        <label class="admin-field admin-check"><input type="checkbox" name="sitemap_include" value="1"><span>사이트맵 포함</span></label>
        <label class="admin-field admin-field--full"><span>추가 head HTML</span>
          <textarea class="admin-input admin-input--code" name="extra_head" rows="4" placeholder="&lt;meta name=&quot;...&quot;&gt;"></textarea>
        </label>
        <input type="hidden" name="sort_order" value="0">
      </div>
      <div class="admin-head-actions" style="margin-top:16px">
        <button type="submit" class="admin-btn admin-btn--primary">페이지 저장</button>
      </div>
    </form>
  </div>
</div>