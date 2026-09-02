<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>메뉴 구성도</h1>
            <p>다단계 메뉴 구조 및 진행상황을 한눈에 확인합니다.</p>
        </div>
        <div class="btn-group">
            <div class="menu-view-tabs">
                <a href="<?= url('menus.php?view=list') ?>"
                   class="menu-view-tab<?= $menuView === 'list' ? ' active' : '' ?>">☰ 목록</a>
                <a href="<?= url('menus.php?view=tree') ?>"
                   class="menu-view-tab<?= $menuView === 'tree' ? ' active' : '' ?>">🌳 트리</a>
            </div>
            <a href="<?= url('menus.php?print=1' . ($menuView === 'tree' ? '&view=tree' : '')) ?>" class="btn btn-primary btn-sm" target="_blank" rel="noopener">PDF로 저장</a>
            <?php if (is_admin()): ?>
            <button class="btn btn-primary" data-modal="menuModal">+ 메뉴 추가</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (empty($menuTree)): ?>
<div class="card">
    <div class="empty-state">
        <div class="empty-state-icon">📋</div>
        <h3>등록된 메뉴가 없습니다</h3>
        <p>관리자 페이지 또는 위 버튼으로 메뉴를 추가해 주세요.</p>
    </div>
</div>
<?php elseif ($menuView === 'tree'): ?>
<div class="menu-structure-wrap card">
    <div class="menu-structure-toolbar">
        <span class="text-muted" style="font-size:13px">조직도 형태로 메뉴 계층을 확인합니다. ▾ 버튼으로 하위 메뉴를 접을 수 있습니다.</span>
        <div class="btn-group">
            <button type="button" class="btn btn-secondary btn-sm" id="menuTreeExpandAll">전체 펼치기</button>
            <button type="button" class="btn btn-secondary btn-sm" id="menuTreeCollapseAll">전체 접기</button>
        </div>
    </div>
    <div class="menu-org-chart">
        <?php render_menu_tree_structure($menuTree); ?>
    </div>
</div>
<?php else: ?>
<div class="menu-tree-view">
    <?php render_menu_list_view($menuTree); ?>
</div>
<?php endif; ?>

<?php if (is_admin()): ?>
<div class="modal-overlay" id="menuModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="menuModalTitle">메뉴 추가</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="post" id="menuForm">
            <?= csrf_field() ?>
            <input type="hidden" name="return_view" value="<?= e($menuView) ?>">
            <input type="hidden" name="action" id="menuAction" value="create">
            <input type="hidden" name="id" id="menuId" value="">
            <div class="form-group">
                <label>메뉴명</label>
                <input type="text" name="title" id="menuTitle" class="form-control" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>메뉴코드</label>
                    <input type="text" id="menuCode" class="form-control" readonly placeholder="저장 시 자동 부여">
                </div>
                <div class="form-group">
                    <label>정렬 순서</label>
                    <input type="number" name="sort_order" id="menuSort" class="form-control" value="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>상위 메뉴</label>
                    <select name="parent_id" id="menuParent" class="form-control">
                        <option value="">— 최상위 —</option>
                        <?= render_menu_tree_options($menuTree) ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>URL 경로</label>
                    <input type="text" name="url_path" id="menuUrl" class="form-control" placeholder="/example/page">
                </div>
            </div>
            <div class="form-group">
                <label>설명</label>
                <textarea name="description" id="menuDesc" class="form-control"></textarea>
            </div>
            <div class="btn-group" style="justify-content:space-between;width:100%">
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">저장</button>
                    <button type="button" class="btn btn-secondary modal-close">취소</button>
                </div>
                <button type="button" class="btn btn-danger" id="menuDeleteBtn" hidden title="하위 메뉴 포함 삭제">삭제</button>
            </div>
        </form>
    </div>
</div>
<script>
function editMenu(item) {
    document.getElementById('menuModalTitle').textContent = '메뉴 수정';
    document.getElementById('menuAction').value = 'update';
    document.getElementById('menuId').value = item.id;
    document.getElementById('menuTitle').value = item.title;
    document.getElementById('menuCode').value = item.menu_code || '';
    document.getElementById('menuParent').value = item.parent_id || '';
    document.getElementById('menuSort').value = item.sort_order || 0;
    document.getElementById('menuUrl').value = item.url_path || '';
    document.getElementById('menuDesc').value = item.description || '';
    var delBtn = document.getElementById('menuDeleteBtn');
    if (delBtn) {
        delBtn.hidden = false;
        delBtn.setAttribute('data-id', item.id);
        delBtn.setAttribute('data-title', item.title || '');
    }
    document.getElementById('menuModal').classList.add('active');
}
function resetMenuForm() {
    document.getElementById('menuModalTitle').textContent = '메뉴 추가';
    document.getElementById('menuAction').value = 'create';
    document.getElementById('menuId').value = '';
    document.getElementById('menuCode').value = '';
    var delBtn = document.getElementById('menuDeleteBtn');
    if (delBtn) {
        delBtn.hidden = true;
        delBtn.removeAttribute('data-id');
    }
}
document.getElementById('menuForm').addEventListener('reset', resetMenuForm);
document.querySelectorAll('#menuModal .modal-close, [data-modal="menuModal"]').forEach(function (el) {
    el.addEventListener('click', function () {
        if (el.getAttribute('data-modal') === 'menuModal') {
            resetMenuForm();
            document.getElementById('menuForm').reset();
            document.getElementById('menuAction').value = 'create';
        }
    });
});
(function () {
    var delBtn = document.getElementById('menuDeleteBtn');
    if (!delBtn) return;
    delBtn.addEventListener('click', function () {
        var id = delBtn.getAttribute('data-id');
        var title = delBtn.getAttribute('data-title') || '';
        if (!id) return;
        if (!confirm('「' + title + '」 메뉴를 삭제할까요?\n하위 메뉴가 있으면 함께 삭제됩니다.')) return;
        var form = document.createElement('form');
        form.method = 'post';
        form.action = '';
        form.style.display = 'none';
        function addHidden(name, value) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        }
        addHidden('_csrf', <?= json_encode(csrf_token(), JSON_UNESCAPED_UNICODE) ?>);
        addHidden('return_view', <?= json_encode($menuView, JSON_UNESCAPED_UNICODE) ?>);
        addHidden('action', 'delete');
        addHidden('id', id);
        document.body.appendChild(form);
        form.submit();
    });
})();
</script>
<?php endif; ?>

<?php if ($menuView === 'tree' && !empty($menuTree)): ?>
<script src="<?= asset('js/menus.js') ?>"></script>
<?php endif; ?>
