<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>사용자 관리</h1>
            <p>시스템 사용자 계정 관리</p>
        </div>
        <button class="btn btn-primary" data-modal="userModal">+ 사용자 추가</button>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>이름</th><th>아이디</th><th>이메일</th><th>역할</th><th>상태</th><th>최근 로그인</th><th>관리</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <span class="avatar" style="background:<?= e($u['avatar_color']) ?>;width:28px;height:28px;font-size:10px;display:inline-flex"><?= e(avatar_initials($u['name'])) ?></span>
                    <?= e($u['name']) ?>
                </td>
                <td><?= e($u['username']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= role_label($u['role']) ?></td>
                <td><?= $u['is_active'] ? '<span class="badge badge-green">활성</span>' : '<span class="badge badge-gray">비활성</span>' ?></td>
                <td><?= $u['last_login_at'] ? time_ago($u['last_login_at']) : '-' ?></td>
                <td>
                    <form method="post" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn btn-secondary btn-sm"><?= $u['is_active'] ? '비활성화' : '활성화' ?></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="userModal">
    <div class="modal">
        <div class="modal-header"><h3>사용자 추가</h3><button class="modal-close">&times;</button></div>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="form-group"><label>이름</label><input type="text" name="name" class="form-control" required></div>
            <div class="form-group"><label>아이디</label><input type="text" name="username" class="form-control" required pattern="[a-zA-Z0-9_]+" title="영문, 숫자, 밑줄만 사용"></div>
            <div class="form-group"><label>이메일</label><input type="email" name="email" class="form-control" required></div>
            <div class="form-group"><label>비밀번호</label><input type="password" name="password" class="form-control" required minlength="6"></div>
            <div class="form-row">
                <div class="form-group">
                    <label>역할</label>
                    <select name="role" class="form-control">
                        <?php foreach (array('admin', 'pm', 'designer', 'developer', 'qa', 'viewer') as $r): ?>
                        <option value="<?= $r ?>"><?= role_label($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>연락처</label><input type="text" name="phone" class="form-control"></div>
            </div>
            <button type="submit" class="btn btn-primary">등록</button>
        </form>
    </div>
</div>
