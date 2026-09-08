#!/usr/bin/env python3
"""Generate 150 high-quality pattern cliparts (unique, non-overlapping with hq_*).

Outputs:
  public/assets/cliparts/hq_pattern_{NN}_{id}.png
  storage/imports/clipart_pattern_manifest.json
"""
from __future__ import annotations

import json
import math
import random
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter

from typing import Any

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "cliparts"
MANIFEST = ROOT / "storage" / "imports" / "clipart_pattern_manifest.json"
SIZE = 768
TARGET = 150

# Brand-aligned premium palettes (fill, accent, soft)
PALETTES: list[tuple[tuple[int, int, int], tuple[int, int, int], tuple[int, int, int], str]] = [
    ((123, 40, 64), (196, 90, 120), (240, 190, 205), "burgundy"),
    ((28, 70, 130), (70, 130, 200), (170, 205, 240), "navy"),
    ((30, 110, 75), (70, 165, 115), (170, 220, 185), "forest"),
    ((180, 95, 30), (230, 150, 60), (250, 210, 145), "amber"),
    ((95, 45, 145), (150, 100, 205), (210, 185, 240), "violet"),
    ((40, 42, 50), (95, 100, 115), (175, 180, 190), "charcoal"),
    ((190, 55, 90), (235, 115, 145), (255, 190, 205), "rose"),
    ((15, 125, 135), (45, 175, 180), (155, 225, 225), "teal"),
    ((145, 40, 45), (205, 85, 75), (240, 175, 160), "crimson"),
    ((55, 95, 45), (115, 155, 75), (185, 210, 140), "olive"),
    ((70, 55, 45), (140, 110, 85), (210, 185, 155), "mocha"),
    ((20, 90, 160), (80, 160, 220), (175, 215, 245), "sky"),
    ((160, 70, 120), (210, 120, 165), (245, 195, 220), "orchid"),
    ((100, 80, 40), (170, 140, 70), (230, 205, 140), "gold"),
    ((50, 80, 90), (90, 140, 150), (175, 205, 210), "slate"),
]


def rgba(c: tuple[int, int, int], a: int = 255) -> tuple[int, int, int, int]:
    return (c[0], c[1], c[2], a)


def lerp(a: float, b: float, t: float) -> float:
    return a + (b - a) * t


