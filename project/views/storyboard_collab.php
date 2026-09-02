        <!-- 팀 의견 & 변경 이력 -->
        <div class="sb-collab-panel">
            <div class="tabs sb-collab-tabs">
                <button type="button" class="tab active" data-tab="sbTabComments">💬 팀 의견 <span class="sb-tab-count"><?= count($comments) ?></span></button>
                <button type="button" class="tab" data-tab="sbTabHistory">📋 변경 이력 <span class="sb-tab-count"><?= count($history) ?></span></button>
            </div>

            <div id="sbTabComments" class="tab-panel sb-collab-body">
                <form method="post" class="sb-comment-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_comment">
                    <input type="hidden" name="frame_id" id="commentFrameId" value="<?= (int) $activeFrameId ?>">
                    <div class="sb-comment-form-row">
                        <textarea name="content" class="form-control" rows="3" required placeholder="의견, 피드백, 제안 사항을 자유롭게 작성해 주세요..."></textarea>
                        <div class="sb-comment-form-meta">
                            <?php if (!empty($frames)): ?>
                            <select name="comment_scope" id="commentScope" class="form-control">
                                <option value="frame">현재 화면에 의견</option>
                                <option value="menu">메뉴 전체에 의견</option>
                            </select>
                            <?php else: ?>
                            <input type="hidden" name="comment_scope" value="menu">
                            <span class="sb-comment-scope-label">메뉴 전체 의견</span>
                            <?php endif; ?>
                            <label class="sb-filter-check" id="sbFilterCurrentWrap" style="<?= empty($frames) ? 'display:none' : '' ?>">
                                <input type="checkbox" id="sbFilterCurrent"> 현재 화면만
                            </label>
                            <button type="submit" class="btn btn-primary">의견 등록</button>
                        </div>
                    </div>
                </form>

                <div class="sb-comment-list" id="sbCommentList">
                    <?php if (empty($comments)): ?>
                    <div class="sb-collab-empty">아직 등록된 의견이 없습니다. 첫 의견을 남겨 보세요!</div>
                    <?php else: ?>
                    <?php foreach ($comments as $c): ?>
                    <div class="sb-comment-item" data-frame-id="<?= e($c['frame_id'] ? $c['frame_id'] : '0') ?>">
                        <span class="avatar" style="background:<?= e($c['avatar_color']) ?>"><?= e(avatar_initials($c['user_name'])) ?></span>
                        <div class="sb-comment-body">
                            <div class="sb-comment-head">
                                <strong><?= e($c['user_name']) ?></strong>
                                <span class="sb-comment-role"><?= e(role_label($c['user_role'])) ?></span>
                                <?php if ($c['frame_id'] && $c['frame_title']): ?>
                                <span class="badge badge-light sb-comment-frame-tag" data-frame-id="<?= $c['frame_id'] ?>"><?= e($c['frame_title']) ?></span>
                                <?php else: ?>
                                <span class="badge badge-blue">메뉴 전체</span>
                                <?php endif; ?>
                                <span class="sb-comment-time"><?= time_ago($c['created_at']) ?></span>
                            </div>
                            <p class="sb-comment-text"><?= nl2br(e($c['content'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div id="sbTabHistory" class="tab-panel sb-collab-body" style="display:none">
                <div class="sb-history-list" id="sbHistoryList">
                    <?php if (empty($history)): ?>
                    <div class="sb-collab-empty">변경 이력이 없습니다.</div>
                    <?php else: ?>
                    <?php foreach ($history as $h): ?>
                    <div class="sb-history-item" data-frame-id="<?= e($h['frame_id'] ? $h['frame_id'] : '0') ?>">
                        <div class="sb-history-icon"><?= storyboard_history_action_icon($h['action']) ?></div>
                        <div class="sb-history-body">
                            <div class="sb-history-head">
                                <span class="badge badge-gray"><?= e(storyboard_history_action_label($h['action'])) ?></span>
                                <strong><?= e($h['summary']) ?></strong>
                            </div>
                            <div class="sb-history-meta">
                                <?php if ($h['user_name']): ?><span><?= e($h['user_name']) ?></span><?php endif; ?>
                                <?php if ($h['frame_title']): ?><span>· <?= e($h['frame_title']) ?></span><?php endif; ?>
                                <span>· <?= time_ago($h['created_at']) ?></span>
                            </div>
                            <?php if (!empty($h['detail'])): ?>
                            <pre class="sb-history-detail"><?= e($h['detail']) ?></pre>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
