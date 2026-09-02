<?php
/**
 * 개발범위 3depth 시트 — 엑셀형 인라인 편집
 */
$phaseMeta = isset($phases[$phaseKey]) ? $phases[$phaseKey] : array('label' => $phaseKey, 'period' => '');
$saveUrl = url('dev-scope.php?phase=' . urlencode($phaseKey));
?>
<div class="ds-app" id="dsApp"
     data-save-url="<?= e($saveUrl) ?>"
     data-csrf="<?= e($csrfToken) ?>"
     data-phase="<?= e($phaseKey) ?>"
     data-can-edit="<?= !empty($canEdit) ? '1' : '0' ?>"
     data-phases="<?= e(json_encode($phases, JSON_UNESCAPED_UNICODE)) ?>">

    <header class="ds-bar">
        <div class="ds-bar-left">
            <h1 class="ds-title">개발범위</h1>
            <nav class="ds-tabs" aria-label="구축 단계">
                <?php foreach ($phases as $key => $ph): ?>
                <a href="<?= url('dev-scope.php?phase=' . urlencode($key)) ?>"
                   class="ds-tab<?= $phaseKey === $key ? ' is-active' : '' ?>"><?= e($ph['label']) ?></a>
                <?php endforeach; ?>
            </nav>
            <span class="ds-meta"><?= e($phaseMeta['period']) ?> · D1 <?= (int) $stats['d1'] ?> / D2 <?= (int) $stats['d2'] ?> / D3 <?= (int) $stats['d3'] ?> · 완료 <?= (int) $stats['done'] ?></span>
            <span class="ds-save-hint" id="dsSaveHint" aria-live="polite"></span>
        </div>
        <div class="ds-bar-right">
            <?php if (!empty($canEdit)): ?>
            <button type="button" class="ds-btn" data-quick-add="1" data-parent="0" title="1depth 영역 추가">＋영역</button>
            <button type="button" class="ds-btn" data-quick-add="2" title="2depth 블록 추가">＋블록</button>
            <button type="button" class="ds-btn ds-btn-primary" data-quick-add="3" title="3depth 항목 추가">＋항목</button>
            <form method="post" class="ds-reseed" onsubmit="return confirm('기존 데이터를 모두 지우고 시드를 다시 불러올까요?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reseed">
                <button type="submit" class="ds-btn ds-btn-ghost">시드</button>
            </form>
            <?php endif; ?>
            <a href="<?= url('dev-scope.php?export=xlsx&scope=current&phase=' . urlencode($phaseKey)) ?>" class="ds-btn" title="현재 단계만 엑셀 내려받기">⬇ 엑셀</a>
            <a href="<?= url('dev-scope.php?export=xlsx&scope=all') ?>" class="ds-btn ds-btn-ghost" title="1차 구축·고도화 전체 엑셀 내려받기">⬇ 전체</a>
            <a href="<?= url('dev-scope.php?print=1&scope=current&phase=' . urlencode($phaseKey)) ?>" class="ds-btn" target="_blank" rel="noopener" title="현재 단계 PDF로 저장">📄 PDF</a>
            <a href="<?= url('dev-scope.php?print=1&scope=all') ?>" class="ds-btn ds-btn-ghost" target="_blank" rel="noopener" title="전체 단계 PDF로 저장">📄 PDF 전체</a>
            <a href="<?= url('feature-spec.php') ?>" class="ds-btn ds-btn-ghost">명세표</a>
            <button type="button" class="ds-btn" id="dsFullscreen" title="전체화면 (Alt+F)">⛶ 전체화면</button>
        </div>
    </header>

    <?php if (!empty($canEdit)): ?>
    <div class="ds-format-bar" id="dsFormatBar" aria-label="셀 서식">
        <span class="ds-format-label">서식</span>
        <span class="ds-format-target" id="dsFormatTarget">셀을 선택하세요</span>
        <span class="ds-format-hint">순서: ▲▼ 또는 ⋮⋮ 드래그 (같은 상위끼리)</span>
        <div class="ds-format-group" title="배경색">
            <span class="ds-format-group-label">배경</span>
            <button type="button" class="ds-swatch" data-style-bg="" title="없음" style="background:#fff;border:1px dashed #999;"></button>
            <button type="button" class="ds-swatch" data-style-bg="#FFF2CC" title="노랑" style="background:#FFF2CC;"></button>
            <button type="button" class="ds-swatch" data-style-bg="#D9EAD3" title="연두" style="background:#D9EAD3;"></button>
            <button type="button" class="ds-swatch" data-style-bg="#CFE2F3" title="하늘" style="background:#CFE2F3;"></button>
            <button type="button" class="ds-swatch" data-style-bg="#F4CCCC" title="분홍" style="background:#F4CCCC;"></button>
            <button type="button" class="ds-swatch" data-style-bg="#FCE5CD" title="주황" style="background:#FCE5CD;"></button>
            <button type="button" class="ds-swatch" data-style-bg="#E0E0E0" title="회색" style="background:#E0E0E0;"></button>
            <label class="ds-swatch ds-swatch-custom" title="직접 선택">
                <input type="color" id="dsBgColor" value="#FFF2CC">
            </label>
        </div>
        <div class="ds-format-group" title="글자색">
            <span class="ds-format-group-label">글자</span>
            <button type="button" class="ds-swatch ds-swatch-text" data-style-color="" title="기본" style="color:#111;">A</button>
            <button type="button" class="ds-swatch ds-swatch-text" data-style-color="#000000" title="검정" style="color:#000;">A</button>
            <button type="button" class="ds-swatch ds-swatch-text" data-style-color="#CC0000" title="빨강" style="color:#CC0000;">A</button>
            <button type="button" class="ds-swatch ds-swatch-text" data-style-color="#1155CC" title="파랑" style="color:#1155CC;">A</button>
            <button type="button" class="ds-swatch ds-swatch-text" data-style-color="#38761D" title="초록" style="color:#38761D;">A</button>
            <button type="button" class="ds-swatch ds-swatch-text" data-style-color="#666666" title="회색" style="color:#666;">A</button>
            <label class="ds-swatch ds-swatch-custom" title="직접 선택">
                <input type="color" id="dsFgColor" value="#CC0000">
            </label>
        </div>
        <button type="button" class="ds-btn ds-btn-bold" id="dsBoldBtn" title="굵게" aria-pressed="false"><strong>B</strong></button>
        <button type="button" class="ds-btn ds-btn-ghost" id="dsStyleClear" title="서식 지우기">지우기</button>
    </div>
    <?php endif; ?>

    <?php if (!empty($canEdit)): ?>
    <div class="ds-add-panel" id="dsAddPanel" hidden>
        <label class="ds-add-label">상위 선택
            <select id="dsAddParent" class="ds-cell-select"></select>
        </label>
        <input type="text" id="dsAddTitle" class="ds-cell-input" placeholder="제목 (Enter로 추가)" maxlength="500">
        <button type="button" class="ds-btn ds-btn-primary" id="dsAddConfirm">추가</button>
        <button type="button" class="ds-btn" id="dsAddCancel">취소</button>
    </div>
    <?php endif; ?>

    <div class="ds-grid-wrap">
        <table class="ds-grid" id="dsGrid">
            <thead>
                <tr>
                    <th class="ds-c-sort"></th>
                    <th class="ds-c-depth">D</th>
                    <th class="ds-c-d1">구분</th>
                    <th class="ds-c-d2">항목</th>
                    <th class="ds-c-d3">내용</th>
                    <th class="ds-c-prio">우선순위</th>
                    <th class="ds-c-status">상태</th>
                    <th class="ds-c-phase">단계</th>
                    <th class="ds-c-desc">설명</th>
                    <th class="ds-c-act"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sheetRows)): ?>
                <tr class="ds-empty-row">
                    <td colspan="10">데이터가 없습니다. 상단 「시드」또는 ＋영역으로 시작하세요.</td>
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
                    // 같은 상위 그룹 안에서는 상위 컬럼 반복 표시 생략
                    $showCtxD1 = ($d > 1 && $d1Changed);
                    $showCtxD2 = ($d === 3 && $d2Changed);

                    $rowClass = 'ds-r ds-r--d' . $d;
                    if ($d === 1 || ($d > 1 && $d1Changed)) {
                        $rowClass .= ' is-group-d1';
                    }
                    if ($d === 2 || ($d === 3 && $d2Changed)) {
                        $rowClass .= ' is-group-d2';
                    }
                    if (isset($it['status']) && $it['status'] === 'done') {
                        $rowClass .= ' is-done';
                    }
                    if (isset($it['status']) && $it['status'] === 'out') {
                        $rowClass .= ' is-out';
                    }
                    if ($focusId && $focusId === $id) {
                        $rowClass .= ' is-focus';
                    }
                    $title = isset($it['title']) ? $it['title'] : '';
                    $desc = isset($it['description']) ? $it['description'] : '';
                    $prio = isset($it['priority']) ? $it['priority'] : 'P1';
                    $st = isset($it['status']) ? $it['status'] : 'planned';
                    $rowStyles = DevScopeService::parseStyle(isset($it['style_json']) ? $it['style_json'] : null);
                    $titleStyle = DevScopeService::fieldStyleAttr($rowStyles, 'title');
                    $descStyle = DevScopeService::fieldStyleAttr($rowStyles, 'description');
                    $titleStyleJson = isset($rowStyles['title']) ? json_encode($rowStyles['title'], JSON_UNESCAPED_UNICODE) : '{}';
                    $descStyleJson = isset($rowStyles['description']) ? json_encode($rowStyles['description'], JSON_UNESCAPED_UNICODE) : '{}';
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
                        <?php if (!empty($canEdit)): ?>
                        <select class="ds-cell ds-cell--status" data-field="status">
                            <?php foreach ($statuses as $sk => $sl): ?>
                            <option value="<?= e($sk) ?>" <?= $st === $sk ? 'selected' : '' ?>><?= e($sl) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <?= e(isset($statuses[$st]) ? $statuses[$st] : $st) ?>
                        <?php endif; ?>
                    </td>
                    <td class="ds-c-phase">
                        <?php if (!empty($canEdit)): ?>
                        <select class="ds-cell ds-cell--phase" data-move-phase="<?= $id ?>" data-depth="<?= $d ?>" title="구축 단계 이동 (하위 포함)">
                            <?php foreach ($phases as $pk => $ph): ?>
                            <option value="<?= e($pk) ?>" <?= $phaseKey === $pk ? 'selected' : '' ?>><?= e($ph['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <?= e($phaseMeta['label']) ?>
                        <?php endif; ?>
                    </td>
                    <td class="ds-c-desc">
                        <?php if (!empty($canEdit)): ?>
                        <input type="text" class="ds-cell" data-field="description" value="<?= e($desc) ?>" placeholder=""
                               data-style="<?= e($descStyleJson) ?>"<?= $descStyle !== '' ? ' style="' . e($descStyle) . '"' : '' ?>>
                        <?php else: ?>
                        <span<?= $descStyle !== '' ? ' style="' . e($descStyle) . '"' : '' ?>><?= e($desc) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="ds-c-act">
                        <?php if (!empty($canEdit)): ?>
                        <?php
                            $otherPhase = $phaseKey === 'phase-1' ? 'phase-enhance' : 'phase-1';
                            $otherLabel = isset($phases[$otherPhase]['label']) ? $phases[$otherPhase]['label'] : $otherPhase;
                            $moveTitle = '「' . $otherLabel . '」로 이동 (하위 포함)';
                        ?>
                        <?php if ($d < 3): ?>
                        <button type="button" class="ds-ico" data-add-child="<?= $d + 1 ?>" data-parent="<?= $id ?>" title="하위 추가">＋</button>
                        <?php endif; ?>
                        <button type="button" class="ds-ico ds-ico-move" data-move-to="<?= e($otherPhase) ?>" data-id="<?= $id ?>" data-depth="<?= $d ?>" title="<?= e($moveTitle) ?>">⇄</button>
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
    var app = document.getElementById('dsApp');
    if (!app) return;

    // 전체화면
    var btnFs = document.getElementById('dsFullscreen');
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

    if (app.getAttribute('data-can-edit') !== '1') return;

    var saveUrl = app.getAttribute('data-save-url');
    var csrf = app.getAttribute('data-csrf');
    var phase = app.getAttribute('data-phase');
    var hint = document.getElementById('dsSaveHint');
    var addPanel = document.getElementById('dsAddPanel');
    var addParent = document.getElementById('dsAddParent');
    var addTitle = document.getElementById('dsAddTitle');
    var pendingDepth = 3;
    var pendingParent = 0;
    var saveTimer = null;
    var activeCell = null;
    var activeStyle = { bg: '', color: '', bold: 0 };

    var d1Parents = <?= json_encode(array_map(function ($p) {
        return array('id' => (int) $p['id'], 'title' => $p['title']);
    }, isset($d1Parents) ? $d1Parents : array()), JSON_UNESCAPED_UNICODE) ?>;
    var d2Parents = <?= json_encode(array_map(function ($p) {
        return array('id' => (int) $p['id'], 'title' => $p['title']);
    }, isset($d2Parents) ? $d2Parents : array()), JSON_UNESCAPED_UNICODE) ?>;

    function setHint(msg, ok) {
        if (!hint) return;
        hint.textContent = msg || '';
        hint.className = 'ds-save-hint' + (msg ? (ok ? ' is-ok' : ' is-err') : '');
        if (msg && ok) {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(function () { setHint('', true); }, 1600);
        }
    }

    function parseCellStyle(el) {
        if (!el) return { bg: '', color: '', bold: 0 };
        try {
            var raw = el.getAttribute('data-style') || '{}';
            var s = JSON.parse(raw);
            return {
                bg: s.bg || '',
                color: s.color || '',
                bold: s.bold ? 1 : 0
            };
        } catch (err) {
            return { bg: '', color: '', bold: 0 };
        }
    }

    function applyStyleToEl(el, style) {
        if (!el) return;
        el.style.backgroundColor = style.bg || '';
        el.style.color = style.color || '';
        if (style.bold) {
            el.style.fontWeight = '700';
        } else if (style.bg || style.color) {
            el.style.fontWeight = '400';
        } else {
            el.style.fontWeight = '';
        }
        el.setAttribute('data-style', JSON.stringify({
            bg: style.bg || '',
            color: style.color || '',
            bold: style.bold ? 1 : 0
        }));
    }

    function syncFormatUi() {
        var target = document.getElementById('dsFormatTarget');
        var boldBtn = document.getElementById('dsBoldBtn');
        var bar = document.getElementById('dsFormatBar');
        if (!activeCell) {
            if (target) target.textContent = '셀을 선택하세요';
            if (boldBtn) boldBtn.setAttribute('aria-pressed', 'false');
            if (bar) bar.classList.remove('is-active');
            return;
        }
        var field = activeCell.getAttribute('data-field') || '';
        var tr = activeCell.closest('tr[data-id]');
        var label = field === 'description' ? '설명' : '제목';
        if (target) target.textContent = (tr ? ('#' + tr.getAttribute('data-id') + ' ') : '') + label;
        if (boldBtn) boldBtn.setAttribute('aria-pressed', activeStyle.bold ? 'true' : 'false');
        if (bar) bar.classList.add('is-active');
        var bgInput = document.getElementById('dsBgColor');
        var fgInput = document.getElementById('dsFgColor');
        if (bgInput && activeStyle.bg) bgInput.value = activeStyle.bg;
        if (fgInput && activeStyle.color) fgInput.value = activeStyle.color;
    }

    function saveActiveStyle() {
        if (!activeCell) {
            setHint('먼저 제목/설명 셀을 선택하세요', false);
            return;
        }
        var tr = activeCell.closest('tr[data-id]');
        if (!tr) return;
        var field = activeCell.getAttribute('data-field') || 'title';
        if (field !== 'title' && field !== 'description') {
            setHint('제목·설명 셀만 서식 지정 가능', false);
            return;
        }
        applyStyleToEl(activeCell, activeStyle);
        setHint('서식 저장 중…', true);
        postForm({
            action: 'save_style',
            item_id: tr.getAttribute('data-id'),
            field: field,
            bg: activeStyle.bg || '',
            color: activeStyle.color || '',
            bold: activeStyle.bold ? '1' : '0'
        }).then(function (res) {
            if (!res.ok) {
                setHint((res.json && res.json.error) || '서식 저장 실패', false);
                return;
            }
            if (res.json && res.json.style) {
                var fieldKey = field;
                var st = res.json.style[fieldKey] || { bg: '', color: '', bold: 0 };
                activeStyle = {
                    bg: st.bg || '',
                    color: st.color || '',
                    bold: st.bold ? 1 : 0
                };
                applyStyleToEl(activeCell, activeStyle);
                syncFormatUi();
            }
            setHint('서식 저장됨', true);
        }).catch(function () {
            setHint('서식 저장 실패', false);
        });
    }

    function setActiveCell(el) {
        if (activeCell) activeCell.classList.remove('is-format-target');
        activeCell = el;
        if (activeCell) {
            activeCell.classList.add('is-format-target');
            activeStyle = parseCellStyle(activeCell);
        } else {
            activeStyle = { bg: '', color: '', bold: 0 };
        }
        syncFormatUi();
    }

    // 서식 툴바
    (function initFormatBar() {
        var bar = document.getElementById('dsFormatBar');
        if (!bar) return;

        bar.addEventListener('mousedown', function (e) {
            // 셀 focus 유지
            if (e.target && e.target.tagName !== 'INPUT') {
                e.preventDefault();
            }
        });

        bar.querySelectorAll('[data-style-bg]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!activeCell) { setHint('먼저 제목/설명 셀을 선택하세요', false); return; }
                activeStyle.bg = btn.getAttribute('data-style-bg') || '';
                saveActiveStyle();
            });
        });
        bar.querySelectorAll('[data-style-color]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!activeCell) { setHint('먼저 제목/설명 셀을 선택하세요', false); return; }
                activeStyle.color = btn.getAttribute('data-style-color') || '';
                saveActiveStyle();
            });
        });

        var bgInput = document.getElementById('dsBgColor');
        var fgInput = document.getElementById('dsFgColor');
        if (bgInput) {
            bgInput.addEventListener('change', function () {
                if (!activeCell) { setHint('먼저 제목/설명 셀을 선택하세요', false); return; }
                activeStyle.bg = bgInput.value;
                saveActiveStyle();
            });
        }
        if (fgInput) {
            fgInput.addEventListener('change', function () {
                if (!activeCell) { setHint('먼저 제목/설명 셀을 선택하세요', false); return; }
                activeStyle.color = fgInput.value;
                saveActiveStyle();
            });
        }

        var boldBtn = document.getElementById('dsBoldBtn');
        if (boldBtn) {
            boldBtn.addEventListener('click', function () {
                if (!activeCell) { setHint('먼저 제목/설명 셀을 선택하세요', false); return; }
                activeStyle.bold = activeStyle.bold ? 0 : 1;
                saveActiveStyle();
            });
        }
        var clearBtn = document.getElementById('dsStyleClear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (!activeCell) { setHint('먼저 제목/설명 셀을 선택하세요', false); return; }
                activeStyle = { bg: '', color: '', bold: 0 };
                saveActiveStyle();
            });
        }
    })();

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
            // redirect:manual 이면 302 opaqueredirect / 0 등으로 올 수 있음 → 새로고침
            if (r.type === 'opaqueredirect' || (r.status >= 300 && r.status < 400)) {
                return { ok: true, json: { ok: true, redirected: true } };
            }
            return r.text().then(function (text) {
                var j = null;
                try { j = JSON.parse(text); } catch (err) { j = null; }
                if (!j) {
                    var looksHtml = /<!DOCTYPE|<html|<body|sidebar-nav|ds-app/i.test(text || '');
                    var msg = looksHtml
                        ? '서버가 HTML을 반환했습니다. 세션·보안토큰 문제일 수 있습니다. 새로고침 후 다시 시도해 주세요.'
                        : ('서버 오류 HTTP ' + r.status + (text ? (': ' + text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').slice(0, 180)) : ''));
                    return { ok: false, json: { error: msg, code: looksHtml ? 'html_response' : 'bad_json' } };
                }
                return { ok: r.ok && !!j.ok, json: j };
            });
        });
    }

    function rowSnapshot(tr) {
        var titleEl = tr.querySelector('[data-field="title"]');
        var descEl = tr.querySelector('[data-field="description"]');
        var prioEl = tr.querySelector('[data-field="priority"]');
        var stEl = tr.querySelector('[data-field="status"]');
        return {
            item_id: tr.getAttribute('data-id'),
            title: titleEl ? titleEl.value : '',
            description: descEl ? descEl.value : '',
            priority: prioEl ? prioEl.value : 'P1',
            status: stEl ? stEl.value : 'planned'
        };
    }

    function saveRow(tr) {
        if (!tr || !tr.getAttribute('data-id')) return;
        var snap = rowSnapshot(tr);
        if (!snap.title.trim()) {
            setHint('제목은 비울 수 없습니다', false);
            return;
        }
        tr.classList.add('is-saving');
        setHint('저장 중…', true);
        postForm({
            action: 'inline_save',
            item_id: snap.item_id,
            title: snap.title,
            description: snap.description,
            priority: snap.priority,
            status: snap.status
        }).then(function (res) {
            tr.classList.remove('is-saving');
            if (!res.ok) {
                setHint((res.json && res.json.error) || '저장 실패', false);
                return;
            }
            var st = snap.status;
            tr.classList.toggle('is-done', st === 'done');
            tr.classList.toggle('is-out', st === 'out');
            setHint('저장됨', true);
        }).catch(function () {
            tr.classList.remove('is-saving');
            setHint('저장 실패', false);
        });
    }

    var phaseLabels = {};
    try {
        var rawPhases = JSON.parse(app.getAttribute('data-phases') || '{}');
        Object.keys(rawPhases).forEach(function (k) {
            phaseLabels[k] = rawPhases[k].label || k;
        });
    } catch (err) {}

    function confirmMove(depth, targetPhase) {
        var label = phaseLabels[targetPhase] || targetPhase;
        var extra = depth < 3 ? ' (하위 항목 포함)' : '';
        return confirm('「' + label + '」단계로 이동할까요?' + extra);
    }

    function movePhase(itemId, targetPhase, depth, selectEl) {
        if (!targetPhase || targetPhase === phase) {
            if (selectEl) selectEl.value = phase;
            return;
        }
        if (!confirmMove(depth || 3, targetPhase)) {
            if (selectEl) selectEl.value = phase;
            return;
        }
        setHint('이동 중…', true);
        postForm({
            action: 'move_phase',
            item_id: String(itemId),
            target_phase: targetPhase
        }).then(function (res) {
            if (!res.ok) {
                if (selectEl) selectEl.value = phase;
                setHint((res.json && res.json.error) || '이동 실패', false);
                return;
            }
            var msg = (res.json && res.json.message) || '이동됨';
            setHint(msg, true);
            var focus = (res.json && res.json.ids && res.json.ids[0]) ? res.json.ids[0] : itemId;
            var base = saveUrl.split('?')[0];
            location.href = base + '?phase=' + encodeURIComponent(targetPhase) + '&focus=' + encodeURIComponent(focus);
        }).catch(function () {
            if (selectEl) selectEl.value = phase;
            setHint('이동 실패', false);
        });
    }

    var grid = document.getElementById('dsGrid');
    if (grid) {
        grid.addEventListener('change', function (e) {
            var el = e.target;
            if (!el.classList.contains('ds-cell')) return;
            if (el.getAttribute('data-move-phase')) {
                movePhase(
                    el.getAttribute('data-move-phase'),
                    el.value,
                    parseInt(el.getAttribute('data-depth') || '3', 10),
                    el
                );
                return;
            }
            var tr = el.closest('tr[data-id]');
            if (tr) saveRow(tr);
        });
        grid.addEventListener('focusout', function (e) {
            var el = e.target;
            if (!el.classList || !el.classList.contains('ds-cell') || el.tagName !== 'INPUT') return;
            var tr = el.closest('tr[data-id]');
            if (!tr) return;
            if (el.value !== (el.getAttribute('data-last') || el.defaultValue)) {
                el.setAttribute('data-last', el.value);
                saveRow(tr);
            }
        });
        grid.addEventListener('focusin', function (e) {
            var el = e.target;
            if (el.classList && el.classList.contains('ds-cell') && el.tagName === 'INPUT') {
                el.setAttribute('data-last', el.value);
                var field = el.getAttribute('data-field');
                if (field === 'title' || field === 'description') {
                    setActiveCell(el);
                }
            }
        });
        grid.addEventListener('click', function (e) {
            var cell = e.target.closest('.ds-cell[data-field="title"], .ds-cell[data-field="description"]');
            if (cell && cell.tagName === 'INPUT') {
                setActiveCell(cell);
            }
        });
        grid.addEventListener('keydown', function (e) {
            var el = e.target;
            if (!el.classList || !el.classList.contains('ds-cell')) return;
            if (e.key === 'Enter' && el.tagName === 'INPUT') {
                e.preventDefault();
                el.blur();
            }
            if (e.key === 'Escape' && el.tagName === 'INPUT') {
                el.value = el.getAttribute('data-last') || el.defaultValue;
                el.blur();
            }
        });
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
                    location.href = saveUrl.split('?')[0] + '?phase=' + encodeURIComponent(phase) + '&focus=' + encodeURIComponent(sortId);
                }).catch(function () { setHint('순서 변경 실패', false); });
                return;
            }
            var moveBtn = e.target.closest('[data-move-to]');
            if (moveBtn) {
                movePhase(
                    moveBtn.getAttribute('data-id'),
                    moveBtn.getAttribute('data-move-to'),
                    parseInt(moveBtn.getAttribute('data-depth') || '3', 10),
                    null
                );
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

        // 드래그 앤 드롭 (같은 parent·depth 형제끼리)
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

                // DOM: 드래그한 행(+하위 블록)을 목표 형제 앞/뒤로 이동
                // 하위 포함 블록 = 해당 tr부터 다음 형제(또는 상위 depth 변경) 전까지
                function blockEnd(startTr) {
                    var d0 = parseInt(startTr.getAttribute('data-depth'), 10);
                    var next = startTr.nextElementSibling;
                    while (next && next.getAttribute('data-id')) {
                        var nd = parseInt(next.getAttribute('data-depth'), 10);
                        if (nd <= d0) break;
                        next = next.nextElementSibling;
                    }
                    return next; // insertBefore 기준 (null이면 append)
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
                    // 아래로: over 블록 뒤
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
                    location.href = saveUrl.split('?')[0] + '?phase=' + encodeURIComponent(phase) + '&focus=' + encodeURIComponent(dragRow.getAttribute('data-id'));
                }).catch(function () {
                    setHint('순서 저장 실패', false);
                    location.reload();
                });
            });
        })();
    }

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
            doQuickAdd(depth, parentId || 0, '');
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
            location.href = saveUrl + (id ? ('&focus=' + id) : '');
        }).catch(function () { setHint('추가 실패', false); });
    }

    app.querySelectorAll('[data-quick-add]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var depth = parseInt(btn.getAttribute('data-quick-add'), 10);
            var parent = parseInt(btn.getAttribute('data-parent') || '0', 10);
            openAdd(depth, parent);
        });
    });

    var confirmBtn = document.getElementById('dsAddConfirm');
    var cancelBtn = document.getElementById('dsAddCancel');
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
