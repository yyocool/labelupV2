(function () {
  var FEATURES = {
    design: {
      title: "AI 디자인",
      hint: "만들고 싶은 라벨을 말해 주세요.",
      reply: "어떤 라벨이 필요하신가요? 예: 「카페 로고용 원형 스티커, 따뜻한 톤」처럼 용도와 분위기를 알려주시면 시안을 바로 만들어 드릴게요."
    },
    paper: {
      title: "용지 추천",
      hint: "붙이는 곳·환경을 알려 주세요.",
      reply: "부착 면(유리·종이·의류), 방수 여부, 실내/실외를 알려주시면 모조·PET·감열 등 맞는 용지를 추천해 드릴게요."
    },
    spec: {
      title: "규격 찾기",
      hint: "칸 수나 사이즈를 알려 주세요.",
      reply: "A4 24칸, 40×30mm처럼 치수나 사용 중인 규격 코드를 알려주시면 호환 라벨지를 찾아 드릴게요."
    },
    print: {
      title: "출력 도우미",
      hint: "프린터나 오류 메시지를 알려 주세요.",
      reply: "여백·스케일·양면 여부를 확인한 뒤 PDF 저장 또는 직접 출력 순서로 안내해 드릴게요. 사용 중인 프린터 기종이 있으면 더 정확해요."
    },
    cs: {
      title: "사용법 / CS",
      hint: "궁금한 점을 적어 주세요.",
      reply: "회원가입, 편집기 사용법, 주문·배송까지 도와드릴게요. 지금 막히신 화면이나 단계를 알려주시면 짧게 안내해 드릴게요."
    },
    shop: {
      title: "라비 쇼핑",
      hint: "필요한 상품을 말해 주세요.",
      reply: "라벨지·스티커·롤 용지를 찾아 드릴게요. 아래에서 추천 상품도 바로 보실 수 있어요.",
      scroll: ".product-section"
    }
  };

  var TIPS = [
    "안녕하세요! 라벨 고민 있으면 저 라비한테 물어보세요 😊",
    "AI로 라벨 디자인, 말로 설명하면 시안을 바로 만들어 드려요!",
    "처음이신가요? 규격 찾기부터 같이 해볼까요?",
    "오늘도 반가워요! 마이페이지에서 크레딧도 확인해 보세요 ✨",
    "쇼핑몰에서 인기 라벨지도 구경해 보세요 🛒",
    "바코드·QR 라벨도 제가 도와드릴게요!",
    "템플릿 고르기 어려우면 AI 추천 칩을 눌러보세요!",
    "출력 전 여백 설정이 헷갈리면 출력 도우미를 써보세요~",
    "막히는 부분 있으면 언제든 저를 눌러주세요!",
    "핸드메이드·식품·배송 라벨, 용도만 알려주시면 추천해요",
    "회원가입하면 웰컴 크레딧도 받을 수 있어요 🎁",
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
    ".labi-form button{height:40px;border:0;border-radius:12px;background:var(--accent,#7B2840);color:#fff;font-size:12px;font-weight:800;padding:0 12px}",
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
    "@media(max-width:768px){.labi-assistant{right:14px;bottom:76px}.labi-float-row{flex-direction:column-reverse;align-items:flex-end;gap:10px}.labi-fab{width:52px;height:52px;padding:7px}.labi-tip-wrap{width:min(260px,calc(100vw - 28px))}.labi-tip-text{font-size:12.5px;padding:6px 12px 11px}.labi-tip-tail{right:18px;bottom:-7px;transform:rotate(45deg)}}"
  ].join("");

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
    return SCRIPT_BASE + name;
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
            '<input type="text" id="labiInput" placeholder="라비에게 물어보기" autocomplete="off">' +
            "<button type=\"submit\">보내기</button>" +
          "</form>" +
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
    var current = "design";

    function showFeature(id) {
      var feat = FEATURES[id] || FEATURES.design;
      current = id;
      wrap.classList.add("is-chat");
      bubble.innerHTML = "<strong>" + feat.title + "</strong>" + feat.reply;
      input.placeholder = feat.hint;
      input.value = "";
      input.focus();
      if (feat.scroll) {
        var el = document.querySelector(feat.scroll);
        if (el) el.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    }

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

    wrap.querySelector("#labiForm").addEventListener("submit", function (e) {
      e.preventDefault();
      var text = (input.value || "").trim();
      var feat = FEATURES[current] || FEATURES.design;
      if (!text) {
        bubble.innerHTML = "<strong>" + feat.title + "</strong>" + feat.reply;
        return;
      }
      bubble.innerHTML = "<strong>" + feat.title + "</strong>「" + text.replace(/</g, "&lt;") + "」 확인했어요. 이 내용으로 바로 도와드릴게요. (시안 미리보기)";
      input.value = "";
    });

    document.addEventListener("click", function (e) {
      if (!wrap.contains(e.target)) {
        wrap.classList.remove("open");
        wrap.classList.remove("is-chat");
        fab.setAttribute("aria-expanded", "false");
      }
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        wrap.classList.remove("open");
        wrap.classList.remove("is-chat");
        fab.setAttribute("aria-expanded", "false");
        hideTip(true);
      }
    });

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

    scheduleTip(4000);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", build);
  } else {
    build();
  }
})();
