<div class="sb-hifi-editor" data-sb-editor-root>
    <header class="sb-hifi-editor__topbar sb-wf-zone" data-zone-id="M-01">
        <span class="sb-wf-zone-label">M-01</span>
        <div class="sb-hifi-editor__file">
            <div class="sb-hifi-editor__file-icon">🏷</div>
            <div>
                <input type="text"
                    class="sb-hifi-editor__file-name-input"
                    data-sb-editor-file-name
                    value="올리브오일 라벨"
                    maxlength="80"
                    aria-label="디자인 제목">
                <div class="sb-hifi-editor__saved is-saved" data-sb-editor-save-status>● 저장됨</div>
            </div>
        </div>
        <div class="sb-hifi-editor__center-tools">
            <button type="button" class="sb-hifi-editor__tool-chip" data-sb-proto="undo" title="실행 취소">↶</button>
            <button type="button" class="sb-hifi-editor__tool-chip" data-sb-proto="redo" title="다시 실행">↷</button>
            <button type="button" class="sb-hifi-editor__tool-chip" data-sb-proto="fit" title="화면 맞춤">▢</button>
            <div class="sb-hifi-editor__zoom">
                <button type="button" data-sb-proto="zoom-out" title="축소">−</button>
                <span data-sb-proto-zoom-label>100%</span>
                <button type="button" data-sb-proto="zoom-in" title="확대">+</button>
            </div>
            <button type="button" class="sb-hifi-editor__tool-chip" data-sb-proto="grid" title="그리드">⊞</button>
        </div>
        <div class="sb-hifi-editor__top-actions">
            <button type="button" class="sb-hifi-editor__btn" data-sb-editor-save data-sb-proto="save">💾 저장하기</button>
            <button type="button" class="sb-hifi-editor__btn" data-sb-action="preview" data-sb-proto="preview" onclick="return sbEdOpenPreview(this);">👁 미리보기</button>
            <button type="button" class="sb-hifi-editor__btn sb-hifi-editor__btn--primary" data-sb-proto="export">⎙ 편집기에서 출력</button>
        </div>
    </header>

    <div class="sb-hifi-editor__body">
        <div class="sb-hifi-editor__workspace sb-wf-zone" data-zone-id="M-02">
            <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-02</span>

            <?php include __DIR__ . '/01-05-asset-slide-panel.php'; ?>

            <?php include __DIR__ . '/01-05-floating-tools.php'; ?>

            <div class="sb-hifi-editor__canvas-wrap" data-sb-proto="canvas">
                <div class="sb-hifi-editor__ruler-h"></div>
                <div class="sb-hifi-editor__ruler-v"></div>
                <div class="sb-hifi-editor__artboard" data-sb-artboard>
                    <div class="sb-hifi-editor__artboard-base" data-sb-proto="select-object">
                        <div class="sb-hifi-editor__label-brand">100% ORGANIC</div>
                        <div class="sb-hifi-editor__label-title">OLIVE<br>OIL</div>
                        <div class="sb-hifi-editor__label-sub">EXTRA VIRGIN</div>
                        <div class="sb-hifi-editor__label-leaf">🌿</div>
                        <span class="sb-hifi-editor__label-badge">COLD PRESSED</span>
                    </div>
                    <div class="sb-hifi-editor__canvas-objects" data-sb-canvas-objects aria-label="캔버스 객체"></div>
                </div>
            </div>
            <div class="sb-hifi-editor__float-bar sb-wf-zone" data-zone-id="M-04">
                <span class="sb-wf-zone-label">M-04</span>
                <button type="button" data-sb-proto="align-h" title="정렬">≡</button>
                <button type="button" data-sb-proto="spacing" title="간격">⇔</button>
                <button type="button" data-sb-proto="resize" title="크기">⤢</button>
                <button type="button" data-sb-proto="group" title="그룹">⊞</button>
                <button type="button" data-sb-proto="lock" title="잠금">🔒</button>
                <button type="button" data-sb-proto="duplicate" title="복제">⧉</button>
                <button type="button" data-sb-proto="delete" title="삭제">🗑</button>
            </div>
        </div>

        <aside class="sb-hifi-editor__props sb-wf-zone" data-zone-id="R-01" data-sb-props-panel data-sb-snap-id="tr">
            <span class="sb-wf-zone-label sb-wf-zone-label--purple">R-01</span>
            <div class="sb-hifi-editor__props-chrome" data-sb-props-drag-handle>
                <span class="sb-hifi-editor__props-grip" aria-hidden="true">⋮⋮</span>
                <span class="sb-hifi-editor__props-title">속성 / 레이어</span>
                <button type="button" class="sb-hifi-editor__props-min" data-sb-props-minimize title="접기" aria-label="패널 접기">—</button>
            </div>
            <div class="sb-hifi-editor__props-inner" data-sb-props-collapse>
                <div class="sb-hifi-editor__props-tabs">
                    <button type="button" class="sb-hifi-editor__props-tab is-active" data-sb-proto="props-tab" data-sb-proto-tab="props">속성</button>
                    <button type="button" class="sb-hifi-editor__props-tab" data-sb-proto="props-tab" data-sb-proto-tab="layers">레이어</button>
                </div>
                <div class="sb-hifi-editor__props-body" data-sb-proto-props-panel="props">
                <div class="sb-hifi-editor__field-group">
                    <div class="sb-hifi-editor__field-label">사이즈 &amp; 위치</div>
                    <div class="sb-hifi-editor__field-row">
                        <div class="sb-hifi-editor__field">X 24</div>
                        <div class="sb-hifi-editor__field">Y 48</div>
                        <div class="sb-hifi-editor__field">W 172</div>
                        <div class="sb-hifi-editor__field">H 36</div>
                    </div>
                </div>
                <div class="sb-hifi-editor__field-group">
                    <div class="sb-hifi-editor__field-label">텍스트</div>
                    <div class="sb-hifi-editor__field" style="margin-bottom:6px">Playfair Display · Bold · 36</div>
                    <div class="sb-hifi-editor__field">#2E4D2B</div>
                </div>
                <div class="sb-hifi-editor__field-group">
                    <div class="sb-hifi-editor__field-label">채우기</div>
                    <div class="sb-hifi-editor__field">#FFFFFF · 100%</div>
                </div>
                <div class="sb-hifi-editor__field-group">
                    <div class="sb-hifi-editor__toggle-row" data-sb-proto="toggle">
                        <span>테두리</span>
                        <span class="sb-hifi-editor__toggle is-on"></span>
                    </div>
                    <div class="sb-hifi-editor__field">#EDE6C8 · 1.5pt</div>
                </div>
                <div class="sb-hifi-editor__field-group">
                    <div class="sb-hifi-editor__toggle-row" data-sb-proto="toggle">
                        <span>그림자</span>
                        <span class="sb-hifi-editor__toggle"></span>
                    </div>
                </div>
            </div>
            <div class="sb-hifi-editor__props-body" data-sb-proto-props-panel="layers" hidden>
                <div class="sb-hifi-editor__layer-item" data-sb-proto="layer-item">👁 🔒 OLIVE OIL</div>
                <div class="sb-hifi-editor__layer-item" data-sb-proto="layer-item">👁 🔒 EXTRA VIRGIN</div>
                <div class="sb-hifi-editor__layer-item" data-sb-proto="layer-item">👁 🔒 올리브 이미지</div>
                <div class="sb-hifi-editor__layer-item" data-sb-proto="layer-item">👁 🔒 100% ORGANIC</div>
                </div>
            </div>
        </aside>

        <aside class="sb-hifi-editor__preview-panel sb-wf-zone" data-zone-id="R-02" data-sb-preview-panel data-sb-snap-id="stack-props">
            <span class="sb-wf-zone-label sb-wf-zone-label--purple">R-02</span>
            <div class="sb-hifi-editor__props-chrome" data-sb-preview-drag-handle>
                <span class="sb-hifi-editor__props-grip" aria-hidden="true">⋮⋮</span>
                <span class="sb-hifi-editor__props-title">미리보기</span>
                <button type="button" class="sb-hifi-editor__props-min" data-sb-preview-minimize title="접기" aria-label="패널 접기">—</button>
            </div>
            <div class="sb-hifi-editor__preview-panel-inner" data-sb-preview-collapse>
                <p class="sb-hifi-editor__preview-spec">
                    규격코드 <strong>TLF0061</strong> <span>(86.5×86.5 mm)</span>
                </p>

                <div class="sb-hifi-editor__preview-panel-stage sb-wf-zone" data-zone-id="R-02-A">
                    <div class="sb-hifi-editor__preview-panel-sheet" data-sb-preview-sheet>
                        <?php for ($pi = 0; $pi < 6; $pi++): ?>
                        <button type="button"
                            class="sb-hifi-editor__preview-panel-cell<?= $pi === 0 ? ' is-selected' : '' ?>"
                            data-sb-preview-cell="<?= (int) $pi ?>"
                            aria-pressed="<?= $pi === 0 ? 'true' : 'false' ?>">
                            <span class="sb-preview-cell__hint">텍스트를 입력하세요</span>
                            <span class="sb-preview-cell__qr" aria-hidden="true"></span>
                            <span class="sb-preview-cell__brand">Label Space</span>
                        </button>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="sb-hifi-editor__preview-pager">
                    <button type="button" class="sb-hifi-editor__preview-pager-btn" data-sb-preview-page="first" title="첫 페이지">«</button>
                    <button type="button" class="sb-hifi-editor__preview-pager-btn" data-sb-preview-page="prev" title="이전 페이지">‹</button>
                    <span class="sb-hifi-editor__preview-pager-label" data-sb-preview-page-label>페이지 1 / 3</span>
                    <button type="button" class="sb-hifi-editor__preview-pager-btn" data-sb-preview-page="next" title="다음 페이지">›</button>
                    <button type="button" class="sb-hifi-editor__preview-pager-btn" data-sb-preview-page="last" title="마지막 페이지">»</button>
                </div>

                <div class="sb-hifi-editor__preview-actions">
                    <div class="sb-hifi-editor__preview-action-wrap">
                        <button type="button" class="sb-hifi-editor__preview-action" data-sb-preview-copy-toggle aria-expanded="false" aria-haspopup="true">
                            <span class="sb-hifi-editor__preview-action-icon sb-hifi-editor__preview-action-icon--copy" aria-hidden="true"></span>
                            <span>라벨복사</span>
                        </button>
                        <div class="sb-hifi-editor__preview-copy-menu" data-sb-preview-copy-menu hidden role="menu" aria-label="라벨 복사">
                            <button type="button" class="sb-hifi-editor__preview-copy-item" data-sb-preview-copy-action="master-all" role="menuitem">
                                <span class="sb-preview-copy-icon sb-preview-copy-icon--master" aria-hidden="true">M</span>
                                <span>마스터로 전체 적용</span>
                            </button>
                            <hr class="sb-hifi-editor__preview-copy-sep" aria-hidden="true">
                            <button type="button" class="sb-hifi-editor__preview-copy-item" data-sb-preview-copy-action="copy-all" role="menuitem">
                                <span class="sb-preview-copy-icon sb-preview-copy-icon--grid" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
                                <span>전체로 복사</span>
                            </button>
                            <button type="button" class="sb-hifi-editor__preview-copy-item" data-sb-preview-copy-action="dup-page" role="menuitem">
                                <span class="sb-preview-copy-icon sb-preview-copy-icon--dup-page" aria-hidden="true"></span>
                                <span>페이지 복제</span>
                            </button>
                            <button type="button" class="sb-hifi-editor__preview-copy-item" data-sb-preview-copy-action="copy-next" role="menuitem">
                                <span class="sb-preview-copy-icon sb-preview-copy-icon--grid sb-preview-copy-icon--grid-next" aria-hidden="true"><i class="is-solid"></i><i></i><i></i><i></i></span>
                                <span>다음으로 복사</span>
                            </button>
                            <button type="button" class="sb-hifi-editor__preview-copy-item" data-sb-preview-copy-action="copy-rest" role="menuitem">
                                <span class="sb-preview-copy-icon sb-preview-copy-icon--grid sb-preview-copy-icon--grid-rest" aria-hidden="true"><i class="is-solid"></i><i></i><i></i><i></i></span>
                                <span>나머지로 복사</span>
                            </button>
                            <button type="button" class="sb-hifi-editor__preview-copy-item" data-sb-preview-copy-action="copy-row" role="menuitem">
                                <span class="sb-preview-copy-icon sb-preview-copy-icon--grid sb-preview-copy-icon--grid-row" aria-hidden="true"><i class="is-solid"></i><i class="is-solid"></i><i></i><i></i></span>
                                <span>행으로 복사</span>
                            </button>
                            <button type="button" class="sb-hifi-editor__preview-copy-item" data-sb-preview-copy-action="copy-col" role="menuitem">
                                <span class="sb-preview-copy-icon sb-preview-copy-icon--grid sb-preview-copy-icon--grid-col" aria-hidden="true"><i class="is-solid"></i><i></i><i class="is-solid"></i><i></i></span>
                                <span>열로 복사</span>
                            </button>
                            <hr class="sb-hifi-editor__preview-copy-sep" aria-hidden="true">
                            <button type="button" class="sb-hifi-editor__preview-copy-item" data-sb-preview-copy-action="copy-page" role="menuitem">
                                <span class="sb-preview-copy-icon sb-preview-copy-icon--grid" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
                                <span>페이지로 복사</span>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="sb-hifi-editor__preview-action" data-sb-preview-action="delete">
                        <span class="sb-hifi-editor__preview-action-icon sb-hifi-editor__preview-action-icon--delete" aria-hidden="true"></span>
                        <span>삭제</span>
                    </button>
                    <button type="button" class="sb-hifi-editor__preview-action" data-sb-preview-action="add">
                        <span class="sb-hifi-editor__preview-action-icon sb-hifi-editor__preview-action-icon--add" aria-hidden="true"></span>
                        <span>추가</span>
                    </button>
                </div>

                <div class="sb-hifi-editor__preview-foot">
                    <label class="sb-hifi-editor__preview-qty">
                        <span>라벨인쇄수량</span>
                        <input type="number" min="1" max="9999" value="1" data-sb-preview-print-qty>
                    </label>
                    <div class="sb-hifi-editor__preview-toggle-row" data-sb-preview-toggle-row>
                        <span>미리보기</span>
                        <button type="button" class="sb-hifi-editor__toggle" data-sb-preview-live-toggle aria-pressed="false" aria-label="미리보기 토글"></button>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <?php include __DIR__ . '/01-05-layer-popups.php'; ?>
    <?php include __DIR__ . '/01-05-import-popup.php'; ?>
    <?php include __DIR__ . '/01-05-data-import-popup.php'; ?>
    <?php include __DIR__ . '/01-05-spec-detail-popup.php'; ?>

    <button type="button" class="sb-hifi-editor__import-fab" data-sb-import-open onclick="return sbEdOpenImport(this);" title="가져오기">
        <span class="sb-hifi-editor__import-fab__icon" aria-hidden="true">↓</span>
        <span>가져오기</span>
    </button>

    <div class="sb-hifi-editor__preview-overlay" data-sb-preview-overlay aria-hidden="true" role="dialog" aria-label="인쇄 미리보기">
        <div class="sb-hifi-editor__preview-dialog">
            <header class="sb-hifi-editor__preview-top sb-wf-zone" data-zone-id="P-01">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">P-01</span>
                <button type="button" class="sb-hifi-editor__preview-back" data-sb-action="close-preview" onclick="return sbEdClosePreview(this);">‹</button>
                <div class="sb-hifi-editor__preview-title" data-sb-editor-preview-title>올리브오일 라벨 프리뷰</div>
                <div class="sb-hifi-editor__preview-mode">
                    <button type="button" data-sb-proto="preview-mode" data-sb-proto-mode="edit">편집</button>
                    <button type="button" class="is-active" data-sb-proto="preview-mode" data-sb-proto-mode="preview">미리보기</button>
                </div>
                <button type="button" class="sb-hifi-editor__btn sb-hifi-editor__btn--primary" data-sb-proto="preview-continue">편집기에서 계속 ▾</button>
                <button type="button" class="sb-hifi-editor__panel-close" data-sb-action="close-preview" aria-label="닫기" onclick="return sbEdClosePreview(this);">×</button>
            </header>
            <div class="sb-hifi-editor__preview-body">
                <aside class="sb-hifi-editor__paper-panel sb-wf-zone" data-zone-id="P-02">
                    <span class="sb-wf-zone-label">P-02</span>
                    <div class="sb-hifi-editor__field-label">용지 설정</div>
                    <div class="sb-hifi-editor__paper-field">
                        <label>용지 크기</label>
                        <button type="button" class="sb-hifi-editor__paper-select" data-sb-proto="paper-size">A4 · 210 × 297 mm</button>
                    </div>
                    <div class="sb-hifi-editor__paper-field">
                        <label>레이아웃</label>
                        <button type="button" class="sb-hifi-editor__paper-select" data-sb-proto="paper-layout">3 × 4</button>
                    </div>
                    <div class="sb-hifi-editor__paper-field">
                        <label>라벨 크기</label>
                        <button type="button" class="sb-hifi-editor__paper-select" data-sb-proto="label-size">93.0 × 93.0 mm</button>
                    </div>
                    <div class="sb-hifi-editor__paper-field">
                        <label>여백</label>
                        <div class="sb-hifi-editor__paper-grid">
                            <div class="sb-hifi-editor__field">상 12</div>
                            <div class="sb-hifi-editor__field">하 12</div>
                            <div class="sb-hifi-editor__field">좌 10</div>
                            <div class="sb-hifi-editor__field">우 10</div>
                        </div>
                    </div>
                    <div class="sb-hifi-editor__paper-field">
                        <label>간격</label>
                        <div class="sb-hifi-editor__paper-grid">
                            <div class="sb-hifi-editor__field">가로 3.0</div>
                            <div class="sb-hifi-editor__field">세로 3.0</div>
                        </div>
                    </div>
                    <div class="sb-hifi-editor__toggle-row" data-sb-proto="toggle"><span>재단선 표시</span><span class="sb-hifi-editor__toggle is-on"></span></div>
                    <div class="sb-hifi-editor__paper-summary">
                        예상 인쇄 매수: 12장 (1페이지)<br>
                        총 라벨 수: 12개
                    </div>
                    <button type="button" class="sb-hifi-editor__btn sb-hifi-editor__btn--primary" style="width:100%;justify-content:center" data-sb-proto="preview-export">편집기에서 출력</button>
                </aside>
                <div class="sb-hifi-editor__preview-canvas sb-wf-zone" data-zone-id="P-03">
                    <span class="sb-wf-zone-label sb-wf-zone-label--purple">P-03</span>
                    <div class="sb-hifi-editor__page-nav">
                        <button type="button" data-sb-proto="page-prev">‹</button>
                        <span>1 / 1 페이지</span>
                        <button type="button" data-sb-proto="page-next">›</button>
                    </div>
                    <div class="sb-hifi-editor__sheet">
                        <?php for ($i = 0; $i < 12; $i++): ?>
                        <div class="sb-hifi-editor__mini-label">
                            <span>100% ORGANIC</span>
                            <strong>OLIVE OIL</strong>
                            <span>EXTRA VIRGIN</span>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div class="sb-hifi-editor__page-nav sb-hifi-editor__page-nav--tools">
                        <button type="button" data-sb-proto="preview-zoom-out">−</button>
                        <span data-sb-proto-preview-zoom>100%</span>
                        <button type="button" data-sb-proto="preview-zoom-in">+</button>
                        <button type="button" data-sb-proto="preview-fit">⊞</button>
                        <button type="button" data-sb-proto="preview-layers">☰</button>
                        <button type="button" data-sb-proto="preview-actual">실제 크기</button>
                    </div>
                </div>
                <aside class="sb-hifi-editor__preview-side sb-wf-zone" data-zone-id="P-04">
                    <span class="sb-wf-zone-label">P-04</span>
                    <div class="sb-hifi-editor__field-label">레이어</div>
                    <div class="sb-hifi-editor__layer-item">👁 🔒 OLIVE OIL</div>
                    <div class="sb-hifi-editor__layer-item">👁 🔒 EXTRA VIRGIN</div>
                    <div class="sb-hifi-editor__layer-item">👁 🔒 올리브 이미지</div>
                    <div class="sb-hifi-editor__field-label" style="margin-top:12px">프리뷰 옵션</div>
                    <div class="sb-hifi-editor__toggle-row" data-sb-proto="toggle"><span>배경색</span><span class="sb-hifi-editor__toggle is-on"></span></div>
                    <div class="sb-hifi-editor__toggle-row" data-sb-proto="toggle" data-sb-proto-toggle="grid"><span>그리드</span><span class="sb-hifi-editor__toggle"></span></div>
                    <div class="sb-hifi-editor__toggle-row" data-sb-proto="toggle"><span>눈금자</span><span class="sb-hifi-editor__toggle"></span></div>
                    <div class="sb-hifi-editor__toggle-row" data-sb-proto="toggle"><span>안전 영역</span><span class="sb-hifi-editor__toggle"></span></div>
                    <div class="sb-hifi-editor__help-box">
                        💡 인쇄 시 모니터와 실제 색상이 다를 수 있습니다. 시안 확인 후 인쇄해 주세요.
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <div class="sb-ed-prototype-gate" data-sb-prototype-gate>
        <div class="sb-ed-prototype-gate__backdrop"></div>
        <button type="button" class="sb-ed-prototype-gate__btn" data-sb-prototype-start>
            <span class="sb-ed-prototype-gate__icon" aria-hidden="true">▶</span>
            <strong>프로토타입</strong>
        </button>
    </div>

    <div class="sb-ed-proto-modal" data-sb-proto-modal hidden aria-hidden="true" role="dialog">
        <div class="sb-ed-proto-modal__dialog">
            <button type="button" class="sb-ed-proto-modal__close" data-sb-proto-modal-close aria-label="닫기">×</button>
            <h4 class="sb-ed-proto-modal__title" data-sb-proto-modal-title></h4>
            <div class="sb-ed-proto-modal__body" data-sb-proto-modal-body></div>
            <div class="sb-ed-proto-modal__actions">
                <button type="button" class="sb-hifi-editor__btn sb-hifi-editor__btn--primary" data-sb-proto-modal-ok>확인</button>
            </div>
        </div>
    </div>
    <div class="sb-ed-proto-toast-wrap" data-sb-proto-toast-wrap aria-live="polite"></div>
</div>

<?php include __DIR__ . '/01-05-editor-init.inline.php'; ?>
