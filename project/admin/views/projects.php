<div class="page-header">
    <h1>프로젝트 설정</h1>
    <p>프로젝트 기본 정보 및 환경 설정</p>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>프로젝트 정보</h3></div>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-group"><label>프로젝트명</label><input type="text" name="name" class="form-control" value="<?= e($project['name']) ?>" required></div>
            <div class="form-group"><label>클라이언트</label><input type="text" name="client_name" class="form-control" value="<?= e(isset($project['client_name']) ? $project['client_name'] : '') ?>"></div>
            <div class="form-group"><label>설명</label><textarea name="description" class="form-control"><?= e(isset($project['description']) ? $project['description'] : '') ?></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>시작일</label><input type="date" name="start_date" class="form-control" value="<?= e(isset($project['start_date']) ? $project['start_date'] : '') ?>"></div>
                <div class="form-group"><label>종료일</label><input type="date" name="end_date" class="form-control" value="<?= e(isset($project['end_date']) ? $project['end_date'] : '') ?>"></div>
            </div>
            <div class="form-group">
                <label>상태</label>
                <select name="status" class="form-control">
                    <?php foreach (array('planning', 'active', 'review', 'completed', 'on_hold') as $s): ?>
                    <option value="<?= $s ?>" <?= (isset($project['status']) ? $project['status'] : '')===$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">저장</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h3>환경 설정</h3></div>
        <p style="margin-bottom:12px;color:var(--text-secondary)">현재 DB 환경: <strong><?= e($env) ?></strong></p>
        <ul style="color:var(--text-secondary);font-size:13px;line-height:2">
            <li><code>config/app.php</code> — <code>environment</code> 값 변경</li>
            <li><code>local</code> → <code>config/database.local.php</code></li>
            <li><code>remote</code> → <code>config/database.remote.php</code></li>
            <li>또는 서버 환경변수 <code>LABELUP_ENV=remote</code></li>
        </ul>
        <div style="margin-top:20px;padding:16px;background:var(--bg-subtle);border-radius:var(--radius-sm)">
            <strong>로컬 기본 설정</strong>
            <pre style="font-size:12px;margin-top:8px;color:var(--text-secondary)">host: localhost
user: labelup
db: labelup</pre>
        </div>
    </div>
</div>
