(function () {
  const form = document.getElementById('partnerDownloadForm');
  const all = document.getElementById('partnerCheckAll');
  const boxes = () => Array.from(document.querySelectorAll('.js-partner-check'));

  all?.addEventListener('change', () => {
    boxes().forEach((box) => {
      box.checked = all.checked;
    });
  });

  document.addEventListener('change', (event) => {
    if (!event.target.classList || !event.target.classList.contains('js-partner-check') || !all) return;
    const list = boxes();
    all.checked = list.length > 0 && list.every((box) => box.checked);
  });

  form?.addEventListener('submit', (event) => {
    const submitter = event.submitter;
    const scope = submitter && submitter.name === 'scope' ? submitter.value : 'selected';
    if (scope === 'selected' && boxes().every((box) => !box.checked)) {
      event.preventDefault();
      window.alert('받을 상품을 선택해주세요.');
    }
  });

  document.addEventListener('click', (event) => {
    const preview = event.target.closest('.js-image-preview');
    if (preview) {
      event.preventDefault();
      const lightbox = document.getElementById('adminLightbox');
      const img = document.getElementById('adminLightboxImg');
      const title = document.getElementById('adminLightboxTitle');
      if (!lightbox || !img) return;
      img.src = preview.dataset.src || '';
      img.alt = preview.dataset.title || '';
      if (title) title.textContent = preview.dataset.title || '이미지 미리보기';
      lightbox.hidden = false;
      return;
    }
    if (event.target.closest('.js-lightbox-close')) {
      const lightbox = document.getElementById('adminLightbox');
      const img = document.getElementById('adminLightboxImg');
      if (lightbox) lightbox.hidden = true;
      if (img) img.src = '';
    }
  });
})();
