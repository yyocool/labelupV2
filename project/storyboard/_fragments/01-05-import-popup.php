<?php
/** 가져오기 레이어 팝업 — 라벨 · 태그 · 템플릿 · 스마트라벨 · 외부포맷 · 내 디자인 */

require __DIR__ . '/01-05-import-catalog-data.php';

$sbImportBlankLabel = $sbImportBlankSpecs['label'];
$sbImportBlankTag = $sbImportBlankSpecs['tag'];
$sbImportDesignLabel = $sbImportDesignItems['label'];
$sbImportDesignTag = $sbImportDesignItems['tag'];
?>
<div class="sb-hifi-editor__import-overlay sb-wf-zone" data-zone-id="I-01" data-sb-import-overlay aria-hidden="true" role="dialog" aria-label="가져오기">
    <span class="sb-wf-zone-label sb-wf-zone-label--purple">I-01</span>
    <div class="sb-hifi-editor__import-dialog">
        <header class="sb-ed-import__head">
            <h3>가져오기</h3>
            <p>라벨을 선택하고 디자인을 선택하면 편집화면으로 이동합니다.</p>
            <button type="button" class="sb-hifi-editor__layer-close" data-sb-action="close-import" aria-label="닫기" onclick="return sbEdCloseImport(this);">×</button>
        </header>

        <nav class="sb-ed-import__tabs" role="tablist" aria-label="가져오기">
            <button type="button" class="sb-ed-import__tab is-active" data-sb-import-tab="label" role="tab" aria-selected="true">
                <span class="sb-ed-import__tab-icon" aria-hidden="true">🏷</span>
                <span>라벨</span>
            </button>
            <button type="button" class="sb-ed-import__tab" data-sb-import-tab="tag" role="tab">
                <span class="sb-ed-import__tab-icon" aria-hidden="true">🔖</span>
                <span>태그</span>
            </button>
            <button type="button" class="sb-ed-import__tab" data-sb-import-tab="template" role="tab">
                <span class="sb-ed-import__tab-icon" aria-hidden="true">📋</span>
                <span>템플릿</span>
            </button>
            <button type="button" class="sb-ed-import__tab" data-sb-import-tab="smart" role="tab">스마트라벨</button>
            <button type="button" class="sb-ed-import__tab" data-sb-import-tab="external" role="tab">외부라벨포맷</button>
            <button type="button" class="sb-ed-import__tab" data-sb-import-tab="mydesign" role="tab">내 디자인</button>
        </nav>

        <!-- 라벨 -->
        <div class="sb-ed-import__panel sb-ed-layer sb-ed-layer--catalog" data-sb-import-panel="label" data-sb-import-catalog="label">
            <div class="sb-ed-layer__tabs">
                <?php foreach ($sbImportLabelCategories as $cat): ?>
                <button type="button"
                    class="sb-ed-layer__tab<?= !empty($cat['active']) ? ' is-active' : '' ?>"
                    data-sb-import-cat="<?= e($cat['id']) ?>"
                    data-sb-import-count="<?= (int) $cat['count'] ?>">
                    <?= e($cat['label']) ?> <em><?= (int) $cat['count'] ?></em>
                </button>
                <?php endforeach; ?>
            </div>
            <div class="sb-ed-layer__toolbar">
                <div class="sb-ed-layer__subtabs" data-sb-import-subtype-group="label">
                    <button type="button" class="sb-ed-layer__subtab is-active" data-sb-import-subtype="blank">빈(Blank) 라벨</button>
                    <button type="button" class="sb-ed-layer__subtab" data-sb-import-subtype="design">디자인 라벨</button>
                </div>
                <div class="sb-ed-layer__tools">
                    <div class="sb-ed-layer__select">20개씩 보기 ▾</div>
                    <div class="sb-ed-layer__search">⌕ 규격코드로 찾아보세요</div>
                    <button type="button" class="sb-ed-import__reset" data-sb-import-reset aria-label="검색 초기화">↺</button>
                    <div class="sb-ed-layer__select">규격별 보기 ▾</div>
                </div>
            </div>
            <div class="sb-ed-import__catalog-scroll sb-ed-layer__scroll">
                <?php foreach ($sbImportBlankLabel as $catId => $items): ?>
                <div class="sb-ed-layer__grid sb-ed-layer__grid--6 sb-ed-import__catalog-grid"
                    data-sb-import-grid="label-<?= e($catId) ?>-blank"
                    <?= $catId !== 'a4' ? 'hidden' : '' ?>>
                    <?php foreach ($items as $item): ?>
                    <button type="button" class="sb-ed-spec-card sb-ed-import__spec-card" data-sb-import-pick="spec" data-sb-import-kind="label" data-spec-code="<?= e($item['code']) ?>">
                        <div class="sb-ed-spec-card__thumb sb-ed-spec-card__thumb--sheet sb-ed-spec-card__thumb--<?= e($item['shape']) ?>">
                            <span class="sb-ed-spec-card__qty"><?= (int) $item['qty'] ?></span>
                            <span class="sb-ed-spec-card__brand">Label-UP</span>
                        </div>
                        <div class="sb-ed-spec-card__meta">
                            <span><?= e($item['size']) ?></span>
                            <strong><?= e($item['code']) ?></strong>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                <?php foreach ($sbImportDesignLabel as $catId => $items): ?>
                <div class="sb-ed-layer__grid sb-ed-layer__grid--6 sb-ed-import__catalog-grid sb-ed-import__catalog-grid--design"
                    data-sb-import-grid="label-<?= e($catId) ?>-design" hidden>
                    <?php foreach ($items as $item): ?>
                    <button type="button" class="sb-ed-tpl-card" data-sb-import-pick="design" data-sb-import-kind="label">
                        <div class="sb-ed-tpl-card__thumb" style="background:<?= e($item['tone']) ?>">
                            <span class="sb-ed-tpl-card__preview"><?= e(mb_substr($item['title'], 0, 8, 'UTF-8')) ?></span>
                        </div>
                        <div class="sb-ed-tpl-card__title"><?= e($item['title']) ?></div>
                        <div class="sb-ed-tpl-card__tags"><?= e($item['tags']) ?></div>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 태그 -->
        <div class="sb-ed-import__panel sb-ed-layer sb-ed-layer--catalog" data-sb-import-panel="tag" data-sb-import-catalog="tag" hidden>
            <div class="sb-ed-layer__tabs">
                <?php foreach ($sbImportTagCategories as $cat): ?>
                <button type="button"
                    class="sb-ed-layer__tab<?= !empty($cat['active']) ? ' is-active' : '' ?>"
                    data-sb-import-cat="<?= e($cat['id']) ?>"
                    data-sb-import-count="<?= (int) $cat['count'] ?>">
                    <?= e($cat['label']) ?> <em><?= (int) $cat['count'] ?></em>
                </button>
                <?php endforeach; ?>
            </div>
            <div class="sb-ed-layer__toolbar">
                <div class="sb-ed-layer__subtabs" data-sb-import-subtype-group="tag">
                    <button type="button" class="sb-ed-layer__subtab is-active" data-sb-import-subtype="blank">빈(Blank) 태그</button>
                    <button type="button" class="sb-ed-layer__subtab" data-sb-import-subtype="design">디자인태그</button>
                </div>
                <div class="sb-ed-layer__tools">
                    <div class="sb-ed-layer__select">20개씩 보기 ▾</div>
                    <div class="sb-ed-layer__search">⌕ 규격코드로 찾아보세요</div>
                    <button type="button" class="sb-ed-import__reset" data-sb-import-reset aria-label="검색 초기화">↺</button>
                    <div class="sb-ed-layer__select">규격별 보기 ▾</div>
                </div>
            </div>
            <div class="sb-ed-import__catalog-scroll sb-ed-layer__scroll">
                <?php foreach ($sbImportBlankTag as $catId => $items): ?>
                <div class="sb-ed-layer__grid sb-ed-layer__grid--6 sb-ed-import__catalog-grid"
                    data-sb-import-grid="tag-<?= e($catId) ?>-blank"
                    <?= $catId !== 'a4' ? 'hidden' : '' ?>>
                    <?php foreach ($items as $item): ?>
                    <button type="button" class="sb-ed-spec-card sb-ed-spec-card--tag sb-ed-import__spec-card" data-sb-import-pick="spec" data-sb-import-kind="tag" data-spec-code="<?= e($item['code']) ?>">
                        <div class="sb-ed-spec-card__thumb sb-ed-spec-card__thumb--tag sb-ed-spec-card__thumb--sheet">
                            <span class="sb-ed-spec-card__qty"><?= (int) $item['qty'] ?></span>
                            <span class="sb-ed-spec-card__brand">Label-UP</span>
                        </div>
                        <div class="sb-ed-spec-card__meta">
                            <span><?= e($item['size']) ?></span>
                            <strong><?= e($item['code']) ?></strong>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                <?php foreach ($sbImportDesignTag as $catId => $items): ?>
                <div class="sb-ed-layer__grid sb-ed-layer__grid--6 sb-ed-import__catalog-grid sb-ed-import__catalog-grid--design"
                    data-sb-import-grid="tag-<?= e($catId) ?>-design" hidden>
                    <?php foreach ($items as $item): ?>
                    <button type="button" class="sb-ed-tpl-card" data-sb-import-pick="design" data-sb-import-kind="tag">
                        <div class="sb-ed-tpl-card__thumb" style="background:<?= e($item['tone']) ?>">
                            <span class="sb-ed-tpl-card__preview"><?= e(mb_substr($item['title'], 0, 8, 'UTF-8')) ?></span>
                        </div>
                        <div class="sb-ed-tpl-card__title"><?= e($item['title']) ?></div>
                        <div class="sb-ed-tpl-card__tags"><?= e($item['tags']) ?></div>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 템플릿 -->
        <div class="sb-ed-import__panel sb-ed-layer sb-ed-layer--template" data-sb-import-panel="template" hidden>
            <div class="sb-ed-layer__searchbar">
                <div class="sb-ed-layer__search sb-ed-layer__search--wide">키워드로 찾아보세요 ⌕</div>
            </div>
            <div class="sb-ed-layer__tagbar">
                <?php foreach ($sbImportTemplateTags as $tag): ?>
                <button type="button" class="sb-ed-layer__htag<?= !empty($tag[2]) ? ' is-active' : '' ?>" data-sb-import-htag>
                    <?= e($tag[0]) ?> <em><?= (int) $tag[1] ?></em>
                </button>
                <?php endforeach; ?>
            </div>
            <div class="sb-ed-import__catalog-scroll sb-ed-layer__scroll">
                <div class="sb-ed-layer__grid sb-ed-layer__grid--6">
                    <?php foreach ($sbImportTemplateItems as $item): ?>
                    <button type="button" class="sb-ed-tpl-card" data-sb-import-pick="template">
                        <div class="sb-ed-tpl-card__thumb" style="background:<?= e($item['tone']) ?>">
                            <span class="sb-ed-tpl-card__preview"><?= e(mb_substr($item['title'], 0, 8, 'UTF-8')) ?></span>
                        </div>
                        <div class="sb-ed-tpl-card__title"><?= e($item['title']) ?></div>
                        <div class="sb-ed-tpl-card__tags"><?= e($item['tags']) ?></div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 내 디자인 -->
        <div class="sb-ed-import__panel" data-sb-import-panel="mydesign" hidden>
            <div class="sb-ed-import__toolbar">
                <div class="sb-ed-layer__search sb-ed-import__search">⌕ 제목·규격으로 검색</div>
                <div class="sb-ed-layer__select">수정일 내림차순 ▾</div>
            </div>
            <div class="sb-ed-import__scroll">
                <p class="sb-ed-import__section-label">내가 작업한 라벨 프로젝트</p>
                <div class="sb-ed-import__design-grid">
                    <?php foreach ($sbImportMyDesigns as $item): ?>
                    <button type="button" class="sb-ed-import__design-card" data-sb-import-pick="design">
                        <div class="sb-ed-import__design-thumb" style="background:<?= e($item['tone']) ?>">
                            <?php if (!empty($item['badge'])): ?>
                            <span class="sb-ed-import__design-badge"><?= e($item['badge']) ?></span>
                            <?php endif; ?>
                            <span class="sb-ed-import__design-preview"><?= e(mb_substr($item['title'], 0, 6, 'UTF-8')) ?></span>
                        </div>
                        <div class="sb-ed-import__design-meta">
                            <strong><?= e($item['title']) ?></strong>
                            <span><?= e($item['spec']) ?></span>
                            <em><?= e($item['date']) ?></em>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 스마트라벨 -->
        <div class="sb-ed-import__panel sb-ed-import__panel--smart" data-sb-import-panel="smart" hidden>
            <div class="sb-ed-import__scroll sb-ed-import__scroll--smart">
                <div class="sb-ed-import__ai-wrap">
                    <div class="sb-ed-import__ai-box">
                        <div class="sb-ed-import__ai-title">✦ 어떤 라벨을 디자인할까요?</div>
                        <p class="sb-ed-import__ai-sub">아이디어나 요구사항을 자유롭게 입력해주세요.</p>
                        <div class="sb-ed-import__ai-input">
                            <textarea rows="4" data-sb-import-prompt maxlength="2000" placeholder="예) 유기농 꿀 라벨, 70x50mm, 자연 느낌, 밝은 색상, 꿀벌 일러스트"></textarea>
                            <div class="sb-ed-import__ai-input-foot">
                                <span data-sb-import-prompt-count>0 / 2000</span>
                                <button type="button" class="sb-ed-import__ai-send" data-sb-import-smart="send" aria-label="전송">➤</button>
                            </div>
                        </div>
                        <div class="sb-ed-import__ai-or">또는</div>
                        <div class="sb-ed-import__ai-cards">
                            <button type="button" class="sb-ed-import__ai-card" data-sb-import-smart="image">
                                <span class="sb-ed-import__ai-card-icon">🖼</span>
                                <strong>이미지 붙여넣기</strong>
                                <small>이미지를 붙여넣어 디자인을 생성해보세요.</small>
                            </button>
                            <button type="button" class="sb-ed-import__ai-card" data-sb-import-smart="excel">
                                <span class="sb-ed-import__ai-card-icon sb-ed-import__ai-card-icon--green">📊</span>
                                <strong>엑셀 파일 업로드</strong>
                                <small>엑셀(CSV) 파일을 드래그하거나 업로드하세요.</small>
                            </button>
                            <button type="button" class="sb-ed-import__ai-card" data-sb-import-smart="examples">
                                <span class="sb-ed-import__ai-card-icon sb-ed-import__ai-card-icon--purple">T</span>
                                <strong>프롬프트 예시 사용</strong>
                                <small>다양한 예시를 활용하여 시작해보세요.</small>
                            </button>
                            <button type="button" class="sb-ed-import__ai-card" data-sb-import-smart="myfile">
                                <span class="sb-ed-import__ai-card-icon sb-ed-import__ai-card-icon--purple">📁</span>
                                <strong>내 파일에서 시작</strong>
                                <small>이전에 업로드한 파일을 불러와 시작하세요.</small>
                            </button>
                        </div>
                        <div class="sb-ed-import__ai-tip">💡 팁! 구체적으로 입력할수록 더 정확한 결과를 얻을 수 있어요.</div>
                        <div class="sb-ed-import__ai-examples">
                            <button type="button" class="sb-ed-import__ai-chip" data-sb-import-example="유기농 올리브오일 라벨">유기농 올리브오일 라벨</button>
                            <button type="button" class="sb-ed-import__ai-chip" data-sb-import-example="빈티지 스타일">빈티지 스타일</button>
                            <button type="button" class="sb-ed-import__ai-chip" data-sb-import-example="70x50mm">70x50mm</button>
                            <button type="button" class="sb-ed-import__ai-chip" data-sb-import-example="올리브 일러스트">올리브 일러스트</button>
                            <button type="button" class="sb-ed-import__ai-chip" data-sb-import-example="녹색 톤">녹색 톤</button>
                            <button type="button" class="sb-ed-import__ai-more" data-sb-import-smart="more-examples">더 많은 예시 보기 &gt;</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 외부라벨포맷 -->
        <div class="sb-ed-import__panel" data-sb-import-panel="external" hidden>
            <div class="sb-ed-import__scroll">
                <p class="sb-ed-import__section-label">타사 라벨 포맷 파일 가져오기</p>
                <div class="sb-ed-import__format-list">
                    <div class="sb-ed-import__format-block">
                        <div class="sb-ed-import__format-head">
                            <span class="sb-ed-import__format-logo sb-ed-import__format-logo--ilabel">iL</span>
                            <div>
                                <strong>iLabel2</strong>
                                <span>.lbl · .xml 포맷 지원</span>
                            </div>
                        </div>
                        <label class="sb-ed-import__dropzone" data-sb-import-drop="ilabel2">
                            <input type="file" class="sb-ed-import__file-input" accept=".lbl,.xml,.zip" tabindex="-1">
                            <span class="sb-ed-import__drop-icon">📁</span>
                            <span class="sb-ed-import__drop-title">파일을 드래그하거나 클릭하여 선택</span>
                            <span class="sb-ed-import__drop-hint">iLabel2 프로젝트 파일</span>
                            <span class="sb-ed-import__drop-name" data-sb-import-filename hidden></span>
                        </label>
                    </div>
                    <div class="sb-ed-import__format-block">
                        <div class="sb-ed-import__format-head">
                            <span class="sb-ed-import__format-logo sb-ed-import__format-logo--formtec">F9</span>
                            <div>
                                <strong>폼텍디자인프로9</strong>
                                <span class="sb-ed-import__format-tag">고도화</span>
                                <span>.fmt · .fdx 포맷 지원</span>
                            </div>
                        </div>
                        <label class="sb-ed-import__dropzone" data-sb-import-drop="formtec">
                            <input type="file" class="sb-ed-import__file-input" accept=".fmt,.fdx,.zip" tabindex="-1">
                            <span class="sb-ed-import__drop-icon">📁</span>
                            <span class="sb-ed-import__drop-title">파일을 드래그하거나 클릭하여 선택</span>
                            <span class="sb-ed-import__drop-hint">폼텍 디자인프로9(고도화) 파일</span>
                            <span class="sb-ed-import__drop-name" data-sb-import-filename hidden></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
