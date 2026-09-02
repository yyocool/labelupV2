#!/usr/bin/env python3
"""Generate premium high-res product thumbnail images (A4 label sheet previews)."""
from __future__ import annotations

import json
import math
import re
import sys
from pathlib import Path

try:
    from PIL import Image, ImageDraw, ImageFilter, ImageFont
except ImportError:
    import subprocess

    subprocess.check_call([sys.executable, "-m", "pip", "install", "pillow", "-q"])
    from PIL import Image, ImageDraw, ImageFilter, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "products"
MANIFEST = ROOT / "storage" / "imports" / "products_import.json"

# A4 portrait high-res canvas
W, H = 840, 1188  # ~210:297
BG = (250, 250, 252)
SHEET = (255, 255, 255)
EDGE = (210, 212, 220)
INK = (36, 38, 48)
MUTED = (120, 124, 136)
ACCENT = (123, 45, 62)


def load_font(size: int, bold: bool = False):
    paths = [
        "C:/Windows/Fonts/malgunbd.ttf" if bold else "C:/Windows/Fonts/malgun.ttf",
        "C:/Windows/Fonts/arialbd.ttf" if bold else "C:/Windows/Fonts/arial.ttf",
        "/usr/share/fonts/truetype/nanum/NanumGothicBold.ttf" if bold else "/usr/share/fonts/truetype/nanum/NanumGothic.ttf",
    ]
    for path in paths:
        if Path(path).exists():
            return ImageFont.truetype(path, size=size)
    return ImageFont.load_default()


def parse_size(text) -> tuple[float, float]:
    if text is None:
        return 0.0, 0.0
    m = re.search(r"([\d.]+)\s*[x×X]\s*([\d.]+)", str(text))
    if not m:
        return 0.0, 0.0
    return float(m.group(1)), float(m.group(2))


def grid_dims(count: int, label_w: float, label_h: float) -> tuple[int, int]:
    count = max(1, int(count or 1))
    if count == 1:
        return 1, 1

    candidates: list[tuple[int, int]] = []
    for cols in range(1, count + 1):
        if count % cols == 0:
            rows = count // cols
            candidates.append((cols, rows))

    # Prefer layout whose aspect matches label aspect on A4
    a4_ratio = 210 / 297
    label_ratio = label_w / max(label_h, 0.01)

    def score(pair: tuple[int, int]) -> float:
        cols, rows = pair
        # Approximate sheet fill aspect
        layout_ratio = (cols * label_w) / max(rows * label_h, 0.01)
        return abs(math.log(layout_ratio / a4_ratio)) + abs(cols - rows) * 0.05

    if not candidates:
        cols = max(1, int(round(math.sqrt(count))))
        rows = math.ceil(count / cols)
        return cols, rows
    return min(candidates, key=score)


def material_style(material: str, group: str) -> dict:
    text = f"{material} {group}".lower()
    if any(k in text for k in ("투명", "clear", "반투명", "translucent")):
        return {"fill": (236, 245, 252), "stroke": (170, 198, 220), "glow": True}
    if any(k in text for k in ("크라프트", "kraft")):
        return {"fill": (214, 184, 148), "stroke": (168, 132, 96), "glow": False}
    if any(k in text for k in ("방수", "waterproof", "pp")):
        return {"fill": (232, 244, 252), "stroke": (120, 170, 200), "glow": True}
    if any(k in text for k in ("광택", "gloss")):
        return {"fill": (248, 250, 255), "stroke": (190, 196, 210), "glow": True}
    if any(k in text for k in ("파스텔", "pastel")):
        return {"fill": (255, 236, 242), "stroke": (230, 180, 196), "glow": False}
    if any(k in text for k in ("컬러", "color", "노랑", "빨강", "파랑", "초록")):
        return {"fill": (255, 232, 232), "stroke": (220, 150, 150), "glow": False}
    if any(k in text for k in ("감열", "thermal")):
        return {"fill": (255, 250, 244), "stroke": (210, 198, 184), "glow": False}
    return {"fill": (246, 247, 250), "stroke": (200, 204, 214), "glow": False}


def trim_num(value: float) -> str:
    if abs(value - round(value)) < 0.05:
        return str(int(round(value)))
    return f"{value:.1f}".rstrip("0").rstrip(".")


