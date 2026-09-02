<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>이슈 관리</h1>
            <p>버그, 기능 요청, 개선 사항을 추적합니다.</p>
        </div>
        <button class="btn btn-primary" data-modal="issueModal">+ 이슈 등록</button>
    </div>
</div>

<div class="btn-group" style="margin-bottom:20px">
    <a href="<?= url('issues.php') ?>" class="btn btn-sm <?= !$filter ? 'btn-primary' : 'btn-secondary' ?>">전체</a>
    <a href="<?= url('issues.php?status=open') ?>" class="btn btn-sm <?= $filter==='open'?'btn-primary':'btn-secondary' ?>">열림</a>
    <a href="<?= url('issues.php?status=in_progress') ?>" class="btn btn-sm <?= $filter==='in_progress'?'btn-primary':'btn-secondary' ?>">진행중</a>
    <a href="<?= url('issues.php?status=resolved') ?>" class="btn btn-sm <?= $filter==='resolved'?'btn-primary':'btn-secondary' ?>">해결</a>
    <a href="<?= url('issues.php?status=closed') ?>" class="btn btn-sm <?= $filter==='closed'?'btn-primary':'btn-secondary' ?>">종료</a>
</div>

<div class="grid-2">
    <div class="card" style="grid-column: span 2">
        <?php if (empty($issues)): ?>
        <div class="empty-state"><p>등록된 이슈가 없습니다.</p></div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>유형</th>
                        <th>제목</th>
                        <th>메뉴</th>
                        <th>상태</th>
                        <th>우선순위</th>
                        <th>담당</th>
                        <th>등록일</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($issues as $issue): ?>
                <tr>
                    <td><?= issue_type_icon($issue['type']) ?></td>
                    <td><a href="<?= url('issue-detail.php?id=' . $issue['id']) ?>"><strong><?= e($issue['title']) ?></strong></a></td>
                    <td><?= e(isset($issue['menu_title']) ? $issue['menu_title'] : '-') ?></td>
                    <td><?= status_badge($issue['status']) ?></td>
                    <td><?= priority_badge($issue['priority']) ?></td>
                    <td><?= e(isset($issue['assignee_name']) ? $issue['assignee_name'] : '-') ?></td>
                    <td><?= time_ago($issue['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Kanban overview -->
<?php
$kanban = array('open'=>array(), 'in_progress'=>array(), 'resolved'=>array(), 'closed'=>array());
foreach ($issues as $issue) {
    if (isset($kanban[$issue['status']])) $kanban[$issue['status']][] = $issue;
}
?>
<div class="card" style="margin-top:20px">
    <div class="card-header"><h3>칸반 보드</h3></div>
    <div class="kanban-board">
        <?php foreach (array('open'=>'열림','in_progress'=>'진행중','resolved'=>'해결','closed'=>'종료') as $st=>$label): ?>
        <div class="kanban-col">
            <div class="kanban-col-header"><?= $label ?> (<?= count($kanban[$st]) ?>)</div>
            <?php foreach ($kanban[$st] as $item): ?>
            <a href="<?= url('issue-detail.php?id=' . $item['id']) ?>" class="kanban-card" style="display:block;color:inherit">
                <?= issue_type_icon($item['type']) ?> <?= e(mb_safe_substr($item['title'], 0, 30)) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal-overlay <?= $showNew ? 'active' : '' ?>" id="issueModal">
    <div class="modal">
        <div class="modal-header">
            <h3>이슈 등록</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>제목</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>유형</label>
                    <select name="type" class="form-control">
                        <option value="bug">버그</option>
                        <option value="feature">기능</option>
                        <option value="improvement">개선</option>
                        <option value="task">작업</option>
                        <option value="question">질문</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>우선순위</label>
                    <select name="priority" class="form-control">
                        <option value="low">낮음</option>
                        <option value="medium" selected>보통</option>
                        <option value="high">높음</option>
                        <option value="urgent">긴급</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>관련 메뉴</label>
                    <select name="menu_id" class="form-control">
                        <option value="">— 없음 —</option>
                        <?= render_menu_tree_options($menuTree, 0, $prefillMenuId ?: null) ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>담당자</label>
                    <select name="assignee_id" class="form-control">
                        <option value="">— 미지정 —</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= e($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>설명</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>
            <div class="form-group">
                <label>마감일</label>
                <input type="date" name="due_date" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">등록</button>
        </form>
    </div>
</div>
