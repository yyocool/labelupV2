<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>메뉴 구성도 시드</h1>
            <p>Label 몰 IA 전체 메뉴를 한 번에 등록합니다. (Front + Admin)</p>
        </div>
        <a href="<?= admin_url('menus.php') ?>" class="btn btn-secondary btn-sm">← 메뉴 관리</a>
    </div>
</div>

<?php if (!empty($result)): ?>
<div class="alert <?= !empty($result['success']) ? 'alert-success' : 'alert-error' ?>">
    <?= e($result['message']) ?>
    <?php if (!empty($result['success'])): ?>
    <div style="margin-top:10px">
        <a href="<?= url('menus.php?view=tree') ?>" class="btn btn-primary btn-sm">메뉴 구성도(트리) 확인</a>
        <a href="<?= url('menus.php') ?>" class="btn btn-secondary btn-sm">목록 보기</a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>현재 상태</h3></div>
        <p style="font-size:14px;line-height:1.8">
            프로젝트: <strong><?= e($project['name']) ?></strong><br>
            현재 등록 메뉴: <strong><?= (int) $currentCount ?>개</strong><br>
            시드 예정 메뉴: <strong><?= (int) $seedCount ?>개</strong>
        </p>
    </div>
    <div class="card">
        <div class="card-header"><h3>구조 미리보기</h3></div>
        <ul style="font-size:13px;line-height:1.7;color:var(--text-secondary);padding-left:18px">
            <li><strong>사용자 페이지</strong> — HOME, 라벨 편집기(템플릿·규격·자료실 통합), 쇼핑몰, 맞춤제작, 고객센터, 마이페이지</li>
            <li><strong>Backoffice</strong> — 대시보드(포인트 포함), 회원·주문·규격·AI·정산 등 간소화</li>
            <li>기존 대비 메뉴 수 대폭 축소 (v2 IA)</li>
            <li>시드 실행 시 사용하지 않는 스토리보드 PHP 파일도 정리됩니다</li>
        </ul>
    </div>
</div>

<div class="card" style="margin-top:20px;border-color:var(--warning)">
    <div class="card-header"><h3>⚠️ 주의사항</h3></div>
    <ul style="font-size:13px;line-height:1.8;color:var(--text-secondary);padding-left:18px">
        <li><strong>기존 메뉴 전체가 삭제</strong>되고 새 데이터로 교체됩니다.</li>
        <li>연결된 스토리보드·화면도 함께 삭제됩니다.</li>
        <li>이슈의 menu_id 연결은 해제됩니다.</li>
        <li>메뉴코드가 바뀐 스토리보드 PHP 파일은 삭제되고, 새 메뉴는 스텁이 생성됩니다.</li>
        <li>이미 편집한 스토리보드 파일(동일 메뉴코드)은 유지됩니다.</li>
    </ul>

    <form method="post" style="margin-top:20px" onsubmit="return confirm('기존 메뉴 <?= (int) $currentCount ?>개를 모두 삭제하고 <?= (int) $seedCount ?>개 메뉴를 새로 등록합니다.\n\n계속하시겠습니까?')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="seed">
        <button type="submit" class="btn btn-danger">기존 메뉴 초기화 후 시드 데이터 삽입</button>
    </form>
</div>
