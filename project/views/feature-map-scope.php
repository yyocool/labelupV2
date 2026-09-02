<?php
/**
 * 구축 범위 단계 상세 (1차 / 고도화)
 * @var array $scope
 * @var array $phase
 * @var array $phaseSummaries
 * @var array $deckSlides
 * @var string $tone
 * @var string $phaseId
 */
$slideJson = json_encode(isset($deckSlides) ? $deckSlides : array(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
if ($slideJson === false) {
    $slideJson = '[]';
}
$areas = isset($phase['areas']) ? $phase['areas'] : array();
$schedule = isset($phase['schedule']) ? $phase['schedule'] : array();
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+KR:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

<style>
.fsc {
    --ink: #14202e; --muted: #5a6a7a; --line: #d5dde6; --paper: #f3f6f9;
    --teal: #0a6b6b; --teal-deep: #064e4e; --amber: #b45309; --amber-deep: #7c3a00;
    font-family: "IBM Plex Sans KR", sans-serif; color: var(--ink);
}
.fsc * { box-sizing: border-box; }
.fsc-crumb { font-size: .95rem; color: var(--muted); margin-bottom: 1rem; }
.fsc-crumb a { color: var(--teal); text-decoration: none; font-weight: 600; }
.fsc-crumb a:hover { text-decoration: underline; }

.fsc-hero {
    padding: 1.75rem 1.6rem; border-radius: 4px; color: #fff; margin-bottom: 1.5rem;
    display: flex; flex-wrap: wrap; gap: 1.25rem; justify-content: space-between; align-items: flex-end;
}
.fsc[data-tone="teal"] .fsc-hero { background: linear-gradient(125deg, var(--teal-deep), var(--teal)); }
.fsc[data-tone="amber"] .fsc-hero { background: linear-gradient(125deg, var(--amber-deep), var(--amber)); }
.fsc-hero .period { font-family: Outfit, sans-serif; font-weight: 700; letter-spacing: .06em; opacity: .9; margin-bottom: .35rem; }
.fsc-hero h1 { font-family: Outfit, sans-serif; font-size: clamp(1.9rem, 3vw, 2.6rem); font-weight: 800; margin: 0 0 .45rem; letter-spacing: -0.03em; }
.fsc-hero .goal { margin: 0; font-size: 1.1rem; line-height: 1.5; opacity: .92; max-width: 40rem; }
.fsc-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
.fsc-btn {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .65rem 1.1rem; border-radius: 4px; font-weight: 600; font-size: .98rem;
    border: 1px solid transparent; cursor: pointer; text-decoration: none; font-family: inherit; color: inherit; background: none;
}
.fsc-btn-primary { background: #fff; color: #0f172a; }
.fsc-btn-primary:hover { background: #f1f5f9; color: #0f172a; }
.fsc-btn-ghost { background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.35); color: #fff; }
.fsc-btn-ghost:hover { background: rgba(255,255,255,.2); color: #fff; }

.fsc-layout { display: grid; gap: 1.25rem; }
@media (min-width: 960px) {
    .fsc-layout { grid-template-columns: 240px 1fr; align-items: start; }
}
.fsc-toc {
    position: sticky; top: 1rem;
    border: 1px solid var(--line); border-radius: 4px; background: #fff; padding: 1rem;
}
.fsc-toc h2 { font-size: .8rem; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin: 0 0 .75rem; font-family: Outfit, sans-serif; }
.fsc-toc a {
    display: block; padding: .45rem .5rem; border-radius: 3px; text-decoration: none;
    color: var(--ink); font-size: .95rem; font-weight: 500; margin-bottom: 2px;
}
.fsc-toc a:hover { background: var(--paper); }
.fsc[data-tone="teal"] .fsc-toc a.is-active { background: #ccfbf1; color: #115e59; font-weight: 700; }
.fsc[data-tone="amber"] .fsc-toc a.is-active { background: #fde68a; color: #78350f; font-weight: 700; }
.fsc-toc .peer { margin-top: 1rem; padding-top: .85rem; border-top: 1px solid var(--line); }
.fsc-toc .peer a { font-size: .9rem; color: var(--muted); }

.fsc-section { scroll-margin-top: 5rem; margin-bottom: 1.5rem; border: 1px solid var(--line); border-radius: 4px; background: #fff; overflow: hidden; }
.fsc-section-head {
    padding: 1.1rem 1.25rem; background: var(--paper); border-bottom: 1px solid var(--line);
    display: flex; flex-wrap: wrap; gap: .5rem; justify-content: space-between; align-items: baseline;
}
.fsc[data-tone="teal"] .fsc-section { border-left: 4px solid var(--teal); }
.fsc[data-tone="amber"] .fsc-section { border-left: 4px solid var(--amber); }
.fsc-section-head h2 { margin: 0; font-size: 1.35rem; font-family: Outfit, sans-serif; font-weight: 700; }
.fsc-section-head .sub { margin: 0; font-size: .95rem; color: var(--muted); }
.fsc-section-body { padding: 1.15rem 1.25rem 1.35rem; }

.fsc-sched { margin-bottom: 0; overflow-x: auto; }
.fsc-sched table { width: 100%; border-collapse: collapse; font-size: 1rem; }
.fsc-sched th, .fsc-sched td { padding: .85rem .8rem; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
.fsc-sched th { background: #f1f5f9; font-size: .82rem; color: var(--muted); }
.fsc-sched .wave { font-weight: 800; font-family: Outfit, sans-serif; white-space: nowrap; }
.fsc[data-tone="amber"] .fsc-sched .wave { color: var(--amber); }
.fsc-sched ul { margin: .4rem 0 0; padding-left: 1.15rem; line-height: 1.55; }

.fsc-block {
    border: 1px solid var(--line); border-radius: 3px; padding: 1rem 1.1rem;
    margin-bottom: .85rem; background: var(--paper);
}
.fsc-block:last-child { margin-bottom: 0; }
.fsc-block h3 { margin: 0 0 .35rem; font-size: 1.15rem; }
.fsc-block .desc { margin: 0 0 .65rem; color: #334155; font-size: 1rem; line-height: 1.45; }
.fsc-block ul { margin: 0; padding-left: 1.2rem; font-size: 1.02rem; line-height: 1.6; }
.fsc-block li { margin-bottom: .28rem; }

.fsc-switch {
    display: grid; gap: .85rem; margin-top: 2rem;
}
@media (min-width: 720px) { .fsc-switch { grid-template-columns: 1fr 1fr; } }
.fsc-switch a {
    display: block; padding: 1.25rem 1.35rem; border-radius: 4px; text-decoration: none; color: #fff;
}
.fsc-switch a.teal { background: linear-gradient(120deg, var(--teal-deep), var(--teal)); }
.fsc-switch a.amber { background: linear-gradient(120deg, var(--amber-deep), var(--amber)); }
.fsc-switch a strong { display: block; font-family: Outfit, sans-serif; font-size: 1.35rem; margin-bottom: .35rem; }
.fsc-switch a span { font-size: .95rem; opacity: .9; line-height: 1.4; }

/* deck (reuse compact) */
.fsc-deck { position: fixed; inset: 0; z-index: 10000; display: none; flex-direction: column; background: #0d1520; color: #f4f7fa; font-family: "IBM Plex Sans KR", sans-serif; }
.fsc-deck.is-open { display: flex; }
.fsc-deck-bar { flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .75rem 1.25rem; background: rgba(0,0,0,.35); border-bottom: 1px solid rgba(255,255,255,.08); }
.fsc-deck-bar strong { font-family: Outfit, sans-serif; }
.fsc-deck-controls button { font-family: inherit; font-size: 1rem; font-weight: 600; padding: .5rem .9rem; border-radius: 3px; border: 1px solid rgba(255,255,255,.2); background: rgba(255,255,255,.06); color: #fff; cursor: pointer; }
.fsc-deck-controls button.fsc-close { background: #e11d48; border-color: #e11d48; }
.fsc-deck-body { flex: 1; display: flex; min-height: 0; }
.fsc-deck-tree { flex: 0 0 min(260px, 32vw); background: #0a1018; border-right: 1px solid rgba(255,255,255,.08); display: flex; flex-direction: column; min-height: 0; }
.fsc-deck-tree-head { padding: .85rem 1rem; border-bottom: 1px solid rgba(255,255,255,.08); }
.fsc-deck-tree-head strong { display: block; font-size: .8rem; letter-spacing: .08em; text-transform: uppercase; color: rgba(255,255,255,.4); margin-bottom: .4rem; font-family: Outfit, sans-serif; }
.fsc-here-line { display: block; font-size: .95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fsc-here-line.is-branch { padding-left: .85rem; color: rgba(244,247,250,.7); }
.fsc-here-line.is-leaf { padding-left: 1.7rem; color: #5eead4; font-weight: 700; }
.fsc[data-tone="amber"] .fsc-here-line.is-leaf, .fsc-deck-tree[data-tone="amber"] .fsc-here-line.is-leaf { color: #fbbf24; }
.fsc-deck-tree-scroll { flex: 1; overflow: auto; padding: .55rem; }
.fsc-tree-ul { list-style: none; margin: 0; padding: 0; }
.fsc-tree-ul .fsc-tree-ul { margin-left: .5rem; padding-left: .5rem; border-left: 1px solid rgba(255,255,255,.12); }
.fsc-tree-btn { display: flex; gap: .35rem; width: 100%; text-align: left; padding: .4rem .5rem; border: none; border-radius: 3px; background: transparent; color: rgba(244,247,250,.62); font-family: inherit; font-size: .9rem; cursor: pointer; }
.fsc-tree-btn:hover { background: rgba(255,255,255,.06); color: #fff; }
.fsc-tree-btn.is-current { background: rgba(20,184,166,.18); color: #5eead4; font-weight: 700; box-shadow: inset 2px 0 0 #14b8a6; }
.fsc-deck-tree[data-tone="amber"] .fsc-tree-btn.is-current { background: rgba(245,158,11,.16); color: #fbbf24; box-shadow: inset 2px 0 0 #f59e0b; }
.fsc-deck-tree[data-tone="amber"] .fsc-tree-btn.is-ancestor { color: rgba(244,247,250,.88); font-weight: 600; }
.fsc-tree-btn.is-ancestor { color: rgba(244,247,250,.88); font-weight: 600; }
.fsc-deck-stage { flex: 1; display: flex; align-items: center; justify-content: center; padding: 1.5rem; overflow: hidden; position: relative; min-width: 0; }
.fsc-slide { width: min(1000px, 96vw); max-height: calc(100vh - 6.5rem); overflow: auto; padding: clamp(1.4rem, 3.5vw, 2.75rem); border-radius: 6px; background: radial-gradient(ellipse at 20% 0%, rgba(10,107,107,.25), transparent 55%), radial-gradient(ellipse at 90% 100%, rgba(180,83,9,.18), transparent 50%), #152233; border: 1px solid rgba(255,255,255,.08); }
.fsc-slide .kicker { font-family: Outfit, sans-serif; font-size: 1.05rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #5eead4; margin: 0 0 .7rem; }
.fsc-slide[data-tone="amber"] .kicker { color: #fbbf24; }
.fsc-slide h2 { font-family: Outfit, sans-serif; font-size: clamp(1.9rem, 3.8vw, 3rem); font-weight: 800; margin: 0 0 .9rem; letter-spacing: -0.03em; line-height: 1.15; }
.fsc-slide .lead { font-size: clamp(1.15rem, 2vw, 1.5rem); line-height: 1.5; color: rgba(244,247,250,.88); margin: 0 0 1.2rem; }
.fsc-slide .sub { font-size: 1.15rem; color: rgba(244,247,250,.65); margin: 0 0 1rem; }
.fsc-group-list { display: grid; gap: .55rem; }
.fsc-group-list div { display: flex; gap: .85rem; padding: .7rem .9rem; background: rgba(0,0,0,.22); border-radius: 3px; border-left: 4px solid #14b8a6; font-size: 1.15rem; }
.fsc-slide[data-tone="amber"] .fsc-group-list div { border-left-color: #f59e0b; }
.fsc-group-list strong { min-width: 7rem; }
.fsc-group-list span { color: rgba(244,247,250,.75); font-size: 1.05rem; }
.fsc-feat-rules { list-style: none; margin: 1rem 0 0; padding: 0; counter-reset: fsc-r; }
.fsc-feat-rules li { position: relative; padding: .75rem 1rem .75rem 2.5rem; margin-bottom: .45rem; font-size: clamp(1.05rem, 1.8vw, 1.3rem); line-height: 1.45; background: rgba(0,0,0,.25); border-radius: 3px; }
.fsc-feat-rules li::before { content: counter(fsc-r); counter-increment: fsc-r; position: absolute; left: .75rem; top: .8rem; width: 1.3rem; height: 1.3rem; border-radius: 50%; background: #14b8a6; color: #042f2e; font-family: Outfit, sans-serif; font-weight: 800; font-size: .85rem; display: flex; align-items: center; justify-content: center; }
.fsc-slide[data-tone="amber"] .fsc-feat-rules li::before { background: #fbbf24; color: #451a03; }
.fsc-sched-slide { display: grid; gap: .7rem; }
.fsc-sched-card { padding: .95rem 1rem; background: rgba(0,0,0,.25); border-radius: 4px; border-left: 4px solid #f59e0b; }
.fsc-sched-card .wh { font-family: Outfit, sans-serif; font-size: 1.2rem; font-weight: 800; color: #fbbf24; margin: 0 0 .2rem; }
.fsc-sched-card .wp { margin: 0 0 .35rem; color: rgba(244,247,250,.65); }
.fsc-sched-card .wf { margin: 0 0 .4rem; font-size: 1.1rem; }
.fsc-sched-card ul { margin: 0; padding-left: 1.15rem; }
.fsc-deck-hint { position: absolute; bottom: .75rem; left: 50%; transform: translateX(-50%); font-size: .85rem; color: rgba(255,255,255,.35); pointer-events: none; }
@media (max-width: 640px) { .fsc-deck-tree { display: none; } }
</style>

<div class="fsc" data-tone="<?= e($tone) ?>">
    <nav class="fsc-crumb" aria-label="경로">
        <a href="<?= url('feature-map.php') ?>">기능 구조 맵</a>
        · <a href="<?= url('feature-map.php#fmap-scope') ?>">구축 범위</a>
        · <strong><?= e(isset($phase['name']) ? $phase['name'] : '') ?></strong>
    </nav>

    <header class="fsc-hero">
        <div>
            <?php if (!empty($phase['period'])): ?>
            <div class="period"><?= e($phase['period']) ?></div>
            <?php endif; ?>
            <h1><?= e(isset($phase['name']) ? $phase['name'] : '') ?></h1>
            <?php if (!empty($phase['goal'])): ?>
            <p class="goal"><?= e($phase['goal']) ?></p>
            <?php endif; ?>
        </div>
        <div class="fsc-actions">
            <button type="button" class="fsc-btn fsc-btn-primary" id="fscOpenSlide">슬라이드 모드</button>
            <a href="<?= url('feature-map.php') ?>" class="fsc-btn fsc-btn-ghost">전체 맵</a>
        </div>
    </header>

    <div class="fsc-layout">
        <aside class="fsc-toc">
            <h2>목차</h2>
            <?php if (!empty($schedule)): ?>
            <a href="#fsc-schedule">고도화 일정</a>
            <?php endif; ?>
            <?php foreach ($areas as $area): ?>
            <a href="#<?= e(isset($area['id']) ? $area['id'] : '') ?>"><?= e(isset($area['name']) ? $area['name'] : '') ?></a>
            <?php endforeach; ?>
            <div class="peer">
                <h2>다른 단계</h2>
                <?php foreach ($phaseSummaries as $ps): ?>
                <?php if ($ps['id'] === $phaseId) continue; ?>
                <a href="<?= url($ps['url']) ?>"><?= e($ps['name']) ?> →</a>
                <?php endforeach; ?>
            </div>
        </aside>

        <div class="fsc-main">
            <?php if (!empty($schedule)): ?>
            <section class="fsc-section" id="fsc-schedule">
                <div class="fsc-section-head">
                    <h2>일정 (Wave)</h2>
                    <p class="sub"><?= e(isset($phase['period']) ? $phase['period'] : '') ?></p>
                </div>
                <div class="fsc-section-body">
                    <div class="fsc-sched">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:90px">Wave</th>
                                    <th style="width:150px">기간</th>
                                    <th>초점 · 산출물 · 마일스톤</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedule as $w): ?>
                                <tr>
                                    <td class="wave"><?= e(isset($w['wave']) ? $w['wave'] : '') ?></td>
                                    <td><?= e(isset($w['period']) ? $w['period'] : '') ?></td>
                                    <td>
                                        <strong><?= e(isset($w['focus']) ? $w['focus'] : '') ?></strong>
                                        <?php if (!empty($w['deliverables'])): ?>
                                        <ul>
                                            <?php foreach ($w['deliverables'] as $d): ?>
                                            <li><?= e($d) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <?php foreach ($areas as $area): ?>
            <section class="fsc-section" id="<?= e(isset($area['id']) ? $area['id'] : '') ?>">
                <div class="fsc-section-head">
                    <h2><?= e(isset($area['name']) ? $area['name'] : '') ?></h2>
                    <?php if (!empty($area['subtitle'])): ?>
                    <p class="sub"><?= e($area['subtitle']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="fsc-section-body">
                    <?php foreach (isset($area['blocks']) ? $area['blocks'] : array() as $b): ?>
                    <article class="fsc-block">
                        <h3><?= e(isset($b['name']) ? $b['name'] : '') ?></h3>
                        <?php if (!empty($b['desc'])): ?>
                        <p class="desc"><?= e($b['desc']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($b['items'])): ?>
                        <ul>
                            <?php foreach ($b['items'] as $it): ?>
                            <li><?= e($it) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endforeach; ?>

            <?php if (count($phaseSummaries) > 1): ?>
            <div class="fsc-switch">
                <?php foreach ($phaseSummaries as $ps): ?>
                <?php if ($ps['id'] === $phaseId) continue; ?>
                <a class="<?= e($ps['tone']) ?>" href="<?= url($ps['url']) ?>">
                    <strong><?= e($ps['name']) ?> 상세 보기</strong>
                    <span><?= e($ps['period']) ?><?= $ps['goal'] !== '' ? ' · ' . e($ps['goal']) : '' ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="fsc-deck" id="fscDeck" aria-hidden="true">
    <div class="fsc-deck-bar">
        <strong><?= e(isset($phase['name']) ? $phase['name'] : '구축 범위') ?> · 슬라이드</strong>
        <span id="fscProgress">1 / 1</span>
        <div class="fsc-deck-controls">
            <button type="button" id="fscPrev">← 이전</button>
            <button type="button" id="fscNext">다음 →</button>
            <button type="button" class="fsc-close" id="fscClose">닫기</button>
        </div>
    </div>
    <div class="fsc-deck-body">
        <aside class="fsc-deck-tree" id="fscTree" data-tone="<?= e($tone) ?>">
            <div class="fsc-deck-tree-head">
                <strong>현재 위치</strong>
                <div id="fscHere"></div>
            </div>
            <div class="fsc-deck-tree-scroll" id="fscTreeScroll"></div>
        </aside>
        <div class="fsc-deck-stage" id="fscStage"></div>
    </div>
</div>

<script>
(function () {
    var SLIDES = <?= $slideJson ?>;
    var toneDefault = <?= json_encode($tone) ?>;
    var deck = document.getElementById('fscDeck');
    var stage = document.getElementById('fscStage');
    var treeScroll = document.getElementById('fscTreeScroll');
    var hereEl = document.getElementById('fscHere');
    var treeEl = document.getElementById('fscTree');
    var progress = document.getElementById('fscProgress');
    var idx = 0, open = false, TREE = null;

    function esc(s) {
        return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function buildTree(slides) {
        var roots = [], curCat = null, curGroup = null, misc = [];
        slides.forEach(function (s, i) {
            var node = { index: i, type: s.type, title: s.title || '(제목 없음)', tone: s.tone || toneDefault, children: [] };
            if (s.type === 'category') { curCat = node; curGroup = null; roots.push(node); }
            else if (s.type === 'group') { curGroup = node; if (curCat) curCat.children.push(node); else roots.push(node); }
            else if (s.type === 'feature') {
                if (curGroup) curGroup.children.push(node);
                else if (curCat) curCat.children.push(node);
                else roots.push(node);
            } else { misc.push(node); }
        });
        return { roots: roots, misc: misc };
    }

    function renderHere(i) {
        var s = SLIDES[i]; if (!s || !hereEl) return;
        var path = s.path && s.path.length ? s.path : [s.title || ''];
        var tone = s.tone === 'amber' ? 'amber' : 'teal';
        if (treeEl) treeEl.setAttribute('data-tone', tone);
        var html = '';
        path.forEach(function (p, n) {
            var cls = 'fsc-here-line' + (n === path.length - 1 && n > 0 ? ' is-leaf' : (n > 0 ? ' is-branch' : ''));
            html += '<span class="' + cls + '">' + esc((n === 0 ? '' : 'ㄴ ') + p) + '</span>';
        });
        hereEl.innerHTML = html;
    }

    function collectAncestors(index) {
        var set = {}, s = SLIDES[index]; if (!s) return set;
        set[index] = true;
        var cat = s.cat || '', group = s.group || '';
        for (var i = 0; i <= index; i++) {
            var t = SLIDES[i]; if (!t) continue;
            if (t.type === 'category' && t.title === cat) set[i] = true;
            if (t.type === 'group' && t.title === group) set[i] = true;
        }
        return set;
    }

    function renderTreeNode(node, depth, activeSet) {
        var mark = depth === 0 ? '●' : 'ㄴ';
        var cls = 'fsc-tree-btn';
        if (activeSet[node.index]) cls += node.index === idx ? ' is-current' : ' is-ancestor';
        var html = '<li><button type="button" class="' + cls + '" data-goto="' + node.index + '"><span>' + mark + '</span><span>' + esc(node.title) + '</span></button>';
        if (node.children && node.children.length) {
            html += '<ul class="fsc-tree-ul">';
            node.children.forEach(function (ch) { html += renderTreeNode(ch, depth + 1, activeSet); });
            html += '</ul>';
        }
        return html + '</li>';
    }

    function renderTree() {
        if (!treeScroll) return;
        if (!TREE) TREE = buildTree(SLIDES);
        var active = collectAncestors(idx);
        var html = '';
        if (TREE.misc.length) {
            html += '<ul class="fsc-tree-ul">';
            TREE.misc.forEach(function (n) { html += renderTreeNode(n, 0, active); });
            html += '</ul>';
        }
        if (TREE.roots.length) {
            html += '<ul class="fsc-tree-ul">';
            TREE.roots.forEach(function (n) { html += renderTreeNode(n, 0, active); });
            html += '</ul>';
        }
        treeScroll.innerHTML = html;
        treeScroll.querySelectorAll('[data-goto]').forEach(function (btn) {
            btn.addEventListener('click', function () { show(parseInt(btn.getAttribute('data-goto'), 10) || 0); });
        });
        var cur = treeScroll.querySelector('.fsc-tree-btn.is-current');
        if (cur && cur.scrollIntoView) cur.scrollIntoView({ block: 'nearest' });
    }

    function renderLines(lines) {
        var html = '';
        (lines || []).forEach(function (line) {
            html += '<div><strong>' + esc(line.name || '') + '</strong><span>' + esc(line.desc || '') + '</span></div>';
        });
        return html ? '<div class="fsc-group-list">' + html + '</div>' : '';
    }

    function renderSlide(s) {
        var tone = s.tone === 'amber' ? 'amber' : 'teal';
        var type = s.type || 'custom';
        if (type === 'feature' && s.schedule && s.schedule.length) {
            var cards = '';
            s.schedule.forEach(function (w) {
                var dels = '';
                (w.deliverables || []).forEach(function (d) { dels += '<li>' + esc(d) + '</li>'; });
                cards += '<div class="fsc-sched-card"><p class="wh">' + esc(w.wave || '') + '</p><p class="wp">' + esc(w.period || '') + '</p><p class="wf">' + esc(w.focus || '') + '</p>' + (dels ? '<ul>' + dels + '</ul>' : '') + '</div>';
            });
            return '<div class="fsc-slide" data-tone="' + tone + '"><p class="kicker">' + esc(s.kicker || '') + '</p><h2>' + esc(s.title) + '</h2><p class="lead">' + esc(s.lead || '') + '</p><div class="fsc-sched-slide">' + cards + '</div></div>';
        }
        if (type === 'feature') {
            var rules = '';
            (s.rules || []).forEach(function (r) { rules += '<li>' + esc(r) + '</li>'; });
            return '<div class="fsc-slide" data-tone="' + tone + '"><p class="kicker">' + esc(s.kicker || '') + '</p><h2>' + esc(s.title) + '</h2><p class="lead">' + esc(s.lead || '') + '</p>' + (rules ? '<ul class="fsc-feat-rules">' + rules + '</ul>' : '') + '</div>';
        }
        var rules2 = '';
        (s.rules || []).forEach(function (r) { rules2 += '<li>' + esc(r) + '</li>'; });
        return '<div class="fsc-slide" data-tone="' + tone + '"><p class="kicker">' + esc(s.kicker || '') + '</p><h2>' + esc(s.title) + '</h2>' +
            (s.lead ? '<p class="lead">' + esc(s.lead) + '</p>' : '') +
            (s.subtitle ? '<p class="sub">' + esc(s.subtitle) + '</p>' : '') +
            renderLines(s.lines) +
            (rules2 ? '<ul class="fsc-feat-rules">' + rules2 + '</ul>' : '') + '</div>';
    }

    function show(i) {
        if (!SLIDES.length) return;
        idx = Math.max(0, Math.min(SLIDES.length - 1, i));
        stage.innerHTML = renderSlide(SLIDES[idx]);
        var h = document.createElement('div');
        h.className = 'fsc-deck-hint';
        h.textContent = '← → · Space · Esc';
        stage.appendChild(h);
        progress.textContent = (idx + 1) + ' / ' + SLIDES.length;
        renderHere(idx);
        renderTree();
    }

    function openDeck(start) {
        if (!SLIDES.length) { alert('슬라이드 없음'); return; }
        open = true;
        deck.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        show(typeof start === 'number' ? start : 0);
    }
    function closeDeck() {
        open = false;
        deck.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    document.getElementById('fscOpenSlide').addEventListener('click', function () { openDeck(0); });
    document.getElementById('fscClose').addEventListener('click', closeDeck);
    document.getElementById('fscPrev').addEventListener('click', function () { show(idx - 1); });
    document.getElementById('fscNext').addEventListener('click', function () { show(idx + 1); });
    document.addEventListener('keydown', function (e) {
        if (!open) return;
        if (e.key === 'Escape') { e.preventDefault(); closeDeck(); }
        else if (e.key === 'ArrowRight' || e.key === ' ') { e.preventDefault(); show(idx + 1); }
        else if (e.key === 'ArrowLeft') { e.preventDefault(); show(idx - 1); }
    });

    // TOC active on scroll
    var tocLinks = document.querySelectorAll('.fsc-toc a[href^="#"]');
    var sections = [];
    tocLinks.forEach(function (a) {
        var id = a.getAttribute('href').slice(1);
        var el = document.getElementById(id);
        if (el) sections.push({ id: id, el: el, a: a });
    });
    function syncToc() {
        var y = window.scrollY + 120;
        var cur = sections[0];
        sections.forEach(function (s) { if (s.el.offsetTop <= y) cur = s; });
        tocLinks.forEach(function (a) { a.classList.remove('is-active'); });
        if (cur) cur.a.classList.add('is-active');
    }
    window.addEventListener('scroll', syncToc, { passive: true });
    syncToc();
})();
</script>
