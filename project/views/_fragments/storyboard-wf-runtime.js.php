<script>
(function () {
    var wrap = document.getElementById('sbWfWrap');
    var viewport = document.getElementById('sbWfViewport');
    var fsBarTitle = document.getElementById('sbWfFsBarTitle');
    var currentMenuId = parseInt(wrap.dataset.initialMenuId || '0', 10);
    var fragmentUrl = wrap.dataset.fragmentUrl || '';
    var fragmentLoading = false;
    var selectedZone = null;
    var btnFs = document.getElementById('sbWfFullscreen');
    var btnExit = document.getElementById('sbWfExitFullscreen');
    var btnAnnotate = document.getElementById('sbWfToggleAnnotate');
    var infoPanel = document.getElementById('sbWfInfoPanel');
    var infoId = document.getElementById('sbWfInfoId');
    var infoType = document.getElementById('sbWfInfoType');
    var infoBody = document.getElementById('sbWfInfoBody');
    var infoClose = document.getElementById('sbWfInfoClose');
    if (!wrap) return;

    var zoneData = <?= json_encode(isset($sbZoneDataMap) ? $sbZoneDataMap : array(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;

    function getRoot() {
        return document.getElementById('sbWfRoot');
    }

    function updateFsBarTitle(title, code) {
        if (!fsBarTitle) return;
        var hint = '<span class="sb-wf-fs-bar-hint">· ☰ 메뉴 · 영역 ID 클릭</span>';
        fsBarTitle.innerHTML = escHtml(title) + (code ? ' (' + escHtml(code) + ')' : '') + ' — 와이어프레임 (전체화면)' + hint;
    }

    function updateMenuActive(menuId) {
        if (!menuTreeEl) return;
        menuTreeEl.querySelectorAll('.sb-wf-fs-menu-link').forEach(function (a) {
            a.classList.toggle('is-active', parseInt(a.dataset.menuId, 10) === menuId);
        });
        if (menuDataEl) {
            try {
                var data = JSON.parse(menuDataEl.textContent);
                (function walk(nodes) {
                    nodes.forEach(function (n) {
                        n.active = (n.id === menuId);
                        if (n.children) walk(n.children);
                    });
                })(data);
                menuDataEl.textContent = JSON.stringify(data);
            } catch (err) { /* ignore */ }
        }
    }

    function applyFragmentZoneData() {
        if (!viewport) return;
        var el = viewport.querySelector('script.sb-wf-zone-data');
        if (!el) return;
        try {
            zoneData = JSON.parse(el.textContent);
        } catch (err) { /* keep existing zoneData */ }
        el.remove();
    }

    function sbExecuteScripts(container) {
        if (!container) return;
        var scripts = Array.prototype.slice.call(container.querySelectorAll('script'));
        scripts.forEach(function (oldScript) {
            var script = document.createElement('script');
            Array.prototype.forEach.call(oldScript.attributes, function (attr) {
                script.setAttribute(attr.name, attr.value);
            });
            script.textContent = oldScript.textContent;
            if (oldScript.parentNode) {
                oldScript.parentNode.replaceChild(script, oldScript);
            } else {
                document.head.appendChild(script);
            }
        });
    }

    function sbInitEditorInViewport() {
        if (!viewport) return;
        if (!viewport.querySelector('[data-sb-editor-root]')) return;
        if (window.SbEditorInteractive) {
            window.SbEditorInteractive.bindAll();
        }
        if (window.SbEditorPrototype) {
            window.SbEditorPrototype.bindAll();
        }
    }

    function loadFragment(menuId, skipHistory) {
        if (!viewport || !fragmentUrl || fragmentLoading) return;
        menuId = parseInt(menuId, 10);
        if (!menuId) return;
        if (menuId === currentMenuId) {
            closeFsMenu();
            return;
        }

        fragmentLoading = true;
        viewport.classList.add('is-loading');
        closeZoneInfo();
        closeFsMenu();

        fetch(fragmentUrl + '?menu_id=' + menuId, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.ok) throw new Error(res.error || '로드 실패');
                viewport.innerHTML = res.html;
                applyFragmentZoneData();
                sbExecuteScripts(viewport);
                sbInitEditorInViewport();
                currentMenuId = res.menuId;
                updateFsBarTitle(res.title, res.code);
                updateMenuActive(res.menuId);
                if (!skipHistory && res.pageUrl) {
                    history.pushState({ menuId: res.menuId, fs: true }, '', res.pageUrl);
                }
            })
            .catch(function (err) {
                alert(err.message || '스토리보드를 불러오지 못했습니다.');
            })
            .finally(function () {
                fragmentLoading = false;
                viewport.classList.remove('is-loading');
            });
    }

    window.addEventListener('popstate', function (e) {
        if (!isFs()) return;
        var id = (e.state && e.state.menuId) ? e.state.menuId : parseInt(wrap.dataset.initialMenuId || '0', 10);
        if (id && id !== currentMenuId) loadFragment(id, true);
    });

    function enterFs() {
        var el = wrap;
        if (el.requestFullscreen) el.requestFullscreen();
        else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
    }
    function exitFs() {
        if (document.exitFullscreen) document.exitFullscreen();
        else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
    }
    function isFs() {
        return !!(document.fullscreenElement || document.webkitFullscreenElement);
    }
    function updateBtn() {
        if (btnFs) btnFs.textContent = isFs() ? '⛶ 전체화면 종료' : '⛶ 전체화면 보기';
        if (!isFs()) {
            closeZoneInfo();
            closeFsMenu();
        }
    }

    function escHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function renderZoneInfo(id) {
        var d = zoneData[id];
        if (!d || !infoPanel) return;

        infoId.textContent = id;
        infoType.textContent = d.type;
        infoType.className = 'sb-wf-info-type sb-wf-info-type--' + d.typeKey;

        var html = '';
        html += '<dl class="sb-wf-info-row"><dt>블록</dt><dd>' + escHtml(d.block) + '</dd></dl>';
        html += '<dl class="sb-wf-info-row"><dt>포함 요소</dt><dd>' + escHtml(d.elements) + '</dd></dl>';
        html += '<dl class="sb-wf-info-row"><dt>연결 메뉴</dt><dd><code>' + escHtml(d.menu) + '</code></dd></dl>';
        if (d.ux) {
            html += '<dl class="sb-wf-info-row"><dt>UX / 인터랙션</dt><dd><div class="sb-wf-info-ux">' + escHtml(d.ux) + '</div></dd></dl>';
        }
        infoBody.innerHTML = html;
        infoPanel.classList.add('is-open');
    }

    function selectZone(zoneEl, id) {
        if (!isFs() || !id || !zoneData[id]) return;
        if (selectedZone) selectedZone.classList.remove('is-selected');
        selectedZone = zoneEl;
        selectedZone.classList.add('is-selected');
        renderZoneInfo(id);
    }

    function closeZoneInfo() {
        if (infoPanel) infoPanel.classList.remove('is-open');
        if (selectedZone) {
            selectedZone.classList.remove('is-selected');
            selectedZone = null;
        }
    }

    function findZone(el) {
        while (el && el !== wrap) {
            if (el.dataset && el.dataset.zoneId) return el;
            el = el.parentElement;
        }
        return null;
    }

    if (btnFs) btnFs.addEventListener('click', function () { isFs() ? exitFs() : enterFs(); });
    if (btnExit) btnExit.addEventListener('click', exitFs);
    if (infoClose) infoClose.addEventListener('click', closeZoneInfo);

    document.addEventListener('fullscreenchange', updateBtn);
    document.addEventListener('webkitfullscreenchange', updateBtn);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'f' && e.altKey) { e.preventDefault(); isFs() ? exitFs() : enterFs(); }
        if (e.key === 'Escape' && isFs()) {
            if (menuOverlay && menuOverlay.classList.contains('is-open')) {
                e.preventDefault();
                closeFsMenu();
            } else if (infoPanel && infoPanel.classList.contains('is-open')) {
                e.preventDefault();
                closeZoneInfo();
            }
        }
    });

    wrap.addEventListener('click', function (e) {
        if (!isFs()) return;
        var root = getRoot();
        if (!root || !root.contains(e.target)) return;
        /* 프로토타입 모드: 영역 설명 패널(우측 인포) 열지 않음 */
        if (e.target.closest('.sb-hifi-editor--prototype')) return;
        if (e.target.closest('[data-sb-props-panel], [data-sb-props-drag-handle], [data-sb-props-minimize], [data-sb-preview-panel], [data-sb-preview-drag-handle], [data-sb-preview-minimize], [data-sb-asset-slide], [data-sb-asset-tool]')) return;
        if (e.target.closest('[data-sb-layer], [data-sb-action]')) return;
        var label = e.target.closest('.sb-wf-zone-label');
        if (label) {
            e.preventDefault();
            e.stopPropagation();
            var zone = findZone(label);
            if (zone) selectZone(zone, zone.dataset.zoneId);
            return;
        }
        var zone = findZone(e.target);
        if (zone && e.target.closest('[data-zone-id]') === zone) {
            selectZone(zone, zone.dataset.zoneId);
        }
    });

    if (btnAnnotate) {
        btnAnnotate.addEventListener('click', function () {
            var root = getRoot();
            if (!root) return;
            root.classList.toggle('sb-wf-annotate');
            btnAnnotate.classList.toggle('sb-front-btn--primary');
            btnAnnotate.textContent = root.classList.contains('sb-wf-annotate') ? '📌 영역 표시 ON' : '📌 영역 표시';
        });
    }

    var menuBtn = document.getElementById('sbWfFsMenuBtn');
    var menuOverlay = document.getElementById('sbWfFsMenuOverlay');
    var menuClose = document.getElementById('sbWfFsMenuClose');
    var menuTreeEl = document.getElementById('sbWfFsMenuTree');
    var menuDataEl = document.getElementById('sbWfFsMenuData');
    var fsMenuBuilt = false;

    function statusLabel(status) {
        if (status === 'ready') return 'SB';
        if (status === 'stub') return '준비중';
        return '미작성';
    }

    function renderMenuNodes(nodes) {
        var ul = document.createElement('ul');
        ul.className = 'sb-wf-fs-menu-list';
        nodes.forEach(function (n) {
            var li = document.createElement('li');
            var a = document.createElement('a');
            a.className = 'sb-wf-fs-menu-link is-' + n.status + (n.active ? ' is-active' : '');
            a.href = '#';
            a.dataset.menuId = String(n.id);
            if (n.code) {
                var code = document.createElement('span');
                code.className = 'sb-wf-fs-menu-code';
                code.textContent = n.code;
                a.appendChild(code);
            }
            var label = document.createElement('span');
            label.className = 'sb-wf-fs-menu-label';
            label.textContent = n.title;
            a.appendChild(label);
            var badge = document.createElement('span');
            badge.className = 'sb-wf-fs-menu-badge sb-wf-fs-menu-badge--' + n.status;
            badge.textContent = statusLabel(n.status);
            a.appendChild(badge);
            li.appendChild(a);
            if (n.children && n.children.length) {
                li.appendChild(renderMenuNodes(n.children));
            }
            ul.appendChild(li);
        });
        return ul;
    }

    function buildFsMenu() {
        if (fsMenuBuilt || !menuTreeEl || !menuDataEl) return;
        try {
            var data = JSON.parse(menuDataEl.textContent);
            menuTreeEl.innerHTML = '';
            menuTreeEl.appendChild(renderMenuNodes(data));
            fsMenuBuilt = true;
        } catch (err) { /* ignore */ }
    }

    function openFsMenu() {
        if (!isFs() || !menuOverlay) return;
        buildFsMenu();
        menuOverlay.classList.add('is-open');
        menuOverlay.setAttribute('aria-hidden', 'false');
        closeZoneInfo();
    }

    function closeFsMenu() {
        if (!menuOverlay) return;
        menuOverlay.classList.remove('is-open');
        menuOverlay.setAttribute('aria-hidden', 'true');
    }

    if (menuTreeEl) {
        menuTreeEl.addEventListener('click', function (e) {
            var link = e.target.closest('.sb-wf-fs-menu-link');
            if (!link) return;
            e.preventDefault();
            if (!isFs()) return;
            loadFragment(link.dataset.menuId);
        });
    }
    if (menuBtn) menuBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (menuOverlay && menuOverlay.classList.contains('is-open')) closeFsMenu();
        else openFsMenu();
    });
    if (menuClose) menuClose.addEventListener('click', closeFsMenu);
    if (menuOverlay) {
        menuOverlay.addEventListener('click', function (e) {
            if (e.target === menuOverlay) closeFsMenu();
        });
        var menuPanel = menuOverlay.querySelector('.sb-wf-fs-menu-panel');
        if (menuPanel) menuPanel.addEventListener('click', function (e) { e.stopPropagation(); });
    }

    sbInitEditorInViewport();
})();
</script>
