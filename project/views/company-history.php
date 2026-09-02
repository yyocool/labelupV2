<?php
/**
 * 회사 연혁 관리 UI
 * @var array $companies
 * @var array $eventCategories
 * @var array|null $editCompany
 * @var array $editEvents
 * @var array|null $viewCompany
 * @var array $viewEventsByYear
 */
$qSuffix = ($search !== '') ? '&q=' . urlencode($search) : '';
?>
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>회사 연혁</h1>
            <p>회사별 설립·사업·수상·확장 연혁과 주요 실적을 관리하고 보기·PDF로 저장합니다. (메뉴 미등록 · URL 직접 접근)</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <a href="<?= url('resume.php') ?>" class="btn btn-outline">이력서 관리</a>
            <?php if (!empty($canEdit)): ?>
            <button type="button" class="btn btn-primary" data-modal="chCompanyModal" data-company-mode="create">+ 회사 추가</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:16px;padding:14px 16px">
    <form method="get" action="<?= url('company-history.php') ?>" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input type="search" name="q" class="form-control" value="<?= e($search) ?>" placeholder="회사명·업종·소개 검색" style="flex:1;min-width:200px;max-width:360px">
        <button type="submit" class="btn btn-outline">검색</button>
        <?php if ($search !== ''): ?>
        <a href="<?= url('company-history.php') ?>" class="btn btn-outline">초기화</a>
        <?php endif; ?>
        <span style="font-size:12px;color:var(--text-muted);margin-left:auto">총 <?= (int) $totalCount ?>개</span>
    </form>
</div>

<?php if (empty($companies)): ?>
<div class="card">
    <div class="empty-state">
        <p><?= $search !== '' ? '검색 결과가 없습니다.' : '등록된 회사가 없습니다.' ?>
        <?php if (!empty($canEdit) && $search === ''): ?> 「회사 추가」로 시작해 보세요.<?php endif; ?></p>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php if (!empty($canEdit)): ?><th style="width:36px"></th><?php endif; ?>
                    <th>회사명</th>
                    <th style="width:90px">설립</th>
                    <th style="width:160px">업종</th>
                    <th style="width:180px">웹사이트</th>
                    <th style="width:70px">연혁</th>
                    <th style="width:70px">실적</th>
                    <th style="width:220px"></th>
                </tr>
            </thead>
            <tbody id="chCompanyList" class="lu-sortable-list">
                <?php foreach ($companies as $c): ?>
                <tr data-id="<?= (int) $c['id'] ?>">
                    <?php if (!empty($canEdit)): ?>
                    <td class="lu-drag-cell">
                        <span class="lu-drag" draggable="true" title="드래그하여 순서 변경" data-drag-handle>⋮⋮</span>
                    </td>
                    <?php endif; ?>
                    <td>
                        <a href="<?= url('company-history.php?view=' . (int) $c['id'] . $qSuffix) ?>" style="font-weight:700;color:inherit;text-decoration:none">
                            <?= e($c['name']) ?>
                        </a>
                    </td>
                    <td style="font-size:13px"><?= !empty($c['founded_year']) ? e($c['founded_year']) : '—' ?></td>
                    <td style="font-size:13px"><?= $c['industry'] ? e($c['industry']) : '—' ?></td>
                    <td style="font-size:12px;color:var(--text-secondary)">
                        <?php if (!empty($c['website'])): ?>
                        <a href="<?= e($c['website']) ?>" target="_blank" rel="noopener" style="color:inherit"><?= e($c['website']) ?></a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td style="text-align:center;font-size:13px"><?= (int) $c['event_count'] ?></td>
                    <td style="text-align:center;font-size:13px"><?= (int) (isset($c['achievement_count']) ? $c['achievement_count'] : 0) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <a href="<?= url('company-history.php?view=' . (int) $c['id'] . $qSuffix) ?>" class="btn btn-sm btn-outline">보기</a>
                            <a href="<?= url('company-history.php?view=' . (int) $c['id'] . '&print=1') ?>" class="btn btn-sm btn-outline" target="_blank" rel="noopener">PDF</a>
                            <?php if (!empty($canEdit)): ?>
                            <a href="<?= url('company-history.php?edit=' . (int) $c['id'] . $qSuffix) ?>" class="btn btn-sm btn-outline">편집</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('「<?= e($c['name']) ?>」회사와 연혁을 모두 삭제할까요?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_company">
                                <input type="hidden" name="company_id" value="<?= (int) $c['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">삭제</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($editCompany)): ?>
