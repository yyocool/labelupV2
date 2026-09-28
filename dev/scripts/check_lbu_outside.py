"""저장된 .lbu 문서에서 라벨 밖으로 나가 있는 항목을 찾는다.

용지를 바꾸면 라벨 크기가 달라지므로, 지금은 안 보이던 항목이 큰 라벨에서 드러날 수 있다.
그 대상을 미리 확인하는 용도다.

사용: python scripts/check_lbu_outside.py <파일> [새라벨가로mm] [새라벨세로mm]
"""
from __future__ import annotations

import json
import sys


def classify(obj: dict, w: float, h: float) -> str:
    x, y = float(obj.get('x', 0)), float(obj.get('y', 0))
    ow, oh = float(obj.get('width', 0)), float(obj.get('height', 0))
    if x >= w or y >= h or x + ow <= 0 or y + oh <= 0:
        return 'outside'
    if x < -0.01 or y < -0.01 or x + ow > w + 0.01 or y + oh > h + 0.01:
        return 'partial'
    return 'inside'


def report(doc: dict, w: float, h: float, title: str) -> None:
    counts = {'inside': 0, 'partial': 0, 'outside': 0}
    hidden = []
    for page in doc.get('pages', []):
        for cell in page.get('cells', []):
            for obj in cell.get('objects') or []:
                state = classify(obj, w, h)
                counts[state] += 1
                if state == 'outside':
                    hidden.append((page.get('index'), cell.get('index'), obj))
    print(f'=== {title} (라벨 {w}x{h}mm)')
    print(f'   전부 보임 {counts["inside"]} · 일부 잘림 {counts["partial"]} · 완전히 숨음 {counts["outside"]}')
    for pi, ci, obj in hidden:
        print(f'   숨음: page {pi} cell {ci} | {obj.get("type")}'
              f' | x={float(obj.get("x", 0)):.2f} y={float(obj.get("y", 0)):.2f}'
              f' w={float(obj.get("width", 0)):.2f} h={float(obj.get("height", 0)):.2f}')


def main() -> None:
    if len(sys.argv) < 2:
        print('사용: python scripts/check_lbu_outside.py <파일> [새가로mm] [새세로mm]', file=sys.stderr)
        sys.exit(1)
    with open(sys.argv[1], encoding='utf-8') as fh:
        doc = json.load(fh)

    paper = doc.get('paper', {})
    cur_w = float(paper.get('labelWidthMm', 0))
    cur_h = float(paper.get('labelHeightMm', 0))
    report(doc, cur_w, cur_h, f'현재 용지 {paper.get("paperNo")}')

    if len(sys.argv) >= 4:
        new_w, new_h = float(sys.argv[2]), float(sys.argv[3])
        print()
        report(doc, new_w, new_h, '바꿀 용지')


if __name__ == '__main__':
    main()
