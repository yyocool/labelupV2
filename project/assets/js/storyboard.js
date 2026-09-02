document.addEventListener('DOMContentLoaded', function () {
    // 햄버거 → 사이드 드로어
    var hamburger = document.getElementById('sbHamburger');
    var drawer = document.getElementById('sbDrawer');
    var overlay = document.getElementById('sbDrawerOverlay');

    function openDrawer() {
        if (drawer) drawer.classList.add('open');
        if (overlay) overlay.classList.add('active');
        document.body.classList.add('sb-drawer-open');
    }
    function closeDrawer() {
        if (drawer) drawer.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.classList.remove('sb-drawer-open');
    }
    if (hamburger) {
        hamburger.addEventListener('click', function () {
            if (drawer && drawer.classList.contains('open')) closeDrawer();
            else openDrawer();
        });
    }
    if (overlay) overlay.addEventListener('click', closeDrawer);
    if (drawer) {
        drawer.querySelectorAll('.nav-item, .sidebar-brand a, .logout-link').forEach(function (link) {
            link.addEventListener('click', closeDrawer);
        });
    }

    var tree = document.getElementById('sbMenuTree');
    var searchInput = document.getElementById('sbTreeSearch');
    var frameTabs = document.querySelectorAll('.sb-frame-tab');
    var framePanels = document.querySelectorAll('.sb-preview-panel');
    var prevBtn = document.getElementById('sbPrevFrame');
    var nextBtn = document.getElementById('sbNextFrame');
    var counter = document.getElementById('sbFrameCounter');

    // 트리 접기/펼치기
    if (tree) {
        tree.querySelectorAll('.sb-tree-toggle').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var node = btn.closest('.sb-tree-node');
                if (node) node.classList.toggle('expanded');
            });
        });
    }

    // 트리 검색
    if (searchInput && tree) {
        searchInput.addEventListener('input', function () {
            var q = searchInput.value.toLowerCase().trim();
            tree.querySelectorAll('.sb-tree-node').forEach(function (node) {
                var label = node.querySelector('.sb-tree-label');
                if (!label) return;
                var match = !q || label.textContent.toLowerCase().indexOf(q) !== -1;
                node.style.display = match ? '' : 'none';
                if (match && q) {
                    var parent = node.parentElement;
                    while (parent) {
                        if (parent.classList && parent.classList.contains('sb-tree-node')) {
                            parent.classList.add('expanded');
                        }
                        parent = parent.parentElement;
                    }
                }
            });
        });
    }

    function applyCollabFilter(frameId) {
        var filter = document.getElementById('sbFilterCurrent');
        var onlyCurrent = filter && filter.checked;
        document.querySelectorAll('.sb-comment-item, .sb-history-item').forEach(function (el) {
            var fid = el.getAttribute('data-frame-id');
            if (!onlyCurrent) {
                el.style.display = '';
                return;
            }
            el.style.display = (fid === '0' || fid === String(frameId)) ? '' : 'none';
        });
    }

    function showFrame(frameId) {
        frameTabs.forEach(function (tab) {
            tab.classList.toggle('active', tab.getAttribute('data-frame-id') === String(frameId));
        });
        framePanels.forEach(function (panel) {
            panel.classList.toggle('active', panel.getAttribute('data-frame-id') === String(frameId));
        });
        updateCounter(frameId);
        var commentFrameId = document.getElementById('commentFrameId');
        if (commentFrameId) commentFrameId.value = frameId;
        applyCollabFilter(frameId);
        if (history.replaceState) {
            var url = new URL(window.location.href);
            url.searchParams.set('frame_id', frameId);
            history.replaceState(null, '', url.toString());
        }
    }

    function updateCounter(frameId) {
        if (!counter) return;
        var tabs = Array.prototype.slice.call(frameTabs);
        var idx = tabs.findIndex(function (t) { return t.getAttribute('data-frame-id') === String(frameId); });
        if (idx >= 0) counter.textContent = (idx + 1) + ' / ' + tabs.length;
    }

    frameTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            showFrame(tab.getAttribute('data-frame-id'));
        });
    });

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            var tabs = Array.prototype.slice.call(frameTabs);
            var active = tabs.findIndex(function (t) { return t.classList.contains('active'); });
            if (active > 0) showFrame(tabs[active - 1].getAttribute('data-frame-id'));
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            var tabs = Array.prototype.slice.call(frameTabs);
            var active = tabs.findIndex(function (t) { return t.classList.contains('active'); });
            if (active < tabs.length - 1) showFrame(tabs[active + 1].getAttribute('data-frame-id'));
        });
    }

    // 키보드 네비
    document.addEventListener('keydown', function (e) {
        if (!frameTabs.length) return;
        if (e.key === 'ArrowLeft' && prevBtn) prevBtn.click();
        if (e.key === 'ArrowRight' && nextBtn) nextBtn.click();
    });

    // 화면 수정
    document.querySelectorAll('.sb-edit-frame').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var frame = JSON.parse(btn.getAttribute('data-frame'));
            document.getElementById('frameModalTitle').textContent = '화면 수정';
            document.getElementById('frameAction').value = 'update_frame';
            document.getElementById('frameId').value = frame.id;
            document.getElementById('frameTitle').value = frame.title;
            document.getElementById('frameDesc').value = frame.description || '';
            document.getElementById('frameNotes').value = frame.notes || '';
            document.getElementById('frameModal').classList.add('active');
        });
    });

    // 의견/이력 패널 토글
    var collabWrap = document.getElementById('sbCollabWrap');
    var collabToggle = document.getElementById('sbCollabToggle') || document.querySelector('.sb-collab-toggle-btn');
    if (collabToggle && collabWrap) {
        collabToggle.addEventListener('click', function () {
            collabWrap.classList.toggle('sb-collab-collapsed');
        });
    }

    // 의견/이력 — 현재 화면만 필터
    var filterCurrent = document.getElementById('sbFilterCurrent');
    if (filterCurrent) {
        filterCurrent.addEventListener('change', function () {
            var activeTab = document.querySelector('.sb-frame-tab.active');
            var fid = activeTab ? activeTab.getAttribute('data-frame-id') : (document.getElementById('commentFrameId') || {}).value;
            applyCollabFilter(fid || '0');
        });
    }

    // 의견 태그 클릭 → 해당 화면으로 이동
    document.querySelectorAll('.sb-comment-frame-tag').forEach(function (tag) {
        tag.style.cursor = 'pointer';
        tag.addEventListener('click', function () {
            var fid = tag.getAttribute('data-frame-id');
            if (fid && document.querySelector('.sb-frame-tab[data-frame-id="' + fid + '"]')) {
                showFrame(fid);
            }
        });
    });

    // 초기 필터 (활성 화면 기준)
    var initialTab = document.querySelector('.sb-frame-tab.active');
    if (initialTab) {
        applyCollabFilter(initialTab.getAttribute('data-frame-id'));
    }
});