<div class="ch-edit-panel card" style="margin-top:20px">
    <div class="page-header-row" style="margin-bottom:16px">
        <div>
            <h2 style="font-size:18px;margin:0">연혁 편집 — <?= e($editCompany['name']) ?></h2>
            <p style="margin:4px 0 0;font-size:13px;color:var(--text-muted)">연혁 타임라인과 주요 실적을 추가·수정·삭제합니다. ⋮⋮ 를 드래그해 순서를 바꿀 수 있습니다.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="<?= url('company-history.php?view=' . (int) $editCompany['id']) ?>" class="btn btn-outline">보기</a>
            <a href="<?= url('company-history.php?view=' . (int) $editCompany['id'] . '&print=1') ?>" class="btn btn-outline" target="_blank" rel="noopener">PDF 저장</a>
            <a href="<?= url('company-history.php' . ($search !== '' ? '?q=' . urlencode($search) : '')) ?>" class="btn btn-outline">목록</a>
        </div>
    </div>

    <form method="post" class="ch-company-form" style="margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border)">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_company">
        <input type="hidden" name="company_id" value="<?= (int) $editCompany['id'] ?>">
        <div class="ch-form-grid">
            <div class="form-group">
                <label>회사명 *</label>
                <input type="text" name="name" class="form-control" required maxlength="200" value="<?= e($editCompany['name']) ?>">
            </div>
            <div class="form-group">
                <label>설립 연도</label>
                <input type="text" name="founded_year" class="form-control" maxlength="10" placeholder="2018" value="<?= e(isset($editCompany['founded_year']) ? $editCompany['founded_year'] : '') ?>">
            </div>
            <div class="form-group">
                <label>업종</label>
                <input type="text" name="industry" class="form-control" maxlength="200" value="<?= e(isset($editCompany['industry']) ? $editCompany['industry'] : '') ?>">
            </div>
            <div class="form-group">
                <label>웹사이트</label>
                <input type="text" name="website" class="form-control" maxlength="300" value="<?= e(isset($editCompany['website']) ? $editCompany['website'] : '') ?>">
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label>소개 / 요약</label>
                <textarea name="summary" class="form-control" rows="3"><?= e(isset($editCompany['summary']) ? $editCompany['summary'] : '') ?></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">기본 정보 저장</button>
    </form>

    <section class="ch-events">
        <div class="ch-events-head">
            <h3>연혁 타임라인 <span class="ch-count"><?= count($editEvents) ?></span></h3>
            <button type="button" class="btn btn-sm btn-primary"
                data-modal="chEventModal"
                data-event-mode="create"
                data-company-id="<?= (int) $editCompany['id'] ?>">+ 연혁 추가</button>
        </div>
        <?php if (empty($editEvents)): ?>
        <p class="ch-empty">등록된 연혁이 없습니다.</p>
        <?php else: ?>
        <ul class="ch-event-list lu-sortable-list" id="chEventList" data-company-id="<?= (int) $editCompany['id'] ?>">
            <?php foreach ($editEvents as $row): ?>
            <li class="ch-event-item" data-id="<?= (int) $row['id'] ?>">
                <span class="lu-drag" draggable="true" title="드래그하여 순서 변경" data-drag-handle>⋮⋮</span>
                <div class="ch-event-main">
                    <div class="ch-event-meta">
                        <?php $when = CompanyHistoryService::formatEventDate($row); ?>
                        <?php if ($when !== ''): ?><span class="ch-event-date"><?= e($when) ?></span><?php endif; ?>
                        <span class="ch-event-cat"><?= e(isset($eventCategories[$row['category']]) ? $eventCategories[$row['category']] : $row['category']) ?></span>
                    </div>
                    <strong><?= e($row['title']) ?></strong>
                    <?php if (!empty($row['description'])): ?>
                    <p class="ch-event-desc"><?= nl2br(e($row['description'])) ?></p>
                    <?php endif; ?>
                </div>
                <div class="ch-event-actions">
                    <?php
                    $eventPayload = json_encode(array(
                        'id' => (int) $row['id'],
                        'category' => $row['category'],
                        'event_year' => isset($row['event_year']) ? $row['event_year'] : '',
                        'event_month' => isset($row['event_month']) ? $row['event_month'] : '',
                        'title' => $row['title'],
                        'description' => isset($row['description']) ? $row['description'] : '',
                    ), JSON_UNESCAPED_UNICODE);
                    ?>
                    <button type="button" class="btn btn-sm btn-outline"
                        data-modal="chEventModal"
                        data-event-mode="edit"
                        data-company-id="<?= (int) $editCompany['id'] ?>"
                        data-payload="<?= e($eventPayload) ?>">수정</button>
                    <form method="post" style="display:inline" onsubmit="return confirm('이 연혁을 삭제할까요?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_event">
                        <input type="hidden" name="company_id" value="<?= (int) $editCompany['id'] ?>">
                        <input type="hidden" name="event_id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">삭제</button>
                    </form>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>

    <section class="ch-events" id="achievements" style="margin-top:28px;padding-top:22px;border-top:1px solid var(--border)">
        <div class="ch-events-head">
            <h3>주요 실적 <span class="ch-count"><?= count($editAchievements) ?></span></h3>
            <button type="button" class="btn btn-sm btn-primary"
                data-modal="chAchievementModal"
                data-achievement-mode="create"
                data-company-id="<?= (int) $editCompany['id'] ?>">+ 실적 추가</button>
        </div>
        <?php if (empty($editAchievements)): ?>
        <p class="ch-empty">등록된 주요 실적이 없습니다.</p>
        <?php else: ?>
        <ul class="ch-event-list lu-sortable-list" id="chAchievementList" data-company-id="<?= (int) $editCompany['id'] ?>">
            <?php foreach ($editAchievements as $row): ?>
            <li class="ch-event-item" data-id="<?= (int) $row['id'] ?>">
                <span class="lu-drag" draggable="true" title="드래그하여 순서 변경" data-drag-handle>⋮⋮</span>
                <div class="ch-event-main">
                    <div class="ch-event-meta">
                        <?php if (!empty($row['achieved_year'])): ?><span class="ch-event-date"><?= e($row['achieved_year']) ?></span><?php endif; ?>
                        <span class="ch-event-cat"><?= e(isset($achievementCategories[$row['category']]) ? $achievementCategories[$row['category']] : $row['category']) ?></span>
                        <?php if (!empty($row['metric'])): ?><span class="ch-event-metric"><?= e($row['metric']) ?></span><?php endif; ?>
                    </div>
                    <strong><?= e($row['title']) ?></strong>
                    <?php if (!empty($row['client'])): ?>
                    <span class="ch-event-client"><?= e($row['client']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($row['description'])): ?>
                    <p class="ch-event-desc"><?= nl2br(e($row['description'])) ?></p>
                    <?php endif; ?>
                </div>
                <div class="ch-event-actions">
                    <?php
                    $achPayload = json_encode(array(
                        'id' => (int) $row['id'],
                        'category' => $row['category'],
                        'title' => $row['title'],
                        'client' => isset($row['client']) ? $row['client'] : '',
                        'metric' => isset($row['metric']) ? $row['metric'] : '',
                        'achieved_year' => isset($row['achieved_year']) ? $row['achieved_year'] : '',
                        'description' => isset($row['description']) ? $row['description'] : '',
                    ), JSON_UNESCAPED_UNICODE);
                    ?>
                    <button type="button" class="btn btn-sm btn-outline"
                        data-modal="chAchievementModal"
                        data-achievement-mode="edit"
                        data-company-id="<?= (int) $editCompany['id'] ?>"
                        data-payload="<?= e($achPayload) ?>">수정</button>
                    <form method="post" style="display:inline" onsubmit="return confirm('이 실적을 삭제할까요?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_achievement">
                        <input type="hidden" name="company_id" value="<?= (int) $editCompany['id'] ?>">
                        <input type="hidden" name="achievement_id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">삭제</button>
                    </form>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>
