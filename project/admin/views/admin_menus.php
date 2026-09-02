<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>메뉴 관리</h1>
            <p>다단계 메뉴 등록 · 수정 · 삭제</p>
        </div>
        <div class="btn-group">
            <a href="<?= admin_url('seed-menus.php') ?>" class="btn btn-secondary btn-sm">📥 IA 시드</a>
            <button class="btn btn-primary" data-modal="menuModal">+ 메뉴 추가</button>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>메뉴코드</th><th>메뉴명</th><th>순서</th><th>진척</th><th>URL</th><th>관리</th></tr></thead>
            <tbody>
            <?php foreach ($menuList as $m): ?>
            <tr>
                <td><code class="menu-code-inline"><?= e(isset($m['menu_code']) ? $m['menu_code'] : '-') ?></code></td>
                <td><?= str_repeat('— ', $m['depth']) . e($m['title']) ?></td>
                <td><?= $m['sort_order'] ?></td>
                <td><?= (isset($m['progress_pct']) ? $m['progress_pct'] : 0) ?>%</td>
                <td><?= e(isset($m['url_path']) ? $m['url_path'] : '-') ?></td>
                <td class="table-actions">
                    <a href="<?= url('menu-detail.php?id=' . $m['id']) ?>" class="btn btn-secondary btn-sm">진행</a>
                    <a href="<?= url('storyboard.php?menu_id=' . $m['id']) ?>" class="btn btn-secondary btn-sm">SB</a>
                    <button class="btn btn-secondary btn-sm" onclick="editMenu(<?= json_html_attr($m) ?>)">수정</button>
                    <form method="post" style="display:inline" onsubmit="return confirm('삭제하시겠습니까?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">삭제</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="menuModal">
    <div class="modal">
        <div class="modal-header"><h3 id="menuModalTitle">메뉴 추가</h3><button class="modal-close">&times;</button></div>
        <form method="post" id="menuForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" id="menuAction" value="create">
            <input type="hidden" name="id" id="menuId" value="">
            <div class="form-group"><label>메뉴명</label><input type="text" name="title" id="menuTitle" class="form-control" required></div>
            <div class="form-row">
                <div class="form-group">
                    <label>메뉴코드</label>
                    <input type="text" id="menuCode" class="form-control" readonly placeholder="저장 시 자동 부여">
                </div>
                <div class="form-group"><label>정렬</label><input type="number" name="sort_order" id="menuSort" class="form-control" value="0"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>상위 메뉴</label>
                    <select name="parent_id" id="menuParent" class="form-control">
                        <option value="">— 최상위 —</option>
                        <?= render_menu_tree_options($menuTree) ?>
                    </select>
                </div>
                <div class="form-group"><label>URL</label><input type="text" name="url_path" id="menuUrl" class="form-control"></div>
            </div>
            <div class="form-group"><label>설명</label><textarea name="description" id="menuDesc" class="form-control"></textarea></div>
            <button type="submit" class="btn btn-primary">저장</button>
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
    document.getElementById('menuModal').classList.add('active');
}
</script>
