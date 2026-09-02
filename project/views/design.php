<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>디자인</h1>
            <p>첨부 시안을 퍼블리싱한 미리보기입니다. 시안보기로 새 창에서 확인하세요.</p>
        </div>
    </div>
</div>

<div class="design-draft-grid">
    <?php foreach ($designDrafts as $draft):
        $thumb = $draft['id'] === 'b'
            ? asset('img/design/pub/b-hero-visual.png')
            : asset('img/design/pub/a-hero-visual.png');
    ?>
    <article class="card design-draft-card">
        <div class="design-draft-card__preview">
            <img src="<?= e($thumb) ?>" alt="" class="design-draft-card__img">
            <span class="design-draft-card__badge"><?= e($draft['badge']) ?></span>
        </div>
        <div class="design-draft-card__body">
            <h3><?= e($draft['title']) ?></h3>
            <p><?= e($draft['subtitle']) ?></p>
            <div class="btn-group" style="margin-top:14px">
                <a class="btn btn-primary"
                   href="<?= url('design-preview.php?id=' . rawurlencode($draft['id'])) ?>"
                   target="_blank"
                   rel="noopener">
                    시안보기
                </a>
            </div>
        </div>
    </article>
    <?php endforeach; ?>
</div>

<style>
.design-draft-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}
.design-draft-card { overflow: hidden; padding: 0; }
.design-draft-card__preview {
    position: relative;
    height: 160px;
    border-bottom: 1px solid var(--border-light);
    background: #f8fafc;
    overflow: hidden;
}
.design-draft-card__img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: top left;
}
.design-draft-card__badge {
    position: absolute;
    left: 12px;
    bottom: 12px;
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    background: rgba(255,255,255,.92);
    color: #0f172a;
    box-shadow: 0 4px 12px rgba(15,23,42,.12);
}
.design-draft-card__body { padding: 18px 20px 20px; }
.design-draft-card__body h3 { margin: 0 0 6px; font-size: 16px; }
.design-draft-card__body p { margin: 0; color: var(--text-muted); font-size: 13px; line-height: 1.45; }
</style>