</div>
<?php endif; ?>

<?php if (!empty($viewCompany) && empty($editCompany)): ?>
<div class="modal-overlay active" id="chViewModal">
    <div class="modal ch-view-modal" style="max-width:760px">
        <div class="modal-header">
            <h3>회사 연혁 — <?= e($viewCompany['name']) ?></h3>
            <div style="display:flex;gap:8px;align-items:center">
                <a href="<?= url('company-history.php?view=' . (int) $viewCompany['id'] . '&print=1') ?>" class="btn btn-sm btn-primary" target="_blank" rel="noopener">PDF로 저장</a>
                <?php if (!empty($canEdit)): ?>
                <a href="<?= url('company-history.php?edit=' . (int) $viewCompany['id']) ?>" class="btn btn-sm btn-outline">편집</a>
                <?php endif; ?>
                <a href="<?= url('company-history.php' . ($search !== '' ? '?q=' . urlencode($search) : '')) ?>" class="modal-close" aria-label="닫기">&times;</a>
            </div>
        </div>
        <div class="modal-body">
            <?php
            $vc = $viewCompany;
            $ve = $viewEvents;
            $veByYear = $viewEventsByYear;
            $va = $viewAchievements;
            include __DIR__ . '/company-history-document.inc.php';
            ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($canEdit)): ?>
