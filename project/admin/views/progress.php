<div class="page-header">
    <h1>진행 관리</h1>
    <p>프로젝트 진행 단계 설정 및 메뉴별 스토리보드 · 디자인 · 퍼블리싱 · 코딩 · 검수 현황</p>
</div>

<?php
$phaseMode = isset($project['phase_mode']) ? $project['phase_mode'] : 'auto';
$selectedPhase = isset($project['current_phase']) ? $project['current_phase'] : (isset($phaseTracker['current_key']) ? $phaseTracker['current_key'] : 'planning');
?>

<div class="grid-2 admin-phase-layout">
    <div class="card admin-phase-card">
        <div class="card-header">
            <h3>프로젝트 진행 단계</h3>
            <?php if ($phaseMode === 'manual'): ?>
            <span class="badge badge-blue">수동 지정</span>
            <?php else: ?>
            <span class="badge">자동</span>
            <?php endif; ?>
        </div>

        <form method="post" id="phaseSettingsForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_phase">
            <input type="hidden" name="current_phase" id="currentPhaseInput" value="<?= e($selectedPhase) ?>">

            <div class="admin-phase-mode">
                <label class="admin-phase-mode-option">
                    <input type="radio" name="phase_mode" value="auto" <?= $phaseMode === 'auto' ? 'checked' : '' ?>>
                    <span>
                        <strong>자동</strong>
                        <small>메뉴별 진행 데이터를 기준으로 현재 단계를 자동 계산합니다.</small>
                    </span>
                </label>
                <label class="admin-phase-mode-option">
                    <input type="radio" name="phase_mode" value="manual" <?= $phaseMode === 'manual' ? 'checked' : '' ?>>
                    <span>
                        <strong>수동 지정</strong>
                        <small>아래에서 현재 진행 단계를 직접 선택합니다.</small>
                    </span>
                </label>
            </div>

            <div class="admin-phase-picker <?= $phaseMode === 'manual' ? '' : 'is-disabled' ?>" id="phasePicker">
                <?php foreach ($phaseOptions as $key => $opt): ?>
                <button type="button"
                    class="admin-phase-option <?= $selectedPhase === $key ? 'is-selected' : '' ?>"
                    data-phase="<?= e($key) ?>"
                    <?= $phaseMode !== 'manual' ? 'disabled' : '' ?>>
                    <span class="admin-phase-option-icon"><?= $opt['icon'] ?></span>
                    <span class="admin-phase-option-label"><?= e($opt['label']) ?></span>
                    <?php if ($selectedPhase === $key && $phaseMode === 'manual'): ?>
                    <span class="admin-phase-option-now">NOW</span>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="admin-phase-actions">
                <button type="submit" class="btn btn-primary">진행 단계 저장</button>
            </div>
        </form>
    </div>

    <div class="card admin-phase-preview-card">
        <div class="card-header"><h3>사이드바 미리보기</h3></div>
        <p class="admin-phase-preview-note">저장 후 메인 화면 사이드바에 아래와 같이 표시됩니다.</p>
        <div class="admin-phase-preview-wrap">
            <?= render_phase_tracker($phaseTracker) ?>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3>프로젝트 공통 단계</h3></div>
    <form method="post" style="padding:16px">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_db_design">
        <div class="form-row" style="align-items:flex-end">
            <div class="form-group" style="flex:0 0 200px">
                <label>DB설계 상태</label>
                <?php $dbStatus = isset($project['db_design_status']) ? $project['db_design_status'] : 'pending'; ?>
                <select name="db_design_status" class="form-control">
                    <option value="pending" <?= $dbStatus === 'pending' ? 'selected' : '' ?>>대기</option>
                    <option value="in_progress" <?= $dbStatus === 'in_progress' ? 'selected' : '' ?>>진행중</option>
                    <option value="done" <?= $dbStatus === 'done' ? 'selected' : '' ?>>완료</option>
                    <option value="na" <?= $dbStatus === 'na' ? 'selected' : '' ?>>해당없음</option>
                </select>
            </div>
            <div class="form-group" style="flex:1">
                <label>DB설계 비고</label>
                <input type="text" name="db_design_note" class="form-control" value="<?= e(isset($project['db_design_note']) ? $project['db_design_note'] : '') ?>" placeholder="스키마·ERD·마이그레이션 메모">
            </div>
            <div class="form-group" style="flex:0 0 auto">
                <button type="submit" class="btn btn-primary">저장</button>
            </div>
        </div>
        <p style="margin:8px 0 0;font-size:12px;color:var(--text-muted)">
            DB설계는 프로젝트 전체 단계입니다. 메뉴·정책 등록과 무관하게 여기서만 진행률이 반영됩니다.
            현재: <strong><?= (int) (isset($progressPhases['DB설계']) ? $progressPhases['DB설계'] : 0) ?>%</strong>
        </p>
    </form>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3>단계별 진행률</h3></div>
    <div class="phase-chart" style="padding:0 16px 16px">
        <?php
        $phaseWeights = array();
        foreach (ProjectService::getProgressPhaseDefinitions() as $phaseDef) {
            $phaseWeights[$phaseDef['label']] = isset($phaseDef['weight']) ? (int) $phaseDef['weight'] : 0;
        }
        ?>
        <?php foreach ($progressPhases as $label => $pct): ?>
        <div class="phase-row">
            <span class="phase-label">
                <?= e($label) ?>
                <?php if (!empty($phaseWeights[$label])): ?>
                <small style="color:var(--text-muted);font-weight:400">(<?= (int) $phaseWeights[$label] ?>%)</small>
                <?php endif; ?>
            </span>
            <div class="phase-bar-wrap">
                <div class="progress-bar"><div class="progress-bar-fill" style="width:<?= (int) $pct ?>%"></div></div>
            </div>
            <span class="phase-pct"><?= (int) $pct ?>%</span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>메뉴</th>
                    <th>스토리보드</th>
                    <th>디자인</th>
                    <th>퍼블리싱</th>
                    <th>코딩</th>
                    <th>검수</th>
                    <th>진척</th>
                    <th>담당</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($menuList as $m):
                $assignee = null;
                if (!empty($m['assignee_id'])) {
                    foreach ($users as $u) {
                        if ($u['id'] == $m['assignee_id']) {
                            $assignee = $u;
                            break;
                        }
                    }
                }
            ?>
            <tr>
                <td><?= str_repeat('— ', $m['depth']) . e($m['title']) ?></td>
                <td><?= status_badge(isset($m['storyboard_status']) ? $m['storyboard_status'] : 'pending') ?></td>
                <td><?= status_badge(isset($m['design_status']) ? $m['design_status'] : 'pending') ?></td>
                <td><?= status_badge(isset($m['publishing_status']) ? $m['publishing_status'] : 'pending') ?></td>
                <td><?= status_badge(isset($m['coding_status']) ? $m['coding_status'] : 'pending') ?></td>
                <td><?= status_badge(isset($m['review_status']) ? $m['review_status'] : 'pending') ?></td>
                <td>
                    <div class="progress-bar" style="width:60px;display:inline-block">
                        <div class="progress-bar-fill" style="width:<?= isset($m['progress_pct']) ? $m['progress_pct'] : 0 ?>%"></div>
                    </div>
                    <?= isset($m['progress_pct']) ? $m['progress_pct'] : 0 ?>%
                </td>
                <td><?= e(isset($assignee['name']) ? $assignee['name'] : '-') ?></td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick="openProgress(<?= json_html_attr($m) ?>)">수정</button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="progressModal">
    <div class="modal">
        <div class="modal-header"><h3 id="progressTitle">진행 수정</h3><button class="modal-close">&times;</button></div>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="menu_id" id="progressMenuId">
            <?php foreach (array('storyboard' => '스토리보드', 'design' => '디자인', 'publishing' => '퍼블리싱', 'coding' => '코딩', 'review' => '검수') as $key => $label): ?>
            <div class="form-row">
                <div class="form-group">
                    <label><?= $label ?> 상태</label>
                    <select name="<?= $key ?>_status" id="progress_<?= $key ?>" class="form-control">
                        <option value="pending">대기</option>
                        <option value="in_progress">진행중</option>
                        <option value="done">완료</option>
                        <option value="na">해당없음</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= $label ?> 비고</label>
                    <input type="text" name="<?= $key ?>_note" id="note_<?= $key ?>" class="form-control">
                </div>
            </div>
            <?php endforeach; ?>
            <div class="form-group">
                <label>담당자</label>
                <select name="assignee_id" id="progressAssignee" class="form-control">
                    <option value="">— 미지정 —</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= e($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">저장</button>
        </form>
    </div>
</div>
<script>
function openProgress(m) {
    document.getElementById('progressTitle').textContent = m.title + ' — 진행 수정';
    document.getElementById('progressMenuId').value = m.id;
    ['storyboard','design','publishing','coding','review'].forEach(function(k) {
        document.getElementById('progress_' + k).value = m[k + '_status'] || 'pending';
        document.getElementById('note_' + k).value = m[k + '_note'] || '';
    });
    document.getElementById('progressAssignee').value = m.assignee_id || '';
    document.getElementById('progressModal').classList.add('active');
}
</script>
<script>
(function() {
    var form = document.getElementById('phaseSettingsForm');
    if (!form) return;

    var picker = document.getElementById('phasePicker');
    var phaseInput = document.getElementById('currentPhaseInput');
    var modeInputs = form.querySelectorAll('input[name="phase_mode"]');

    function setManualEnabled(enabled) {
        picker.classList.toggle('is-disabled', !enabled);
        picker.querySelectorAll('.admin-phase-option').forEach(function(btn) {
            btn.disabled = !enabled;
        });
    }

    modeInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            setManualEnabled(input.value === 'manual');
        });
    });

    picker.querySelectorAll('.admin-phase-option').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (btn.disabled) return;
            var phase = btn.getAttribute('data-phase');
            phaseInput.value = phase;
            picker.querySelectorAll('.admin-phase-option').forEach(function(b) {
                b.classList.remove('is-selected');
                var now = b.querySelector('.admin-phase-option-now');
                if (now) now.remove();
            });
            btn.classList.add('is-selected');
            var badge = document.createElement('span');
            badge.className = 'admin-phase-option-now';
            badge.textContent = 'NOW';
            btn.appendChild(badge);
        });
    });
})();
</script>
