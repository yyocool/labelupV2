(function () {
  var FEATURES = {
    design: {
      title: "AI 디자인",
      intro: "만들고 싶은 라벨을 적어 주시면 메인의 라비 AI 입력창에 옮겨 드려요. 그곳에서 보내기를 눌러야 시안이 만들어지고, 그때만 잉크가 사용돼요.",
      hint: "예: 카페 로고용 원형 스티커",
      submit: "AI로 이어가기",
      chips: ["카페 로고용 원형 스티커, 따뜻한 톤", "수제 딸기잼 병 라벨", "택배 박스 주소 라벨"]
    },
    paper: {
      title: "용지 추천",
      intro: "라벨을 어디에, 어떻게 쓰실 건가요? 가장 가까운 상황을 골라 주세요."
    },
    spec: {
      title: "규격 찾기",
      intro: "품번(A101), 칸 수(24칸), 크기(40x30), 다른 회사 호환 코드(폼텍 3100 등)로 맞는 라벨지를 찾아 드려요.",
      hint: "예: A101, 24칸, 3100",
      submit: "찾기",
      chips: ["1칸", "21칸", "24칸", "65칸"]
    },
    print: {
      title: "출력 도우미",
      intro: "라벨이 칸에서 밀려 나온다면 대부분 인쇄 설정 문제예요. 아래 순서대로 확인해 보세요."
    },
    cs: {
      title: "사용법 / CS",
      intro: "궁금한 내용을 검색하면 자주 묻는 질문에서 답을 찾아 드려요.",
      hint: "예: 배송, 환불, 잉크",
      submit: "검색"
    },
    shop: {
      title: "라비 쇼핑",
      intro: "찾는 라벨지를 검색해 보세요. 바로 상세 페이지로 이동할 수 있어요.",
      hint: "예: 방수 라벨, 주소 라벨",
      submit: "검색",
      chips: ["방수", "주소", "바코드", "크라프트"]
    }
  };

  var PAPER_CHOICES = [
    { label: "물·습기에 닿아요", reason: "냉장고, 욕실, 야외처럼 물기 있는 곳에는 젖어도 번지지 않는 방수 라벨이 좋아요.", cats: [["waterproof-label", "방수 라벨"]] },
    { label: "병·유리에 투명하게", reason: "배경이 비치는 투명 라벨을 쓰세요. 프린터 종류(잉크젯/레이저)에 맞는 제품을 골라야 해요.", cats: [["inkjet-clear-label", "잉크젯 투명"], ["laser-clear-label", "레이저 투명"]] },
    { label: "선명하고 고급스럽게", reason: "사진이나 로고가 또렷하게 나오는 광택 라벨을 추천해요.", cats: [["gloss-label", "광택 라벨"]] },
    { label: "내추럴·핸드메이드", reason: "종이 질감의 크라프트 라벨이 따뜻한 느낌을 줘요.", cats: [["kraft-label", "크라프트 라벨"]] },
    { label: "눈에 띄는 색으로", reason: "분류하거나 강조할 때는 색이 있는 라벨이 좋아요.", cats: [["color-label", "형광 컬러"], ["pastel-color-label", "파스텔 컬러"]] },
    { label: "택배·주소·물류", reason: "백색 다용도 라벨 중 주소용·물류관리용 규격이 잘 맞아요.", cats: [["address-label", "주소용"], ["logistics-use", "물류관리용"]] },
    { label: "바코드·재고 관리", reason: "작은 칸이 많은 바코드용 규격을 추천해요.", cats: [["barcode-label", "바코드용"]] },
    { label: "서류·파일 정리", reason: "파일 등이나 인덱스에 맞춘 규격이 따로 있어요.", cats: [["index-label", "인덱스용"], ["government-doc", "정부문서화일"]] },
    { label: "인쇄면 보호", reason: "출력한 라벨 위에 덧붙여 긁힘과 물기를 막아 주는 보호용 필름이 있어요.", cats: [["protective-film", "보호용 필름"]] }
  ];

  var PRINT_STEPS = [
    ["배율은 100% (실제 크기)", "인쇄 창에서 「페이지에 맞춤」 대신 「실제 크기」 또는 배율 100%를 선택하세요."],
    ["용지 크기는 A4", "프린터 설정의 용지 크기가 A4인지 확인하세요. Letter로 되어 있으면 위치가 어긋나요."],
    ["여백은 없음", "브라우저 인쇄라면 여백을 「없음」으로, 머리글·바닥글은 끄세요."],
    ["먼저 일반 용지로 테스트", "일반 A4에 한 장 출력한 뒤 라벨지와 겹쳐 비춰 보고 위치를 확인하세요."],
    ["라벨지 방향 확인", "인쇄면(흰 면)과 넣는 방향을 프린터 안내에 맞춰 넣어 주세요."],
    ["그래도 밀리면", "편집기 출력 창의 「인쇄 위치 조정」에서 위·왼쪽 위치를 조금씩 옮겨 보세요."],
    ["아래로 갈수록 밀리면", "출력 창의 「인쇄 배율 보정」에 칼선 테스트 출력물을 자로 잰 길이를 넣어 보세요."]
  ];

  var TIPS = [
    "안녕하세요! 라벨 고민 있으면 저 라비한테 물어보세요 😊",
    "AI로 라벨 디자인, 말로 설명하면 시안을 바로 만들어 드려요!",
    "처음이신가요? 규격 찾기부터 같이 해볼까요?",
    "오늘도 반가워요! 마이페이지에서 잉크도 확인해 보세요 ✨",
    "쇼핑몰에서 인기 라벨지도 구경해 보세요 🛒",
    "바코드·QR 라벨도 제가 도와드릴게요!",
    "템플릿 고르기 어려우면 AI 추천 칩을 눌러보세요!",
    "출력 전 여백 설정이 헷갈리면 출력 도우미를 써보세요~",
    "막히는 부분 있으면 언제든 저를 눌러주세요!",
    "핸드메이드·식품·배송 라벨, 용도만 알려주시면 추천해요",
    "회원가입하면 웰컴 잉크도 받을 수 있어요 🎁",
    "라벨 작업 중이에요? 잠깐 쉬어가며 팁 하나 드릴게요 ☕"
  ];

  var css = [
    ".labi-assistant{position:fixed;right:22px;bottom:86px;z-index:10000;font-family:Pretendard,'Noto Sans KR','Apple SD Gothic Neo','Malgun Gothic',Arial,sans-serif;display:flex;flex-direction:column;align-items:flex-end;gap:0}",
    ".labi-float-row{display:flex;flex-direction:row;align-items:flex-end;gap:12px;position:relative}",
    ".labi-panel{position:absolute;right:0;bottom:68px;width:min(340px,calc(100vw - 28px));background:rgba(255,255,255,.98);border:1px solid #ececf2;border-radius:22px;box-shadow:0 18px 48px rgba(28,28,50,.16);opacity:0;transform:translateY(8px) scale(.98);pointer-events:none;transition:opacity .18s ease,transform .18s ease;overflow:hidden;backdrop-filter:blur(16px)}",
    ".labi-assistant.open .labi-panel{opacity:1;transform:none;pointer-events:auto}",
    ".labi-head{display:flex;align-items:center;gap:12px;padding:14px 14px 12px;background:linear-gradient(135deg,var(--accent-soft,#fdf2f5),#fff 62%);border-bottom:1px solid #f0f1f5}",
    ".labi-head img{width:54px;height:54px;border-radius:16px;object-fit:contain;background:transparent;box-shadow:none}",
    ".labi-head b{display:block;font-size:15px;letter-spacing:-.3px;color:#17182a}",
    ".labi-head small{display:block;margin-top:2px;font-size:11px;color:#6b7288}",
    ".labi-head span{display:block;margin-top:4px;font-size:11px;font-weight:700;color:var(--accent,#7B2840)}",
    ".labi-home{padding:12px}",
    ".labi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}",
    ".labi-feat{border:0;background:#f7f8fc;border-radius:14px;padding:8px 6px 8px;cursor:pointer;min-width:0}",
    ".labi-feat:hover{background:var(--accent-soft,#fdf2f5)}",
    ".labi-feat img{width:100%;height:86px;object-fit:contain;object-position:center;display:block;background:transparent}",
    ".labi-feat em{display:block;margin-top:2px;font-style:normal;font-size:11px;font-weight:800;color:#1c1e2a;text-align:center}",
    ".labi-chat{display:none;padding:12px 14px 14px;flex-direction:column;gap:10px}",
    ".labi-assistant.is-chat .labi-home{display:none}",
    ".labi-assistant.is-chat .labi-chat{display:flex}",
    ".labi-back{border:0;background:transparent;color:#6b7288;font-size:12px;font-weight:700;text-align:left;padding:0;cursor:pointer}",
    ".labi-bubble{background:#f4f6fb;border-radius:14px;padding:11px 12px;font-size:13px;line-height:1.55;color:#2a2d3a}",
    ".labi-bubble strong{display:block;margin-bottom:4px;color:var(--accent,#7B2840);font-size:12px}",
    ".labi-form{display:flex;gap:6px}",
    ".labi-form input{flex:1;height:40px;border:1px solid #e4e6ee;border-radius:12px;padding:0 12px;font-size:13px}",
    ".labi-form button{height:40px;border:0;border-radius:12px;background:var(--accent,#7B2840);color:#fff;font-size:12px;font-weight:800;padding:0 12px;white-space:nowrap;cursor:pointer}",
    ".labi-form[hidden]{display:none}",
    ".labi-body{display:flex;flex-direction:column;gap:8px;max-height:min(38vh,330px);overflow:auto;overscroll-behavior:contain}",
    ".labi-body:empty{display:none}",
    ".labi-chips{display:flex;flex-wrap:wrap;gap:6px}",
    ".labi-chip{border:1px solid #e6e6ed;background:#fff;border-radius:999px;padding:6px 11px;font-size:12px;font-weight:700;color:#3a3d4c;cursor:pointer;line-height:1.3;text-align:left}",
    ".labi-chip:hover,.labi-chip.is-on{border-color:var(--accent-soft-2,#E8D4DB);background:var(--accent-soft,#fdf2f5);color:var(--accent,#7B2840)}",
    ".labi-note{font-size:12px;line-height:1.55;color:#4a4d5c;background:#fbf7f4;border-radius:12px;padding:9px 11px}",
    ".labi-status{font-size:12px;color:#8a8d99;padding:4px 2px}",
    ".labi-results{display:flex;flex-direction:column;gap:6px}",
    ".labi-item{display:grid;grid-template-columns:44px minmax(0,1fr);gap:10px;align-items:center;padding:7px;border:1px solid #efeaf0;border-radius:12px;background:#fff;color:inherit;text-decoration:none}",
    ".labi-item:hover{border-color:var(--accent-soft-2,#E8D4DB);background:#fffafb}",
    ".labi-item img,.labi-item .labi-ph{width:44px;height:44px;border-radius:9px;object-fit:contain;background:#f7f4f0;display:grid;place-items:center;font-size:18px}",
    ".labi-item b{display:block;font-size:12.5px;color:#1c1e2a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}",
    ".labi-item small{display:block;font-size:11px;color:#7a7d88;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}",
    ".labi-item i{font-style:normal;font-size:12px;font-weight:800;color:var(--accent,#7B2840)}",
    ".labi-item s{font-size:11px;color:#a0a3ad;margin-right:4px}",
    ".labi-links{display:flex;flex-wrap:wrap;gap:6px}",
    ".labi-link{display:inline-flex;align-items:center;height:32px;padding:0 12px;border-radius:10px;background:var(--accent,#7B2840);color:#fff;font-size:12px;font-weight:800;text-decoration:none}",
    ".labi-link--ghost{background:#fff;color:var(--accent,#7B2840);border:1px solid var(--accent-soft-2,#E8D4DB)}",
    ".labi-steps{margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:6px;counter-reset:labi}",
    ".labi-steps li{position:relative;padding:8px 10px 8px 34px;border-radius:12px;background:#f7f8fc;font-size:12px;line-height:1.5;color:#4a4d5c;counter-increment:labi}",
    ".labi-steps li::before{content:counter(labi);position:absolute;left:10px;top:9px;width:17px;height:17px;border-radius:50%;background:var(--accent,#7B2840);color:#fff;font-size:10.5px;font-weight:800;display:grid;place-items:center}",
    ".labi-steps b{display:block;color:#1c1e2a;font-size:12.5px}",
    ".labi-faq{border:1px solid #efeaf0;border-radius:12px;background:#fff}",
    ".labi-faq summary{padding:9px 11px;font-size:12.5px;font-weight:700;color:#1c1e2a;cursor:pointer;list-style:none}",
    ".labi-faq summary::-webkit-details-marker{display:none}",
    ".labi-faq summary::before{content:'Q ';color:var(--accent,#7B2840);font-weight:900}",
    ".labi-faq p{margin:0;padding:0 11px 10px;font-size:12px;line-height:1.55;color:#5a5d6b}",
    ".labi-fab{position:relative;flex-shrink:0;width:58px;height:58px;border:0;border-radius:50%;padding:8px;overflow:visible;cursor:pointer;background:linear-gradient(145deg,var(--accent-soft,#F3E8EC) 0%,var(--ivory,#F7F3ED) 52%,#fff 100%);box-shadow:0 0 0 1.5px var(--accent-soft-2,#E8D4DB),0 6px 20px var(--accent-shadow,rgba(123,40,64,.22)),0 2px 8px rgba(46,42,39,.08);transition:transform .18s ease,box-shadow .18s ease,background .18s ease}",
    ".labi-fab:hover{transform:translateY(-2px);background:linear-gradient(145deg,var(--accent-soft-2,#E8D4DB) 0%,var(--accent-soft,#F3E8EC) 45%,var(--ivory,#F7F3ED) 100%);box-shadow:0 0 0 1.5px var(--accent-3,#9A3A55),0 10px 28px var(--accent-shadow,rgba(123,40,64,.26)),0 4px 12px rgba(46,42,39,.1)}",
    ".labi-fab:active{transform:translateY(0);background:linear-gradient(145deg,var(--accent-soft,#F3E8EC) 0%,var(--ivory,#F7F3ED) 100%);box-shadow:0 0 0 1.5px var(--accent-soft-2,#E8D4DB),0 4px 14px var(--accent-glow,rgba(123,40,64,.1)),0 1px 4px rgba(46,42,39,.08)}",
    ".labi-assistant.open .labi-fab{background:linear-gradient(145deg,var(--accent-soft-2,#E8D4DB) 0%,var(--accent-soft,#F3E8EC) 55%,var(--ivory,#F7F3ED) 100%);box-shadow:0 0 0 2px var(--accent,#7B2840),0 8px 24px var(--accent-shadow,rgba(123,40,64,.28)),0 2px 8px rgba(46,42,39,.1)}",
    ".labi-fab img{width:100%;height:100%;object-fit:contain;display:block;filter:none}",
    ".labi-badge{position:absolute;right:1px;top:1px;width:11px;height:11px;border-radius:50%;background:#ff5d6c;border:2px solid var(--ivory,#F7F3ED);box-shadow:0 1px 4px rgba(255,93,108,.35);pointer-events:none}",
    ".labi-tip-wrap{flex-shrink:0;width:min(272px,calc(100vw - 110px));pointer-events:none;opacity:0;transform:translateX(8px) scale(.96);transition:opacity .28s ease,transform .28s ease}",
    ".labi-tip-wrap.is-visible{opacity:1;transform:none;pointer-events:auto}",
    ".labi-tip{position:relative;background:#fff;border:1px solid #ece8eb;border-radius:16px;padding:0;box-shadow:0 12px 32px rgba(46,42,39,.14);overflow:visible}",
    ".labi-tip-head{display:flex;align-items:center;gap:8px;padding:10px 12px 0}",
    ".labi-tip-avatar{width:26px;height:26px;border-radius:8px;object-fit:contain;flex-shrink:0;border:0;background:transparent}",
    ".labi-tip-label{flex:1;font-size:12px;font-weight:800;color:var(--accent,#7B2840);letter-spacing:-.2px}",
    ".labi-tip-close{margin-left:auto;width:22px;height:22px;border:0;border-radius:50%;background:#f3f4f8;color:#8b8fa0;font-size:15px;line-height:1;cursor:pointer;padding:0;flex-shrink:0;display:grid;place-items:center}",
    ".labi-tip-close:hover{background:#ebe9ed;color:#555}",
    ".labi-tip-text{display:block;margin:0;padding:8px 14px 12px;font-size:13px;line-height:1.58;color:#2a2d3a;font-weight:600;word-break:keep-all;overflow-wrap:break-word;white-space:normal;letter-spacing:-.02em}",
    ".labi-tip-tail{position:absolute;right:-7px;bottom:18px;width:14px;height:14px;background:#fff;border-right:1px solid #ece8eb;border-bottom:1px solid #ece8eb;transform:rotate(-45deg);box-shadow:4px 4px 8px rgba(46,42,39,.04)}",
    "@media(max-width:768px){.labi-assistant{right:14px;bottom:76px}.labi-float-row{flex-direction:column;align-items:flex-end;gap:10px}.labi-fab{width:52px;height:52px;padding:7px}.labi-tip-wrap{width:min(260px,calc(100vw - 28px))}.labi-tip-text{font-size:12.5px;padding:6px 12px 11px}.labi-tip-tail{right:18px;bottom:-7px;transform:rotate(45deg)}}"
  ].join("");

  function esc(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function injectStyle() {
    var style = document.getElementById("labi-assistant-style");
    if (!style) {
      style = document.createElement("style");
      style.id = "labi-assistant-style";
      document.head.appendChild(style);
    }
    style.textContent = css;
  }

  var SCRIPT_BASE = (function () {
    var script = document.currentScript;
    return script && script.src ? script.src.replace(/[^/]+$/, "") : "assets/";
  })();

  function asset(name) {
    return SCRIPT_BASE + name + "?v=20260913b";
  }

  function closeTheme() {
    var theme = document.getElementById("themePicker");
    if (!theme) return;
    theme.classList.remove("open");
    var fab = theme.querySelector(".theme-fab");
    if (fab) fab.setAttribute("aria-expanded", "false");
  }

  function build() {
    injectStyle();
    if (document.getElementById("labiAssistant")) return;

    var wrap = document.createElement("div");
    wrap.className = "labi-assistant";
    wrap.id = "labiAssistant";

    wrap.innerHTML =
      '<div class="labi-panel" role="dialog" aria-label="라비 AI 도우미">' +
        '<div class="labi-head">' +
          '<img src="' + asset("labi-icon.png") + '" alt="라비">' +
          "<div><b>라비 LABI</b><small>LabelUp AI Assistant</small><span>라벨 작업, 이제 라비와 함께!</span></div>" +
        "</div>" +
        '<div class="labi-home">' +
          '<div class="labi-grid">' +
            '<button class="labi-feat" type="button" data-labi="design"><img src="' + asset("labi-ai-design.png") + '" alt=""><em>AI 디자인</em></button>' +
            '<button class="labi-feat" type="button" data-labi="paper"><img src="' + asset("labi-paper.png") + '" alt=""><em>용지 추천</em></button>' +
            '<button class="labi-feat" type="button" data-labi="spec"><img src="' + asset("labi-spec.png") + '" alt=""><em>규격 찾기</em></button>' +
            '<button class="labi-feat" type="button" data-labi="print"><img src="' + asset("labi-print.png") + '" alt=""><em>출력 도우미</em></button>' +
            '<button class="labi-feat" type="button" data-labi="cs"><img src="' + asset("labi-cs.png") + '" alt=""><em>사용법/CS</em></button>' +
            '<button class="labi-feat" type="button" data-labi="shop"><img src="' + asset("labi-shop.png") + '" alt=""><em>라비 쇼핑</em></button>' +
          "</div>" +
        "</div>" +
        '<div class="labi-chat">' +
          '<button class="labi-back" type="button">← 기능 목록</button>' +
          '<div class="labi-bubble" id="labiBubble"></div>' +
          '<form class="labi-form" id="labiForm">' +
            '<input type="text" id="labiInput" placeholder="라비에게 물어보기" autocomplete="off" maxlength="120">' +
            '<button type="submit" id="labiSubmit">보내기</button>' +
          "</form>" +
          '<div class="labi-body" id="labiBody"></div>' +
        "</div>" +
      "</div>" +
      '<div class="labi-float-row">' +
        '<div class="labi-tip-wrap" id="labiTipWrap" hidden aria-live="polite">' +
          '<div class="labi-tip" role="status">' +
            '<div class="labi-tip-head">' +
              '<img class="labi-tip-avatar" src="' + asset("labi-icon.png") + '" alt="">' +
              '<span class="labi-tip-label">라비</span>' +
              '<button type="button" class="labi-tip-close" aria-label="닫기">×</button>' +
            "</div>" +
            '<p class="labi-tip-text" id="labiTipText"></p>' +
            '<span class="labi-tip-tail" aria-hidden="true"></span>' +
          "</div>" +
        "</div>" +
        '<button class="labi-fab" type="button" aria-label="라비 AI 도우미" aria-expanded="false">' +
          '<img src="' + asset("labi-icon.png") + '" alt="라비">' +
          '<span class="labi-badge" aria-hidden="true"></span>' +
        "</button>" +
      "</div>";

    document.body.appendChild(wrap);

    var fab = wrap.querySelector(".labi-fab");
    var bubble = wrap.querySelector("#labiBubble");
    var input = wrap.querySelector("#labiInput");
    var form = wrap.querySelector("#labiForm");
    var submitBtn = wrap.querySelector("#labiSubmit");
    var body = wrap.querySelector("#labiBody");
    var current = "design";
    var requestSeq = 0;

    function closePanel() {
      wrap.classList.remove("open");
      wrap.classList.remove("is-chat");
      fab.setAttribute("aria-expanded", "false");
    }

    function chipsHtml(list, attr) {
      return '<div class="labi-chips">' + list.map(function (text, i) {
        return '<button type="button" class="labi-chip" ' + attr + '="' + esc(attr === "data-labi-paper" ? i : text) + '">' + esc(text) + "</button>";
      }).join("") + "</div>";
    }

    function productItem(p) {
      var thumb = p.thumbnail
        ? '<img src="' + esc(p.thumbnail) + '" alt="" loading="lazy">'
        : '<span class="labi-ph" aria-hidden="true">' + (p.is_ink ? "C" : "▦") + "</span>";
      var price = (p.on_sale ? "<s>" + esc(p.list_price_label) + "</s>" : "") + "<i>" + esc(p.price_label) + "</i>";
      var size = p.width_mm && p.height_mm ? Number(p.width_mm) + "×" + Number(p.height_mm) + "mm" : "";
      var meta = [p.category, p.material, size, p.labels_per_sheet ? p.labels_per_sheet + "칸" : ""].filter(Boolean).join(" · ");
      return '<a class="labi-item" href="/shop/products/' + Number(p.id) + '">' + thumb +
        "<span><b>" + esc(p.name) + "</b><small>" + esc(meta) + "</small>" + price + "</span></a>";
    }

    function setBody(html) {
      body.innerHTML = html;
      body.scrollTop = 0;
    }

    function fetchJson(url) {
      return fetch(url, { credentials: "same-origin", headers: { Accept: "application/json" } })
        .then(function (res) { return res.json(); })
        .then(function (json) {
          if (!json || json.success === false) throw new Error((json && json.message) || "요청 실패");
          return json.data || {};
        });
    }

    function searchProducts(query, category, limit, emptyText, moreHref) {
      var seq = ++requestSeq;
      var holder = body.querySelector("[data-labi-results]");
      if (holder) holder.innerHTML = '<p class="labi-status">찾는 중…</p>';
      var qs = (query ? "q=" + encodeURIComponent(query) : "") + (category ? (query ? "&" : "") + "category=" + encodeURIComponent(category) : "");
      fetchJson("/api/shop/catalog?" + qs).then(function (data) {
        if (seq !== requestSeq || !holder) return;
        var items = (data.items || []).slice(0, limit);
        if (!items.length) {
          holder.innerHTML = '<p class="labi-status">' + esc(emptyText) + "</p>";
          return;
        }
        var more = (data.total || 0) > items.length && moreHref
          ? '<div class="labi-links"><a class="labi-link labi-link--ghost" href="' + esc(moreHref) + '">전체 ' + Number(data.total) + "개 보기 →</a></div>"
          : "";
        holder.innerHTML = '<div class="labi-results">' + items.map(productItem).join("") + "</div>" + more;
      }).catch(function () {
        if (seq === requestSeq && holder) holder.innerHTML = '<p class="labi-status">불러오지 못했어요. 잠시 후 다시 시도해 주세요.</p>';
      });
    }

    function searchFaq(query) {
      var seq = ++requestSeq;
      var holder = body.querySelector("[data-labi-results]");
      if (holder) holder.innerHTML = '<p class="labi-status">찾는 중…</p>';
      fetchJson("/api/faq/search?q=" + encodeURIComponent(query || "")).then(function (data) {
        if (seq !== requestSeq || !holder) return;
        var items = data.items || [];
        var list = items.length
          ? items.map(function (it) {
              return '<details class="labi-faq"><summary>' + esc(it.question) + "</summary><p>" + esc(it.answer) + "</p></details>";
            }).join("")
          : '<p class="labi-status">맞는 답을 찾지 못했어요. 1:1 문의로 남겨 주시면 답변드릴게요.</p>';
        holder.innerHTML = '<div class="labi-results">' + list + "</div>" +
          '<div class="labi-links">' +
            '<a class="labi-link labi-link--ghost" href="' + esc(data.faq_url || "/faq") + '">FAQ 전체 보기</a>' +
            '<a class="labi-link" href="' + esc(data.inquiry_url || "/account#inquiry") + '">1:1 문의하기</a>' +
          "</div>";
      }).catch(function () {
        if (seq === requestSeq && holder) holder.innerHTML = '<p class="labi-status">불러오지 못했어요. <a href="/faq">FAQ 페이지</a>에서 확인해 주세요.</p>';
      });
    }

    function sendToDesign(text) {
      var promptBox = document.getElementById("promptInput");
      if (promptBox) {
        promptBox.value = text;
        promptBox.dispatchEvent(new Event("input", { bubbles: true }));
        closePanel();
        promptBox.scrollIntoView({ behavior: "smooth", block: "center" });
        if (!promptBox.readOnly) promptBox.focus({ preventScroll: true });
        return;
      }
      window.location.href = "/?labi_prompt=" + encodeURIComponent(text);
    }

    function showPaperChoice(index) {
      var choice = PAPER_CHOICES[index];
      if (!choice) return;
      body.querySelectorAll("[data-labi-paper]").forEach(function (chip) {
        chip.classList.toggle("is-on", Number(chip.getAttribute("data-labi-paper")) === index);
      });
      var detail = body.querySelector("[data-labi-detail]");
      detail.innerHTML =
        '<p class="labi-note">' + esc(choice.reason) + "</p>" +
        '<div class="labi-links">' + choice.cats.map(function (c) {
          return '<a class="labi-link labi-link--ghost" href="/shop/products?category=' + encodeURIComponent(c[0]) + '">' + esc(c[1]) + " 보기 →</a>";
        }).join("") + "</div>" +
        '<div data-labi-results></div>';
      var first = choice.cats[0];
      searchProducts("", first[0], 3, "아직 등록된 상품이 없어요.", "");
      detail.scrollIntoView({ block: "nearest" });
    }

    function showFeature(id) {
      var feat = FEATURES[id] || FEATURES.design;
      current = id;
      requestSeq++;
      wrap.classList.add("is-chat");
      bubble.innerHTML = "<strong>" + feat.title + "</strong>" + esc(feat.intro);
      input.value = "";
      form.hidden = !feat.submit;
      if (feat.submit) {
        input.placeholder = feat.hint;
        submitBtn.textContent = feat.submit;
      }

      if (id === "design") {
        setBody(chipsHtml(feat.chips, "data-labi-fill"));
      } else if (id === "paper") {
        setBody(chipsHtml(PAPER_CHOICES.map(function (c) { return c.label; }), "data-labi-paper") + '<div data-labi-detail></div>');
      } else if (id === "spec" || id === "shop") {
        setBody(chipsHtml(feat.chips, "data-labi-query") + "<div data-labi-results></div>");
      } else if (id === "print") {
        setBody(
          '<ol class="labi-steps">' + PRINT_STEPS.map(function (s) {
            return "<li><b>" + esc(s[0]) + "</b>" + esc(s[1]) + "</li>";
          }).join("") + "</ol>" +
          '<div class="labi-links">' +
            '<a class="labi-link labi-link--ghost" href="/guide/">사용 가이드</a>' +
            '<a class="labi-link labi-link--ghost" href="/faq?cat=print">출력 FAQ</a>' +
            '<a class="labi-link" href="/editor/">편집기 열기</a>' +
          "</div>"
        );
      } else if (id === "cs") {
        setBody("<div data-labi-results></div>");
        searchFaq("");
      }

      if (feat.submit && window.matchMedia("(min-width: 769px)").matches) input.focus();
    }

    function runQuery(text) {
      if (current === "design") {
        sendToDesign(text);
      } else if (current === "spec") {
        var q = text.replace(/\s*[×*X]\s*/g, "x").replace(/\s+/g, " ").trim();
        searchProducts(q, "", 5, "맞는 규격을 찾지 못했어요. 숫자만 바꿔 다시 검색해 보세요.", "/shop/products?q=" + encodeURIComponent(q));
      } else if (current === "shop") {
        searchProducts(text, "", 5, "검색 결과가 없어요. 다른 단어로 찾아 보세요.", "/shop/products?q=" + encodeURIComponent(text));
      } else if (current === "cs") {
        searchFaq(text);
      }
    }

    body.addEventListener("click", function (e) {
      var fill = e.target.closest("[data-labi-fill]");
      if (fill) {
        input.value = fill.getAttribute("data-labi-fill");
        input.focus();
        return;
      }
      var paper = e.target.closest("[data-labi-paper]");
      if (paper) {
        showPaperChoice(Number(paper.getAttribute("data-labi-paper")));
        return;
      }
      var query = e.target.closest("[data-labi-query]");
      if (query) {
        body.querySelectorAll("[data-labi-query]").forEach(function (chip) {
          chip.classList.toggle("is-on", chip === query);
        });
        input.value = query.getAttribute("data-labi-query");
        runQuery(input.value);
      }
    });

    fab.addEventListener("click", function () {
      var open = wrap.classList.toggle("open");
      fab.setAttribute("aria-expanded", open ? "true" : "false");
      if (open) {
        closeTheme();
        wrap.classList.remove("is-chat");
      }
    });

    wrap.querySelectorAll(".labi-feat").forEach(function (btn) {
      btn.addEventListener("click", function () {
        showFeature(btn.getAttribute("data-labi"));
      });
    });

    wrap.querySelector(".labi-back").addEventListener("click", function () {
      wrap.classList.remove("is-chat");
    });

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var text = (input.value || "").trim();
      if (!text) {
        input.focus();
        return;
      }
      runQuery(text);
    });

    document.addEventListener("click", function (e) {
      var path = typeof e.composedPath === "function" ? e.composedPath() : [];
      if (path.indexOf(wrap) === -1 && !wrap.contains(e.target)) closePanel();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        closePanel();
        hideTip(true);
      }
    });

    (function fillFromQuery() {
      var promptBox = document.getElementById("promptInput");
      if (!promptBox || !window.URLSearchParams) return;
      var params = new URLSearchParams(window.location.search);
      var text = (params.get("labi_prompt") || "").trim();
      if (!text) return;
      params.delete("labi_prompt");
      var rest = params.toString();
      history.replaceState(null, "", window.location.pathname + (rest ? "?" + rest : "") + window.location.hash);
      setTimeout(function () { sendToDesign(text.slice(0, 500)); }, 300);
    })();

    var tipWrap = wrap.querySelector("#labiTipWrap");
    var tipText = wrap.querySelector("#labiTipText");
    var tipClose = wrap.querySelector(".labi-tip-close");
    var tipTimer = null;
    var tipHideTimer = null;
    var tipIndex = 0;
    var tipsPaused = false;

    function shuffleTips() {
      for (var i = TIPS.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var tmp = TIPS[i];
        TIPS[i] = TIPS[j];
        TIPS[j] = tmp;
      }
    }

    function hideTip(pause) {
      if (pause) tipsPaused = true;
      if (tipHideTimer) {
        clearTimeout(tipHideTimer);
        tipHideTimer = null;
      }
      if (!tipWrap) return;
      tipWrap.classList.remove("is-visible");
      setTimeout(function () {
        if (!tipWrap.classList.contains("is-visible")) tipWrap.hidden = true;
      }, 280);
    }

    function showTip() {
      if (tipsPaused || wrap.classList.contains("open") || !tipWrap || !tipText) return;
      tipText.textContent = TIPS[tipIndex % TIPS.length];
      tipIndex++;
      tipWrap.hidden = false;
      requestAnimationFrame(function () {
        tipWrap.classList.add("is-visible");
      });
      if (tipHideTimer) clearTimeout(tipHideTimer);
      tipHideTimer = setTimeout(function () {
        hideTip(false);
      }, 6500);
    }

    function scheduleTip(delay) {
      if (tipTimer) clearTimeout(tipTimer);
      tipTimer = setTimeout(function () {
        showTip();
        scheduleTip(18000 + Math.floor(Math.random() * 12000));
      }, delay);
    }

    shuffleTips();
    if (tipClose) {
      tipClose.addEventListener("click", function (e) {
        e.stopPropagation();
        hideTip(true);
      });
    }
    if (tipWrap) {
      tipWrap.addEventListener("click", function (e) {
        if (e.target === tipClose) return;
        hideTip(false);
        wrap.classList.add("open");
        fab.setAttribute("aria-expanded", "true");
        wrap.classList.remove("is-chat");
        closeTheme();
      });
    }

    fab.addEventListener("click", function () {
      hideTip(false);
    }, true);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", build);
  } else {
    build();
  }
})();
