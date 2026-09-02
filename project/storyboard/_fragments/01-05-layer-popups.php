<?php
/** L-01 하단 네비 — 큰 레이어 팝업 (라벨·태그·템플릿·나의디자인·AI) */

$sbEdLabelTabs = array(
    array('id' => 'a4', 'label' => 'A4 라벨', 'count' => 249, 'active' => true),
    array('id' => 'zebra', 'label' => '제브라라벨', 'count' => 198),
    array('id' => 'name', 'label' => '이름라벨', 'count' => 461),
    array('id' => 'a3', 'label' => 'A3 라벨', 'count' => 1),
);

$sbEdLabelItems = array(
    array('code' => '100', 'size' => '25×20 mm', 'qty' => 84, 'shape' => 'heart'),
    array('code' => '101', 'size' => '15×15 mm', 'qty' => 15, 'shape' => 'circle'),
    array('code' => '102', 'size' => '57.5×48 mm', 'qty' => 12, 'shape' => 'star'),
    array('code' => '103', 'size' => '25×25 mm', 'qty' => 21, 'shape' => 'clover'),
    array('code' => '105', 'size' => '25×25 mm', 'qty' => 24, 'shape' => 'speech'),
    array('code' => '106', 'size' => '25×25 mm', 'qty' => 20, 'shape' => 'bone'),
    array('code' => '111', 'size' => '25×25 mm', 'qty' => 24, 'shape' => 'arrow'),
    array('code' => '112', 'size' => '25×25 mm', 'qty' => 24, 'shape' => 'cloud'),
    array('code' => '113', 'size' => '25×25 mm', 'qty' => 24, 'shape' => 'hex'),
    array('code' => '114', 'size' => '25×25 mm', 'qty' => 24, 'shape' => 'circle'),
    array('code' => '115', 'size' => '25×25 mm', 'qty' => 24, 'shape' => 'star'),
    array('code' => '116', 'size' => '25×25 mm', 'qty' => 24, 'shape' => 'heart'),
);

$sbEdTagTabs = array(
    array('id' => 'a4', 'label' => 'A4 태그', 'count' => 78, 'active' => true),
    array('id' => 'jet', 'label' => '제트 태그', 'count' => 7),
    array('id' => 'muff', 'label' => '머플 태그', 'count' => 6),
);

$sbEdTagItems = array(
    array('code' => 'TLF0021', 'size' => '210 × 143.5 mm', 'qty' => 2, 'type' => 'FOLD'),
    array('code' => 'TLF0061', 'size' => '210 × 143.5 mm', 'qty' => 4, 'type' => 'FOLD'),
    array('code' => 'TLF0101', 'size' => '210 × 143.5 mm', 'qty' => 6, 'type' => 'FOLD'),
    array('code' => 'TLF0141', 'size' => '210 × 143.5 mm', 'qty' => 8, 'type' => 'FOLD'),
    array('code' => 'TLF0181', 'size' => '210 × 143.5 mm', 'qty' => 10, 'type' => 'FOLD'),
    array('code' => 'TLF0221', 'size' => '210 × 143.5 mm', 'qty' => 12, 'type' => 'FOLD'),
    array('code' => 'TLH0021', 'size' => '210 × 143.5 mm', 'qty' => 2, 'type' => 'HANG'),
    array('code' => 'TLH0061', 'size' => '210 × 143.5 mm', 'qty' => 4, 'type' => 'HANG'),
    array('code' => 'TLH0101', 'size' => '210 × 143.5 mm', 'qty' => 6, 'type' => 'HANG'),
    array('code' => 'TLH0141', 'size' => '210 × 143.5 mm', 'qty' => 8, 'type' => 'HANG'),
    array('code' => 'TLH0181', 'size' => '210 × 143.5 mm', 'qty' => 10, 'type' => 'HANG'),
    array('code' => 'TLH0221', 'size' => '210 × 143.5 mm', 'qty' => 12, 'type' => 'HANG'),
);

