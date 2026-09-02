<?php
/**
 * 디자인 시안 정의 (개발 > 디자인)
 * A = 마케팅 랜딩(공개 홈) · B = 로그인 후 워크스페이스
 */
function design_draft_list()
{
    return array(
        array(
            'id' => 'a',
            'title' => '시안 A · 마케팅 랜딩',
            'subtitle' => '공개 홈 · 라벨 디자인·템플릿·쇼핑몰 랜딩',
            'badge' => 'Draft A',
            'tone' => 'light',
        ),
        array(
            'id' => 'b',
            'title' => '시안 B · 워크스페이스',
            'subtitle' => '로그인 후 홈 · 듀얼 사이드바 대시보드',
            'badge' => 'Draft B',
            'tone' => 'brand',
        ),
    );
}

function design_draft_by_id($id)
{
    foreach (design_draft_list() as $d) {
        if ($d['id'] === $id) {
            return $d;
        }
    }
    return null;
}