<div class="modal-overlay" id="chCompanyModal">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <h3 id="chCompanyModalTitle">회사 추가</h3>
            <button type="button" class="modal-close" data-close>&times;</button>
        </div>
        <form method="post" class="modal-body">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_company">
            <input type="hidden" name="company_id" id="chCompanyId" value="0">
            <div class="form-group">
                <label>회사명 *</label>
                <input type="text" name="name" id="chCompanyName" class="form-control" required maxlength="200" placeholder="(주)라벨업">
            </div>
            <div class="ch-form-grid">
                <div class="form-group">
                    <label>설립 연도</label>
                    <input type="text" name="founded_year" class="form-control" maxlength="10" placeholder="2018">
                </div>
                <div class="form-group">
                    <label>업종</label>
                    <input type="text" name="industry" class="form-control" maxlength="200" placeholder="소프트웨어 / SaaS">
                </div>
            </div>
            <div class="form-group">
                <label>웹사이트</label>
                <input type="text" name="website" class="form-control" maxlength="300" placeholder="https://">
            </div>
            <div class="form-group">
                <label>소개 / 요약</label>
                <textarea name="summary" class="form-control" rows="3" placeholder="회사 소개"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close>취소</button>
                <button type="submit" class="btn btn-primary">저장 후 연혁 편집</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="chEventModal">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <h3 id="chEventModalTitle">연혁 추가</h3>
            <button type="button" class="modal-close" data-close>&times;</button>
        </div>
        <form method="post" class="modal-body">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_event">
            <input type="hidden" name="company_id" id="chEventCompanyId" value="0">
            <input type="hidden" name="event_id" id="chEventId" value="0">
            <div class="form-group">
                <label>유형 *</label>
                <select name="category" id="chEventCategory" class="form-control" required>
                    <?php foreach ($eventCategories as $k => $label): ?>
                    <option value="<?= e($k) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ch-form-grid">
                <div class="form-group">
                    <label>연도</label>
                    <input type="text" name="event_year" id="chEventYear" class="form-control" maxlength="10" placeholder="2024">
                </div>
                <div class="form-group">
                    <label>월</label>
                    <select name="event_month" id="chEventMonth" class="form-control">
                        <option value="">—</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>"><?= $m ?>월</option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>제목 *</label>
                <input type="text" name="title" id="chEventTitle" class="form-control" required maxlength="300" placeholder="법인 설립 / 시리즈A 유치 등">
            </div>
            <div class="form-group">
                <label>설명</label>
                <textarea name="description" id="chEventDesc" class="form-control" rows="4" placeholder="상세 내용"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close>취소</button>
                <button type="submit" class="btn btn-primary">저장</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="chAchievementModal">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <h3 id="chAchievementModalTitle">실적 추가</h3>
            <button type="button" class="modal-close" data-close>&times;</button>
        </div>
        <form method="post" class="modal-body">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_achievement">
            <input type="hidden" name="company_id" id="chAchCompanyId" value="0">
            <input type="hidden" name="achievement_id" id="chAchId" value="0">
            <div class="form-group">
                <label>유형 *</label>
                <select name="category" id="chAchCategory" class="form-control" required>
                    <?php foreach ($achievementCategories as $k => $label): ?>
                    <option value="<?= e($k) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>제목 *</label>
                <input type="text" name="title" id="chAchTitle" class="form-control" required maxlength="300" placeholder="대형 커머스 플랫폼 구축">
            </div>
            <div class="ch-form-grid">
                <div class="form-group">
                    <label>고객·발주처</label>
                    <input type="text" name="client" id="chAchClient" class="form-control" maxlength="200" placeholder="○○전자">
                </div>
                <div class="form-group">
                    <label>연도</label>
                    <input type="text" name="achieved_year" id="chAchYear" class="form-control" maxlength="10" placeholder="2024">
                </div>
            </div>
            <div class="form-group">
                <label>성과·수치</label>
                <input type="text" name="metric" id="chAchMetric" class="form-control" maxlength="200" placeholder="전환율 32% 향상 / 계약 12건">
            </div>
            <div class="form-group">
                <label>설명</label>
                <textarea name="description" id="chAchDesc" class="form-control" rows="4" placeholder="상세 내용"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close>취소</button>
                <button type="submit" class="btn btn-primary">저장</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.ch-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:0 14px; }
