(function () {
  const tabs = Array.from(document.querySelectorAll('.faq-tab'));
  const groups = Array.from(document.querySelectorAll('.faq-group'));
  const items = Array.from(document.querySelectorAll('.faq-item'));
  const search = document.getElementById('faqSearch');
  const empty = document.querySelector('.faq-empty-search');
  let activeCat = 'all';

  function apply() {
    const q = (search && search.value ? search.value : '').trim().toLowerCase();
    let visible = 0;
    groups.forEach((group) => {
      const slug = group.getAttribute('data-cat') || '';
      const catOk = activeCat === 'all' || slug === activeCat;
      let groupVisible = 0;
      group.querySelectorAll('.faq-item').forEach((item) => {
        const text = (item.getAttribute('data-q') || '') + ' ' + (item.textContent || '').toLowerCase();
        const match = catOk && (q === '' || text.indexOf(q) !== -1);
        item.classList.toggle('is-hidden', !match);
        if (match) {
          groupVisible += 1;
          visible += 1;
        }
      });
      group.classList.toggle('is-hidden', groupVisible === 0);
    });
    if (empty) empty.hidden = visible > 0 || items.length === 0;
  }

  function selectCat(cat) {
    const tab = tabs.find((t) => t.getAttribute('data-cat') === cat) || tabs[0];
    if (!tab) return;
    activeCat = tab.getAttribute('data-cat') || 'all';
    tabs.forEach((t) => t.classList.toggle('is-active', t === tab));
    tab.scrollIntoView({ block: 'nearest', inline: 'center' });
    apply();
  }

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      selectCat(tab.getAttribute('data-cat') || 'all');
      const url = new URL(location.href);
      if (activeCat === 'all') url.searchParams.delete('cat');
      else url.searchParams.set('cat', activeCat);
      history.replaceState(null, '', url.pathname + url.search + url.hash);
    });
  });

  if (search) {
    search.addEventListener('input', apply);
    const form = search.closest('form');
    form?.addEventListener('submit', (e) => {
      e.preventDefault();
      apply();
    });
  }

  const initialCat = new URLSearchParams(location.search).get('cat');
  if (initialCat) {
    selectCat(initialCat);
    const nav = document.querySelector('.faq-tabs');
    if (nav) {
      let covered = 0;
      document.elementsFromPoint(window.innerWidth / 2, 2).forEach((el) => {
        for (let node = el; node && node !== document.body; node = node.parentElement) {
          const pos = getComputedStyle(node).position;
          const rect = node.getBoundingClientRect();
          if ((pos === 'fixed' || pos === 'sticky') && rect.top <= 2 && rect.height < window.innerHeight * 0.4) {
            covered = Math.max(covered, rect.bottom);
          }
        }
      });
      window.scrollTo({ top: Math.max(0, nav.getBoundingClientRect().top + window.scrollY - covered - 8) });
    }
  } else if (search && search.value) {
    apply();
  }
})();
