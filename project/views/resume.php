<?php
/**
 * 이력서 관리 UI
 * @var array $people
 * @var array $categories
 * @var array|null $editPerson
 * @var array $editEntries
 * @var array|null $viewPerson
 * @var array $viewEntries
 */
$qSuffix = ($search !== '') ? '&q=' . urlencode($search) : '';
?>
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>이력서 관리</h1>
            <p>인물별 학력·경력·수상·프로젝트를 관리하고 보기·PDF로 저장합니다. (메뉴 미등록 · URL 직접 접근)</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <a href="<?= url('company-history.php') ?>" class="btn btn-outline">회사 연혁</a>
            <?php if (!empty($canEdit)): ?>
            <button type="button" class="btn btn-primary" data-modal="resumePersonModal" data-person-mode="create">+ 인물 추가</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:16px;padding:14px 16px">
    <form method="get" action="<?= url('resume.php') ?>" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input type="search" name="q" class="form-control" value="<?= e($search) ?>" placeholder="이름·직함·소속·이메일 검색" style="flex:1;min-width:200px;max-width:360px">
        <button type="submit" class="btn btn-outline">검색</button>
        <?php if ($search !== ''): ?>
        <a href="<?= url('resume.php') ?>" class="btn btn-outline">초기화</a>
        <?php endif; ?>
        <span style="font-size:12px;color:var(--text-muted);margin-left:auto">총 <?= (int) $totalCount ?>명</span>
    </form>
</div>

