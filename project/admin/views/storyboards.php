<div class="page-header">

    <div class="page-header-row">

        <div>

            <h1>스토리보드 관리</h1>

            <p>메뉴별 스토리보드를 등록하고, 작업중/공개 상태를 관리합니다. 공개로 설정하면 팀원이 스토리보드 메뉴에서 볼 수 있습니다.</p>

        </div>

        <?php if (!empty($menusWithoutStoryboard)): ?>

        <button class="btn btn-primary" data-modal="createStoryboardModal">+ 스토리보드 등록</button>

        <?php endif; ?>

    </div>

</div>



<div class="card">

    <div class="table-wrap">

        <table>

            <thead>

                <tr>

                    <th>메뉴</th>

                    <th>스토리보드</th>

                    <th>화면 수</th>

                    <th>공개 상태</th>

                    <th>수정일</th>

                    <th>관리</th>

                </tr>

            </thead>

            <tbody>

            <?php foreach ($storyboardList as $row): ?>

            <tr>

                <td><?= str_repeat('— ', (int) $row['depth']) . e($row['menu_title']) ?></td>

                <td>

                    <?php if (!empty($row['storyboard_id'])): ?>

                        <?= e($row['storyboard_title']) ?>

                    <?php else: ?>

                        <span class="text-muted">미등록</span>

                    <?php endif; ?>

                </td>

                <td><?= !empty($row['storyboard_id']) ? (int) $row['frame_count'] : '-' ?></td>

                <td>

                    <?php if (!empty($row['storyboard_id'])): ?>

                    <form method="post" class="sb-visibility-form">

                        <?= csrf_field() ?>

                        <input type="hidden" name="action" value="set_visibility">

                        <input type="hidden" name="storyboard_id" value="<?= (int) $row['storyboard_id'] ?>">

                        <select name="visibility" class="form-control form-control-sm sb-visibility-select" onchange="this.form.submit()">

                            <?php foreach ($visibilityOptions as $key => $opt): ?>

                            <option value="<?= e($key) ?>" <?= (isset($row['visibility']) ? $row['visibility'] : 'working') === $key ? 'selected' : '' ?>>

                                <?= e($opt['label']) ?>

                            </option>

                            <?php endforeach; ?>

                        </select>

                    </form>

                    <?php else: ?>

                    —

                    <?php endif; ?>

                </td>

                <td>

                    <?= !empty($row['updated_at']) ? e(date('Y-m-d H:i', strtotime($row['updated_at']))) : '-' ?>

                </td>

                <td>

                    <?php if (!empty($row['storyboard_id'])): ?>

                    <a href="<?= admin_url('storyboard.php?menu_id=' . $row['menu_id']) ?>" class="btn btn-primary btn-sm">편집</a>

                    <a href="<?= url('storyboard.php?menu_id=' . $row['menu_id']) ?>" class="btn btn-secondary btn-sm" target="_blank">미리보기</a>

                    <?php else: ?>

                    <form method="post" style="display:inline">

                        <?= csrf_field() ?>

                        <input type="hidden" name="action" value="create_storyboard">

                        <input type="hidden" name="menu_id" value="<?= (int) $row['menu_id'] ?>">

                        <button type="submit" class="btn btn-secondary btn-sm">등록</button>

                    </form>

                    <?php endif; ?>

                </td>

            </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>



<?php if (!empty($menusWithoutStoryboard)): ?>

<div class="modal-overlay" id="createStoryboardModal">

    <div class="modal">

        <div class="modal-header">

            <h3>스토리보드 등록</h3>

            <button type="button" class="modal-close">&times;</button>

        </div>

        <form method="post">

            <?= csrf_field() ?>

            <input type="hidden" name="action" value="create_storyboard">

            <div class="form-group">

                <label>메뉴 선택</label>

                <select name="menu_id" class="form-control" required>

                    <?php foreach ($menusWithoutStoryboard as $row): ?>

                    <option value="<?= (int) $row['menu_id'] ?>"><?= str_repeat('— ', (int) $row['depth']) . e($row['menu_title']) ?></option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label>스토리보드 제목</label>

                <input type="text" name="title" class="form-control" placeholder="미입력 시 메뉴명 + 스토리보드">

            </div>

            <div class="form-group">

                <label>설명 (선택)</label>

                <textarea name="description" class="form-control" rows="3"></textarea>

            </div>

            <button type="submit" class="btn btn-primary">등록</button>

        </form>

    </div>

</div>

<?php endif; ?>

