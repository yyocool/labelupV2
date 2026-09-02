<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>참여자 관리</h1>
            <p>프로젝트 팀원을 추가하고 역할을 지정합니다.</p>
        </div>
        <button class="btn btn-primary" data-modal="addMemberModal">+ 참여자 추가</button>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>이름</th><th>이메일</th><th>역할</th><th>참여일</th><th>관리</th></tr></thead>
            <tbody>
            <?php foreach ($members as $m): ?>
            <tr>
                <td>
                    <span class="avatar" style="background:<?= e($m['avatar_color']) ?>;width:28px;height:28px;font-size:10px;display:inline-flex"><?= e(avatar_initials($m['name'])) ?></span>
                    <?= e($m['name']) ?>
                </td>
                <td><?= e($m['email']) ?></td>
                <td>
                    <form method="post" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="user_id" value="<?= $m['user_id'] ?>">
                        <select name="role" class="status-select" onchange="this.form.submit()">
                            <?php foreach (array('owner', 'pm', 'designer', 'developer', 'qa', 'viewer') as $r): ?>
                            <option value="<?= $r ?>" <?= $m['role']===$r?'selected':'' ?>><?= role_label($r) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td><?= e(substr($m['joined_at'], 0, 10)) ?></td>
                <td>
                    <?php if ($m['role'] !== 'owner'): ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('제거하시겠습니까?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="user_id" value="<?= $m['user_id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">제거</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="addMemberModal">
    <div class="modal">
        <div class="modal-header"><h3>참여자 추가</h3><button class="modal-close">&times;</button></div>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>사용자</label>
                <select name="user_id" class="form-control" required>
                    <?php foreach ($availableUsers as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['username']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>역할</label>
                <select name="role" class="form-control">
                    <?php foreach (array('pm', 'designer', 'developer', 'qa', 'viewer') as $r): ?>
                    <option value="<?= $r ?>"><?= role_label($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">추가</button>
        </form>
    </div>
</div>
