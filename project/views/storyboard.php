<div class="sb-studio<?= !empty($storyboardAdminMode) ? ' sb-studio--admin' : '' ?>">
    <?php if (!empty($storyboardAdminMode)): ?>
    <div class="sb-admin-toolbar">
        <a href="<?= e($storyboardBackUrl) ?>" class="btn btn-secondary btn-sm">← 목록으로</a>
        <?php if (!empty($storyboard)): ?>
        <div class="sb-admin-toolbar-meta">
            <span><?= storyboard_visibility_badge(isset($storyboard['visibility']) ? $storyboard['visibility'] : 'working') ?></span>
            <form method="post" class="sb-visibility-inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="set_visibility">
                <select name="visibility" class="form-control form-control-sm" onchange="this.form.submit()">
                    <?php foreach (StoryboardService::getVisibilityOptions() as $key => $opt): ?>
                    <option value="<?= e($key) ?>" <?= (isset($storyboard['visibility']) ? $storyboard['visibility'] : 'working') === $key ? 'selected' : '' ?>>
                        <?= e($opt['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="sb-studio-body">
    <!-- 좌측: 메뉴 트리 -->
    <aside class="sb-tree-panel">
        <div class="sb-tree-header">
            <span class="sb-tree-header-title">메뉴 구성</span>
            <span class="sb-tree-header-sub"><?= is_array($menus) ? count($menus) : 0 ?>개</span>
        </div>
        <div class="sb-tree-actions">
            <a href="<?= url('storyboard-pdf.php?scope=files') ?>" class="btn btn-primary btn-sm" title="스토리보드 전체 PDF로 저장" target="_blank" rel="noopener">⬇ PDF</a>
            <a href="<?= url('storyboard-pdf.php?scope=files&autoprint=1') ?>" class="btn btn-secondary btn-sm" title="인쇄 대화상자 바로 열기" target="_blank" rel="noopener">인쇄</a>
        </div>
        <div class="sb-tree-search">
            <input type="text" id="sbTreeSearch" class="form-control" placeholder="메뉴 검색...">
        </div>
        <div class="sb-tree-legend" aria-label="스토리보드 작업 상태">
            <span><i class="sb-tree-legend-dot sb-tree-legend-dot--ready"></i> 완료</span>
            <span><i class="sb-tree-legend-dot sb-tree-legend-dot--stub"></i> 준비중</span>
            <span><i class="sb-tree-legend-dot sb-tree-legend-dot--none"></i> 미작성</span>
        </div>
        <?php if (empty($menuTree)): ?>
        <div class="sb-tree-empty">등록된 메뉴가 없습니다</div>
        <?php else: ?>
        <ul class="sb-tree" id="sbMenuTree">
            <?= render_storyboard_menu_tree($menuTree, $menuId, $frameCounts, 0, isset($storyboardLinkBase) ? $storyboardLinkBase : null, isset($storyboardContentStatusMap) ? $storyboardContentStatusMap : array()) ?>
        </ul>
        <?php endif; ?>
    </aside>

    <!-- 우측: 화면 뷰어 -->
    <main class="sb-viewer-panel">
        <?php if (!$menu): ?>
        <div class="sb-viewer-empty sb-viewer-empty-minimal">
            <p style="color:var(--text-muted);font-size:14px">좌측에서 메뉴를 선택하세요.</p>
        </div>

        <?php elseif (!empty($storyboardFileExists)): ?>
        <div class="sb-viewer-topbar sb-viewer-topbar-compact">
            <div class="sb-viewer-title-wrap">
                <?php if (!empty($menu['menu_code'])): ?>
                <span class="sb-viewer-code"><?= e($menu['menu_code']) ?></span>
                <?php endif; ?>
                <h2 class="sb-viewer-title"><?= e($menu['title']) ?></h2>
            </div>
            <?php if (!empty($storyboard) && empty($storyboardAdminMode)): ?>
            <span class="sb-public-badge"><?= storyboard_visibility_badge(isset($storyboard['visibility']) ? $storyboard['visibility'] : 'working') ?></span>
            <?php endif; ?>
            <div class="btn-group">
                <a href="<?= url('storyboard-pdf.php?scope=files') ?>" class="btn btn-secondary btn-sm" title="스토리보드 전체 PDF" target="_blank" rel="noopener">PDF</a>
                <button type="button" class="btn btn-secondary btn-sm" id="sbCollabToggle" title="의견/이력">💬</button>
            </div>
        </div>

        <div class="sb-preview-wrap sb-preview-wrap--file">
            <div class="sb-preview-panel active">
                <div class="sb-device-frame">
                    <div class="sb-device-bar">
                        <span class="sb-device-dots"><i></i><i></i><i></i></span>
                        <span class="sb-device-url"><?= e($menu['url_path'] ? $menu['url_path'] : $menu['title']) ?></span>
                    </div>
                    <div class="sb-device-screen sb-device-screen--file">
                        <?php StoryboardFileService::render($menu, isset($storyboard) ? $storyboard : null, array(
                            'sbFsMenuTree' => $menuTree,
                            'sbFsMenuId' => $menuId,
                            'sbFsLinkBase' => isset($storyboardLinkBase) ? $storyboardLinkBase : url('storyboard.php'),
                            'sbFsContentStatusMap' => StoryboardFileService::getContentStatusMap($menus),
                        )); ?>
                    </div>
                </div>
                <div class="sb-frame-info sb-frame-info--file">
                    <p class="sb-file-edit-hint">
                        소스 파일: <code>storyboard/<?= e($menu['menu_code']) ?>.php</code>
                    </p>
                </div>
            </div>
        </div>

        <?php elseif (!$storyboardFileExists && !$storyboard): ?>
        <div class="sb-viewer-topbar sb-viewer-topbar-compact">
            <h2 class="sb-viewer-title"><?= e($menu['title']) ?></h2>
        </div>
        <div class="sb-viewer-empty sb-viewer-empty-minimal">
            <p style="color:var(--text-muted);font-size:14px">스토리보드 파일이 없습니다.</p>
            <?php if (!empty($menu['menu_code'])): ?>
            <p style="color:var(--text-muted);font-size:13px;margin-top:8px">
                <code>storyboard/<?= e($menu['menu_code']) ?>.php</code> 파일을 생성해 주세요.
            </p>
            <?php endif; ?>
        </div>

        <?php elseif (!$storyboard): ?>
        <div class="sb-viewer-topbar sb-viewer-topbar-compact">
            <h2 class="sb-viewer-title"><?= e($menu['title']) ?></h2>
        </div>
        <div class="sb-viewer-empty sb-viewer-empty-minimal">
            <p style="color:var(--text-muted);font-size:14px">등록된 스토리보드가 없습니다.</p>
            <?php if (!empty($canEditStoryboard)): ?>
            <a href="<?= admin_url('storyboards.php') ?>" class="btn btn-primary btn-sm" style="margin-top:12px">관리자에서 등록하기</a>
            <?php endif; ?>
        </div>

        <?php elseif (empty($frames)): ?>
        <div class="sb-viewer-topbar sb-viewer-topbar-compact">
            <h2 class="sb-viewer-title"><?= e($menu['title']) ?></h2>
            <div class="btn-group">
                <?php if (!empty($canEditStoryboard)): ?>
                <button class="btn btn-primary btn-sm" data-modal="frameModal">+ 화면</button>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary btn-sm sb-collab-toggle-btn" title="의견/이력">💬</button>
            </div>
        </div>
        <div class="sb-viewer-empty sb-viewer-empty-minimal">
            <?php if (!empty($canEditStoryboard)): ?>
            <button class="btn btn-primary btn-sm" data-modal="frameModal">+ 화면 추가</button>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <div class="sb-viewer-topbar sb-viewer-topbar-compact">
            <h2 class="sb-viewer-title"><?= e($menu['title']) ?></h2>
            <?php if (!empty($storyboard) && empty($storyboardAdminMode)): ?>
            <span class="sb-public-badge"><?= storyboard_visibility_badge(isset($storyboard['visibility']) ? $storyboard['visibility'] : 'working') ?></span>
            <?php endif; ?>
            <div class="btn-group">
                <?php if (!empty($canEditStoryboard)): ?>
                <button class="btn btn-primary btn-sm" data-modal="frameModal">+ 화면</button>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary btn-sm" id="sbCollabToggle" title="의견/이력">💬</button>
            </div>
        </div>

        <!-- 화면 탭 (썸네일 스트립) -->
        <div class="sb-frame-strip" id="sbFrameStrip">
            <?php foreach ($frames as $i => $frame):
                $isActive = ($frame['id'] == $activeFrameId);
            ?>
            <button type="button"
                    class="sb-frame-tab<?= $isActive ? ' active' : '' ?>"
                    data-frame-id="<?= $frame['id'] ?>"
                    data-index="<?= $i + 1 ?>">
                <span class="sb-frame-tab-num"><?= $i + 1 ?></span>
                <span class="sb-frame-tab-title"><?= e($frame['title']) ?></span>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- 화면 미리보기 -->
        <div class="sb-preview-wrap">
            <?php foreach ($frames as $i => $frame):
                $isActive = ($frame['id'] == $activeFrameId);
            ?>
            <div class="sb-preview-panel<?= $isActive ? ' active' : '' ?>" id="frame-panel-<?= $frame['id'] ?>" data-frame-id="<?= $frame['id'] ?>">
                <div class="sb-device-frame">
                    <div class="sb-device-bar">
                        <span class="sb-device-dots"><i></i><i></i><i></i></span>
                        <span class="sb-device-url"><?= e($menu['url_path'] ? $menu['url_path'] : $menu['title']) ?></span>
                    </div>
                    <div class="sb-device-screen">
                        <?php if (!empty($frame['image_path'])): ?>
                        <img src="<?= e($frame['image_path']) ?>" alt="<?= e($frame['title']) ?>" class="sb-screen-image">
                        <?php else: ?>
                        <div class="sb-wireframe">
                            <div class="sb-wf-header">
                                <div class="sb-wf-block sb-wf-block-lg"></div>
                                <div class="sb-wf-block sb-wf-block-sm"></div>
                            </div>
                            <div class="sb-wf-hero">
                                <div class="sb-wf-block sb-wf-block-xl"></div>
                                <div class="sb-wf-block sb-wf-block-md"></div>
                            </div>
                            <div class="sb-wf-grid">
                                <div class="sb-wf-card"><div class="sb-wf-block sb-wf-block-md"></div><div class="sb-wf-block sb-wf-block-xs"></div></div>
                                <div class="sb-wf-card"><div class="sb-wf-block sb-wf-block-md"></div><div class="sb-wf-block sb-wf-block-xs"></div></div>
                                <div class="sb-wf-card"><div class="sb-wf-block sb-wf-block-md"></div><div class="sb-wf-block sb-wf-block-xs"></div></div>
                            </div>
                            <div class="sb-wf-label"><?= e($frame['title']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sb-frame-info">
                    <div class="sb-frame-info-header">
                        <div>
                            <span class="sb-frame-num">Screen #<?= $i + 1 ?></span>
                            <h3><?= e($frame['title']) ?></h3>
                        </div>
                        <?php if (!empty($canEditStoryboard)): ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-secondary btn-sm sb-edit-frame"
                                    data-frame="<?= storyboard_json_frame($frame) ?>">수정</button>
                            <form method="post" style="display:inline" onsubmit="return confirm('이 화면을 삭제하시겠습니까?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_frame">
                                <input type="hidden" name="frame_id" value="<?= $frame['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">삭제</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($frame['description'])): ?>
                    <p class="sb-frame-desc"><?= nl2br(e($frame['description'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($frame['notes'])): ?>
                    <div class="sb-frame-notes">
                        <strong>메모</strong>
                        <p><?= nl2br(e($frame['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 이전/다음 네비 -->
        <div class="sb-frame-nav sb-frame-nav-compact">
            <button type="button" class="btn btn-secondary btn-sm" id="sbPrevFrame">← 이전 화면</button>
            <span id="sbFrameCounter"><?= storyboard_active_frame_number($frames, $activeFrameId) ?> / <?= count($frames) ?></span>
            <button type="button" class="btn btn-secondary btn-sm" id="sbNextFrame">다음 화면 →</button>
        </div>
        <?php endif; ?>

        <?php if ($menu && $storyboard && !empty($storyboardFileExists)): ?>
        <div class="sb-collab-wrap sb-collab-collapsed" id="sbCollabWrap">
        <?php include __DIR__ . '/storyboard_collab.php'; ?>
        </div>
        <?php elseif ($menu && $storyboard && empty($storyboardFileExists)): ?>
        <div class="sb-collab-wrap sb-collab-collapsed" id="sbCollabWrap">
        <?php include __DIR__ . '/storyboard_collab.php'; ?>
        </div>
        <?php endif; ?>
    </main>
    </div>
</div>

<?php if ($menu && !empty($canEditStoryboard)): ?>
<div class="modal-overlay" id="frameModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="frameModalTitle">화면 추가</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="post" id="frameForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" id="frameAction" value="add_frame">
            <input type="hidden" name="frame_id" id="frameId" value="">
            <div class="form-group">
                <label>화면명</label>
                <input type="text" name="title" id="frameTitle" class="form-control" required placeholder="예: 메인 화면, 로그인 폼">
            </div>
            <div class="form-group">
                <label>화면 설명</label>
                <textarea name="description" id="frameDesc" class="form-control" placeholder="화면 구성 및 UX 설명"></textarea>
            </div>
            <div class="form-group">
                <label>메모 / 요구사항</label>
                <textarea name="notes" id="frameNotes" class="form-control" placeholder="개발 시 참고사항"></textarea>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">저장</button>
                <button type="button" class="btn btn-secondary modal-close">취소</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="<?= asset('js/storyboard.js') ?>"></script>