def make_base() -> Image.Image:
    """White canvas with subtle vignette for sticker-like depth."""
    img = Image.new("RGBA", (SIZE, SIZE), (255, 255, 255, 255))
    overlay = Image.new("RGBA", (SIZE, SIZE), (255, 255, 255, 0))
    d = ImageDraw.Draw(overlay)
    cx = cy = SIZE / 2
    for r in range(SIZE // 2, 0, -6):
        alpha = int(10 * (r / (SIZE / 2)) ** 2)
        d.ellipse([cx - r, cy - r, cx + r, cy + r], fill=(245, 240, 238, alpha))
    return Image.alpha_composite(img, overlay)


def clip_round_rect(img: Image.Image, radius: int = 48) -> Image.Image:
    """Optional soft rounded frame — keep full square for tile usability."""
    return img


# --- pattern painters (draw into full canvas) ---

def paint_polka(draw: ImageDraw.ImageDraw, fill, accent, soft, rng: random.Random, dense: bool) -> None:
    step = 48 if dense else 72
    r = 10 if dense else 14
    for y in range(step // 2, SIZE, step):
        for x in range(step // 2, SIZE, step):
            ox = (step // 2) if ((y // step) % 2) else 0
            c = accent if ((x + y) // step) % 3 else fill
            draw.ellipse([x + ox - r, y - r, x + ox + r, y + r], fill=rgba(c))


def paint_stripes(draw: ImageDraw.ImageDraw, fill, accent, soft, angle: float, width: int) -> None:
    # Draw thick diagonal/horizontal bands via polygons
    rad = math.radians(angle)
    dx, dy = math.cos(rad), math.sin(rad)
    # perpendicular
    px, py = -dy, dx
    span = SIZE * 2
    i = 0
    t = -span
    while t < span:
        c = fill if i % 2 == 0 else soft
        pts = [
            (SIZE / 2 + dx * t + px * span, SIZE / 2 + dy * t + py * span),
            (SIZE / 2 + dx * (t + width) + px * span, SIZE / 2 + dy * (t + width) + py * span),
            (SIZE / 2 + dx * (t + width) - px * span, SIZE / 2 + dy * (t + width) - py * span),
            (SIZE / 2 + dx * t - px * span, SIZE / 2 + dy * t - py * span),
        ]
        draw.polygon(pts, fill=rgba(c))
        t += width
        i += 1
    # thin accent line every 4th
    t = -span
    i = 0
    while t < span:
        if i % 4 == 0:
            pts = [
                (SIZE / 2 + dx * t + px * span, SIZE / 2 + dy * t + py * span),
                (SIZE / 2 + dx * (t + 4) + px * span, SIZE / 2 + dy * (t + 4) + py * span),
                (SIZE / 2 + dx * (t + 4) - px * span, SIZE / 2 + dy * (t + 4) - py * span),
                (SIZE / 2 + dx * t - px * span, SIZE / 2 + dy * t - py * span),
            ]
            draw.polygon(pts, fill=rgba(accent, 180))
        t += width
        i += 1


def paint_chevron(draw: ImageDraw.ImageDraw, fill, accent, soft, step: int) -> None:
    y = -step
    row = 0
    while y < SIZE + step:
        c = fill if row % 2 == 0 else soft
        for x in range(-step, SIZE + step, step * 2):
            mid = x + step
            pts = [(x, y + step), (mid, y), (mid + step, y + step), (mid, y + step * 2)]
            draw.polygon(pts, fill=rgba(c))
            draw.polygon(
                [(x + 2, y + step), (mid, y + 2), (mid + step - 2, y + step), (mid, y + step * 2 - 2)],
                fill=rgba(accent if row % 2 else soft, 90),
            )
        y += step
        row += 1


def paint_honeycomb(draw: ImageDraw.ImageDraw, fill, accent, soft, r: int) -> None:
    h = int(r * math.sqrt(3))
    row = 0
    for y in range(-h, SIZE + h, h):
        for x in range(-r * 2, SIZE + r * 2, int(r * 1.5)):
            ox = (r * 0.75) if row % 2 else 0
            cx, cy = x + ox, y
            pts = []
            for k in range(6):
                a = math.radians(60 * k - 30)
                pts.append((cx + r * math.cos(a), cy + r * math.sin(a)))
            c = fill if (row + int(x / r)) % 3 else accent
            draw.polygon(pts, fill=rgba(c, 210), outline=rgba(soft))
        row += 1


def paint_grid(draw: ImageDraw.ImageDraw, fill, accent, soft, cell: int, style: str) -> None:
    for y in range(0, SIZE, cell):
        for x in range(0, SIZE, cell):
            if style == "check":
                c = fill if ((x // cell) + (y // cell)) % 2 == 0 else soft
                draw.rectangle([x, y, x + cell, y + cell], fill=rgba(c))
            elif style == "window":
                draw.rectangle([x + 4, y + 4, x + cell - 4, y + cell - 4], outline=rgba(fill), width=3)
                if ((x // cell) + (y // cell)) % 3 == 0:
                    draw.rectangle([x + 12, y + 12, x + cell - 12, y + cell - 12], fill=rgba(accent, 120))
            else:  # plus
                draw.rectangle([x, y, x + cell, y + cell], fill=rgba(soft, 80))
                m = cell // 2
                w = max(4, cell // 8)
                draw.rectangle([x + m - w, y + 8, x + m + w, y + cell - 8], fill=rgba(fill))
                draw.rectangle([x + 8, y + m - w, x + cell - 8, y + m + w], fill=rgba(fill))


def paint_diamonds(draw: ImageDraw.ImageDraw, fill, accent, soft, step: int) -> None:
    for y in range(-step, SIZE + step, step):
        for x in range(-step, SIZE + step, step):
            cx, cy = x + step // 2, y + step // 2
            s = step // 2 - 4
            pts = [(cx, cy - s), (cx + s, cy), (cx, cy + s), (cx - s, cy)]
            c = fill if ((x // step) + (y // step)) % 2 == 0 else accent
            draw.polygon(pts, fill=rgba(c, 220))
            draw.polygon([(cx, cy - s + 6), (cx + s - 6, cy), (cx, cy + s - 6), (cx - s + 6, cy)], fill=rgba(soft, 100))


def paint_waves(draw: ImageDraw.ImageDraw, fill, accent, soft, amp: int, period: int) -> None:
    band = 28
    y0 = -band
    i = 0
    while y0 < SIZE + band:
        c = fill if i % 2 == 0 else soft
        pts = []
        for x in range(0, SIZE + 1, 4):
            y = y0 + amp * math.sin(x / period * math.pi * 2)
            pts.append((x, y))
        for x in range(SIZE, -1, -4):
            y = y0 + band + amp * math.sin(x / period * math.pi * 2 + 0.4)
            pts.append((x, y))
        draw.polygon(pts, fill=rgba(c))
        if i % 3 == 0:
            for x in range(0, SIZE, 8):
                y = y0 + amp * math.sin(x / period * math.pi * 2)
                draw.ellipse([x - 2, y - 2, x + 2, y + 2], fill=rgba(accent, 160))
        y0 += band
        i += 1


def paint_scales(draw: ImageDraw.ImageDraw, fill, accent, soft, r: int) -> None:
    row = 0
    for y in range(-r, SIZE + r, r):
        for x in range(-r * 2, SIZE + r * 2, r * 2):
            ox = r if row % 2 else 0
            cx, cy = x + ox, y
            c = fill if (row + x // r) % 2 == 0 else accent
            draw.pieslice([cx - r, cy - r, cx + r, cy + r], 0, 180, fill=rgba(c, 230), outline=rgba(soft))
        row += 1


def paint_stars(draw: ImageDraw.ImageDraw, fill, accent, soft, rng: random.Random, count: int) -> None:
    draw.rectangle([0, 0, SIZE, SIZE], fill=rgba(soft, 40))
    for _ in range(count):
        cx = rng.randint(40, SIZE - 40)
        cy = rng.randint(40, SIZE - 40)
        r = rng.randint(10, 28)
        pts = []
        for k in range(10):
            ang = math.radians(-90 + k * 36)
            rad = r if k % 2 == 0 else r * 0.42
            pts.append((cx + rad * math.cos(ang), cy + rad * math.sin(ang)))
        c = fill if rng.random() > 0.35 else accent
        draw.polygon(pts, fill=rgba(c, 230))


def paint_crosshatch(draw: ImageDraw.ImageDraw, fill, accent, soft, gap: int) -> None:
    draw.rectangle([0, 0, SIZE, SIZE], fill=rgba(soft, 50))
    for i in range(-SIZE, SIZE * 2, gap):
        draw.line([(i, 0), (i + SIZE, SIZE)], fill=rgba(fill, 200), width=3)
        draw.line([(i, SIZE), (i + SIZE, 0)], fill=rgba(accent, 140), width=2)


def paint_hex_geo(draw: ImageDraw.ImageDraw, fill, accent, soft, r: int) -> None:
    paint_honeycomb(draw, fill, soft, accent, r)
    # inner dots
    h = int(r * math.sqrt(3))
    row = 0
    for y in range(0, SIZE, h):
        for x in range(0, SIZE, int(r * 1.5)):
            ox = (r * 0.75) if row % 2 else 0
            draw.ellipse([x + ox - 4, y - 4, x + ox + 4, y + 4], fill=rgba(accent))
        row += 1


def paint_floral_geo(draw: ImageDraw.ImageDraw, fill, accent, soft, rng: random.Random, step: int) -> None:
    draw.rectangle([0, 0, SIZE, SIZE], fill=rgba(soft, 35))
    for y in range(step // 2, SIZE, step):
        for x in range(step // 2, SIZE, step):
            ox = (step // 2) if ((y // step) % 2) else 0
            cx, cy = x + ox, y
            for k in range(6):
                a = math.radians(60 * k)
                px = cx + 16 * math.cos(a)
                py = cy + 16 * math.sin(a)
                draw.ellipse([px - 10, py - 10, px + 10, py + 10], fill=rgba(fill, 210))
            draw.ellipse([cx - 8, cy - 8, cx + 8, cy + 8], fill=rgba(accent))


def paint_scallop(draw: ImageDraw.ImageDraw, fill, accent, soft, r: int) -> None:
    row = 0
    for y in range(0, SIZE + r, r):
        for x in range(-r, SIZE + r, r * 2):
            ox = r if row % 2 else 0
            draw.ellipse([x + ox - r, y - r, x + ox + r, y + r], fill=rgba(fill if row % 2 == 0 else soft, 220))
            draw.arc([x + ox - r + 6, y - r + 6, x + ox + r - 6, y + r - 6], 200, 340, fill=rgba(accent, 180), width=3)
        row += 1


def paint_tartan(draw: ImageDraw.ImageDraw, fill, accent, soft, band: int) -> None:
    draw.rectangle([0, 0, SIZE, SIZE], fill=rgba(soft, 60))
    for x in range(0, SIZE, band * 2):
        draw.rectangle([x, 0, x + band, SIZE], fill=rgba(fill, 160))
    for y in range(0, SIZE, band * 2):
        draw.rectangle([0, y, SIZE, y + band], fill=rgba(accent, 120))
    for x in range(band // 2, SIZE, band * 2):
        draw.rectangle([x, 0, x + 4, SIZE], fill=rgba(fill, 200))
    for y in range(band // 2, SIZE, band * 2):
        draw.rectangle([0, y, SIZE, y + 4], fill=rgba(accent, 180))


def paint_triangles(draw: ImageDraw.ImageDraw, fill, accent, soft, step: int) -> None:
    for y in range(0, SIZE, step):
        for x in range(0, SIZE, step):
            if ((x // step) + (y // step)) % 2 == 0:
                draw.polygon([(x, y + step), (x + step // 2, y), (x + step, y + step)], fill=rgba(fill))
            else:
                draw.polygon([(x, y), (x + step, y), (x + step // 2, y + step)], fill=rgba(accent, 200))


def paint_rings(draw: ImageDraw.ImageDraw, fill, accent, soft, step: int) -> None:
    draw.rectangle([0, 0, SIZE, SIZE], fill=rgba(soft, 40))
    for y in range(step // 2, SIZE, step):
        for x in range(step // 2, SIZE, step):
            ox = (step // 2) if ((y // step) % 2) else 0
            cx, cy = x + ox, y
            for rad, col, w in [(22, fill, 5), (14, accent, 4), (6, soft, 0)]:
                if w:
                    draw.ellipse([cx - rad, cy - rad, cx + rad, cy + rad], outline=rgba(col), width=w)
                else:
                    draw.ellipse([cx - rad, cy - rad, cx + rad, cy + rad], fill=rgba(col))


def paint_leaves(draw: ImageDraw.ImageDraw, fill, accent, soft, rng: random.Random, step: int) -> None:
    draw.rectangle([0, 0, SIZE, SIZE], fill=rgba(soft, 30))
    for y in range(step // 2, SIZE, step):
        for x in range(step // 2, SIZE, step):
            ox = (step // 2) if ((y // step) % 2) else 0
            cx, cy = x + ox, y
            ang = rng.choice([0, 30, -30, 15])
            leaf = []
            for t in range(0, 181, 10):
                a = math.radians(ang + t)
                rr = 8 + 18 * math.sin(math.radians(t))
                leaf.append((cx + rr * math.cos(a), cy + rr * math.sin(a)))
            draw.polygon(leaf, fill=rgba(fill if ((x + y) // step) % 2 == 0 else accent, 220))


# Catalog builders below — 15 families × 10 palettes = 150
    specs: list[tuple[str, str, str, dict]] = [
        ("polka_dense", "조밀 도트", "dense polka", {"fn": "polka", "dense": True}),
        ("polka_open", "와이드 도트", "open polka", {"fn": "polka", "dense": False}),
        ("stripe_h", "가로 스트라이프", "horizontal stripes", {"fn": "stripes", "angle": 0, "width": 36}),
        ("stripe_v", "세로 스트라이프", "vertical stripes", {"fn": "stripes", "angle": 90, "width": 36}),
        ("stripe_diag", "대각 스트라이프", "diagonal stripes", {"fn": "stripes", "angle": 35, "width": 40}),
        ("stripe_fine", "슬림 스트라이프", "fine stripes", {"fn": "stripes", "angle": -25, "width": 22}),
        ("chevron", "쉐브론", "chevron", {"fn": "chevron", "step": 56}),
        ("chevron_tight", "슬림 쉐브론", "tight chevron", {"fn": "chevron", "step": 40}),
        ("honeycomb", "허니콤", "honeycomb", {"fn": "honey", "r": 34}),
        ("honey_mini", "미니 허니콤", "mini honeycomb", {"fn": "honey", "r": 24}),
        ("check", "체크", "checkerboard", {"fn": "grid", "cell": 48, "style": "check"}),
        ("window", "윈도 그리드", "window grid", {"fn": "grid", "cell": 64, "style": "window"}),
        ("plus_grid", "플러스 그리드", "plus grid", {"fn": "grid", "cell": 56, "style": "plus"}),
        ("diamond", "다이아몬드", "diamonds", {"fn": "diamonds", "step": 56}),
        ("diamond_sm", "미니 다이아", "small diamonds", {"fn": "diamonds", "step": 40}),
        ("wave", "웨이브", "waves", {"fn": "waves", "amp": 14, "period": 90}),
        ("wave_soft", "소프트 웨이브", "soft waves", {"fn": "waves", "amp": 10, "period": 120}),
        ("scales", "스케일", "fish scales", {"fn": "scales", "r": 36}),
        ("stars", "스타 스캐터", "star scatter", {"fn": "stars", "count": 48}),
        ("stars_few", "빅 스타", "big stars", {"fn": "stars", "count": 22}),
        ("hatch", "크로스해치", "crosshatch", {"fn": "hatch", "gap": 28}),
        ("hatch_wide", "와이드 해치", "wide hatch", {"fn": "hatch", "gap": 40}),
        ("hex", "헥사 지오", "hex geometry", {"fn": "hex", "r": 30}),
        ("floral", "지오 플로럴", "geo floral", {"fn": "floral", "step": 72}),
        ("floral_dense", "밀집 플로럴", "dense floral", {"fn": "floral", "step": 56}),
        ("scallop", "스캘럽", "scallop", {"fn": "scallop", "r": 40}),
        ("tartan", "타탄", "tartan", {"fn": "tartan", "band": 36}),
        ("tartan_fine", "파인 타탄", "fine tartan", {"fn": "tartan", "band": 24}),
        ("tri", "트라이앵글", "triangles", {"fn": "tri", "step": 48}),
        ("rings", "링 도트", "ring dots", {"fn": "rings", "step": 64}),
        ("leaves", "리프 패턴", "leaf pattern", {"fn": "leaves", "step": 68}),
    ]
    # 31 families × pick 10 palettes with rotation = need exactly 150
    # Use first 15 families × 10 palettes = 150
    specs = specs[:15]
    items = []
    n = 0
    for fi, (fid, title_ko, title_en, params) in enumerate(specs):
        for pi, (fill, accent, soft, pname) in enumerate(PALETTES[:10]):
            n += 1
            pid = f"{fid}_{pname}"
            items.append({
                "id": pid,
                "n": n,
                "title": f"{title_ko} · {pname}",
                "title_en": f"{title_en} {pname}",
                "params": params,
                "palette": (fill, accent, soft, pname),
                "tags": f"#패턴 #텍스처 #{title_ko.replace(' ', '')} #{pname}",
            })
    assert len(items) == TARGET, len(items)
    return items


def render_item(item: dict) -> Image.Image:
    fill, accent, soft, _ = item["palette"]
    params = item["params"]
    fn = params["fn"]
    rng = random.Random(hash(item["id"]) & 0xFFFFFFFF)

    img = make_base()
    layer = Image.new("RGBA", (SIZE, SIZE), (255, 255, 255, 0))
    draw = ImageDraw.Draw(layer)

    if fn == "polka":
        paint_polka(draw, fill, accent, soft, rng, bool(params.get("dense")))
    elif fn == "stripes":
        paint_stripes(draw, fill, accent, soft, float(params["angle"]), int(params["width"]))
    elif fn == "chevron":
        paint_chevron(draw, fill, accent, soft, int(params["step"]))
    elif fn == "honey":
        paint_honeycomb(draw, fill, accent, soft, int(params["r"]))
    elif fn == "grid":
        paint_grid(draw, fill, accent, soft, int(params["cell"]), str(params["style"]))
    elif fn == "diamonds":
        paint_diamonds(draw, fill, accent, soft, int(params["step"]))
    elif fn == "waves":
        paint_waves(draw, fill, accent, soft, int(params["amp"]), int(params["period"]))
    elif fn == "scales":
        paint_scales(draw, fill, accent, soft, int(params["r"]))
    elif fn == "stars":
        paint_stars(draw, fill, accent, soft, rng, int(params["count"]))
    elif fn == "hatch":
        paint_crosshatch(draw, fill, accent, soft, int(params["gap"]))
    elif fn == "hex":
        paint_hex_geo(draw, fill, accent, soft, int(params["r"]))
    elif fn == "floral":
        paint_floral_geo(draw, fill, accent, soft, rng, int(params["step"]))
    elif fn == "scallop":
        paint_scallop(draw, fill, accent, soft, int(params["r"]))
    elif fn == "tartan":
        paint_tartan(draw, fill, accent, soft, int(params["band"]))
    elif fn == "tri":
        paint_triangles(draw, fill, accent, soft, int(params["step"]))
    elif fn == "rings":
        paint_rings(draw, fill, accent, soft, int(params["step"]))
    elif fn == "leaves":
        paint_leaves(draw, fill, accent, soft, rng, int(params["step"]))
    else:
        paint_polka(draw, fill, accent, soft, rng, False)

    out = Image.alpha_composite(img, layer)
    # Soften slightly for premium print look
    out = out.filter(ImageFilter.SMOOTH_MORE)
    return out.convert("RGBA")


def main() -> int:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    MANIFEST.parent.mkdir(parents=True, exist_ok=True)

    items = build_catalog()
    manifest_items = []
    written = 0
    skipped = 0

    for item in items:
        n = item["n"]
        pid = item["id"]
        fname = f"hq_pattern_{n:03d}_{pid}.png"
        path = OUT_DIR / fname
        rel = f"/assets/cliparts/{fname}"

        if path.is_file() and path.stat().st_size > 8000:
            skipped += 1
            print(f"[skip] {fname}")
        else:
            img = render_item(item)
            img.save(path, "PNG", optimize=True)
            written += 1
            print(f"[ok] {fname}")

        manifest_items.append({
            "title": item["title"],
            "category_slug": "pattern",
            "image_path": rel,
            "hashtags": f"#패턴 #텍스처 #라벨 #클립아트 #고품질 {item['tags']}",
            "description": f"패턴·텍스처용 {item['title']} 클립아트",
            "sort_order": 1000 + n,
        })

    payload = {"count": len(manifest_items), "items": manifest_items}
    MANIFEST.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(json.dumps({"written": written, "skipped": skipped, "total": len(manifest_items), "manifest": str(MANIFEST)}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
