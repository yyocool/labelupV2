<?php
/**
 * 기능 명세표 뷰
 * @var array $spec
 * @var string $pageTitle
 */
$meta = isset($spec['meta']) ? $spec['meta'] : array();
?>
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>기능 명세표</h1>
            <p>라벨 디자인 편집기(01-05) · 가져오기/데이터 · 미리보기 · Backoffice 전체화면까지 기능·Zone·UX를 표로 정리</p>
        </div>
        <div class="btn-group">
            <a href="<?= url('storyboard.php') ?>" class="btn btn-outline btn-sm">스토리보드</a>
            <a href="<?= url('competitive-analysis.php') ?>" class="btn btn-outline btn-sm">경쟁서비스 분석</a>
            <a href="<?= url('pricing-analysis.php') ?>" class="btn btn-secondary btn-sm">요금정책분석</a>
        </div>
    </div>
</div>

<?php if (!empty($meta)): ?>
<div class="pricing-meta-bar">
    <span>버전 <?= e(isset($meta['version']) ? $meta['version'] : '-') ?></span>
    <span>기준일 <?= e(isset($meta['updated']) ? $meta['updated'] : '-') ?></span>
    <span><?= e(isset($meta['basis']) ? $meta['basis'] : '') ?></span>
    <?php if (!empty($meta['status'])): ?>
    <span class="badge badge-yellow"><?= e($meta['status']) ?></span>
    <?php endif; ?>
</div>
<?php endif; ?>

<nav class="fs-toc card" aria-label="목차">
    <div class="card-header"><h3>목차</h3></div>
    <ol class="fs-toc-list">
        <li><a href="#fs-summary">문서 개요</a></li>
        <li><a href="#fs-screens">대상 화면</a></li>
        <li><a href="#fs-zones">영역(Zone) 총괄</a></li>
        <li><a href="#fs-modules">기능 명세 모듈</a></li>
        <li><a href="#fs-ux">공통 UX 규칙</a></li>
        <li><a href="#fs-backlog">개발 이관 백로그</a></li>
        <li><a href="#fs-files">관련 소스 맵</a></li>
        <li><a href="#fs-glossary">용어</a></li>
    </ol>
</nav>

<?php if (!empty($spec['summary'])): ?>
<?php $sum = $spec['summary']; ?>
<section id="fs-summary" class="card fs-section">
    <div class="card-header"><h3><?= e($sum['title']) ?></h3></div>
    <div class="fs-section-body">
        <p class="fs-lead"><?= e($sum['text']) ?></p>
        <?php if (!empty($sum['goals'])): ?>
        <ul class="fs-goals">
            <?php foreach ($sum['goals'] as $g): ?>
            <li><?= e($g) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($spec['screens'])): ?>
<section id="fs-screens" class="card fs-section">
    <div class="card-header"><h3>대상 화면</h3></div>
    <div class="table-wrap">
        <table class="fs-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>화면명</th>
                    <th>메뉴코드</th>
                    <th>URL</th>
                    <th>권한</th>
                    <th>레이아웃</th>
                    <th>목적</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($spec['screens'] as $sc): ?>
                <tr>
                    <td><code><?= e($sc['id']) ?></code></td>
                    <td><strong><?= e($sc['name']) ?></strong></td>
                    <td><code><?= e($sc['code']) ?></code></td>
                    <td><code><?= e($sc['url']) ?></code></td>
                    <td><?= e($sc['auth']) ?></td>
                    <td><?= e($sc['layout']) ?></td>
                    <td><?= e($sc['purpose']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($spec['zones'])): ?>
