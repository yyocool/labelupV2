(function () {
  const cfg = window.LABELUP_INTRO_ADMIN || {};
  const alertEl = document.getElementById('adminAlert');
  const form = document.getElementById('introForm');
  const typeSel = document.getElementById('introSourceType');
  const pathInput = document.getElementById('introVideoPath');
  const fileInput = document.getElementById('introVideoFile');
  const statusEl = document.getElementById('introUploadStatus');
  const thumb = document.getElementById('introThumb');

  function showAlert(msg, ok) {
    if (!alertEl) return;
    alertEl.textContent = msg;
    alertEl.className = 'admin-alert ' + (ok ? 'is-ok' : 'is-err');
    alertEl.style.display = 'block';
  }

  function syncPanels() {
    const type = typeSel ? typeSel.value : 'youtube';
    document.querySelectorAll('[data-intro-panel]').forEach(function (el) {
      el.hidden = el.getAttribute('data-intro-panel') !== type;
    });
  }

  if (typeSel) {
    typeSel.addEventListener('change', syncPanels);
    syncPanels();
  }

  async function uploadVideo() {
    if (!fileInput || !fileInput.files || !fileInput.files[0]) {
      showAlert('업로드할 동영상 파일을 선택해 주세요.', false);
      return;
    }
    const fd = new FormData();
    fd.append('video', fileInput.files[0]);
    if (statusEl) statusEl.textContent = '업로드 중…';
    try {
      const res = await fetch(cfg.uploadUrl || '/api/admin/intro/upload', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      });
      const json = await res.json().catch(function () { return null; });
      if (!res.ok || !json || json.success === false) {
        throw new Error((json && json.message) || '업로드에 실패했습니다.');
      }
      const data = json.data || {};
      if (pathInput) pathInput.value = data.path || '';
      if (statusEl) statusEl.textContent = '등록됨: ' + (data.path || '');
      if (thumb && data.url) {
        thumb.hidden = false;
        thumb.src = data.url;
      }
      if (cfg.intro) {
        cfg.intro.video_path = data.path || '';
        cfg.intro.video_url = data.url || '';
        cfg.intro.source_type = 'upload';
      }
      showAlert(json.message || '업로드되었습니다.', true);
    } catch (e) {
      if (statusEl) statusEl.textContent = '업로드 실패';
      showAlert(e.message || '업로드에 실패했습니다.', false);
    }
  }

  document.getElementById('introUploadBtn')?.addEventListener('click', uploadVideo);

  document.getElementById('introSaveBtn')?.addEventListener('click', async function () {
    if (!form) return;
    const fd = new FormData(form);
    const payload = {
      is_enabled: form.querySelector('[name="is_enabled"]')?.checked ? 1 : 0,
      source_type: fd.get('source_type') || 'youtube',
      youtube_url: String(fd.get('youtube_url') || ''),
      video_path: String(fd.get('video_path') || ''),
      skip_label: String(fd.get('skip_label') || '건너뛰기')
    };
    try {
      const res = await fetch(cfg.saveUrl || '/api/admin/intro/save', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(payload)
      });
      const json = await res.json().catch(function () { return null; });
      if (!res.ok || !json || json.success === false) {
        throw new Error((json && json.message) || '저장에 실패했습니다.');
      }
      if (json.data && json.data.intro) {
        cfg.intro = Object.assign(cfg.intro || {}, json.data.intro);
      }
      showAlert(json.message || '저장되었습니다.', true);
    } catch (e) {
      showAlert(e.message || '저장에 실패했습니다.', false);
    }
  });

  document.getElementById('introPreviewBtn')?.addEventListener('click', function () {
    if (!window.LabelUpSiteIntro) {
      showAlert('미리보기 스크립트를 불러오지 못했습니다.', false);
      return;
    }
    const type = typeSel ? typeSel.value : 'youtube';
    const youtubeUrl = document.getElementById('introYoutubeUrl')?.value || '';
    let youtubeId = (cfg.intro && cfg.intro.youtube_id) || '';
    const m = youtubeUrl.match(/(?:youtube\.com\/(?:watch\?(?:[^#]*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/i);
    if (m) youtubeId = m[1];
    const videoUrl = (thumb && thumb.src) || (cfg.intro && cfg.intro.video_url) || '';
    const skip = form?.querySelector('[name="skip_label"]')?.value || '건너뛰기';
    if (type === 'youtube' && !youtubeId) {
      showAlert('미리볼 유튜브 URL을 입력해 주세요.', false);
      return;
    }
    if (type === 'upload' && !videoUrl) {
      showAlert('미리볼 업로드 동영상이 없습니다.', false);
      return;
    }
    window.LabelUpSiteIntro.open({
      source_type: type,
      youtube_id: youtubeId,
      video_url: videoUrl,
      skip_label: skip,
      labiIconUrl: cfg.labiIconUrl || '/assets/labi-icon.png',
      logoUrl: cfg.logoUrl || '/assets/logo.png'
    }, { force: true });
  });
})();
