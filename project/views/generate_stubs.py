#!/usr/bin/env python3
"""menu_seed_tree.php 기준으로 storyboard/*.php 스텁 파일 생성"""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SEED_FILE = ROOT / 'includes' / 'data' / 'menu_seed_tree.php'
OUT_DIR = Path(__file__).resolve().parent


def parse_seed_tree(text):
    lines = text.splitlines()
    root = []
    stack = [root]
    current = None

    title_re = re.compile(r"'title'\s*=>\s*'((?:\\'|[^'])*)'")

    for line in lines:
        stripped = line.strip()
        m = title_re.search(stripped)
        if m:
            title = m.group(1).replace("\\'", "'")
            node = {'title': title, 'children': []}
            stack[-1].append(node)
            current = node
            if "'children'" in stripped and 'array(' in stripped:
                stack.append(node['children'])

        if current and "'children'" in stripped and 'array(' in stripped and not m:
            stack.append(current['children'])

        closes = stripped.count(')),')
        for _ in range(closes):
            if len(stack) > 1:
                stack.pop()

    return root


def assign_codes(nodes, parent=None):
    out = []
    for i, node in enumerate(nodes, 1):
        seg = f'{i:02d}'
        code = seg if parent is None else f'{parent}-{seg}'
        out.append((code, node['title']))
        if node.get('children'):
            out.extend(assign_codes(node['children'], code))
    return out


def stub_content(code, title):
    esc_title = title.replace("'", "\\'")
    return f"""<?php
/**
 * 스토리보드: {title}
 * 메뉴코드: {code}
 *
 * @var array $menu
 * @var array|null $storyboard
 */
?>
<div class="sb-page sb-page--placeholder">
    <div class="sb-page-meta">
        <span class="sb-page-code"><?= e(isset($menu['menu_code']) ? $menu['menu_code'] : '{code}') ?></span>
        <h2 class="sb-page-title"><?= e(isset($menu['title']) ? $menu['title'] : '{esc_title}') ?></h2>
    </div>
    <p class="sb-page-notice">스토리보드 작업 예정입니다.</p>
    <p class="sb-page-hint">이 파일(<code>storyboard/{code}.php</code>)을 직접 편집하여 화면을 구성하세요.</p>
</div>
"""


def main():
    text = SEED_FILE.read_text(encoding='utf-8')
    tree = parse_seed_tree(text)
    items = assign_codes(tree)
    OUT_DIR.mkdir(parents=True, exist_ok=True)

    created = 0
    skipped = 0
    for code, title in items:
        path = OUT_DIR / f'{code}.php'
        if path.exists():
            skipped += 1
            continue
        path.write_text(stub_content(code, title), encoding='utf-8', newline='\n')
        created += 1

    print(f'Generated: {created}, skipped (existing): {skipped}, total: {len(items)}')


if __name__ == '__main__':
    main()
