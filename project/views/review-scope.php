<?php
/**
 * 검수 3depth 시트 — 1차 구축 전용 (엑셀형 인라인 편집)
 */
$saveUrl = url('review-scope.php');
$periodLabel = isset($phaseMeta['period']) ? $phaseMeta['period'] : '';
?>
<style>
.rs-app .ds-c-status { width: 100px; text-align: center; }
.rs-app .ds-c-review { width: 108px; text-align: center; }
.rs-app .ds-c-url { width: 160px; }
.rs-app .ds-url-wrap {
    display: flex;
    align-items: center;
    gap: 2px;
    min-width: 0;
    padding: 0 2px;
}
.rs-app .ds-url-wrap .ds-cell--url {
    flex: 1;
    min-width: 0;
    width: 100%;
    font-size: 11px;
}
.rs-app a.ds-url-open {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 4px;
    color: #1d4ed8;
    text-decoration: none;
    font-size: 12px;
    line-height: 1;
}
.rs-app a.ds-url-open:hover { background: rgba(255,255,255,.7); }
.rs-app a.ds-url-open.is-empty { visibility: hidden; pointer-events: none; }
.rs-app .ds-url-text {
    display: block;
    padding: 4px 6px;
    font-size: 11px;
    color: #1d4ed8;
    word-break: break-all;
}
.rs-app .ds-c-rcomment { width: auto; min-width: 160px; }
.rs-app .ds-cell--status,
.rs-app .ds-cell--review {
    width: 100%;
    font-size: 11px;
    font-weight: 700;
}
.rs-app .ds-status-readonly,
.rs-app .ds-review-readonly {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 6px;
}
/* 검수 상태: 행 전체 배경 */
.rs-app .ds-r.is-review-in_review td { background: #dbeafe !important; }
.rs-app .ds-r.is-review-done td { background: #dcfce7 !important; }
.rs-app .ds-r.is-review-need_fix td { background: #fee2e2 !important; }
.rs-app .ds-r.is-review-in_review .ds-cell--review,
.rs-app .ds-r.is-review-in_review .ds-review-readonly { color: #1d4ed8; }
.rs-app .ds-r.is-review-done .ds-cell--review,
.rs-app .ds-r.is-review-done .ds-review-readonly { color: #15803d; }
.rs-app .ds-r.is-review-need_fix .ds-cell--review,
.rs-app .ds-r.is-review-need_fix .ds-review-readonly { color: #b91c1c; }
/* 개발자확인: 좌측 띠 + 해당 칸 색상 (검수 행색 위에도 보이도록 뒤에 선언) */
.rs-app .ds-r.is-dev-planned td.ds-c-sort { box-shadow: inset 4px 0 0 #94a3b8; }
.rs-app .ds-r.is-dev-in_progress td.ds-c-sort { box-shadow: inset 4px 0 0 #2563eb; }
.rs-app .ds-r.is-dev-done td.ds-c-sort { box-shadow: inset 4px 0 0 #16a34a; }
.rs-app .ds-r.is-dev-deferred td.ds-c-sort { box-shadow: inset 4px 0 0 #ca8a04; }
.rs-app .ds-r.is-dev-out td.ds-c-sort { box-shadow: inset 4px 0 0 #64748b; }
.rs-app .ds-r.is-dev-planned td.ds-c-status { background: #e2e8f0 !important; }
.rs-app .ds-r.is-dev-in_progress td.ds-c-status { background: #93c5fd !important; }
.rs-app .ds-r.is-dev-done td.ds-c-status { background: #86efac !important; }
.rs-app .ds-r.is-dev-deferred td.ds-c-status { background: #fcd34d !important; }
.rs-app .ds-r.is-dev-out td.ds-c-status { background: #cbd5e1 !important; }
.rs-app .ds-r.is-dev-planned .ds-cell--status,
.rs-app .ds-r.is-dev-planned .ds-status-readonly { color: #334155; }
.rs-app .ds-r.is-dev-in_progress .ds-cell--status,
.rs-app .ds-r.is-dev-in_progress .ds-status-readonly { color: #1e3a8a; }
.rs-app .ds-r.is-dev-done .ds-cell--status,
.rs-app .ds-r.is-dev-done .ds-status-readonly { color: #14532d; }
.rs-app .ds-r.is-dev-deferred .ds-cell--status,
.rs-app .ds-r.is-dev-deferred .ds-status-readonly { color: #854d0e; }
.rs-app .ds-r.is-dev-out .ds-cell--status,
.rs-app .ds-r.is-dev-out .ds-status-readonly { color: #475569; }
.rs-app textarea.ds-cell--rcomment {
    display: block;
    width: 100%;
    min-height: 44px;
    height: auto;
    margin: 0;
    padding: 4px 6px;
    border: 1px solid transparent;
    border-radius: 0;
    background: transparent;
    font: inherit;
    font-size: 11px;
    line-height: 1.35;
    color: inherit;
    box-sizing: border-box;
    outline: none;
    resize: vertical;
}
.rs-app textarea.ds-cell--rcomment:hover { background: rgba(255,255,255,.55); }
.rs-app textarea.ds-cell--rcomment:focus {
    border-color: #217346;
    background: #fff;
    box-shadow: inset 0 0 0 1px #217346;
    z-index: 1;
    position: relative;
}
.rs-app .ds-c-rcomment .ds-rcomment-text {
    display: block;
    padding: 4px 6px;
    font-size: 11px;
    color: #475569;
    white-space: pre-wrap;
    word-break: break-word;
}
</style>
<div class="ds-app rs-app" id="rsApp"
     data-save-url="<?= e($saveUrl) ?>"
     data-csrf="<?= e($csrfToken) ?>"
     data-phase="<?= e(isset($phaseKey) ? $phaseKey : 'phase-1') ?>"
     data-can-edit="<?= !empty($canEdit) ? '1' : '0' ?>"
     data-can-status="<?= !empty($canEditStatus) ? '1' : '0' ?>"
     data-can-review="<?= !empty($canReview) ? '1' : '0' ?>"
     data-can-url="<?= !empty($canEditUrl) ? '1' : '0' ?>">

    <header class="ds-bar">
        <div class="ds-bar-left">
            <h1 class="ds-title">검수</h1>
            <span class="ds-meta"><?= e($periodLabel) ?> · D1 <?= (int) $stats['d1'] ?> / D2 <?= (int) $stats['d2'] ?> / D3 <?= (int) $stats['d3'] ?> · 완료 <?= (int) $stats['done'] ?> · 검수중 <?= (int) (isset($stats['review_in_review']) ? $stats['review_in_review'] : 0) ?> · 검수완료 <?= (int) (isset($stats['review_done']) ? $stats['review_done'] : 0) ?> · 보완 <?= (int) (isset($stats['review_need_fix']) ? $stats['review_need_fix'] : 0) ?></span>
            <span class="ds-save-hint" id="rsSaveHint" aria-live="polite"></span>
        </div>
        <div class="ds-bar-right">
            <?php if (!empty($canEdit)): ?>
            <button type="button" class="ds-btn" data-quick-add="1" data-parent="0" title="1depth 영역 추가">＋영역</button>
            <button type="button" class="ds-btn" data-quick-add="2" title="2depth 블록 추가">＋블록</button>
            <button type="button" class="ds-btn ds-btn-primary" data-quick-add="3" title="3depth 항목 추가">＋항목</button>
            <?php endif; ?>
            <a href="<?= url('dev-scope.php?phase=phase-1') ?>" class="ds-btn ds-btn-ghost" title="개발범위로 이동">개발범위</a>
            <a href="<?= url('feature-spec.php') ?>" class="ds-btn ds-btn-ghost">명세표</a>
            <a href="<?= url('review-scope.php?export=xlsx') ?>" class="ds-btn" title="검수 엑셀 내려받기">⬇ 엑셀</a>
            <a href="<?= url('review-scope.php?print=1') ?>" class="ds-btn" target="_blank" rel="noopener" title="PDF로 저장">📄 PDF</a>
            <button type="button" class="ds-btn" id="rsFullscreen" title="전체화면 (Alt+F)">⛶ 전체화면</button>
        </div>
    </header>

    <?php if (!empty($canEdit)): ?>
    <div class="ds-add-panel" id="rsAddPanel" hidden>
        <label class="ds-add-label">상위 선택
            <select id="rsAddParent" class="ds-cell-select"></select>
        </label>
        <input type="text" id="rsAddTitle" class="ds-cell-input" placeholder="제목 (Enter로 추가)" maxlength="500">
        <button type="button" class="ds-btn ds-btn-primary" id="rsAddConfirm">추가</button>
        <button type="button" class="ds-btn" id="rsAddCancel">취소</button>
    </div>
    <?php endif; ?>

    <div class="ds-grid-wrap">
        <table class="ds-grid" id="rsGrid">
            <thead>
                <tr>
                    <th class="ds-c-sort">순서</th>
                    <th class="ds-c-depth">D</th>
                    <th class="ds-c-d1">구분</th>
                    <th class="ds-c-d2">항목</th>
                    <th class="ds-c-d3">내용</th>
                    <th class="ds-c-url">페이지 URL</th>
                    <th class="ds-c-prio">우선순위</th>
                    <th class="ds-c-status">개발자확인</th>
                    <th class="ds-c-review">검수 상태</th>
                    <th class="ds-c-rcomment">검수 코멘트</th>
                    <th class="ds-c-act">액션</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sheetRows)): ?>
                <tr class="ds-empty-row">
                    <td colspan="11"><?= !empty($canEdit) ? '데이터가 없습니다. ＋영역으로 시작하세요.' : '데이터가 없습니다.' ?></td>
                </tr>
                <?php else: ?>
                <?php
                    $prevD1Id = null;
                    $prevD2Id = null;
                ?>
                <?php foreach ($sheetRows as $row): ?>
                <?php
                    $it = $row['item'];
                    $d = (int) $row['depth'];
                    $id = (int) $it['id'];
                    $d1Id = isset($row['d1_id']) ? (int) $row['d1_id'] : 0;
                    $d2Id = isset($row['d2_id']) ? (int) $row['d2_id'] : 0;
                    $d1Changed = ($prevD1Id === null || $d1Id !== $prevD1Id);
                    $d2Changed = ($prevD2Id === null || $d2Id !== $prevD2Id || $d1Changed);
                    $showCtxD1 = ($d > 1 && $d1Changed);
                    $showCtxD2 = ($d === 3 && $d2Changed);

                    $rowClass = 'ds-r ds-r--d' . $d;
                    if ($d === 1 || ($d > 1 && $d1Changed)) {
                        $rowClass .= ' is-group-d1';
                    }
                    if ($d === 2 || ($d === 3 && $d2Changed)) {
                        $rowClass .= ' is-group-d2';
                    }
                    $st = isset($it['status']) ? $it['status'] : 'planned';
                    if (!isset($statuses[$st])) {
                        $st = 'planned';
                    }
                    $rowClass .= ' is-dev-' . $st;
                    if ($st === 'done') {
                        $rowClass .= ' is-done';
                    }
                    if ($st === 'out') {
                        $rowClass .= ' is-out';
                    }
                    $reviewStatuses = isset($reviewStatuses) ? $reviewStatuses : DevScopeService::getReviewStatuses();
                    $reviewStatus = DevScopeService::normalizeReviewStatus(
                        isset($it['review_status'])
                            ? $it['review_status']
                            : (empty($it['review_confirmed']) ? 'in_review' : 'done')
                    );
                    $reviewComment = isset($it['review_comment']) ? $it['review_comment'] : '';
                    $pageUrl = isset($it['page_url']) ? $it['page_url'] : '';
                    $pageUrlHref = DevScopeService::pageUrlHref($pageUrl);
                    $rowClass .= ' is-review-' . $reviewStatus;
                    if ($focusId && $focusId === $id) {
                        $rowClass .= ' is-focus';
                    }
                    $title = isset($it['title']) ? $it['title'] : '';
                    $prio = isset($it['priority']) ? $it['priority'] : 'P1';
                    $rowStyles = DevScopeService::parseStyle(isset($it['style_json']) ? $it['style_json'] : null);
                    $titleStyle = DevScopeService::fieldStyleAttr($rowStyles, 'title');
                    $titleStyleJson = isset($rowStyles['title']) ? json_encode($rowStyles['title'], JSON_UNESCAPED_UNICODE) : '{}';
                ?>
                <tr class="<?= e($rowClass) ?>" data-id="<?= $id ?>" data-depth="<?= $d ?>" data-parent="<?= (int) $it['parent_id'] ?>" draggable="false">
                    <td class="ds-c-sort">
                        <?php if (!empty($canEdit)): ?>
                        <span class="ds-drag" draggable="true" title="드래그하여 같은 단계·같은 상위끼리 순서 변경" data-drag-handle>⋮⋮</span>
                        <button type="button" class="ds-ico ds-ico-sort" data-sort="up" data-id="<?= $id ?>" title="위로">▲</button>
                        <button type="button" class="ds-ico ds-ico-sort" data-sort="down" data-id="<?= $id ?>" title="아래로">▼</button>
                        <?php endif; ?>
                    </td>
                    <td class="ds-c-depth"><span class="ds-dbadge ds-dbadge--<?= $d ?>"><?= $d ?></span></td>
                    <td class="ds-c-d1<?= ($d > 1 && !$showCtxD1) ? ' is-cont' : '' ?>">
                        <?php if ($d === 1 && !empty($canEdit)): ?>
                        <input type="text" class="ds-cell" data-field="title" value="<?= e($title) ?>" maxlength="500"
                               data-style="<?= e($titleStyleJson) ?>"<?= $titleStyle !== '' ? ' style="' . e($titleStyle) . '"' : '' ?>>
                        <?php elseif ($d === 1): ?>
                        <strong<?= $titleStyle !== '' ? ' style="' . e($titleStyle) . '"' : '' ?>><?= e($title) ?></strong>
                        <?php elseif ($showCtxD1): ?>
                        <span class="ds-ctx"><?= e($row['d1']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="ds-c-d2<?= ($d === 3 && !$showCtxD2) ? ' is-cont' : '' ?>">
                        <?php if ($d === 2 && !empty($canEdit)): ?>
                        <input type="text" class="ds-cell" data-field="title" value="<?= e($title) ?>" maxlength="500"
                               data-style="<?= e($titleStyleJson) ?>"<?= $titleStyle !== '' ? ' style="' . e($titleStyle) . '"' : '' ?>>
                        <?php elseif ($d === 2): ?>
                        <strong<?= $titleStyle !== '' ? ' style="' . e($titleStyle) . '"' : '' ?>><?= e($title) ?></strong>
                        <?php elseif ($d === 3 && $showCtxD2): ?>
                        <span class="ds-ctx"><?= e($row['d2']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="ds-c-d3">
                        <?php if ($d === 3 && !empty($canEdit)): ?>
                        <input type="text" class="ds-cell" data-field="title" value="<?= e($title) ?>" maxlength="500"
                               data-style="<?= e($titleStyleJson) ?>"<?= $titleStyle !== '' ? ' style="' . e($titleStyle) . '"' : '' ?>>
                        <?php elseif ($d === 3): ?>
                        <span<?= $titleStyle !== '' ? ' style="' . e($titleStyle) . '"' : '' ?>><?= e($title) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="ds-c-url">
                        <?php if (!empty($canEditUrl)): ?>
                        <div class="ds-url-wrap">
                            <input type="text" class="ds-cell ds-cell--url" data-field="page_url" value="<?= e($pageUrl) ?>" maxlength="1000" placeholder="https://...">
                            <a class="ds-url-open<?= $pageUrlHref === '' ? ' is-empty' : '' ?>" href="<?= $pageUrlHref !== '' ? e($pageUrlHref) : '#' ?>" target="_blank" rel="noopener" title="페이지 열기">↗</a>
                        </div>
                        <?php elseif ($pageUrlHref !== ''): ?>
                        <a class="ds-url-text" href="<?= e($pageUrlHref) ?>" target="_blank" rel="noopener"><?= e($pageUrl) ?></a>
                        <?php else: ?>
                        <span class="ds-url-text" style="color:#94a3b8">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="ds-c-prio">
                        <?php if (!empty($canEdit)): ?>
                        <select class="ds-cell ds-cell--prio" data-field="priority">
                            <?php foreach ($priorities as $pk => $pl): ?>
                            <option value="<?= e($pk) ?>" <?= $prio === $pk ? 'selected' : '' ?>><?= e($pk) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <span class="fs-prio fs-prio--<?= e(strtolower($prio)) ?>"><?= e($prio) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="ds-c-status">
                        <?php if (!empty($canEditStatus)): ?>
                        <select class="ds-cell ds-cell--status" data-field="status" title="개발자확인">
                            <?php foreach ($statuses as $sk => $sl): ?>
                            <option value="<?= e($sk) ?>" <?= $st === $sk ? 'selected' : '' ?>><?= e($sl) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <span class="ds-status-readonly"><?= e(isset($statuses[$st]) ? $statuses[$st] : $st) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="ds-c-review">
                        <?php if (!empty($canReview)): ?>
                        <select class="ds-cell ds-cell--review" data-field="review_status" title="검수 상태">
                            <?php foreach ($reviewStatuses as $rk => $rl): ?>
                            <option value="<?= e($rk) ?>" <?= $reviewStatus === $rk ? 'selected' : '' ?>><?= e($rl) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <span class="ds-review-readonly is-<?= e($reviewStatus) ?>"><?= e(isset($reviewStatuses[$reviewStatus]) ? $reviewStatuses[$reviewStatus] : $reviewStatus) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="ds-c-rcomment">
                        <?php if (!empty($canReview)): ?>
                        <textarea class="ds-cell ds-cell--rcomment" data-field="review_comment" rows="2" maxlength="2000"><?= e($reviewComment) ?></textarea>
                        <?php else: ?>
                        <span class="ds-rcomment-text"><?= e($reviewComment) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="ds-c-act">
                        <?php if (!empty($canEdit)): ?>
                        <?php if ($d < 3): ?>
                        <button type="button" class="ds-ico" data-add-child="<?= $d + 1 ?>" data-parent="<?= $id ?>" title="하위 추가">＋</button>
                        <?php endif; ?>
                        <button type="button" class="ds-ico ds-ico-del" data-delete="<?= $id ?>" data-depth="<?= $d ?>" title="삭제">×</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php
                    $prevD1Id = $d1Id;
                    $prevD2Id = $d2Id;
                ?>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var app = document.getElementById('rsApp');
    if (!app) return;

    var saveUrl = app.getAttribute('data-save-url');
    var csrf = app.getAttribute('data-csrf');
    var phase = app.getAttribute('data-phase') || 'phase-1';
    var canEdit = app.getAttribute('data-can-edit') === '1';
    var canStatus = app.getAttribute('data-can-status') === '1';
    var canReview = app.getAttribute('data-can-review') === '1';
    var canUrl = app.getAttribute('data-can-url') === '1';
    var hint = document.getElementById('rsSaveHint');
    var saveTimer = null;

    // 전체화면
    var btnFs = document.getElementById('rsFullscreen');
    function isFs() {
        return !!(document.fullscreenElement || document.webkitFullscreenElement);
    }
    function enterFs() {
        if (app.requestFullscreen) app.requestFullscreen();
        else if (app.webkitRequestFullscreen) app.webkitRequestFullscreen();
    }
    function exitFs() {
        if (document.exitFullscreen) document.exitFullscreen();
        else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
    }
    function updateFsBtn() {
        if (!btnFs) return;
        btnFs.textContent = isFs() ? '⛶ 전체화면 종료' : '⛶ 전체화면';
        btnFs.title = isFs() ? '전체화면 종료 (Esc)' : '전체화면 (Alt+F)';
    }
    if (btnFs) {
        btnFs.addEventListener('click', function () {
            if (isFs()) exitFs();
            else enterFs();
        });
    }
    document.addEventListener('fullscreenchange', updateFsBtn);
    document.addEventListener('webkitfullscreenchange', updateFsBtn);
    document.addEventListener('keydown', function (e) {
        if (e.altKey && (e.key === 'f' || e.key === 'F')) {
            e.preventDefault();
            if (isFs()) exitFs();
            else enterFs();
        }
    });

    function setHint(msg, ok) {
        if (!hint) return;
        hint.textContent = msg || '';
        hint.className = 'ds-save-hint' + (msg ? (ok ? ' is-ok' : ' is-err') : '');
        if (msg && ok) {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(function () { setHint('', true); }, 1600);
        }
    }

    function postForm(data) {
        var body = new FormData();
        body.append('_csrf', csrf || '');
        body.append('ajax', '1');
        Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
        return fetch(saveUrl, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            redirect: 'manual',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (r) {
            if (r.type === 'opaqueredirect' || (r.status >= 300 && r.status < 400)) {
                return { ok: true, json: { ok: true, redirected: true } };
            }
            return r.text().then(function (text) {
                var j = null;
                try { j = JSON.parse(text); } catch (err) { j = null; }
                if (!j) {
                    var looksHtml = /<!DOCTYPE|<html|<body|sidebar-nav|ds-app|rs-app/i.test(text || '');
                    var msg = looksHtml
                        ? '서버가 HTML을 반환했습니다. 세션·보안토큰 문제일 수 있습니다. 새로고침 후 다시 시도해 주세요.'
                        : ('서버 오류 HTTP ' + r.status + (text ? (': ' + text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').slice(0, 180)) : ''));
                    return { ok: false, json: { error: msg, code: looksHtml ? 'html_response' : 'bad_json' } };
                }
                return { ok: r.ok && !!j.ok, json: j };
            });
        });
    }

    function focusUrl(id) {
        var base = saveUrl.split('?')[0];
        return base + (id ? ('?focus=' + encodeURIComponent(id)) : '');
    }

    function hrefFromUrl(url) {
        url = (url || '').trim();
        if (!url) return '';
        if (/^(https?:\/\/|mailto:|\/|#)/i.test(url)) return url;
        return 'https://' + url;
    }

    function updateUrlOpen(tr, url) {
        var a = tr.querySelector('a.ds-url-open');
        if (!a) return;
        var href = hrefFromUrl(url);
        if (!href) {
            a.href = '#';
            a.classList.add('is-empty');
        } else {
            a.href = href;
            a.classList.remove('is-empty');
        }
    }

    function savePageUrl(tr, url) {
        if (!tr || !tr.getAttribute('data-id')) return;
        tr.classList.add('is-saving');
        setHint('페이지 URL 저장 중…', true);
        postForm({
            action: 'save_page_url',
            item_id: tr.getAttribute('data-id'),
            page_url: url || ''
        }).then(function (res) {
            tr.classList.remove('is-saving');
            if (!res.ok) {
                setHint((res.json && res.json.error) || '페이지 URL 저장 실패', false);
                return;
            }
            var saved = res.json && res.json.item ? (res.json.item.page_url || '') : url;
            updateUrlOpen(tr, saved);
            setHint('페이지 URL 저장됨', true);
        }).catch(function () {
            tr.classList.remove('is-saving');
            setHint('페이지 URL 저장 실패', false);
        });
    }

    function applyDevRowClass(tr, status) {
        tr.classList.remove('is-dev-planned', 'is-dev-in_progress', 'is-dev-done', 'is-dev-deferred', 'is-dev-out', 'is-done', 'is-out');
        tr.classList.add('is-dev-' + status);
        tr.classList.toggle('is-done', status === 'done');
        tr.classList.toggle('is-out', status === 'out');
    }

    function saveStatus(tr, status) {
        if (!tr || !tr.getAttribute('data-id')) return;
        var select = tr.querySelector('[data-field="status"]');
        var prev = select ? (select.getAttribute('data-last') || select.value) : null;
        tr.classList.add('is-saving');
        setHint('개발자확인 저장 중…', true);
        postForm({
            action: 'save_status',
            item_id: tr.getAttribute('data-id'),
            status: status
        }).then(function (res) {
            tr.classList.remove('is-saving');
            if (!res.ok) {
                if (select && prev !== null) select.value = prev;
                setHint((res.json && res.json.error) || '개발자확인 저장 실패', false);
                return;
            }
            var st = (res.json && res.json.item && res.json.item.status) ? res.json.item.status : status;
            applyDevRowClass(tr, st);
            if (select) select.setAttribute('data-last', st);
            setHint('개발자확인 저장됨', true);
        }).catch(function () {
            tr.classList.remove('is-saving');
            if (select && prev !== null) select.value = prev;
            setHint('개발자확인 저장 실패', false);
        });
    }

    function applyReviewRowClass(tr, status) {
        tr.classList.remove('is-review-in_review', 'is-review-done', 'is-review-need_fix');
        tr.classList.add('is-review-' + status);
    }

    function saveReview(tr, payload) {
        if (!tr || !tr.getAttribute('data-id')) return;
        var select = tr.querySelector('[data-field="review_status"]');
        var prev = select ? select.getAttribute('data-last') || select.value : null;
        tr.classList.add('is-saving');
        setHint('검수 저장 중…', true);
        var data = {
            action: 'save_review',
            item_id: tr.getAttribute('data-id')
        };
        Object.keys(payload).forEach(function (k) { data[k] = payload[k]; });
        postForm(data).then(function (res) {
            tr.classList.remove('is-saving');
            if (!res.ok) {
                if (payload.review_status !== undefined && select && prev !== null) {
                    select.value = prev;
                }
                setHint((res.json && res.json.error) || '검수 저장 실패', false);
                return;
            }
            var st = res.json && res.json.item && res.json.item.review_status
                ? res.json.item.review_status
                : (payload.review_status || (select ? select.value : 'in_review'));
            applyReviewRowClass(tr, st);
            if (select) select.setAttribute('data-last', st);
            setHint('검수 저장됨', true);
        }).catch(function () {
            tr.classList.remove('is-saving');
            if (payload.review_status !== undefined && select && prev !== null) {
                select.value = prev;
            }
            setHint('검수 저장 실패', false);
        });
    }

    function rowSnapshot(tr) {
        var titleEl = tr.querySelector('[data-field="title"]');
        var prioEl = tr.querySelector('[data-field="priority"]');
        var stEl = tr.querySelector('[data-field="status"]');
        return {
            item_id: tr.getAttribute('data-id'),
            title: titleEl ? titleEl.value : '',
            priority: prioEl ? prioEl.value : 'P1',
            status: stEl ? stEl.value : 'planned'
        };
    }

    function saveRow(tr) {
        if (!canEdit || !tr || !tr.getAttribute('data-id')) return;
        var snap = rowSnapshot(tr);
        if (!snap.title.trim()) {
            setHint('제목은 비울 수 없습니다', false);
            return;
        }
        tr.classList.add('is-saving');
        setHint('저장 중…', true);
        var payload = {
            action: 'inline_save',
            item_id: snap.item_id,
            title: snap.title,
            priority: snap.priority
        };
        if (tr.querySelector('[data-field="status"]')) {
            payload.status = snap.status;
        }
        postForm(payload).then(function (res) {
            tr.classList.remove('is-saving');
            if (!res.ok) {
                setHint((res.json && res.json.error) || '저장 실패', false);
                return;
            }
            if (payload.status) {
                applyDevRowClass(tr, payload.status);
            }
            setHint('저장됨', true);
        }).catch(function () {
            tr.classList.remove('is-saving');
            setHint('저장 실패', false);
        });
    }

    var grid = document.getElementById('rsGrid');
    if (grid) {
        grid.addEventListener('focusin', function (e) {
            var el = e.target;
            if (!el.classList || !el.classList.contains('ds-cell')) return;
            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') {
                el.setAttribute('data-last', el.value);
            }
        });

        grid.addEventListener('change', function (e) {
            var el = e.target;
            if (!el.classList || !el.classList.contains('ds-cell')) return;
            var tr = el.closest('tr[data-id]');
            if (!tr) return;

            if (el.getAttribute('data-field') === 'status' && canStatus) {
                saveStatus(tr, el.value);
                return;
            }
            if (el.getAttribute('data-field') === 'review_status' && canReview) {
                saveReview(tr, { review_status: el.value });
                return;
            }
            if (canEdit && (el.getAttribute('data-field') === 'priority')) {
                saveRow(tr);
            }
        });

        grid.addEventListener('focusout', function (e) {
            var el = e.target;
            if (!el.classList || !el.classList.contains('ds-cell')) return;
            var tr = el.closest('tr[data-id]');
            if (!tr) return;

            if (el.getAttribute('data-field') === 'review_comment' && canReview && el.tagName === 'TEXTAREA') {
                if (el.value !== (el.getAttribute('data-last') || el.defaultValue)) {
                    el.setAttribute('data-last', el.value);
                    saveReview(tr, { review_comment: el.value });
                }
                return;
            }

            if (el.getAttribute('data-field') === 'page_url' && canUrl && el.tagName === 'INPUT') {
                if (el.value !== (el.getAttribute('data-last') || el.defaultValue)) {
                    el.setAttribute('data-last', el.value);
                    savePageUrl(tr, el.value);
                }
                return;
            }

            if (!canEdit) return;
            if (el.tagName !== 'INPUT') return;
            if (el.value !== (el.getAttribute('data-last') || el.defaultValue)) {
                el.setAttribute('data-last', el.value);
                saveRow(tr);
            }
        });

        grid.addEventListener('keydown', function (e) {
            var el = e.target;
            if (!el.classList || !el.classList.contains('ds-cell')) return;
            if (e.key === 'Enter' && el.tagName === 'INPUT') {
                e.preventDefault();
                el.blur();
            }
            if (e.key === 'Escape' && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA')) {
                el.value = el.getAttribute('data-last') || el.defaultValue;
                el.blur();
            }
        });

        if (canEdit) {
            grid.addEventListener('click', function (e) {
                var sortBtn = e.target.closest('[data-sort]');
                if (sortBtn) {
                    var sortId = sortBtn.getAttribute('data-id');
                    var dir = sortBtn.getAttribute('data-sort');
                    setHint('순서 변경 중…', true);
                    postForm({
                        action: 'reorder_move',
                        item_id: sortId,
                        direction: dir
                    }).then(function (res) {
                        if (!res.ok) {
                            setHint((res.json && res.json.error) || '순서 변경 실패', false);
                            return;
                        }
                        if (res.json && res.json.swapped === false) {
                            setHint(dir === 'up' ? '이미 맨 위입니다' : '이미 맨 아래입니다', false);
                            return;
                        }
                        location.href = focusUrl(sortId);
                    }).catch(function () { setHint('순서 변경 실패', false); });
                    return;
                }
                var btn = e.target.closest('[data-add-child]');
                if (btn) {
                    openAdd(parseInt(btn.getAttribute('data-add-child'), 10), parseInt(btn.getAttribute('data-parent'), 10));
                    return;
                }
                var del = e.target.closest('[data-delete]');
                if (del) {
                    var depth = parseInt(del.getAttribute('data-depth'), 10);
                    var msg = depth < 3 ? '이 항목과 모든 하위를 삭제할까요?' : '이 항목을 삭제할까요?';
                    if (!confirm(msg)) return;
                    postForm({ action: 'delete', item_id: del.getAttribute('data-delete') })
                        .then(function (res) {
                            if (!res.ok) {
                                setHint((res.json && res.json.error) || '삭제 실패', false);
                                return;
                            }
                            location.reload();
                        });
                }
            });

            (function initDragReorder() {
                var dragRow = null;
                var tbody = grid.querySelector('tbody');
                if (!tbody) return;

                function siblingRows(parent, depth) {
                    return Array.prototype.filter.call(tbody.querySelectorAll('tr[data-id]'), function (tr) {
                        return tr.getAttribute('data-parent') === parent && tr.getAttribute('data-depth') === depth;
                    });
                }

                tbody.addEventListener('dragstart', function (e) {
                    var handle = e.target.closest('[data-drag-handle]');
                    if (!handle) {
                        e.preventDefault();
                        return;
                    }
                    dragRow = handle.closest('tr[data-id]');
                    if (!dragRow) {
                        e.preventDefault();
                        return;
                    }
                    dragRow.classList.add('is-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    try { e.dataTransfer.setData('text/plain', dragRow.getAttribute('data-id')); } catch (err) {}
                });

                tbody.addEventListener('dragend', function () {
                    if (dragRow) dragRow.classList.remove('is-dragging');
                    tbody.querySelectorAll('.is-drag-over').forEach(function (el) {
                        el.classList.remove('is-drag-over');
                    });
                    dragRow = null;
                });

                tbody.addEventListener('dragover', function (e) {
                    if (!dragRow) return;
                    var over = e.target.closest('tr[data-id]');
                    if (!over || over === dragRow) return;
                    if (over.getAttribute('data-parent') !== dragRow.getAttribute('data-parent')) return;
                    if (over.getAttribute('data-depth') !== dragRow.getAttribute('data-depth')) return;
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    tbody.querySelectorAll('.is-drag-over').forEach(function (el) {
                        el.classList.remove('is-drag-over');
                    });
                    over.classList.add('is-drag-over');
                });

                tbody.addEventListener('drop', function (e) {
                    if (!dragRow) return;
                    var over = e.target.closest('tr[data-id]');
                    if (!over || over === dragRow) return;
                    if (over.getAttribute('data-parent') !== dragRow.getAttribute('data-parent')) return;
                    if (over.getAttribute('data-depth') !== dragRow.getAttribute('data-depth')) return;
                    e.preventDefault();

                    var parent = dragRow.getAttribute('data-parent');
                    var depth = dragRow.getAttribute('data-depth');
                    var rows = siblingRows(parent, depth);
                    var fromIdx = rows.indexOf(dragRow);
                    var toIdx = rows.indexOf(over);
                    if (fromIdx < 0 || toIdx < 0 || fromIdx === toIdx) return;

                    function blockEnd(startTr) {
                        var d0 = parseInt(startTr.getAttribute('data-depth'), 10);
                        var next = startTr.nextElementSibling;
                        while (next && next.getAttribute('data-id')) {
                            var nd = parseInt(next.getAttribute('data-depth'), 10);
                            if (nd <= d0) break;
                            next = next.nextElementSibling;
                        }
                        return next;
                    }

                    var blockNodes = [];
                    var n = dragRow;
                    var d0 = parseInt(dragRow.getAttribute('data-depth'), 10);
                    while (n) {
                        blockNodes.push(n);
                        n = n.nextElementSibling;
                        if (!n || !n.getAttribute('data-id')) break;
                        if (parseInt(n.getAttribute('data-depth'), 10) <= d0) break;
                    }

                    var ref = over;
                    if (fromIdx < toIdx) {
                        ref = blockEnd(over);
                    }
                    blockNodes.forEach(function (node) {
                        tbody.insertBefore(node, ref);
                    });

                    var ordered = siblingRows(parent, depth).map(function (tr) {
                        return tr.getAttribute('data-id');
                    });
                    setHint('순서 저장 중…', true);
                    postForm({
                        action: 'reorder_siblings',
                        ordered_ids: JSON.stringify(ordered)
                    }).then(function (res) {
                        if (!res.ok) {
                            setHint((res.json && res.json.error) || '순서 저장 실패', false);
                            location.reload();
                            return;
                        }
                        setHint('순서 저장됨', true);
                        location.href = focusUrl(dragRow.getAttribute('data-id'));
                    }).catch(function () {
                        setHint('순서 저장 실패', false);
                        location.reload();
                    });
                });
            })();
        }
    }

    if (!canEdit) {
        var focusRowReadonly = document.querySelector('.ds-r.is-focus');
        if (focusRowReadonly) {
            focusRowReadonly.scrollIntoView({ block: 'center' });
        }
        return;
    }

    var addPanel = document.getElementById('rsAddPanel');
    var addParent = document.getElementById('rsAddParent');
    var addTitle = document.getElementById('rsAddTitle');
    var pendingDepth = 3;
    var pendingParent = 0;

    var d1Parents = <?= json_encode(array_map(function ($p) {
        return array('id' => (int) $p['id'], 'title' => $p['title']);
    }, isset($d1Parents) ? $d1Parents : array()), JSON_UNESCAPED_UNICODE) ?>;
    var d2Parents = <?= json_encode(array_map(function ($p) {
        return array('id' => (int) $p['id'], 'title' => $p['title']);
    }, isset($d2Parents) ? $d2Parents : array()), JSON_UNESCAPED_UNICODE) ?>;

    function fillParents(depth, selectedId) {
        if (!addParent) return;
        addParent.innerHTML = '';
        if (depth === 1) {
            addParent.disabled = true;
            var o = document.createElement('option');
            o.value = '0';
            o.textContent = '(최상위)';
            addParent.appendChild(o);
            return;
        }
        addParent.disabled = false;
        var list = depth === 2 ? d1Parents : d2Parents;
        if (!list.length) {
            var empty = document.createElement('option');
            empty.value = '';
            empty.textContent = depth === 2 ? '영역이 없습니다 — 먼저 ＋영역' : '블록이 없습니다 — 먼저 ＋블록';
            addParent.appendChild(empty);
            return;
        }
        list.forEach(function (p) {
            var o = document.createElement('option');
            o.value = String(p.id);
            o.textContent = p.title;
            if (selectedId && selectedId === p.id) o.selected = true;
            addParent.appendChild(o);
        });
    }

    function openAdd(depth, parentId) {
        pendingDepth = depth;
        pendingParent = parentId || 0;
        if (!addPanel) {
            var title = window.prompt(depth === 1 ? '영역 제목' : (depth === 2 ? '블록 제목' : '항목 제목'), '');
            if (title === null) return;
            doQuickAdd(depth, parentId || 0, title.trim());
            return;
        }
        fillParents(depth, parentId || 0);
        addPanel.hidden = false;
        if (addTitle) {
            addTitle.value = '';
            addTitle.focus();
        }
    }

    function doQuickAdd(depth, parentId, title) {
        setHint('추가 중…', true);
        postForm({
            action: 'quick_add',
            depth: String(depth),
            parent_id: String(parentId || 0),
            phase_key: phase,
            title: title || ''
        }).then(function (res) {
            if (!res.ok) {
                var err = (res.json && res.json.error) || '추가 실패';
                setHint(err, false);
                if (res.json && (res.json.code === 'csrf' || res.json.code === 'html_response')) {
                    setTimeout(function () {
                        if (confirm(err + '\n\n페이지를 새로고침할까요?')) location.reload();
                    }, 50);
                }
                return;
            }
            if (res.json && res.json.redirected) {
                location.reload();
                return;
            }
            var id = (res.json && res.json.id) || '';
            location.href = focusUrl(id);
        }).catch(function () { setHint('추가 실패', false); });
    }

    app.querySelectorAll('[data-quick-add]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var depth = parseInt(btn.getAttribute('data-quick-add'), 10);
            var parent = parseInt(btn.getAttribute('data-parent') || '0', 10);
            openAdd(depth, parent);
        });
    });

    var confirmBtn = document.getElementById('rsAddConfirm');
    var cancelBtn = document.getElementById('rsAddCancel');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            var parentId = pendingDepth === 1 ? 0 : (addParent ? parseInt(addParent.value || '0', 10) : pendingParent);
            if (pendingDepth > 1 && !parentId) {
                setHint('상위를 선택하세요', false);
                return;
            }
            doQuickAdd(pendingDepth, parentId, addTitle ? addTitle.value.trim() : '');
        });
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            if (addPanel) addPanel.hidden = true;
        });
    }
    if (addTitle) {
        addTitle.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (confirmBtn) confirmBtn.click();
            }
            if (e.key === 'Escape' && addPanel) addPanel.hidden = true;
        });
    }

    var focusRow = document.querySelector('.ds-r.is-focus');
    if (focusRow) {
        focusRow.scrollIntoView({ block: 'center' });
        var inp = focusRow.querySelector('[data-field="title"]');
        if (inp) {
            inp.focus();
            inp.select();
        }
    }
})();
</script>
