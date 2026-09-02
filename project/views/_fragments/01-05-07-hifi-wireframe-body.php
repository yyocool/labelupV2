<?php
/**
 * Label-UP 도움말 와이어프레임 바디 (01-05-07)
 */
?>
<div class="sb-hifi-help">
    <aside class="sb-hifi-help__sidebar">
        <nav class="sb-hifi-help__icon-rail sb-wf-zone" data-zone-id="L-01" aria-label="글로벌 아이콘 네비">
            <span class="sb-wf-zone-label">L-01</span>
            <div class="sb-hifi-help__icon-btn" title="홈">⌂</div>
            <div class="sb-hifi-help__icon-btn is-active" title="라벨 디자인">✎</div>
            <div class="sb-hifi-help__icon-btn" title="템플릿">▦</div>
            <div class="sb-hifi-help__icon-btn" title="규격 검색">⌕</div>
            <div class="sb-hifi-help__icon-btn" title="인쇄">⎙</div>
            <div class="sb-hifi-help__icon-btn" title="맞춤 제작">⚙</div>
            <div class="sb-hifi-help__icon-btn" title="자료실">📁</div>
            <div class="sb-hifi-help__icon-btn" title="고객센터">?</div>
        </nav>
        <div class="sb-hifi-help__nav-panel sb-wf-zone" data-zone-id="L-02">
            <span class="sb-wf-zone-label">L-02</span>
            <div class="sb-hifi-help__logo"><strong>라벨업</strong><small>LABEL UP</small></div>
            <nav class="sb-hifi-help__nav">
                <div class="sb-hifi-help__nav-item"><span>⌂</span> 홈</div>
                <div class="sb-hifi-help__nav-item is-active"><span>✎</span> 라벨 디자인</div>
                <div class="sb-hifi-help__nav-item"><span>▦</span> 템플릿</div>
                <div class="sb-hifi-help__nav-item"><span>⌕</span> 규격 검색</div>
                <div class="sb-hifi-help__nav-item"><span>?</span> 도움말</div>
            </nav>
        </div>
    </aside>

    <div class="sb-hifi-help__main">
        <header class="sb-hifi-help__top sb-wf-zone" data-zone-id="M-01">
            <span class="sb-wf-zone-label">M-01</span>
            <div>
                <h1 class="sb-hifi-help__title">Label-UP 도움말</h1>
                <p class="sb-hifi-help__sub">라벨 편집기 · AI · 출력까지 한곳에서 확인</p>
            </div>
            <div class="sb-hifi-help__search">
                <span>⌕</span>
                <input type="text" placeholder="도움말 검색 (예: 단축키, AI, 바코드)" readonly>
            </div>
            <button type="button" class="sb-hifi-help__close" title="편집기로 돌아가기">✕</button>
        </header>

        <nav class="sb-hifi-help__tabs sb-wf-zone" data-zone-id="M-02" aria-label="도움말 카테고리">
            <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-02</span>
            <button type="button" class="sb-hifi-help__tab is-active">시작하기</button>
            <button type="button" class="sb-hifi-help__tab">편집</button>
            <button type="button" class="sb-hifi-help__tab">AI 디자인</button>
            <button type="button" class="sb-hifi-help__tab">출력·저장</button>
            <button type="button" class="sb-hifi-help__tab">단축키</button>
            <button type="button" class="sb-hifi-help__tab">FAQ</button>
        </nav>

        <div class="sb-hifi-help__body">
            <div class="sb-hifi-help__content">
                <section class="sb-hifi-help__section sb-wf-zone" data-zone-id="M-03">
                    <span class="sb-wf-zone-label">M-03</span>
                    <h2>시작하기</h2>
                    <div class="sb-hifi-help__cards">
                        <article class="sb-hifi-help__card">
                            <strong>1. 새 라벨 만들기</strong>
                            <p>편집기에서 규격을 고르거나 템플릿을 불러와 시작합니다.</p>
                            <span class="sb-hifi-help__link">관련: 01-05 디자인 편집 ›</span>
                        </article>
                        <article class="sb-hifi-help__card">
                            <strong>2. 오브젝트 추가</strong>
                            <p>텍스트·이미지·바코드·표를 레일에서 추가하고 속성 패널에서 조정합니다.</p>
                            <span class="sb-hifi-help__link">관련: 레이어 · 속성 패널 ›</span>
                        </article>
                        <article class="sb-hifi-help__card">
                            <strong>3. AI로 빠르게</strong>
                            <p>프롬프트·이미지·엑셀로 시안을 받고 「편집기로 보내기」로 이어 작업합니다.</p>
                            <span class="sb-hifi-help__link">관련: 01-05-05 AI 디자인 ›</span>
                        </article>
                    </div>
                </section>

                <section class="sb-hifi-help__section sb-wf-zone" data-zone-id="M-04">
                    <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-04</span>
                    <h2>자주 쓰는 단축키</h2>
                    <table class="sb-hifi-help__keys">
                        <thead><tr><th>동작</th><th>Windows</th><th>macOS</th></tr></thead>
                        <tbody>
                            <tr><td>저장</td><td>Ctrl + S</td><td>⌘ S</td></tr>
                            <tr><td>실행 취소</td><td>Ctrl + Z</td><td>⌘ Z</td></tr>
                            <tr><td>복제</td><td>Ctrl + D</td><td>⌘ D</td></tr>
                            <tr><td>전체 선택</td><td>Ctrl + A</td><td>⌘ A</td></tr>
                            <tr><td>미리보기</td><td>Ctrl + P</td><td>⌘ P</td></tr>
                            <tr><td>도움말</td><td>F1 / ?</td><td>F1 / ?</td></tr>
                            <tr><td>팝업 닫기</td><td>Esc</td><td>Esc</td></tr>
                        </tbody>
                    </table>
                </section>
            </div>

            <aside class="sb-hifi-help__aside">
                <div class="sb-hifi-help__aside-card sb-wf-zone" data-zone-id="R-01">
                    <span class="sb-wf-zone-label">R-01</span>
                    <h3>빠른 링크</h3>
                    <ul>
                        <li>← 편집기로 돌아가기</li>
                        <li>AI 디자인 생성 (01-05-05)</li>
                        <li>규격 검색</li>
                        <li>템플릿 둘러보기</li>
                    </ul>
                </div>
                <div class="sb-hifi-help__aside-card sb-hifi-help__aside-card--cta sb-wf-zone" data-zone-id="R-02">
                    <span class="sb-wf-zone-label">R-02</span>
                    <h3>더 도움이 필요하신가요?</h3>
                    <p>운영시간 평일 10:00–18:00</p>
                    <button type="button" class="sb-hifi-help__cta">1:1 문의</button>
                    <button type="button" class="sb-hifi-help__cta sb-hifi-help__cta--ghost">FAQ 보기</button>
                </div>
            </aside>
        </div>
    </div>
</div>
