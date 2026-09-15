"""Batch-remove solid white/light backgrounds from registered cliparts.

Targets OpenAI HQ illustrations with opaque white canvas.
Skips already-transparent PNGs and full-bleed pattern tiles (hq_pattern_*).
"""
from __future__ import annotations

import argparse
import sys
from collections import deque
from pathlib import Path

from PIL import Image


ROOT = Path(__file__).resolve().parents[1]
CLIPART_DIR = ROOT / "public" / "assets" / "cliparts"


def is_bg(r: int, g: int, b: int, a: int, tol: int = 32) -> bool:
    if a < 8:
        return True
    mx, mn = max(r, g, b), min(r, g, b)
    if mx - mn > 40:
        return False
    # near-white / light gray canvas
    if mx >= 235 and (mx - mn) <= 28:
        return True
    if mx >= 210 and (mx - mn) <= 16:
        return True
    targets = [(255, 255, 255), (250, 250, 250), (248, 248, 248), (245, 245, 245), (240, 240, 240)]
    for tr, tg, tb in targets:
        if abs(r - tr) <= tol and abs(g - tg) <= tol and abs(b - tb) <= tol:
            return True
    return False


def needs_processing(path: Path) -> bool:
    name = path.name.lower()
    if name.startswith("hq_pattern_"):
        return False
    try:
        with Image.open(path) as im:
            if im.mode != "RGBA":
                return True
            a = im.getchannel("A")
            mn, _ = a.getextrema()
            return mn >= 250
    except Exception:
        return False


def remove_background(src: Path, dst: Path, trim: bool = True) -> dict:
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

    cleared = 0
    while q:
        x, y = q.popleft()
        if x < 0 or y < 0 or x >= w or y >= h or visited[y][x]:
            continue
        visited[y][x] = True
        r, g, b, a = px[x, y]
        if not is_bg(r, g, b, a):
            continue
        if a != 0:
            px[x, y] = (r, g, b, 0)
            cleared += 1
        q.extend([(x + 1, y), (x - 1, y), (x, y + 1), (x, y - 1)])

    if trim:
        bbox = im.getbbox()
        if bbox:
            # keep a little padding so soft edges are not clipped
            pad = max(2, min(w, h) // 128)
            left = max(0, bbox[0] - pad)
            top = max(0, bbox[1] - pad)
            right = min(w, bbox[2] + pad)
            bottom = min(h, bbox[3] + pad)
            im = im.crop((left, top, right, bottom))

    dst.parent.mkdir(parents=True, exist_ok=True)
    im.save(dst, "PNG", optimize=True)
    a = im.getchannel("A")
    mn, mx = a.getextrema()
    return {
        "cleared": cleared,
        "size": im.size,
        "alpha_min": mn,
        "alpha_max": mx,
        "bytes": dst.stat().st_size,
    }


def main() -> int:
    parser = argparse.ArgumentParser(description="Remove white backgrounds from cliparts")
    parser.add_argument("--dir", type=Path, default=CLIPART_DIR)
    parser.add_argument("--limit", type=int, default=0, help="Process at most N files (0=all)")
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--no-trim", action="store_true")
    parser.add_argument("--include-pattern", action="store_true", help="Also process hq_pattern_*")
    args = parser.parse_args()

    root: Path = args.dir
    if not root.is_dir():
        print(f"Directory not found: {root}", file=sys.stderr)
        return 1

    files = sorted(root.glob("*.png"))
    targets: list[Path] = []
    for path in files:
        if path.name.lower().startswith("hq_pattern_") and not args.include_pattern:
            continue
        if needs_processing(path):
            targets.append(path)

    if args.limit > 0:
        targets = targets[: args.limit]

    print(f"Found {len(targets)} opaque cliparts under {root}")
    if args.dry_run:
        for p in targets[:30]:
            print(f"  DRY {p.name}")
        if len(targets) > 30:
            print(f"  ... +{len(targets) - 30} more")
        return 0

    ok = 0
    fail = 0
    for i, path in enumerate(targets, 1):
        try:
            info = remove_background(path, path, trim=not args.no_trim)
            ok += 1
            print(
                f"[{i}/{len(targets)}] {path.name} "
                f"cleared={info['cleared']} alpha={info['alpha_min']}-{info['alpha_max']} "
                f"size={info['size']}"
            )
        except Exception as e:
            fail += 1
            print(f"[{i}/{len(targets)}] FAIL {path.name}: {e}", file=sys.stderr)

    print(f"Done. ok={ok} fail={fail}")
    return 0 if fail == 0 else 2


if __name__ == "__main__":
    raise SystemExit(main())
