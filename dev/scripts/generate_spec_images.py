#!/usr/bin/env python3
"""Generate A4 label spec preview images on white background."""
from __future__ import annotations

import json
import math
import sys
from pathlib import Path

try:
    from PIL import Image, ImageDraw, ImageFilter, ImageFont
except ImportError:
    import subprocess

    subprocess.check_call([sys.executable, "-m", "pip", "install", "pillow", "-q"])
    from PIL import Image, ImageDraw, ImageFilter, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "specs"
MANIFEST = ROOT / "storage" / "imports" / "specs_export.json"

SIZE = 1024
BG = (252, 252, 253)
PAPER = (255, 255, 255)
PAPER_EDGE = (228, 228, 234)
INK = (28, 28, 36)
A4_RATIO = 210 / 297


def load_font(size: int, bold: bool = False):
    paths = [
        "C:/Windows/Fonts/malgunbd.ttf" if bold else "C:/Windows/Fonts/malgun.ttf",
        "C:/Windows/Fonts/arialbd.ttf" if bold else "C:/Windows/Fonts/arial.ttf",
    ]
    for path in paths:
        if Path(path).exists():
            return ImageFont.truetype(path, size=size)
    return ImageFont.load_default()


def new_canvas() -> Image.Image:
    return Image.new("RGBA", (SIZE, SIZE), BG + (255,))


def add_shadow(base: Image.Image, layer: Image.Image, xy: tuple[int, int] = (0, 0)) -> None:
    alpha = layer.split()[3]
    shadow = Image.new("RGBA", base.size, (0, 0, 0, 0))
    shadow.paste((0, 0, 0, 55), (xy[0], xy[1] + 18), alpha)
    shadow = shadow.filter(ImageFilter.GaussianBlur(22))
    base.alpha_composite(shadow)
    base.alpha_composite(layer, xy)


def grid_dims(count: int) -> tuple[int, int]:
    count = max(1, int(count or 1))
    if count == 1:
        return 1, 1
    best: tuple[int, int] | None = None
    for cols in range(1, int(math.sqrt(count)) + 2):
        if count % cols != 0:
            continue
        rows = count // cols
        pair = (cols, rows) if cols <= rows else (rows, cols)
        if best is None or abs(pair[0] - pair[1]) < abs(best[0] - best[1]):
            best = pair
    return best or (1, count)


def label_fill(material: str) -> tuple[int, int, int]:
    m = (material or "").lower()
    if "pp" in m or "투명" in m:
        return (236, 246, 255)
    if "크라프트" in m or "kraft" in m:
        return (210, 180, 140)
    if "감열" in m:
        return (255, 250, 245)
    if "컬러" in m or "color" in m:
        return (255, 236, 236)
    return (248, 248, 252)


def trim_num(value: float) -> str:
    if abs(value - round(value)) < 0.01:
        return str(int(round(value)))
    return f"{value:.1f}".rstrip("0").rstrip(".")


def render_spec(spec: dict) -> Image.Image:
    width = float(spec.get("width_mm") or 50)
    height = float(spec.get("height_mm") or 30)
    shape = str(spec.get("shape") or "rect")
    labels = int(spec.get("labels_per_sheet") or 1)
    material = str(spec.get("material") or "")
    name = str(spec.get("name") or "")

    cols, rows = grid_dims(labels)
    fill = label_fill(material)

    base = new_canvas()
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)

    sheet_h = 620
    sheet_w = int(sheet_h * A4_RATIO)
    sx = (SIZE - sheet_w) // 2
    sy = (SIZE - sheet_h) // 2 - 20
    sheet = (sx, sy, sx + sheet_w, sy + sheet_h)
    draw.rounded_rectangle(sheet, radius=12, fill=PAPER, outline=PAPER_EDGE, width=2)

    pad_x, pad_y = 36, 48
    inner = (sheet[0] + pad_x, sheet[1] + pad_y, sheet[2] - pad_x, sheet[3] - pad_y - 36)
    gap = 8
    cell_w = (inner[2] - inner[0] - gap * (cols - 1)) / cols
    cell_h = (inner[3] - inner[1] - gap * (rows - 1)) / rows
    label_ratio = width / max(height, 0.01)

    for r in range(rows):
        for c in range(cols):
            cx0 = inner[0] + c * (cell_w + gap)
            cy0 = inner[1] + r * (cell_h + gap)
            cx1 = cx0 + cell_w
            cy1 = cy0 + cell_h
            lw = cell_w
            lh = lw / label_ratio
            if lh > cell_h:
                lh = cell_h
                lw = lh * label_ratio
            lx0 = cx0 + (cell_w - lw) / 2
            ly0 = cy0 + (cell_h - lh) / 2
            lx1 = lx0 + lw
            ly1 = ly0 + lh
            box = (lx0, ly0, lx1, ly1)
            if shape == "round" or (width == height and labels <= 6):
                draw.ellipse(box, fill=fill, outline=(210, 210, 218), width=2)
            else:
                draw.rounded_rectangle(box, radius=max(4, int(min(lw, lh) * 0.08)), fill=fill, outline=(210, 210, 218), width=2)

    title = f"{trim_num(width)} x {trim_num(height)} mm"
    sub = f"{labels}칸 · {material or '라벨지'}"
    draw.text((sheet[0] + 20, sheet[3] - 30), title, fill=INK, font=load_font(22, True))
    draw.text((sheet[0] + sheet_w - 220, sheet[3] - 30), sub, fill=(100, 100, 112), font=load_font(18))
    if name:
        draw.text((sheet[0] + 20, sheet[1] + 12), name[:28], fill=(120, 120, 132), font=load_font(16))

    add_shadow(base, layer)
    return base.convert("RGB")


def main() -> int:
    manifest_path = Path(sys.argv[1]) if len(sys.argv) > 1 else MANIFEST
    if not manifest_path.exists():
        print(f"Manifest not found: {manifest_path}", file=sys.stderr)
        return 1

    specs = json.loads(manifest_path.read_text(encoding="utf-8"))
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    created = 0
    for row in specs:
        spec_id = int(row.get("id") or 0)
        if spec_id <= 0:
            continue
        img = render_spec(row)
        out = OUT_DIR / f"spec_{spec_id}.webp"
        img.save(out, format="WEBP", quality=92, method=6)
        created += 1
        print(f"created {out.name} ({row.get('name', '')})")

    print(f"Generated {created} spec images -> {OUT_DIR}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
