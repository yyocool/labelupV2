<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1><?= issue_type_icon($issue['type']) ?> <?= e($issue['title']) ?></h1>
            <p>#<?= $issue['id'] ?> · <?= e(isset($issue['reporter_name']) ? $issue['reporter_name'] : '알 수 없음') ?> · <?= time_ago($issue['created_at']) ?></p>
        </div>
        <a href="<?= url('issues.php') ?>" class="btn btn-secondary btn-sm">← 목록</a>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <div class="form-group">
                <label>제목</label>
                <input type="text" name="title" class="form-control" value="<?= e($issue['title']) ?>" required>
            </div>
            <div class="form-group">
                <label>설명</label>
                <textarea name="description" class="form-control" rows="6"><?= e(isset($issue['description']) ? $issue['description'] : '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>상태</label>
                    <select name="status" class="form-control">
                        <?php foreach (array('open'=>'열림','in_progress'=>'진행중','resolved'=>'해결','closed'=>'종료','wont_fix'=>'수정안함') as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= $issue['status']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>유형</label>
                    <select name="type" class="form-control">
                        <?php foreach (array('bug'=>'버그','feature'=>'기능','improvement'=>'개선','task'=>'작업','question'=>'질문') as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= $issue['type']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>우선순위</label>
                    <select name="priority" class="form-control">
                        <?php foreach (array('low','medium','high','urgent') as $p): ?>
                        <option value="<?= $p ?>" <?= $issue['priority']===$p?'selected':'' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>담당자</label>
                    <select name="assignee_id" class="form-control">
                        <option value="">— 미지정 —</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (isset($issue['assignee_id']) ? $issue['assignee_id'] : '')==$u['id']?'selected':'' ?>><?= e($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>관련 메뉴</label>
                    <select name="menu_id" class="form-control">
                        <option value="">— 없음 —</option>
                        <?= render_menu_tree_options($menuTree, 0, $issue['menu_id']) ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>마감일</label>
                    <input type="date" name="due_date" class="form-control" value="<?= e(isset($issue['due_date']) ? $issue['due_date'] : '') ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">저장</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h3>댓글 (<?= count($comments) ?>)</h3></div>
        <ul class="comment-list">
            <?php foreach ($comments as $c): ?>
            <li class="comment-item">
                <span class="avatar" style="background:<?= e($c['avatar_color']) ?>;width:28px;height:28px;font-size:10px"><?= e(avatar_initials($c['user_name'])) ?></span>
                <div class="comment-body">
                    <span class="comment-author"><?= e($c['user_name']) ?></span>
                    <span class="comment-time"><?= time_ago($c['created_at']) ?></span>
                    <div class="comment-text"><?= nl2br(e($c['content'])) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <form method="post" style="margin-top:16px">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="comment">
            <div class="form-group">
                <textarea name="content" class="form-control" rows="3" placeholder="댓글을 입력하세요..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">댓글 등록</button>
        </form>
    </div>
</div>
