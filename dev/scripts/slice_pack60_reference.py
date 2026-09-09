#!/usr/bin/env python3
"""Slice the reference sticker sheet into 60 full sticker PNGs and rebuild manifest for exact-match templates."""
from __future__ import annotations

import json
import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageFilter

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "templates" / "pack60"
MANIFEST = ROOT / "storage" / "imports" / "template_pack60_manifest.json"
REF_CANDIDATES = [
    ROOT / "storage" / "imports" / "pack60_reference.png",
    Path(r"C:\Users\PW1234\.cursor\projects\c-phpstudy-pro-WWW-labelup\assets")
    / "pack60_sheet_hq.png",
    Path(r"C:\Users\PW1234\.cursor\projects\c-phpstudy-pro-WWW-labelup\assets")
    / "c__Users_PW1234_AppData_Roaming_Cursor_User_workspaceStorage_6e6a9ae665954e1a85919f8f8f840eb1_images_image-31a3dfb3-a8f0-483d-af67-96568f2e9b25.png",
]
OUT_SIZE = 768
COLS, ROWS = 10, 6


def find_ref() -> Path:
    for p in REF_CANDIDATES:
        if p.is_file():
            return p
    raise FileNotFoundError("pack60 reference sheet not found")


def detect_cells(im: Image.Image) -> list[tuple[int, int, int, int]]:
    """Return 60 boxes (l,t,r,b) in reading order."""
    arr = np.asarray(im.convert("RGB"), dtype=np.int16)
    # near-white background
    white = (arr[:, :, 0] > 245) & (arr[:, :, 1] > 245) & (arr[:, :, 2] > 245)
    content = ~white
    # project to find gaps
    row_density = content.mean(axis=1)
    col_density = content.mean(axis=0)

    def segments(density: np.ndarray, n: int, min_gap: int = 2) -> list[tuple[int, int]]:
        # threshold adaptive
        thr = max(0.02, float(np.percentile(density[density > 0], 20)) * 0.5) if density.any() else 0.02
        mask = density > thr
        # fill tiny holes
        idx = np.where(mask)[0]
        if len(idx) == 0:
            # equal split fallback
            step = len(density) / n
            return [(int(i * step), int((i + 1) * step) - 1) for i in range(n)]
        cuts = np.where(np.diff(idx) > min_gap)[0]
        starts = [int(idx[0])] + [int(idx[c + 1]) for c in cuts]
        ends = [int(idx[c]) for c in cuts] + [int(idx[-1])]
        segs = list(zip(starts, ends))
        if len(segs) == n:
            return segs
        # fallback equal split within content bounds
        a, b = starts[0], ends[-1]
        step = (b - a + 1) / n
        return [(int(a + i * step), int(a + (i + 1) * step) - 1) for i in range(n)]

    row_segs = segments(row_density, ROWS, min_gap=3)
    col_segs = segments(col_density, COLS, min_gap=2)
    boxes = []
    for rs, re in row_segs:
        for cs, ce in col_segs:
            boxes.append((cs, rs, ce + 1, re + 1))
    return boxes


def to_square(cell: Image.Image, size: int) -> Image.Image:
    cell = cell.convert("RGBA")
    # trim residual white border a bit
    arr = np.asarray(cell)
    alpha = arr[:, :, 3]
    rgb = arr[:, :, :3]
    white = (rgb[:, :, 0] > 250) & (rgb[:, :, 1] > 250) & (rgb[:, :, 2] > 250)
    mask = (alpha > 10) & (~white)
    if mask.any():
        ys, xs = np.where(mask)
        pad = 2
        l = max(0, int(xs.min()) - pad)
        t = max(0, int(ys.min()) - pad)
        r = min(cell.width, int(xs.max()) + 1 + pad)
        b = min(cell.height, int(ys.max()) + 1 + pad)
        cell = cell.crop((l, t, r, b))
    # rounded look on white canvas
    side = max(cell.width, cell.height)
    canvas = Image.new("RGBA", (side, side), (255, 255, 255, 255))
    canvas.paste(cell, ((side - cell.width) // 2, (side - cell.height) // 2), cell)
    out = canvas.resize((size, size), Image.LANCZOS)
    # slight sharpen
    return out.filter(ImageFilter.UnsharpMask(radius=1.2, percent=120, threshold=2))


def main() -> None:
    ref = find_ref()
    # keep a copy in project
    dest_ref = ROOT / "storage" / "imports" / "pack60_reference.png"
    dest_ref.parent.mkdir(parents=True, exist_ok=True)
    if ref.resolve() != dest_ref.resolve():
        Image.open(ref).save(dest_ref)

    sheet = Image.open(ref).convert("RGB")
    print("ref", ref, sheet.size)
    boxes = detect_cells(sheet)
    print("cells", len(boxes))
    if len(boxes) != 60:
        # forced equal grid with margin
        w, h = sheet.size
        ml, mt, mr, mb = 8, 6, 8, 6
        cw = (w - ml - mr) / COLS
        ch = (h - mt - mb) / ROWS
        boxes = []
        for r in range(ROWS):
            for c in range(COLS):
                l = int(ml + c * cw + 2)
                t = int(mt + r * ch + 2)
                boxes.append((l, t, int(ml + (c + 1) * cw - 2), int(mt + (r + 1) * ch - 2)))
        print("fallback equal grid", len(boxes))

    if not MANIFEST.is_file():
        raise SystemExit(f"missing manifest {MANIFEST}")
    data = json.loads(MANIFEST.read_text(encoding="utf-8"))
    items = data["items"]
    if len(items) != 60:
        raise SystemExit(f"manifest items {len(items)} != 60")

    OUT_DIR.mkdir(parents=True, exist_ok=True)
    for i, (item, box) in enumerate(zip(items, boxes), start=1):
        slug = item["slug"]
        cell = sheet.crop(box)
        full = to_square(cell, OUT_SIZE)
        name = f"{slug}_full.png"
        full.save(OUT_DIR / name, "PNG", optimize=True)
        item["art_url"] = f"/assets/templates/pack60/{name}"
        item["layout"] = "full_sticker"
        item["full_image"] = True
        print(f"[{i:02d}/60] {slug} box={box} -> {name}")

    MANIFEST.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    print("updated", MANIFEST)


if __name__ == "__main__":
    main()
