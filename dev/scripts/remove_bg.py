"""Remove checkerboard/light background from Labi icon PNG."""
from __future__ import annotations

import sys
from collections import deque
from pathlib import Path

from PIL import Image


def is_bg(r: int, g: int, b: int, tol: int = 28) -> bool:
    """Detect checkerboard whites/grays, not character colors."""
    mx, mn = max(r, g, b), min(r, g, b)
    if mx - mn > 35:
        return False
    if mx < 120:
        return False
    targets = [(255, 255, 255), (240, 240, 240), (224, 224, 224), (208, 208, 208), (192, 192, 192)]
    for tr, tg, tb in targets:
        if abs(r - tr) <= tol and abs(g - tg) <= tol and abs(b - tb) <= tol:
            return True
    return mx >= 200 and (mx - mn) <= 18


def remove_background(src: Path, dst: Path) -> None:
    im = Image.open(src).convert("RGBA")
    w, h = im.size
    px = im.load()
    visited = [[False] * w for _ in range(h)]
    q: deque[tuple[int, int]] = deque()

    for x in range(w):
        q.append((x, 0))
        q.append((x, h - 1))
    for y in range(h):
        q.append((0, y))
        q.append((w - 1, y))

    while q:
        x, y = q.popleft()
        if x < 0 or y < 0 or x >= w or y >= h or visited[y][x]:
            continue
        visited[y][x] = True
        r, g, b, a = px[x, y]
        if not is_bg(r, g, b):
            continue
        px[x, y] = (r, g, b, 0)
        q.extend([(x + 1, y), (x - 1, y), (x, y + 1), (x, y - 1)])

    # Trim transparent padding
    bbox = im.getbbox()
    if bbox:
        im = im.crop(bbox)

    # Resize to 512 for crisp FAB use
    im = im.resize((512, 512), Image.Resampling.LANCZOS)
    im.save(dst, "PNG", optimize=True)


if __name__ == "__main__":
    src = Path(sys.argv[1])
    dst = Path(sys.argv[2])
    remove_background(src, dst)
    print(f"Saved {dst} ({dst.stat().st_size} bytes)")
