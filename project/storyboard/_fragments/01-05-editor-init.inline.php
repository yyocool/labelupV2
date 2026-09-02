<script data-sb-editor-init>
(function (window, document) {
    'use strict';

    function editorRoot(el) {
        return el && el.closest ? el.closest('[data-sb-editor-root]') : null;
    }

    function setNavActive(root, layerName) {
        if (!root) return;
        root.querySelectorAll('[data-sb-layer]').forEach(function (btn) {
            var isNav = btn.classList.contains('sb-hifi-editor__rail-nav-btn');
            var isFab = btn.classList.contains('sb-hifi-editor__ai-fab');
            if (!isNav && !isFab && !btn.classList.contains('sb-hifi-editor__rail-btn')) return;
            var name = btn.getAttribute('data-sb-layer');
            btn.classList.toggle('is-active', !!layerName && name === layerName && (isNav || isFab));
        });
    }

    function closePreview(root) {
        var overlay = root.querySelector('[data-sb-preview-overlay]');
        if (!overlay) return;
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
    }

    window.sbEdCloseLayer = function (el) {
        var root = editorRoot(el);
        if (!root) return false;
        var overlay = root.querySelector('[data-sb-layer-overlay]');
        if (!overlay) return false;
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        overlay.querySelectorAll('[data-sb-layer-panel]').forEach(function (panel) {
            panel.hidden = true;
        });
        setNavActive(root, null);
        return false;
    };

    window.sbEdOpenLayer = function (btn, layerName) {
        var root = editorRoot(btn);
        if (!root || !layerName) return false;
        if (!root.classList.contains('sb-hifi-editor--prototype')) {
            window.SbEditorPrototype && window.SbEditorPrototype.enable(root);
        }
        var overlay = root.querySelector('[data-sb-layer-overlay]');
        if (!overlay) return false;

        closePreview(root);
        closeImport(root);

        var activePanel = overlay.querySelector('[data-sb-layer-panel]:not([hidden])');
        var activeName = activePanel ? activePanel.getAttribute('data-sb-layer-panel') : null;
        if (overlay.classList.contains('is-open') && activeName === layerName) {
            return window.sbEdCloseLayer(btn);
        }

        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        overlay.querySelectorAll('[data-sb-layer-panel]').forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-sb-layer-panel') !== layerName;
        });
        setNavActive(root, layerName);
        return false;
    };

    window.sbEdOpenPreview = function (btn) {
        var root = editorRoot(btn);
        if (!root) return false;
        if (!root.classList.contains('sb-hifi-editor--prototype')) {
            window.SbEditorPrototype && window.SbEditorPrototype.enable(root);
        }
        window.sbEdCloseLayer(btn);
        closeImport(root);
        var overlay = root.querySelector('[data-sb-preview-overlay]');
        if (!overlay) return false;
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        return false;
    };

    window.sbEdClosePreview = function (el) {
        var root = editorRoot(el);
        if (!root) return false;
        closePreview(root);
        return false;
    };

    function closeImport(root) {
        var overlay = root.querySelector('[data-sb-import-overlay]');
        if (!overlay) return;
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
    }

    function closeDataImport(root) {
        var overlay = root.querySelector('[data-sb-data-import-overlay]');
        if (!overlay) return;
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
    }

    function switchDataImportTab(root, tabName) {
        root.querySelectorAll('[data-sb-data-import-tab]').forEach(function (tab) {
            var name = tab.getAttribute('data-sb-data-import-tab');
            var active = name === tabName;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        root.querySelectorAll('[data-sb-data-import-panel]').forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-sb-data-import-panel') !== tabName;
        });
        root._sbDataImportTab = tabName;
    }

    function getActiveDataImportPanel(root) {
        var tab = root._sbDataImportTab || 'excel';
        return root.querySelector('[data-sb-data-import-panel="' + tab + '"]');
    }

    function resetDataImportFiles(root) {
        root.querySelectorAll('[data-sb-data-import-drop]').forEach(function (zone) {
            zone.classList.remove('has-file', 'is-dragover');
            var input = zone.querySelector('.sb-ed-data-import__file-input');
            if (input) input.value = '';
            var nameEl = zone.querySelector('[data-sb-data-import-filename]');
            if (nameEl) {
                nameEl.textContent = '';
                nameEl.hidden = true;
            }
        });
    }

    window.sbEdOpenDataImport = function (btn) {
        var root = editorRoot(btn);
        if (!root) return false;
        if (!root.classList.contains('sb-hifi-editor--prototype')) {
            window.SbEditorPrototype && window.SbEditorPrototype.enable(root);
        }
        window.sbEdCloseLayer(btn);
        closePreview(root);
        closeImport(root);
        if (root._sbAssetSlide && root._sbAssetSlide.close) {
            root._sbAssetSlide.close();
        }
        var overlay = root.querySelector('[data-sb-data-import-overlay]');
        if (!overlay) return false;
        resetDataImportFiles(root);
        switchDataImportTab(root, 'excel');
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        return false;
    };

    window.sbEdCloseDataImport = function (el) {
        var root = editorRoot(el);
        if (!root) return false;
        closeDataImport(root);
        return false;
    };

    function switchImportTab(root, tabName) {
        root.querySelectorAll('[data-sb-import-tab]').forEach(function (tab) {
            var name = tab.getAttribute('data-sb-import-tab');
            var active = name === tabName;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        root.querySelectorAll('[data-sb-import-panel]').forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-sb-import-panel') !== tabName;
        });
    }

    function getImportCatalogState(root, kind) {
        var panel = root.querySelector('[data-sb-import-panel="' + kind + '"]');
        if (!panel) return null;
        var catBtn = panel.querySelector('[data-sb-import-cat].is-active');
        var subBtn = panel.querySelector('[data-sb-import-subtype].is-active');
        return {
            panel: panel,
            cat: catBtn ? catBtn.getAttribute('data-sb-import-cat') : 'a4',
            subtype: subBtn ? subBtn.getAttribute('data-sb-import-subtype') : 'blank',
            count: catBtn ? parseInt(catBtn.getAttribute('data-sb-import-count') || '0', 10) : 0
        };
    }

    function switchImportCatalogGrid(root, kind) {
        var state = getImportCatalogState(root, kind);
        if (!state) return;
        var gridKey = kind + '-' + state.cat + '-' + state.subtype;
        state.panel.querySelectorAll('[data-sb-import-grid]').forEach(function (grid) {
            grid.hidden = grid.getAttribute('data-sb-import-grid') !== gridKey;
        });
        var countEl = state.panel.querySelector('[data-sb-import-count-label]');
        if (countEl) {
            var suffix = state.subtype === 'design' ? '디자인 검색' : '규격 검색';
            countEl.textContent = state.count + ' ' + suffix;
        }
    }

    function switchImportCatalogCat(root, kind, catId) {
        var panel = root.querySelector('[data-sb-import-panel="' + kind + '"]');
        if (!panel) return;
        panel.querySelectorAll('[data-sb-import-cat]').forEach(function (btn) {
            var active = btn.getAttribute('data-sb-import-cat') === catId;
            btn.classList.toggle('is-active', active);
        });
        switchImportCatalogGrid(root, kind);
    }

    function switchImportCatalogSubtype(root, kind, subtype) {
        var panel = root.querySelector('[data-sb-import-panel="' + kind + '"]');
        if (!panel) return;
        panel.querySelectorAll('[data-sb-import-subtype]').forEach(function (btn) {
            var active = btn.getAttribute('data-sb-import-subtype') === subtype;
            btn.classList.toggle('is-active', active);
        });
        switchImportCatalogGrid(root, kind);
    }

    var importSpecMapCache = null;

    function getImportSpecMap() {
        if (importSpecMapCache) return importSpecMapCache;
        var el = document.getElementById('sb-import-spec-map');
        if (!el) return {};
        try {
            importSpecMapCache = JSON.parse(el.textContent || '{}');
        } catch (err) {
            importSpecMapCache = {};
        }
        return importSpecMapCache;
    }

    function formatSpecNum(n) {
        var v = parseFloat(n);
        if (isNaN(v)) return '0';
        return (Math.round(v * 100) / 100).toString().replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1');
    }

    function buildSpecDiagramSvg(d) {
        var pw = d.paperW;
        var ph = d.paperH;
        var pad = 14;
        var viewW = 280;
        var viewH = 300;
        var scale = Math.min((viewW - pad * 2) / pw, (viewH - pad * 2 - 18) / ph);
        var ox = (viewW - pw * scale) / 2;
        var oy = pad;
        var lw = d.labelW * scale;
        var lh = d.labelH * scale;
        var mh = d.marginH * scale;
        var mt = d.marginT * scale;
        var gh = (d.gapH || 0) * scale;
        var gv = (d.gapV || 0) * scale;
        var parts = [];
        parts.push('<svg viewBox="0 0 ' + viewW + ' ' + viewH + '" class="sb-ed-spec-detail__svg" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">');
        parts.push('<rect x="' + ox + '" y="' + oy + '" width="' + (pw * scale) + '" height="' + (ph * scale) + '" fill="#fff" stroke="#cbd5e1" stroke-width="1.5" rx="4"/>');
        for (var r = 0; r < d.rows; r++) {
            for (var c = 0; c < d.cols; c++) {
                var x = ox + mh + c * (lw + gh);
                var y = oy + mt + r * (lh + gv);
                var rot = d.rotation || 0;
                if (rot) {
                    var cx = x + lw / 2;
                    var cy = y + lh / 2;
                    parts.push('<rect x="' + x + '" y="' + y + '" width="' + lw + '" height="' + lh + '" fill="#f8fafc" stroke="#94a3b8" stroke-width="1" rx="2" transform="rotate(' + rot + ' ' + cx + ' ' + cy + ')"/>');
                } else if (d.shape === 'circle') {
                    parts.push('<ellipse cx="' + (x + lw / 2) + '" cy="' + (y + lh / 2) + '" rx="' + (lw / 2) + '" ry="' + (lh / 2) + '" fill="#f8fafc" stroke="#94a3b8" stroke-width="1"/>');
                } else {
                    parts.push('<rect x="' + x + '" y="' + y + '" width="' + lw + '" height="' + lh + '" fill="#f8fafc" stroke="#94a3b8" stroke-width="1" rx="2"/>');
                }
            }
        }
        parts.push('<text x="' + (ox + pw * scale / 2) + '" y="' + (oy + ph * scale / 2) + '" text-anchor="middle" dominant-baseline="middle" fill="#e2e8f0" font-size="42" font-weight="800" font-family="Segoe UI,sans-serif">' + d.qty + '</text>');
        if (d.rotation) {
            parts.push('<text x="' + ox + '" y="' + (oy - 4) + '" fill="#6366f1" font-size="8" font-family="Segoe UI,sans-serif">Rotation Angle: ' + d.rotation + '°</text>');
        }
        parts.push('<text x="' + (ox + pw * scale / 2) + '" y="' + (oy + ph * scale + 14) + '" text-anchor="middle" fill="#64748b" font-size="9" font-family="Segoe UI,sans-serif">' + formatSpecNum(d.paperW) + ' × ' + formatSpecNum(d.paperH) + ' mm</text>');
        parts.push('</svg>');
        return parts.join('');
    }

    function getEditorFileName(root) {
        var el = root.querySelector('[data-sb-editor-file-name]');
        return el ? String(el.value || '').trim() : '';
    }

    function setEditorFileName(root, name) {
        var el = root.querySelector('[data-sb-editor-file-name]');
        if (!el) return;
        el.value = name;
        syncEditorPreviewTitle(root);
    }

    function syncEditorPreviewTitle(root) {
        var titleEl = root.querySelector('[data-sb-editor-preview-title]');
        if (!titleEl) return;
        var name = getEditorFileName(root) || '제목 없음';
        titleEl.textContent = name + ' 프리뷰';
    }

    function setEditorSaveStatus(root, state) {
        var statusEl = root.querySelector('[data-sb-editor-save-status]');
        if (!statusEl) return;
        statusEl.classList.remove('is-saved', 'is-unsaved', 'is-saving');
        if (state === 'saved') {
            statusEl.classList.add('is-saved');
            statusEl.textContent = '● 저장됨';
        } else if (state === 'saving') {
            statusEl.classList.add('is-saving');
            statusEl.textContent = '● 저장 중…';
        } else {
            statusEl.classList.add('is-unsaved');
            statusEl.textContent = '● 저장 필요';
        }
    }

    function markEditorUnsaved(root) {
        setEditorSaveStatus(root, 'unsaved');
    }

    function saveEditorDesign(root, silent) {
        var name = getEditorFileName(root) || '제목 없음';
        setEditorSaveStatus(root, 'saving');
        setTimeout(function () {
            setEditorSaveStatus(root, 'saved');
            if (!silent && window.SbEditorPrototype) {
                window.SbEditorPrototype.toast(root, '저장됨: ' + name);
                window.SbEditorPrototype.pushHistory(root, '저장');
            }
        }, 320);
    }

    function bindTopbar(root) {
        var nameInput = root.querySelector('[data-sb-editor-file-name]');
        var saveBtn = root.querySelector('[data-sb-editor-save]');

        if (nameInput) {
            nameInput.addEventListener('input', function () {
                markEditorUnsaved(root);
            });
            nameInput.addEventListener('change', function () {
                syncEditorPreviewTitle(root);
            });
            nameInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    nameInput.blur();
                }
            });
        }

        if (saveBtn) {
            saveBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                saveEditorDesign(root, false);
            });
        }
    }

    function closeSpecDetail(root) {
        var overlay = root.querySelector('[data-sb-spec-detail-overlay]');
        if (!overlay) return;
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        root._sbSpecDetailCurrent = null;
    }

    function applySpecToEditor(root, meta) {
        setEditorFileName(root, meta.code + ' · ' + meta.size);
        markEditorUnsaved(root);
        closeImport(root);
        closeSpecDetail(root);
        if (window.SbEditorPrototype) {
            window.SbEditorPrototype.toast(root, (meta.kind === 'tag' ? '태그' : '라벨') + ' 편집 시작: ' + meta.code);
            window.SbEditorPrototype.pushHistory(root, '규격 편집 시작');
        }
    }

    function openSpecDetail(root, spec, meta) {
        var overlay = root.querySelector('[data-sb-spec-detail-overlay]');
        if (!overlay || !spec) return;
        root._sbSpecDetailCurrent = { spec: spec, meta: meta };

        var titleEl = overlay.querySelector('[data-sb-spec-detail-title]');
        if (titleEl) titleEl.textContent = meta.code + ' 상세 사양';

        var diagramEl = overlay.querySelector('[data-sb-spec-detail-diagram]');
        if (diagramEl) {
            diagramEl.innerHTML = buildSpecDiagramSvg(spec);
            diagramEl.setAttribute('aria-hidden', 'false');
        }

        var specsEl = overlay.querySelector('[data-sb-spec-detail-specs]');
        if (specsEl) {
            var orientVal = spec.orientation === 'landscape' ? 'landscape' : 'portrait';
            var rows = [
                ['용지 너비 × 높이', formatSpecNum(spec.paperW) + ' × ' + formatSpecNum(spec.paperH) + ' mm'],
                ['라벨 너비 × 높이', formatSpecNum(spec.labelW) + ' × ' + formatSpecNum(spec.labelH) + ' mm'],
                ['열 × 행', spec.cols + ' × ' + spec.rows + ' 개'],
                ['장당 라벨 수', spec.qty + '개'],
                ['상하 간격', formatSpecNum(spec.gapV) + 'mm'],
                ['좌우 간격', formatSpecNum(spec.gapH) + 'mm'],
                ['용지 좌우 여백', formatSpecNum(spec.marginH) + 'mm'],
                ['용지 상단 여백', formatSpecNum(spec.marginT) + 'mm']
            ];
            specsEl.innerHTML = rows.map(function (row) {
                return '<div class="sb-ed-spec-detail__row"><dt>' + row[0] + '</dt><dd>' + row[1] + '</dd></div>';
            }).join('') +
            '<div class="sb-ed-spec-detail__row"><dt>용지 방향</dt><dd>' +
            '<select class="sb-ed-spec-detail__select" data-sb-spec-detail-orient>' +
            '<option value="portrait"' + (orientVal === 'portrait' ? ' selected' : '') + '>세로</option>' +
            '<option value="landscape"' + (orientVal === 'landscape' ? ' selected' : '') + '>가로</option>' +
            '</select></dd></div>';
        }

        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function bindSpecDetailRoot(root) {
        var overlay = root.querySelector('[data-sb-spec-detail-overlay]');
        if (!overlay || overlay.getAttribute('data-sb-spec-detail-bound') === '1') return;
        overlay.setAttribute('data-sb-spec-detail-bound', '1');

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                e.preventDefault();
                closeSpecDetail(root);
            }
        });

        overlay.querySelectorAll('[data-sb-spec-detail-close]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                closeSpecDetail(root);
            });
        });

        overlay.querySelectorAll('[data-sb-spec-detail-action]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var action = btn.getAttribute('data-sb-spec-detail-action');
                var current = root._sbSpecDetailCurrent;
                if (!current) return;
                if (action === 'buy') {
                    if (window.SbEditorPrototype) {
                        window.SbEditorPrototype.toast(root, '「' + current.meta.code + '」용지 구매 페이지 (프로토타입)');
                    }
                    return;
                }
                if (action === 'edit') {
                    applySpecToEditor(root, current.meta);
                }
            });
        });
    }

    window.sbEdOpenImport = function (btn) {
        var root = editorRoot(btn);
        if (!root) return false;
        if (!root.classList.contains('sb-hifi-editor--prototype')) {
            window.SbEditorPrototype && window.SbEditorPrototype.enable(root);
        }
        window.sbEdCloseLayer(btn);
        closePreview(root);
        closeDataImport(root);
        var overlay = root.querySelector('[data-sb-import-overlay]');
        if (!overlay) return false;
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        switchImportTab(root, 'label');
        switchImportCatalogGrid(root, 'label');
        return false;
    };

    window.sbEdCloseImport = function (el) {
        var root = editorRoot(el);
        if (!root) return false;
        closeImport(root);
        return false;
    };

    function bindImportRoot(root) {
        var importOverlay = root.querySelector('[data-sb-import-overlay]');
        if (!importOverlay || importOverlay.getAttribute('data-sb-import-bound') === '1') return;
        importOverlay.setAttribute('data-sb-import-bound', '1');

        importOverlay.addEventListener('click', function (e) {
            if (e.target === importOverlay) {
                e.preventDefault();
                e.stopPropagation();
                closeImport(root);
            }
        });

        root.querySelectorAll('[data-sb-import-tab]').forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                var tabName = tab.getAttribute('data-sb-import-tab');
                switchImportTab(root, tabName);
                if (tabName === 'label' || tabName === 'tag') {
                    switchImportCatalogGrid(root, tabName);
                }
                if (root.classList.contains('sb-hifi-editor--prototype') && window.SbEditorPrototype) {
                    window.SbEditorPrototype.toast(root, '탭: ' + tab.textContent.trim());
                }
            });
        });

        root.querySelectorAll('[data-sb-import-cat]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var panel = btn.closest('[data-sb-import-catalog]');
                if (!panel) return;
                var kind = panel.getAttribute('data-sb-import-catalog');
                switchImportCatalogCat(root, kind, btn.getAttribute('data-sb-import-cat'));
                if (root.classList.contains('sb-hifi-editor--prototype') && window.SbEditorPrototype) {
                    window.SbEditorPrototype.toast(root, btn.textContent.trim());
                }
            });
        });

        root.querySelectorAll('[data-sb-import-subtype]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var panel = btn.closest('[data-sb-import-catalog]');
                if (!panel) return;
                var kind = panel.getAttribute('data-sb-import-catalog');
                switchImportCatalogSubtype(root, kind, btn.getAttribute('data-sb-import-subtype'));
                if (root.classList.contains('sb-hifi-editor--prototype') && window.SbEditorPrototype) {
                    window.SbEditorPrototype.toast(root, btn.textContent.trim());
                }
            });
        });

        root.querySelectorAll('[data-sb-import-htag]').forEach(function (htag) {
            htag.addEventListener('click', function (e) {
                e.preventDefault();
                var bar = htag.closest('.sb-ed-layer__tagbar');
                if (bar) bar.querySelectorAll('[data-sb-import-htag]').forEach(function (t) { t.classList.remove('is-active'); });
                htag.classList.add('is-active');
                if (root.classList.contains('sb-hifi-editor--prototype') && window.SbEditorPrototype) {
                    window.SbEditorPrototype.toast(root, '필터: ' + htag.textContent.trim());
                }
            });
        });

        root.querySelectorAll('[data-sb-import-reset]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (window.SbEditorPrototype) {
                    window.SbEditorPrototype.toast(root, '검색 조건을 초기화했습니다');
                }
            });
        });

        root.querySelectorAll('[data-sb-import-pick="spec"]').forEach(function (card) {
            card.addEventListener('click', function (e) {
                e.preventDefault();
                var codeEl = card.querySelector('.sb-ed-spec-card__meta strong');
                var sizeEl = card.querySelector('.sb-ed-spec-card__meta span');
                var code = card.getAttribute('data-spec-code') || (codeEl ? codeEl.textContent.trim() : '');
                var size = sizeEl ? sizeEl.textContent.trim() : '';
                var kind = card.getAttribute('data-sb-import-kind') || 'label';
                var map = getImportSpecMap();
                var spec = map[code] ? JSON.parse(JSON.stringify(map[code])) : null;
                if (!spec) {
                    spec = {
                        code: code, kind: kind, paperW: 210, paperH: 297,
                        labelW: 25, labelH: 25, cols: 1, rows: 1,
                        gapH: 2.5, gapV: 0, marginH: 4, marginT: 10,
                        qty: 1, orientation: 'portrait', rotation: 0
                    };
                }
                openSpecDetail(root, spec, { code: code, size: size, kind: kind });
            });
        });

        root.querySelectorAll('[data-sb-import-pick="design"]').forEach(function (card) {
            card.addEventListener('click', function (e) {
                e.preventDefault();
                var title = card.querySelector('.sb-ed-tpl-card__title, .sb-ed-import__design-meta strong, strong');
                var name = title ? title.textContent : '디자인';
                setEditorFileName(root, name);
                markEditorUnsaved(root);
                closeImport(root);
                if (window.SbEditorPrototype) {
                    window.SbEditorPrototype.toast(root, '「' + name + '」디자인을 불러왔습니다');
                    window.SbEditorPrototype.pushHistory(root, '디자인 가져오기');
                }
            });
        });

        root.querySelectorAll('[data-sb-import-pick="template"]').forEach(function (card) {
            card.addEventListener('click', function (e) {
                e.preventDefault();
                var title = card.querySelector('.sb-ed-tpl-card__title');
                var name = title ? title.textContent : '템플릿';
                closeImport(root);
                if (window.SbEditorPrototype) {
                    window.SbEditorPrototype.toast(root, '템플릿 적용: ' + name);
                    window.SbEditorPrototype.pushHistory(root, '템플릿 가져오기');
                }
            });
        });

        var promptEl = root.querySelector('[data-sb-import-prompt]');
        var promptCountEl = root.querySelector('[data-sb-import-prompt-count]');
        function updateImportPromptCount() {
            if (!promptEl || !promptCountEl) return;
            promptCountEl.textContent = (promptEl.value.length) + ' / 2000';
        }
        if (promptEl) {
            promptEl.addEventListener('input', updateImportPromptCount);
            updateImportPromptCount();
        }

        root.querySelectorAll('[data-sb-import-smart]').forEach(function (card) {
            card.addEventListener('click', function (e) {
                e.preventDefault();
                var mode = card.getAttribute('data-sb-import-smart');
                var Proto = window.SbEditorPrototype;
                if (mode === 'send') {
                    var text = promptEl ? promptEl.value.trim() : '';
                    if (!text) {
                        if (Proto) Proto.toast(root, '프롬프트를 입력해 주세요');
                        else alert('프롬프트를 입력해 주세요');
                        return;
                    }
                    closeImport(root);
                    if (Proto) {
                        Proto.toast(root, 'AI 라벨 생성 시작: ' + text.substring(0, 40) + (text.length > 40 ? '…' : ''));
                        Proto.pushHistory(root, '스마트라벨 생성');
                    }
                    return;
                }
                if (mode === 'image') {
                    if (Proto) Proto.modal(root, '이미지 붙여넣기', '<p>클립보드 이미지를 붙여넣거나 파일을 선택해 디자인을 생성합니다.</p>');
                    else alert('이미지 붙여넣기 (프로토타입)');
                    return;
                }
                if (mode === 'excel') {
                    if (Proto) Proto.modal(root, '엑셀 파일 업로드', '<p>CSV·엑셀 파일을 드래그하거나 선택해 일괄 라벨을 생성합니다.</p>');
                    else alert('엑셀 업로드 (프로토타입)');
                    return;
                }
                if (mode === 'examples') {
                    if (promptEl) {
                        promptEl.value = '유기농 올리브오일 라벨, 70x50mm, 자연 느낌, 밝은 색상, 올리브 일러스트';
                        updateImportPromptCount();
                        promptEl.focus();
                    }
                    if (Proto) Proto.toast(root, '프롬프트 예시가 입력되었습니다');
                    return;
                }
                if (mode === 'myfile') {
                    if (Proto) Proto.modal(root, '내 파일에서 시작', '<p>이전에 업로드한 이미지·엑셀·디자인 파일 목록에서 선택합니다.</p>');
                    else alert('내 파일 (프로토타입)');
                    return;
                }
                if (mode === 'more-examples') {
                    if (Proto) Proto.modal(root, '프롬프트 예시', '<ul><li>카페 테이크아웃 스티커, 50x30mm</li><li>친환경 제품 라벨, 미니멀</li><li>수제청 증정품, 파스텔 톤</li></ul>');
                    return;
                }
            });
        });

        root.querySelectorAll('[data-sb-import-example]').forEach(function (chip) {
            chip.addEventListener('click', function (e) {
                e.preventDefault();
                var text = chip.getAttribute('data-sb-import-example') || chip.textContent.trim();
                if (!promptEl) return;
                promptEl.value = promptEl.value ? (promptEl.value.trim() + ', ' + text) : text;
                updateImportPromptCount();
                promptEl.focus();
                if (window.SbEditorPrototype) {
                    window.SbEditorPrototype.toast(root, '예시 추가: ' + text);
                }
            });
        });

        root.querySelectorAll('.sb-ed-import__file-input').forEach(function (input) {
            input.addEventListener('change', function () {
                var zone = input.closest('.sb-ed-import__dropzone');
                if (!zone || !input.files || !input.files.length) return;
                var file = input.files[0];
                zone.classList.add('has-file');
                var nameEl = zone.querySelector('[data-sb-import-filename]');
                if (nameEl) {
                    nameEl.textContent = file.name;
                    nameEl.hidden = false;
                }
                if (window.SbEditorPrototype) {
                    var format = zone.getAttribute('data-sb-import-drop');
                    window.SbEditorPrototype.toast(root, '파일 선택: ' + file.name);
                }
            });
        });

        root.querySelectorAll('.sb-ed-import__dropzone').forEach(function (zone) {
            zone.addEventListener('dragover', function (e) {
                e.preventDefault();
                zone.classList.add('is-dragover');
            });
            zone.addEventListener('dragleave', function () {
                zone.classList.remove('is-dragover');
            });
            zone.addEventListener('drop', function (e) {
                e.preventDefault();
                zone.classList.remove('is-dragover');
                var input = zone.querySelector('.sb-ed-import__file-input');
                if (!input || !e.dataTransfer.files.length) return;
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    }

    function bindDataImportRoot(root) {
        var dataOverlay = root.querySelector('[data-sb-data-import-overlay]');
        if (!dataOverlay || dataOverlay.getAttribute('data-sb-data-import-bound') === '1') return;
        dataOverlay.setAttribute('data-sb-data-import-bound', '1');

        dataOverlay.addEventListener('click', function (e) {
            if (e.target === dataOverlay) {
                e.preventDefault();
                e.stopPropagation();
                closeDataImport(root);
            }
        });

        root.querySelectorAll('[data-sb-data-import-tab]').forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                var tabName = tab.getAttribute('data-sb-data-import-tab');
                if (!tabName) return;
                switchDataImportTab(root, tabName);
                if (window.SbEditorPrototype) {
                    window.SbEditorPrototype.toast(root, tab.textContent.trim() + ' 포맷 선택');
                }
            });
        });

        root.querySelectorAll('[data-sb-data-import-close]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                closeDataImport(root);
            });
        });

        root.querySelectorAll('.sb-ed-data-import__file-input').forEach(function (input) {
            input.addEventListener('change', function () {
                var zone = input.closest('.sb-ed-data-import__dropzone');
                if (!zone || !input.files || !input.files.length) return;
                var file = input.files[0];
                zone.classList.add('has-file');
                var nameEl = zone.querySelector('[data-sb-data-import-filename]');
                if (nameEl) {
                    nameEl.textContent = file.name;
                    nameEl.hidden = false;
                }
                if (window.SbEditorPrototype) {
                    var format = zone.getAttribute('data-sb-data-import-drop');
                    window.SbEditorPrototype.toast(root, '파일 선택: ' + file.name);
                }
            });
        });

        root.querySelectorAll('.sb-ed-data-import__dropzone').forEach(function (zone) {
            zone.addEventListener('dragover', function (e) {
                e.preventDefault();
                zone.classList.add('is-dragover');
            });
            zone.addEventListener('dragleave', function () {
                zone.classList.remove('is-dragover');
            });
            zone.addEventListener('drop', function (e) {
                e.preventDefault();
                zone.classList.remove('is-dragover');
                var input = zone.querySelector('.sb-ed-data-import__file-input');
                if (!input || !e.dataTransfer.files.length) return;
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        var submitBtn = root.querySelector('[data-sb-data-import-submit]');
        if (submitBtn) {
            submitBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var panel = getActiveDataImportPanel(root);
                if (!panel) return;
                var input = panel.querySelector('.sb-ed-data-import__file-input');
                var tab = root._sbDataImportTab || 'excel';
                var tabLabels = { excel: '엑셀', csv: 'CSV', ilabel: '아이라벨', formtec: '폼택' };
                var label = tabLabels[tab] || tab;
                if (!input || !input.files || !input.files.length) {
                    if (window.SbEditorPrototype) {
                        window.SbEditorPrototype.toast(root, label + ' 파일을 먼저 선택해 주세요');
                    }
                    return;
                }
                var file = input.files[0];
                if (window.SbEditorPrototype) {
                    window.SbEditorPrototype.toast(root, label + ' 데이터 가져오기: ' + file.name);
                    window.SbEditorPrototype.pushHistory(root, '데이터 연동');
                }
                closeDataImport(root);
            });
        }
    }

    var SB_SNAP_PAD = 12;
    var SB_SNAP_MAGNET = 14;
    var SB_SNAP_RELEASE = 22;
    var SB_SNAP_LABELS = {
        tl: '좌측 상단',
        tr: '우측 상단',
        bl: '좌측 하단',
        br: '우측 하단',
        'stack-props': '속성 패널 아래'
    };

    function sbSnapCornerTargets(container, el, pad) {
        pad = typeof pad === 'number' ? pad : SB_SNAP_PAD;
        var cw = container.clientWidth;
        var ch = container.clientHeight;
        var w = el.offsetWidth;
        var h = el.offsetHeight;
        return {
            tl: { left: pad, top: pad },
            tr: { left: Math.max(pad, cw - w - pad), top: pad },
            bl: { left: pad, top: Math.max(pad, ch - h - pad) },
            br: { left: Math.max(pad, cw - w - pad), top: Math.max(pad, ch - h - pad) }
        };
    }

    function sbSnapNearest(targets, el, left, top, maxDist) {
        var best = null;
        var min = Infinity;
        Object.keys(targets).forEach(function (id) {
            var t = targets[id];
            var dx = left - t.left;
            var dy = top - t.top;
            var d = Math.sqrt(dx * dx + dy * dy);
            if (d < min) {
                min = d;
                best = { id: id, left: t.left, top: t.top, dist: d };
            }
        });
        if (maxDist != null && best && best.dist > maxDist) return null;
        return best;
    }

    function sbSnapClamp(container, el, left, top, pad) {
        pad = typeof pad === 'number' ? pad : 8;
        var bw = container.clientWidth;
        var bh = container.clientHeight;
        var pw = el.offsetWidth;
        var ph = el.offsetHeight;
        return {
            left: Math.max(pad, Math.min(left, bw - pw - pad)),
            top: Math.max(pad, Math.min(top, bh - ph - pad))
        };
    }

    function sbPreviewStackTargets(root) {
        var body = root.querySelector('.sb-hifi-editor__body');
        var props = root.querySelector('[data-sb-props-panel]');
        if (!body || !props || props.classList.contains('is-minimized')) return {};
        var br = body.getBoundingClientRect();
        var pr = props.getBoundingClientRect();
        return {
            'stack-props': {
                left: pr.left - br.left,
                top: pr.bottom - br.top + 12
            }
        };
    }

    function bindFloatingTools(root) {
        var workspace = root.querySelector('.sb-hifi-editor__workspace');
        var wrap = root.querySelector('[data-sb-float-tools]');
        if (!workspace || !wrap || wrap.getAttribute('data-sb-float-bound') === '1') return;
        wrap.setAttribute('data-sb-float-bound', '1');

        var bar = wrap.querySelector('[data-sb-float-tools-bar]');
        var pad = 12;
        var drag = null;
        var currentCorner = 'tl';

        function clearInlinePos() {
            wrap.style.left = '';
            wrap.style.top = '';
            wrap.style.right = '';
            wrap.style.bottom = '';
            wrap.style.width = '';
        }

        function clampPos(left, top) {
            var maxLeft = Math.max(0, workspace.clientWidth - wrap.offsetWidth);
            var maxTop = Math.max(0, workspace.clientHeight - wrap.offsetHeight);
            return {
                left: Math.max(0, Math.min(left, maxLeft)),
                top: Math.max(0, Math.min(top, maxTop))
            };
        }

        function applyCorner(corner, silent) {
            if (!corner || !/^(tl|tr|bl|br)$/.test(corner)) corner = 'tl';
            currentCorner = corner;
            wrap.classList.add('is-snapping');
            clearInlinePos();

            var bw = wrap.offsetWidth;
            var bh = wrap.offsetHeight;
            var ww = workspace.clientWidth;
            var wh = workspace.clientHeight;
            var left = pad;
            var top = pad;

            if (corner === 'tr') left = ww - bw - pad;
            if (corner === 'bl') top = wh - bh - pad;
            if (corner === 'br') {
                left = ww - bw - pad;
                top = wh - bh - pad;
            }

            var pos = clampPos(left, top);
            wrap.style.left = pos.left + 'px';
            wrap.style.top = pos.top + 'px';
            wrap.setAttribute('data-sb-float-corner', corner);

            try { localStorage.setItem('sb-ed-float-corner', corner); } catch (err) { /* ignore */ }

            var menu = wrap.querySelector('[data-sb-float-tools-dock-menu]');
            if (menu) {
                menu.querySelectorAll('[data-sb-float-corner]').forEach(function (btn) {
                    btn.classList.toggle('is-active', btn.getAttribute('data-sb-float-corner') === corner);
                });
            }

            setTimeout(function () { wrap.classList.remove('is-snapping'); }, 280);

            if (!silent && window.SbEditorPrototype && root.classList.contains('sb-hifi-editor--prototype')) {
                var labels = { tl: '좌측 상단', tr: '우측 상단', bl: '좌측 하단', br: '우측 하단' };
                window.SbEditorPrototype.toast(root, '도구바 · ' + labels[corner]);
            }
        }

        function applyOrient(orient, silent) {
            if (orient !== 'vertical') orient = 'horizontal';
            wrap.setAttribute('data-sb-float-orient', orient);
            try { localStorage.setItem('sb-ed-float-orient', orient); } catch (err) { /* ignore */ }

            var menu = wrap.querySelector('[data-sb-float-tools-dock-menu]');
            if (menu) {
                menu.querySelectorAll('[data-sb-float-orient]').forEach(function (btn) {
                    btn.classList.toggle('is-active', btn.getAttribute('data-sb-float-orient') === orient);
                });
            }

            if (currentCorner !== 'free') {
                applyCorner(currentCorner, true);
            }

            if (!silent && window.SbEditorPrototype && root.classList.contains('sb-hifi-editor--prototype')) {
                window.SbEditorPrototype.toast(root, '도구바 · ' + (orient === 'vertical' ? '세로 배치' : '가로 배치'));
            }
        }

        function startDrag(e) {
            if (e.button !== 0) return;
            e.preventDefault();
            e.stopPropagation();
            var wr = workspace.getBoundingClientRect();
            var br = wrap.getBoundingClientRect();
            drag = {
                ox: e.clientX - br.left,
                oy: e.clientY - br.top
            };
            wrap.classList.add('is-dragging');
            wrap.style.left = (br.left - wr.left) + 'px';
            wrap.style.top = (br.top - wr.top) + 'px';
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        }

        function onMove(e) {
            if (!drag) return;
            e.preventDefault();
            var wr = workspace.getBoundingClientRect();
            var raw = clampPos(
                e.clientX - wr.left - drag.ox,
                e.clientY - wr.top - drag.oy
            );
            var targets = sbSnapCornerTargets(workspace, wrap, pad);
            var magnet = sbSnapNearest(targets, wrap, raw.left, raw.top, SB_SNAP_MAGNET);
            if (magnet) {
                wrap.classList.add('is-magnet-near');
                wrap.style.left = magnet.left + 'px';
                wrap.style.top = magnet.top + 'px';
            } else {
                wrap.classList.remove('is-magnet-near');
                wrap.style.left = raw.left + 'px';
                wrap.style.top = raw.top + 'px';
            }
        }

        function onUp() {
            if (!drag) return;
            drag = null;
            wrap.classList.remove('is-dragging');
            wrap.classList.remove('is-magnet-near');
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
            var wr = workspace.getBoundingClientRect();
            var br = wrap.getBoundingClientRect();
            var left = br.left - wr.left;
            var top = br.top - wr.top;
            var targets = sbSnapCornerTargets(workspace, wrap, pad);
            var nearest = sbSnapNearest(targets, wrap, left, top, SB_SNAP_RELEASE);
            if (nearest) {
                applyCorner(nearest.id, false);
            } else {
                currentCorner = 'free';
                wrap.setAttribute('data-sb-float-corner', 'free');
                var pos = clampPos(left, top);
                wrap.style.left = pos.left + 'px';
                wrap.style.top = pos.top + 'px';
                try { localStorage.setItem('sb-ed-float-corner', 'free'); } catch (err) { /* ignore */ }
            }
        }

        var grip = wrap.querySelector('[data-sb-float-tools-grip]');
        if (grip) grip.addEventListener('mousedown', startDrag);

        if (bar) {
            bar.addEventListener('mousedown', function (e) {
                if (e.target.closest('.sb-ed-float-tools__item') ||
                    e.target.closest('.sb-ed-float-tools__dock-wrap') ||
                    e.target.closest('[data-sb-float-tools-grip]')) return;
                startDrag(e);
            });
        }

        var dockToggle = wrap.querySelector('[data-sb-float-tools-dock-toggle]');
        var dockMenu = wrap.querySelector('[data-sb-float-tools-dock-menu]');
        if (dockToggle && dockMenu) {
            dockToggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dockMenu.hidden = !dockMenu.hidden;
                dockToggle.classList.toggle('is-open', !dockMenu.hidden);
            });
            dockMenu.querySelectorAll('[data-sb-float-corner]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    applyCorner(btn.getAttribute('data-sb-float-corner'), false);
                    dockMenu.hidden = true;
                    dockToggle.classList.remove('is-open');
                });
            });
            dockMenu.querySelectorAll('[data-sb-float-orient]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    applyOrient(btn.getAttribute('data-sb-float-orient'), false);
                });
            });
            document.addEventListener('click', function (e) {
                if (dockMenu.hidden) return;
                if (dockToggle.contains(e.target) || dockMenu.contains(e.target)) return;
                dockMenu.hidden = true;
                dockToggle.classList.remove('is-open');
            });
        }

        wrap.querySelectorAll('.sb-ed-float-tools__item[data-sb-layer]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        });

        var resizeTimer = null;
        window.addEventListener('resize', function () {
            if (!root.contains(wrap)) return;
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                if (currentCorner === 'free') {
                    var left = parseFloat(wrap.style.left) || pad;
                    var top = parseFloat(wrap.style.top) || pad;
                    var pos = clampPos(left, top);
                    wrap.style.left = pos.left + 'px';
                    wrap.style.top = pos.top + 'px';
                } else {
                    applyCorner(currentCorner, true);
                }
            }, 100);
        });

        try {
            var savedOrient = localStorage.getItem('sb-ed-float-orient');
            if (savedOrient === 'vertical' || savedOrient === 'horizontal') {
                applyOrient(savedOrient, true);
            }
            var savedCorner = localStorage.getItem('sb-ed-float-corner');
            if (savedCorner === 'free') {
                currentCorner = 'free';
            } else if (savedCorner && /^(tl|tr|bl|br)$/.test(savedCorner)) {
                currentCorner = savedCorner;
            }
        } catch (err) { /* ignore */ }

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                if (currentCorner !== 'free') {
                    applyCorner(currentCorner, true);
                }
            });
        });
    }

    function bindDraggableCard(root, opts) {
        var panel = root.querySelector(opts.panelSelector);
        if (!panel) return null;
        if (panel.getAttribute(opts.boundAttr) === '1') {
            return panel._sbSnapApi || null;
        }
        panel.setAttribute(opts.boundAttr, '1');

        var handle = panel.querySelector(opts.handleSelector);
        var minBtn = panel.querySelector(opts.minSelector);
        var body = root.querySelector('.sb-hifi-editor__body');
        var pad = typeof opts.snapPad === 'number' ? opts.snapPad : SB_SNAP_PAD;
        var currentSnapId = opts.defaultSnap || 'tr';
        var drag = { active: false, startX: 0, startY: 0, startLeft: 0, startTop: 0, pending: false, moved: false };

        function getAllTargets() {
            var targets = sbSnapCornerTargets(body, panel, pad);
            var extra = typeof opts.getExtraSnapTargets === 'function' ? opts.getExtraSnapTargets() : {};
            Object.keys(extra).forEach(function (id) {
                targets[id] = extra[id];
            });
            return targets;
        }

        function applySnapPosition(snapId, left, top, silent) {
            if (!body) return;
            var pos = sbSnapClamp(body, panel, left, top, 8);
            panel.classList.add('is-snapping');
            panel.style.right = 'auto';
            panel.style.left = pos.left + 'px';
            panel.style.top = pos.top + 'px';
            panel.classList.add('is-moved');
            currentSnapId = snapId;
            panel.setAttribute('data-sb-snap-id', snapId);
            if (opts.snapStorageKey) {
                try { localStorage.setItem(opts.snapStorageKey, snapId); } catch (err) { /* ignore */ }
            }
            setTimeout(function () { panel.classList.remove('is-snapping'); }, 280);
            if (!silent && opts.toastPrefix && window.SbEditorPrototype && root.classList.contains('sb-hifi-editor--prototype')) {
                var label = SB_SNAP_LABELS[snapId] || snapId;
                window.SbEditorPrototype.toast(root, opts.toastPrefix + label);
            }
        }

        function applySnapById(snapId, silent) {
            var targets = getAllTargets();
            var t = targets[snapId];
            if (!t) return;
            applySnapPosition(snapId, t.left, t.top, silent);
        }

        function snapFromPosition(left, top, silent) {
            var targets = getAllTargets();
            var nearest = sbSnapNearest(targets, panel, left, top, SB_SNAP_RELEASE);
            if (!nearest) {
                setFreePosition(left, top);
                panel.classList.add('is-moved');
                currentSnapId = 'free';
                panel.setAttribute('data-sb-snap-id', 'free');
                if (opts.snapStorageKey) {
                    try { localStorage.setItem(opts.snapStorageKey, 'free'); } catch (err) { /* ignore */ }
                }
                return null;
            }
            applySnapPosition(nearest.id, nearest.left, nearest.top, silent);
            return nearest.id;
        }

        function setFreePosition(left, top) {
            var pos = sbSnapClamp(body, panel, left, top, 8);
            panel.style.right = 'auto';
            panel.style.left = pos.left + 'px';
            panel.style.top = pos.top + 'px';
        }

        if (minBtn) {
            minBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var minimized = panel.classList.toggle('is-minimized');
                minBtn.textContent = minimized ? '□' : '—';
                minBtn.title = minimized ? '펼치기' : '접기';
                minBtn.setAttribute('aria-label', minimized ? '패널 펼치기' : '패널 접기');
                if (opts.onStack) opts.onStack();
                if (window.SbEditorPrototype && root.classList.contains('sb-hifi-editor--prototype') && opts.toastMinimize) {
                    window.SbEditorPrototype.toast(root, minimized ? opts.toastMinimize[0] : opts.toastMinimize[1]);
                }
            });
        }

        if (handle && body) {
            handle.addEventListener('mousedown', function (e) {
                if (e.target.closest(opts.minSelector)) return;
                if (e.button !== 0) return;
                e.preventDefault();
                e.stopPropagation();
                var rect = panel.getBoundingClientRect();
                var bodyRect = body.getBoundingClientRect();
                drag.active = false;
                drag.moved = false;
                drag.startX = e.clientX;
                drag.startY = e.clientY;
                drag.startLeft = rect.left - bodyRect.left;
                drag.startTop = rect.top - bodyRect.top;
                drag.pending = true;
            });

            var onMove = function (e) {
                if (!drag.pending && !drag.active) return;
                var dx = e.clientX - drag.startX;
                var dy = e.clientY - drag.startY;
                if (!drag.active) {
                    if (Math.abs(dx) < 3 && Math.abs(dy) < 3) return;
                    drag.active = true;
                    drag.pending = false;
                    drag.moved = true;
                    setFreePosition(drag.startLeft, drag.startTop);
                    panel.classList.add('is-dragging');
                }
                var rawLeft = drag.startLeft + dx;
                var rawTop = drag.startTop + dy;
                var targets = getAllTargets();
                var magnet = sbSnapNearest(targets, panel, rawLeft, rawTop, SB_SNAP_MAGNET);
                if (magnet) {
                    panel.classList.add('is-magnet-near');
                    setFreePosition(magnet.left, magnet.top);
                } else {
                    panel.classList.remove('is-magnet-near');
                    setFreePosition(rawLeft, rawTop);
                }
            };
            var onUp = function (e) {
                if (!drag.pending && !drag.active) return;
                var wasDragging = drag.active;
                drag.pending = false;
                drag.active = false;
                panel.classList.remove('is-dragging');
                panel.classList.remove('is-magnet-near');
                if (wasDragging) {
                    var bodyRect = body.getBoundingClientRect();
                    var rect = panel.getBoundingClientRect();
                    snapFromPosition(rect.left - bodyRect.left, rect.top - bodyRect.top, false);
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }
                if (wasDragging && opts.onDragEnd) opts.onDragEnd();
            };
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp, true);

            handle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
            });
        }

        var api = {
            panel: panel,
            applySnapById: applySnapById,
            snapFromPosition: snapFromPosition,
            getSnapId: function () {
                return panel.getAttribute('data-sb-snap-id') || currentSnapId;
            }
        };
        panel._sbSnapApi = api;
        return api;
    }

    function layoutPreviewPanelStack(root, silent) {
        var body = root.querySelector('.sb-hifi-editor__body');
        var props = root.querySelector('[data-sb-props-panel]');
        var preview = root.querySelector('[data-sb-preview-panel]');
        if (!body || !props || !preview) return;
        var snapId = preview.getAttribute('data-sb-snap-id');
        if (snapId && snapId !== 'stack-props') return;

        var stack = sbPreviewStackTargets(root)['stack-props'];
        if (!stack) return;

        var pos = sbSnapClamp(body, preview, stack.left, stack.top, 8);
        preview.classList.add('is-snapping');
        preview.style.right = 'auto';
        preview.style.left = pos.left + 'px';
        preview.style.top = pos.top + 'px';
        preview.classList.add('is-moved');
        preview.setAttribute('data-sb-snap-id', 'stack-props');
        try { localStorage.setItem('sb-ed-preview-snap', 'stack-props'); } catch (err) { /* ignore */ }
        setTimeout(function () { preview.classList.remove('is-snapping'); }, 280);

        if (!silent && window.SbEditorPrototype && root.classList.contains('sb-hifi-editor--prototype')) {
            window.SbEditorPrototype.toast(root, '미리보기 · ' + SB_SNAP_LABELS['stack-props']);
        }
    }

    function bindPropsPanel(root) {
        var api = bindDraggableCard(root, {
            panelSelector: '[data-sb-props-panel]',
            handleSelector: '[data-sb-props-drag-handle]',
            minSelector: '[data-sb-props-minimize]',
            boundAttr: 'data-sb-props-bound',
            snapStorageKey: 'sb-ed-props-corner',
            defaultSnap: 'tr',
            toastPrefix: '속성 · ',
            toastMinimize: ['속성 패널 접음', '속성 패널 펼침'],
            onStack: function () { layoutPreviewPanelStack(root, true); },
            onDragEnd: function () { layoutPreviewPanelStack(root, true); }
        });
        if (!api) return;

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                try {
                    var saved = localStorage.getItem('sb-ed-props-corner');
                    if (saved === 'free') {
                        /* 자유 배치 유지 */
                    } else if (saved && /^(tl|tr|bl|br)$/.test(saved)) {
                        api.applySnapById(saved, true);
                    } else {
                        api.applySnapById('tr', true);
                    }
                } catch (err) {
                    api.applySnapById('tr', true);
                }
                layoutPreviewPanelStack(root, true);
            });
        });

        var resizeTimer = null;
        window.addEventListener('resize', function () {
            if (!root.contains(api.panel)) return;
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                var snapId = api.getSnapId();
                var editorBody = root.querySelector('.sb-hifi-editor__body');
                if (snapId === 'free' && editorBody) {
                    var left = parseFloat(api.panel.style.left) || 0;
                    var top = parseFloat(api.panel.style.top) || 0;
                    var pos = sbSnapClamp(editorBody, api.panel, left, top, 8);
                    api.panel.style.left = pos.left + 'px';
                    api.panel.style.top = pos.top + 'px';
                } else if (snapId !== 'free') {
                    api.applySnapById(snapId, true);
                }
                layoutPreviewPanelStack(root, true);
            }, 100);
        });
    }

    function bindPreviewPanel(root) {
        var api = bindDraggableCard(root, {
            panelSelector: '[data-sb-preview-panel]',
            handleSelector: '[data-sb-preview-drag-handle]',
            minSelector: '[data-sb-preview-minimize]',
            boundAttr: 'data-sb-preview-bound',
            snapStorageKey: 'sb-ed-preview-snap',
            defaultSnap: 'stack-props',
            toastPrefix: '미리보기 · ',
            toastMinimize: ['미리보기 패널 접음', '미리보기 패널 펼침'],
            getExtraSnapTargets: function () { return sbPreviewStackTargets(root); }
        });
        if (!api) return;

        var panel = api.panel;
        var pageLabel = panel.querySelector('[data-sb-preview-page-label]');
        var copyToggle = panel.querySelector('[data-sb-preview-copy-toggle]');
        var copyMenu = panel.querySelector('[data-sb-preview-copy-menu]');
        var pageState = { current: 1, total: 3 };

        function toast(msg) {
            if (window.SbEditorPrototype) window.SbEditorPrototype.toast(root, msg);
        }

        function updatePageLabel() {
            if (pageLabel) {
                pageLabel.textContent = '페이지 ' + pageState.current + ' / ' + pageState.total;
            }
            panel.querySelectorAll('[data-sb-preview-page]').forEach(function (btn) {
                var action = btn.getAttribute('data-sb-preview-page');
                var disabled = false;
                if (action === 'first' || action === 'prev') disabled = pageState.current <= 1;
                if (action === 'next' || action === 'last') disabled = pageState.current >= pageState.total;
                btn.disabled = disabled;
            });
        }

        function closeCopyMenu() {
            if (!copyMenu || !copyToggle) return;
            copyMenu.hidden = true;
            copyToggle.classList.remove('is-open');
            copyToggle.setAttribute('aria-expanded', 'false');
        }

        function openCopyMenu() {
            if (!copyMenu || !copyToggle) return;
            copyMenu.hidden = false;
            copyToggle.classList.add('is-open');
            copyToggle.setAttribute('aria-expanded', 'true');
        }

        panel.querySelectorAll('[data-sb-preview-cell]').forEach(function (cell) {
            cell.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                panel.querySelectorAll('[data-sb-preview-cell]').forEach(function (c) {
                    c.classList.remove('is-selected');
                    c.setAttribute('aria-pressed', 'false');
                });
                cell.classList.add('is-selected');
                cell.setAttribute('aria-pressed', 'true');
                var idx = parseInt(cell.getAttribute('data-sb-preview-cell') || '0', 10) + 1;
                toast('라벨 ' + idx + ' 선택');
            });
        });

        panel.querySelectorAll('[data-sb-preview-page]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var action = btn.getAttribute('data-sb-preview-page');
                if (action === 'first') pageState.current = 1;
                else if (action === 'prev') pageState.current = Math.max(1, pageState.current - 1);
                else if (action === 'next') pageState.current = Math.min(pageState.total, pageState.current + 1);
                else if (action === 'last') pageState.current = pageState.total;
                updatePageLabel();
                toast('페이지 ' + pageState.current + ' / ' + pageState.total);
            });
        });

        if (copyToggle && copyMenu) {
            copyToggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (copyMenu.hidden) openCopyMenu();
                else closeCopyMenu();
            });
        }

        var copyLabels = {
            'master-all': '마스터로 전체 적용',
            'copy-all': '전체로 복사',
            'dup-page': '페이지 복제',
            'copy-next': '다음으로 복사',
            'copy-rest': '나머지로 복사',
            'copy-row': '행으로 복사',
            'copy-col': '열로 복사',
            'copy-page': '페이지로 복사'
        };
        panel.querySelectorAll('[data-sb-preview-copy-action]').forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var action = item.getAttribute('data-sb-preview-copy-action');
                var label = copyLabels[action] || item.textContent.trim();
                toast('라벨복사 · ' + label);
                closeCopyMenu();
            });
        });

        panel.querySelectorAll('[data-sb-preview-action]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var action = btn.getAttribute('data-sb-preview-action');
                if (action === 'delete') toast('선택 라벨 삭제 (프로토타입)');
                else if (action === 'add') toast('라벨 추가 (프로토타입)');
            });
        });

        var qtyInput = panel.querySelector('[data-sb-preview-print-qty]');
        if (qtyInput) {
            qtyInput.addEventListener('change', function () {
                var val = parseInt(qtyInput.value || '1', 10);
                if (isNaN(val) || val < 1) val = 1;
                if (val > 9999) val = 9999;
                qtyInput.value = String(val);
                toast('인쇄 수량: ' + val);
            });
        }

        var liveToggle = panel.querySelector('[data-sb-preview-live-toggle]');
        if (liveToggle) {
            liveToggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var on = liveToggle.classList.toggle('is-on');
                liveToggle.setAttribute('aria-pressed', on ? 'true' : 'false');
                toast('미리보기 ' + (on ? 'ON' : 'OFF'));
            });
        }

        if (!panel.getAttribute('data-sb-preview-ui-bound')) {
            panel.setAttribute('data-sb-preview-ui-bound', '1');
            document.addEventListener('click', function (e) {
                if (!panel.contains(e.target)) closeCopyMenu();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeCopyMenu();
            });
        }

        updatePageLabel();

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                try {
                    var saved = localStorage.getItem('sb-ed-preview-snap');
                    if (saved === 'stack-props') {
                        layoutPreviewPanelStack(root, true);
                    } else if (saved === 'free') {
                        /* 자유 배치 유지 */
                    } else if (saved && /^(tl|tr|bl|br)$/.test(saved)) {
                        api.applySnapById(saved, true);
                    } else {
                        layoutPreviewPanelStack(root, true);
                    }
                } catch (err) {
                    layoutPreviewPanelStack(root, true);
                }
            });
        });

        var stackTimer = null;
        window.addEventListener('resize', function () {
            if (!root.contains(panel)) return;
            clearTimeout(stackTimer);
            stackTimer = setTimeout(function () {
                var snapId = api.getSnapId();
                var editorBody = root.querySelector('.sb-hifi-editor__body');
                if (snapId === 'stack-props') {
                    layoutPreviewPanelStack(root, true);
                } else if (snapId === 'free' && editorBody) {
                    var left = parseFloat(panel.style.left) || 0;
                    var top = parseFloat(panel.style.top) || 0;
                    var pos = sbSnapClamp(editorBody, panel, left, top, 8);
                    panel.style.left = pos.left + 'px';
                    panel.style.top = pos.top + 'px';
                } else if (snapId !== 'free') {
                    api.applySnapById(snapId, true);
                }
            }, 100);
        });
    }

    function sbCanvasGetArtboard(root) {
        return root.querySelector('[data-sb-artboard]') || root.querySelector('.sb-hifi-editor__artboard');
    }

    function sbCanvasGetLayer(root) {
        var board = sbCanvasGetArtboard(root);
        return board ? board.querySelector('[data-sb-canvas-objects]') : null;
    }

    function sbCanvasSelect(root, obj) {
        var layer = sbCanvasGetLayer(root);
        if (!layer) return;
        layer.querySelectorAll('.sb-ed-canvas-obj.is-selected').forEach(function (el) {
            el.classList.remove('is-selected');
            var textEl = el.querySelector('.sb-ed-canvas-obj__text');
            if (textEl) textEl.removeAttribute('contenteditable');
        });
        if (obj) obj.classList.add('is-selected');
        root._sbCanvasSelectedId = obj ? obj.getAttribute('data-sb-obj-id') : null;
    }

    function sbCanvasImageHtml() {
        return '<div class="sb-ed-canvas-obj__image-frame">' +
            '<span class="sb-ed-canvas-obj__img-corner sb-ed-canvas-obj__img-corner--tl" aria-hidden="true"></span>' +
            '<span class="sb-ed-canvas-obj__img-corner sb-ed-canvas-obj__img-corner--tr" aria-hidden="true"></span>' +
            '<span class="sb-ed-canvas-obj__img-corner sb-ed-canvas-obj__img-corner--bl" aria-hidden="true"></span>' +
            '<span class="sb-ed-canvas-obj__img-corner sb-ed-canvas-obj__img-corner--br" aria-hidden="true"></span>' +
            '<svg viewBox="0 0 56 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">' +
            '<rect x="14" y="4" width="34" height="26" rx="2" fill="#f1f5f9" stroke="#64748b" stroke-width="1.5"/>' +
            '<rect x="6" y="12" width="38" height="28" rx="2" fill="#fff" stroke="#334155" stroke-width="1.5"/>' +
            '<path d="M10 34 L20 24 L28 30 L38 18 L44 34 Z" fill="#86efac" stroke="#22c55e" stroke-width="1"/>' +
            '<circle cx="36" cy="20" r="4.5" fill="#fdba74" stroke="#f97316" stroke-width="1"/>' +
            '</svg></div>';
    }

    function sbCanvasShapeHtml() {
        return '<div class="sb-ed-canvas-obj__shape" aria-hidden="true"></div>';
    }

    function sbCanvasTableHtml() {
        var rows = [
            ['A', 'B', 'C'],
            ['1', '2', '3']
        ];
        var html = '<table class="sb-ed-canvas-obj__table"><tbody>';
        rows.forEach(function (row) {
            html += '<tr>';
            row.forEach(function (cell) {
                html += '<td>' + cell + '</td>';
            });
            html += '</tr>';
        });
        html += '</tbody></table>';
        return html;
    }

    function sbCanvasBarcodeHtml() {
        return '<div class="sb-ed-canvas-obj__barcode">' +
            '<svg class="sb-ed-canvas-obj__barcode-bars" viewBox="0 0 120 32" aria-hidden="true">' +
            '<rect x="2" y="4" width="2" height="24" fill="#0f172a"/>' +
            '<rect x="6" y="4" width="1" height="24" fill="#0f172a"/>' +
            '<rect x="9" y="4" width="3" height="24" fill="#0f172a"/>' +
            '<rect x="14" y="4" width="1" height="24" fill="#0f172a"/>' +
            '<rect x="17" y="4" width="2" height="24" fill="#0f172a"/>' +
            '<rect x="21" y="4" width="4" height="24" fill="#0f172a"/>' +
            '<rect x="27" y="4" width="1" height="24" fill="#0f172a"/>' +
            '<rect x="30" y="4" width="2" height="24" fill="#0f172a"/>' +
            '<rect x="34" y="4" width="1" height="24" fill="#0f172a"/>' +
            '<rect x="37" y="4" width="3" height="24" fill="#0f172a"/>' +
            '<rect x="42" y="4" width="2" height="24" fill="#0f172a"/>' +
            '<rect x="46" y="4" width="1" height="24" fill="#0f172a"/>' +
            '<rect x="49" y="4" width="4" height="24" fill="#0f172a"/>' +
            '<rect x="55" y="4" width="2" height="24" fill="#0f172a"/>' +
            '<rect x="59" y="4" width="1" height="24" fill="#0f172a"/>' +
            '<rect x="62" y="4" width="3" height="24" fill="#0f172a"/>' +
            '<rect x="67" y="4" width="1" height="24" fill="#0f172a"/>' +
            '<rect x="70" y="4" width="2" height="24" fill="#0f172a"/>' +
            '<rect x="74" y="4" width="4" height="24" fill="#0f172a"/>' +
            '<rect x="80" y="4" width="1" height="24" fill="#0f172a"/>' +
            '<rect x="83" y="4" width="2" height="24" fill="#0f172a"/>' +
            '<rect x="87" y="4" width="3" height="24" fill="#0f172a"/>' +
            '<rect x="92" y="4" width="1" height="24" fill="#0f172a"/>' +
            '<rect x="95" y="4" width="2" height="24" fill="#0f172a"/>' +
            '<rect x="99" y="4" width="4" height="24" fill="#0f172a"/>' +
            '<rect x="105" y="4" width="1" height="24" fill="#0f172a"/>' +
            '<rect x="108" y="4" width="2" height="24" fill="#0f172a"/>' +
            '<rect x="112" y="4" width="3" height="24" fill="#0f172a"/>' +
            '</svg>' +
            '<span class="sb-ed-canvas-obj__barcode-label">8801234567890</span>' +
            '</div>';
    }

    var SB_CANVAS_INSTANT = ['text', 'image', 'shape', 'table', 'barcode'];
    var SB_CANVAS_LABELS = {
        text: '텍스트',
        image: '이미지',
        shape: '도형',
        table: '표',
        barcode: '바·QR코드'
    };

    function sbCanvasAddObject(root, type, opts) {
        var layer = sbCanvasGetLayer(root);
        var board = sbCanvasGetArtboard(root);
        if (!layer || !board) return null;

        opts = opts || {};
        if (!root._sbCanvasObjSeq) root._sbCanvasObjSeq = 0;
        root._sbCanvasObjSeq += 1;
        var id = 'obj-' + root._sbCanvasObjSeq;
        var count = layer.querySelectorAll('.sb-ed-canvas-obj--' + type).length;

        var el = document.createElement('div');
        el.className = 'sb-ed-canvas-obj sb-ed-canvas-obj--' + type;
        el.setAttribute('data-sb-canvas-obj', '');
        el.setAttribute('data-sb-obj-type', type);
        el.setAttribute('data-sb-obj-id', id);

        var bw = board.clientWidth;
        var bh = board.clientHeight;
        var left = typeof opts.left === 'number' ? opts.left : 0;
        var top = typeof opts.top === 'number' ? opts.top : 0;
        var defaultW = 80;
        var defaultH = 24;

        if (type === 'text') {
            if (typeof opts.left !== 'number') left = 14 + (count % 3) * 6;
            if (typeof opts.top !== 'number') top = 18 + count * 22;
            el.innerHTML = '<div class="sb-ed-canvas-obj__text">텍스트를 입력하세요.</div>';
            defaultW = 80;
            defaultH = 22;
        } else if (type === 'image') {
            var size = 92;
            defaultW = size;
            defaultH = size;
            if (typeof opts.left !== 'number') left = Math.max(8, Math.round((bw - size) / 2));
            if (typeof opts.top !== 'number') top = Math.max(24, Math.round((bh - size) / 2) + count * 8);
            el.style.width = size + 'px';
            el.style.height = size + 'px';
            el.innerHTML = sbCanvasImageHtml();
        } else if (type === 'shape') {
            defaultW = 80;
            defaultH = 56;
            if (typeof opts.left !== 'number') left = Math.max(8, Math.round((bw - defaultW) / 2) - 20 + (count % 3) * 12);
            if (typeof opts.top !== 'number') top = Math.max(20, Math.round((bh - defaultH) / 2) - 30 + count * 14);
            el.style.width = defaultW + 'px';
            el.style.height = defaultH + 'px';
            el.innerHTML = sbCanvasShapeHtml();
        } else if (type === 'table') {
            defaultW = 108;
            defaultH = 52;
            if (typeof opts.left !== 'number') left = Math.max(8, Math.round((bw - defaultW) / 2) + (count % 2) * 10);
            if (typeof opts.top !== 'number') top = Math.max(16, Math.round((bh - defaultH) / 2) + count * 12);
            el.innerHTML = sbCanvasTableHtml();
        } else if (type === 'barcode') {
            defaultW = 116;
            defaultH = 48;
            if (typeof opts.left !== 'number') left = Math.max(8, Math.round((bw - defaultW) / 2));
            if (typeof opts.top !== 'number') top = Math.max(20, Math.round((bh - defaultH) / 2) + 40 + count * 10);
            el.style.width = defaultW + 'px';
            el.innerHTML = sbCanvasBarcodeHtml();
        } else {
            return null;
        }

        layer.appendChild(el);
        var pw = el.offsetWidth || defaultW;
        var ph = el.offsetHeight || defaultH;
        left = Math.max(4, Math.min(left, bw - pw - 4));
        top = Math.max(4, Math.min(top, bh - ph - 4));
        el.style.left = left + 'px';
        el.style.top = top + 'px';

        sbCanvasSelect(root, el);

        if (window.SbEditorPrototype && root.classList.contains('sb-hifi-editor--prototype')) {
            var label = SB_CANVAS_LABELS[type] || type;
            window.SbEditorPrototype.toast(root, label + ' 객체 추가됨');
            window.SbEditorPrototype.pushHistory(root, label + ' 추가');
        }
        return el;
    }

    function bindCanvasObjects(root) {
        if (!root || root.getAttribute('data-sb-canvas-bound') === '1') return;
        root.setAttribute('data-sb-canvas-bound', '1');

        var board = sbCanvasGetArtboard(root);
        var layer = sbCanvasGetLayer(root);
        if (!board || !layer) return;

        var drag = null;

        root.querySelectorAll('.sb-ed-float-tools__item[data-sb-proto]').forEach(function (btn) {
            var proto = btn.getAttribute('data-sb-proto');
            if (SB_CANVAS_INSTANT.indexOf(proto) === -1) return;
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                sbCanvasAddObject(root, proto);
            });
        });

        board.addEventListener('mousedown', function (e) {
            if (e.target.closest('[data-sb-canvas-obj]')) return;
            if (e.target.closest('[data-sb-proto="select-object"]')) return;
            sbCanvasSelect(root, null);
        });

        layer.addEventListener('mousedown', function (e) {
            var obj = e.target.closest('[data-sb-canvas-obj]');
            if (!obj || e.button !== 0) return;
            if (e.target.closest('[contenteditable="true"]')) return;

            e.preventDefault();
            e.stopPropagation();
            sbCanvasSelect(root, obj);

            var textEl = obj.querySelector('.sb-ed-canvas-obj__text[contenteditable="true"]');
            if (textEl) return;

            var br = board.getBoundingClientRect();
            var or = obj.getBoundingClientRect();
            drag = {
                obj: obj,
                ox: e.clientX - or.left,
                oy: e.clientY - or.top,
                bw: board.clientWidth,
                bh: board.clientHeight
            };
            obj.classList.add('is-dragging');
        });

        document.addEventListener('mousemove', function (e) {
            if (!drag || !root.contains(drag.obj)) return;
            e.preventDefault();
            var br = board.getBoundingClientRect();
            var left = e.clientX - br.left - drag.ox;
            var top = e.clientY - br.top - drag.oy;
            var pw = drag.obj.offsetWidth;
            var ph = drag.obj.offsetHeight;
            left = Math.max(0, Math.min(left, drag.bw - pw));
            top = Math.max(0, Math.min(top, drag.bh - ph));
            drag.obj.style.left = left + 'px';
            drag.obj.style.top = top + 'px';
        });

        document.addEventListener('mouseup', function () {
            if (!drag || !root.contains(drag.obj)) {
                drag = null;
                return;
            }
            drag.obj.classList.remove('is-dragging');
            drag = null;
        });

        layer.addEventListener('dblclick', function (e) {
            var obj = e.target.closest('.sb-ed-canvas-obj--text');
            if (!obj) return;
            e.preventDefault();
            e.stopPropagation();
            sbCanvasSelect(root, obj);
            var textEl = obj.querySelector('.sb-ed-canvas-obj__text');
            if (!textEl) return;
            textEl.setAttribute('contenteditable', 'true');
            textEl.focus();
            var range = document.createRange();
            range.selectNodeContents(textEl);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        });

        layer.addEventListener('blur', function (e) {
            var textEl = e.target.closest('.sb-ed-canvas-obj__text[contenteditable="true"]');
            if (!textEl) return;
            textEl.removeAttribute('contenteditable');
            if (!textEl.textContent.trim()) {
                textEl.textContent = '텍스트를 입력하세요.';
            }
        }, true);

        layer.addEventListener('keydown', function (e) {
            var textEl = e.target.closest('.sb-ed-canvas-obj__text[contenteditable="true"]');
            if (!textEl) return;
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                textEl.blur();
            }
        });
    }

    function bindAssetSlidePanel(root) {
        var slide = root.querySelector('[data-sb-asset-slide]');
        var workspace = root.querySelector('.sb-hifi-editor__workspace');
        if (!slide || !workspace || slide.getAttribute('data-sb-asset-bound') === '1') return;
        slide.setAttribute('data-sb-asset-bound', '1');

        var currentType = null;
        var kindLabels = { background: '배경', template: '템플릿', clipart: '클립아트', icon: '아이콘' };

        function setActiveTool(type) {
            root.querySelectorAll('[data-sb-asset-tool]').forEach(function (btn) {
                btn.classList.toggle('is-tool-active', type && btn.getAttribute('data-sb-asset-tool') === type);
            });
        }

        function showView(type) {
            slide.querySelectorAll('[data-sb-asset-view]').forEach(function (view) {
                view.hidden = view.getAttribute('data-sb-asset-view') !== type;
            });
        }

        function closePanel() {
            currentType = null;
            slide.classList.remove('is-open');
            slide.setAttribute('aria-hidden', 'true');
            workspace.classList.remove('is-asset-slide-open');
            setActiveTool(null);
        }

        function openPanel(type) {
            if (!type) return;
            if (currentType === type && slide.classList.contains('is-open')) {
                closePanel();
                return;
            }
            currentType = type;
            showView(type);
            slide.classList.add('is-open');
            slide.setAttribute('aria-hidden', 'false');
            workspace.classList.add('is-asset-slide-open');
            setActiveTool(type);
            var view = slide.querySelector('[data-sb-asset-view="' + type + '"]');
            if (view) {
                var input = view.querySelector('.sb-ed-asset-slide__search-input');
                if (input) {
                    input.value = '';
                    input.dispatchEvent(new Event('input'));
                }
            }
        }

        root._sbAssetSlide = {
            open: openPanel,
            close: closePanel,
            toggle: openPanel,
            isOpen: function () { return slide.classList.contains('is-open'); }
        };

        root.querySelectorAll('[data-sb-asset-tool]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                openPanel(btn.getAttribute('data-sb-asset-tool'));
            });
        });

        slide.querySelectorAll('[data-sb-asset-slide-close], [data-sb-asset-slide-collapse]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                closePanel();
            });
        });

        slide.querySelectorAll('[data-sb-asset-tags]').forEach(function (bar) {
            bar.addEventListener('click', function (e) {
                var tag = e.target.closest('.sb-ed-asset-slide__tag');
                if (!tag) return;
                e.preventDefault();
                bar.querySelectorAll('.sb-ed-asset-slide__tag').forEach(function (t) {
                    t.classList.remove('is-active');
                });
                tag.classList.add('is-active');
            });
        });

        slide.querySelectorAll('.sb-ed-asset-slide__search-input').forEach(function (input) {
            var wrap = input.closest('.sb-ed-asset-slide__search');
            var clearBtn = wrap ? wrap.querySelector('[data-sb-asset-search-clear]') : null;
            input.addEventListener('input', function () {
                if (clearBtn) clearBtn.hidden = !input.value;
                var view = input.closest('[data-sb-asset-view]');
                if (!view) return;
                var q = input.value.trim().toLowerCase();
                view.querySelectorAll('[data-sb-asset-pick]').forEach(function (card) {
                    var text = card.textContent.toLowerCase();
                    card.hidden = q && text.indexOf(q) === -1;
                });
            });
        });

        slide.querySelectorAll('[data-sb-asset-search-clear]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var input = btn.closest('.sb-ed-asset-slide__search').querySelector('.sb-ed-asset-slide__search-input');
                if (input) {
                    input.value = '';
                    input.dispatchEvent(new Event('input'));
                    input.focus();
                }
            });
        });

        slide.querySelectorAll('[data-sb-proto="bg-delete"]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (window.SbEditorPrototype && root.classList.contains('sb-hifi-editor--prototype')) {
                    window.SbEditorPrototype.toast(root, '배경을 삭제했습니다');
                    window.SbEditorPrototype.pushHistory(root, '배경 삭제');
                }
            });
        });

        slide.querySelectorAll('[data-sb-asset-pick]').forEach(function (card) {
            card.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var kind = card.getAttribute('data-sb-asset-pick');
                var nameEl = card.querySelector('.sb-ed-asset-slide__card-title, .sb-ed-asset-slide__icon-label');
                var label = nameEl ? nameEl.textContent.trim() : kind;
                if (window.SbEditorPrototype && root.classList.contains('sb-hifi-editor--prototype')) {
                    window.SbEditorPrototype.toast(root, (kindLabels[kind] || kind) + ' 적용: ' + label);
                    window.SbEditorPrototype.pushHistory(root, label + ' 적용');
                }
            });
        });
    }

    window.sbEdCloseAssetSlide = function (el) {
        var root = el && el.closest ? el.closest('[data-sb-editor-root]') : null;
        if (root && root._sbAssetSlide) root._sbAssetSlide.close();
        return false;
    };

    function bindEditorRoot(root) {
        if (!root || root.getAttribute('data-sb-editor-bound') === '1') return;
        root.setAttribute('data-sb-editor-bound', '1');

        var layerOverlay = root.querySelector('[data-sb-layer-overlay]');
        if (layerOverlay) {
            layerOverlay.addEventListener('click', function (e) {
                if (e.target === layerOverlay) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.sbEdCloseLayer(layerOverlay);
                }
            });
        }

        var previewOverlay = root.querySelector('[data-sb-preview-overlay]');
        if (previewOverlay) {
            previewOverlay.addEventListener('click', function (e) {
                if (e.target === previewOverlay) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.sbEdClosePreview(previewOverlay);
                }
            });
        }

        window.SbEditorPrototype && window.SbEditorPrototype.bindRoot(root);
        bindTopbar(root);
        bindImportRoot(root);
        bindDataImportRoot(root);
        bindSpecDetailRoot(root);
        bindFloatingTools(root);
        bindPropsPanel(root);
        bindPreviewPanel(root);
        bindCanvasObjects(root);
        bindAssetSlidePanel(root);
    }

    function bindAllEditors() {
        document.querySelectorAll('[data-sb-editor-root]').forEach(bindEditorRoot);
    }

    window.SbEditorInteractive = {
        bindRoot: bindEditorRoot,
        bindAll: bindAllEditors,
        openLayer: window.sbEdOpenLayer,
        closeLayer: window.sbEdCloseLayer,
        openImport: window.sbEdOpenImport,
        closeImport: window.sbEdCloseImport,
        openDataImport: window.sbEdOpenDataImport,
        closeDataImport: window.sbEdCloseDataImport
    };

    /* ── 프로토타입 모드 ── */
    var Proto = {
        zoom: 100,
        previewZoom: 100,
        gridOn: false,
        history: [],

        toast: function (root, message) {
            var wrap = root.querySelector('[data-sb-proto-toast-wrap]');
            if (!wrap) return;
            var el = document.createElement('div');
            el.className = 'sb-ed-proto-toast';
            el.textContent = message;
            wrap.appendChild(el);
            requestAnimationFrame(function () { el.classList.add('is-show'); });
            setTimeout(function () {
                el.classList.remove('is-show');
                setTimeout(function () { el.remove(); }, 300);
            }, 2400);
        },

        modal: function (root, title, bodyHtml) {
            var modal = root.querySelector('[data-sb-proto-modal]');
            if (!modal) return;
            modal.querySelector('[data-sb-proto-modal-title]').textContent = title;
            modal.querySelector('[data-sb-proto-modal-body]').innerHTML = bodyHtml;
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.add('is-open');
        },

        closeModal: function (root) {
            var modal = root.querySelector('[data-sb-proto-modal]');
            if (!modal) return;
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            modal.classList.remove('is-open');
        },

        setZoomLabel: function (root) {
            var label = root.querySelector('[data-sb-proto-zoom-label]');
            if (label) label.textContent = Proto.zoom + '%';
            var canvas = root.querySelector('.sb-hifi-editor__artboard');
            if (canvas) canvas.style.transform = 'scale(' + (Proto.zoom / 100) + ')';
        },

        setPreviewZoomLabel: function (root) {
            var label = root.querySelector('[data-sb-proto-preview-zoom]');
            if (label) label.textContent = Proto.previewZoom + '%';
        },

        setGridVisible: function (root, on) {
            Proto.gridOn = !!on;
            var canvasWrap = root.querySelector('.sb-hifi-editor__canvas-wrap');
            if (canvasWrap) canvasWrap.classList.toggle('is-grid-on', Proto.gridOn);
            var gridBtn = root.querySelector('[data-sb-proto="grid"]');
            if (gridBtn) gridBtn.classList.toggle('is-active', Proto.gridOn);
            root.querySelectorAll('[data-sb-proto-toggle="grid"] .sb-hifi-editor__toggle').forEach(function (toggle) {
                toggle.classList.toggle('is-on', Proto.gridOn);
            });
            var sheet = root.querySelector('.sb-hifi-editor__sheet');
            if (sheet) sheet.classList.toggle('is-grid-on', Proto.gridOn);
        },

        toggleGrid: function (root) {
            Proto.setGridVisible(root, !Proto.gridOn);
            Proto.toast(root, '그리드 ' + (Proto.gridOn ? '표시' : '숨김'));
        },

        pushHistory: function (root, action) {
            Proto.history.push(action);
            if (Proto.history.length > 20) Proto.history.shift();
        },

        enable: function (root) {
            if (!root || root.classList.contains('sb-hifi-editor--prototype')) return;
            root.classList.add('sb-hifi-editor--prototype');
            var gate = root.querySelector('[data-sb-prototype-gate]');
            if (gate) gate.classList.add('is-off');
            var annotateRoot = root.closest('#sbWfRoot');
            if (annotateRoot) annotateRoot.classList.remove('sb-wf-annotate');
            var annotateBtn = document.getElementById('sbWfToggleAnnotate');
            if (annotateBtn) {
                annotateBtn.classList.remove('sb-front-btn--primary');
                annotateBtn.textContent = '📌 영역 표시';
            }
            Proto.toast(root, '프로토타입 모드 — 모든 버튼을 눌러 동작을 확인하세요');
            var infoPanel = document.getElementById('sbWfInfoPanel');
            if (infoPanel) infoPanel.classList.remove('is-open');
            document.querySelectorAll('.sb-wf-zone.is-selected').forEach(function (z) {
                z.classList.remove('is-selected');
            });
        },

        disable: function (root) {
            if (!root) return;
            root.classList.remove('sb-hifi-editor--prototype');
            var gate = root.querySelector('[data-sb-prototype-gate]');
            if (gate) gate.classList.remove('is-off');
            window.sbEdCloseLayer(root);
            closePreview(root);
            Proto.closeModal(root);
            closeImport(root);
            Proto.setGridVisible(root, false);
        },

        handleProtoAction: function (root, action, el) {
            var actions = {
                undo: function () {
                    if (!Proto.history.length) return Proto.toast(root, '실행 취소할 작업이 없습니다');
                    Proto.toast(root, '실행 취소: ' + Proto.history.pop());
                },
                redo: function () { Proto.toast(root, '다시 실행 (프로토타입)'); },
                fit: function () { Proto.zoom = 100; Proto.setZoomLabel(root); Proto.toast(root, '화면에 맞춤 100%'); },
                'zoom-in': function () { Proto.zoom = Math.min(200, Proto.zoom + 10); Proto.setZoomLabel(root); Proto.toast(root, '줌 ' + Proto.zoom + '%'); },
                'zoom-out': function () { Proto.zoom = Math.max(50, Proto.zoom - 10); Proto.setZoomLabel(root); Proto.toast(root, '줌 ' + Proto.zoom + '%'); },
                grid: function () { Proto.toggleGrid(root); },
                save: function () { saveEditorDesign(root, false); },
                preview: function () { /* handled by onclick */ },
                export: function () {
                    Proto.modal(root, '편집기에서 출력', '<p>인쇄 주문·PDF 다운로드·용지 설정 확인 플로우로 이동합니다.</p><ul><li>용지: A4 · 3×4</li><li>예상 매수: 12장</li></ul>');
                },
                add: function () {
                    Proto.modal(root, '요소 추가', '<p>텍스트·이미지·도형·바코드·QR 등을 캔버스에 추가합니다.</p><div class="sb-ed-proto-chip-row"><span>텍스트</span><span>이미지</span><span>도형</span><span>바코드</span></div>');
                },
                text: function () { sbCanvasAddObject(root, 'text'); },
                image: function () { sbCanvasAddObject(root, 'image'); },
                background: function () { if (root._sbAssetSlide) root._sbAssetSlide.toggle('background'); },
                template: function () { if (root._sbAssetSlide) root._sbAssetSlide.toggle('template'); },
                clipart: function () { if (root._sbAssetSlide) root._sbAssetSlide.toggle('clipart'); },
                icon: function () { if (root._sbAssetSlide) root._sbAssetSlide.toggle('icon'); },
                shape: function () { sbCanvasAddObject(root, 'shape'); },
                table: function () { sbCanvasAddObject(root, 'table'); },
                barcode: function () { sbCanvasAddObject(root, 'barcode'); },
                master: function () { Proto.toast(root, '마스터 레이어 편집'); },
                data: function () { window.sbEdOpenDataImport(root); },
                layers: function () {
                    root.querySelectorAll('[data-sb-proto-props-panel]').forEach(function (p) {
                        p.hidden = p.getAttribute('data-sb-proto-props-panel') !== 'layers';
                    });
                    root.querySelectorAll('[data-sb-proto="props-tab"]').forEach(function (t) {
                        t.classList.toggle('is-active', t.getAttribute('data-sb-proto-tab') === 'layers');
                    });
                    Proto.toast(root, '레이어 패널로 전환');
                },
                settings: function () {
                    Proto.modal(root, '편집기 설정', '<p>단위(mm/px) · 스냅 · 안내선 · 자동 저장 간격 등을 설정합니다.</p>');
                },
                help: function () {
                    Proto.modal(root, '도움말', '<ul><li><strong>라벨·태그·템플릿·AI</strong> — 하단 메뉴에서 레이어 팝업</li><li><strong>미리보기</strong> — 인쇄용 시트 확인</li><li><strong>ESC</strong> — 열린 팝업 닫기</li></ul>');
                },
                'align-h': function () { Proto.toast(root, '선택 객체 가로 정렬'); },
                spacing: function () { Proto.toast(root, '객체 간격 균등 배분'); },
                resize: function () { Proto.toast(root, '선택 영역 크기 조절'); },
                group: function () { Proto.toast(root, '선택 객체 그룹화'); },
                lock: function () { Proto.toast(root, '객체 잠금 토글'); },
                duplicate: function () { Proto.toast(root, '선택 객체 복제'); Proto.pushHistory(root, '복제'); },
                delete: function () { Proto.toast(root, '선택 객체 삭제'); Proto.pushHistory(root, '삭제'); },
                canvas: function () { sbCanvasSelect(root, null); },
                'select-object': function () { Proto.toast(root, '「OLIVE OIL」텍스트 선택됨 · R-01 속성 패널 연동'); },
                'preview-continue': function () { window.sbEdClosePreview(root); Proto.toast(root, '편집기로 돌아갑니다'); },
                'preview-export': function () { Proto.modal(root, '인쇄 출력', '<p>12장 · 3×4 레이아웃으로 인쇄 주문을 진행합니다.</p>'); },
                'paper-size': function () { Proto.modal(root, '용지 크기', '<p>A4 · A3 · Letter · 사용자 정의</p>'); },
                'paper-layout': function () { Proto.modal(root, '레이아웃', '<p>3×4 · 2×5 · 4×6 등 배열 선택</p>'); },
                'label-size': function () { Proto.modal(root, '라벨 크기', '<p>93.0 × 93.0 mm · 규격 코드 100</p>'); },
                'page-prev': function () { Proto.toast(root, '이전 페이지 (1페이지)'); },
                'page-next': function () { Proto.toast(root, '다음 페이지 (1페이지)'); },
                'preview-zoom-in': function () { Proto.previewZoom = Math.min(200, Proto.previewZoom + 10); Proto.setPreviewZoomLabel(root); },
                'preview-zoom-out': function () { Proto.previewZoom = Math.max(50, Proto.previewZoom - 10); Proto.setPreviewZoomLabel(root); },
                'preview-fit': function () { Proto.previewZoom = 100; Proto.setPreviewZoomLabel(root); Proto.toast(root, '미리보기 화면 맞춤'); },
                'preview-layers': function () { Proto.toast(root, '미리보기 레이어 패널'); },
                'preview-actual': function () { Proto.toast(root, '실제 크기(100%) 보기'); }
            };
            if (actions[action]) actions[action]();
        },

        bindRoot: function (root) {
            if (!root || root.getAttribute('data-sb-prototype-bound') === '1') return;
            root.setAttribute('data-sb-prototype-bound', '1');

            var startBtn = root.querySelector('[data-sb-prototype-start]');
            if (startBtn) {
                startBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    Proto.enable(root);
                });
            }

            root.addEventListener('click', function (e) {
                if (!root.classList.contains('sb-hifi-editor--prototype')) return;

                var protoBtn = e.target.closest('[data-sb-proto]');
                if (protoBtn) {
                    var action = protoBtn.getAttribute('data-sb-proto');
                    if (action === 'preview' || action === 'props-tab' || action === 'toggle' ||
                        action === 'layer-item' || action === 'preview-mode' ||
                        protoBtn.getAttribute('data-sb-action') === 'pick-spec' ||
                        protoBtn.getAttribute('data-sb-action') === 'pick-template') {
                        /* handled below or by onclick */
                    } else if (action !== 'template-rail' && action !== 'background' && action !== 'template' &&
                        action !== 'clipart' && action !== 'icon') {
                        e.preventDefault();
                        e.stopPropagation();
                        Proto.handleProtoAction(root, action, protoBtn);
                    }
                }

                var pickSpec = e.target.closest('[data-sb-action="pick-spec"]');
                if (pickSpec && root.classList.contains('sb-hifi-editor--prototype')) {
                    var code = pickSpec.querySelector('strong');
                    var size = pickSpec.querySelector('.sb-ed-spec-card__meta span');
                    window.sbEdCloseLayer(pickSpec);
                    Proto.toast(root, '규격 선택: ' + (code ? code.textContent : '') + (size ? ' · ' + size.textContent : ''));
                    Proto.pushHistory(root, '규격 변경');
                    return;
                }

                var pickTpl = e.target.closest('[data-sb-action="pick-template"]');
                if (pickTpl && root.classList.contains('sb-hifi-editor--prototype')) {
                    var title = pickTpl.querySelector('.sb-ed-tpl-card__title');
                    window.sbEdCloseLayer(pickTpl);
                    Proto.toast(root, '템플릿 적용: ' + (title ? title.textContent : ''));
                    Proto.pushHistory(root, '템플릿 적용');
                    return;
                }

                var tabBtn = e.target.closest('.sb-ed-layer__tab');
                if (tabBtn && root.classList.contains('sb-hifi-editor--prototype')) {
                    e.preventDefault();
                    var tabs = tabBtn.closest('.sb-ed-layer__tabs');
                    if (tabs) tabs.querySelectorAll('.sb-ed-layer__tab').forEach(function (t) { t.classList.remove('is-active'); });
                    tabBtn.classList.add('is-active');
                    Proto.toast(root, '탭: ' + tabBtn.textContent.trim());
                }

                var subtab = e.target.closest('.sb-ed-layer__subtab');
                if (subtab && root.classList.contains('sb-hifi-editor--prototype')) {
                    e.preventDefault();
                    var group = subtab.closest('.sb-ed-layer__subtabs');
                    if (group) group.querySelectorAll('.sb-ed-layer__subtab').forEach(function (t) { t.classList.remove('is-active'); });
                    subtab.classList.add('is-active');
                    Proto.toast(root, subtab.textContent.trim());
                }

                var htag = e.target.closest('.sb-ed-layer__htag');
                if (htag && root.classList.contains('sb-hifi-editor--prototype')) {
                    e.preventDefault();
                    htag.closest('.sb-ed-layer__tagbar').querySelectorAll('.sb-ed-layer__htag').forEach(function (t) { t.classList.remove('is-active'); });
                    htag.classList.add('is-active');
                    Proto.toast(root, '해시태그 필터: ' + htag.textContent.trim());
                }

                var aiCard = e.target.closest('.sb-ed-layer__ai-card');
                if (aiCard && root.classList.contains('sb-hifi-editor--prototype')) {
                    e.preventDefault();
                    var strong = aiCard.querySelector('strong');
                    Proto.toast(root, (strong ? strong.textContent : 'AI') + ' 시작 (프로토타입)');
                }

                var aiExample = e.target.closest('.sb-ed-layer__ai-examples span');
                if (aiExample && root.classList.contains('sb-hifi-editor--prototype')) {
                    e.preventDefault();
                    Proto.toast(root, '프롬프트 예시: ' + aiExample.textContent.trim());
                }

                var importBtn = e.target.closest('.sb-ed-layer__import');
                if (importBtn && root.classList.contains('sb-hifi-editor--prototype')) {
                    e.preventDefault();
                    Proto.modal(root, '쿠팡 바코드 가져오기', '<p>쿠팡 상품 바코드를 스캔·입력해 라벨 데이터를 불러옵니다.</p>');
                }

                var propsTab = e.target.closest('[data-sb-proto="props-tab"]');
                if (propsTab && root.classList.contains('sb-hifi-editor--prototype')) {
                    e.preventDefault();
                    var tabName = propsTab.getAttribute('data-sb-proto-tab');
                    root.querySelectorAll('[data-sb-proto="props-tab"]').forEach(function (t) {
                        t.classList.toggle('is-active', t === propsTab);
                    });
                    root.querySelectorAll('[data-sb-proto-props-panel]').forEach(function (p) {
                        p.hidden = p.getAttribute('data-sb-proto-props-panel') !== tabName;
                    });
                }

                var toggleRow = e.target.closest('[data-sb-proto="toggle"]');
                if (toggleRow && root.classList.contains('sb-hifi-editor--prototype')) {
                    if (toggleRow.getAttribute('data-sb-proto-toggle') === 'grid') {
                        e.preventDefault();
                        Proto.toggleGrid(root);
                        return;
                    }
                    var toggle = toggleRow.querySelector('.sb-hifi-editor__toggle');
                    if (toggle) {
                        toggle.classList.toggle('is-on');
                        var label = toggleRow.querySelector('span');
                        Proto.toast(root, (label ? label.textContent : '옵션') + ': ' + (toggle.classList.contains('is-on') ? 'ON' : 'OFF'));
                    }
                }

                var layerItem = e.target.closest('[data-sb-proto="layer-item"]');
                if (layerItem && root.classList.contains('sb-hifi-editor--prototype')) {
                    e.preventDefault();
                    root.querySelectorAll('[data-sb-proto="layer-item"]').forEach(function (i) { i.classList.remove('is-selected'); });
                    layerItem.classList.add('is-selected');
                    Proto.toast(root, '레이어 선택: ' + layerItem.textContent.replace(/👁|🔒/g, '').trim());
                }

                var previewMode = e.target.closest('[data-sb-proto="preview-mode"]');
                if (previewMode && root.classList.contains('sb-hifi-editor--prototype')) {
                    e.preventDefault();
                    previewMode.parentElement.querySelectorAll('[data-sb-proto="preview-mode"]').forEach(function (m) { m.classList.remove('is-active'); });
                    previewMode.classList.add('is-active');
                    Proto.toast(root, previewMode.textContent.trim() + ' 모드');
                }

                var modalClose = e.target.closest('[data-sb-proto-modal-close], [data-sb-proto-modal-ok]');
                if (modalClose) {
                    e.preventDefault();
                    Proto.closeModal(root);
                }

                if (e.target === root.querySelector('[data-sb-proto-modal]')) {
                    Proto.closeModal(root);
                }
            });
        },

        bindAll: function () {
            document.querySelectorAll('[data-sb-editor-root]').forEach(Proto.bindRoot);
        },

        startInPage: function () {
            var root = document.querySelector('#sbWfRoot [data-sb-editor-root]') ||
                document.querySelector('[data-sb-editor-root]');
            if (!root) return;
            var wrap = document.getElementById('sbWfWrap');
            if (wrap && wrap.scrollIntoView) wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
            Proto.enable(root);
        }
    };

    window.SbEditorPrototype = Proto;

    if (!document.documentElement.getAttribute('data-sb-editor-esc')) {
        document.documentElement.setAttribute('data-sb-editor-esc', '1');
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            var openLayer = document.querySelector('[data-sb-layer-overlay].is-open');
            if (openLayer) {
                window.sbEdCloseLayer(openLayer);
                return;
            }
            var openPreview = document.querySelector('[data-sb-preview-overlay].is-open');
            if (openPreview) {
                window.sbEdClosePreview(openPreview);
                return;
            }
            var openSpecDetail = document.querySelector('[data-sb-spec-detail-overlay].is-open');
            if (openSpecDetail) {
                closeSpecDetail(editorRoot(openSpecDetail));
                return;
            }
            var openImport = document.querySelector('[data-sb-import-overlay].is-open');
            if (openImport) {
                closeImport(editorRoot(openImport));
                return;
            }
            var openDataImport = document.querySelector('[data-sb-data-import-overlay].is-open');
            if (openDataImport) {
                closeDataImport(editorRoot(openDataImport));
                return;
            }
            var openAssetSlide = document.querySelector('[data-sb-asset-slide].is-open');
            if (openAssetSlide) {
                window.sbEdCloseAssetSlide(openAssetSlide);
                return;
            }
            var openModal = document.querySelector('[data-sb-proto-modal].is-open');
            if (openModal) {
                Proto.closeModal(editorRoot(openModal));
            }
        });
    }

    var pageProtoBtn = document.getElementById('sbEdStartPrototype');
    if (pageProtoBtn) {
        pageProtoBtn.addEventListener('click', function () {
            Proto.startInPage();
        });
    }

    bindAllEditors();
})(window, document);
</script>
