/**
 * 공용 드래그 정렬 (인물/회사 목록, 이력/연혁 항목)
 * window.LabelUpSortable.init(container, options)
 */
(function (global) {
    function parseOrderedIds(raw) {
        if (Array.isArray(raw)) return raw.map(Number).filter(function (n) { return n > 0; });
        if (typeof raw === 'string') {
            try {
                var d = JSON.parse(raw);
                if (Array.isArray(d)) return d.map(Number).filter(function (n) { return n > 0; });
            } catch (e) {}
            return raw.split(/[,\s]+/).map(Number).filter(function (n) { return n > 0; });
        }
        return [];
    }

    function collectIds(container, itemSel) {
        return Array.prototype.map.call(container.querySelectorAll(itemSel), function (el) {
            return parseInt(el.getAttribute('data-id'), 10);
        }).filter(function (n) { return n > 0; });
    }

    function postReorder(url, fields) {
        var body = new FormData();
        Object.keys(fields).forEach(function (k) {
            body.append(k, fields[k]);
        });
        return fetch(url, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.text().then(function (text) {
                var data = null;
                try { data = JSON.parse(text); } catch (e) {}
                if (!res.ok || !data || !data.ok) {
                    throw new Error((data && data.error) ? data.error : ('저장 실패 HTTP ' + res.status));
                }
                return data;
            });
        });
    }

    /**
     * @param {HTMLElement} container
     * @param {object} opts
     *   itemSelector: '[data-id]'
     *   handleSelector: '[data-drag-handle]'
     *   url: string
     *   buildPayload: function(ids) -> object (must include action, _csrf, ajax:1)
     *   onSaved?: function
     *   onError?: function(err)
     */
    function init(container, opts) {
        if (!container || !opts || !opts.url || typeof opts.buildPayload !== 'function') return;
        var itemSel = opts.itemSelector || '[data-id]';
        var handleSel = opts.handleSelector || '[data-drag-handle]';
        var dragItem = null;
        var orderBefore = null;

        function eventEl(e) {
            var t = e.target;
            if (t && t.nodeType === 3) t = t.parentElement;
            return t;
        }

        container.addEventListener('dragstart', function (e) {
            var t = eventEl(e);
            var handle = t && t.closest ? t.closest(handleSel) : null;
            if (!handle || !container.contains(handle)) {
                e.preventDefault();
                return;
            }
            dragItem = handle.closest(itemSel);
            if (!dragItem || !container.contains(dragItem)) {
                dragItem = null;
                e.preventDefault();
                return;
            }
            orderBefore = collectIds(container, itemSel).join(',');
            dragItem.classList.add('is-dragging');
            try {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', dragItem.getAttribute('data-id') || '');
            } catch (err) {}
        });

        container.addEventListener('dragend', function () {
            var moved = dragItem;
            if (dragItem) dragItem.classList.remove('is-dragging');
            container.querySelectorAll('.is-drag-over').forEach(function (el) {
                el.classList.remove('is-drag-over');
            });
            dragItem = null;
            if (!moved) return;
            var ids = collectIds(container, itemSel);
            var orderAfter = ids.join(',');
            if (orderAfter === orderBefore) return;
            orderBefore = null;
            var payload = opts.buildPayload(ids);
            payload.ajax = '1';
            postReorder(opts.url, payload).then(function (data) {
                if (typeof opts.onSaved === 'function') opts.onSaved(data, ids);
            }).catch(function (err) {
                if (typeof opts.onError === 'function') {
                    opts.onError(err);
                } else {
                    alert(err.message || '순서 저장에 실패했습니다.');
                }
            });
        });

        container.addEventListener('dragover', function (e) {
            if (!dragItem) return;
            e.preventDefault();
            try { e.dataTransfer.dropEffect = 'move'; } catch (err) {}
            var t = eventEl(e);
            var over = t && t.closest ? t.closest(itemSel) : null;
            if (!over || over === dragItem || !container.contains(over)) return;
            container.querySelectorAll('.is-drag-over').forEach(function (el) {
                el.classList.remove('is-drag-over');
            });
            over.classList.add('is-drag-over');
            var rect = over.getBoundingClientRect();
            var before = (e.clientY - rect.top) < rect.height / 2;
            if (before) {
                container.insertBefore(dragItem, over);
            } else if (over.nextSibling) {
                container.insertBefore(dragItem, over.nextSibling);
            } else {
                container.appendChild(dragItem);
            }
        });

        container.addEventListener('drop', function (e) {
            e.preventDefault();
        });
    }

    global.LabelUpSortable = {
        init: init,
        parseOrderedIds: parseOrderedIds,
        collectIds: collectIds
    };
})(window);
