(function () {
    'use strict';

    var card = document.getElementById('ganttCard');
    if (!card) {
        return;
    }

    var endpoint = card.getAttribute('data-endpoint');
    var csrfToken = card.getAttribute('data-csrf') || '';
    if (!csrfToken) {
        var csrfInput = card.querySelector('input[name="_csrf"]');
        csrfToken = csrfInput ? csrfInput.value : '';
    }
    var avgEl = document.getElementById('ganttAvgProgress');

    function clamp(value) {
        value = parseInt(value, 10);
        if (isNaN(value) || value < 0) {
            return 0;
        }
        return value > 100 ? 100 : value;
    }

    function findRow(taskId) {
        return card.querySelector('.gantt-row[data-task-id="' + taskId + '"]');
    }

    function updateRowVisuals(taskId, progress) {
        var row = findRow(taskId);
        if (!row) {
            return;
        }
        var fill = row.querySelector('.gantt-bar-fill');
        var label = row.querySelector('.gantt-bar-label');
        if (fill) {
            fill.style.width = progress + '%';
        }
        if (label) {
            label.textContent = progress + '%';
        }
    }

    function updatePhaseSummaries(phases) {
        if (!phases) {
            return;
        }
        var phaseRows = card.querySelectorAll('.gantt-phase-row');
        // 단계 순서는 서버 tasks 순서와 동일하므로 label 매칭으로 갱신
        phases.forEach(function (p) {
            phaseRows.forEach(function (row) {
                var strong = row.querySelector('strong');
                if (strong && strong.textContent === p.label) {
                    var pct = row.querySelector('.gantt-phase-pct');
                    if (pct) {
                        pct.textContent = p.progress + '%';
                    }
                }
            });
        });
    }

    function saveProgress(input) {
        var taskId = input.getAttribute('data-task-id');
        var value = clamp(input.value);
        input.value = value;
        input.disabled = true;

        var body = new URLSearchParams();
        body.append('action', 'update_progress');
        body.append('ajax', '1');
        body.append('id', taskId);
        body.append('progress', value);
        body.append('_csrf', csrfToken);

        fetch(endpoint, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body,
            credentials: 'same-origin'
        }).then(function (res) {
            return res.json().catch(function () { return { ok: false, error: '응답 오류' }; });
        }).then(function (data) {
            input.disabled = false;
            if (!data || !data.ok) {
                input.classList.add('gantt-input-error');
                setTimeout(function () { input.classList.remove('gantt-input-error'); }, 1500);
                if (data && data.error) {
                    console.warn('일정 저장 실패: ' + data.error);
                }
                return;
            }
            updateRowVisuals(data.id, data.progress);
            if (avgEl && typeof data.avg_progress !== 'undefined') {
                avgEl.textContent = data.avg_progress + '%';
            }
            updatePhaseSummaries(data.phases);
            input.classList.add('gantt-input-saved');
            setTimeout(function () { input.classList.remove('gantt-input-saved'); }, 900);
        }).catch(function () {
            input.disabled = false;
            input.classList.add('gantt-input-error');
            setTimeout(function () { input.classList.remove('gantt-input-error'); }, 1500);
        });
    }

    card.querySelectorAll('.gantt-progress-input').forEach(function (input) {
        input.addEventListener('change', function () {
            saveProgress(input);
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                input.blur();
            }
        });
    });

    // ---- 시작/종료일 인라인 편집 ----------------------------------------

    function buildMonthsHtml(months) {
        return months.map(function (m) {
            return '<div class="gantt-month" style="width:' + m.width + '%">'
                + '<span>' + m.year + '년 ' + m.label + '</span></div>';
        }).join('');
    }

    function buildGridHtml(months) {
        return months.map(function (m) {
            return '<div class="gantt-grid-col" style="width:' + m.width + '%"></div>';
        }).join('');
    }

    /** 서버가 재계산한 간트 모델로 월 헤더 · 격자 · 막대 · 오늘선을 갱신 */
    function applyGanttModel(model) {
        if (!model) {
            return;
        }

        var monthsWrap = card.querySelector('.gantt-head .gantt-months');
        if (monthsWrap && model.months) {
            monthsWrap.innerHTML = buildMonthsHtml(model.months);
        }

        (model.tasks || []).forEach(function (task) {
            var row = findRow(task.id);
            if (!row) {
                return;
            }

            var grid = row.querySelector('.gantt-grid');
            if (grid && model.months) {
                grid.innerHTML = buildGridHtml(model.months);
            }

            var bar = row.querySelector('.gantt-bar');
            if (bar) {
                bar.style.left = task.offset_pct + '%';
                bar.style.width = task.width_pct + '%';
                bar.title = task.start + ' ~ ' + task.end + ' (' + task.days + '일)';
            }

            var track = row.querySelector('.gantt-track');
            if (track) {
                var todayLine = track.querySelector('.gantt-today');
                if (model.today_offset !== null && typeof model.today_offset !== 'undefined') {
                    if (!todayLine) {
                        todayLine = document.createElement('div');
                        todayLine.className = 'gantt-today';
                        todayLine.title = '오늘';
                        track.insertBefore(todayLine, bar);
                    }
                    todayLine.style.left = model.today_offset + '%';
                } else if (todayLine) {
                    todayLine.parentNode.removeChild(todayLine);
                }
            }
        });

        if (avgEl && typeof model.avg_progress !== 'undefined') {
            avgEl.textContent = model.avg_progress + '%';
        }
        updatePhaseSummaries(model.phases);
    }

    function markInputs(inputs, className) {
        inputs.forEach(function (input) {
            input.classList.add(className);
            setTimeout(function () { input.classList.remove(className); }, 1200);
        });
    }

    function saveDates(taskId) {
        var row = findRow(taskId);
        if (!row) {
            return;
        }
        var startInput = row.querySelector('.gantt-date-input[data-role="start"]');
        var endInput = row.querySelector('.gantt-date-input[data-role="end"]');
        if (!startInput || !endInput) {
            return;
        }

        var start = startInput.value;
        var end = endInput.value;
        var inputs = [startInput, endInput];

        if (!start || !end) {
            markInputs(inputs, 'gantt-input-error');
            return;
        }
        // 종료일이 시작일보다 빠르면 시작일로 보정 (서버와 동일 규칙)
        if (end < start) {
            end = start;
            endInput.value = end;
        }

        inputs.forEach(function (input) { input.disabled = true; });

        var body = new URLSearchParams();
        body.append('action', 'update_dates');
        body.append('ajax', '1');
        body.append('id', taskId);
        body.append('start_date', start);
        body.append('end_date', end);
        body.append('_csrf', csrfToken);

        fetch(endpoint, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body,
            credentials: 'same-origin'
        }).then(function (res) {
            return res.json().catch(function () { return { ok: false, error: '응답 오류' }; });
        }).then(function (data) {
            inputs.forEach(function (input) { input.disabled = false; });
            if (!data || !data.ok) {
                markInputs(inputs, 'gantt-input-error');
                if (data && data.error) {
                    console.warn('일정 저장 실패: ' + data.error);
                }
                return;
            }
            startInput.value = data.start;
            endInput.value = data.end;
            applyGanttModel(data.gantt);
            markInputs(inputs, 'gantt-input-saved');
        }).catch(function () {
            inputs.forEach(function (input) { input.disabled = false; });
            markInputs(inputs, 'gantt-input-error');
        });
    }

    card.querySelectorAll('.gantt-date-input').forEach(function (input) {
        input.addEventListener('change', function () {
            saveDates(input.getAttribute('data-task-id'));
        });
    });

    // ---- 드래그앤드롭 순서/단계 이동 ------------------------------------

    var body = card.querySelector('.gantt-body');
    var taskHandles = card.querySelectorAll('.gantt-drag-handle');
    var phaseHandles = card.querySelectorAll('.gantt-phase-drag-handle');

    if (body && (taskHandles.length || phaseHandles.length)) {
        var dragged = null;        // 이동 대상(작업 행 또는 단계 헤더)
        var dragMode = null;       // 'task' | 'phase'
        var draggedGroupEls = [];  // 단계 드래그 시 함께 이동하는 그룹 요소들
        var orderBefore = null;    // 드래그 시작 시점 순서 스냅샷
        var savingOrder = false;   // 중복 저장 방지

        /** DOM 표시 순서를 읽어 [{id, phase}] 배열로 만든다. phase = 바로 위 단계 헤더. */
        function collectOrder() {
            var items = [];
            var currentPhase = null;
            var firstPhase = null;
            Array.prototype.slice.call(body.children).forEach(function (el) {
                if (el.classList.contains('gantt-phase-row')) {
                    currentPhase = el.getAttribute('data-phase');
                    if (firstPhase === null) {
                        firstPhase = currentPhase;
                    }
                } else if (el.classList.contains('gantt-row')) {
                    var phase = currentPhase !== null ? currentPhase : firstPhase;
                    var id = parseInt(el.getAttribute('data-task-id'), 10);
                    if (!id) {
                        return;
                    }
                    if (phase) {
                        el.setAttribute('data-phase', phase);
                    }
                    items.push({
                        id: id,
                        phase: phase || ''
                    });
                }
            });
            return items;
        }

        function orderKey(items) {
            return items.map(function (it) {
                return it.id + ':' + (it.phase || '');
            }).join('|');
        }

        function persistOrder() {
            if (savingOrder) {
                return;
            }
            var items = collectOrder();
            var afterKey = orderKey(items);
            if (!items.length || afterKey === orderBefore) {
                orderBefore = null;
                return;
            }
            if (!endpoint) {
                console.warn('일정 순서 저장 실패: endpoint 없음');
                return;
            }
            if (!csrfToken) {
                alert('보안 토큰이 없어 순서를 저장할 수 없습니다. 페이지를 새로고침 후 다시 시도하세요.');
                return;
            }

            savingOrder = true;
            orderBefore = null;
            card.classList.add('gantt-reordering');

            var payload = new FormData();
            payload.append('action', 'reorder');
            payload.append('ajax', '1');
            payload.append('order', JSON.stringify(items));
            payload.append('_csrf', csrfToken);

            fetch(endpoint, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: payload,
                credentials: 'same-origin'
            }).then(function (res) {
                return res.text().then(function (text) {
                    var data = null;
                    try {
                        data = JSON.parse(text);
                    } catch (err) {
                        data = null;
                    }
                    if (!res.ok || !data || !data.ok) {
                        var msg = (data && data.error) ? data.error : ('저장 실패 (HTTP ' + res.status + ')');
                        throw new Error(msg);
                    }
                    return data;
                });
            }).then(function () {
                // 단계 헤더·색상·집계를 서버 기준으로 맞추기 위해 새로고침
                window.location.reload();
            }).catch(function (err) {
                savingOrder = false;
                card.classList.remove('gantt-reordering');
                console.warn('일정 순서 저장 실패:', err && err.message ? err.message : err);
                alert((err && err.message) ? err.message : '일정 순서 저장에 실패했습니다.');
            });
        }

        function endDragSession() {
            var hadDrag = !!dragged;
            stopAutoScroll();
            if (dragMode === 'phase') {
                draggedGroupEls.forEach(function (el) {
                    el.classList.remove('gantt-group-dragging');
                });
            } else if (dragged) {
                dragged.classList.remove('gantt-row-dragging');
            }
            dragged = null;
            dragMode = null;
            draggedGroupEls = [];
            if (hadDrag) {
                persistOrder();
            } else {
                orderBefore = null;
            }
        }

        /** 현재 커서 Y 위치 기준, 그 앞에 삽입할 형제 요소를 찾는다(단계 헤더 포함). */
        function getDragAfter(y) {
            var children = Array.prototype.slice.call(body.children);
            for (var i = 0; i < children.length; i++) {
                var el = children[i];
                if (el === dragged) {
                    continue;
                }
                if (dragMode === 'phase' && draggedGroupEls.indexOf(el) !== -1) {
                    continue;
                }
                var box = el.getBoundingClientRect();
                if (y < box.top + box.height / 2) {
                    return el;
                }
            }
            return null;
        }

        /** 본문을 단계 그룹 블록(헤더 + 소속 작업 행들)으로 분해 */
        function getGroupBlocks() {
            var blocks = [];
            var cur = null;
            Array.prototype.slice.call(body.children).forEach(function (el) {
                if (el.classList.contains('gantt-phase-row')) {
                    cur = { header: el, els: [el] };
                    blocks.push(cur);
                } else if (el.classList.contains('gantt-row') && cur) {
                    cur.els.push(el);
                }
            });
            return blocks;
        }

        /** 단계 그룹 전체를 커서 위치의 다른 그룹 앞/뒤로 이동 */
        function repositionGroup(y) {
            var blocks = getGroupBlocks();
            var refHeader = null;
            for (var i = 0; i < blocks.length; i++) {
                var b = blocks[i];
                if (b.header === dragged) {
                    continue;
                }
                var top = b.els[0].getBoundingClientRect().top;
                var bottom = b.els[b.els.length - 1].getBoundingClientRect().bottom;
                if (y < top + (bottom - top) / 2) {
                    refHeader = b.header;
                    break;
                }
            }
            draggedGroupEls.forEach(function (el) {
                if (refHeader) {
                    body.insertBefore(el, refHeader);
                } else {
                    body.appendChild(el);
                }
            });
        }

        /** 현재 커서 Y 기준으로 드래그 항목을 목록 내 올바른 위치에 삽입 */
        function reposition(y) {
            var after = getDragAfter(y);
            if (after == null) {
                body.appendChild(dragged);
            } else if (after !== dragged) {
                body.insertBefore(dragged, after);
            }
        }

        /** 드래그 모드에 따라 위치 갱신 */
        function repositionCurrent(y) {
            if (!dragged) {
                return;
            }
            if (dragMode === 'phase') {
                repositionGroup(y);
            } else {
                reposition(y);
            }
        }

        // 뷰포트 가장자리 근처에서 창을 자동 스크롤 (화면 밖 위치로도 이동 가능하게)
        var EDGE = 70;
        var MAX_SPEED = 20;
        var lastClientY = 0;
        var scrollRAF = null;

        function autoScrollTick() {
            if (!dragged) {
                scrollRAF = null;
                return;
            }
            var vh = window.innerHeight;
            var y = lastClientY;
            var speed = 0;
            if (y < EDGE) {
                speed = -Math.ceil((EDGE - y) / EDGE * MAX_SPEED);
            } else if (y > vh - EDGE) {
                speed = Math.ceil((y - (vh - EDGE)) / EDGE * MAX_SPEED);
            }
            if (speed !== 0) {
                var before = window.pageYOffset;
                window.scrollBy(0, speed);
                if (window.pageYOffset !== before) {
                    repositionCurrent(y);
                }
            }
            scrollRAF = window.requestAnimationFrame(autoScrollTick);
        }

        function stopAutoScroll() {
            if (scrollRAF) {
                window.cancelAnimationFrame(scrollRAF);
                scrollRAF = null;
            }
        }

        function startAutoScroll() {
            if (!scrollRAF) {
                scrollRAF = window.requestAnimationFrame(autoScrollTick);
            }
        }

        // 핸들에서만 드래그 시작 (행 자체는 draggable이 아님 → 날짜/입력과 충돌 방지)
        taskHandles.forEach(function (handle) {
            handle.addEventListener('dragstart', function (e) {
                var row = handle.closest('.gantt-row');
                if (!row || !body.contains(row)) {
                    e.preventDefault();
                    return;
                }
                dragMode = 'task';
                dragged = row;
                orderBefore = orderKey(collectOrder());
                row.classList.add('gantt-row-dragging');
                lastClientY = e.clientY;
                if (e.dataTransfer) {
                    e.dataTransfer.effectAllowed = 'move';
                    try {
                        e.dataTransfer.setData('text/plain', row.getAttribute('data-task-id') || '');
                        if (e.dataTransfer.setDragImage) {
                            e.dataTransfer.setDragImage(row, 24, 12);
                        }
                    } catch (err) { /* ignore */ }
                }
                startAutoScroll();
            });

            handle.addEventListener('dragend', function () {
                endDragSession();
            });
        });

        phaseHandles.forEach(function (handle) {
            handle.addEventListener('dragstart', function (e) {
                var ph = handle.closest('.gantt-phase-row');
                if (!ph || !body.contains(ph)) {
                    e.preventDefault();
                    return;
                }
                dragMode = 'phase';
                dragged = ph;
                orderBefore = orderKey(collectOrder());
                draggedGroupEls = [ph];
                var sib = ph.nextElementSibling;
                while (sib && sib.classList.contains('gantt-row')) {
                    draggedGroupEls.push(sib);
                    sib = sib.nextElementSibling;
                }
                draggedGroupEls.forEach(function (el) {
                    el.classList.add('gantt-group-dragging');
                });
                lastClientY = e.clientY;
                if (e.dataTransfer) {
                    e.dataTransfer.effectAllowed = 'move';
                    try {
                        e.dataTransfer.setData('text/plain', ph.getAttribute('data-phase') || 'phase');
                        if (e.dataTransfer.setDragImage) {
                            e.dataTransfer.setDragImage(ph, 24, 12);
                        }
                    } catch (err) { /* ignore */ }
                }
                startAutoScroll();
            });

            handle.addEventListener('dragend', function () {
                endDragSession();
            });
        });

        // 헤더 등 목록 바깥에 커서가 있어도 잡히도록 문서 전역에서 처리
        document.addEventListener('dragover', function (e) {
            if (!dragged) {
                return;
            }
            e.preventDefault();
            if (e.dataTransfer) {
                e.dataTransfer.dropEffect = 'move';
            }
            lastClientY = e.clientY;
            repositionCurrent(e.clientY);
        });

        document.addEventListener('drop', function (e) {
            if (!dragged) {
                return;
            }
            e.preventDefault();
            lastClientY = e.clientY;
            repositionCurrent(e.clientY);
        });
    }
})();
