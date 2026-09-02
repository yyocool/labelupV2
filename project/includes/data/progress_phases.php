<?php
/**
 * Label-UP 프로젝트 진행 단계 정의 (가중치 합계 100)
 */
return array(
    array('key' => 'policy_setup', 'label' => '정책수립',     'weight' => 3,  'icon' => '📜'),
    array('key' => 'menu_setup',   'label' => '메뉴구성',     'weight' => 3,  'icon' => '📋'),
    array('key' => 'storyboard',   'label' => '스토리보드',   'weight' => 10, 'icon' => '🎨'),
    array('key' => 'design',       'label' => '디자인',       'weight' => 8,  'icon' => '✏️'),
    array('key' => 'publishing',   'label' => '퍼블리싱',     'weight' => 12, 'icon' => '🖌️'),
    array('key' => 'db_design',    'label' => 'DB설계',       'weight' => 7,  'icon' => '🗄️'),
    array('key' => 'dev_front',    'label' => '개발(사용자)', 'weight' => 22, 'icon' => '💻'),
    array('key' => 'dev_admin',    'label' => '개발(관리자)', 'weight' => 15, 'icon' => '⚙️'),
    array('key' => 'test',         'label' => '테스트',       'weight' => 10, 'icon' => '🧪'),
    array('key' => 'review',       'label' => '보완검수',     'weight' => 7,  'icon' => '✅'),
    array('key' => 'launch',       'label' => '배포',         'weight' => 3,  'icon' => '🚀'),
);