$sbEdTemplateTags = array(
    array('#전체', 657, true),
    array('#감사', 336),
    array('#선물', 206),
    array('#인사', 170),
    array('#증정품', 137),
    array('#추석', 107),
    array('#구매', 98),
    array('#반가워', 85),
    array('#답례품', 83),
    array('#이벤트', 82),
    array('#여름', 75),
    array('#겨울', 68),
    array('#크리스마스', 61),
    array('#생일', 58),
    array('#카페', 52),
);

$sbEdTemplateItems = array(
    array('title' => '젖은 우산은 우산꽂이에 보관해주세요', 'tags' => '#안내 #카페', 'tone' => '#dbeafe'),
    array('title' => '한라봉청', 'tags' => '#식품 #선물', 'tone' => '#ffedd5'),
    array('title' => 'Green Choice - 친환경 제품', 'tags' => '#친환경 #구매', 'tone' => '#dcfce7'),
    array('title' => '지구를 위한 작은 실천 감사합니다', 'tags' => '#감사 #친환경', 'tone' => '#d1fae5'),
    array('title' => '926-증정용라벨', 'tags' => '#증정품 #여름', 'tone' => '#fce7f3'),
    array('title' => 'INCHEON KOREA', 'tags' => '#여행 #스탬프', 'tone' => '#e0e7ff'),
    array('title' => '961-증정품라벨', 'tags' => '#증정품 #여름', 'tone' => '#fef3c7'),
    array('title' => '634-세일라벨', 'tags' => '#이벤트 #세일', 'tone' => '#fee2e2'),
    array('title' => '823-미끄럼주의안내', 'tags' => '#안내 #주의', 'tone' => '#fef9c3'),
    array('title' => '536-네임스티커', 'tags' => '#카페 #이름', 'tone' => '#f3e8ff'),
    array('title' => '844-안내라벨', 'tags' => '#안내 #카페', 'tone' => '#cffafe'),
    array('title' => '520-감사라벨', 'tags' => '#감사 #선물', 'tone' => '#ffe4e6'),
);
?>
<div class="sb-hifi-editor__layer-overlay sb-wf-zone" data-zone-id="L-02" data-sb-layer-overlay aria-hidden="true" role="dialog" aria-label="편집기 레이어">
    <span class="sb-wf-zone-label sb-wf-zone-label--purple">L-02</span>
    <div class="sb-hifi-editor__layer-dialog">
        <button type="button" class="sb-hifi-editor__layer-close" data-sb-action="close-layer" aria-label="닫기" onclick="return sbEdCloseLayer(this);">×</button>

        <!-- 라벨 -->
        <div class="sb-ed-layer sb-ed-layer--catalog" data-sb-layer-panel="label" hidden>
            <div class="sb-ed-layer__tabs sb-wf-zone" data-zone-id="Q-01">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">Q-01</span>
                <?php foreach ($sbEdLabelTabs as $tab): ?>
                <button type="button" class="sb-ed-layer__tab<?= !empty($tab['active']) ? ' is-active' : '' ?>">
                    <?= e($tab['label']) ?> <em><?= (int) $tab['count'] ?></em>
                </button>
                <?php endforeach; ?>
            </div>
            <div class="sb-ed-layer__toolbar">
                <div class="sb-ed-layer__subtabs">
                    <button type="button" class="sb-ed-layer__subtab is-active">빈(Blank) 라벨</button>
                    <button type="button" class="sb-ed-layer__subtab">디자인 라벨</button>
                </div>
                <div class="sb-ed-layer__tools">
                    <span class="sb-ed-layer__count">248 규격 검색</span>
                    <div class="sb-ed-layer__search">⌕ 규격코드로 찾아보세요</div>
                    <div class="sb-ed-layer__select">규격코드 순 ▾</div>
                </div>
            </div>
            <div class="sb-ed-layer__scroll">
                <div class="sb-ed-layer__grid sb-ed-layer__grid--6">
                    <?php foreach ($sbEdLabelItems as $item): ?>
                    <button type="button" class="sb-ed-spec-card" data-sb-action="pick-spec" onclick="return sbEdCloseLayer(this);">
                        <div class="sb-ed-spec-card__thumb sb-ed-spec-card__thumb--<?= e($item['shape']) ?>">
                            <span class="sb-ed-spec-card__qty"><?= (int) $item['qty'] ?></span>
                            <span class="sb-ed-spec-card__badge">i</span>
                            <span class="sb-ed-spec-card__paper">A4</span>
                        </div>
                        <div class="sb-ed-spec-card__meta">
                            <span><?= e($item['size']) ?></span>
                            <strong><?= e($item['code']) ?></strong>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 태그 -->
        <div class="sb-ed-layer sb-ed-layer--catalog" data-sb-layer-panel="tag" hidden>
            <div class="sb-ed-layer__tabs sb-wf-zone" data-zone-id="Q-02">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">Q-02</span>
                <?php foreach ($sbEdTagTabs as $tab): ?>
                <button type="button" class="sb-ed-layer__tab<?= !empty($tab['active']) ? ' is-active' : '' ?>">
                    <?= e($tab['label']) ?> <em><?= (int) $tab['count'] ?></em>
                </button>
                <?php endforeach; ?>
            </div>
            <div class="sb-ed-layer__toolbar">
                <div class="sb-ed-layer__subtabs">
                    <button type="button" class="sb-ed-layer__subtab is-active">빈(Blank) 태그</button>
                    <button type="button" class="sb-ed-layer__subtab">디자인 태그</button>
                </div>
                <div class="sb-ed-layer__tools">
                    <span class="sb-ed-layer__count">78 규격 검색</span>
                    <div class="sb-ed-layer__search">⌕ 규격코드로 찾아보세요</div>
                    <div class="sb-ed-layer__select">규격코드 순 ▾</div>
                </div>
            </div>
            <div class="sb-ed-layer__scroll">
                <div class="sb-ed-layer__grid sb-ed-layer__grid--6">
                    <?php foreach ($sbEdTagItems as $item): ?>
                    <button type="button" class="sb-ed-spec-card sb-ed-spec-card--tag" data-sb-action="pick-spec" onclick="return sbEdCloseLayer(this);">
                        <div class="sb-ed-spec-card__thumb sb-ed-spec-card__thumb--tag">
                            <span class="sb-ed-spec-card__qty"><?= (int) $item['qty'] ?></span>
                            <span class="sb-ed-spec-card__brand">iLabel iTag</span>
                            <span class="sb-ed-spec-card__tagtype"><?= e($item['type']) ?></span>
                        </div>
                        <div class="sb-ed-spec-card__meta">
                            <span><?= e($item['size']) ?></span>
                            <strong><?= e($item['code']) ?></strong>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 템플릿 -->
        <div class="sb-ed-layer sb-ed-layer--template" data-sb-layer-panel="template" hidden>
            <div class="sb-ed-layer__searchbar sb-wf-zone" data-zone-id="Q-03">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">Q-03</span>
                <div class="sb-ed-layer__search sb-ed-layer__search--wide">키워드로 찾아보세요 ⌕</div>
            </div>
            <div class="sb-ed-layer__tagbar">
                <?php foreach ($sbEdTemplateTags as $tag): ?>
                <button type="button" class="sb-ed-layer__htag<?= !empty($tag[2]) ? ' is-active' : '' ?>">
                    <?= e($tag[0]) ?> <em><?= (int) $tag[1] ?></em>
                </button>
                <?php endforeach; ?>
            </div>
            <div class="sb-ed-layer__scroll">
                <div class="sb-ed-layer__grid sb-ed-layer__grid--6">
                    <?php foreach ($sbEdTemplateItems as $item): ?>
                    <button type="button" class="sb-ed-tpl-card" data-sb-action="pick-template" onclick="return sbEdCloseLayer(this);">
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

        <!-- 나의디자인 -->
        <div class="sb-ed-layer sb-ed-layer--mydesign" data-sb-layer-panel="mydesign" hidden>
            <div class="sb-ed-layer__my-head sb-wf-zone" data-zone-id="Q-04">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">Q-04</span>
                <button type="button" class="sb-ed-layer__import">쿠팡 바코드 가져오기</button>
                <div class="sb-ed-layer__my-tools">
                    <span class="sb-ed-layer__count">0 디자인 검색</span>
                    <div class="sb-ed-layer__search">⌕ 제목으로 찾아보세요</div>
                    <div class="sb-ed-layer__select">수정일 내림차순 ▾</div>
                </div>
            </div>
            <div class="sb-ed-layer__scroll sb-ed-layer__scroll--my">
                <div class="sb-ed-layer__section">
                    <h4>최근 사용한 규격</h4>
                    <div class="sb-ed-layer__recent">
                        <span class="sb-ed-layer__recent-chip">📄 RJ018018</span>
                    </div>
                </div>
                <div class="sb-ed-layer__section">
                    <h4>내가 저장한 디자인</h4>
                    <div class="sb-ed-layer__empty">
                        <span class="sb-ed-layer__empty-icon">ⓘ</span>
                        선택하신 조건에 맞는 저장된 디자인이 없습니다.
                    </div>
                </div>
            </div>
        </div>

        <!-- AI -->
        <div class="sb-ed-layer sb-ed-layer--ai" data-sb-layer-panel="ai" hidden>
            <div class="sb-ed-layer__ai-wrap sb-wf-zone" data-zone-id="Q-05">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">Q-05</span>
                <div class="sb-ed-layer__ai-hero">
                    <p>아이디어를 입력하면 AI가 빠르게 라벨 디자인을 제안해드려요.</p>
                    <div class="sb-ed-layer__ai-bot" aria-hidden="true">🤖</div>
                </div>
                <div class="sb-ed-layer__ai-box">
                    <div class="sb-ed-layer__ai-title">✦ 어떤 라벨을 디자인할까요?</div>
                    <p class="sb-ed-layer__ai-sub">아이디어나 요구사항을 자유롭게 입력해주세요.</p>
                    <div class="sb-ed-layer__ai-input">
                        <textarea rows="4" placeholder="예) 유기농 꿀 라벨, 70x50mm, 자연 느낌, 밝은 색상, 꿀벌 일러스트" readonly></textarea>
                        <div class="sb-ed-layer__ai-input-foot">
                            <span>0 / 2000</span>
                            <button type="button" class="sb-ed-layer__ai-send" aria-label="전송">➤</button>
                        </div>
                    </div>
                    <div class="sb-ed-layer__ai-or">또는</div>
                    <div class="sb-ed-layer__ai-cards">
                        <button type="button" class="sb-ed-layer__ai-card">
                            <span class="sb-ed-layer__ai-card-icon">🖼</span>
                            <strong>이미지 붙여넣기</strong>
                            <small>이미지를 붙여넣어 디자인을 생성해보세요.</small>
                        </button>
                        <button type="button" class="sb-ed-layer__ai-card">
                            <span class="sb-ed-layer__ai-card-icon sb-ed-layer__ai-card-icon--green">📊</span>
                            <strong>엑셀 파일 업로드</strong>
                            <small>엑셀(CSV) 파일을 드래그하거나 업로드하세요.</small>
                        </button>
                        <button type="button" class="sb-ed-layer__ai-card">
                            <span class="sb-ed-layer__ai-card-icon sb-ed-layer__ai-card-icon--purple">T</span>
                            <strong>프롬프트 예시 사용</strong>
                            <small>다양한 예시를 활용하여 시작해보세요.</small>
                        </button>
                        <button type="button" class="sb-ed-layer__ai-card">
                            <span class="sb-ed-layer__ai-card-icon sb-ed-layer__ai-card-icon--purple">📁</span>
                            <strong>내 파일에서 시작</strong>
                            <small>이전에 업로드한 파일을 불러와 시작하세요.</small>
                        </button>
                    </div>
                    <div class="sb-ed-layer__ai-tip">
                        💡 팁! 구체적으로 입력할수록 더 정확한 결과를 얻을 수 있어요.
                    </div>
                    <div class="sb-ed-layer__ai-examples">
                        <span>유기농 올리브오일 라벨</span>
                        <span>빈티지 스타일</span>
                        <span>70x50mm</span>
                        <span>올리브 일러스트</span>
                        <span>녹색 톤</span>
                        <a href="#" class="sb-ed-layer__ai-more" onclick="return false">더 많은 예시 보기 &gt;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
