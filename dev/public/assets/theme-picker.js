(function () {
  var STORAGE_KEY = "labelup-theme";

  var THEMES = {
    violet: {
      name: "바이올렛",
      theory: "유사색 · 크리에이티브",
      accent: "#5B4CFF",
      accent2: "#4338CA",
      accent3: "#2563EB",
      soft: "#F1EFFF",
      soft2: "#E4DEFF",
      highlight: "#E8A317",
      rgb: "91,76,255"
    },
    azure: {
      name: "아주르",
      theory: "유사색 · 신뢰 / 커머스",
      accent: "#1558EA",
      accent2: "#0B3FBF",
      accent3: "#0EA5E9",
      soft: "#EEF4FF",
      soft2: "#D9E7FF",
      highlight: "#F59E0B",
      rgb: "21,88,234"
    },
    teal: {
      name: "틸",
      theory: "유사색 · 내추럴",
      accent: "#0F766E",
      accent2: "#115E59",
      accent3: "#2A9D8F",
      soft: "#F0FDFA",
      soft2: "#CCFBF1",
      highlight: "#E07A5F",
      rgb: "15,118,110"
    },
    blush: {
      name: "블러시",
      theory: "유사색 · 라이프스타일",
      accent: "#B44A5A",
      accent2: "#8F3846",
      accent3: "#C9785A",
      soft: "#FDF2F4",
      soft2: "#F8DDE2",
      highlight: "#3D6B5A",
      rgb: "180,74,90"
    },
    olive: {
      name: "올리브",
      theory: "유사색 · 오가닉",
      accent: "#4F6F46",
      accent2: "#3D5637",
      accent3: "#6B8F4E",
      soft: "#F3F6F0",
      soft2: "#DDE6D6",
      highlight: "#C45C26",
      rgb: "79,111,70"
    },
    terracotta: {
      name: "테라코타",
      theory: "유사색 · 웜 럭셔리",
      accent: "#C26B3D",
      accent2: "#9A4F28",
      accent3: "#D4A04A",
      soft: "#FBF4EE",
      soft2: "#F3E0D0",
      highlight: "#2A6F6F",
      rgb: "194,107,61"
    },
    ink: {
      name: "잉크",
      theory: "단색조 · 에디토리얼",
      accent: "#3D4A5C",
      accent2: "#243040",
      accent3: "#6B7C90",
      soft: "#F2F4F7",
      soft2: "#DDE3EA",
      highlight: "#C4A574",
      rgb: "61,74,92"
    }
  };

  var css = [
    ".theme-picker{position:fixed;right:22px;bottom:22px;z-index:9999;font-family:Pretendard,'Noto Sans KR','Apple SD Gothic Neo','Malgun Gothic',Arial,sans-serif}",
    ".theme-panel{position:absolute;right:0;bottom:64px;width:278px;background:rgba(255,255,255,.97);border:1px solid #ececf2;border-radius:18px;box-shadow:0 18px 48px rgba(28,28,50,.16);padding:14px 12px 10px;opacity:0;transform:translateY(8px) scale(.98);pointer-events:none;transition:opacity .18s ease,transform .18s ease;backdrop-filter:blur(16px)}",
    ".theme-picker.open .theme-panel{opacity:1;transform:none;pointer-events:auto}",
    ".theme-panel-head{padding:2px 8px 12px;border-bottom:1px solid #f0f1f5;margin-bottom:8px}",
    ".theme-panel-head strong{display:block;font-size:13px;letter-spacing:-.2px;color:#17182a}",
    ".theme-panel-head span{display:block;margin-top:3px;font-size:11px;color:#8a8ea0}",
    ".theme-list{display:flex;flex-direction:column;gap:4px;max-height:min(62vh,420px);overflow:auto}",
    ".theme-item{display:flex;align-items:center;gap:10px;width:100%;border:0;background:transparent;border-radius:12px;padding:8px 8px;text-align:left;cursor:pointer}",
    ".theme-item:hover{background:#f6f7fb}",
    ".theme-item.is-on{background:var(--accent-soft,#f1efff);box-shadow:inset 0 0 0 1px var(--accent-soft-2,#e4deff)}",
    ".theme-swatch{display:flex;flex:0 0 auto}",
    ".theme-swatch i{width:16px;height:16px;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 1px rgba(0,0,0,.06);display:block}",
    ".theme-swatch i + i{margin-left:-6px}",
    ".theme-meta{min-width:0;flex:1}",
    ".theme-meta b{display:block;font-size:12.5px;color:#1c1e2a;font-weight:800}",
    ".theme-meta small{display:block;margin-top:2px;font-size:10.5px;color:#8b8fa3}",
    ".theme-check{width:16px;color:var(--accent,#5B4CFF);font-size:13px;opacity:0}",
    ".theme-item.is-on .theme-check{opacity:1}",
    ".theme-fab{width:52px;height:52px;border:0;border-radius:16px;background:#11131d;color:#fff;box-shadow:0 12px 28px rgba(20,22,40,.22);display:grid;place-items:center;cursor:pointer;padding:0}",
    ".theme-fab:hover{transform:translateY(-1px)}",
    ".theme-fab-disc{width:26px;height:26px;border-radius:50%;background:conic-gradient(var(--accent,#5B4CFF) 0 33%,var(--accent-3,#2563EB) 33% 66%,var(--highlight,#E8A317) 66% 100%);box-shadow:inset 0 0 0 2px rgba(255,255,255,.35)}",
    "@media(max-width:768px){.theme-picker{right:14px;bottom:14px}.theme-panel{width:min(278px,calc(100vw - 28px))}.theme-fab{width:48px;height:48px;border-radius:14px}}"
  ].join("");

  function injectStyle() {
    if (document.getElementById("theme-picker-style")) return;
    var style = document.createElement("style");
    style.id = "theme-picker-style";
    style.textContent = css;
    document.head.appendChild(style);
  }

  function applyTheme(id) {
    var theme = THEMES[id] || THEMES.violet;
    var root = document.documentElement;
    var glow = "rgba(" + theme.rgb + ",.12)";
    var shadow = "rgba(" + theme.rgb + ",.18)";
    root.style.setProperty("--accent", theme.accent);
    root.style.setProperty("--accent-2", theme.accent2);
    root.style.setProperty("--accent-3", theme.accent3);
    root.style.setProperty("--accent-soft", theme.soft);
    root.style.setProperty("--accent-soft-2", theme.soft2);
    root.style.setProperty("--accent-glow", glow);
    root.style.setProperty("--accent-shadow", shadow);
    root.style.setProperty("--highlight", theme.highlight);
    root.style.setProperty("--accent-rgb", theme.rgb);
    root.style.setProperty("--blue", theme.accent);
    root.style.setProperty("--purple", theme.accent);
    root.style.setProperty("--purple2", theme.accent2);
    root.setAttribute("data-theme", id);
    try { localStorage.setItem(STORAGE_KEY, id); } catch (e) {}
    document.querySelectorAll(".theme-item").forEach(function (btn) {
      btn.classList.toggle("is-on", btn.getAttribute("data-theme-id") === id);
    });
  }

  function currentId() {
    var saved = null;
    try { saved = localStorage.getItem(STORAGE_KEY); } catch (e) {}
    if (saved && THEMES[saved]) return saved;
    var fallback = document.documentElement.getAttribute("data-default-theme");
    return THEMES[fallback] ? fallback : "violet";
  }

  function build() {
    injectStyle();
    if (document.getElementById("themePicker")) return;

    var wrap = document.createElement("div");
    wrap.className = "theme-picker";
    wrap.id = "themePicker";

    var items = Object.keys(THEMES).map(function (id) {
      var t = THEMES[id];
      return (
        '<button class="theme-item" type="button" data-theme-id="' + id + '">' +
          '<span class="theme-swatch">' +
            '<i style="background:' + t.accent + '"></i>' +
            '<i style="background:' + t.accent3 + '"></i>' +
            '<i style="background:' + t.highlight + '"></i>' +
          "</span>" +
          '<span class="theme-meta"><b>' + t.name + "</b><small>" + t.theory + "</small></span>" +
          '<span class="theme-check">✓</span>' +
        "</button>"
      );
    }).join("");

    wrap.innerHTML =
      '<div class="theme-panel" role="dialog" aria-label="테마 색상">' +
        '<div class="theme-panel-head"><strong>테마 색상</strong><span>디자인 이론에 맞는 조화로운 배색</span></div>' +
        '<div class="theme-list">' + items + "</div>" +
      "</div>" +
      '<button class="theme-fab" type="button" aria-label="테마 색상 변경" aria-expanded="false">' +
        '<span class="theme-fab-disc"></span>' +
      "</button>";

    document.body.appendChild(wrap);

    var fab = wrap.querySelector(".theme-fab");
    fab.addEventListener("click", function () {
      var open = wrap.classList.toggle("open");
      fab.setAttribute("aria-expanded", open ? "true" : "false");
    });
    wrap.querySelectorAll(".theme-item").forEach(function (btn) {
      btn.addEventListener("click", function () {
        applyTheme(btn.getAttribute("data-theme-id"));
      });
    });
    document.addEventListener("click", function (e) {
      if (!wrap.contains(e.target)) {
        wrap.classList.remove("open");
        fab.setAttribute("aria-expanded", "false");
      }
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        wrap.classList.remove("open");
        fab.setAttribute("aria-expanded", "false");
      }
    });

    applyTheme(currentId());
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", build);
  } else {
    build();
  }
})();
