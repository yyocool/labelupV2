<?php
/** 좌측 에셋 슬라이드 패널 — 배경·템플릿·클립아트·아이콘 */
include __DIR__ . '/01-05-asset-slide-data.php';
?>
<aside class="sb-ed-asset-slide sb-wf-zone" data-zone-id="S-00" data-sb-asset-slide aria-hidden="true" aria-label="에셋 라이브러리">
    <span class="sb-wf-zone-label sb-wf-zone-label--purple">S-00</span>

    <!-- 배경 -->
    <div class="sb-ed-asset-slide__view" data-sb-asset-view="background" hidden>
        <header class="sb-ed-asset-slide__head sb-ed-asset-slide__head--bg">
            <div class="sb-ed-asset-slide__search">
                <span class="sb-ed-asset-slide__search-icon" aria-hidden="true">⌕</span>
                <input type="search" class="sb-ed-asset-slide__search-input" placeholder="키워드로 찾아보세요." autocomplete="off">
                <button type="button" class="sb-ed-asset-slide__search-clear" data-sb-asset-search-clear aria-label="검색어 지우기" hidden>×</button>
            </div>
            <div class="sb-ed-asset-slide__head-actions">
                <button type="button" class="sb-ed-asset-slide__action-btn" data-sb-proto="bg-delete">
                    <span aria-hidden="true">🗑</span> 배경 삭제
                </button>
                <button type="button" class="sb-ed-asset-slide__close" data-sb-asset-slide-close aria-label="닫기">×</button>
            </div>
        </header>
        <div class="sb-ed-asset-slide__tags" data-sb-asset-tags>
            <?php foreach ($sbAssetBgTags as $tag): ?>
            <button type="button" class="sb-ed-asset-slide__tag<?= !empty($tag['active']) ? ' is-active' : '' ?>"><?= e($tag['label']) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="sb-ed-asset-slide__sort-row">
            <button type="button" class="sb-ed-asset-slide__sort">추천순 ▾</button>
        </div>
        <div class="sb-ed-asset-slide__body">
            <div class="sb-ed-asset-slide__grid sb-ed-asset-slide__grid--3">
                <?php foreach ($sbAssetBgItems as $item): ?>
                <button type="button" class="sb-ed-asset-slide__card sb-ed-asset-slide__card--bg" data-sb-asset-pick="background">
                    <span class="sb-ed-asset-slide__thumb" style="background:<?= e($item['tone']) ?>"></span>
                    <span class="sb-ed-asset-slide__card-title"><?= e($item['title']) ?></span>
                    <span class="sb-ed-asset-slide__card-tags"><?= e($item['tags']) ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 템플릿 -->
    <div class="sb-ed-asset-slide__view" data-sb-asset-view="template" hidden>
        <header class="sb-ed-asset-slide__head">
            <div class="sb-ed-asset-slide__search sb-ed-asset-slide__search--full">
                <span class="sb-ed-asset-slide__search-icon" aria-hidden="true">⌕</span>
                <input type="search" class="sb-ed-asset-slide__search-input" placeholder="키워드로 찾아보세요." autocomplete="off">
                <button type="button" class="sb-ed-asset-slide__search-clear" data-sb-asset-search-clear aria-label="검색어 지우기" hidden>×</button>
            </div>
            <button type="button" class="sb-ed-asset-slide__close" data-sb-asset-slide-close aria-label="닫기">×</button>
        </header>
        <div class="sb-ed-asset-slide__tags" data-sb-asset-tags>
            <?php foreach ($sbAssetTplTags as $tag): ?>
            <button type="button" class="sb-ed-asset-slide__tag<?= !empty($tag['active']) ? ' is-active' : '' ?>"><?= e($tag['label']) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="sb-ed-asset-slide__sort-row">
            <button type="button" class="sb-ed-asset-slide__sort">추천순 ▾</button>
        </div>
        <div class="sb-ed-asset-slide__body">
            <div class="sb-ed-asset-slide__grid sb-ed-asset-slide__grid--3">
                <?php foreach ($sbAssetTplItems as $item): ?>
                <button type="button" class="sb-ed-asset-slide__card sb-ed-asset-slide__card--tpl" data-sb-asset-pick="template">
                    <span class="sb-ed-asset-slide__thumb sb-ed-asset-slide__thumb--tpl" style="background:<?= e($item['tone']) ?>"></span>
                    <span class="sb-ed-asset-slide__card-title"><?= e($item['title']) ?></span>
                    <span class="sb-ed-asset-slide__card-tags"><?= e($item['tags']) ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 클립아트 -->
    <div class="sb-ed-asset-slide__view" data-sb-asset-view="clipart" hidden>
        <header class="sb-ed-asset-slide__head">
            <div class="sb-ed-asset-slide__search sb-ed-asset-slide__search--full">
                <span class="sb-ed-asset-slide__search-icon" aria-hidden="true">⌕</span>
                <input type="search" class="sb-ed-asset-slide__search-input" placeholder="키워드로 찾아보세요." autocomplete="off">
                <button type="button" class="sb-ed-asset-slide__search-clear" data-sb-asset-search-clear aria-label="검색어 지우기" hidden>×</button>
            </div>
            <button type="button" class="sb-ed-asset-slide__close" data-sb-asset-slide-close aria-label="닫기">×</button>
        </header>
        <div class="sb-ed-asset-slide__tags sb-ed-asset-slide__tags--count" data-sb-asset-tags>
            <?php foreach ($sbAssetClipCats as $cat): ?>
            <button type="button" class="sb-ed-asset-slide__tag<?= !empty($cat['active']) ? ' is-active' : '' ?>">
                <?= e($cat['label']) ?> <em><?= (int) $cat['count'] ?></em>
            </button>
            <?php endforeach; ?>
        </div>
        <div class="sb-ed-asset-slide__body">
            <div class="sb-ed-asset-slide__grid sb-ed-asset-slide__grid--3">
                <?php foreach ($sbAssetClipItems as $item): ?>
                <button type="button" class="sb-ed-asset-slide__card sb-ed-asset-slide__card--clip" data-sb-asset-pick="clipart">
                    <span class="sb-ed-asset-slide__clip-art" aria-hidden="true"><?= e($item['emoji']) ?></span>
                    <span class="sb-ed-asset-slide__card-tags"><?= e($item['tags']) ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 아이콘 -->
    <div class="sb-ed-asset-slide__view" data-sb-asset-view="icon" hidden>
        <header class="sb-ed-asset-slide__head">
            <div class="sb-ed-asset-slide__search sb-ed-asset-slide__search--full">
                <span class="sb-ed-asset-slide__search-icon" aria-hidden="true">⌕</span>
                <input type="search" class="sb-ed-asset-slide__search-input" placeholder="아이콘 이름으로 찾아보세요." autocomplete="off">
                <button type="button" class="sb-ed-asset-slide__search-clear" data-sb-asset-search-clear aria-label="검색어 지우기" hidden>×</button>
            </div>
            <button type="button" class="sb-ed-asset-slide__close" data-sb-asset-slide-close aria-label="닫기">×</button>
        </header>
        <div class="sb-ed-asset-slide__body sb-ed-asset-slide__body--icon">
            <div class="sb-ed-asset-slide__grid sb-ed-asset-slide__grid--6">
                <?php foreach ($sbAssetIconItems as $item): ?>
                <button type="button" class="sb-ed-asset-slide__card sb-ed-asset-slide__card--icon" data-sb-asset-pick="icon" title="<?= e($item['label']) ?>">
                    <span class="sb-ed-asset-slide__icon-glyph" aria-hidden="true"><?= e($item['glyph']) ?></span>
                    <span class="sb-ed-asset-slide__icon-label"><?= e($item['label']) ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <button type="button" class="sb-ed-asset-slide__rail-btn" data-sb-asset-slide-collapse aria-label="패널 접기" title="패널 접기">›</button>
</aside>
