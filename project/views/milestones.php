<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>마일스톤</h1>
            <p>프로젝트 주요 일정 및 목표를 관리합니다.</p>
        </div>
        <?php if (is_admin()): ?>
        <button class="btn btn-primary" data-modal="milestoneModal">+ 마일스톤 추가</button>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <?php if (empty($milestones)): ?>
    <div class="empty-state"><p>등록된 마일스톤이 없습니다.</p></div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>마일스톤</th><th>설명</th><th>마감일</th><th>상태</th><?php if(is_admin()): ?><th>관리</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($milestones as $ms):
                $isOverdue = $ms['status'] !== 'completed' && strtotime($ms['due_date']) < time();
            ?>
            <tr>
                <td><strong><?= e($ms['title']) ?></strong></td>
                <td><?= e(isset($ms['description']) ? $ms['description'] : '-') ?></td>
                <td style="<?= $isOverdue ? 'color:var(--danger)' : '' ?>"><?= e($ms['due_date']) ?></td>
                <td><?= status_badge($isOverdue && $ms['status']==='upcoming' ? 'overdue' : $ms['status']) ?></td>
                <?php if (is_admin()): ?>
                <td>
                    <form method="post" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" value="<?= $ms['id'] ?>">
                        <select name="status" class="status-select" onchange="this.form.submit()">
                            <?php foreach (array('upcoming','in_progress','completed','overdue') as $s): ?>
                            <option value="<?= $s ?>" <?= $ms['status']===$s?'selected':'' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php if (is_admin()): ?>
<div class="modal-overlay" id="milestoneModal">
    <div class="modal">
        <div class="modal-header"><h3>마일스톤 추가</h3><button class="modal-close">&times;</button></div>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="form-group"><label>제목</label><input type="text" name="title" class="form-control" required></div>
            <div class="form-group"><label>설명</label><textarea name="description" class="form-control"></textarea></div>
            <div class="form-group"><label>마감일</label><input type="date" name="due_date" class="form-control" required></div>
            <button type="submit" class="btn btn-primary">등록</button>
        </form>
    </div>
</div>
<?php endif; ?>
