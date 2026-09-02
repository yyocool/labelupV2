<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>정책관리</h1>
            <p>서비스 이용·개인정보·쇼핑·AI·디자인 등 운영 정책을 등록·관리합니다</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="<?= url('policies.php?print=1' . ($filterCategory !== 'all' ? '&category=' . urlencode($filterCategory) : '') . ($filterStatus !== 'all' ? '&status=' . urlencode($filterStatus) : '')) ?>" class="btn btn-primary btn-sm" target="_blank" rel="noopener">PDF로 저장</a>
            <?php if (is_admin()): ?>
            <button type="button" class="btn btn-outline" data-modal="policyModal" data-policy-mode="create">+ 정책 등록</button>
            <form method="post" style="display:inline" onsubmit="return confirm('기존 정책을 모두 삭제하고 기본 정책을 다시 등록합니다. 계속할까요?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reseed">
                <button type="submit" class="btn btn-outline">기본 정책 재등록</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="archive-filters card" style="margin-bottom:16px;padding:12px 16px">
    <div class="archive-filter-tabs">
        <a href="<?= url('policies.php') ?>" class="archive-filter-tab <?= $filterCategory === 'all' ? 'active' : '' ?>">
            전체 <span class="archive-filter-count"><?= (int) $categoryCounts['all'] ?></span>
        </a>
        <?php foreach ($categories as $key => $meta): ?>
        <a href="<?= url('policies.php?category=' . urlencode($key)) ?>" class="archive-filter-tab <?= $filterCategory === $key ? 'active' : '' ?>">
            <?= e($meta['icon'] . ' ' . $meta['label']) ?>
            <span class="archive-filter-count"><?= (int) (isset($categoryCounts[$key]) ? $categoryCounts[$key] : 0) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <div style="padding:12px 16px;border-top:1px solid var(--border);display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <span style="font-size:12px;color:var(--text-muted)">상태</span>
        <?php
        $statusQuery = $filterCategory !== 'all' ? 'category=' . urlencode($filterCategory) . '&' : '';
        ?>
        <a href="<?= url('policies.php?' . ($filterCategory !== 'all' ? 'category=' . urlencode($filterCategory) : '')) ?>" class="badge <?= $filterStatus === 'all' ? 'badge-primary' : 'badge-gray' ?>">전체</a>
        <?php foreach ($statuses as $sKey => $sMeta): ?>
        <a href="<?= url('policies.php?' . ($filterCategory !== 'all' ? 'category=' . urlencode($filterCategory) . '&' : '') . 'status=' . urlencode($sKey)) ?>" class="badge <?= $filterStatus === $sKey ? 'badge-primary' : 'badge-gray' ?>"><?= e($sMeta['label']) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (empty($policies)): ?>
<div class="card"><div class="empty-state"><p>등록된 정책이 없습니다.<?php if (is_admin()): ?> 「기본 정책 재등록」으로 Label-UP 기본 정책을 불러올 수 있습니다.<?php endif; ?></p></div></div>
<?php else: ?>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:110px">카테고리</th>
                    <th>정책명</th>
                    <th style="width:90px">버전</th>
                    <th style="width:80px">상태</th>
                    <th style="width:100px">대상</th>
                    <th style="width:90px">메뉴코드</th>
                    <th style="width:120px">수정</th>
                    <?php if (is_admin()): ?><th style="width:100px"></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($policies as $policy): ?>
                <?php
                    $cat = isset($categories[$policy['category']]) ? $categories[$policy['category']] : array('label' => $policy['category'], 'icon' => '📄');
                    $st = isset($statuses[$policy['status']]) ? $statuses[$policy['status']] : array('label' => $policy['status'], 'class' => 'badge-gray');
                    $aud = isset($audiences[$policy['audience']]) ? $audiences[$policy['audience']] : $policy['audience'];
                ?>
                <tr>
                    <td><span class="badge badge-gray"><?= e($cat['icon'] . ' ' . $cat['label']) ?></span></td>
                    <td>
                        <strong><?= e($policy['title']) ?></strong>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;line-height:1.45"><?= e($policy['summary']) ?></div>
                        <code style="font-size:10px;color:var(--text-muted)"><?= e($policy['policy_key']) ?></code>
                    </td>
                    <td>v<?= e($policy['version']) ?></td>
                    <td><span class="badge <?= e($st['class']) ?>"><?= e($st['label']) ?></span></td>
                    <td style="font-size:12px"><?= e($aud) ?></td>
                    <td><?= $policy['related_menu_code'] ? '<code>' . e($policy['related_menu_code']) . '</code>' : '—' ?></td>
                    <td style="font-size:11px;color:var(--text-muted)">
                        <?= e(isset($policy['updater_name']) ? $policy['updater_name'] : '') ?>
                        <?php if (!empty($policy['updated_at'])): ?><br><?= time_ago($policy['updated_at']) ?><?php endif; ?>
                    </td>
                    <?php if (is_admin()): ?>
                    <td>
                        <a href="<?= url('policies.php?edit=' . (int) $policy['id'] . ($filterCategory !== 'all' ? '&category=' . urlencode($filterCategory) : '') . ($filterStatus !== 'all' ? '&status=' . urlencode($filterStatus) : '')) ?>" class="btn btn-sm btn-outline">편집</a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (is_admin()): ?>
