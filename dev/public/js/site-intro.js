(function () {
  const STORAGE_KEY = 'labelup_site_intro_seen';
  const ANIM_MS = 14500;

  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function youtubeEmbedUrl(id, muted) {
    const params = new URLSearchParams({
      autoplay: '1',
      mute: muted === false ? '0' : '1',
      controls: '0',
      disablekb: '1',
      fs: '0',
      iv_load_policy: '3',
      cc_load_policy: '0',
      rel: '0',
      modestbranding: '1',
      playsinline: '1',
      enablejsapi: '1'
    });
    return 'https://www.youtube.com/embed/' + encodeURIComponent(id) + '?' + params.toString();
  }

  function markSeen() {
    try { localStorage.setItem(STORAGE_KEY, '1'); } catch (e) { /* ignore */ }
  }

  function hasSeen() {
    try { return localStorage.getItem(STORAGE_KEY) === '1'; } catch (e) { return false; }
  }

  function closePlayer(root, timer) {
    if (timer) clearTimeout(timer);
    if (!root) return;
    root.classList.remove('is-open');
    document.body.classList.remove('site-intro-open');
    const video = root.querySelector('video');
    if (video) {
      try { video.pause(); } catch (e) { /* ignore */ }
    }
    setTimeout(function () {
      if (root.parentNode) root.parentNode.removeChild(root);
    }, 220);
  }

  function buildAnimationHtml(cfg) {
    const labi = escapeHtml(cfg.labiIconUrl || '/assets/labi-icon.png');
    const logo = escapeHtml(cfg.logoUrl || '/assets/logo.png');
    return '' +
      '<div class="site-intro-anim" aria-hidden="true">' +
      '  <div class="site-intro-anim__bg"></div>' +
      '  <div class="site-intro-anim__orb site-intro-anim__orb--a"></div>' +
      '  <div class="site-intro-anim__orb site-intro-anim__orb--b"></div>' +
      '  <div class="site-intro-anim__stage">' +
      '    <section class="sia-scene sia-scene--1">' +
      '      <img class="sia-labi" src="' + labi + '" alt="">' +
      '      <img class="sia-logo" src="' + logo + '" alt="LABEL UP">' +
      '      <p class="sia-kicker">with AI</p>' +
      '      <h2 class="sia-title">라벨업</h2>' +
      '      <p class="sia-sub">라벨 디자인부터 출력·구매까지</p>' +
      '    </section>' +
      '    <section class="sia-scene sia-scene--2">' +
      '      <span class="sia-badge">AI DESIGN</span>' +
      '      <h2 class="sia-title">말로 만드는<br>라벨 디자인</h2>' +
      '      <p class="sia-sub">용도만 말하면 라비가 시안을 제안해요</p>' +
      '      <div class="sia-chips"><em>배송라벨</em><em>원형 스티커</em><em>바코드</em></div>' +
      '    </section>' +
      '    <section class="sia-scene sia-scene--3">' +
      '      <div class="sia-steps">' +
      '        <div><b>01</b><span>용지 선택</span></div>' +
      '        <div><b>02</b><span>캔버스 편집</span></div>' +
      '        <div><b>03</b><span>미리보기·출력</span></div>' +
      '      </div>' +
      '      <h2 class="sia-title">편집기에서<br>바로 완성</h2>' +
      '      <p class="sia-sub">텍스트·바코드·데이터를 한 화면에서</p>' +
      '    </section>' +
      '    <section class="sia-scene sia-scene--4">' +
      '      <div class="sia-shop-card" aria-hidden="true"><span></span><span></span><span></span></div>' +
      '      <h2 class="sia-title">라벨지까지<br>한곳에서</h2>' +
      '      <p class="sia-sub">디자인하고, 필요한 용지를 바로 구매하세요</p>' +
      '      <p class="sia-end">LABEL UP</p>' +
      '    </section>' +
      '  </div>' +
      '  <div class="site-intro-anim__progress" aria-hidden="true"><i></i></div>' +
      '</div>';
  }

  function openIntro(cfg, opts) {
    opts = opts || {};
    if (!cfg) return;
    const force = !!opts.force;
    let type = cfg.source_type || 'youtube';
    if (type !== 'upload' && type !== 'animation') type = 'youtube';
    if (type === 'youtube' && !cfg.youtube_id) return;
    if (type === 'upload' && !cfg.video_url) return;
    if (!force && hasSeen()) return;

    const existing = document.getElementById('siteIntroRoot');
    if (existing) existing.remove();

    const skipLabel = cfg.skip_label || '건너뛰기';
    const root = document.createElement('div');
    root.id = 'siteIntroRoot';
    root.className = 'site-intro' + (type === 'animation' ? ' site-intro--anim' : '');
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');
    root.setAttribute('aria-label', '인트로');

    let mediaHtml = '';
    let endTimer = null;
    if (type === 'animation') {
      mediaHtml = buildAnimationHtml(cfg);
    } else if (type === 'youtube') {
      mediaHtml =
        '<div class="site-intro__frame site-intro__frame--youtube">' +
        '<iframe class="site-intro__iframe" src="' + escapeHtml(youtubeEmbedUrl(cfg.youtube_id, true)) + '" ' +
        'title="인트로 영상" allow="autoplay; encrypted-media; picture-in-picture" tabindex="-1"></iframe>' +
        '<div class="site-intro__shield" aria-hidden="true"></div>' +
        '<div class="site-intro__yt-mask site-intro__yt-mask--br" aria-hidden="true"></div>' +
        '<div class="site-intro__yt-mask site-intro__yt-mask--tr" aria-hidden="true"></div>' +
        '</div>';
    } else {
      mediaHtml =
        '<video class="site-intro__video" src="' + escapeHtml(cfg.video_url) + '" autoplay muted playsinline controls></video>';
    }

    const soundBtn = type === 'animation'
      ? ''
      : '<button type="button" class="site-intro__sound" data-intro-sound>소리 켜기</button>';

    root.innerHTML =
      '<div class="site-intro__stage">' + mediaHtml + '</div>' +
      '<div class="site-intro__bar">' +
      soundBtn +
      '  <button type="button" class="site-intro__skip" data-intro-skip>' + escapeHtml(skipLabel) + '</button>' +
      '</div>';

    document.body.appendChild(root);
    document.body.classList.add('site-intro-open');
    requestAnimationFrame(function () { root.classList.add('is-open'); });

    if (type === 'animation') {
      endTimer = setTimeout(function () {
        if (!force) markSeen();
        closePlayer(root, endTimer);
      }, ANIM_MS);
    }

    const video = root.querySelector('.site-intro__video');
    if (video) {
      const tryPlay = function () {
        const p = video.play();
        if (p && typeof p.catch === 'function') p.catch(function () { /* autoplay blocked */ });
      };
      tryPlay();
      video.addEventListener('ended', function () {
        if (!force) markSeen();
        closePlayer(root, endTimer);
      });
    }

    root.addEventListener('click', function (e) {
      const t = e.target;
      if (!t || !t.closest) return;
      if (t.closest('[data-intro-skip]')) {
        if (!force) markSeen();
        closePlayer(root, endTimer);
        return;
      }
      if (t.closest('[data-intro-sound]')) {
        const btn = t.closest('[data-intro-sound]');
        if (video) {
          video.muted = false;
          video.volume = 1;
          video.play().catch(function () { /* ignore */ });
          btn.textContent = '소리 켜짐';
          btn.disabled = true;
        } else {
          const iframe = root.querySelector('.site-intro__iframe');
          if (iframe && cfg.youtube_id) {
            iframe.src = youtubeEmbedUrl(cfg.youtube_id, false);
            btn.textContent = '소리 켜짐';
            btn.disabled = true;
          }
        }
      }
    });

    document.addEventListener('keydown', function onKey(e) {
      if (e.key === 'Escape') {
        document.removeEventListener('keydown', onKey);
        if (!force) markSeen();
        closePlayer(root, endTimer);
      }
    });
  }

  window.LabelUpSiteIntro = {
    open: openIntro,
    hasSeen: hasSeen,
    markSeen: markSeen,
    clearSeen: function () {
      try { localStorage.removeItem(STORAGE_KEY); } catch (e) { /* ignore */ }
    }
  };

  document.addEventListener('DOMContentLoaded', function () {
    const cfg = window.LABELUP_SITE_INTRO;
    if (cfg && cfg.enabled) {
      openIntro(cfg, { force: false });
    }
  });
})();