def soft_shadow(base: Image.Image, box: tuple[int, int, int, int], radius: int = 28, alpha: int = 48) -> None:
    shadow = Image.new("RGBA", base.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(shadow)
    d.rounded_rectangle((box[0] + 6, box[1] + 14, box[2] + 6, box[3] + 18), radius=18, fill=(0, 0, 0, alpha))
    shadow = shadow.filter(ImageFilter.GaussianBlur(radius))
    base.alpha_composite(shadow)


def draw_label(draw: ImageDraw.ImageDraw, box: tuple[float, float, float, float], style: dict, shape: str) -> None:
    x0, y0, x1, y1 = box
    fill = style["fill"]
    stroke = style["stroke"]
    w, h = x1 - x0, y1 - y0
    is_round = shape == "round" or (abs(w - h) < 2 and w < 90)
    border_w = 3 if min(w, h) >= 40 else 2

    if is_round:
        draw.ellipse((x0, y0, x1, y1), fill=fill, outline=stroke, width=border_w)
    else:
        r = max(5, int(min(w, h) * 0.05))
        draw.rounded_rectangle((x0, y0, x1, y1), radius=r, fill=fill, outline=stroke, width=border_w)


def render_product(row: dict) -> Image.Image:
    sku = str(row.get("sku") or "")
    name = str(row.get("name") or sku)
    group = str(row.get("group") or "")
    material = str(row.get("material") or row.get("material_name") or "")
    labels = int(row.get("labels_per_sheet") or 1)
    lw, lh = parse_size(row.get("spec_mm") or row.get("std_size"))
    if lw <= 0 or lh <= 0:
        lw, lh = 50.0, 30.0

    shape = "round" if abs(lw - lh) < 0.2 and labels >= 8 else "rect"
    if "원형" in name or "원형" in material:
        shape = "round"

    cols, rows = grid_dims(labels, lw, lh)
    style = material_style(material, group)
    style = {**style, "stroke": tuple(max(0, c - 28) for c in style["stroke"])}

    base = Image.new("RGBA", (W, H), BG + (255,))
    layer = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)

    margin_x, margin_top, margin_bottom = 78, 96, 150
    sheet = (margin_x, margin_top, W - margin_x, H - margin_bottom)
    soft_shadow(base, sheet, radius=32, alpha=42)
    draw.rounded_rectangle(sheet, radius=16, fill=SHEET, outline=EDGE, width=2)

    pad = 42
    inner = (sheet[0] + pad, sheet[1] + pad + 28, sheet[2] - pad, sheet[3] - pad - 20)
    gap_x = 14 if cols <= 4 else (10 if cols <= 8 else 7)
    gap_y = 14 if rows <= 4 else (10 if rows <= 8 else 7)
    cell_w = (inner[2] - inner[0] - gap_x * (cols - 1)) / cols
    cell_h = (inner[3] - inner[1] - gap_y * (rows - 1)) / rows
    label_ratio = lw / max(lh, 0.01)

    drawn = 0
    for r in range(rows):
        for c in range(cols):
            if drawn >= labels:
                break
            cx0 = inner[0] + c * (cell_w + gap_x)
            cy0 = inner[1] + r * (cell_h + gap_y)
            box_w, box_h = cell_w, cell_w / label_ratio
            if box_h > cell_h:
                box_h = cell_h
                box_w = cell_h * label_ratio
            lx0 = cx0 + (cell_w - box_w) / 2
            ly0 = cy0 + (cell_h - box_h) / 2
            draw_label(draw, (lx0, ly0, lx0 + box_w, ly0 + box_h), style, shape)
            drawn += 1

    # center total cell count only — no badge, no thick stroke (stroke looked like a rounded box)
    count_text = str(labels)
    font_size = 120 if labels < 10 else (96 if labels < 100 else 72)
    count_font = load_font(font_size, True)
    bbox = count_font.getbbox(count_text)
    tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
    cx = (inner[0] + inner[2]) / 2
    cy = (inner[1] + inner[3]) / 2
    tx = cx - tw / 2
    ty = cy - th / 2 - bbox[1] * 0.1
    draw.text((tx, ty), count_text, fill=(72, 76, 88), font=count_font)

    draw.rounded_rectangle((sheet[0] + 28, sheet[1] + 18, sheet[0] + 150, sheet[1] + 42), radius=999, fill=(243, 232, 236))
    draw.text((sheet[0] + 42, sheet[1] + 22), "LABEL UP", fill=ACCENT, font=load_font(14, True))

    title = f"{trim_num(lw)} × {trim_num(lh)} mm"
    sub_parts = [f"{labels}칸"]
    if material:
        sub_parts.append(material)
    sheets = row.get("sheets_per_pack")
    if sheets:
        sub_parts.append(f"{sheets}매")
    sub = " · ".join(sub_parts)

    draw.text((sheet[0] + 24, sheet[3] + 22), title, fill=INK, font=load_font(28, True))
    draw.text((sheet[0] + 24, sheet[3] + 58), sub, fill=MUTED, font=load_font(18))
    draw.text((sheet[2] - 170, sheet[3] + 28), sku, fill=ACCENT, font=load_font(16, True))

    short = name.split("/")[0] if "/" in name else name
    if short and short != sku:
        draw.text((sheet[0] + 24, sheet[1] + 48), short[:36], fill=MUTED, font=load_font(15))

    base.alpha_composite(layer)
    return base.convert("RGB")


def safe_sku(sku: str) -> str:
    return re.sub(r"[^\w\-]+", "_", sku)[:80]


def main() -> int:
    path = Path(sys.argv[1]) if len(sys.argv) > 1 else MANIFEST
    if not path.exists():
        print(f"Manifest not found: {path}", file=sys.stderr)
        return 1

    rows = json.loads(path.read_text(encoding="utf-8"))
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    created = 0
    for row in rows:
        sku = str(row.get("sku") or "").strip()
        if not sku:
            continue
        img = render_product(row)
        key = safe_sku(sku)
        # high-quality webp + png for compatibility
        webp = OUT_DIR / f"prod_{key}.webp"
        png = OUT_DIR / f"prod_{key}.png"
        # also overwrite old excel extract path so existing thumbs refresh via filemtime
        legacy = OUT_DIR / f"spec_{key}.png"
        img.save(webp, format="WEBP", quality=94, method=6)
        img.save(png, format="PNG", optimize=True)
        img.save(legacy, format="PNG", optimize=True)
        created += 1
        if created % 20 == 0:
            print(f"... {created} images")

    print(f"Generated {created} premium product images -> {OUT_DIR}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
