<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>회의록</h1>
            <p>팀 회의 일정·참석자·안건·결정 사항을 기록·관리합니다</p>
        </div>
        <?php if (is_admin()): ?>
        <button type="button" class="btn btn-primary" data-minute-mode="create">+ 회의록 등록</button>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-bottom:16px;padding:14px 16px">
    <form method="get" action="<?= url('meeting-minutes.php') ?>" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input type="search" name="q" class="form-control" value="<?= e($search) ?>" placeholder="제목·참석자·장소·내용 검색" style="flex:1;min-width:200px;max-width:360px">
        <button type="submit" class="btn btn-outline">검색</button>
        <?php if ($search !== ''): ?>
        <a href="<?= url('meeting-minutes.php') ?>" class="btn btn-outline">초기화</a>
        <?php endif; ?>
        <span style="font-size:12px;color:var(--text-muted);margin-left:auto">총 <?= (int) $totalCount ?>건</span>
    </form>
</div>

<?php if (empty($minutes)): ?>
<div class="card"><div class="empty-state"><p><?= $search !== '' ? '검색 결과가 없습니다.' : '등록된 회의록이 없습니다.' ?><?php if (is_admin() && $search === ''): ?> 「회의록 등록」으로 첫 회의록을 추가해 보세요.<?php endif; ?></p></div></div>
<?php else: ?>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:130px">회의일</th>
                    <th>제목</th>
                    <th style="width:120px">장소</th>
                    <th style="width:160px">참석자</th>
                    <th style="width:100px">작성</th>
                    <th style="width:140px"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($minutes as $minute): ?>
                <tr>
                    <td style="white-space:nowrap;font-size:13px">
                        <?= e(MeetingMinutesService::formatDateLabel($minute['meeting_date'])) ?>
                        <?php if (!empty($minute['meeting_time'])): ?>
                        <div style="font-size:11px;color:var(--text-muted)"><?= e(MeetingMinutesService::formatTimeLabel($minute['meeting_time'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= url('meeting-minutes.php?view=' . (int) $minute['id'] . ($search !== '' ? '&q=' . urlencode($search) : '')) ?>" style="font-weight:600;color:inherit;text-decoration:none">
                            <?= e($minute['title']) ?>
                        </a>
                        <?php if (!empty($minute['agenda'])): ?>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;line-height:1.45"><?= e(mb_strimwidth($minute['agenda'], 0, 80, '…', 'UTF-8')) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px"><?= $minute['location'] ? e($minute['location']) : '—' ?></td>
                    <td style="font-size:12px;color:var(--text-secondary);line-height:1.45"><?= $minute['attendees'] ? e(mb_strimwidth(str_replace(array("\r\n", "\n", "\r"), ', ', $minute['attendees']), 0, 48, '…', 'UTF-8')) : '—' ?></td>
                    <td style="font-size:11px;color:var(--text-muted)">
                        <?= e(isset($minute['creator_name']) ? $minute['creator_name'] : '') ?>
                        <?php if (!empty($minute['created_at'])): ?><br><?= time_ago($minute['created_at']) ?><?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <a href="<?= url('meeting-minutes.php?view=' . (int) $minute['id'] . ($search !== '' ? '&q=' . urlencode($search) : '')) ?>" class="btn btn-sm btn-outline">보기</a>
                            <?php if (is_admin()): ?>
                            <a href="<?= url('meeting-minutes.php?edit=' . (int) $minute['id'] . ($search !== '' ? '&q=' . urlencode($search) : '')) ?>" class="btn btn-sm btn-outline">편집</a>
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

<?php if (!empty($viewMinute)): ?>
<div class="modal-overlay active" id="minuteViewModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3><?= e($viewMinute['title']) ?></h3>
            <a href="<?= url('meeting-minutes.php' . ($search !== '' ? '?q=' . urlencode($search) : '')) ?>" class="modal-close" style="text-decoration:none">&times;</a>
        </div>
        <div style="padding:0 4px 8px">
            <dl class="mm-meta-grid">
                <div><dt>회의일</dt><dd><?= e(MeetingMinutesService::formatDateLabel($viewMinute['meeting_date'])) ?><?php if (!empty($viewMinute['meeting_time'])): ?> <?= e(MeetingMinutesService::formatTimeLabel($viewMinute['meeting_time'])) ?><?php endif; ?></dd></div>
                <div><dt>장소</dt><dd><?= $viewMinute['location'] ? e($viewMinute['location']) : '—' ?></dd></div>
                <div><dt>작성</dt><dd><?= e(isset($viewMinute['creator_name']) ? $viewMinute['creator_name'] : '') ?><?php if (!empty($viewMinute['updated_at'])): ?> · 수정 <?= time_ago($viewMinute['updated_at']) ?><?php endif; ?></dd></div>
            </dl>
            <?php if (!empty($viewMinute['attendees'])): ?>
            <div class="mm-section">
                <h4>참석자</h4>
                <p><?= nl2br(e($viewMinute['attendees'])) ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($viewMinute['agenda'])): ?>
            <div class="mm-section">
                <h4>안건</h4>
                <p><?= nl2br(e($viewMinute['agenda'])) ?></p>
            </div>
            <?php endif; ?>
            <div class="mm-section">
                <h4>회의 내용</h4>
                <div class="mm-content mm-rich-content"><?= rich_html_display($viewMinute['content']) ?></div>
            </div>
            <?php if (is_admin()): ?>
            <div style="display:flex;gap:8px;margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
                <a href="<?= url('meeting-minutes.php?edit=' . (int) $viewMinute['id'] . ($search !== '' ? '&q=' . urlencode($search) : '')) ?>" class="btn btn-primary">편집</a>
                <form method="post" style="display:inline" onsubmit="return confirm('이 회의록을 삭제할까요?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="minute_id" value="<?= (int) $viewMinute['id'] ?>">
                    <button type="submit" class="btn btn-outline" style="color:var(--danger,#dc2626);border-color:var(--danger,#dc2626)">삭제</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (is_admin()): ?>
<div class="modal-overlay" id="minuteModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 id="minuteModalTitle">회의록 등록</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <form method="post" id="minuteForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="minute_id" id="minute_id" value="0">
            <div class="form-group">
                <label>회의 제목</label>
                <input type="text" name="title" id="minute_title" class="form-control" required maxlength="300">
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>회의일</label>
                    <input type="date" name="meeting_date" id="minute_meeting_date" class="form-control" required>
                </div>
                <div class="form-group" style="width:140px">
                    <label>시간 (선택)</label>
                    <input type="time" name="meeting_time" id="minute_meeting_time" class="form-control">
                </div>
                <div class="form-group" style="flex:1">
                    <label>장소 (선택)</label>
                    <input type="text" name="location" id="minute_location" class="form-control" maxlength="200" placeholder="회의실, Zoom 등">
                </div>
            </div>
            <div class="form-group">
                <label>참석자 (선택)</label>
                <textarea name="attendees" id="minute_attendees" class="form-control" rows="2" placeholder="이름을 줄바꿈 또는 쉼표로 구분"></textarea>
            </div>
            <div class="form-group">
                <label>안건 (선택)</label>
                <textarea name="agenda" id="minute_agenda" class="form-control" rows="3" placeholder="회의 안건을 입력하세요"></textarea>
            </div>
            <div class="form-group">
                <label>회의 내용</label>
                <textarea name="content" id="minute_content" class="form-control summernote-target" rows="12" placeholder="논의 내용, 결정 사항, 후속 조치 등"></textarea>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <button type="submit" class="btn btn-primary">저장</button>
                <button type="button" class="btn btn-outline modal-close">취소</button>
                <button type="button" class="btn btn-outline" id="minuteDeleteBtn" style="display:none;margin-left:auto;color:var(--danger,#dc2626);border-color:var(--danger,#dc2626)">삭제</button>
            </div>
        </form>
        <form method="post" id="minuteDeleteForm" style="display:none">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="minute_id" id="minute_delete_id" value="0">
        </form>
    </div>
</div>

<style>
.mm-meta-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin:0 0 16px; padding:12px 14px; background:var(--bg,#f8fafc); border-radius:8px; border:1px solid var(--border); }
.mm-meta-grid dt { font-size:11px; font-weight:600; color:var(--text-muted); margin-bottom:4px; }
.mm-meta-grid dd { margin:0; font-size:13px; }
.mm-section { margin-bottom:16px; }
.mm-section h4 { margin:0 0 8px; font-size:13px; font-weight:700; color:var(--text); }
.mm-section p, .mm-content { margin:0; font-size:14px; line-height:1.7; color:var(--text-secondary); white-space:pre-wrap; }
.mm-rich-content { white-space:normal; }
.mm-rich-content p { margin:0 0 10px; }
.mm-rich-content ul, .mm-rich-content ol { margin:0 0 10px; padding-left:1.4em; }
.mm-rich-content img { max-width:100%; height:auto; border-radius:6px; }
.mm-rich-content table { width:100%; border-collapse:collapse; margin:8px 0; font-size:13px; }
.mm-rich-content th, .mm-rich-content td { border:1px solid var(--border); padding:6px 8px; }
#minuteModal .note-editor { border:1px solid var(--border); border-radius:8px; overflow:hidden; }
#minuteModal .note-toolbar { background:#f8fafc; border-bottom:1px solid var(--border); }
#minuteModal .note-editable { min-height:240px; font-size:14px; line-height:1.65; }
</style>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-ko-KR.min.js"></script>
<script>
(function () {
    var summernoteReady = false;

    function isSummernoteActive() {
        return window.jQuery && jQuery('#minute_content').next('.note-editor').length > 0;
    }

    function destroyMinuteSummernote() {
        if (!window.jQuery) return;
        var $el = jQuery('#minute_content');
        if ($el.next('.note-editor').length) {
            $el.summernote('destroy');
        }
        summernoteReady = false;
    }

    function initMinuteSummernote() {
        if (!window.jQuery || !jQuery.fn.summernote) return;
        var $el = jQuery('#minute_content');
        if (!$el.length) return;
        if ($el.next('.note-editor').length) {
            summernoteReady = true;
            return;
        }
        $el.summernote({
            lang: 'ko-KR',
            height: 280,
            placeholder: '논의 내용, 결정 사항, 후속 조치 등',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'table', 'hr']],
                ['view', ['fullscreen', 'codeview']]
            ],
            callbacks: {
                onInit: function () {
                    summernoteReady = true;
                }
            }
        });
        summernoteReady = true;
    }

    function setMinuteContent(html) {
        if (isSummernoteActive()) {
            jQuery('#minute_content').summernote('code', html || '');
            return;
        }
        var el = document.getElementById('minute_content');
        if (el) el.value = html || '';
    }

    function getMinuteContent() {
        if (isSummernoteActive()) {
            return jQuery('#minute_content').summernote('code') || '';
        }
        var el = document.getElementById('minute_content');
        return el ? el.value : '';
    }

    function isEmptyRichHtml(html) {
        var text = (html || '').replace(/<[^>]+>/g, '').replace(/&nbsp;/gi, '').replace(/\s+/g, '');
        return text === '';
    }

    function fillMinuteForm(data) {
        document.getElementById('minuteModalTitle').textContent = data.id ? '회의록 수정' : '회의록 등록';
        document.getElementById('minute_id').value = data.id || 0;
        document.getElementById('minute_delete_id').value = data.id || 0;
        document.getElementById('minute_title').value = data.title || '';
        document.getElementById('minute_meeting_date').value = data.meeting_date || '';
        document.getElementById('minute_meeting_time').value = data.meeting_time ? data.meeting_time.substring(0, 5) : '';
        document.getElementById('minute_location').value = data.location || '';
        document.getElementById('minute_attendees').value = data.attendees || '';
        document.getElementById('minute_agenda').value = data.agenda || '';
        setMinuteContent(data.content || '');
        var delBtn = document.getElementById('minuteDeleteBtn');
        if (delBtn) delBtn.style.display = data.id ? 'inline-flex' : 'none';
    }

    function openMinuteEditor(data) {
        fillMinuteForm(data || { meeting_date: new Date().toISOString().slice(0, 10) });
        var modal = document.getElementById('minuteModal');
        if (modal) modal.classList.add('active');
        setTimeout(function () {
            initMinuteSummernote();
            if (data && data.content) setMinuteContent(data.content);
        }, 30);
    }

    document.querySelectorAll('[data-minute-mode="create"]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            destroyMinuteSummernote();
            openMinuteEditor({ meeting_date: new Date().toISOString().slice(0, 10) });
        });
    });

    var minuteForm = document.getElementById('minuteForm');
    if (minuteForm) {
        minuteForm.addEventListener('submit', function (e) {
            var html = getMinuteContent();
            if (isEmptyRichHtml(html)) {
                e.preventDefault();
                alert('회의 내용을 입력하세요.');
                return;
            }
            document.getElementById('minute_content').value = html;
        });
    }

    var delBtn = document.getElementById('minuteDeleteBtn');
    if (delBtn) {
        delBtn.addEventListener('click', function () {
            if (!confirm('이 회의록을 삭제할까요?')) return;
            document.getElementById('minuteDeleteForm').submit();
        });
    }

    document.querySelectorAll('#minuteModal .modal-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            destroyMinuteSummernote();
            if (window.location.search.indexOf('edit=') !== -1) {
                var url = new URL(window.location.href);
                url.searchParams.delete('edit');
                window.history.replaceState({}, '', url.pathname + (url.search ? url.search : ''));
            }
        });
    });

    var minuteModal = document.getElementById('minuteModal');
    if (minuteModal) {
        minuteModal.addEventListener('click', function (e) {
            if (e.target === minuteModal) {
                destroyMinuteSummernote();
            }
        });
    }

    <?php if (!empty($editMinute)): ?>
    document.addEventListener('DOMContentLoaded', function () {
        openMinuteEditor(<?= json_encode(array(
            'id' => (int) $editMinute['id'],
            'title' => $editMinute['title'],
            'meeting_date' => $editMinute['meeting_date'],
            'meeting_time' => $editMinute['meeting_time'],
            'location' => $editMinute['location'],
            'attendees' => $editMinute['attendees'],
            'agenda' => $editMinute['agenda'],
            'content' => $editMinute['content'],
        ), JSON_UNESCAPED_UNICODE) ?>);
    });
    <?php endif; ?>
})();
</script>
<?php endif; ?>