<section id="fs-zones" class="card fs-section">
    <div class="card-header">
        <h3>영역(Zone) 총괄</h3>
        <span class="fs-hint">와이어프레임 영역 ID · 프로토타입 상태</span>
    </div>
    <div class="table-wrap">
        <table class="fs-table">
            <thead>
                <tr>
                    <th>Zone</th>
                    <th>구분</th>
                    <th>블록</th>
                    <th>포함 요소</th>
                    <th>우선순위</th>
                    <th>프로토타입</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($spec['zones'] as $z): ?>
                <tr>
                    <td><code><?= e($z['id']) ?></code></td>
                    <td><span class="badge badge-blue"><?= e($z['type']) ?></span></td>
                    <td><?= e($z['block']) ?></td>
                    <td><?= e($z['elements']) ?></td>
                    <td><span class="fs-prio fs-prio--<?= e(strtolower($z['priority'])) ?>"><?= e($z['priority']) ?></span></td>
                    <td><span class="badge badge-green"><?= e($z['proto']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<section id="fs-modules">
    <h2 class="fs-modules-title">기능 명세 모듈</h2>

    <?php if (!empty($spec['modules'])): ?>
    <?php foreach ($spec['modules'] as $mod): ?>
    <section id="<?= e($mod['id']) ?>" class="card fs-section fs-module">
        <div class="card-header">
            <h3><?= e($mod['title']) ?></h3>
        </div>
        <div class="fs-section-body">
            <p class="fs-module-desc"><?= e($mod['desc']) ?></p>

            <?php if (!empty($mod['features'])): ?>
            <div class="table-wrap">
                <table class="fs-table fs-table--features">
                    <thead>
                        <tr>
                            <th>기능 ID</th>
                            <th>기능명</th>
                            <th>설명</th>
                            <th>입력</th>
                            <th>출력</th>
                            <th>UX</th>
                            <th>상태</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mod['features'] as $f): ?>
                        <tr>
                            <td><code><?= e($f['fid']) ?></code></td>
                            <td><strong><?= e($f['name']) ?></strong></td>
                            <td><?= e($f['desc']) ?></td>
                            <td><?= e($f['input']) ?></td>
                            <td><?= e($f['output']) ?></td>
                            <td><?= e($f['ux']) ?></td>
                            <td><span class="badge badge-light"><?= e($f['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if (!empty($mod['tools'])): ?>
            <h4 class="fs-subhead">툴바 도구별 동작</h4>
            <div class="table-wrap">
                <table class="fs-table">
                    <thead>
                        <tr><th>도구</th><th>동작</th><th>유형</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mod['tools'] as $t): ?>
                        <tr>
                            <td><strong><?= e($t['tool']) ?></strong></td>
                            <td><?= e($t['action']) ?></td>
                            <td><span class="badge badge-blue"><?= e($t['type']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if (!empty($mod['copy_menu'])): ?>
            <h4 class="fs-subhead">라벨복사 메뉴 항목</h4>
            <div class="table-wrap">
                <table class="fs-table">
                    <thead>
                        <tr><th>#</th><th>메뉴</th><th>설명</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mod['copy_menu'] as $i => $cm): ?>
                        <tr>
                            <td><?= (int) ($i + 1) ?></td>
                            <td><strong><?= e($cm['action']) ?></strong></td>
                            <td><?= e($cm['desc']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if (!empty($mod['formats'])): ?>
            <h4 class="fs-subhead">지원 데이터 포맷</h4>
            <div class="table-wrap">
                <table class="fs-table">
                    <thead>
                        <tr><th>포맷</th><th>확장자</th><th>안내</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mod['formats'] as $fmt): ?>
                        <tr>
                            <td><strong><?= e($fmt['format']) ?></strong></td>
                            <td><code><?= e($fmt['ext']) ?></code></td>
                            <td><?= e($fmt['hint']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php if (!empty($spec['ux_rules'])): ?>
<section id="fs-ux" class="card fs-section">
    <div class="card-header"><h3>공통 UX 규칙</h3></div>
    <div class="table-wrap">
        <table class="fs-table">
            <thead>
                <tr><th>항목</th><th>내용</th></tr>
            </thead>
            <tbody>
                <?php foreach ($spec['ux_rules'] as $u): ?>
                <tr>
                    <td><strong><?= e($u['item']) ?></strong></td>
                    <td><?= e($u['desc']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($spec['backlog'])): ?>
<section id="fs-backlog" class="card fs-section">
    <div class="card-header"><h3>개발 이관 백로그</h3></div>
    <div class="table-wrap">
        <table class="fs-table">
            <thead>
                <tr><th>우선순위</th><th>항목</th><th>설명</th></tr>
            </thead>
            <tbody>
                <?php foreach ($spec['backlog'] as $b): ?>
                <tr>
                    <td><span class="fs-prio fs-prio--<?= e(strtolower($b['prio'])) ?>"><?= e($b['prio']) ?></span></td>
                    <td><strong><?= e($b['item']) ?></strong></td>
                    <td><?= e($b['desc']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($spec['file_map'])): ?>
<section id="fs-files" class="card fs-section">
    <div class="card-header"><h3>관련 소스 맵</h3></div>
    <div class="table-wrap">
        <table class="fs-table">
            <thead>
                <tr><th>영역</th><th>경로</th></tr>
            </thead>
            <tbody>
                <?php foreach ($spec['file_map'] as $fm): ?>
                <tr>
                    <td><?= e($fm['area']) ?></td>
                    <td><code><?= e($fm['path']) ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($spec['glossary'])): ?>
<section id="fs-glossary" class="card fs-section">
    <div class="card-header"><h3>용어</h3></div>
    <div class="table-wrap">
        <table class="fs-table">
            <thead>
                <tr><th>용어</th><th>설명</th></tr>
            </thead>
            <tbody>
                <?php foreach ($spec['glossary'] as $g): ?>
                <tr>
                    <td><strong><?= e($g['term']) ?></strong></td>
                    <td><?= e($g['desc']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<p class="fs-footnote">
    ※ 본 명세는 스토리보드 프로토타입 기준입니다. API 스키마·권한·성능·접근성은 개발 상세 설계에서 확정합니다.
    편집기 인터랙션 확인:
    <a href="<?= url('storyboard.php') ?>">스토리보드 → 01-05 디자인 편집</a>
</p>
