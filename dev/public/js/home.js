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

document.querySelectorAll('.tabs button').forEach((btn) => {
  btn.addEventListener('click', () => {
    btn.parentElement.querySelectorAll('button').forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');
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
