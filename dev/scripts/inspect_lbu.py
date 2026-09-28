"""로컬에 저장한 .lbu 문서의 용지·칸·항목 구성을 훑어 본다. 문제 재현용."""
from __future__ import annotations

import collections
import json
import sys


def main() -> None:
    if len(sys.argv) < 2:
        print('사용: python scripts/inspect_lbu.py <파일경로>', file=sys.stderr)
        sys.exit(1)
    with open(sys.argv[1], encoding='utf-8') as fh:
        doc = json.load(fh)

    paper = doc.get('paper', {})
    print('paper:', paper.get('paperNo'), paper.get('name'))
    print('  grid:', paper.get('columns'), 'x', paper.get('rows'),
          '| label', paper.get('labelWidthMm'), 'x', paper.get('labelHeightMm'),
          '| sheet', paper.get('paperWidthMm'), 'x', paper.get('paperHeightMm'))
    print('  customSlots:', len(paper.get('customSlots') or []))

    pages = doc.get('pages', [])
    print('pages:', len(pages))
    for pi, page in enumerate(pages):
        cells = page.get('cells', [])
        filled = [(c.get('index'), len(c.get('objects') or [])) for c in cells if c.get('objects')]
        print(f'  page {pi}: cells={len(cells)} filled={filled}')

    types: collections.Counter[str] = collections.Counter()
    for page in pages:
        for cell in page.get('cells', []):
            for obj in cell.get('objects') or []:
                types[str(obj.get('type'))] += 1
    print('object types:', dict(types))

    for page in pages:
        for cell in page.get('cells', []):
            for obj in cell.get('objects') or []:
                print(f"  cell {cell.get('index')} | {obj.get('type')}"
                      f" | x={obj.get('x')} y={obj.get('y')}"
                      f" w={obj.get('width')} h={obj.get('height')}"
                      f" | rows={obj.get('tableRows')} cols={obj.get('tableCols')}"
                      f" | cells={obj.get('tableCells')}"
                      f" | text={str(obj.get('text'))[:24]}")


if __name__ == '__main__':
    main()
