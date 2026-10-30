(() => {
  const cfg = window.LABELUP_REVIEWS || {};
  const root = document.getElementById('reviews');
  if (!root || !cfg.productId) return;

  const listEl = root.querySelector('.js-review-list');
  const emptyEl = root.querySelector('.js-review-empty');
  const moreEl = root.querySelector('.js-review-more');
  const modal = document.getElementById('shopReviewModal');
  const editorEl = modal?.querySelector('.js-review-editor');
  const starsEl = modal?.querySelector('.js-review-stars');
  const rateText = modal?.querySelector('.js-review-rate-text');
  const submitBtn = modal?.querySelector('.js-review-submit');
  const deleteBtn = modal?.querySelector('.js-review-delete');
  const RATE_TEXT = ['', '별로예요', '그저 그래요', '보통이에요', '좋아요', '최고예요'];
  const SUMMERNOTE = 'https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/';

  let type = 'all';
  let page = 1;
  let rating = 0;
  let editorReady = null;

  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  async function api(url, opts = {}) {
    const res = await fetch(url, { credentials: 'same-origin', ...opts });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      const err = new Error(data.message || '요청 처리 중 오류가 발생했습니다.');
      err.status = res.status;
      throw err;
    }
    return data;
  }

  function reviewItem(r) {
    const badges = [
      r.type === 'photo' ? '<span class="shop-review__badge shop-review__badge--photo">포토</span>' : '',
      r.purchased ? '<span class="shop-review__badge">구매인증</span>' : '',
      r.mine ? '<span class="shop-review__badge shop-review__badge--mine">내 리뷰</span>' : '',
    ].join('');
    return `<li class="shop-review" data-id="${r.id}">
      <div class="shop-review__meta">
        <span class="shop-stars" style="--rate:${Number(r.rating) || 0}" aria-label="${Number(r.rating)}점"></span>
        <b>${esc(r.author)}</b>
        <time>${esc(r.date)}</time>
        ${badges}
      </div>
      <div class="shop-review__body">${r.content}</div>
      <button type="button" class="shop-review__toggle" hidden>더보기</button>
    </li>`;
  }

  function fitCollapse(scope) {
    scope.querySelectorAll('.shop-review').forEach((li) => {
      const body = li.querySelector('.shop-review__body');
      const toggle = li.querySelector('.shop-review__toggle');
      if (!body || !toggle) return;
      const check = () => {
        if (body.scrollHeight > 380 && !li.classList.contains('is-open')) {
          li.classList.add('is-clamped');
          toggle.hidden = false;
        }
      };
      check();
      body.querySelectorAll('img').forEach((img) => img.addEventListener('load', check, { once: true }));
    });
  }

  async function load(reset) {
    if (reset) {
      page = 1;
      listEl.innerHTML = '';
    }
    moreEl.hidden = true;
    try {
      const res = await api(`/api/shop/reviews?product_id=${cfg.productId}&type=${type}&page=${page}`);
      const data = res.data || {};
      const items = data.items || [];
      const frag = document.createElement('ul');
      frag.innerHTML = items.map(reviewItem).join('');
      fitCollapse(frag);
      while (frag.firstChild) listEl.appendChild(frag.firstChild);
      emptyEl.hidden = listEl.children.length > 0;
      moreEl.hidden = !data.has_more;
    } catch (err) {
      emptyEl.hidden = false;
      emptyEl.textContent = err.message;
    }
  }

  root.querySelectorAll('[data-review-tab]').forEach((btn) => {
    btn.addEventListener('click', () => {
      root.querySelectorAll('[data-review-tab]').forEach((b) => {
        b.classList.toggle('is-active', b === btn);
        b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
      });
      type = btn.dataset.reviewTab;
      load(true);
    });
  });

  moreEl.addEventListener('click', () => {
    page += 1;
    load(false);
  });

  listEl.addEventListener('click', (e) => {
    const toggle = e.target.closest('.shop-review__toggle');
    if (toggle) {
      const li = toggle.closest('.shop-review');
      const open = !li.classList.contains('is-open');
      li.classList.toggle('is-open', open);
      li.classList.toggle('is-clamped', !open);
      toggle.textContent = open ? '접기' : '더보기';
      return;
    }
    const img = e.target.closest('.shop-review__body img');
    if (img) openLightbox(img.src);
  });

  function openLightbox(src) {
    const box = document.createElement('div');
    box.className = 'shop-review-lightbox';
    box.innerHTML = `<img src="${esc(src)}" alt=""><button type="button" aria-label="닫기">×</button>`;
    box.addEventListener('click', () => box.remove());
    document.body.appendChild(box);
  }

  function loadScript(src) {
    return new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = src;
      s.onload = resolve;
      s.onerror = () => reject(new Error('에디터를 불러오지 못했습니다.'));
      document.head.appendChild(s);
    });
  }

  function ensureEditor() {
    if (editorReady) return editorReady;
    editorReady = (async () => {
      if (!document.querySelector('link[data-summernote]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = `${SUMMERNOTE}summernote-lite.min.css`;
        link.dataset.summernote = '1';
        document.head.appendChild(link);
      }
      if (!window.jQuery) await loadScript('https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js');
      if (!window.jQuery.fn.summernote) {
        await loadScript(`${SUMMERNOTE}summernote-lite.min.js`);
        await loadScript(`${SUMMERNOTE}lang/summernote-ko-KR.min.js`);
      }
      window.jQuery(editorEl).summernote({
        lang: 'ko-KR',
        height: 220,
        placeholder: '상품을 사용해 본 솔직한 후기를 남겨 주세요. (5자 이상)',
        toolbar: [
          ['font', ['bold', 'underline', 'strikethrough']],
          ['color', ['forecolor']],
          ['para', ['ul', 'ol']],
          ['insert', ['picture']],
        ],
        dialogsInBody: true,
        disableDragAndDrop: false,
        callbacks: {
          onImageUpload: (files) => uploadImages(files),
        },
      });
    })().catch((err) => {
      editorReady = null;
      throw err;
    });
    return editorReady;
  }

  async function uploadImages(files) {
    const list = Array.from(files || []).filter((f) => f.type && f.type.indexOf('image/') === 0);
    const current = (editorCode().match(/<img\b/gi) || []).length;
    if (current + list.length > 10) {
      alert('사진은 최대 10장까지 넣을 수 있습니다.');
      return;
    }
    submitBtn.disabled = true;
    const prev = submitBtn.textContent;
    submitBtn.textContent = '사진 올리는 중…';
    try {
      for (const file of list) {
        const fd = new FormData();
        fd.append('image', file, file.name || 'photo.jpg');
        const res = await api('/api/shop/reviews/upload', { method: 'POST', body: fd });
        window.jQuery(editorEl).summernote('insertImage', res.data.url);
      }
    } catch (err) {
      alert(err.message);
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = prev;
    }
  }

  function editorCode() {
    if (window.jQuery && window.jQuery.fn.summernote && window.jQuery(editorEl).next('.note-editor').length) {
      const code = window.jQuery(editorEl).summernote('code') || '';
      return code === '<p><br></p>' ? '' : code;
    }
    return editorEl.value || '';
  }

  function setRating(n) {
    rating = n;
    starsEl.querySelectorAll('[data-star]').forEach((b) => {
      const on = Number(b.dataset.star) <= n;
      b.classList.toggle('is-on', on);
      b.setAttribute('aria-checked', Number(b.dataset.star) === n ? 'true' : 'false');
    });
    rateText.textContent = n ? `${n}점 · ${RATE_TEXT[n]}` : '별점을 선택해 주세요';
  }

  starsEl?.addEventListener('click', (e) => {
    const b = e.target.closest('[data-star]');
    if (b) setRating(Number(b.dataset.star));
  });

  async function openModal() {
    if (!cfg.loggedIn) {
      if (window.confirm('리뷰는 로그인 후 작성할 수 있습니다. 로그인할까요?')) {
        window.location.href = `/login?redirect=${encodeURIComponent(location.pathname + '#reviews')}`;
      }
      return;
    }
    const mine = cfg.myReview;
    modal.querySelector('#shopReviewModalTitle').textContent = mine ? '내 리뷰 수정' : '리뷰 쓰기';
    submitBtn.textContent = mine ? '수정하기' : '등록하기';
    deleteBtn.hidden = !mine;
    setRating(mine ? Number(mine.rating) : 0);
    modal.hidden = false;
    document.body.classList.add('is-review-modal-open');
    try {
      await ensureEditor();
      window.jQuery(editorEl).summernote('code', mine ? mine.content : '');
    } catch (err) {
      editorEl.value = mine ? mine.content : '';
      alert(err.message);
    }
  }

  function closeModal() {
    modal.hidden = true;
    document.body.classList.remove('is-review-modal-open');
  }

  root.querySelector('.js-review-write')?.addEventListener('click', openModal);
  modal?.querySelectorAll('[data-review-close]').forEach((el) => el.addEventListener('click', closeModal));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal && !modal.hidden && !document.querySelector('.note-modal.open')) closeModal();
  });

  function reloadToReviews() {
    const url = new URL(window.location.href);
    url.hash = 'reviews';
    window.location.replace(url.toString());
    window.location.reload();
  }

  submitBtn?.addEventListener('click', async () => {
    if (!rating) {
      alert('별점을 선택해 주세요.');
      return;
    }
    const content = editorCode();
    submitBtn.disabled = true;
    try {
      const res = await api('/api/shop/reviews/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: cfg.productId, rating, content }),
      });
      alert(res.message || '리뷰가 등록되었습니다.');
      reloadToReviews();
    } catch (err) {
      alert(err.message);
      if (err.status === 401) window.location.href = `/login?redirect=${encodeURIComponent(location.pathname + '#reviews')}`;
    } finally {
      submitBtn.disabled = false;
    }
  });

  deleteBtn?.addEventListener('click', async () => {
    if (!cfg.myReview || !window.confirm('내 리뷰를 삭제할까요?')) return;
    try {
      await api('/api/shop/reviews/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: cfg.myReview.id }),
      });
      reloadToReviews();
    } catch (err) {
      alert(err.message);
    }
  });

  if (cfg.myReview && cfg.myReview.status !== 'approved') {
    const note = document.createElement('p');
    note.className = 'shop-reviews__pending';
    note.textContent = cfg.myReview.status === 'pending'
      ? '작성하신 리뷰는 관리자 확인 후 노출됩니다.'
      : '작성하신 리뷰는 현재 비공개 상태입니다.';
    root.querySelector('.shop-reviews__head')?.after(note);
  }

  load(true);
})();
