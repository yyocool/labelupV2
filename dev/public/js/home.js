(() => {
  const slider = document.getElementById('heroSlider');
  if (!slider) return;

  const track = slider.querySelector('.hero-track');
  const slides = Array.from(slider.querySelectorAll('.hero-slide'));
  const dots = Array.from(slider.querySelectorAll('.hero-dots button'));
  const prev = slider.querySelector('.hero-prev');
  const next = slider.querySelector('.hero-next');

  let current = 0;
  let timer = null;
  const delay = 3800;

  function go(index) {
    current = (index + slides.length) % slides.length;
    track.style.transform = `translate3d(${-current * 100}%,0,0)`;
    dots.forEach((dot, i) => dot.classList.toggle('active', i === current));
  }

  function stop() {
    if (timer !== null) {
      clearInterval(timer);
      timer = null;
    }
  }

  function start() {
    stop();
    timer = setInterval(() => go(current + 1), delay);
  }

  prev?.addEventListener('click', () => { go(current - 1); start(); });
  next?.addEventListener('click', () => { go(current + 1); start(); });
  dots.forEach((dot, i) => dot.addEventListener('click', () => { go(i); start(); }));

  let startX = 0;
  slider.addEventListener('touchstart', (e) => {
    startX = e.changedTouches[0].clientX;
    stop();
  }, { passive: true });
  slider.addEventListener('touchend', (e) => {
    const dx = e.changedTouches[0].clientX - startX;
    if (Math.abs(dx) > 40) go(current + (dx < 0 ? 1 : -1));
    start();
  }, { passive: true });

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) stop(); else start();
  });

  go(0);
  start();

  const promptBox = document.querySelector('.prompt');
  if (promptBox) {
    promptBox.addEventListener('focusin', stop);
    promptBox.addEventListener('focusout', start);
  }
})();

function setupCardSlider(wrap) {
  const scroller = wrap.querySelector('.cards');
  const prev = wrap.querySelector('.card-arrow-prev');
  const next = wrap.querySelector('.card-arrow-next') || (!prev ? wrap.querySelector('.card-arrow') : null);
  if (!scroller) return;

  const step = () => {
    const card = scroller.querySelector('.card:not([hidden])');
    if (!card) return Math.min(320, scroller.clientWidth * 0.85);
    const styles = getComputedStyle(scroller);
    const gap = parseFloat(styles.columnGap || styles.gap) || 11;
    return card.getBoundingClientRect().width + gap;
  };

  const update = () => {
    const max = scroller.scrollWidth - scroller.clientWidth;
    const overflow = max > 4;
    const atStart = scroller.scrollLeft <= 4;
    const atEnd = scroller.scrollLeft >= max - 4;
    [prev, next].forEach((btn) => {
      if (!btn) return;
      btn.hidden = !overflow;
    });
    if (prev) prev.disabled = !overflow || atStart;
    if (next) next.disabled = !overflow || atEnd;
  };

  wrap.resetSlider = () => {
    scroller.scrollTo({ left: 0, behavior: 'auto' });
    update();
  };

  prev?.addEventListener('click', () => {
    scroller.scrollBy({ left: -step(), behavior: 'smooth' });
  });
  next?.addEventListener('click', () => {
    scroller.scrollBy({ left: step(), behavior: 'smooth' });
  });
  scroller.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  update();
}

document.querySelectorAll('.cards-wrap').forEach(setupCardSlider);

document.querySelectorAll('[data-filter-cards]').forEach((section) => {
  const tabs = section.querySelectorAll('.tabs button');
  const cards = section.querySelectorAll('.card[data-category]');
  const wrap = section.querySelector('.cards-wrap');
  if (!tabs.length) return;
  tabs.forEach((btn) => {
    btn.addEventListener('click', () => {
      tabs.forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
      const cat = btn.getAttribute('data-cat') || '';
      cards.forEach((card) => {
        card.hidden = cat !== '' && card.getAttribute('data-category') !== cat;
      });
      wrap?.resetSlider?.();
    });
  });
});

document.querySelectorAll('a.js-home-clipart').forEach((link) => {
  link.addEventListener('click', () => {
    try {
      sessionStorage.setItem('labelup.pendingClipart', JSON.stringify({
        url: link.getAttribute('data-clipart-url') || '',
        title: link.getAttribute('data-clipart-name') || '',
      }));
    } catch (e) { /* ignore */ }
  });
});

(() => {
  const menu = document.getElementById('profileMenu');
  const trigger = document.getElementById('profileTrigger');
  const dropdown = document.getElementById('profileDropdown');
  if (!menu || !trigger || !dropdown) return;

  const close = () => {
    trigger.setAttribute('aria-expanded', 'false');
    dropdown.hidden = true;
  };

  const open = () => {
    trigger.setAttribute('aria-expanded', 'true');
    dropdown.hidden = false;
  };

  const toggle = () => {
    if (dropdown.hidden) open();
    else close();
  };

  trigger.addEventListener('click', (e) => {
    e.stopPropagation();
    toggle();
  });

  document.addEventListener('click', (e) => {
    if (!menu.contains(e.target)) close();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });
})();

(() => {
  const app = document.getElementById('userApp');
  const btn = document.getElementById('sidebarToggle');
  if (!app || !btn) return;

  const STORAGE_KEY = 'labelup_sidebar_collapsed';
  const isMobile = () => window.matchMedia('(max-width:1080px)').matches;

  const setCollapsed = (collapsed, persist = true) => {
    app.classList.toggle('is-sidebar-collapsed', collapsed);
    btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    btn.setAttribute('aria-label', collapsed ? '사이드바 펼치기' : '사이드바 접기');
    btn.title = collapsed ? '사이드바 펼치기' : '사이드바 접기';
    if (persist) {
      try {
        localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
      } catch (_) {}
    }
  };

  try {
    if (localStorage.getItem(STORAGE_KEY) === null && isMobile()) {
      app.classList.add('is-sidebar-collapsed');
    }
  } catch (_) {}

  if (app.classList.contains('is-sidebar-collapsed')) {
    btn.setAttribute('aria-expanded', 'false');
    btn.setAttribute('aria-label', '사이드바 펼치기');
    btn.title = '사이드바 펼치기';
  }

  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    setCollapsed(!app.classList.contains('is-sidebar-collapsed'));
  });

  document.addEventListener('click', (e) => {
    if (!isMobile() || app.classList.contains('is-sidebar-collapsed')) return;
    const sidebar = app.querySelector('.sidebar');
    if (sidebar?.contains(e.target) || btn.contains(e.target)) return;
    setCollapsed(true);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !app.classList.contains('is-sidebar-collapsed')) {
      setCollapsed(true);
    }
  });
})();
