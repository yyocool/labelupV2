<?php
/**
 * Label-UP 기능 구조 맵 + PPT 슬라이드 (DB) + 수동 편집
 * @var array $map
 * @var array $doc
 * @var array $dbSlides
 * @var array $deckSlides
 * @var array $slideTypes
 * @var array $tones
 * @var bool $manage
 * @var array|null $editSlide
 * @var array $editExtras
 */
$meta = isset($map['meta']) ? $map['meta'] : array();
$categories = isset($map['categories']) ? $map['categories'] : array();
$bridges = isset($map['bridges']) ? $map['bridges'] : array();
$scope = isset($map['scope']) ? $map['scope'] : array();
$scopeSummaries = isset($scopeSummaries) ? $scopeSummaries : array();
$slideJson = json_encode(isset($deckSlides) ? $deckSlides : array(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
if ($slideJson === false) {
    $slideJson = '[]';
}
$isAdmin = is_admin();
$editMode = $editSlide !== null;
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+KR:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

<style>
.fmap {
    --fmap-ink: #14202e;
    --fmap-muted: #5a6a7a;
    --fmap-line: #d5dde6;
    --fmap-paper: #f3f6f9;
    --fmap-teal: #0a6b6b;
    --fmap-teal-deep: #064e4e;
    --fmap-amber: #b45309;
    --fmap-amber-deep: #7c3a00;
    --fmap-white: #ffffff;
    font-family: "IBM Plex Sans KR", sans-serif;
    color: var(--fmap-ink);
}
.fmap * { box-sizing: border-box; }

.fmap-hero {
    display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between;
    gap: 1.25rem; margin-bottom: 1.75rem; padding: 1.75rem 1.5rem; border-radius: 4px;
    background:
        linear-gradient(135deg, rgba(10,107,107,.08) 0%, transparent 45%),
        linear-gradient(225deg, rgba(180,83,9,.07) 0%, transparent 40%),
        var(--fmap-paper);
    border: 1px solid var(--fmap-line);
}
.fmap-hero h1 {
    font-family: Outfit, "IBM Plex Sans KR", sans-serif;
    font-size: clamp(1.85rem, 3vw, 2.55rem); font-weight: 800;
    letter-spacing: -0.03em; line-height: 1.15; margin: 0 0 .45rem;
}
.fmap-hero p { font-size: 1.1rem; color: var(--fmap-muted); margin: 0; max-width: 36rem; line-height: 1.5; }
.fmap-hero-actions { display: flex; flex-wrap: wrap; gap: .6rem; }
.fmap-btn {
    display: inline-flex; align-items: center; gap: .45rem;
    padding: .7rem 1.15rem; border-radius: 4px; font-size: 1rem; font-weight: 600;
    border: 1px solid transparent; cursor: pointer; text-decoration: none;
    font-family: inherit; line-height: 1.2; background: none; color: inherit;
}
.fmap-btn-primary { background: var(--fmap-ink); color: #fff; }
.fmap-btn-primary:hover { background: #0c1622; color: #fff; }
.fmap-btn-ghost { background: var(--fmap-white); border-color: var(--fmap-line); color: var(--fmap-ink); }
.fmap-btn-ghost:hover { border-color: #9aabbc; }
.fmap-btn-accent { background: var(--fmap-teal); color: #fff; }
.fmap-btn-accent:hover { background: var(--fmap-teal-deep); color: #fff; }

.fmap-meta { display: flex; flex-wrap: wrap; gap: .65rem 1.25rem; font-size: .95rem; color: var(--fmap-muted); margin-bottom: 1.5rem; }

.fmap-manage {
    margin-bottom: 2rem; border: 1px solid var(--fmap-line); border-radius: 4px;
    background: #fff; overflow: hidden;
}
.fmap-manage-head {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem;
    padding: 1rem 1.25rem; background: #eef3f7; border-bottom: 1px solid var(--fmap-line);
}
.fmap-manage-head h2 { margin: 0; font-size: 1.25rem; font-family: Outfit, sans-serif; }
.fmap-manage-body { padding: 1.1rem 1.25rem 1.4rem; }
.fmap-manage .table-wrap { overflow-x: auto; }
.fmap-manage table { width: 100%; border-collapse: collapse; font-size: .95rem; }
.fmap-manage th, .fmap-manage td { padding: .55rem .5rem; border-bottom: 1px solid var(--fmap-line); text-align: left; vertical-align: top; }
.fmap-manage th { font-size: .8rem; color: var(--fmap-muted); font-weight: 600; }
.fmap-manage tr:hover td { background: #f8fafc; }
.fmap-badge {
    display: inline-block; font-size: .75rem; font-weight: 700; padding: .15rem .45rem;
    border-radius: 3px; background: #e2e8f0; color: #334155;
}
.fmap-badge.teal { background: #ccfbf1; color: #115e59; }
.fmap-badge.amber { background: #fde68a; color: #78350f; }
.fmap-badge.off { background: #fee2e2; color: #991b1b; }

.fmap-form-grid { display: grid; gap: .85rem; }
@media (min-width: 800px) {
    .fmap-form-grid.cols-2 { grid-template-columns: 1fr 1fr; }
    .fmap-form-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
}
.fmap-form-grid .full { grid-column: 1 / -1; }
.fmap-form-grid label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: .3rem; }
.fmap-form-grid .hint { font-size: .8rem; color: var(--fmap-muted); margin-top: .25rem; }
.fmap-form-grid input, .fmap-form-grid select, .fmap-form-grid textarea {
    width: 100%; padding: .55rem .65rem; border: 1px solid var(--fmap-line);
    border-radius: 3px; font-family: inherit; font-size: .95rem;
}
.fmap-form-grid textarea { min-height: 110px; resize: vertical; }
.fmap-form-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
.fmap-doc-form { margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px dashed var(--fmap-line); }

.fmap-cat { margin-bottom: 2.75rem; border: 1px solid var(--fmap-line); border-radius: 4px; overflow: hidden; background: var(--fmap-white); }
.fmap-cat-head { padding: 1.5rem 1.65rem 1.35rem; color: #fff; }
.fmap-cat[data-color="teal"] .fmap-cat-head { background: linear-gradient(120deg, var(--fmap-teal-deep), var(--fmap-teal)); }
.fmap-cat[data-color="amber"] .fmap-cat-head { background: linear-gradient(120deg, var(--fmap-amber-deep), var(--fmap-amber)); }
.fmap-cat-code { display: inline-block; font-family: Outfit, sans-serif; font-weight: 700; font-size: .85rem; letter-spacing: .08em; opacity: .85; margin-bottom: .35rem; }
.fmap-cat-head h2 { font-family: Outfit, "IBM Plex Sans KR", sans-serif; font-size: clamp(1.75rem, 2.6vw, 2.25rem); font-weight: 800; margin: 0 0 .35rem; letter-spacing: -0.02em; }
.fmap-cat-head .fmap-tagline { font-size: 1.2rem; font-weight: 500; opacity: .92; margin: 0 0 .65rem; }
.fmap-cat-head .fmap-summary { font-size: 1.05rem; line-height: 1.55; opacity: .88; margin: 0; max-width: 48rem; }
.fmap-groups { padding: 1.25rem 1.25rem 1.5rem; display: grid; gap: 1rem; }
@media (min-width: 900px) { .fmap-groups { grid-template-columns: 1fr 1fr; } }
.fmap-group { border: 1px solid var(--fmap-line); border-radius: 4px; padding: 1.15rem 1.2rem 1.25rem; background: var(--fmap-paper); scroll-margin-top: 5rem; }
.fmap-cat[data-color="teal"] .fmap-group { border-left: 4px solid var(--fmap-teal); }
.fmap-cat[data-color="amber"] .fmap-group { border-left: 4px solid var(--fmap-amber); }
.fmap-group-top { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .5rem; margin-bottom: .5rem; }
.fmap-group h3 { font-family: Outfit, "IBM Plex Sans KR", sans-serif; font-size: 1.35rem; font-weight: 700; margin: 0; letter-spacing: -0.02em; }
.fmap-pill { display: inline-block; font-size: .8rem; font-weight: 700; padding: .2rem .5rem; border-radius: 3px; background: #e8eef4; color: var(--fmap-ink); }
.fmap-pill.p0 { background: #c8f0e0; color: #065f46; }
.fmap-pill.p1 { background: #fde68a; color: #78350f; }
.fmap-group-sum { font-size: 1.02rem; color: var(--fmap-muted); margin: 0 0 .85rem; line-height: 1.45; }
.fmap-group-meta { font-size: .88rem; color: var(--fmap-muted); margin-bottom: .85rem; }
.fmap-group-meta code { font-size: .85rem; background: #fff; padding: .1rem .35rem; border: 1px solid var(--fmap-line); }
.fmap-feat { background: var(--fmap-white); border: 1px solid var(--fmap-line); border-radius: 3px; padding: .9rem 1rem; margin-bottom: .65rem; }
.fmap-feat:last-child { margin-bottom: 0; }
.fmap-feat h4 { font-size: 1.12rem; font-weight: 700; margin: 0 0 .35rem; }
.fmap-feat p { font-size: 1rem; line-height: 1.5; margin: 0 0 .55rem; color: #334155; }
.fmap-rules { margin: 0; padding-left: 1.15rem; font-size: .98rem; line-height: 1.55; }
.fmap-rules li { margin-bottom: .25rem; }

.fmap-bridges { margin-bottom: 2rem; padding: 1.4rem 1.5rem; border: 1px dashed #9aabbc; border-radius: 4px; background: #fafbfc; }
.fmap-bridges h2 { font-family: Outfit, sans-serif; font-size: 1.5rem; margin: 0 0 1rem; }
.fmap-bridge-row { display: grid; grid-template-columns: 1fr auto 1fr; gap: .75rem; align-items: center; padding: .75rem 0; border-top: 1px solid var(--fmap-line); font-size: 1.05rem; }
.fmap-bridge-row:first-of-type { border-top: none; }
.fmap-bridge-arrow { font-family: Outfit, sans-serif; font-weight: 700; color: var(--fmap-teal); }
.fmap-bridge-note { grid-column: 1 / -1; font-size: .95rem; color: var(--fmap-muted); margin-top: -.35rem; }

.fmap-scope { margin-bottom: 2.75rem; }
.fmap-scope-intro {
    margin-bottom: 1.25rem; padding: 1.5rem 1.65rem;
    border: 1px solid var(--fmap-line); border-radius: 4px;
    background: linear-gradient(120deg, #0f172a 0%, #1e293b 100%); color: #f8fafc;
}
.fmap-scope-intro h2 {
    font-family: Outfit, "IBM Plex Sans KR", sans-serif;
    font-size: clamp(1.6rem, 2.4vw, 2.1rem); font-weight: 800; margin: 0 0 .5rem;
}
.fmap-scope-intro p { margin: 0; font-size: 1.05rem; line-height: 1.5; opacity: .88; }
.fmap-scope-cards { display: grid; gap: 1rem; }
@media (min-width: 800px) { .fmap-scope-cards { grid-template-columns: 1fr 1fr; } }
.fmap-scope-card {
    display: block; text-decoration: none; color: #fff; border-radius: 4px;
    padding: 1.5rem 1.45rem; transition: transform .15s ease, box-shadow .15s ease;
}
.fmap-scope-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(15,23,42,.18); color: #fff; }
.fmap-scope-card.teal { background: linear-gradient(125deg, #064e4e, #0a6b6b); }
.fmap-scope-card.amber { background: linear-gradient(125deg, #7c3a00, #b45309); }
.fmap-scope-card .period { font-family: Outfit, sans-serif; font-weight: 700; font-size: .9rem; letter-spacing: .05em; opacity: .9; margin-bottom: .35rem; }
.fmap-scope-card h3 { font-family: Outfit, sans-serif; font-size: 1.7rem; font-weight: 800; margin: 0 0 .45rem; }
.fmap-scope-card .goal { margin: 0 0 .85rem; font-size: 1.02rem; line-height: 1.45; opacity: .92; }
.fmap-scope-card .areas { margin: 0 0 1rem; padding: 0; list-style: none; font-size: .95rem; opacity: .88; }
.fmap-scope-card .areas li { margin-bottom: .2rem; }
.fmap-scope-card .areas li::before { content: "· "; }
.fmap-scope-card .cta {
    display: inline-block; font-weight: 700; font-size: .98rem;
    padding: .45rem .85rem; border-radius: 3px; background: rgba(255,255,255,.95); color: #0f172a;
}

.fmap-deck {
    position: fixed; inset: 0; z-index: 10000; display: none; flex-direction: column;
    background: #0d1520; color: #f4f7fa; font-family: "IBM Plex Sans KR", sans-serif;
}
.fmap-deck.is-open { display: flex; }
.fmap-deck-bar {
    flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: .75rem 1.25rem; background: rgba(0,0,0,.35); border-bottom: 1px solid rgba(255,255,255,.08);
}
.fmap-deck-bar strong { font-family: Outfit, sans-serif; font-size: 1rem; font-weight: 600; }
.fmap-deck-progress { font-variant-numeric: tabular-nums; font-size: 1.05rem; color: rgba(255,255,255,.7); }
.fmap-deck-controls { display: flex; gap: .45rem; align-items: center; }
.fmap-deck-controls button {
    font-family: inherit; font-size: 1rem; font-weight: 600; padding: .5rem .9rem; border-radius: 3px;
    border: 1px solid rgba(255,255,255,.2); background: rgba(255,255,255,.06); color: #fff; cursor: pointer;
}
.fmap-deck-controls button:hover { background: rgba(255,255,255,.14); }
.fmap-deck-controls button.fmap-deck-close { background: #e11d48; border-color: #e11d48; }
.fmap-deck-body {
    flex: 1 1 auto; display: flex; min-height: 0; overflow: hidden;
}
.fmap-deck-tree {
    flex: 0 0 min(280px, 32vw);
    width: min(280px, 32vw);
    background: #0a1018;
    border-right: 1px solid rgba(255,255,255,.08);
    display: flex; flex-direction: column; min-height: 0;
}
.fmap-deck-tree-head {
    flex: 0 0 auto;
    padding: .85rem 1rem .65rem;
    border-bottom: 1px solid rgba(255,255,255,.08);
}
.fmap-deck-tree-head strong {
    display: block;
    font-family: Outfit, sans-serif;
    font-size: .85rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: rgba(255,255,255,.45);
    margin-bottom: .45rem;
}
.fmap-deck-here {
    font-size: .95rem;
    line-height: 1.45;
    color: rgba(244,247,250,.9);
}
.fmap-deck-here .fmap-here-line { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fmap-deck-here .fmap-here-line.is-branch { padding-left: .85rem; color: rgba(244,247,250,.75); }
.fmap-deck-here .fmap-here-line.is-leaf { padding-left: 1.7rem; color: #5eead4; font-weight: 700; }
.fmap-deck-here[data-tone="amber"] .fmap-here-line.is-leaf { color: #fbbf24; }
.fmap-deck-tree-scroll {
    flex: 1 1 auto; overflow: auto; padding: .65rem .55rem 1rem;
}
.fmap-tree-ul { list-style: none; margin: 0; padding: 0; }
.fmap-tree-ul .fmap-tree-ul { margin-left: .55rem; padding-left: .55rem; border-left: 1px solid rgba(255,255,255,.12); }
.fmap-tree-item { margin: 0; }
.fmap-tree-btn {
    display: flex; align-items: flex-start; gap: .35rem;
    width: 100%; text-align: left;
    padding: .4rem .5rem; margin: 1px 0;
    border: none; border-radius: 3px; cursor: pointer;
    background: transparent; color: rgba(244,247,250,.62);
    font-family: inherit; font-size: .92rem; line-height: 1.35;
}
.fmap-tree-btn:hover { background: rgba(255,255,255,.06); color: #fff; }
.fmap-tree-btn .fmap-tree-mark {
    flex: 0 0 auto;
    font-family: Outfit, sans-serif;
    font-size: .78rem;
    color: rgba(255,255,255,.28);
    margin-top: .12rem;
    width: 1rem;
}
.fmap-tree-btn .fmap-tree-label { flex: 1 1 auto; min-width: 0; }
.fmap-tree-btn.is-ancestor { color: rgba(244,247,250,.88); font-weight: 600; }
.fmap-tree-btn.is-current {
    background: rgba(20,184,166,.18);
    color: #5eead4;
    font-weight: 700;
    box-shadow: inset 2px 0 0 #14b8a6;
}
.fmap-tree-btn.is-current[data-tone="amber"],
.fmap-deck-tree[data-tone="amber"] .fmap-tree-btn.is-current {
    background: rgba(245,158,11,.16);
    color: #fbbf24;
    box-shadow: inset 2px 0 0 #f59e0b;
}
.fmap-tree-btn.is-dim { opacity: .45; }
.fmap-tree-sec {
    margin: .65rem 0 .25rem;
    padding: 0 .5rem;
    font-size: .72rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: rgba(255,255,255,.3);
    font-family: Outfit, sans-serif;
}
.fmap-deck-stage {
    flex: 1 1 auto; display: flex; align-items: center; justify-content: center;
    padding: 1.5rem 2rem 2rem; overflow: hidden; position: relative; min-width: 0;
}
@media (max-width: 820px) {
    .fmap-deck-tree { flex-basis: 220px; width: 220px; }
    .fmap-tree-btn { font-size: .85rem; }
}
@media (max-width: 640px) {
    .fmap-deck-tree { display: none; }
}
.fmap-slide {
    display: none; width: min(1100px, 96vw); max-height: calc(100vh - 6.5rem); overflow: auto;
    padding: clamp(1.5rem, 4vw, 3rem); border-radius: 6px;
    background:
        radial-gradient(ellipse at 20% 0%, rgba(10,107,107,.25), transparent 55%),
        radial-gradient(ellipse at 90% 100%, rgba(180,83,9,.18), transparent 50%),
        #152233;
    border: 1px solid rgba(255,255,255,.08); box-shadow: 0 24px 80px rgba(0,0,0,.45);
    animation: fmapSlideIn .28s ease;
}
.fmap-slide.is-active { display: block; }
@keyframes fmapSlideIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
.fmap-slide .kicker { font-family: Outfit, sans-serif; font-size: clamp(1rem, 1.6vw, 1.2rem); font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #5eead4; margin: 0 0 .75rem; }
.fmap-slide[data-tone="amber"] .kicker { color: #fbbf24; }
.fmap-slide h2 { font-family: Outfit, "IBM Plex Sans KR", sans-serif; font-size: clamp(2.2rem, 4.5vw, 3.4rem); font-weight: 800; letter-spacing: -0.03em; line-height: 1.15; margin: 0 0 1rem; }
.fmap-slide .lead { font-size: clamp(1.25rem, 2.2vw, 1.65rem); line-height: 1.5; color: rgba(244,247,250,.88); margin: 0 0 1.5rem; max-width: 40em; }
.fmap-slide .sub { font-size: clamp(1.1rem, 1.8vw, 1.35rem); color: rgba(244,247,250,.65); margin: 0 0 1.25rem; }
.fmap-slide-cover { text-align: center; padding-top: clamp(3rem, 10vh, 6rem); padding-bottom: clamp(3rem, 10vh, 6rem); }
.fmap-slide-cover h2 { font-size: clamp(2.6rem, 5.5vw, 4rem); }
.fmap-slide-cover .lead { margin-left: auto; margin-right: auto; }
.fmap-ov-grid { display: grid; gap: 1.25rem; margin-top: 1.5rem; }
@media (min-width: 720px) { .fmap-ov-grid { grid-template-columns: 1fr 1fr; } }
.fmap-ov-card { padding: 1.5rem 1.4rem; border-radius: 4px; border: 1px solid rgba(255,255,255,.12); background: rgba(0,0,0,.22); }
.fmap-ov-card.teal { border-top: 5px solid #14b8a6; }
.fmap-ov-card.amber { border-top: 5px solid #f59e0b; }
.fmap-ov-card h3 { font-family: Outfit, sans-serif; font-size: 1.85rem; margin: 0 0 .5rem; }
.fmap-ov-card p { font-size: 1.15rem; line-height: 1.45; margin: 0 0 .75rem; color: rgba(244,247,250,.8); }
.fmap-ov-card ul { margin: 0; padding-left: 1.2rem; font-size: 1.1rem; line-height: 1.55; }
.fmap-group-list { display: grid; gap: .65rem; margin-top: .5rem; }
.fmap-group-list div { display: flex; gap: 1rem; align-items: baseline; padding: .75rem 1rem; background: rgba(0,0,0,.2); border-radius: 3px; border-left: 4px solid #14b8a6; font-size: 1.25rem; }
.fmap-slide[data-tone="amber"] .fmap-group-list div { border-left-color: #f59e0b; }
.fmap-group-list strong { font-weight: 700; min-width: 9rem; }
.fmap-group-list span { color: rgba(244,247,250,.75); font-size: 1.1rem; }
.fmap-slide .feat-rules { margin: 1rem 0 0; padding: 0; list-style: none; counter-reset: fmap-r; }
.fmap-slide .feat-rules li {
    position: relative; padding: .85rem 1rem .85rem 2.6rem; margin-bottom: .55rem;
    font-size: clamp(1.2rem, 2vw, 1.45rem); line-height: 1.45; background: rgba(0,0,0,.25); border-radius: 3px;
}
.fmap-slide .feat-rules li::before {
    content: counter(fmap-r); counter-increment: fmap-r; position: absolute; left: .85rem; top: .9rem;
    width: 1.4rem; height: 1.4rem; border-radius: 50%; background: #14b8a6; color: #042f2e;
    font-family: Outfit, sans-serif; font-weight: 800; font-size: .95rem;
    display: flex; align-items: center; justify-content: center;
}
.fmap-slide[data-tone="amber"] .feat-rules li::before { background: #fbbf24; color: #451a03; }
.fmap-bridge-slide .row { display: grid; grid-template-columns: 1fr auto 1fr; gap: 1rem; align-items: center; padding: 1rem 0; border-bottom: 1px solid rgba(255,255,255,.1); font-size: clamp(1.15rem, 2vw, 1.4rem); }
.fmap-bridge-slide .row:last-child { border-bottom: none; }
.fmap-bridge-slide .arr { color: #5eead4; font-weight: 800; font-family: Outfit, sans-serif; }
.fmap-bridge-slide .note { grid-column: 1 / -1; font-size: 1.05rem; color: rgba(244,247,250,.55); margin-top: -.35rem; }
.fmap-sched-slide { display: grid; gap: .75rem; margin-top: .5rem; }
.fmap-sched-card {
    padding: 1rem 1.1rem; border-radius: 4px;
    background: rgba(0,0,0,.25); border-left: 4px solid #f59e0b;
}
.fmap-sched-card .wh { font-family: Outfit, sans-serif; font-size: 1.25rem; font-weight: 800; margin: 0 0 .25rem; color: #fbbf24; }
.fmap-sched-card .wp { font-size: 1.05rem; color: rgba(244,247,250,.7); margin: 0 0 .45rem; }
.fmap-sched-card .wf { font-size: 1.15rem; margin: 0 0 .45rem; }
.fmap-sched-card ul { margin: 0; padding-left: 1.2rem; font-size: 1.05rem; line-height: 1.45; }
.fmap-deck-hint { position: absolute; bottom: .85rem; left: 50%; transform: translateX(-50%); font-size: .9rem; color: rgba(255,255,255,.4); pointer-events: none; }
@media (max-width: 640px) {
    .fmap-bridge-row, .fmap-bridge-slide .row { grid-template-columns: 1fr; }
    .fmap-bridge-arrow { display: none; }
}
</style>

<div class="fmap">
    <div class="fmap-hero">
        <div>
            <h1><?= e(isset($meta['title']) ? $meta['title'] : '기능 구조 맵') ?></h1>
            <p><?= e(isset($meta['subtitle']) ? $meta['subtitle'] : '') ?></p>
        </div>
        <div class="fmap-hero-actions">
            <button type="button" class="fmap-btn fmap-btn-primary" id="fmapOpenSlide">슬라이드 모드</button>
            <a href="<?= url('feature-map-scope.php?phase=phase-1') ?>" class="fmap-btn fmap-btn-accent">1차 구축</a>
            <a href="<?= url('feature-map-scope.php?phase=phase-enhance') ?>" class="fmap-btn fmap-btn-ghost">고도화</a>
            <?php if ($isAdmin): ?>
            <?php if ($manage): ?>
            <a href="<?= url('feature-map.php') ?>" class="fmap-btn fmap-btn-ghost">문서 보기</a>
            <?php else: ?>
            <a href="<?= url('feature-map.php?manage=1') ?>" class="fmap-btn fmap-btn-ghost">슬라이드 관리</a>
            <?php endif; ?>
            <?php endif; ?>
            <a href="<?= url('feature-spec.php') ?>" class="fmap-btn fmap-btn-ghost">기능 명세표</a>
        </div>
    </div>

    <div class="fmap-meta">
        <span>버전 <?= e(isset($meta['version']) ? $meta['version'] : '-') ?></span>
        <span>슬라이드 <?= count(isset($deckSlides) ? $deckSlides : array()) ?>페이지 (DB)</span>
        <span><?= e(isset($meta['basis']) ? $meta['basis'] : '') ?></span>
    </div>

    <?php if ($isAdmin && $manage): ?>
    <section class="fmap-manage" id="fmap-manage">
        <div class="fmap-manage-head">
            <h2>슬라이드 페이지 관리</h2>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="<?= url('feature-map.php?manage=1') ?>" class="fmap-btn fmap-btn-ghost" style="padding:.45rem .8rem;font-size:.9rem">+ 새 슬라이드</a>
                <form method="post" style="display:inline" onsubmit="return confirm('기존 슬라이드를 모두 삭제하고 시드 파일로 다시 등록합니다. 계속할까요?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reseed">
                    <button type="submit" class="fmap-btn fmap-btn-ghost" style="padding:.45rem .8rem;font-size:.9rem">시드 재등록</button>
                </form>
            </div>
        </div>
        <div class="fmap-manage-body">
            <form method="post" class="fmap-doc-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_doc">
                <div class="fmap-form-grid cols-2">
                    <div class="full">
                        <label>문서 제목</label>
                        <input type="text" name="title" value="<?= e(isset($doc['title']) ? $doc['title'] : '') ?>" required>
                    </div>
                    <div class="full">
                        <label>부제</label>
                        <input type="text" name="subtitle" value="<?= e(isset($doc['subtitle']) ? $doc['subtitle'] : '') ?>">
                    </div>
                    <div>
                        <label>버전</label>
                        <input type="text" name="version" value="<?= e(isset($doc['version']) ? $doc['version'] : '1.0') ?>">
                    </div>
                    <div>
                        <label>기준</label>
                        <input type="text" name="basis" value="<?= e(isset($doc['basis']) ? $doc['basis'] : '') ?>">
                    </div>
                </div>
                <div class="fmap-form-actions">
                    <button type="submit" class="fmap-btn fmap-btn-primary" style="padding:.5rem 1rem;font-size:.9rem">문서 정보 저장</button>
                </div>
            </form>

            <form method="post" id="fmapSlideForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_slide">
                <input type="hidden" name="slide_id" value="<?= $editMode ? (int) $editSlide['id'] : 0 ?>">
                <h3 style="margin:0 0 .85rem;font-size:1.1rem"><?= $editMode ? '슬라이드 수정 #' . (int) $editSlide['id'] : '슬라이드 등록' ?></h3>
                <div class="fmap-form-grid cols-3">
                    <div>
                        <label>슬라이드 키</label>
                        <input type="text" name="slide_key" value="<?= e($editMode ? $editSlide['slide_key'] : '') ?>" placeholder="ed-design-f1" required>
                    </div>
                    <div>
                        <label>유형</label>
                        <select name="slide_type" id="fmapSlideType">
                            <?php
                            $curType = $editMode ? $editSlide['slide_type'] : 'feature';
                            foreach ($slideTypes as $tk => $tl):
                            ?>
                            <option value="<?= e($tk) ?>" <?= $curType === $tk ? 'selected' : '' ?>><?= e($tl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>순서</label>
                        <input type="number" name="sort_order" value="<?= e($editMode ? (string) $editSlide['sort_order'] : (string) (count($dbSlides) * 10 + 10)) ?>">
                    </div>
                    <div>
                        <label>톤</label>
                        <select name="tone">
                            <?php
                            $curTone = $editMode ? $editSlide['tone'] : 'teal';
                            foreach ($tones as $tk => $tl):
                            ?>
                            <option value="<?= e($tk) ?>" <?= $curTone === $tk ? 'selected' : '' ?>><?= e($tl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>키커</label>
                        <input type="text" name="kicker" value="<?= e($editMode ? $editSlide['kicker'] : '') ?>" placeholder="라벨편집 › 중분류">
                    </div>
                    <div>
                        <label>표시</label>
                        <label style="font-weight:500;display:flex;align-items:center;gap:.4rem;margin-top:.45rem">
                            <input type="checkbox" name="is_visible" value="1" <?= (!$editMode || !empty($editSlide['is_visible'])) ? 'checked' : '' ?>>
                            슬라이드 모드에 표시
                        </label>
                    </div>
                    <div class="full">
                        <label>제목</label>
                        <input type="text" name="title" value="<?= e($editMode ? $editSlide['title'] : '') ?>" required>
                    </div>
                    <div class="full">
                        <label>리드 (주요 설명)</label>
                        <textarea name="lead_text" rows="2"><?= e($editMode ? $editSlide['lead_text'] : '') ?></textarea>
                    </div>
                    <div class="full">
                        <label>부제 / 메타 한 줄</label>
                        <input type="text" name="subtitle" value="<?= e($editMode ? $editSlide['subtitle'] : '') ?>">
                    </div>

                    <div class="full fmap-field" data-for="cover">
                        <label>표지 메타 / 기준</label>
                        <input type="text" name="body_meta" value="<?= e($editExtras['body_meta']) ?>" placeholder="v1.0 · 2026-07-15" style="margin-bottom:.4rem">
                        <input type="text" name="body_basis" value="<?= e($editExtras['body_basis']) ?>" placeholder="메뉴 시드 · 스토리보드">
                    </div>
                    <div class="full fmap-field" data-for="category">
                        <label>대분류 코드</label>
                        <input type="text" name="body_code" value="<?= e($editExtras['body_code']) ?>" placeholder="ED">
                    </div>
                    <div class="full fmap-field" data-for="group">
                        <label>화면 / 우선순위 / 상위 대분류명</label>
                        <div class="fmap-form-grid cols-3">
                            <input type="text" name="body_screen" value="<?= e($editExtras['body_screen']) ?>" placeholder="01-05">
                            <input type="text" name="body_priority" value="<?= e($editExtras['body_priority']) ?>" placeholder="P0">
                            <input type="text" name="body_cat_name" value="<?= e($editExtras['body_cat_name']) ?>" placeholder="라벨편집">
                        </div>
                    </div>
                    <div class="full fmap-field" data-for="overview category group custom scope">
                        <label>목록 줄 (이름 | 설명)</label>
                        <textarea name="body_lines" rows="5"><?= e($editExtras['body_lines']) ?></textarea>
                        <p class="hint">한 줄에 하나. 예: 디자인 관리 | 새 작업과 내 디자인 진입점</p>
                    </div>
                    <div class="full fmap-field" data-for="feature custom scope">
                        <label>규칙 (한 줄에 하나)</label>
                        <textarea name="body_rules" rows="5"><?= e($editExtras['body_rules']) ?></textarea>
                    </div>
                    <div class="full fmap-field" data-for="bridges">
                        <label>연결점 (from => to | note)</label>
                        <textarea name="body_bridges" rows="5"><?= e($editExtras['body_bridges']) ?></textarea>
                        <p class="hint">예: 편집기 「출력」 => 장바구니 · 인쇄 의뢰 | 시안 첨부(P1)</p>
                    </div>
                    <div class="full">
                        <label style="display:flex;align-items:center;gap:.4rem;font-weight:600">
                            <input type="checkbox" name="use_body_json" value="1" id="fmapUseJson">
                            고급: body JSON 직접 저장 (체크 시 위 필드 대신 JSON 사용)
                        </label>
                        <textarea name="body_json" rows="6" style="margin-top:.4rem;font-family:ui-monospace,monospace;font-size:.85rem"><?= e($editExtras['body_json']) ?></textarea>
                    </div>
                </div>
                <div class="fmap-form-actions">
                    <button type="submit" class="fmap-btn fmap-btn-primary" style="padding:.55rem 1.1rem;font-size:.95rem"><?= $editMode ? '수정 저장' : '슬라이드 등록' ?></button>
                    <?php if ($editMode): ?>
                    <a href="<?= url('feature-map.php?manage=1') ?>" class="fmap-btn fmap-btn-ghost" style="padding:.55rem 1.1rem;font-size:.95rem">취소</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-wrap" style="margin-top:1.5rem">
                <table>
                    <thead>
                        <tr>
                            <th style="width:70px">순서</th>
                            <th style="width:90px">유형</th>
                            <th>제목</th>
                            <th style="width:80px">톤</th>
                            <th style="width:70px">표시</th>
                            <th style="width:140px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dbSlides)): ?>
                        <tr><td colspan="6">등록된 슬라이드가 없습니다. 「시드 재등록」을 사용하세요.</td></tr>
                        <?php else: ?>
                        <?php foreach ($dbSlides as $i => $s): ?>
                        <tr>
                            <td><?= (int) $s['sort_order'] ?></td>
                            <td><span class="fmap-badge"><?= e(isset($slideTypes[$s['slide_type']]) ? $slideTypes[$s['slide_type']] : $s['slide_type']) ?></span></td>
                            <td>
                                <strong><?= e($s['title']) ?></strong>
                                <div style="font-size:12px;color:var(--fmap-muted);margin-top:3px"><?= e($s['kicker']) ?> · <code><?= e($s['slide_key']) ?></code></div>
                            </td>
                            <td><span class="fmap-badge <?= e($s['tone']) ?>"><?= e($s['tone']) ?></span></td>
                            <td><?= !empty($s['is_visible']) ? 'Y' : '<span class="fmap-badge off">숨김</span>' ?></td>
                            <td style="white-space:nowrap">
                                <a class="btn btn-sm btn-outline" href="<?= url('feature-map.php?manage=1&edit=' . (int) $s['id']) ?>">편집</a>
                                <button type="button" class="btn btn-sm btn-outline" data-fmap-preview-id="<?= (int) $s['id'] ?>">미리보기</button>
                                <form method="post" style="display:inline" onsubmit="return confirm('이 슬라이드를 삭제할까요?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_slide">
                                    <input type="hidden" name="slide_id" value="<?= (int) $s['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline" style="color:#b91c1c">삭제</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!$manage): ?>
    <?php foreach ($categories as $cat): ?>
    <section class="fmap-cat" id="<?= e($cat['id']) ?>" data-color="<?= e($cat['color']) ?>">
        <header class="fmap-cat-head">
            <div class="fmap-cat-code"><?= e($cat['code']) ?> · 대분류</div>
            <h2><?= e($cat['name']) ?></h2>
            <p class="fmap-tagline"><?= e($cat['tagline']) ?></p>
            <p class="fmap-summary"><?= e($cat['summary']) ?></p>
        </header>
        <div class="fmap-groups">
            <?php foreach ($cat['groups'] as $g): ?>
            <article class="fmap-group" id="<?= e($g['id']) ?>">
                <div class="fmap-group-top">
                    <h3><?= e($g['name']) ?></h3>
                    <?php
                    $prio = isset($g['priority']) ? strtolower($g['priority']) : '';
                    $pillClass = $prio === 'p0' ? 'p0' : ($prio === 'p1' ? 'p1' : '');
                    ?>
                    <?php if (!empty($g['priority'])): ?>
                    <span class="fmap-pill <?= e($pillClass) ?>"><?= e($g['priority']) ?></span>
                    <?php endif; ?>
                </div>
                <p class="fmap-group-sum"><?= e($g['summary']) ?></p>
                <?php if (!empty($g['screen'])): ?>
                <div class="fmap-group-meta">화면 · <code><?= e($g['screen']) ?></code></div>
                <?php endif; ?>
                <?php foreach ($g['features'] as $f): ?>
                <div class="fmap-feat">
                    <h4><?= e($f['name']) ?></h4>
                    <p><?= e($f['desc']) ?></p>
                    <?php if (!empty($f['rules'])): ?>
                    <ul class="fmap-rules">
                        <?php foreach ($f['rules'] as $r): ?>
                        <li><?= e($r) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>

    <?php if (!empty($bridges)): ?>
    <section class="fmap-bridges" id="fmap-bridges">
        <h2>편집 ↔ 쇼핑 연결점</h2>
        <?php foreach ($bridges as $b): ?>
        <div class="fmap-bridge-row">
            <div><?= e($b['from']) ?></div>
            <div class="fmap-bridge-arrow">→</div>
            <div><?= e($b['to']) ?></div>
            <?php if (!empty($b['note'])): ?>
            <div class="fmap-bridge-note"><?= e($b['note']) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <?php if (!empty($scopeSummaries)): ?>
    <section class="fmap-scope" id="fmap-scope">
        <div class="fmap-scope-intro">
            <h2><?= e(isset($scope['title']) ? $scope['title'] : '구축 범위') ?></h2>
            <p><?= e(isset($scope['summary']) ? $scope['summary'] : '1차 구축과 고도화를 각각 상세 페이지에서 확인.') ?></p>
        </div>
        <div class="fmap-scope-cards">
            <?php foreach ($scopeSummaries as $ps): ?>
            <a class="fmap-scope-card <?= e($ps['tone']) ?>" href="<?= url($ps['url']) ?>">
                <?php if ($ps['period'] !== ''): ?>
                <div class="period"><?= e($ps['period']) ?></div>
                <?php endif; ?>
                <h3><?= e($ps['name']) ?></h3>
                <?php if ($ps['goal'] !== ''): ?>
                <p class="goal"><?= e($ps['goal']) ?></p>
                <?php endif; ?>
                <?php if (!empty($ps['areas'])): ?>
                <ul class="areas">
                    <?php foreach ($ps['areas'] as $an): ?>
                    <li><?= e($an) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <span class="cta">상세 보기 →</span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    <?php endif; ?>
</div>

<div class="fmap-deck" id="fmapDeck" aria-hidden="true" role="dialog" aria-label="슬라이드 모드">
    <div class="fmap-deck-bar">
        <strong>Label-UP · 슬라이드</strong>
        <span class="fmap-deck-progress" id="fmapDeckProgress">1 / 1</span>
        <div class="fmap-deck-controls">
            <button type="button" id="fmapPrev">← 이전</button>
            <button type="button" id="fmapNext">다음 →</button>
            <button type="button" class="fmap-deck-close" id="fmapCloseSlide">닫기</button>
        </div>
    </div>
    <div class="fmap-deck-body">
        <aside class="fmap-deck-tree" id="fmapDeckTree" aria-label="슬라이드 위치 트리">
            <div class="fmap-deck-tree-head">
                <strong>현재 위치</strong>
                <div class="fmap-deck-here" id="fmapDeckHere"></div>
            </div>
            <div class="fmap-deck-tree-scroll" id="fmapDeckTreeScroll"></div>
        </aside>
        <div class="fmap-deck-stage" id="fmapDeckStage"></div>
    </div>
</div>

<script>
(function () {
    var SLIDES = <?= $slideJson ?>;
    var deck = document.getElementById('fmapDeck');
    var stage = document.getElementById('fmapDeckStage');
    var treeEl = document.getElementById('fmapDeckTree');
    var treeScroll = document.getElementById('fmapDeckTreeScroll');
    var hereEl = document.getElementById('fmapDeckHere');
    var progress = document.getElementById('fmapDeckProgress');
    var idx = 0;
    var open = false;
    var TREE = null;

    function esc(s) {
        if (s == null) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /** 슬라이드 순서 → 트리 (대분류 > 중분류 > 기능) */
    function buildTree(slides) {
        var roots = [];
        var curCat = null;
        var curGroup = null;
        var misc = { label: '구성', nodes: [] };

        slides.forEach(function (s, i) {
            var node = {
                index: i,
                id: s.id || 0,
                type: s.type || 'custom',
                title: s.title || '(제목 없음)',
                tone: s.tone || 'teal',
                children: []
            };
            if (s.type === 'category') {
                curCat = node;
                curGroup = null;
                roots.push(node);
            } else if (s.type === 'group') {
                curGroup = node;
                if (curCat) curCat.children.push(node);
                else roots.push(node);
            } else if (s.type === 'feature') {
                if (curGroup) curGroup.children.push(node);
                else if (curCat) curCat.children.push(node);
                else roots.push(node);
            } else {
                misc.nodes.push(node);
            }
        });

        return { roots: roots, misc: misc.nodes };
    }

    function collectAncestors(index) {
        var set = {};
        var s = SLIDES[index];
        if (!s) return set;
        set[index] = true;
        // path 기반 + 인덱스 역추적
        var cat = s.cat || '';
        var group = s.group || '';
        for (var i = 0; i <= index; i++) {
            var t = SLIDES[i];
            if (!t) continue;
            if (t.type === 'category' && t.title === cat) set[i] = true;
            if (t.type === 'group' && t.title === group && (!cat || t.cat === cat || !t.cat)) set[i] = true;
        }
        return set;
    }

    function renderHere(i) {
        var s = SLIDES[i];
        if (!s || !hereEl) return;
        var path = s.path && s.path.length ? s.path : [s.title || ''];
        var tone = s.tone === 'amber' ? 'amber' : 'teal';
        hereEl.setAttribute('data-tone', tone);
        var html = '';
        path.forEach(function (p, n) {
            var cls = 'fmap-here-line';
            if (n === 0) cls += '';
            else if (n === path.length - 1) cls += ' is-leaf';
            else cls += ' is-branch';
            var prefix = n === 0 ? '' : 'ㄴ ';
            html += '<span class="' + cls + '">' + esc(prefix + p) + '</span>';
        });
        hereEl.innerHTML = html || '<span class="fmap-here-line">—</span>';
        if (treeEl) treeEl.setAttribute('data-tone', tone);
    }

    function renderTreeNode(node, depth, activeSet) {
        var mark = depth === 0 ? '●' : 'ㄴ';
        var cls = 'fmap-tree-btn';
        if (activeSet[node.index]) {
            cls += node.index === idx ? ' is-current' : ' is-ancestor';
        } else if (node.type === 'category' || node.type === 'group' || node.type === 'feature') {
            // 다른 대분류는 살짝 흐리게
            if (SLIDES[idx] && (SLIDES[idx].type === 'category' || SLIDES[idx].type === 'group' || SLIDES[idx].type === 'feature')) {
                if (node.type === 'category' && !activeSet[node.index]) cls += ' is-dim';
            }
        }
        var html =
            '<li class="fmap-tree-item">' +
            '<button type="button" class="' + cls + '" data-tone="' + esc(node.tone) + '" data-goto="' + node.index + '" title="' + esc(node.title) + '">' +
            '<span class="fmap-tree-mark">' + mark + '</span>' +
            '<span class="fmap-tree-label">' + esc(node.title) + '</span>' +
            '</button>';
        if (node.children && node.children.length) {
            html += '<ul class="fmap-tree-ul">';
            node.children.forEach(function (ch) {
                html += renderTreeNode(ch, depth + 1, activeSet);
            });
            html += '</ul>';
        }
        html += '</li>';
        return html;
    }

    function renderTree() {
        if (!treeScroll) return;
        if (!TREE) TREE = buildTree(SLIDES);
        var activeSet = collectAncestors(idx);
        var html = '';

        if (TREE.misc && TREE.misc.length) {
            html += '<div class="fmap-tree-sec">안내</div><ul class="fmap-tree-ul">';
            TREE.misc.forEach(function (n) {
                html += renderTreeNode(n, 0, activeSet);
            });
            html += '</ul>';
        }

        if (TREE.roots && TREE.roots.length) {
            html += '<div class="fmap-tree-sec">기능 구조</div><ul class="fmap-tree-ul">';
            TREE.roots.forEach(function (n) {
                html += renderTreeNode(n, 0, activeSet);
            });
            html += '</ul>';
        }

        treeScroll.innerHTML = html || '<div class="fmap-tree-sec">슬라이드 없음</div>';
        treeScroll.querySelectorAll('[data-goto]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                show(parseInt(btn.getAttribute('data-goto'), 10) || 0);
            });
        });

        // 현재 항목이 보이도록 스크롤
        var cur = treeScroll.querySelector('.fmap-tree-btn.is-current');
        if (cur && cur.scrollIntoView) {
            cur.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    function renderLines(lines) {
        var html = '';
        (lines || []).forEach(function (line) {
            html += '<div><strong>' + esc(line.name || '') + '</strong><span>' + esc(line.desc || '') + '</span></div>';
        });
        return html ? '<div class="fmap-group-list">' + html + '</div>' : '';
    }

    function renderSlide(s) {
        var tone = s.tone === 'amber' ? 'amber' : 'teal';
        var kicker = s.kicker || '';
        var title = s.title || '';
        var lead = s.lead || '';
        var sub = s.subtitle || '';
        var type = s.type || 'custom';
        var html = '';

        if (type === 'cover') {
            html =
                '<div class="fmap-slide fmap-slide-cover is-active" data-tone="' + tone + '">' +
                '<p class="kicker">' + esc(kicker || 'LABEL-UP') + '</p>' +
                '<h2>' + esc(title) + '</h2>' +
                '<p class="lead">' + esc(s.subtitle || lead) + '</p>' +
                '<p class="sub">' + esc(s.meta || '') + '<br>' + esc(s.basis || '') + '</p>' +
                '</div>';
        } else if (type === 'overview') {
            var cards = '';
            (s.cards || []).forEach(function (c) {
                var items = (c.items || []).map(esc).join(' · ');
                cards +=
                    '<div class="fmap-ov-card ' + esc(c.color || 'teal') + '">' +
                    '<h3>' + esc(c.name) + '</h3>' +
                    '<p>' + esc(c.tagline) + '</p>' +
                    (items ? '<ul><li>' + items + '</li></ul>' : '') +
                    '</div>';
            });
            html =
                '<div class="fmap-slide is-active" data-tone="' + tone + '">' +
                '<p class="kicker">' + esc(kicker || 'OVERVIEW') + '</p>' +
                '<h2>' + esc(title) + '</h2>' +
                '<p class="lead">' + esc(sub || lead) + '</p>' +
                '<div class="fmap-ov-grid">' + cards + '</div>' +
                '</div>';
        } else if (type === 'bridges') {
            var rows = '';
            (s.bridges || []).forEach(function (b) {
                rows +=
                    '<div class="row"><div>' + esc(b.from) + '</div><div class="arr">→</div><div>' + esc(b.to) + '</div>' +
                    (b.note ? '<div class="note">' + esc(b.note) + '</div>' : '') + '</div>';
            });
            html =
                '<div class="fmap-slide fmap-bridge-slide is-active" data-tone="' + tone + '">' +
                '<p class="kicker">' + esc(kicker || 'CONNECTIONS') + '</p>' +
                '<h2>' + esc(title) + '</h2>' + rows + '</div>';
        } else if (type === 'end') {
            html =
                '<div class="fmap-slide fmap-slide-cover is-active" data-tone="' + tone + '">' +
                '<p class="kicker">' + esc(kicker || 'DONE') + '</p>' +
                '<h2>' + esc(title) + '</h2>' +
                '<p class="lead">' + esc(lead || sub) + '</p></div>';
        } else if (type === 'feature') {
            if (s.schedule && s.schedule.length) {
                var cards = '';
                s.schedule.forEach(function (w) {
                    var dels = '';
                    (w.deliverables || []).forEach(function (d) { dels += '<li>' + esc(d) + '</li>'; });
                    cards +=
                        '<div class="fmap-sched-card">' +
                        '<p class="wh">' + esc(w.wave || '') + '</p>' +
                        '<p class="wp">' + esc(w.period || '') + '</p>' +
                        '<p class="wf">' + esc(w.focus || '') + '</p>' +
                        (dels ? '<ul>' + dels + '</ul>' : '') +
                        '</div>';
                });
                html =
                    '<div class="fmap-slide is-active" data-tone="' + tone + '">' +
                    '<p class="kicker">' + esc(kicker) + '</p>' +
                    '<h2>' + esc(title) + '</h2>' +
                    '<p class="lead">' + esc(lead) + '</p>' +
                    '<div class="fmap-sched-slide">' + cards + '</div>' +
                    '</div>';
            } else {
                var rules = '';
                (s.rules || []).forEach(function (r) { rules += '<li>' + esc(r) + '</li>'; });
                html =
                    '<div class="fmap-slide is-active" data-tone="' + tone + '">' +
                    '<p class="kicker">' + esc(kicker) + '</p>' +
                    '<h2>' + esc(title) + '</h2>' +
                    '<p class="lead">' + esc(lead) + '</p>' +
                    (rules ? '<ul class="feat-rules">' + rules + '</ul>' : '') +
                    '</div>';
            }
        } else if (type === 'scope') {
            var rulesS = '';
            (s.rules || []).forEach(function (r) { rulesS += '<li>' + esc(r) + '</li>'; });
            html =
                '<div class="fmap-slide is-active" data-tone="' + tone + '">' +
                '<p class="kicker">' + esc(kicker || 'SCOPE') + '</p>' +
                '<h2>' + esc(title) + '</h2>' +
                (lead ? '<p class="lead">' + esc(lead) + '</p>' : '') +
                (sub ? '<p class="sub">' + esc(sub) + '</p>' : '') +
                renderLines(s.lines) +
                (rulesS ? '<ul class="feat-rules">' + rulesS + '</ul>' : '') +
                '</div>';
        } else {
            // category / group / custom
            var rules2 = '';
            (s.rules || []).forEach(function (r) { rules2 += '<li>' + esc(r) + '</li>'; });
            html =
                '<div class="fmap-slide is-active" data-tone="' + tone + '">' +
                '<p class="kicker">' + esc(kicker) + '</p>' +
                '<h2>' + esc(title) + '</h2>' +
                (lead ? '<p class="lead">' + esc(lead) + '</p>' : '') +
                (sub ? '<p class="sub">' + esc(sub) + '</p>' : '') +
                renderLines(s.lines) +
                (rules2 ? '<ul class="feat-rules">' + rules2 + '</ul>' : '') +
                '</div>';
        }
        return html;
    }

    function show(i) {
        if (!SLIDES.length) return;
        idx = Math.max(0, Math.min(SLIDES.length - 1, i));
        stage.innerHTML = renderSlide(SLIDES[idx]);
        var h = document.createElement('div');
        h.className = 'fmap-deck-hint';
        h.textContent = '← → 키 · Space 다음 · Esc 닫기';
        stage.appendChild(h);
        progress.textContent = (idx + 1) + ' / ' + SLIDES.length;
        renderHere(idx);
        renderTree();
    }

    function openDeck(start) {
        if (!SLIDES.length) { alert('표시할 슬라이드가 없습니다.'); return; }
        open = true;
        deck.classList.add('is-open');
        deck.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        show(typeof start === 'number' ? start : 0);
    }

    function closeDeck() {
        open = false;
        deck.classList.remove('is-open');
        deck.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    var openBtn = document.getElementById('fmapOpenSlide');
    if (openBtn) openBtn.addEventListener('click', function () { openDeck(0); });
    document.getElementById('fmapCloseSlide').addEventListener('click', closeDeck);
    document.getElementById('fmapPrev').addEventListener('click', function () { show(idx - 1); });
    document.getElementById('fmapNext').addEventListener('click', function () { show(idx + 1); });

    document.querySelectorAll('[data-fmap-preview-id]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = parseInt(btn.getAttribute('data-fmap-preview-id'), 10) || 0;
            var found = 0;
            for (var i = 0; i < SLIDES.length; i++) {
                if (SLIDES[i].id === id) { found = i; break; }
            }
            openDeck(found);
        });
    });

    document.addEventListener('keydown', function (e) {
        if (!open) return;
        if (e.key === 'Escape') { e.preventDefault(); closeDeck(); }
        else if (e.key === 'ArrowRight' || e.key === ' ' || e.key === 'PageDown') { e.preventDefault(); show(idx + 1); }
        else if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); show(idx - 1); }
        else if (e.key === 'Home') { e.preventDefault(); show(0); }
        else if (e.key === 'End') { e.preventDefault(); show(SLIDES.length - 1); }
    });

    // 폼 필드 토글
    var typeSel = document.getElementById('fmapSlideType');
    function syncFields() {
        if (!typeSel) return;
        var t = typeSel.value;
        document.querySelectorAll('.fmap-field').forEach(function (el) {
            var forTypes = (el.getAttribute('data-for') || '').split(/\s+/);
            el.style.display = forTypes.indexOf(t) !== -1 ? '' : 'none';
        });
    }
    if (typeSel) {
        typeSel.addEventListener('change', syncFields);
        syncFields();
    }

    <?php if ($editMode && $manage): ?>
    // 편집 진입 시 폼으로 스크롤
    var form = document.getElementById('fmapSlideForm');
    if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    <?php endif; ?>

    var m = location.hash.match(/slide=(\d+)/);
    if (m) openDeck(parseInt(m[1], 10) - 1);
})();
</script>