.ch-events-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:12px; }
.ch-events-head h3 { margin:0; font-size:15px; }
.ch-count { display:inline-flex; min-width:22px; height:22px; padding:0 7px; align-items:center; justify-content:center; border-radius:999px; background:var(--bg-subtle); font-size:12px; color:var(--text-muted); margin-left:6px; }
.ch-empty { font-size:13px; color:var(--text-muted); margin:0 0 8px; }
.ch-event-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px; }
.ch-event-item { display:flex; gap:10px; align-items:flex-start; justify-content:space-between; padding:12px 14px; border:1px solid var(--border); border-radius:10px; background:var(--bg-subtle, #f8fafc); }
.lu-drag {
    flex-shrink:0; display:inline-flex; align-items:center; justify-content:center;
    width:22px; height:28px; margin-top:1px; cursor:grab; user-select:none;
    color:var(--text-muted); font-size:12px; letter-spacing:-2px; line-height:1;
    border-radius:4px;
}
.lu-drag:hover { color:var(--text); background:rgba(15,23,42,.06); }
.lu-drag:active { cursor:grabbing; }
.lu-drag-cell { width:36px; vertical-align:middle; }
.lu-sortable-list .is-dragging { opacity:.45; }
.lu-sortable-list tr.is-drag-over td { box-shadow: inset 0 2px 0 #2563eb; }
.lu-sortable-list .ch-event-item.is-drag-over { box-shadow: inset 0 2px 0 #2563eb; }
.ch-event-main { flex:1; min-width:0; }
.ch-event-main strong { display:block; font-size:14px; margin-top:4px; }
.ch-event-meta { font-size:12px; color:var(--text-muted); }
.ch-event-date { font-weight:600; margin-right:8px; color:var(--text-secondary); }
.ch-event-cat { display:inline-block; font-size:11px; padding:2px 8px; border-radius:999px; background:#fff; border:1px solid var(--border); }
.ch-event-metric { display:inline-block; font-size:11px; font-weight:700; color:#1e3a5f; margin-left:6px; }
.ch-event-client { display:block; font-size:12px; color:var(--text-muted); margin-top:2px; }
.ch-event-desc { margin:8px 0 0; font-size:13px; color:var(--text-secondary); line-height:1.5; }
.ch-event-actions { display:flex; gap:6px; flex-shrink:0; }
.ch-doc { font-size:14px; line-height:1.55; color:var(--text); }
.ch-doc-head { margin:0 0 22px; padding:0 0 18px; border-bottom:3px solid #0f3d5e; }
.ch-doc-brand { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.ch-doc-eyebrow { margin:0; font-size:10px; font-weight:800; letter-spacing:.16em; text-transform:uppercase; color:#0f3d5e; }
.ch-doc-docmark { margin:0; font-size:9px; font-weight:700; letter-spacing:.1em; color:var(--text-muted); border:1px solid var(--border); padding:2px 7px; }
.ch-doc-name { margin:0 0 10px; font-size:22px; font-weight:800; letter-spacing:-.02em; }
.ch-doc-meta { list-style:none; margin:0; padding:0; display:flex; flex-wrap:wrap; gap:6px 14px; font-size:13px; color:var(--text-secondary); }
.ch-doc-meta-label { display:inline-block; margin-right:6px; font-size:10px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--text-muted); }
.ch-doc-summary { margin-top:12px; display:grid; grid-template-columns:40px 1fr; gap:10px; padding:10px 12px; background:var(--bg-subtle,#f8fafc); border:1px solid var(--border); border-left:4px solid #0f3d5e; }
.ch-doc-summary-label { font-size:10px; font-weight:800; letter-spacing:.06em; color:#0f3d5e; }
.ch-doc-summary p { margin:0; font-size:13px; line-height:1.6; color:var(--text-secondary); }
.ch-doc-section { margin:0 0 20px; }
.ch-doc-section-head { display:flex; align-items:center; gap:8px; margin:0; padding:7px 10px; background:#0f3d5e; color:#fff; }
.ch-doc-section-num { font-size:11px; font-weight:800; opacity:.7; }
.ch-doc-section-head h3 { margin:0; flex:1; font-size:13px; font-weight:800; }
.ch-doc-section-count { font-size:11px; font-weight:700; background:rgba(255,255,255,.18); padding:2px 7px; border-radius:999px; }
.ch-doc-timeline { border:1px solid var(--border); border-top:none; }
.ch-doc-item { display:grid; grid-template-columns:68px minmax(0,1fr); border-bottom:1px solid var(--border); }
.ch-doc-item:last-child { border-bottom:none; }
.ch-doc-item.is-year-start .ch-doc-side { background:#e8f0f6; }
.ch-doc-side { text-align:right; padding:12px 10px; border-right:1px solid var(--border); background:var(--bg-subtle,#f8fafc); }
.ch-doc-year { font-size:14px; font-weight:800; color:#0f3d5e; }
.ch-doc-date { margin-top:2px; font-size:11px; font-weight:600; color:var(--text-muted); }
.ch-doc-body { padding:0; }
.ch-doc-item-card { padding:12px; }
.ch-doc-item:nth-child(even) .ch-doc-item-card { background:var(--bg-subtle,#f8fafc); }
.ch-doc-item-top { display:flex; flex-wrap:wrap; align-items:baseline; gap:6px 8px; }
.ch-doc-item-index { font-size:10px; font-weight:800; color:var(--text-muted); }
.ch-doc-item-title { font-size:14px; font-weight:800; flex:1; }
.ch-doc-cat { font-size:10px; font-weight:700; color:#0f3d5e; background:#e8f0f6; padding:2px 7px; border:1px solid #d5e4ef; }
.ch-doc-item-desc { margin:8px 0 0; padding-top:8px; border-top:1px dashed var(--border); font-size:13px; color:var(--text-secondary); line-height:1.55; }
.ch-doc-item-sub { margin-top:6px; display:flex; flex-wrap:wrap; gap:6px 12px; font-size:12px; color:var(--text-muted); }
.ch-doc-item-metric { font-weight:800; color:#0f3d5e; background:#fff; border:1px solid #d5e4ef; padding:2px 7px; }
.ch-doc-ach-list { list-style:none; margin:0; padding:0; border:1px solid var(--border); border-top:none; }
.ch-doc-ach-item { display:grid; grid-template-columns:44px minmax(0,1fr); border-bottom:1px solid var(--border); }
.ch-doc-ach-item:last-child { border-bottom:none; }
.ch-doc-ach-item:nth-child(even) { background:var(--bg-subtle,#f8fafc); }
.ch-doc-ach-num { display:flex; align-items:flex-start; justify-content:center; padding:12px 0 0; font-size:12px; font-weight:800; color:#0f3d5e; background:#e8f0f6; border-right:1px solid var(--border); }
.ch-doc-ach-body { padding:12px; }
.ch-doc-ach-year { font-size:11px; font-weight:800; color:#0f3d5e; background:#e8f0f6; border:1px solid #d5e4ef; padding:2px 7px; }
.ch-doc-ach-client { font-weight:600; }
.ch-doc-empty { font-size:13px; color:var(--text-muted); }
@media (max-width:700px) {
    .ch-form-grid { grid-template-columns:1fr; }
    .ch-event-item { flex-direction:column; }
}
</style>

<script src="<?= asset('js/sortable-list.js') ?>"></script>
<script>
(function () {
    var csrf = <?= json_encode(csrf_token(), JSON_UNESCAPED_UNICODE) ?>;
    var saveUrl = <?= json_encode(url('company-history.php'), JSON_UNESCAPED_UNICODE) ?>;

    function openModal(id) {
        var el = document.getElementById(id);
        if (el) el.classList.add('active');
    }
    function closeModal(el) {
        while (el && !el.classList.contains('modal-overlay')) el = el.parentElement;
        if (el) el.classList.remove('active');
    }
    document.querySelectorAll('[data-close], .modal-overlay').forEach(function (node) {
        node.addEventListener('click', function (e) {
            if (e.target === node || node.hasAttribute('data-close')) {
                if (node.classList.contains('modal-overlay') && e.target !== node) return;
                closeModal(e.target);
            }
        });
    });

    document.querySelectorAll('[data-modal="chCompanyModal"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var title = document.getElementById('chCompanyModalTitle');
            var idInput = document.getElementById('chCompanyId');
            if (title) title.textContent = '회사 추가';
            if (idInput) idInput.value = '0';
            var form = document.querySelector('#chCompanyModal form');
            if (form) form.reset();
            openModal('chCompanyModal');
        });
    });

    document.querySelectorAll('[data-modal="chEventModal"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var mode = btn.getAttribute('data-event-mode') || 'create';
            var titleEl = document.getElementById('chEventModalTitle');
            if (titleEl) titleEl.textContent = mode === 'edit' ? '연혁 수정' : '연혁 추가';
            document.getElementById('chEventCompanyId').value = btn.getAttribute('data-company-id') || '0';

            var payload = null;
            try {
                payload = JSON.parse(btn.getAttribute('data-payload') || 'null');
            } catch (err) { payload = null; }

            if (payload) {
                document.getElementById('chEventId').value = String(payload.id || 0);
                document.getElementById('chEventCategory').value = payload.category || 'other';
                document.getElementById('chEventYear').value = payload.event_year || '';
                document.getElementById('chEventMonth').value = payload.event_month ? String(payload.event_month) : '';
                document.getElementById('chEventTitle').value = payload.title || '';
                document.getElementById('chEventDesc').value = payload.description || '';
            } else {
                document.getElementById('chEventId').value = '0';
                document.getElementById('chEventCategory').value = 'other';
                document.getElementById('chEventYear').value = '';
                document.getElementById('chEventMonth').value = '';
                document.getElementById('chEventTitle').value = '';
                document.getElementById('chEventDesc').value = '';
            }
            openModal('chEventModal');
        });
    });

    document.querySelectorAll('[data-modal="chAchievementModal"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var mode = btn.getAttribute('data-achievement-mode') || 'create';
            var titleEl = document.getElementById('chAchievementModalTitle');
            if (titleEl) titleEl.textContent = mode === 'edit' ? '실적 수정' : '실적 추가';
            document.getElementById('chAchCompanyId').value = btn.getAttribute('data-company-id') || '0';

            var payload = null;
            try {
                payload = JSON.parse(btn.getAttribute('data-payload') || 'null');
            } catch (err) { payload = null; }

            if (payload) {
                document.getElementById('chAchId').value = String(payload.id || 0);
                document.getElementById('chAchCategory').value = payload.category || 'project';
                document.getElementById('chAchTitle').value = payload.title || '';
                document.getElementById('chAchClient').value = payload.client || '';
                document.getElementById('chAchMetric').value = payload.metric || '';
                document.getElementById('chAchYear').value = payload.achieved_year || '';
                document.getElementById('chAchDesc').value = payload.description || '';
            } else {
                document.getElementById('chAchId').value = '0';
                document.getElementById('chAchCategory').value = 'project';
                document.getElementById('chAchTitle').value = '';
                document.getElementById('chAchClient').value = '';
                document.getElementById('chAchMetric').value = '';
                document.getElementById('chAchYear').value = '';
                document.getElementById('chAchDesc').value = '';
            }
            openModal('chAchievementModal');
        });
    });

    if (window.LabelUpSortable) {
        var companyList = document.getElementById('chCompanyList');
        if (companyList) {
            LabelUpSortable.init(companyList, {
                itemSelector: 'tr[data-id]',
                url: saveUrl,
                buildPayload: function (ids) {
                    return {
                        action: 'reorder_companies',
                        ordered_ids: JSON.stringify(ids),
                        _csrf: csrf
                    };
                }
            });
        }
        var eventList = document.getElementById('chEventList');
        if (eventList) {
            LabelUpSortable.init(eventList, {
                itemSelector: 'li[data-id]',
                url: saveUrl,
                buildPayload: function (ids) {
                    return {
                        action: 'reorder_events',
                        company_id: eventList.getAttribute('data-company-id') || '0',
                        ordered_ids: JSON.stringify(ids),
                        _csrf: csrf
                    };
                }
            });
        }
        var achList = document.getElementById('chAchievementList');
        if (achList) {
            LabelUpSortable.init(achList, {
                itemSelector: 'li[data-id]',
                url: saveUrl,
                buildPayload: function (ids) {
                    return {
                        action: 'reorder_achievements',
                        company_id: achList.getAttribute('data-company-id') || '0',
                        ordered_ids: JSON.stringify(ids),
                        _csrf: csrf
                    };
                }
            });
        }
    }
})();
</script>
