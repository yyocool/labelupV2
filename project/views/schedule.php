<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>일정관리</h1>
            <p>마일스톤, 이슈, 메뉴 마감일, 프로젝트 기간을 한곳에서 확인합니다.</p>
        </div>
        <div class="btn-group schedule-quick-links">
            <a href="<?= url('milestones.php') ?>" class="btn btn-secondary btn-sm">마일스톤</a>
            <a href="<?= url('issues.php') ?>" class="btn btn-secondary btn-sm">이슈</a>
            <a href="<?= url('menus.php') ?>" class="btn btn-secondary btn-sm">메뉴</a>
        </div>
    </div>
</div>

<div class="schedule-toolbar card">
    <div class="schedule-toolbar-top">
        <div class="schedule-view-tabs tabs">
            <a href="<?= schedule_query(array('view' => 'calendar', 'date' => $anchorDate)) ?>"
               class="tab<?= $view === 'calendar' ? ' active' : '' ?>">📅 달력</a>
            <a href="<?= schedule_query(array('view' => 'week', 'date' => $anchorDate)) ?>"
               class="tab<?= $view === 'week' ? ' active' : '' ?>">📆 주간</a>
            <a href="<?= schedule_query(array('view' => 'list', 'date' => $anchorDate)) ?>"
               class="tab<?= $view === 'list' ? ' active' : '' ?>">📋 목록</a>
        </div>

        <div class="schedule-nav">
            <?php if ($view === 'calendar'): ?>
            <a href="<?= schedule_query(array('view' => 'calendar', 'date' => $prevDate)) ?>" class="btn btn-secondary btn-sm">← 이전</a>
            <strong class="schedule-period-label"><?= $year ?>년 <?= $month ?>월</strong>
            <a href="<?= schedule_query(array('view' => 'calendar', 'date' => $nextDate)) ?>" class="btn btn-secondary btn-sm">다음 →</a>
            <a href="<?= schedule_query(array('view' => 'calendar', 'date' => $today)) ?>" class="btn btn-secondary btn-sm">오늘</a>
            <?php elseif ($view === 'week'): ?>
            <a href="<?= schedule_query(array('view' => 'week', 'date' => $prevWeekDate)) ?>" class="btn btn-secondary btn-sm">← 이전 주</a>
            <strong class="schedule-period-label"><?= e($weekStart) ?> ~ <?= e($weekEnd) ?></strong>
            <a href="<?= schedule_query(array('view' => 'week', 'date' => $nextWeekDate)) ?>" class="btn btn-secondary btn-sm">다음 주 →</a>
            <a href="<?= schedule_query(array('view' => 'week', 'date' => $today)) ?>" class="btn btn-secondary btn-sm">이번 주</a>
            <?php else: ?>
            <strong class="schedule-period-label">전체 일정 <?= count($events) ?>건</strong>
            <a href="<?= schedule_query(array('view' => 'week', 'date' => $today)) ?>" class="btn btn-secondary btn-sm">이번 주 보기</a>
            <?php endif; ?>
        </div>
    </div>

    <form method="get" class="schedule-filters">
        <input type="hidden" name="view" value="<?= e($view) ?>">
        <input type="hidden" name="date" value="<?= e($anchorDate) ?>">
        <span class="schedule-filter-label">유형</span>
        <?php foreach ($typeMeta as $typeKey => $meta): ?>
        <label class="schedule-filter-chip">
            <input type="checkbox" name="types[]" value="<?= e($typeKey) ?>"
                <?= in_array($typeKey, $activeTypes, true) ? 'checked' : '' ?>
                onchange="this.form.submit()">
            <span class="schedule-chip-dot" style="background:<?= e($meta['color']) ?>"></span>
            <?= e($meta['label']) ?>
            <span class="schedule-chip-count"><?= isset($typeCounts[$typeKey]) ? (int) $typeCounts[$typeKey] : 0 ?></span>
        </label>
        <?php endforeach; ?>
    </form>
</div>

