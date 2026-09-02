<script>
(function () {
    if (window.__sbEditorInteractiveBound) return;
    window.__sbEditorInteractiveBound = true;

    function findEditorRoot(el) {
        return el && el.closest ? el.closest('[data-sb-editor-root]') : null;
    }

    function setRailNavActive(root, layerName) {
        root.querySelectorAll('[data-sb-layer]').forEach(function (btn) {
            var isNav = btn.classList.contains('sb-hifi-editor__rail-nav-btn');
            var isFab = btn.classList.contains('sb-hifi-editor__ai-fab');
            var isRail = btn.classList.contains('sb-hifi-editor__rail-btn');
            if (!isNav && !isFab && !isRail) return;
            var name = btn.getAttribute('data-sb-layer');
            btn.classList.toggle('is-active', !!layerName && name === layerName && (isNav || isFab));
        });
    }

    function openLayer(root, layerName) {
        var overlay = root.querySelector('[data-sb-layer-overlay]');
        if (!overlay || !layerName) return;
        closePreview(root);
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        overlay.querySelectorAll('[data-sb-layer-panel]').forEach(function (panel) {
            var show = panel.getAttribute('data-sb-layer-panel') === layerName;
            panel.hidden = !show;
        });
        setRailNavActive(root, layerName);
    }

    function closeLayer(root) {
        var overlay = root.querySelector('[data-sb-layer-overlay]');
        if (!overlay) return;
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        overlay.querySelectorAll('[data-sb-layer-panel]').forEach(function (panel) {
            panel.hidden = true;
        });
        setRailNavActive(root, null);
    }

    function toggleLayer(root, layerName) {
        var overlay = root.querySelector('[data-sb-layer-overlay]');
        if (!overlay) return;
        var isOpen = overlay.classList.contains('is-open');
        var activePanel = overlay.querySelector('[data-sb-layer-panel]:not([hidden])');
        var activeName = activePanel ? activePanel.getAttribute('data-sb-layer-panel') : null;
        if (isOpen && activeName === layerName) {
            closeLayer(root);
        } else {
            openLayer(root, layerName);
        }
    }

    function openPreview(root) {
        var overlay = root.querySelector('[data-sb-preview-overlay]');
        if (!overlay) return;
        closeLayer(root);
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function closePreview(root) {
        var overlay = root.querySelector('[data-sb-preview-overlay]');
        if (!overlay) return;
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
    }

    document.addEventListener('click', function (e) {
        var root = findEditorRoot(e.target);
        if (!root) return;

        var actionEl = e.target.closest('[data-sb-action]');
        if (actionEl) {
            var action = actionEl.getAttribute('data-sb-action');
            if (action === 'preview') {
                e.preventDefault();
                e.stopPropagation();
                openPreview(root);
                return;
            }
            if (action === 'close-preview') {
                e.preventDefault();
                e.stopPropagation();
                closePreview(root);
                return;
            }
            if (action === 'close-layer') {
                e.preventDefault();
                e.stopPropagation();
                closeLayer(root);
                return;
            }
            if (action === 'pick-spec' || action === 'pick-template') {
                e.preventDefault();
                e.stopPropagation();
                closeLayer(root);
                return;
            }
        }

        var layerEl = e.target.closest('[data-sb-layer]');
        if (layerEl) {
            var layerName = layerEl.getAttribute('data-sb-layer');
            if (layerName) {
                e.preventDefault();
                e.stopPropagation();
                toggleLayer(root, layerName);
                return;
            }
        }

        var layerOverlay = root.querySelector('[data-sb-layer-overlay]');
        if (layerOverlay && layerOverlay.classList.contains('is-open') && e.target === layerOverlay) {
            e.preventDefault();
            e.stopPropagation();
            closeLayer(root);
        }

        var previewOverlay = root.querySelector('[data-sb-preview-overlay]');
        if (previewOverlay && previewOverlay.classList.contains('is-open') && e.target === previewOverlay) {
            e.preventDefault();
            e.stopPropagation();
            closePreview(root);
        }
    }, true);

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var openLayerOverlay = document.querySelector('[data-sb-layer-overlay].is-open');
        if (openLayerOverlay) {
            var root = findEditorRoot(openLayerOverlay);
            if (root) closeLayer(root);
            return;
        }
        var openPreviewOverlay = document.querySelector('[data-sb-preview-overlay].is-open');
        if (openPreviewOverlay) {
            var root2 = findEditorRoot(openPreviewOverlay);
            if (root2) closePreview(root2);
        }
    });
})();
</script>
