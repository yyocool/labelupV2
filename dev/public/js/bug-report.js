(function () {
  const CATEGORIES = [
    ['site', '사이트 전반'],
    ['ai', 'AI'],
    ['editor', '편집기'],
    ['shop', '쇼핑몰·주문'],
    ['account', '계정·결제'],
    ['other', '기타'],
  ];

  function endpoint() {
    const btn = document.getElementById('bugReportOpen');
    return (btn && btn.dataset.endpoint) || '/api/error-reports';
  }

  function loginHref() {
    const next = window.location.pathname + window.location.search;
    return '/login?redirect=' + encodeURIComponent(next || '/');
  }

  function ensureModal() {
    let modal = document.getElementById('bugReportModal');
    if (modal) return modal;
    modal = document.createElement('div');
    modal.id = 'bugReportModal';
    modal.className = 'bug-report-modal';
    modal.hidden = true;
    modal.innerHTML =
      '<div class="bug-report-modal__backdrop" data-bug-close></div>' +
      '<div class="bug-report-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="bugReportTitle">' +
        '<div class="bug-report-modal__head">' +
          '<h2 id="bugReportTitle">오류&amp;제안</h2>' +
          '<button type="button" class="bug-report-modal__close" data-bug-close aria-label="닫기">×</button>' +
        '</div>' +
        '<form id="bugReportForm">' +
          '<p class="bug-report-modal__lead">사용 중 만난 오류나, 더 나아지면 좋겠는 점을 보내 주세요. 확인 후 결과를 안내하고, 도움이 된 내용에는 잉크를 지급합니다.</p>' +
          '<div class="bug-report-kind" role="radiogroup" aria-label="구분">' +
            '<label><input type="radio" name="kind" value="error" checked> 오류</label>' +
            '<label><input type="radio" name="kind" value="suggestion"> 제안</label>' +
          '</div>' +
          '<label class="bug-report-field"><span>분류</span>' +
            '<select name="category" required>' +
              CATEGORIES.map((row) => '<option value="' + row[0] + '">' + row[1] + '</option>').join('') +
            '</select></label>' +
          '<label class="bug-report-field"><span>제목</span>' +
            '<input name="title" type="text" maxlength="200" required placeholder="어떤 오류인지 짧게 적어 주세요"></label>' +
          '<label class="bug-report-field"><span>내용</span>' +
            '<textarea name="content" rows="6" maxlength="4000" required placeholder="어떤 화면에서, 무엇을 했더니, 어떻게 되었는지 적어 주세요."></textarea></label>' +
          '<div class="bug-report-field"><span>이미지</span>' +
            '<div class="bug-report-attach">' +
              '<button type="button" class="bug-report-btn bug-report-btn--ghost" id="bugReportPick">이미지 첨부</button>' +
              '<input id="bugReportFile" type="file" accept="image/png,image/jpeg,image/gif,image/webp" multiple hidden>' +
              '<p class="bug-report-attach__hint">캡처한 화면은 이 창에서 Ctrl+V로 붙여 넣을 수 있습니다. 최대 4장, 장당 4MB.</p>' +
            '</div>' +
            '<div class="bug-report-shots" id="bugReportShots"></div></div>' +
          '<p class="bug-report-modal__page" id="bugReportPage"></p>' +
          '<p class="bug-report-modal__alert" id="bugReportAlert" hidden></p>' +
          '<div class="bug-report-modal__foot">' +
            '<button type="button" class="bug-report-btn bug-report-btn--ghost" data-bug-close>취소</button>' +
            '<button type="submit" class="bug-report-btn bug-report-btn--solid" id="bugReportSubmit">보내기</button>' +
          '</div>' +
        '</form>' +
      '</div>';
    if (!document.getElementById('bugReportShotStyle')) {
      const style = document.createElement('style');
      style.id = 'bugReportShotStyle';
      style.textContent = '.bug-report-kind{display:flex;gap:8px;margin:0 0 12px}.bug-report-kind label{flex:1;display:flex;align-items:center;justify-content:center;gap:6px;height:40px;border:1px solid #e6e1de;border-radius:12px;font-size:13px;font-weight:800;cursor:pointer}.bug-report-kind label:has(input:checked){border-color:#7b2840;color:#7b2840;background:#fdf6f8}.bug-report-attach{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.bug-report-attach__hint{margin:0;color:#8a8d98;font-size:12px;font-weight:500;line-height:1.45}.bug-report-shots{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}.bug-report-shot{position:relative;width:92px;height:72px;margin:0;border:1px solid #e6e1de;border-radius:10px;overflow:hidden;background:#f7f5f4}.bug-report-shot img{width:100%;height:100%;object-fit:cover;display:block}.bug-report-shot button{position:absolute;top:2px;right:2px;width:20px;height:20px;border:0;border-radius:999px;background:rgba(46,42,39,.72);color:#fff;cursor:pointer;line-height:1}';
      document.head.appendChild(style);
    }
    document.body.appendChild(modal);
    modal.addEventListener('click', (e) => {
      if (e.target.closest('[data-bug-close]')) closeModal();
    });
    const form = modal.querySelector('#bugReportForm');
    form?.addEventListener('submit', submitReport);
    form?.querySelectorAll('input[name="kind"]').forEach((input) => {
      input.addEventListener('change', syncKindCopy);
    });
    modal.querySelector('#bugReportPick')?.addEventListener('click', () => {
      modal.querySelector('#bugReportFile')?.click();
    });
    modal.querySelector('#bugReportFile')?.addEventListener('change', (e) => {
      addShotFiles(e.target.files);
      e.target.value = '';
    });
    modal.addEventListener('paste', (e) => {
      if (modal.hidden) return;
      const items = e.clipboardData && e.clipboardData.items;
      if (!items) return;
      const files = [];
      for (let i = 0; i < items.length; i += 1) {
        const item = items[i];
        if (item.kind === 'file' && item.type.indexOf('image/') === 0) {
          const file = item.getAsFile();
          if (file) files.push(file);
        }
      }
      if (!files.length) return;
      e.preventDefault();
      addShotFiles(files);
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !modal.hidden) closeModal();
    });
    return modal;
  }

  function openModal() {
    const modal = ensureModal();
    const page = document.getElementById('bugReportPage');
    const alertEl = document.getElementById('bugReportAlert');
    if (page) page.textContent = '발생 화면 ' + window.location.href;
    if (alertEl) {
      alertEl.hidden = true;
      alertEl.textContent = '';
    }
    modal.hidden = false;
    syncKindCopy();
    modal.querySelector('input[name="title"]')?.focus();
  }

  function syncKindCopy() {
    const form = document.getElementById('bugReportForm');
    if (!form) return;
    const kind = form.kind && form.kind.value === 'suggestion' ? 'suggestion' : 'error';
    const title = form.querySelector('input[name="title"]');
    const content = form.querySelector('textarea[name="content"]');
    if (title) {
      title.placeholder = kind === 'suggestion'
        ? '어떤 제안인지 짧게 적어 주세요'
        : '어떤 오류인지 짧게 적어 주세요';
    }
    if (content) {
      content.placeholder = kind === 'suggestion'
        ? '이렇게 바뀌면 좋겠습니다. 이유와 기대하는 점을 적어 주세요.'
        : '어떤 화면에서, 무엇을 했더니, 어떻게 되었는지 적어 주세요.';
    }
  }

  function closeModal() {
    const modal = document.getElementById('bugReportModal');
    if (modal) modal.hidden = true;
    clearShots();
  }

  const shots = [];
  const MAX_SHOTS = 4;
  const MAX_BYTES = 4 * 1024 * 1024;

  function clearShots() {
    shots.forEach((shot) => URL.revokeObjectURL(shot.url));
    shots.length = 0;
    renderShots();
  }

  function addShotFiles(fileList) {
    const incoming = Array.from(fileList || []);
    for (let i = 0; i < incoming.length; i += 1) {
      const file = incoming[i];
      if (shots.length >= MAX_SHOTS) {
        showAlert('이미지는 최대 4장까지 첨부할 수 있습니다.');
        break;
      }
      const type = file.type || '';
      if (type.indexOf('image/') !== 0) {
        showAlert('이미지 파일만 첨부할 수 있습니다.');
        continue;
      }
      if (file.size > MAX_BYTES) {
        showAlert('이미지는 한 장당 4MB까지 첨부할 수 있습니다.');
        continue;
      }
      const named = file.name
        ? file
        : new File([file], 'capture-' + Date.now() + '.png', { type: type || 'image/png' });
      shots.push({ file: named, url: URL.createObjectURL(named) });
    }
    renderShots();
  }

  function renderShots() {
    const box = document.getElementById('bugReportShots');
    if (!box) return;
    box.innerHTML = shots.map((shot, index) => (
      '<figure class="bug-report-shot">' +
        '<img src="' + shot.url + '" alt="첨부 이미지 ' + (index + 1) + '">' +
        '<button type="button" data-shot-remove="' + index + '" aria-label="이미지 삭제">×</button>' +
      '</figure>'
    )).join('');
    box.querySelectorAll('[data-shot-remove]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const index = Number(btn.getAttribute('data-shot-remove'));
        const shot = shots[index];
        if (shot) URL.revokeObjectURL(shot.url);
        shots.splice(index, 1);
        renderShots();
      });
    });
  }

  function showAlert(message, ok) {
    const alertEl = document.getElementById('bugReportAlert');
    if (!alertEl) return;
    alertEl.hidden = false;
    alertEl.textContent = message;
    alertEl.classList.toggle('is-ok', !!ok);
  }

  async function submitReport(e) {
    e.preventDefault();
    const form = e.currentTarget;
    const submit = document.getElementById('bugReportSubmit');
    if (submit) submit.disabled = true;
    const body = new FormData();
    body.append('kind', form.kind && form.kind.value === 'suggestion' ? 'suggestion' : 'error');
    body.append('category', form.category.value);
    body.append('title', form.title.value);
    body.append('content', form.content.value);
    body.append('page_url', window.location.href);
    shots.forEach((shot) => body.append('images[]', shot.file, shot.file.name || 'capture.png'));
    try {
      const res = await fetch(endpoint(), {
        method: 'POST',
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
        body: body,
      });
      const data = await res.json().catch(() => ({}));
      if (res.status === 401) {
        showAlert('로그인 후 오류나 제안을 보낼 수 있습니다.');
        window.setTimeout(() => { window.location.href = loginHref(); }, 700);
        return;
      }
      if (!res.ok || data.success === false) {
        throw new Error(data.message || '보내지 못했습니다.');
      }
      form.reset();
      syncKindCopy();
      showAlert(data.message || '접수되었습니다. 확인 후 결과를 안내합니다.', true);
      window.setTimeout(closeModal, 900);
    } catch (err) {
      showAlert(err.message || '보내지 못했습니다.');
    } finally {
      if (submit) submit.disabled = false;
    }
  }

  document.getElementById('bugReportOpen')?.addEventListener('click', openModal);

  function mountEditorButton() {
    const chip = document.getElementById('lu-credit-chip');
    if (!chip || document.getElementById('edBugReport')) return;
    if (!document.getElementById('edBugReportStyle')) {
      const style = document.createElement('style');
      style.id = 'edBugReportStyle';
      style.textContent = '.ed-bug-report{position:absolute;right:206px;bottom:28px;z-index:340;height:36px;padding:0 12px;border:1px solid #e8d4db;border-radius:999px;background:#fff;color:#2e2a27;font-weight:800;cursor:pointer;white-space:nowrap}.ed.is-mobile .ed-bug-report{display:none}';
      document.head.appendChild(style);
    }
    const btn = document.createElement('button');
    btn.id = 'edBugReport';
    btn.type = 'button';
    btn.className = 'ed-bug-report';
    btn.textContent = '오류&제안';
    btn.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      openModal();
    });
    chip.insertAdjacentElement('afterend', btn);
  }

  if (location.pathname.indexOf('/editor') !== -1) {
    const editorWatch = new MutationObserver(mountEditorButton);
    editorWatch.observe(document.documentElement, { childList: true, subtree: true });
    mountEditorButton();
  }
  window.LabelUpBugReport = { open: openModal };
})();
