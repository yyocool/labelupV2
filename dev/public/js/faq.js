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

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      activeCat = tab.getAttribute('data-cat') || 'all';
      tabs.forEach((t) => t.classList.toggle('is-active', t === tab));
      apply();
    });
  });

  if (search) {
    search.addEventListener('input', apply);
    const form = search.closest('form');
    form?.addEventListener('submit', (e) => {
      e.preventDefault();
      apply();
    });
    if (search.value) apply();
  }
})();