<?php if ($view === 'calendar'): ?>
<div class="card schedule-calendar-card">
    <div class="schedule-calendar">
        <div class="schedule-cal-head">
            <?php foreach (array('월', '화', '수', '목', '금', '토', '일') as $wd): ?>
            <div class="schedule-cal-head-cell"><?= $wd ?></div>
            <?php endforeach; ?>
        </div>
        <div class="schedule-cal-body">
            <?php foreach ($calendarGrid as $cell):
                $cellEvents = isset($eventsByDate[$cell['date']]) ? $eventsByDate[$cell['date']] : array();
                $isToday = ($cell['date'] === $today);
            ?>
            <div class="schedule-cal-cell<?= $cell['in_month'] ? '' : ' is-other-month' ?><?= $isToday ? ' is-today' : '' ?>">
                <div class="schedule-cal-day">
                    <a href="<?= schedule_query(array('view' => 'week', 'date' => $cell['date'])) ?>"><?= (int) $cell['day'] ?></a>
                </div>
                <div class="schedule-cal-events">
                    <?php foreach (array_slice($cellEvents, 0, 3) as $ev): ?>
                    <a href="<?= e($ev['link']) ?>" class="schedule-event-chip<?= !empty($ev['is_overdue']) ? ' is-overdue' : '' ?>"
                       style="--ev-color:<?= e($ev['type_color']) ?>" title="<?= e($ev['title']) ?>">
                        <span><?= e($ev['type_icon']) ?></span>
                        <?= e(mb_safe_substr($ev['title'], 0, 14)) ?>
                    </a>
                    <?php endforeach; ?>
                    <?php if (count($cellEvents) > 3): ?>
                    <span class="schedule-more">+<?= count($cellEvents) - 3 ?>건</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php elseif ($view === 'week'): ?>
<div class="card schedule-week-card">
    <div class="schedule-week-grid">
        <?php foreach ($weekDays as $day):
            $dayEvents = isset($eventsByDate[$day['date']]) ? $eventsByDate[$day['date']] : array();
        ?>
        <div class="schedule-week-col<?= !empty($day['is_today']) ? ' is-today' : '' ?>">
            <div class="schedule-week-head">
                <span class="schedule-week-dow"><?= e($day['label']) ?></span>
                <span class="schedule-week-date"><?= (int) $day['day'] ?></span>
                <span class="schedule-week-count"><?= count($dayEvents) ?></span>
            </div>
            <div class="schedule-week-body">
                <?php if (empty($dayEvents)): ?>
                <div class="schedule-week-empty">일정 없음</div>
                <?php else: ?>
                <?php foreach ($dayEvents as $ev): ?>
                <a href="<?= e($ev['link']) ?>" class="schedule-week-event<?= !empty($ev['is_overdue']) ? ' is-overdue' : '' ?>"
                   style="border-left-color:<?= e($ev['type_color']) ?>">
                    <div class="schedule-week-event-type">
                        <span><?= e($ev['type_icon']) ?></span> <?= e($ev['type_label']) ?>
                    </div>
                    <strong><?= e($ev['title']) ?></strong>
                    <?php if (!empty($ev['meta'])): ?>
                    <small><?= e($ev['meta']) ?></small>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php else: ?>
<div class="card schedule-list-card">
    <?php if (empty($events)): ?>
    <div class="empty-state">
        <p>표시할 일정이 없습니다.</p>
        <p class="text-muted" style="font-size:13px;margin-top:8px">마일스톤, 이슈, 메뉴 상세에서 마감일을 등록하면 여기에 표시됩니다.</p>
    </div>
    <?php else: ?>
    <div class="schedule-list">
        <?php
        $lastDate = null;
        foreach ($events as $ev):
            if ($ev['date'] !== $lastDate):
                $lastDate = $ev['date'];
                $ts = strtotime($ev['date']);
                $dow = array('일', '월', '화', '수', '목', '금', '토');
        ?>
        <div class="schedule-list-date">
            <span class="schedule-list-date-main"><?= date('Y.m.d', $ts) ?></span>
            <span class="schedule-list-date-sub"><?= $dow[(int) date('w', $ts)] ?>요일</span>
            <?php if ($ev['date'] === $today): ?><span class="badge badge-blue">오늘</span><?php endif; ?>
        </div>
        <?php endif; ?>
        <a href="<?= e($ev['link']) ?>" class="schedule-list-item<?= !empty($ev['is_overdue']) ? ' is-overdue' : '' ?>">
            <span class="schedule-list-icon" style="background:<?= e($ev['type_color']) ?>20;color:<?= e($ev['type_color']) ?>">
                <?= e($ev['type_icon']) ?>
            </span>
            <div class="schedule-list-body">
                <div class="schedule-list-head">
                    <strong><?= e($ev['title']) ?></strong>
                    <span class="badge badge-light"><?= e($ev['type_label']) ?></span>
                    <?php if (!empty($ev['is_overdue'])): ?>
                    <span class="badge badge-red">연체</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($ev['meta'])): ?>
                <p class="schedule-list-meta"><?= e($ev['meta']) ?></p>
                <?php endif; ?>
            </div>
            <span class="schedule-list-arrow">→</span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script src="<?= asset('js/schedule.js') ?>"></script>
