/**
 * LabelUp editor tutorial — virtual cursor, spotlight, controller
 * (custom engine; no Driver.js to avoid Blazor DOM conflicts)
 */
(function () {
  'use strict';

  var SPEEDS = [0.75, 1, 1.25, 1.5];
  var STORAGE_SKIP = 'lu-ed-tutorial-skip';
  var STORAGE_SKIP_M = 'lu-ed-tutorial-skip-m';
  /** 전체 진행 배율 (클수록 느림). 요청: 기존 대비 절반 속도 → 2 */
  var PACE = 2;
  /** 한 설명이 끝난 뒤 다음으로 넘어가기 전 쉬는 시간(ms) */
  var GAP_AFTER_STEP = 1000;

  var STEPS = [
    {
      id: 'welcome',
      selector: '[data-tut="topbar"]',
      title: '라벨 편집기에 오신 걸 환영해요',
      description: '상단 바에서 제목 수정, 줌, 그리드, 미리보기·저장하기·출력, 나가기를 할 수 있어요. 버튼마다 아이콘이 붙어 있어요.',
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
      id: 'grid',
      selector: '[data-tut="grid"]',
      title: '그리드',
      description: '그리드는 눈금자(룰러)와 같은 간격으로 맞춰집니다. 줌에 따라 1·2·5mm 칸이 바뀌어요.',
      effect: '눈금과 격자가 어긋나지 않아 정확하게 배치할 수 있습니다.',
      speech: '그리드는 눈금자와 같은 규격으로 맞춰져 있어요.',
      wait: 2600,
      cursor: true
    },
    {
      id: 'presets',
      selector: '[data-tut="presets"]',
      title: '용지선택',
      description: '라벨용지와 태그용지를 여기서 고를 수 있어요. 버튼을 누르면 선택 창이 열립니다.',
      effect: '작업에 맞는 용지 규격으로 빠르게 전환합니다.',
      speech: '용지선택 버튼을 눌러 창을 열어볼게요.',
      wait: 2400,
      cursor: true,
      click: true,
      action: 'openPaperPicker'
    },
    {
      id: 'paper-picker',
      selector: '[data-tut="paper-picker-head"]',
      title: '용지선택 창',
      description: '상단 라벨·태그 탭으로 용지 종류를 나눈 뒤, 검색하거나 카드를 눌러 적용합니다.',
      effect: '라벨과 태그를 같은 창에서 고를 수 있습니다.',
      speech: '용지선택 창이에요. 위쪽에 라벨과 태그 탭이 있어요.',
      wait: 2800,
      cursor: true,
      action: 'openPaperPicker'
    },
    {
      id: 'paper-tab-label',
      selector: '[data-tut="paper-tab-label"]',
      title: '라벨 용지',
      description: '라벨 탭에서는 쇼핑몰에 등록된 라벨용지를 검색하고 선택합니다.',
      effect: '일반 라벨 작업에 맞는 규격을 고릅니다.',
      speech: '라벨 탭입니다. 라벨용지를 고를 수 있어요.',
      wait: 2600,
      cursor: true,
      click: true,
      action: 'paperTab:label'
    },
    {
      id: 'paper-tab-tag',
      selector: '[data-tut="paper-tab-tag"]',
      title: '태그 용지',
      description: '태그 탭에서는 행택·폴드택 같은 태그용지를 선택합니다.',
      effect: '태그 작업도 같은 흐름으로 시작합니다.',
      speech: '태그 탭으로 바꾸면 태그용지를 고를 수 있어요.',
      wait: 2600,
      cursor: true,
      click: true,
      action: 'paperTab:tag'
    },
    {
      id: 'themes',
      selector: '[data-tut="themes"]',
      title: '템플릿선택',
      description: '완성된 라벨 시안을 골라 바로 편집할 수 있어요.',
      effect: '빈 용지 대신 시안으로 빠르게 시작합니다.',
      speech: '옆의 템플릿선택에서 완성 시안을 불러올 수 있어요.',
      wait: 2600,
      cursor: true,
      action: 'closePaperPicker'
    },
    {
      id: 'props',
      selector: '[data-tut="canvas"]',
      title: '선택 속성 막대',
      description: '객체를 선택하면 격자 위쪽에 캔바처럼 글꼴·색·크기를 바로 바꿀 수 있어요.',
      effect: '선택한 객체만 심플하게 조절합니다.',
      speech: '객체를 고르면 격자 위에서 속성을 바로 바꿔요.',
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
      id: 'labi-fab',
      selector: '[data-tut="labi-fab"]',
      title: '라비AI',
      description: '우측 하단 라비AI로 원하는 라벨을 말로 만들 수 있어요.',
      effect: '아이디어만 말해도 초안을 시작할 수 있습니다.',
      speech: '우측 하단의 라비AI 버튼이에요.',
      wait: 2600,
      cursor: true
    },
    {
      id: 'import-fab',
      selector: '[data-tut="import-fab"]',
      title: '타사포맷',
      description: '우측 하단 타사포맷 버튼을 누르면 라비AI처럼 변환 창만 열립니다.',
      effect: '폼텍·아이라벨·애니라벨 파일을 바로 올립니다.',
      speech: '타사포맷 버튼을 눌러 변환 창을 열게요.',
      wait: 2400,
      cursor: true,
      click: true,
      action: 'openVendorPicker'
    },
    {
      id: 'vendor-picker-head',
      selector: '[data-tut="vendor-picker-head"]',
      title: '타사포맷 창',
      description: '다른 탭 없이 타사 파일 변환만 보여 줍니다.',
      effect: '가져오기 전체 메뉴와 섞이지 않습니다.',
      speech: '타사포맷 전용 창이에요.',
      wait: 2400,
      cursor: true,
      action: 'openVendorPicker'
    },
    {
      id: 'vendor-picker-drop',
      selector: '[data-tut="vendor-picker-drop"]',
      title: '파일 올리기',
      description: '폼텍·아이라벨·애니라벨 파일을 끌어다 놓거나 클릭해 선택합니다.',
      effect: '올리면 변환 분석 창으로 이어집니다.',
      speech: '여기에 파일을 올려 변환할 수 있어요.',
      wait: 2800,
      cursor: true,
      action: 'openVendorPicker'
    },
    {
      id: 'topbar-actions',
      selector: '[data-tut="topbar-actions"]',
      title: '상단 작업 버튼',
      description: '라벨쇼핑, 내디자인, 데이터 가져오기, 미리보기, 저장하기, 출력, 나가기가 아이콘과 함께 오른쪽 위에 있어요.',
      effect: '자주 쓰는 작업을 상단에서 바로 실행합니다.',
      speech: '상단 오른쪽 버튼들입니다. 미리보기 다음에 저장하기가 있어요.',
      wait: 2800,
      cursor: true,
      action: 'closeVendorPicker'
    },
    {
      id: 'shop',
      selector: '[data-tut="shop"]',
      title: '라벨쇼핑',
      description: '편집 중에 라벨 상품을 고르고 담을 수 있어요.',
      effect: '용지 구매와 편집을 이어서 합니다.',
      speech: '라벨쇼핑 버튼이에요.',
      wait: 2200,
      cursor: true,
      action: 'closeVendorPicker'
    },
    {
      id: 'mydesign',
      selector: '[data-tut="mydesign"]',
      title: '내디자인',
      description: '저장해 둔 내 프로젝트를 불러와 이어서 편집합니다.',
      effect: '작업 연속성을 유지합니다.',
      speech: '내디자인에서 최근 작업을 불러올 수 있어요.',
      wait: 2400,
      cursor: true,
      action: 'closeVendorPicker'
    },
    {
      id: 'data-import',
      selector: '[data-tut="data-import"]',
      title: '데이터 가져오기',
      description: '엑셀·CSV 파일을 올려 라벨에 연결할 데이터를 가져옵니다.',
      effect: '가변 데이터 라벨을 빠르게 시작합니다.',
      speech: '데이터 가져오기로 표를 올릴 수 있어요.',
      wait: 2400,
      cursor: true,
      action: 'closeVendorPicker'
    },
    {
      id: 'preview-btn',
      selector: '[data-tut="preview-btn"]',
      title: '미리보기',
      description: '인쇄 미리보기 창을 열어 시트 배치를 확인합니다.',
      effect: '출력 전에 결과를 검증합니다.',
      speech: '미리보기 버튼입니다.',
      wait: 2200,
      cursor: true,
      action: 'closeVendorPicker'
    },
    {
      id: 'save',
      selector: '[data-tut="save"]',
      title: '저장하기',
      description: '미리보기 바로 옆에 있어요. 작업 내용은 초안으로 저장됩니다.',
      effect: '작업 손실을 줄입니다.',
      speech: '미리보기 다음의 저장하기를 눌러 초안을 남겨 두세요.',
      wait: 2600,
      cursor: true,
      action: 'closeVendorPicker'
    },
    {
      id: 'export',
      selector: '[data-tut="export"]',
      title: '편집기에서 출력',
      description: '미리보기와 같은 출력 창에서 인쇄하거나 PNG를 저장합니다.',
      effect: '완성된 라벨을 바로 출력합니다.',
      speech: '편집기에서 출력 버튼이에요.',
      wait: 2400,
      cursor: true,
      action: 'closeVendorPicker'
    },
    {
      id: 'exit',
      selector: '[data-tut="exit"]',
      title: '나가기',
      description: '편집기를 닫고 홈으로 돌아갑니다. 예전 닫기 버튼이 나가기로 바뀌었어요.',
      effect: '홈으로 안전하게 이동합니다.',
      speech: '나가기를 누르면 홈으로 돌아갑니다.',
      wait: 2400,
      cursor: true,
      action: 'closeVendorPicker'
    },
    {
      id: 'done',
      selector: '[data-tut="topbar"]',
      title: '튜토리얼 완료!',
      description: '이제 직접 디자인을 시작해 보세요. 좌측 하단 ✦ 튜토리얼로 언제든 다시 볼 수 있어요.',
      effect: '실전 편집으로 바로 이어갑니다.',
      speech: '튜토리얼이 끝났어요. 멋진 라벨을 만들어 보세요!',
      wait: 3200,
      action: 'closeVendorPicker'
    }
  ];

  var MOBILE_STEPS = [
    {
      id: 'm-welcome',
      selector: '[data-tut="topbar"]',
      title: '휴대폰 편집기예요',
      description: '상단은 꼭 필요한 버튼만 남겼어요. 저장·쇼핑·출력은 ☰ 메뉴에, 세부 값은 속성 버튼에 있어요.',
      effect: '작은 화면에서도 캔버스를 가리지 않습니다.',
      speech: '휴대폰용 편집기예요. 모바일 전용 조작부터 알려드릴게요.',
      wait: 2800,
      action: 'closeMobileOverlays'
    },
    {
      id: 'm-menu',
      selector: '.ed-m-more',
      title: '☰ 메뉴',
      description: '오른쪽 위 ☰을 누르면 라벨쇼핑, 저장, 출력, 나가기가 아래에서 올라옵니다.',
      effect: '자주 쓰는 작업을 한곳에서 실행합니다.',
      speech: '메뉴 버튼을 눌러 볼게요.',
      wait: 2600,
      cursor: true,
      click: true,
      action: 'openMobileMenu'
    },
    {
      id: 'm-menu-items',
      selector: '[data-tut="topbar-actions"]',
      title: '메뉴 서랍',
      description: '여기서 미리보기·저장하기·편집기에서 출력을 할 수 있어요. 바깥을 누르거나 메뉴 닫기를 누르면 접힙니다.',
      effect: '상단을 간결하게 유지하면서 기능은 그대로입니다.',
      speech: '저장과 출력은 이 메뉴 안에 있어요.',
      wait: 3000,
      cursor: true,
      action: 'openMobileMenu'
    },
    {
      id: 'm-props-btn',
      selector: '.ed-m-props',
      title: '속성 버튼',
      description: '선택한 객체의 위치·크기·글꼴을 바꾸려면 속성 버튼을 누르세요.',
      effect: '캔버스를 가리지 않고 필요할 때만 시트가 올라옵니다.',
      speech: '속성 버튼을 눌러 패널을 열게요.',
      wait: 2600,
      cursor: true,
      click: true,
      action: 'openMobileProps'
    },
    {
      id: 'm-props-sheet',
      selector: '[data-tut="props"]',
      title: '속성 시트',
      description: '아래에서 올라온 시트에서 값을 바꾸고, 다시 속성을 누르거나 바깥을 누르면 닫혀요.',
      effect: '한 손으로도 세부 조정이 가능합니다.',
      speech: '아래 속성 시트에서 숫자와 색을 바꿔요.',
      wait: 3000,
      cursor: true,
      action: 'openMobileProps'
    },
    {
      id: 'm-tools',
      selector: '[data-tut="float-tools"]',
      title: '하단 도구바',
      description: '텍스트·바코드·이미지·도형은 아래 도구를 가로로 밀어 고릅니다. 용지와 템플릿도 여기에 있어요.',
      effect: '엄지로 바로 추가할 수 있습니다.',
      speech: '도구는 화면 아래에 모아 두었어요.',
      wait: 2800,
      cursor: true,
      action: 'closeMobileProps'
    },
    {
      id: 'm-canvas',
      selector: '[data-tut="canvas"]',
      title: '한 손가락으로 편집',
      description: '객체를 탭하면 선택되고, 끌면 이동합니다. 모서리 핸들을 잡아 크기를 바꿀 수 있어요.',
      effect: '손가락 맞춤 여유를 두고 잡히도록 했습니다.',
      speech: '한 손가락으로 고르고 옮기면 됩니다.',
      wait: 2800,
      cursor: true,
      action: 'closeMobileOverlays'
    },
    {
      id: 'm-pinch',
      selector: '[data-tut="canvas"]',
      title: '두 손가락으로 확대',
      description: '캔버스 위에서 두 손가락을 벌리거나 오므리면 줌이 바뀝니다. 마우스 휠 대신 이 제스처를 쓰세요.',
      effect: '라벨 디테일을 크게 보고 다시 맞출 수 있습니다.',
      speech: '두 손가락을 벌리면 확대돼요.',
      wait: 3200,
      demo: 'pinch',
      action: 'closeMobileOverlays'
    },
    {
      id: 'm-pan',
      selector: '[data-tut="canvas"]',
      title: '두 손가락으로 이동',
      description: '두 손가락을 붙인 채 밀면 라벨이 함께 움직여 화면 밖 영역도 볼 수 있어요.',
      effect: '확대한 뒤에도 원하는 위치로 옮깁니다.',
      speech: '두 손가락으로 밀면 화면이 이동해요.',
      wait: 3000,
      demo: 'pan',
      action: 'closeMobileOverlays'
    },
    {
      id: 'm-longpress',
      selector: '[data-tut="canvas"]',
      title: '길게 누르기',
      description: '같은 자리를 잠시 누르고 있으면 삭제 등 빠른 메뉴가 열립니다. 움직이면 취소돼요.',
      effect: '오른쪽 클릭 대신 쓰는 모바일 메뉴입니다.',
      speech: '길게 누르면 빠른 메뉴가 열려요.',
      wait: 3200,
      demo: 'longpress',
      action: 'closeMobileOverlays'
    },
    {
      id: 'm-zoom',
      selector: '[data-tut="zoom"]',
      title: '줌 숫자',
      description: '핀치로 조절한 배율은 위쪽 숫자에 보여요. − / + 로도 바꿀 수 있습니다.',
      effect: '현재 확대 상태를 바로 확인합니다.',
      speech: '줌 숫자는 상단 가운데에 있어요.',
      wait: 2400,
      cursor: true
    },
    {
      id: 'm-done',
      selector: '[data-tut="topbar"]',
      title: '이제 직접 만들어 보세요',
      description: '왼쪽 아래 ✦ 모바일 튜토리얼에서 언제든 다시 볼 수 있어요.',
      effect: '실전 편집으로 바로 이어갑니다.',
      speech: '모바일 튜토리얼이 끝났어요. 멋진 라벨을 만들어 보세요!',
      wait: 3000,
      action: 'closeMobileOverlays'
    }
  ];
  function clamp(n, a, b) { return Math.max(a, Math.min(b, n)); }
  function isTouch() {
    return window.matchMedia('(pointer: coarse)').matches || ('ontouchstart' in window && navigator.maxTouchPoints > 0);
  }
  function isMobileTour() {
    try {
      if (window.labelUpEditor && typeof window.labelUpEditor.isMobileEditor === 'function')
        return !!window.labelUpEditor.isMobileEditor();
    } catch (e) { /* ignore */ }
    return window.matchMedia('(max-width: 900px)').matches;
  }
  function skipKey() {
    return isMobileTour() ? STORAGE_SKIP_M : STORAGE_SKIP;
  }
  function setMobileMenuOpen(on) {
    var actions = document.querySelector('.ed-topbar__actions');
    var open = !!(actions && actions.classList.contains('is-open'));
    if (on && !open) {
      var more = document.querySelector('.ed-m-more');
      if (more) more.click();
    } else if (!on && open) {
      var closer = document.querySelector('.ed-m-drawer-close');
      if (closer) closer.click();
    }
  }
  function setMobilePropsOpen(on) {
    var props = document.querySelector('[data-ed-props-panel]');
    var open = !!(props && props.classList.contains('is-m-open'));
    if (on === open) return;
    if (window.labelUpEditor && typeof window.labelUpEditor.toggleMobileProps === 'function')
      window.labelUpEditor.toggleMobileProps();
  }
  function closeMobileOverlays() {
    setMobileMenuOpen(false);
    setMobilePropsOpen(false);
  }
  function patchMobileInvite() {
    if (!isMobileTour()) return;
    var card = document.querySelector('.lu-tut-invite__card');
    if (card) {
      var h = card.querySelector('h3');
      var p = card.querySelector('p');
      if (h) h.textContent = '휴대폰에서 편집하는 법, 같이 볼까요?';
      if (p) p.textContent = '☰ 메뉴, 속성 시트, 두 손가락 확대·이동, 길게 누르기 등 모바일 전용 조작을 짧게 안내해요.';
    }
    var reopen = document.querySelector('.ed-tut-reopen');
    if (reopen) {
      var lab = reopen.querySelector('.ed-float-tools__label');
      if (lab) lab.textContent = '튜토리얼';
      else reopen.textContent = '✦ 튜토리얼';
      reopen.setAttribute('title', '모바일 튜토리얼 다시 보기');
    }
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
    this.mobile = false;
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
    this._applyTrack();
    patchMobileInvite();
    setTimeout(patchMobileInvite, 80);
    setTimeout(patchMobileInvite, 400);
    setTimeout(patchMobileInvite, 1200);
  };

  Tutorial.prototype._applyTrack = function () {
    this.mobile = isMobileTour();
    this.steps = this.mobile ? MOBILE_STEPS : STEPS;
    if (this.host) {
      this.host.classList.toggle('is-mobile-tour', this.mobile);
      var title = this.host.querySelector('.lu-tut__ctrl-top strong');
      if (title) title.textContent = this.mobile ? '모바일 튜토리얼' : '튜토리얼';
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
      '<div class="lu-tut__demo" data-tut-demo hidden>' +
        '<i class="lu-tut__finger lu-tut__finger--a"></i>' +
        '<i class="lu-tut__finger lu-tut__finger--b"></i>' +
        '<b class="lu-tut__hold"></b>' +
        '<span class="lu-tut__demo-label" data-tut-demo-label></span>' +
      '</div>' +
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
      demo: host.querySelector('[data-tut-demo]'),
      demoLabel: host.querySelector('[data-tut-demo-label]'),
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
    if (this.els.demo) this.els.demo.hidden = true;
    if (!on) {
      this.els.cursor.hidden = true;
      this.els.toast.hidden = true;
    }
    document.body.classList.toggle('lu-tut-active', on);
  };

  Tutorial.prototype.start = function (fromIndex) {
    this._applyTrack();
    patchMobileInvite();
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
    try { localStorage.setItem(skipKey(), '1'); } catch (e) { /* ignore */ }
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
    var minWait = ((step.wait || 2500) * PACE) / this.speed;
    var gap = GAP_AFTER_STEP / Math.max(this.speed, 0.5);
    var token = this._token;
    var started = Date.now();
    this._whenSpeechDone().then(function () {
      if (token !== self._token || self.paused || !self.playing) return;
      var remain = Math.max(0, minWait - (Date.now() - started));
      return self._wait(remain);
    }).then(function () {
      if (token !== self._token || self.paused || !self.playing) return;
      return self._wait(gap);
    }).then(function () {
      if (token !== self._token || self.paused || !self.playing) return;
      self.next();
    });
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
    if (this.els.demo) this.els.demo.hidden = true;
    this._stopSpeech();

    this._ignoreUserUntil = Date.now() + 900;

    this._runAction(step).then(function () {
      var settle = 0;
      if (step.action === 'openImport' || step.action === 'openPaperPicker' ||
          step.action === 'openVendorPicker' || step.action === 'closeVendorPicker' ||
          step.action === 'closeVendorPickerThenImport' ||
          step.action === 'openMobileMenu' || step.action === 'openMobileProps' ||
          step.action === 'closeMobileProps' || step.action === 'closeMobileOverlays' ||
          (step.action && (step.action.indexOf('importTab:') === 0 || step.action.indexOf('paperTab:') === 0))) {
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
        self._showDemo(step, el);
        if (step.cursor) {
          return self._moveCursor(el, !!step.click);
        }
        return self._wait((180 * PACE) / self.speed);
      }).then(function () {
        self._fillTipContent(step, false);
        self._placeTip(el);
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
      step.action === 'openPaperPicker' || step.action === 'closePaperPicker' ||
      step.action === 'openVendorPicker' || step.action === 'closeVendorPicker' ||
      step.action === 'closeVendorPickerThenImport' ||
      step.action === 'openMobileMenu' || step.action === 'openMobileProps' ||
      step.action === 'closeMobileProps' || step.action === 'closeMobileOverlays' ||
      step.action.indexOf('importTab:') === 0 || step.action.indexOf('paperTab:') === 0;
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
    } else if (action === 'openPaperPicker') {
      if (!document.querySelector('[data-tut="paper-picker-head"]')) {
        var paperBtn = document.querySelector('[data-tut="presets"]');
        if (paperBtn) paperBtn.click();
      }
    } else if (action === 'closePaperPicker') {
      var paperClose = document.querySelector('[data-tut="paper-picker-head"]') &&
        document.querySelector('.ed-modal__card--papers .ed-modal__close');
      if (paperClose) paperClose.click();
    } else if (action.indexOf('paperTab:') === 0) {
      if (!document.querySelector('[data-tut="paper-picker-head"]')) {
        var openPaper = document.querySelector('[data-tut="presets"]');
        if (openPaper) openPaper.click();
      }
      var paperTab = action.split(':')[1];
      var paperTabBtn = document.querySelector('[data-tut="paper-tab-' + paperTab + '"]');
      if (paperTabBtn) paperTabBtn.click();
    } else if (action === 'openVendorPicker') {
      var vendorDlg = document.querySelector('[data-tut="vendor-picker-dialog"]');
      if (!vendorDlg) {
        var vendorFab = document.querySelector('[data-tut="import-fab"]');
        if (vendorFab) vendorFab.click();
      }
    } else if (action === 'closeVendorPicker') {
      var vendorClose = document.querySelector('[data-tut="vendor-picker-head"]') &&
        document.querySelector('.ed-vendor-picker .ed-modal__close');
      if (vendorClose) vendorClose.click();
    } else if (action === 'closeVendorPickerThenImport') {
      var vendorClose2 = document.querySelector('[data-tut="vendor-picker-head"]') &&
        document.querySelector('.ed-vendor-picker .ed-modal__close');
      if (vendorClose2) vendorClose2.click();
      if (window.labelUpEditor && typeof window.labelUpEditor.openImport === 'function') {
        window.labelUpEditor.openImport();
      }
    } else if (action === 'openImport') {
      if (window.labelUpEditor && typeof window.labelUpEditor.openImport === 'function') {
        window.labelUpEditor.openImport();
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
        }
      }
      var tabId = action.split(':')[1];
      var tabBtn = document.querySelector('[data-tut="import-tab-' + tabId + '"]');
      if (tabBtn) tabBtn.click();
    } else if (action === 'openMobileMenu') {
      closeMobileOverlays();
      setMobileMenuOpen(true);
    } else if (action === 'openMobileProps') {
      setMobileMenuOpen(false);
      setMobilePropsOpen(true);
    } else if (action === 'closeMobileProps') {
      setMobilePropsOpen(false);
    } else if (action === 'closeMobileOverlays') {
      closeMobileOverlays();
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
    if (this.mobile) {
      tip.style.left = '12px';
      tip.style.right = '12px';
      tip.style.width = 'auto';
      tip.style.top = 'auto';
      tip.style.bottom = '158px';
      tip.style.transform = '';
      tip.style.visibility = '';
      tip.hidden = true;
      return;
    }
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
    tip.style.right = '';
    tip.style.width = '';
    tip.style.bottom = '';
    tip.style.top = top + 'px';
    tip.style.transform = '';
    tip.style.visibility = '';
    tip.hidden = true;
  };

  Tutorial.prototype._showDemo = function (step, el) {
    var demo = this.els.demo;
    if (!demo) return;
    demo.className = 'lu-tut__demo';
    if (!step.demo) {
      demo.hidden = true;
      return;
    }
    var r = el ? el.getBoundingClientRect() : { left: 40, top: 120, width: window.innerWidth - 80, height: 220 };
    var w = Math.max(120, Math.min(220, r.width * 0.55));
    var h = Math.max(120, Math.min(180, r.height * 0.4));
    demo.style.left = (r.left + (r.width - w) / 2) + 'px';
    demo.style.top = (r.top + (r.height - h) / 2) + 'px';
    demo.style.width = w + 'px';
    demo.style.height = h + 'px';
    demo.classList.add('is-' + step.demo);
    demo.hidden = false;
    if (this.els.demoLabel) {
      this.els.demoLabel.textContent = step.demo === 'pinch' ? '벌리기 / 오므리기'
        : step.demo === 'pan' ? '두 손가락으로 밀기'
        : '잠시 누르기';
    }
  };

  Tutorial.prototype._placeTipCenter = function () {
    var tip = this.els.tip;
    tip.style.visibility = 'hidden';
    tip.hidden = false;
    tip.classList.remove('is-shown');
    this.els.spot.hidden = true;
    this.els.overlay.hidden = false;
    if (this.mobile) {
      this._placeTip(null);
      return;
    }
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

  Tutorial.prototype._whenSpeechDone = function () {
    var self = this;
    return new Promise(function (resolve) {
      var u = self._speechUtter;
      if (!self.voiceOn || !u) {
        resolve();
        return;
      }
      var done = false;
      var finish = function () {
        if (done) return;
        done = true;
        resolve();
      };
      u.addEventListener('end', finish);
      u.addEventListener('error', finish);
      setTimeout(finish, 18000);
    });
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
      return localStorage.getItem(skipKey()) !== '1';
    } catch (e) {
      return true;
    }
  };

  Tutorial.prototype.markSkipped = function () {
    try { localStorage.setItem(skipKey(), '1'); } catch (e) { /* ignore */ }
  };

  var instance = new Tutorial();

  window.labelUpEditor = window.labelUpEditor || {};
  window.labelUpEditor.tutorial = {
    mount: function (root, dotNet) { instance.mount(root, dotNet); },
    start: function (i) { instance.start(i); },
    stop: function () { instance.stop(); },
    shouldAutoOffer: function () { return instance.shouldAutoOffer(); },
    markSkipped: function () { instance.markSkipped(); },
    isActive: function () { return instance.playing; },
    isMobileTour: function () { return isMobileTour(); }
  };
})();
