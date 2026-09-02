/**
 * LabelUp editor tutorial — virtual cursor, spotlight, controller
 * (custom engine; no Driver.js to avoid Blazor DOM conflicts)
 */
(function () {
  'use strict';

  var SPEEDS = [0.75, 1, 1.25, 1.5];
  var STORAGE_SKIP = 'lu-ed-tutorial-skip';
  /** 전체 진행 배율 (클수록 느림). 요청: 기존 대비 절반 속도 → 2 */
  var PACE = 2;

  var STEPS = [
    {
      id: 'welcome',
      selector: '[data-tut="topbar"]',
      title: '라벨 편집기에 오신 걸 환영해요',
      description: '상단 바에서 제목 수정, 실행 취소, 줌, 저장·미리보기·출력을 할 수 있어요.',
      effect: '편집기 전체 흐름을 한눈에 파악합니다.',
      speech: '라벨 편집기에 오신 걸 환영해요. 상단 바부터 살펴볼게요.',
      wait: 2800
    },
    {
      id: 'tools',
      selector: '[data-tut="float-tools"]',
      title: '플로팅 도구바',
      description: '선택·텍스트·이미지·도형 도구가 모여 있어요. 드래그하면 모서리에 자석처럼 붙습니다.',
      effect: '자주 쓰는 도구에 빠르게 접근합니다.',
      speech: '왼쪽의 플로팅 도구바예요. 드래그해서 원하는 모서리로 옮길 수 있어요.',
      wait: 3000,
      cursor: true
    },
    {
      id: 'text-tool',
      selector: '[data-tut="tool-text"]',
      title: '텍스트 도구',
      description: '텍스트 도구를 선택한 뒤 캔버스를 클릭하면 글자를 넣을 수 있어요.',
      effect: '라벨 문구를 바로 추가합니다.',
      speech: '텍스트 도구를 눌러 볼게요.',
      wait: 2200,
      cursor: true,
      click: true,
      action: 'setTool:Text'
    },
    {
      id: 'canvas',
      selector: '[data-tut="canvas"]',
      title: '디자인 캔버스',
      description: '가운데 영역이 실제 라벨 작업 공간입니다. 객체를 드래그해 배치하세요.',
      effect: '라벨 레이아웃을 자유롭게 편집합니다.',
      speech: '가운데 캔버스에서 라벨을 디자인합니다.',
      wait: 2800,
      cursor: true
    },
    {
      id: 'presets',
      selector: '[data-tut="presets"]',
      title: '라벨 규격',
      description: '자주 쓰는 라벨 크기를 여기서 바로 바꿀 수 있어요.',
      effect: '용지에 맞는 규격으로 빠르게 전환합니다.',
      speech: '규격 메뉴에서 라벨 크기를 바꿀 수 있어요.',
      wait: 2400,
      cursor: true
    },
    {
      id: 'props',
      selector: '[data-tut="props"]',
      title: '속성 · 레이어 패널',
      description: '선택한 객체의 위치·크기·색상·텍스트를 수정하고, 레이어 순서를 관리해요.',
      effect: '정밀한 스타일 조정이 가능합니다.',
      speech: '오른쪽 속성 패널에서 세부 값을 수정하세요.',
      wait: 3000,
      cursor: true,
      action: 'expandProps'
    },
    {
      id: 'layers',
      selector: '[data-tut="tab-layers"]',
      title: '레이어 탭',
      description: '레이어 탭으로 전환하면 객체 목록을 보고 선택할 수 있어요.',
      effect: '겹친 객체도 쉽게 고를 수 있습니다.',
      speech: '레이어 탭을 열어볼게요.',
      wait: 2200,
      cursor: true,
      click: true,
      action: 'propsTab:layers'
    },
    {
      id: 'preview',
      selector: '[data-tut="preview"]',
      title: '시트 미리보기',
      description: '지금 편집 중인 라벨이 용지 시트에 어떻게 배치되는지, 실제 디자인 그대로 보여줍니다.',
      effect: '출력 전 배치와 디자인을 검증합니다.',
      speech: '미리보기에는 실제 라벨 레이아웃이 그대로 표시됩니다.',
      wait: 3200,
      cursor: true,
      action: 'expandPreview'
    },
    {
      id: 'import-fab',
      selector: '[data-tut="import-fab"]',
      title: '가져오기 열기',
      description: '우측 하단 가져오기를 누르면 부채 메뉴가 펼쳐지고, 원하는 종류를 고를 수 있어요.',
      effect: '규격·시안을 빠르게 시작할 수 있습니다.',
      speech: '가져오기 버튼을 눌러 메뉴를 펼쳐볼게요.',
      wait: 2400,
      cursor: true,
      click: true,
      action: 'openImport'
    },
    {
      id: 'import-head',
      selector: '[data-tut="import-head"]',
      title: '가져오기 창',
      description: '라벨을 고르고 디자인을 선택하면 편집 화면으로 이어집니다.',
      effect: '가져오기 전체 흐름을 이해합니다.',
      speech: '가져오기 창의 안내 문구예요.',
      wait: 2600,
      cursor: true,
      action: 'openImport'
    },
    {
      id: 'import-tabs',
      selector: '[data-tut="import-tabs"]',
      title: '가져오기 탭 메뉴',
      description: '내디자인, 타사포맷, 템플릿, 라벨, 태그, 라비로 나뉘어 있어요.',
      effect: '목적에 맞는 가져오기 경로를 고릅니다.',
      speech: '위쪽 탭으로 가져오기 종류를 바꿉니다.',
      wait: 2800,
      cursor: true,
      action: 'openImport'
    },
    {
      id: 'import-label',
      selector: '[data-tut="import-tab-label"]',
      title: '라벨 탭',
      description: 'A4·제트·더롤 등 카테고리에서 빈 라벨 또는 디자인 라벨을 고를 수 있어요.',
      effect: '가장 많이 쓰는 라벨 규격을 불러옵니다.',
      speech: '라벨 탭입니다. 규격 카드를 누르면 편집기에 적용됩니다.',
      wait: 2800,
      cursor: true,
      click: true,
      action: 'importTab:label'
    },
    {
      id: 'import-cats',
      selector: '[data-tut="import-cats"]',
      title: '라벨 카테고리',
      description: 'A4 라벨, 제트라벨, 더롤라벨, A3 라벨처럼 용지·브랜드별로 나뉩니다.',
      effect: '원하는 제품군으로 목록을 좁힙니다.',
      speech: '카테고리 칩으로 라벨 종류를 고르세요.',
      wait: 2800,
      cursor: true,
      action: 'importTab:label'
    },
    {
      id: 'import-subtype',
      selector: '[data-tut="import-subtype-blank"]',
      title: '빈 라벨 / 디자인 라벨',
      description: '빈(Blank)은 규격만, 디자인 라벨은 시안이 포함된 템플릿입니다.',
      effect: '처음부터 그릴지, 시안으로 시작할지 선택합니다.',
      speech: '빈 라벨과 디자인 라벨을 전환할 수 있어요.',
      wait: 2800,
      cursor: true,
      action: 'importTab:label'
    },
    {
      id: 'import-search',
      selector: '[data-tut="import-search"]',
      title: '검색 · 보기 옵션',
      description: '규격코드 검색, 개수별 보기, 규격/크기별 정렬을 지원합니다.',
      effect: '긴 목록에서도 원하는 규격을 빠르게 찾습니다.',
      speech: '검색창에 규격코드를 입력해 찾아보세요.',
      wait: 2800,
      cursor: true,
      action: 'importTab:label'
    },
    {
      id: 'import-grid',
      selector: '[data-tut="import-grid"]',
      title: '규격 카드 그리드',
      description: '장당 수량, 사이즈, 코드가 표시됩니다. 카드를 누르면 해당 규격으로 편집이 시작됩니다.',
      effect: '선택한 규격이 캔버스에 바로 적용됩니다.',
      speech: '카드 하나를 고르면 편집기로 이동합니다.',
      wait: 3000,
      cursor: true,
      action: 'importTab:label'
    },
    {
      id: 'import-tag',
      selector: '[data-tut="import-tab-tag"]',
      title: '태그 탭',
      description: '행택·폴드택 등 태그 규격과 디자인 태그를 불러올 수 있어요. 구성은 라벨 탭과 비슷합니다.',
      effect: '태그용 용지도 같은 방식으로 시작합니다.',
      speech: '태그 탭으로 전환해 볼게요.',
      wait: 2800,
      cursor: true,
      click: true,
      action: 'importTab:tag'
    },
    {
      id: 'import-tag-panel',
      selector: '[data-tut="import-panel-tag"]',
      title: '태그 카탈로그',
      description: 'A4 태그, 제트태그, 더롤태그 카테고리와 빈/디자인 서브탭이 제공됩니다.',
      effect: '태그 작업에 맞는 규격을 고릅니다.',
      speech: '태그도 카테고리와 그리드로 고를 수 있어요.',
      wait: 2800,
      cursor: true,
      action: 'importTab:tag'
    },
    {
      id: 'import-template',
      selector: '[data-tut="import-tab-template"]',
      title: '템플릿 탭',
      description: '키워드·해시태그로 완성 시안을 찾아 바로 편집할 수 있어요.',
      effect: '디자인 시간을 크게 줄입니다.',
      speech: '템플릿 탭을 열어볼게요.',
      wait: 2600,
      cursor: true,
      click: true,
      action: 'importTab:template'
    },
    {
      id: 'import-template-tags',
      selector: '[data-tut="import-template-tags"]',
      title: '템플릿 해시태그',
      description: '감사, 선물, 카페 등 태그로 템플릿을 필터링합니다.',
      effect: '테마에 맞는 시안만 모아 봅니다.',
      speech: '해시태그로 템플릿을 걸러보세요.',
      wait: 2800,
      cursor: true,
      action: 'importTab:template'
    },
    {
      id: 'import-template-panel',
      selector: '[data-tut="import-panel-template"]',
      title: '템플릿 목록',
      description: '썸네일·제목·태그가 보이며, 선택하면 편집기로 불러옵니다.',
      effect: '마음에 드는 시안으로 바로 시작합니다.',
      speech: '원하는 템플릿 카드를 눌러 적용하세요.',
      wait: 2800,
      cursor: true,
      action: 'importTab:template'
    },
    {
      id: 'import-smart',
      selector: '[data-tut="import-tab-smart"]',
      title: '라비 탭',
      description: '스마트 라벨 AI 라비에게 원하는 디자인을 말로 요청하거나, 이미지·엑셀·예시로 시작할 수 있어요.',
      effect: '아이디어만으로도 초안을 만듭니다.',
      speech: '라비 스마트 라벨 탭입니다.',
      wait: 2800,
      cursor: true,
      click: true,
      action: 'importTab:smart'
    },
    {
      id: 'import-smart-box',
      selector: '[data-tut="import-smart-box"]',
      title: '라비 입력',
      description: '프롬프트를 적고 전송하거나, 이미지 붙여넣기·엑셀·예시 카드를 이용하세요.',
      effect: '요구사항을 구체화할수록 결과가 좋아집니다.',
      speech: '여기에 원하는 라벨을 자세히 적어보세요.',
      wait: 3000,
      cursor: true,
      action: 'importTab:smart'
    },
    {
      id: 'import-external',
      selector: '[data-tut="import-tab-external"]',
      title: '타사포맷 탭',
      description: 'iLabel2, 폼텍디자인프로9 등 타사 포맷 파일을 가져와 이어서 작업할 수 있어요.',
      effect: '기존 작업물을 LabelUp으로 이전합니다.',
      speech: '타사포맷 가져오기 탭입니다.',
      wait: 2800,
      cursor: true,
      click: true,
      action: 'importTab:external'
    },
    {
      id: 'import-external-list',
      selector: '[data-tut="import-external-list"]',
      title: '타사포맷 업로드',
      description: '지원 포맷별로 파일을 드래그하거나 클릭해 업로드합니다.',
      effect: '호환 파일을 안전하게 불러옵니다.',
      speech: '여기에 파일을 올려 가져올 수 있어요.',
      wait: 2800,
      cursor: true,
      action: 'importTab:external'
    },
    {
      id: 'import-mydesign',
      selector: '[data-tut="import-tab-mydesign"]',
      title: '내디자인 탭',
      description: '이전에 작업한 프로젝트를 다시 불러와 이어서 편집합니다.',
      effect: '작업 연속성을 유지합니다.',
      speech: '내디자인에서 최근 작업을 불러올 수 있어요.',
      wait: 2800,
      cursor: true,
      click: true,
      action: 'importTab:mydesign'
    },
    {
      id: 'import-my-grid',
      selector: '[data-tut="import-my-grid"]',
      title: '내디자인 목록',
      description: '제목·규격·수정일로 검색하고 카드를 눌러 불러옵니다.',
      effect: '저장해 둔 시안을 바로 재사용합니다.',
      speech: '내 프로젝트 카드를 선택해 보세요.',
      wait: 2800,
      cursor: true,
      action: 'importTab:mydesign'
    },
    {
      id: 'import-close',
      selector: '[data-tut="import-fab"]',
      title: '가져오기 닫기',
      description: '튜토리얼을 위해 가져오기 창을 닫고, 저장 기능으로 넘어갑니다.',
      effect: '편집 화면으로 돌아갑니다.',
      speech: '가져오기 창을 닫을게요.',
      wait: 2200,
      action: 'closeImport'
    },
    {
      id: 'save',
      selector: '[data-tut="save"]',
      title: '저장하기',
      description: '작업 내용은 브라우저에 초안으로 저장됩니다. 자주 저장해 주세요.',
      effect: '작업 손실을 줄입니다.',
      speech: '저장하기를 눌러 초안을 남겨 두세요.',
      wait: 2600,
      cursor: true,
      action: 'closeImport'
    },
    {
      id: 'done',
      selector: '[data-tut="topbar"]',
      title: '튜토리얼 완료!',
      description: '이제 직접 디자인을 시작해 보세요. 좌측 하단 ✦ 튜토리얼로 언제든 다시 볼 수 있어요.',
      effect: '실전 편집으로 바로 이어갑니다.',
      speech: '튜토리얼이 끝났어요. 멋진 라벨을 만들어 보세요!',
      wait: 3200,
      action: 'closeImport'
    }
  ];

  function clamp(n, a, b) { return Math.max(a, Math.min(b, n)); }
  function isTouch() {
    return window.matchMedia('(pointer: coarse)').matches || ('ontouchstart' in window && navigator.maxTouchPoints > 0);
  }

  function Tutorial() {
    this.steps = STEPS;
    this.index = 0;
    this.playing = false;
    this.paused = false;
    this.speed = 1;
    this.voiceOn = true;
    this.captionOn = true;
    this.dotNet = null;
    this.root = null;
    this._timer = null;
    this._token = 0;
    this._ignoreUserUntil = 0;
    this._built = false;
    this._speechUtter = null;
  }

  Tutorial.prototype.mount = function (rootSel, dotNet) {
    this.root = document.querySelector(rootSel) || document.querySelector('[data-ed-root]') || document.body;
    this.dotNet = dotNet || null;
    if (!this._built) {
      this._buildUi();
      this._bindKeys();
      this._bindUserInterrupt();
      this._built = true;
    }
  };

  Tutorial.prototype._buildUi = function () {
    var host = document.createElement('div');
    host.className = 'lu-tut';
    host.setAttribute('data-lu-tut', '1');
    host.innerHTML =
      '<div class="lu-tut__overlay" data-tut-overlay hidden></div>' +
      '<div class="lu-tut__spot" data-tut-spot hidden></div>' +
      '<div class="lu-tut__tip" data-tut-tip hidden>' +
        '<div class="lu-tut__tip-kicker" data-tut-step-label></div>' +
        '<h4 data-tut-title></h4>' +
        '<p data-tut-desc></p>' +
        '<p class="lu-tut__effect" data-tut-effect></p>' +
        '<div class="lu-tut__tip-actions">' +
          '<button type="button" data-tut-tip-prev>이전</button>' +
          '<button type="button" data-tut-tip-pause>일시정지</button>' +
          '<button type="button" data-tut-tip-next class="is-primary">다음</button>' +
        '</div>' +
      '</div>' +
      '<div class="lu-tut__cursor" data-tut-cursor hidden aria-hidden="true">' +
        '<span class="lu-tut__cursor-dot"></span>' +
        '<span class="lu-tut__cursor-ripple"></span>' +
      '</div>' +
      '<div class="lu-tut__caption" data-tut-caption hidden></div>' +
      '<div class="lu-tut__toast" data-tut-toast hidden></div>' +
      '<div class="lu-tut__ctrl" data-tut-ctrl hidden>' +
        '<div class="lu-tut__ctrl-top">' +
          '<strong>튜토리얼</strong>' +
          '<span data-tut-progress-text>1 / 1</span>' +
        '</div>' +
        '<div class="lu-tut__progress"><i data-tut-progress-bar></i></div>' +
        '<div class="lu-tut__ctrl-row">' +
          '<button type="button" data-tut-restart title="처음부터">↺</button>' +
          '<button type="button" data-tut-prev title="이전">‹</button>' +
          '<button type="button" data-tut-play title="재생/일시정지">▶</button>' +
          '<button type="button" data-tut-next title="다음">›</button>' +
          '<button type="button" data-tut-stop title="종료">✕</button>' +
        '</div>' +
        '<div class="lu-tut__ctrl-row lu-tut__ctrl-row--opts">' +
          '<label>속도' +
            '<select data-tut-speed>' +
              '<option value="0.75">0.75x</option>' +
              '<option value="1" selected>1x</option>' +
              '<option value="1.25">1.25x</option>' +
              '<option value="1.5">1.5x</option>' +
            '</select>' +
          '</label>' +
          '<button type="button" data-tut-voice class="is-on" title="음성">🔊</button>' +
          '<button type="button" data-tut-caption-btn class="is-on" title="자막">CC</button>' +
        '</div>' +
      '</div>';

    document.body.appendChild(host);
    this.host = host;
    this.els = {
      overlay: host.querySelector('[data-tut-overlay]'),
      spot: host.querySelector('[data-tut-spot]'),
      tip: host.querySelector('[data-tut-tip]'),
      cursor: host.querySelector('[data-tut-cursor]'),
      caption: host.querySelector('[data-tut-caption]'),
      toast: host.querySelector('[data-tut-toast]'),
      ctrl: host.querySelector('[data-tut-ctrl]'),
      title: host.querySelector('[data-tut-title]'),
      desc: host.querySelector('[data-tut-desc]'),
      effect: host.querySelector('[data-tut-effect]'),
      stepLabel: host.querySelector('[data-tut-step-label]'),
      progressText: host.querySelector('[data-tut-progress-text]'),
      progressBar: host.querySelector('[data-tut-progress-bar]'),
      playBtn: host.querySelector('[data-tut-play]')
    };

    if (isTouch()) this.els.cursor.classList.add('is-touch');

    var self = this;
    host.querySelector('[data-tut-play]').addEventListener('click', function () {
      if (!self.playing) self.start(self.index);
      else if (self.paused) self.resume();
      else self.pause('manual');
    });
    host.querySelector('[data-tut-prev]').addEventListener('click', function () { self.prev(); });
    host.querySelector('[data-tut-next]').addEventListener('click', function () { self.next(); });
    host.querySelector('[data-tut-restart]').addEventListener('click', function () { self.restart(); });
    host.querySelector('[data-tut-stop]').addEventListener('click', function () { self.stop(); });
    host.querySelector('[data-tut-tip-prev]').addEventListener('click', function () { self.prev(); });
    host.querySelector('[data-tut-tip-next]').addEventListener('click', function () { self.next(); });
    host.querySelector('[data-tut-tip-pause]').addEventListener('click', function () {
      if (self.paused) self.resume();
      else self.pause('manual');
    });
    host.querySelector('[data-tut-speed]').addEventListener('change', function (e) {
      self.speed = parseFloat(e.target.value) || 1;
    });
    host.querySelector('[data-tut-voice]').addEventListener('click', function (e) {
      self.voiceOn = !self.voiceOn;
      e.currentTarget.classList.toggle('is-on', self.voiceOn);
      e.currentTarget.textContent = self.voiceOn ? '🔊' : '🔇';
      if (!self.voiceOn) self._stopSpeech();
    });
    host.querySelector('[data-tut-caption-btn]').addEventListener('click', function (e) {
      self.captionOn = !self.captionOn;
      e.currentTarget.classList.toggle('is-on', self.captionOn);
      self.els.caption.hidden = !self.captionOn || !self.playing;
    });
  };

  Tutorial.prototype._bindKeys = function () {
    var self = this;
    document.addEventListener('keydown', function (e) {
      if (!self.playing) return;
      if (e.key === 'Escape') {
        e.preventDefault();
        if (!self.paused) self.pause('esc');
        else self.stop();
      } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        self.next();
      } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        self.prev();
      }
    });
  };

  Tutorial.prototype._bindUserInterrupt = function () {
    var self = this;
    var events = ['pointerdown', 'wheel', 'touchstart'];
    events.forEach(function (ev) {
      document.addEventListener(ev, function (e) {
        if (!self.playing || self.paused) return;
        if (Date.now() < self._ignoreUserUntil) return;
        if (e.target && e.target.closest && e.target.closest('[data-lu-tut]')) return;
        self.pause('user');
        self._toast('직접 조작을 감지해 튜토리얼을 일시정지했어요. 재생을 눌러 이어가세요.');
      }, { capture: true, passive: true });
    });
  };

  Tutorial.prototype._toast = function (msg) {
    var el = this.els.toast;
    el.textContent = msg;
    el.hidden = false;
    clearTimeout(this._toastTimer);
    var self = this;
    this._toastTimer = setTimeout(function () { el.hidden = true; }, 3200);
  };

  Tutorial.prototype._setVisible = function (on) {
    this.els.overlay.hidden = !on;
    this.els.spot.hidden = true;
    // Tip/caption appear after focus — never flash previous step text first
    this.els.tip.hidden = true;
    this.els.tip.classList.remove('is-shown');
    this.els.ctrl.hidden = !on;
    this.els.caption.hidden = true;
    if (!on) {
      this.els.cursor.hidden = true;
      this.els.toast.hidden = true;
    }
    document.body.classList.toggle('lu-tut-active', on);
  };

  Tutorial.prototype.start = function (fromIndex) {
    this.index = typeof fromIndex === 'number' ? fromIndex : 0;
    this.playing = true;
    this.paused = false;
    this._setVisible(true);
    this._updatePlayBtn();
    this._runStep();
  };

  Tutorial.prototype.restart = function () {
    this._clearTimer();
    this._stopSpeech();
    this.start(0);
  };

  Tutorial.prototype.stop = function () {
    this._clearTimer();
    this._stopSpeech();
    this.playing = false;
    this.paused = false;
    this._setVisible(false);
    this._updatePlayBtn();
    try { localStorage.setItem(STORAGE_SKIP, '1'); } catch (e) { /* ignore */ }
  };

  Tutorial.prototype.pause = function (reason) {
    if (!this.playing || this.paused) return;
    this.paused = true;
    this._clearTimer();
    this._stopSpeech();
    this._updatePlayBtn();
    var tipPause = this.host.querySelector('[data-tut-tip-pause]');
    if (tipPause) tipPause.textContent = '재생';
    if (reason === 'user' || reason === 'esc') {
      /* toast already or skip */
    }
  };

  Tutorial.prototype.resume = function () {
    if (!this.playing || !this.paused) return;
    this.paused = false;
    this._updatePlayBtn();
    var tipPause = this.host.querySelector('[data-tut-tip-pause]');
    if (tipPause) tipPause.textContent = '일시정지';
    this._scheduleAdvance();
    if (this.voiceOn) {
      var step = this.steps[this.index];
      if (step) this._speak(step.speech || step.description);
    }
  };

  Tutorial.prototype.next = function () {
    if (!this.playing) return;
    this._clearTimer();
    this._stopSpeech();
    if (this.index >= this.steps.length - 1) {
      this.stop();
      this._toast('튜토리얼을 마쳤어요. 좋은 라벨 되세요!');
      return;
    }
    this.index += 1;
    this.paused = false;
    this._updatePlayBtn();
    this._runStep();
  };

  Tutorial.prototype.prev = function () {
    if (!this.playing) return;
    this._clearTimer();
    this._stopSpeech();
    this.index = Math.max(0, this.index - 1);
    this.paused = false;
    this._updatePlayBtn();
    this._runStep();
  };

  Tutorial.prototype._updatePlayBtn = function () {
    if (!this.els.playBtn) return;
    this.els.playBtn.textContent = (!this.playing || this.paused) ? '▶' : '⏸';
    var tipPause = this.host.querySelector('[data-tut-tip-pause]');
    if (tipPause) tipPause.textContent = this.paused ? '재생' : '일시정지';
  };

  Tutorial.prototype._clearTimer = function () {
    if (this._timer) {
      clearTimeout(this._timer);
      this._timer = null;
    }
    this._token += 1;
  };

  Tutorial.prototype._scheduleAdvance = function () {
    var self = this;
    var step = this.steps[this.index];
    if (!step || this.paused) return;
    var wait = ((step.wait || 2500) * PACE) / this.speed;
    var token = this._token;
    this._timer = setTimeout(function () {
      if (token !== self._token || self.paused || !self.playing) return;
      self.next();
    }, wait);
  };

  Tutorial.prototype._runStep = function () {
    var self = this;
    var step = this.steps[this.index];
    if (!step) {
      this.stop();
      return;
    }

    var total = this.steps.length;
    var cur = this.index + 1;
    this.els.progressText.textContent = cur + ' / ' + total;
    this.els.progressBar.style.width = ((cur / total) * 100) + '%';

    // Hide explanation until target is focused (focus → then tip)
    this.els.tip.hidden = true;
    this.els.tip.classList.remove('is-shown');
    this.els.caption.hidden = true;
    this.els.spot.hidden = true;
    this._stopSpeech();

    this._ignoreUserUntil = Date.now() + 900;

    this._runAction(step).then(function () {
      var settle = 0;
      if (step.action === 'openImport' || (step.action && step.action.indexOf('importTab:') === 0)) {
        settle = (420 * PACE) / self.speed;
      }
      return settle ? self._wait(settle) : null;
    }).then(function () {
      var el = step.selector ? document.querySelector(step.selector) : null;
      if (!el) {
        self._fillTipContent(step, true);
        self._placeTipCenter();
        self._revealTip();
        if (self.voiceOn) self._speak(step.speech || step.description);
        if (!self.paused) self._scheduleAdvance();
        return;
      }
      return self._ensureVisible(el, step).then(function () {
        self._highlight(el);
        if (step.cursor) {
          return self._moveCursor(el, !!step.click);
        }
        return self._wait((180 * PACE) / self.speed);
      }).then(function () {
        self._fillTipContent(step, false);
        self._placeTip(el);
        self._revealTip();
        if (self.voiceOn) self._speak(step.speech || step.description);
        if (!self.paused) self._scheduleAdvance();
      });
    }).catch(function () {
      self._fillTipContent(step, true);
      self._placeTipCenter();
      self._revealTip();
      if (!self.paused) self._scheduleAdvance();
    });
  };

  Tutorial.prototype._fillTipContent = function (step, missing) {
    var total = this.steps.length;
    var cur = this.index + 1;
    this.els.stepLabel.textContent = '단계 ' + cur + ' / ' + total;
    this.els.title.textContent = step.title || (missing ? '안내' : '');
    var desc = step.description || '';
    if (missing) desc = desc + ' (해당 UI를 찾지 못해 다음 설명으로 이어갑니다.)';
    this.els.desc.textContent = desc;
    this.els.effect.textContent = step.effect ? ('TIP · ' + step.effect) : '';
    this.els.caption.textContent = step.speech || step.description || '';
  };

  Tutorial.prototype._ensureVisible = function (el, step) {
    var self = this;
    return Promise.resolve().then(function () {
      if (step.action === 'expandProps' || (step.selector || '').indexOf('props') >= 0) {
        var props = document.querySelector('[data-ed-props-panel]');
        if (props && props.classList.contains('is-minimized')) {
          var btn = props.querySelector('.ed-props__min');
          if (btn) btn.click();
        }
      }
      if (step.action === 'expandPreview' || step.id === 'preview') {
        var preview = document.querySelector('[data-ed-preview-panel]');
        if (preview && preview.classList.contains('is-minimized')) {
          var pbtn = preview.querySelector('.ed-props__min');
          if (pbtn) pbtn.click();
        }
      }
      try {
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
      } catch (e) { /* ignore */ }
      return self._wait((280 * PACE) / self.speed);
    });
  };

  Tutorial.prototype._runAction = function (step) {
    var self = this;
    if (!step.action) return Promise.resolve();
    var needsDom = step.action === 'openImport' || step.action === 'closeImport' ||
      step.action.indexOf('importTab:') === 0;
    this._ignoreUserUntil = Date.now() + (needsDom ? 2800 : 1200);
    var runLocal = function () { return self._fallbackAction(step.action); };
    if (this.dotNet) {
      return this.dotNet.invokeMethodAsync('TutorialAction', step.action)
        .then(function () { return needsDom ? runLocal() : null; })
        .catch(runLocal);
    }
    return runLocal();
  };

  Tutorial.prototype._fallbackAction = function (action) {
    if (action === 'expandProps') {
      var props = document.querySelector('[data-ed-props-panel].is-minimized .ed-props__min');
      if (props) props.click();
    } else if (action === 'expandPreview') {
      var prev = document.querySelector('[data-ed-preview-panel].is-minimized .ed-props__min');
      if (prev) prev.click();
    } else if (action.indexOf('propsTab:') === 0) {
      var tab = action.split(':')[1];
      var btn = document.querySelector(tab === 'layers' ? '[data-tut="tab-layers"]' : '[data-tut="tab-props"]');
      if (btn) btn.click();
    } else if (action === 'openImport') {
      if (window.labelUpEditor && typeof window.labelUpEditor.openImport === 'function') {
        window.labelUpEditor.openImport();
      } else {
        var overlay = document.querySelector('[data-ed-import-overlay]');
        if (overlay && !overlay.classList.contains('is-open')) {
          var fab = document.querySelector('[data-ed-import-fab]');
          if (fab) fab.click();
        }
      }
    } else if (action === 'closeImport') {
      if (window.labelUpEditor && typeof window.labelUpEditor.closeImport === 'function') {
        window.labelUpEditor.closeImport();
      }
    } else if (action.indexOf('importTab:') === 0) {
      var ov = document.querySelector('[data-ed-import-overlay]');
      if (ov && !ov.classList.contains('is-open')) {
        if (window.labelUpEditor && typeof window.labelUpEditor.openImport === 'function') {
          window.labelUpEditor.openImport();
        } else {
          var fab2 = document.querySelector('[data-ed-import-fab]');
          if (fab2) fab2.click();
        }
      }
      var tabId = action.split(':')[1];
      var tabBtn = document.querySelector('[data-tut="import-tab-' + tabId + '"]');
      if (tabBtn) tabBtn.click();
    }
    return this._wait(200 * PACE);
  };

  Tutorial.prototype._highlight = function (el) {
    var r = el.getBoundingClientRect();
    var pad = 8;
    var spot = this.els.spot;
    spot.hidden = false;
    this.els.overlay.hidden = true; // spot box-shadow handles dimming
    spot.style.left = (r.left - pad) + 'px';
    spot.style.top = (r.top - pad) + 'px';
    spot.style.width = (r.width + pad * 2) + 'px';
    spot.style.height = (r.height + pad * 2) + 'px';
  };

  Tutorial.prototype._placeTip = function (el) {
    var tip = this.els.tip;
    tip.style.visibility = 'hidden';
    tip.hidden = false;
    tip.classList.remove('is-shown');
    var r = el.getBoundingClientRect();
    var tw = tip.offsetWidth || 300;
    var th = tip.offsetHeight || 160;
    var left = r.right + 16;
    var top = r.top;
    if (left + tw > window.innerWidth - 12) left = r.left - tw - 16;
    if (left < 12) left = clamp((window.innerWidth - tw) / 2, 12, window.innerWidth - tw - 12);
    if (top + th > window.innerHeight - 100) top = window.innerHeight - th - 100;
    if (top < 12) top = 12;
    tip.style.left = left + 'px';
    tip.style.top = top + 'px';
    tip.style.transform = '';
    tip.style.visibility = '';
    tip.hidden = true;
  };

  Tutorial.prototype._placeTipCenter = function () {
    var tip = this.els.tip;
    tip.style.visibility = 'hidden';
    tip.hidden = false;
    tip.classList.remove('is-shown');
    this.els.spot.hidden = true;
    this.els.overlay.hidden = false;
    tip.style.transform = '';
    var tw = tip.offsetWidth || 300;
    tip.style.left = ((window.innerWidth - tw) / 2) + 'px';
    tip.style.top = '22%';
    tip.style.visibility = '';
    tip.hidden = true;
  };

  Tutorial.prototype._revealTip = function () {
    var tip = this.els.tip;
    tip.hidden = false;
    tip.classList.remove('is-shown');
    void tip.offsetWidth;
    tip.classList.add('is-shown');
    this.els.caption.hidden = !this.captionOn;
  };

  Tutorial.prototype._moveCursor = function (el, click) {
    var self = this;
    var cursor = this.els.cursor;
    cursor.hidden = false;
    var r = el.getBoundingClientRect();
    var tx = r.left + r.width * 0.55;
    var ty = r.top + r.height * 0.55;
    cursor.style.transition = 'transform ' + ((0.55 * PACE) / this.speed) + 's cubic-bezier(0.22,1,0.36,1)';
    cursor.style.transform = 'translate(' + tx + 'px,' + ty + 'px)';
    return this._wait((600 * PACE) / this.speed).then(function () {
      if (!click) return;
      cursor.classList.add('is-click');
      return self._wait((280 * PACE) / self.speed).then(function () {
        cursor.classList.remove('is-click');
      });
    });
  };

  Tutorial.prototype._speak = function (text) {
    this._stopSpeech();
    if (!text || !window.speechSynthesis) return;
    try {
      var u = new SpeechSynthesisUtterance(text);
      u.lang = 'ko-KR';
      u.rate = clamp(this.speed, 0.8, 1.3);
      this._speechUtter = u;
      window.speechSynthesis.speak(u);
    } catch (e) { /* ignore */ }
  };

  Tutorial.prototype._stopSpeech = function () {
    try {
      if (window.speechSynthesis) window.speechSynthesis.cancel();
    } catch (e) { /* ignore */ }
    this._speechUtter = null;
  };

  Tutorial.prototype._wait = function (ms) {
    var self = this;
    return new Promise(function (resolve) {
      setTimeout(resolve, ms);
    });
  };

  Tutorial.prototype.shouldAutoOffer = function () {
    try {
      return localStorage.getItem(STORAGE_SKIP) !== '1';
    } catch (e) {
      return true;
    }
  };

  Tutorial.prototype.markSkipped = function () {
    try { localStorage.setItem(STORAGE_SKIP, '1'); } catch (e) { /* ignore */ }
  };

  var instance = new Tutorial();

  window.labelUpEditor = window.labelUpEditor || {};
  window.labelUpEditor.tutorial = {
    mount: function (root, dotNet) { instance.mount(root, dotNet); },
    start: function (i) { instance.start(i); },
    stop: function () { instance.stop(); },
    shouldAutoOffer: function () { return instance.shouldAutoOffer(); },
    markSkipped: function () { instance.markSkipped(); },
    isActive: function () { return instance.playing; }
  };
})();
