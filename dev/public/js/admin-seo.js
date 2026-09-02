(function () {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  function bindTabs(navId) {
    const nav = document.getElementById(navId);
    if (!nav) return;
    nav.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-tab]');
      if (!btn) return;
      const tab = btn.getAttribute('data-tab');
      $$('.admin-legal-tab', nav).forEach((el) => el.classList.toggle('is-active', el === btn));
      $$('[data-panel]').forEach((el) => el.classList.toggle('is-active', el.getAttribute('data-panel') === tab));
      $$('[data-seo-global-actions], [data-mkt-actions]').forEach((el) => {
        el.style.display = (tab === 'pages' || tab === 'files') ? 'none' : '';
      });
    });
  }

  function formObject(form) {
    const data = {};
    new FormData(form).forEach((value, key) => {
      data[key] = value;
    });
    $$('input[type="checkbox"]', form).forEach((el) => {
      data[el.name] = el.checked ? 1 : 0;
    });
    return data;
  }

  function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.hidden = false;
  }
  function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.hidden = true;
  }

  document.addEventListener('click', (e) => {
    const closer = e.target.closest('[data-close]');
    if (closer) closeModal(closer.getAttribute('data-close'));
  });

  bindTabs('seoTabs');
  bindTabs('mktTabs');

  const seoForm = document.getElementById('seoGlobalForm');
  if (seoForm) {
    seoForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = seoForm.querySelector('[type="submit"]');
      if (btn) btn.disabled = true;
      try {
        await AdminAPI.post('/api/admin/seo/save', formObject(seoForm));
        showAdminAlert('SEO 설정을 저장했습니다.');
      } catch (err) {
        showAdminAlert(err.message, 'error');
      } finally {
        if (btn) btn.disabled = false;
      }
    });
  }

  const pageModal = document.getElementById('seoPageModal');
  const pageForm = document.getElementById('seoPageForm');
  $$('.js-seo-page').forEach((btn) => {
    btn.addEventListener('click', () => {
      const page = JSON.parse(btn.getAttribute('data-page') || '{}');
      if (!pageForm) return;
      pageForm.page_key.value = page.page_key || '';
      pageForm.label.value = page.label || '';
      pageForm.path_pattern.value = page.path_pattern || '';
      pageForm.title.value = page.title || '';
      pageForm.description.value = page.description || '';
      pageForm.keywords.value = page.keywords || '';
      pageForm.og_title.value = page.og_title || '';
      pageForm.og_description.value = page.og_description || '';
      pageForm.og_image.value = page.og_image || '';
      pageForm.og_type.value = page.og_type || 'website';
      pageForm.robots.value = page.robots || '';
      pageForm.canonical_path.value = page.canonical_path || '';
      pageForm.sitemap_changefreq.value = page.sitemap_changefreq || 'weekly';
      pageForm.sitemap_priority.value = page.sitemap_priority || '0.5';
      pageForm.extra_head.value = page.extra_head || '';
      pageForm.sort_order.value = page.sort_order || 0;
      pageForm.noindex.checked = Number(page.noindex) === 1;
      pageForm.sitemap_include.checked = Number(page.sitemap_include) === 1;
      const title = document.getElementById('seoPageTitle');
      if (title) title.textContent = (page.label || '페이지') + ' SEO';
      openModal('seoPageModal');
    });
  });
  if (pageForm) {
    pageForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        await AdminAPI.post('/api/admin/seo/page', formObject(pageForm));
        showAdminAlert('페이지 SEO를 저장했습니다.');
        closeModal('seoPageModal');
        window.location.reload();
      } catch (err) {
        showAdminAlert(err.message, 'error');
      }
    });
  }

  const mktForm = document.getElementById('mktForm');
  if (mktForm) {
    mktForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = mktForm.querySelector('[type="submit"]');
      if (btn) btn.disabled = true;
      try {
        const payload = formObject(mktForm);
        const ads = document.getElementById('mktAdsForm');
        if (ads) Object.assign(payload, formObject(ads));
        await AdminAPI.post('/api/admin/marketing/save', payload);
        showAdminAlert('광고 스크립트를 저장했습니다.');
      } catch (err) {
        showAdminAlert(err.message, 'error');
      } finally {
        if (btn) btn.disabled = false;
      }
    });
  }

  const adsForm = document.getElementById('mktAdsForm');
  if (adsForm) {
    adsForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const payload = mktForm ? formObject(mktForm) : {};
        Object.assign(payload, formObject(adsForm));
        await AdminAPI.post('/api/admin/marketing/save', payload);
        showAdminAlert('광고 파일을 저장했습니다.');
      } catch (err) {
        showAdminAlert(err.message, 'error');
      }
    });
  }

  const fileForm = document.getElementById('mktFileForm');
  if (fileForm) {
    fileForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        await AdminAPI.post('/api/admin/marketing/file', formObject(fileForm));
        showAdminAlert('인증 파일을 저장했습니다.');
        window.location.reload();
      } catch (err) {
        showAdminAlert(err.message, 'error');
      }
    });
  }

  $$('.js-mkt-file-del').forEach((btn) => {
    btn.addEventListener('click', async () => {
      if (!confirm('이 인증 파일을 삭제할까요?')) return;
      try {
        await AdminAPI.post('/api/admin/marketing/file/delete', { id: Number(btn.dataset.id) });
        showAdminAlert('삭제했습니다.');
        window.location.reload();
      } catch (err) {
        showAdminAlert(err.message, 'error');
      }
    });
  });
})();