<?php if (empty($people)): ?>
<div class="card">
    <div class="empty-state">
        <p><?= $search !== '' ? '검색 결과가 없습니다.' : '등록된 인물이 없습니다.' ?>
        <?php if (!empty($canEdit) && $search === ''): ?> 「인물 추가」로 시작해 보세요.<?php endif; ?></p>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php if (!empty($canEdit)): ?><th style="width:36px"></th><?php endif; ?>
                    <th>이름</th>
                    <th style="width:160px">직함</th>
                    <th style="width:160px">소속</th>
                    <th style="width:180px">연락처</th>
                    <th style="width:70px">이력</th>
                    <th style="width:220px"></th>
                </tr>
            </thead>
            <tbody id="resumePeopleList" class="lu-sortable-list">
                <?php foreach ($people as $p): ?>
                <tr data-id="<?= (int) $p['id'] ?>">
                    <?php if (!empty($canEdit)): ?>
                    <td class="lu-drag-cell">
                        <span class="lu-drag" draggable="true" title="드래그하여 순서 변경" data-drag-handle>⋮⋮</span>
                    </td>
                    <?php endif; ?>
                    <td>
                        <a href="<?= url('resume.php?view=' . (int) $p['id'] . $qSuffix) ?>" style="font-weight:700;color:inherit;text-decoration:none">
                            <?= e($p['name']) ?>
                        </a>
                    </td>
                    <td style="font-size:13px"><?= $p['job_title'] ? e($p['job_title']) : '—' ?></td>
                    <td style="font-size:13px"><?= $p['organization'] ? e($p['organization']) : '—' ?></td>
                    <td style="font-size:12px;color:var(--text-secondary);line-height:1.45">
                        <?php if (!empty($p['email'])): ?><?= e($p['email']) ?><br><?php endif; ?>
                        <?= !empty($p['phone']) ? e($p['phone']) : '—' ?>
                    </td>
                    <td style="text-align:center;font-size:13px"><?= (int) $p['entry_count'] ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <a href="<?= url('resume.php?view=' . (int) $p['id'] . $qSuffix) ?>" class="btn btn-sm btn-outline">보기</a>
                            <a href="<?= url('resume.php?view=' . (int) $p['id'] . '&print=1') ?>" class="btn btn-sm btn-outline" target="_blank" rel="noopener">PDF</a>
                            <?php if (!empty($canEdit)): ?>
                            <a href="<?= url('resume.php?edit=' . (int) $p['id'] . $qSuffix) ?>" class="btn btn-sm btn-outline">편집</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('「<?= e($p['name']) ?>」인물과 이력을 모두 삭제할까요?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_person">
                                <input type="hidden" name="person_id" value="<?= (int) $p['id'] ?>">
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

<?php if (!empty($editPerson)): ?>
<div class="resume-edit-panel card" style="margin-top:20px">
    <div class="page-header-row" style="margin-bottom:16px">
        <div>
            <h2 style="font-size:18px;margin:0">이력 편집 — <?= e($editPerson['name']) ?></h2>
            <p style="margin:4px 0 0;font-size:13px;color:var(--text-muted)">학력 · 경력 · 수상 · 주요 프로젝트를 추가·수정·삭제합니다. ⋮⋮ 를 드래그해 순서를 바꿀 수 있습니다.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="<?= url('resume.php?view=' . (int) $editPerson['id']) ?>" class="btn btn-outline">보기</a>
            <a href="<?= url('resume.php?view=' . (int) $editPerson['id'] . '&print=1') ?>" class="btn btn-outline" target="_blank" rel="noopener">PDF 저장</a>
            <a href="<?= url('resume.php' . ($search !== '' ? '?q=' . urlencode($search) : '')) ?>" class="btn btn-outline">목록</a>
        </div>
    </div>

    <form method="post" class="resume-person-form" style="margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border)">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_person">
        <input type="hidden" name="person_id" value="<?= (int) $editPerson['id'] ?>">
        <div class="resume-form-grid">
            <div class="form-group">
                <label>이름 *</label>
                <input type="text" name="name" class="form-control" required maxlength="120" value="<?= e($editPerson['name']) ?>">
            </div>
            <div class="form-group">
                <label>직함</label>
                <input type="text" name="job_title" class="form-control" maxlength="200" value="<?= e(isset($editPerson['job_title']) ? $editPerson['job_title'] : '') ?>">
            </div>
            <div class="form-group">
                <label>소속</label>
                <input type="text" name="organization" class="form-control" maxlength="200" value="<?= e(isset($editPerson['organization']) ? $editPerson['organization'] : '') ?>">
            </div>
            <div class="form-group">
                <label>이메일</label>
                <input type="email" name="email" class="form-control" maxlength="200" value="<?= e(isset($editPerson['email']) ? $editPerson['email'] : '') ?>">
            </div>
            <div class="form-group">
                <label>연락처</label>
                <input type="text" name="phone" class="form-control" maxlength="50" value="<?= e(isset($editPerson['phone']) ? $editPerson['phone'] : '') ?>">
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label>소개 / 요약</label>
                <textarea name="summary" class="form-control" rows="3"><?= e(isset($editPerson['summary']) ? $editPerson['summary'] : '') ?></textarea>
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label>보유 기술 (쉼표 또는 줄바꿈)</label>
                <textarea name="skills" class="form-control" rows="2"><?= e(isset($editPerson['skills']) ? $editPerson['skills'] : '') ?></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">기본 정보 저장</button>
    </form>

    <?php foreach ($categories as $catKey => $catMeta): ?>
    <?php $rows = isset($editEntries[$catKey]) ? $editEntries[$catKey] : array(); ?>
    <section class="resume-cat" id="cat-<?= e($catKey) ?>">
        <div class="resume-cat-head">
            <h3><?= e($catMeta['label']) ?> <span class="resume-cat-count"><?= count($rows) ?></span></h3>
            <button type="button" class="btn btn-sm btn-primary"
                data-modal="resumeEntryModal"
                data-entry-mode="create"
                data-person-id="<?= (int) $editPerson['id'] ?>"
                data-category="<?= e($catKey) ?>">+ 추가</button>
        </div>
        <?php if (empty($rows)): ?>
        <p class="resume-empty">등록된 <?= e($catMeta['label']) ?>이(가) 없습니다.</p>
        <?php else: ?>
        <ul class="resume-entry-list lu-sortable-list" data-category="<?= e($catKey) ?>" data-person-id="<?= (int) $editPerson['id'] ?>">
            <?php foreach ($rows as $row): ?>
            <li class="resume-entry-item" data-id="<?= (int) $row['id'] ?>">
                <span class="lu-drag" draggable="true" title="드래그하여 순서 변경" data-drag-handle>⋮⋮</span>
                <div class="resume-entry-main">
                    <strong><?= e($row['title']) ?></strong>
                    <?php if (!empty($row['organization'])): ?>
                    <span class="resume-entry-org"><?= e($row['organization']) ?></span>
                    <?php endif; ?>
                    <?php $period = ResumeService::formatPeriod($row); if ($period !== ''): ?>
                    <span class="resume-entry-period"><?= e($period) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($row['description'])): ?>
                    <p class="resume-entry-desc"><?= nl2br(e($row['description'])) ?></p>
                    <?php endif; ?>
                </div>
                <div class="resume-entry-actions">
                    <?php
                    $entryPayload = json_encode(array(
                        'id' => (int) $row['id'],
                        'category' => $row['category'],
                        'title' => $row['title'],
                        'organization' => isset($row['organization']) ? $row['organization'] : '',
                        'period_start' => isset($row['period_start']) ? $row['period_start'] : '',
                        'period_end' => isset($row['period_end']) ? $row['period_end'] : '',
                        'is_current' => !empty($row['is_current']) ? 1 : 0,
                        'description' => isset($row['description']) ? $row['description'] : '',
                    ), JSON_UNESCAPED_UNICODE);
                    ?>
                    <button type="button" class="btn btn-sm btn-outline"
                        data-modal="resumeEntryModal"
                        data-entry-mode="edit"
                        data-person-id="<?= (int) $editPerson['id'] ?>"
                        data-payload="<?= e($entryPayload) ?>">수정</button>
                    <form method="post" style="display:inline" onsubmit="return confirm('이 이력을 삭제할까요?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_entry">
                        <input type="hidden" name="person_id" value="<?= (int) $editPerson['id'] ?>">
                        <input type="hidden" name="entry_id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">삭제</button>
                    </form>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($viewPerson) && empty($editPerson)): ?>
