<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>대시보드</h1>
            <p><?= e($project['name']) ?> — 프로젝트 전체 현황</p>
        </div>
        <div class="btn-group">
            <a href="<?= url('issues.php?action=new') ?>" class="btn btn-primary btn-sm">+ 이슈 등록</a>
            <a href="<?= url('menus.php') ?>" class="btn btn-secondary btn-sm">메뉴 구성도</a>
        </div>
    </div>
</div>

<?php $canEditSchedule = is_admin(); ?>
<div class="card gantt-card" id="ganttCard"
     data-endpoint="<?= url('schedule-tasks.php') ?>"
     data-csrf="<?= e(csrf_token()) ?>">
    <?php if ($canEditSchedule): ?>
    <?= csrf_field() ?>
    <?php endif; ?>
    <div class="card-header">
        <div>
            <h3>일정 간트 차트</h3>
            <p class="gantt-subtitle">단계별 일정 · 평균 완료율 <strong id="ganttAvgProgress"><?= (int) $gantt['avg_progress'] ?>%</strong></p>
        </div>
        <?php if ($canEditSchedule): ?>
        <button type="button" class="btn btn-primary btn-sm" data-modal="scheduleTaskModal">+ 일정 추가</button>
        <?php endif; ?>
    </div>

    <?php if (empty($gantt['tasks'])): ?>
    <div class="empty-state"><p>등록된 일정이 없습니다.<?= $canEditSchedule ? ' “일정 추가” 버튼으로 등록하세요.' : '' ?></p></div>
    <?php else: ?>
    <div class="gantt-scroll">
        <div class="gantt-inner">
            <div class="gantt-head">
                <div class="gantt-col-label">단계 · 세부 항목</div>
                <div class="gantt-col-timeline">
                    <div class="gantt-months">
                        <?php foreach ($gantt['months'] as $m): ?>
                        <div class="gantt-month" style="width:<?= $m['width'] ?>%">
                            <span><?= e($m['year']) ?>년 <?= e($m['label']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="gantt-col-progress">완료 %</div>
            </div>

            <div class="gantt-body">
                <?php
                $currentPhase = null;
                $phaseSummary = array();
                foreach ($gantt['phases'] as $p) {
                    $phaseSummary[$p['label']] = $p;
                }
                foreach ($gantt['tasks'] as $task):
                    if ($task['phase'] !== $currentPhase):
                        $currentPhase = $task['phase'];
                        $ps = isset($phaseSummary[$currentPhase]) ? $phaseSummary[$currentPhase] : array('color' => $task['color'], 'progress' => 0, 'count' => 0);
                ?>
                <div class="gantt-phase-row" data-phase="<?= e($currentPhase) ?>">
                    <div class="gantt-col-label">
                        <?php if ($canEditSchedule): ?>
                        <span class="gantt-phase-drag-handle" draggable="true" title="단계 전체를 드래그하여 이동">⠿</span>
                        <?php endif; ?>
                        <span class="gantt-dot" style="background:<?= e($ps['color']) ?>"></span>
                        <strong><?= e($currentPhase) ?></strong>
                        <small><?= (int) $ps['count'] ?>개</small>
                    </div>
                    <div class="gantt-col-timeline"></div>
                    <div class="gantt-col-progress"><span class="gantt-phase-pct"><?= (int) $ps['progress'] ?>%</span></div>
                </div>
                <?php endif; ?>

                <div class="gantt-row" data-task-id="<?= (int) $task['id'] ?>" data-phase="<?= e($task['phase']) ?>">
                    <div class="gantt-col-label">
                        <div class="gantt-label-main">
                        <?php if ($canEditSchedule): ?>
                        <span class="gantt-drag-handle" draggable="true" title="드래그하여 순서·단계 이동">⠿</span>
                        <?php endif; ?>
                        <div class="gantt-label-text">
                            <span class="gantt-task-title"><?= e($task['title']) ?></span>
                            <?php if (!empty($task['detail'])): ?>
                            <span class="gantt-task-detail"><?= e($task['detail']) ?></span>
                            <?php endif; ?>
                            <?php if ($canEditSchedule): ?>
                            <span class="gantt-task-dates">
                                <input type="date" class="gantt-date-input" data-role="start"
                                       data-task-id="<?= (int) $task['id'] ?>" value="<?= e($task['start']) ?>"
                                       aria-label="<?= e($task['title']) ?> 시작일">
                                <span class="gantt-date-sep">~</span>
                                <input type="date" class="gantt-date-input" data-role="end"
                                       data-task-id="<?= (int) $task['id'] ?>" value="<?= e($task['end']) ?>"
                                       aria-label="<?= e($task['title']) ?> 종료일">
                            </span>
                            <?php else: ?>
                            <span class="gantt-task-dates-text"><?= e($task['start']) ?> ~ <?= e($task['end']) ?></span>
                            <?php endif; ?>
                        </div>
                        </div>
                        <?php if ($canEditSchedule): ?>
                        <div class="gantt-actions">
                            <form method="post" action="<?= url('schedule-tasks.php') ?>" class="gantt-delete-form" data-confirm="이 일정을 삭제하시겠습니까?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                <button type="submit" class="gantt-delete-btn" title="삭제">&times;</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="gantt-col-timeline">
                        <div class="gantt-track">
                            <div class="gantt-grid">
                                <?php foreach ($gantt['months'] as $m): ?>
                                <div class="gantt-grid-col" style="width:<?= $m['width'] ?>%"></div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($gantt['today_offset'] !== null): ?>
                            <div class="gantt-today" style="left:<?= $gantt['today_offset'] ?>%" title="오늘"></div>
                            <?php endif; ?>
                            <div class="gantt-bar" style="left:<?= $task['offset_pct'] ?>%;width:<?= $task['width_pct'] ?>%;--bar-color:<?= e($task['color']) ?>"
                                 title="<?= e($task['start']) ?> ~ <?= e($task['end']) ?> (<?= (int) $task['days'] ?>일)">
                                <div class="gantt-bar-fill" style="width:<?= (int) $task['progress'] ?>%"></div>
                                <span class="gantt-bar-label"><?= (int) $task['progress'] ?>%</span>
                            </div>
                        </div>
                    </div>
                    <div class="gantt-col-progress">
                        <?php if ($canEditSchedule): ?>
                        <div class="gantt-progress-edit">
                            <input type="number" class="gantt-progress-input" min="0" max="100" step="5"
                                   value="<?= (int) $task['progress'] ?>" data-task-id="<?= (int) $task['id'] ?>"
                                   aria-label="<?= e($task['title']) ?> 완료율">
                            <span class="gantt-progress-suffix">%</span>
                        </div>
                        <?php else: ?>
                        <span class="gantt-progress-value"><?= (int) $task['progress'] ?>%</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($canEditSchedule): ?>
<div class="modal-overlay" id="scheduleTaskModal">
    <div class="modal">
        <div class="modal-header"><h3>일정 추가</h3><button class="modal-close">&times;</button></div>
        <form method="post" action="<?= url('schedule-tasks.php') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>단계</label>
                <input type="text" name="phase" class="form-control" list="schedulePhaseList" placeholder="예: 백엔드 개발" required>
                <datalist id="schedulePhaseList">
                    <?php foreach (array_keys(ScheduleTaskService::getPhaseColors()) as $phaseName): ?>
                    <option value="<?= e($phaseName) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group"><label>세부 항목</label><input type="text" name="title" class="form-control" required></div>
            <div class="form-group"><label>세부정보 (설명)</label><input type="text" name="detail" class="form-control"></div>
            <div class="form-row">
                <div class="form-group"><label>시작일</label><input type="date" name="start_date" class="form-control" required></div>
                <div class="form-group"><label>종료일</label><input type="date" name="end_date" class="form-control" required></div>
            </div>
            <div class="form-group">
                <label>완료율 (%)</label>
                <input type="number" name="progress" class="form-control" min="0" max="100" step="5" value="0">
            </div>
            <button type="submit" class="btn btn-primary">등록</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card highlight">
        <div class="stat-label">전체 진척도</div>
        <div class="stat-value"><?= (int)$project['progress'] ?>%</div>
        <div class="progress-bar progress-bar-lg" style="margin-top:12px">
            <div class="progress-bar-fill" style="width:<?= (int)$project['progress'] ?>%"></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">등록 메뉴</div>
        <div class="stat-value"><?= $stats['totalMenus'] ?></div>
        <div class="stat-sub">다단계 메뉴 항목</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">열린 이슈</div>
        <div class="stat-value"><?= $stats['openIssues'] ?></div>
        <div class="stat-sub">전체 <?= $stats['totalIssues'] ?>건</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">팀원</div>
        <div class="stat-value"><?= $stats['members'] ?></div>
        <div class="stat-sub">마일스톤 <?= $stats['pendingMilestones'] ?>건 남음</div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>단계별 진행률</h3></div>
        <div class="phase-chart">
            <?php
            $phaseWeights = array();
            foreach (ProjectService::getProgressPhaseDefinitions() as $phaseDef) {
                $phaseWeights[$phaseDef['label']] = isset($phaseDef['weight']) ? (int) $phaseDef['weight'] : 0;
            }
            ?>
            <?php foreach ($phases as $label => $pct): ?>
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
        <div style="padding:0 16px 14px;font-size:12px;color:var(--text-muted)">
            11개 단계 가중 평균 = 전체 진척도 (개발·퍼블리싱 비중 높음)
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>최근 활동</h3>
        </div>
        <?php if (empty($activities)): ?>
        <div class="empty-state"><p>아직 활동 기록이 없습니다.</p></div>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach ($activities as $act): ?>
            <li class="activity-item">
                <span class="activity-dot"></span>
                <div>
                    <div class="activity-text">
                        <strong><?= e(isset($act['user_name']) ? $act['user_name'] : '시스템') ?></strong>
                        <?= e(isset($act['description']) ? $act['description'] : $act['action']) ?>
                    </div>
                    <div class="activity-time"><?= time_ago($act['created_at']) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2" style="margin-top:20px">
    <div class="card">
        <div class="card-header">
            <h3>열린 이슈</h3>
            <a href="<?= url('issues.php') ?>" class="btn btn-secondary btn-sm">전체 보기</a>
        </div>
        <?php if (empty($recentIssues)): ?>
        <div class="empty-state"><p>열린 이슈가 없습니다.</p></div>
        <?php else: ?>
        <?php foreach ($recentIssues as $issue): ?>
        <div class="issue-item">
            <span class="issue-icon"><?= issue_type_icon($issue['type']) ?></span>
            <div class="issue-content">
                <a href="<?= url('issue-detail.php?id=' . $issue['id']) ?>" class="issue-title"><?= e($issue['title']) ?></a>
                <div class="issue-meta">
                    <?= status_badge($issue['status']) ?>
                    <?= priority_badge($issue['priority']) ?>
                    <?php if ($issue['assignee_name']): ?><span>담당: <?= e($issue['assignee_name']) ?></span><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>다가오는 마일스톤</h3>
            <a href="<?= url('milestones.php') ?>" class="btn btn-secondary btn-sm">전체 보기</a>
        </div>
        <?php if (empty($milestones)): ?>
        <div class="empty-state"><p>등록된 마일스톤이 없습니다.</p></div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>마일스톤</th><th>마감일</th><th>상태</th></tr></thead>
                <tbody>
                <?php foreach ($milestones as $ms): ?>
                <tr>
                    <td><?= e($ms['title']) ?></td>
                    <td><?= e($ms['due_date']) ?></td>
                    <td><?= status_badge($ms['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($notices)): ?>
<div class="card" style="margin-top:20px">
    <div class="card-header"><h3>공지사항</h3><a href="<?= url('notices.php') ?>" class="btn btn-secondary btn-sm">전체 보기</a></div>
    <?php foreach ($notices as $notice): ?>
    <div style="padding:12px 0;border-bottom:1px solid var(--border-light)">
        <strong><?= e($notice['title']) ?></strong>
        <?php if ($notice['is_pinned']): ?><span class="badge badge-yellow">고정</span><?php endif; ?>
        <p style="font-size:13px;color:var(--text-secondary);margin-top:4px"><?= e(mb_safe_substr($notice['content'], 0, 120)) ?>...</p>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($gantt['tasks']) && is_admin()): ?>
<script src="<?= asset('js/gantt.js') ?>"></script>
<?php endif; ?>