<div class="modal-overlay" id="policyModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 id="policyModalTitle">정책 등록</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <form method="post" id="policyForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="policy_id" id="policy_id" value="0">
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>정책 키 (영문)</label>
                    <input type="text" name="policy_key" id="policy_key" class="form-control" placeholder="terms_of_service" required>
                </div>
                <div class="form-group" style="width:140px">
                    <label>버전</label>
                    <input type="text" name="version" id="policy_version" class="form-control" value="1.0">
                </div>
            </div>
            <div class="form-group">
                <label>정책명</label>
                <input type="text" name="title" id="policy_title" class="form-control" required>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>카테고리</label>
                    <select name="category" id="policy_category" class="form-control">
                        <?php foreach ($categories as $key => $meta): ?>
                        <option value="<?= e($key) ?>"><?= e($meta['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1">
                    <label>상태</label>
                    <select name="status" id="policy_status" class="form-control">
                        <?php foreach ($statuses as $key => $meta): ?>
                        <option value="<?= e($key) ?>"><?= e($meta['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1">
                    <label>대상</label>
                    <select name="audience" id="policy_audience" class="form-control">
                        <?php foreach ($audiences as $key => $label): ?>
                        <option value="<?= e($key) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1">
                    <label>연결 메뉴코드 (선택)</label>
                    <input type="text" name="related_menu_code" id="policy_menu_code" class="form-control" placeholder="01-08">
                </div>
                <div class="form-group" style="width:120px">
                    <label>정렬</label>
                    <input type="number" name="sort_order" id="policy_sort_order" class="form-control" value="0">
                </div>
            </div>
            <div class="form-group">
                <label>요약</label>
                <input type="text" name="summary" id="policy_summary" class="form-control" maxlength="500">
            </div>
            <div class="form-group">
                <label>정책 본문</label>
                <textarea name="content" id="policy_content" class="form-control" rows="12" required></textarea>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <button type="submit" class="btn btn-primary">저장</button>
                <button type="button" class="btn btn-outline modal-close">취소</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    function fillPolicyForm(data) {
        document.getElementById('policyModalTitle').textContent = data.id ? '정책 수정' : '정책 등록';
        document.getElementById('policy_id').value = data.id || 0;
        document.getElementById('policy_key').value = data.policy_key || '';
        document.getElementById('policy_title').value = data.title || '';
        document.getElementById('policy_category').value = data.category || 'service';
        document.getElementById('policy_status').value = data.status || 'draft';
        document.getElementById('policy_audience').value = data.audience || 'customer';
        document.getElementById('policy_version').value = data.version || '1.0';
        document.getElementById('policy_menu_code').value = data.related_menu_code || '';
        document.getElementById('policy_sort_order').value = data.sort_order || 0;
        document.getElementById('policy_summary').value = data.summary || '';
        document.getElementById('policy_content').value = data.content || '';
    }

    document.querySelectorAll('[data-policy-mode="create"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            fillPolicyForm({});
        });
    });

    document.querySelectorAll('#policyModal .modal-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (window.location.search.indexOf('edit=') !== -1) {
                var url = new URL(window.location.href);
                url.searchParams.delete('edit');
                window.history.replaceState({}, '', url.pathname + (url.search ? url.search : ''));
            }
        });
    });

    <?php if (!empty($editPolicy)): ?>
    document.addEventListener('DOMContentLoaded', function () {
        fillPolicyForm(<?= json_encode(array(
            'id' => (int) $editPolicy['id'],
            'policy_key' => $editPolicy['policy_key'],
            'category' => $editPolicy['category'],
            'title' => $editPolicy['title'],
            'summary' => $editPolicy['summary'],
            'content' => $editPolicy['content'],
            'version' => $editPolicy['version'],
            'status' => $editPolicy['status'],
            'audience' => $editPolicy['audience'],
            'related_menu_code' => $editPolicy['related_menu_code'],
            'sort_order' => (int) $editPolicy['sort_order'],
        ), JSON_UNESCAPED_UNICODE) ?>);
        var modal = document.getElementById('policyModal');
        if (modal) modal.classList.add('active');
    });
    <?php endif; ?>
})();
</script>
<?php endif; ?>