<div class="modal-overlay active" id="resumeViewModal">
    <div class="modal resume-view-modal" style="max-width:760px">
        <div class="modal-header">
            <h3>이력서 보기 — <?= e($viewPerson['name']) ?></h3>
            <div style="display:flex;gap:8px;align-items:center">
                <a href="<?= url('resume.php?view=' . (int) $viewPerson['id'] . '&print=1') ?>" class="btn btn-sm btn-primary" target="_blank" rel="noopener">PDF로 저장</a>
                <?php if (!empty($canEdit)): ?>
                <a href="<?= url('resume.php?edit=' . (int) $viewPerson['id']) ?>" class="btn btn-sm btn-outline">편집</a>
                <?php endif; ?>
                <a href="<?= url('resume.php' . ($search !== '' ? '?q=' . urlencode($search) : '')) ?>" class="modal-close" aria-label="닫기">&times;</a>
            </div>
        </div>
        <div class="modal-body">
            <?php
            $vp = $viewPerson;
            $ve = $viewEntries;
            include __DIR__ . '/resume-document.inc.php';
            ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($canEdit)): ?>
<div class="modal-overlay" id="resumePersonModal">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <h3 id="resumePersonModalTitle">인물 추가</h3>
            <button type="button" class="modal-close" data-close>&times;</button>
        </div>
        <form method="post" class="modal-body">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_person">
            <input type="hidden" name="person_id" id="resumePersonId" value="0">
            <div class="form-group">
                <label>이름 *</label>
                <input type="text" name="name" id="resumePersonName" class="form-control" required maxlength="120" placeholder="홍길동">
            </div>
            <div class="resume-form-grid">
                <div class="form-group">
                    <label>직함</label>
                    <input type="text" name="job_title" class="form-control" maxlength="200" placeholder="프론트엔드 개발자">
                </div>
                <div class="form-group">
                    <label>소속</label>
                    <input type="text" name="organization" class="form-control" maxlength="200" placeholder="회사/팀">
                </div>
                <div class="form-group">
                    <label>이메일</label>
                    <input type="email" name="email" class="form-control" maxlength="200">
                </div>
                <div class="form-group">
                    <label>연락처</label>
                    <input type="text" name="phone" class="form-control" maxlength="50">
                </div>
            </div>
            <div class="form-group">
                <label>소개 / 요약</label>
                <textarea name="summary" class="form-control" rows="3" placeholder="간단한 소개"></textarea>
            </div>
            <div class="form-group">
                <label>보유 기술</label>
                <textarea name="skills" class="form-control" rows="2" placeholder="PHP, JavaScript, MySQL"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close>취소</button>
                <button type="submit" class="btn btn-primary">저장 후 이력 편집</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="resumeEntryModal">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <h3 id="resumeEntryModalTitle">이력 추가</h3>
            <button type="button" class="modal-close" data-close>&times;</button>
        </div>
        <form method="post" class="modal-body">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_entry">
            <input type="hidden" name="person_id" id="resumeEntryPersonId" value="0">
            <input type="hidden" name="entry_id" id="resumeEntryId" value="0">
            <div class="form-group">
                <label>유형 *</label>
                <select name="category" id="resumeEntryCategory" class="form-control" required>
                    <?php foreach ($categories as $k => $m): ?>
                    <option value="<?= e($k) ?>"><?= e($m['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>제목 *</label>
                <input type="text" name="title" id="resumeEntryTitle" class="form-control" required maxlength="300" placeholder="학교/회사/수상명/프로젝트명">
            </div>
            <div class="form-group">
                <label>기관 · 소속</label>
                <input type="text" name="organization" id="resumeEntryOrg" class="form-control" maxlength="200">
            </div>
            <div class="resume-form-grid">
                <div class="form-group">
                    <label>시작</label>
                    <input type="text" name="period_start" id="resumeEntryStart" class="form-control" maxlength="40" placeholder="2020.03 또는 2020">
                </div>
                <div class="form-group">
                    <label>종료</label>
                    <input type="text" name="period_end" id="resumeEntryEnd" class="form-control" maxlength="40" placeholder="2024.02">
                </div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;font-weight:500">
                    <input type="checkbox" name="is_current" id="resumeEntryCurrent" value="1"> 현재 진행 중
                </label>
            </div>
            <div class="form-group">
                <label>설명</label>
                <textarea name="description" id="resumeEntryDesc" class="form-control" rows="4" placeholder="주요 내용, 성과, 역할 등"></textarea>
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
.resume-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:0 14px; }
.resume-cat { margin-bottom:22px; }
.resume-cat-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; }
.resume-cat-head h3 { margin:0; font-size:15px; }
.resume-cat-count { display:inline-flex; min-width:22px; height:22px; padding:0 7px; align-items:center; justify-content:center; border-radius:999px; background:var(--bg-subtle); font-size:12px; color:var(--text-muted); margin-left:6px; }
.resume-empty { font-size:13px; color:var(--text-muted); margin:0 0 8px; }
.resume-entry-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px; }
.resume-entry-item { display:flex; gap:10px; align-items:flex-start; justify-content:space-between; padding:12px 14px; border:1px solid var(--border); border-radius:10px; background:var(--bg-subtle, #f8fafc); }
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
.lu-sortable-list .resume-entry-item.is-drag-over,
.lu-sortable-list .ch-event-item.is-drag-over { box-shadow: inset 0 2px 0 #2563eb; }
.resume-entry-main { flex:1; min-width:0; }
.resume-entry-main strong { display:block; font-size:14px; margin-bottom:2px; }
.resume-entry-org, .resume-entry-period { display:inline-block; font-size:12px; color:var(--text-muted); margin-right:10px; }
.resume-entry-desc { margin:8px 0 0; font-size:13px; color:var(--text-secondary); line-height:1.5; }
.resume-entry-actions { display:flex; gap:6px; flex-shrink:0; }
.resume-doc { font-size:14px; line-height:1.55; color:var(--text); }
.resume-doc-head { margin:0 0 22px; padding:0 0 18px; border-bottom:3px solid #0f3d5e; }
.resume-doc-brand { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.resume-doc-eyebrow { margin:0; font-size:10px; font-weight:800; letter-spacing:.16em; text-transform:uppercase; color:#0f3d5e; }
.resume-doc-docmark { margin:0; font-size:9px; font-weight:700; letter-spacing:.1em; color:var(--text-muted); border:1px solid var(--border); padding:2px 7px; }
.resume-doc-name { margin:0 0 6px; font-size:24px; font-weight:800; letter-spacing:-.02em; }
.resume-doc-role { margin:0 0 10px; font-size:14px; font-weight:600; }
.resume-doc-role-sep { margin:0 6px; color:var(--text-muted); }
.resume-doc-role-org { color:var(--text-muted); font-weight:500; }
.resume-doc-contact { list-style:none; margin:0; padding:0; display:flex; flex-wrap:wrap; gap:6px 14px; font-size:13px; color:var(--text-secondary); }
.resume-doc-contact-label { display:inline-block; margin-right:6px; font-size:10px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--text-muted); }
.resume-doc-summary { margin-top:12px; display:grid; grid-template-columns:40px 1fr; gap:10px; padding:10px 12px; background:var(--bg-subtle,#f8fafc); border:1px solid var(--border); border-left:4px solid #0f3d5e; }
.resume-doc-summary-label { font-size:10px; font-weight:800; letter-spacing:.06em; color:#0f3d5e; }
.resume-doc-summary p { margin:0; font-size:13px; line-height:1.6; color:var(--text-secondary); }
.resume-doc-section { margin:0 0 18px; }
.resume-doc-section-head { display:flex; align-items:center; gap:8px; margin:0 0 0; padding:7px 10px; background:#0f3d5e; color:#fff; }
.resume-doc-section-num { font-size:11px; font-weight:800; opacity:.7; }
.resume-doc-section-head h3 { margin:0; flex:1; font-size:13px; font-weight:800; }
.resume-doc-section-count { font-size:11px; font-weight:700; background:rgba(255,255,255,.18); padding:2px 7px; border-radius:999px; }
.resume-doc-items { border:1px solid var(--border); border-top:none; }
.resume-doc-item { padding:12px 12px 12px 14px; border-bottom:1px solid var(--border); border-left:3px solid transparent; }
.resume-doc-item:nth-child(even) { background:var(--bg-subtle,#f8fafc); }
.resume-doc-item:last-child { border-bottom:none; }
.resume-doc-item-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; }
.resume-doc-item-title { font-size:14px; font-weight:800; }
.resume-doc-item-org { margin-top:2px; font-size:12px; color:var(--text-muted); }
.resume-doc-item-period { flex-shrink:0; font-size:11px; font-weight:700; color:#0f3d5e; background:#e8f0f6; padding:3px 8px; white-space:nowrap; border:1px solid #d5e4ef; }
.resume-doc-item-desc { margin:8px 0 0; padding-top:8px; border-top:1px dashed var(--border); font-size:13px; color:var(--text-secondary); line-height:1.55; }
.resume-doc-skills { display:flex; flex-wrap:wrap; gap:6px; padding:12px; border:1px solid var(--border); border-top:none; background:var(--bg-subtle,#f8fafc); }
.resume-doc-skill { font-size:12px; font-weight:600; padding:4px 10px; background:#fff; border:1px solid var(--border); }
@media (max-width:700px) {
    .resume-form-grid { grid-template-columns:1fr; }
    .resume-entry-item { flex-direction:column; }
}
</style>

<script src="<?= asset('js/sortable-list.js') ?>"></script>
<script>
(function () {
    var csrf = <?= json_encode(csrf_token(), JSON_UNESCAPED_UNICODE) ?>;
    var saveUrl = <?= json_encode(url('resume.php'), JSON_UNESCAPED_UNICODE) ?>;

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

    document.querySelectorAll('[data-modal="resumePersonModal"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var title = document.getElementById('resumePersonModalTitle');
            var idInput = document.getElementById('resumePersonId');
            if (title) title.textContent = '인물 추가';
            if (idInput) idInput.value = '0';
            var form = document.querySelector('#resumePersonModal form');
            if (form) form.reset();
            openModal('resumePersonModal');
        });
    });

    document.querySelectorAll('[data-modal="resumeEntryModal"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var mode = btn.getAttribute('data-entry-mode') || 'create';
            var titleEl = document.getElementById('resumeEntryModalTitle');
            if (titleEl) titleEl.textContent = mode === 'edit' ? '이력 수정' : '이력 추가';
            document.getElementById('resumeEntryPersonId').value = btn.getAttribute('data-person-id') || '0';

            var payload = null;
            try {
                payload = JSON.parse(btn.getAttribute('data-payload') || 'null');
            } catch (err) { payload = null; }

            if (payload) {
                document.getElementById('resumeEntryId').value = String(payload.id || 0);
                document.getElementById('resumeEntryCategory').value = payload.category || 'education';
                document.getElementById('resumeEntryTitle').value = payload.title || '';
                document.getElementById('resumeEntryOrg').value = payload.organization || '';
                document.getElementById('resumeEntryStart').value = payload.period_start || '';
                document.getElementById('resumeEntryEnd').value = payload.period_end || '';
                document.getElementById('resumeEntryCurrent').checked = !!payload.is_current;
                document.getElementById('resumeEntryDesc').value = payload.description || '';
            } else {
                document.getElementById('resumeEntryId').value = '0';
                document.getElementById('resumeEntryCategory').value = btn.getAttribute('data-category') || 'education';
                document.getElementById('resumeEntryTitle').value = '';
                document.getElementById('resumeEntryOrg').value = '';
                document.getElementById('resumeEntryStart').value = '';
                document.getElementById('resumeEntryEnd').value = '';
                document.getElementById('resumeEntryCurrent').checked = false;
                document.getElementById('resumeEntryDesc').value = '';
            }
            var endInput = document.getElementById('resumeEntryEnd');
            var curInput = document.getElementById('resumeEntryCurrent');
            if (endInput && curInput) endInput.disabled = curInput.checked;
            openModal('resumeEntryModal');
        });
    });

    var cur = document.getElementById('resumeEntryCurrent');
    var end = document.getElementById('resumeEntryEnd');
    if (cur && end) {
        cur.addEventListener('change', function () {
            end.disabled = cur.checked;
            if (cur.checked) end.value = '';
        });
    }

    if (window.LabelUpSortable) {
        var peopleList = document.getElementById('resumePeopleList');
        if (peopleList) {
            LabelUpSortable.init(peopleList, {
                itemSelector: 'tr[data-id]',
                url: saveUrl,
                buildPayload: function (ids) {
                    return {
                        action: 'reorder_people',
                        ordered_ids: JSON.stringify(ids),
                        _csrf: csrf
                    };
                }
            });
        }
        document.querySelectorAll('.resume-entry-list[data-category]').forEach(function (list) {
            LabelUpSortable.init(list, {
                itemSelector: 'li[data-id]',
                url: saveUrl,
                buildPayload: function (ids) {
                    return {
                        action: 'reorder_entries',
                        person_id: list.getAttribute('data-person-id') || '0',
                        category: list.getAttribute('data-category') || '',
                        ordered_ids: JSON.stringify(ids),
                        _csrf: csrf
                    };
                }
            });
        });
    }
})();
</script>
