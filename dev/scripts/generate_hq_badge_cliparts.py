#!/usr/bin/env python3
"""Generate 100 high-quality badge / seal / ribbon cliparts.

Outputs:
  public/assets/cliparts/hq_badge_{NNN}_{style}_{color}.png
  storage/imports/clipart_badge_manifest.json
"""
from __future__ import annotations

import json
import math
from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw, ImageFilter, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "cliparts"
MANIFEST = ROOT / "storage" / "imports" / "clipart_badge_manifest.json"
OUT = 768
HI = 1536  # 2× supersample
FONT_BOLD = Path(r"C:\Windows\Fonts\malgunbd.ttf")
FONT_REG = Path(r"C:\Windows\Fonts\malgun.ttf")


def rgba(c, a=255):
    return (int(c[0]), int(c[1]), int(c[2]), int(a))


def mix(a, b, t):
    return (
        int(a[0] + (b[0] - a[0]) * t),
        int(a[1] + (b[1] - a[1]) * t),
        int(a[2] + (b[2] - a[2]) * t),
    )


def darker(c, t=0.3):
    return mix(c, (20, 15, 15), t)


def lighter(c, t=0.35):
    return mix(c, (255, 255, 255), t)


def font(size: int, bold=True):
    p = FONT_BOLD if bold else FONT_REG
    if p.is_file():
        try:
            return ImageFont.truetype(str(p), size=size)
        except OSError:
            pass
    return ImageFont.load_default()


def radial(cx, cy, rx, ry, inner, outer, highlight=None):
    pad = 3
    w = max(4, int(math.ceil(rx * 2)) + pad * 2)
    h = max(4, int(math.ceil(ry * 2)) + pad * 2)
    lcx, lcy = rx + pad, ry + pad
    yy, xx = np.mgrid[0:h, 0:w].astype(np.float32)
    nx = (xx - lcx) / max(rx, 1)
    ny = (yy - lcy) / max(ry, 1)
    dist = np.sqrt(nx * nx + ny * ny)
    mask = dist <= 1.0
    hx = (xx - (lcx - rx * 0.28)) / max(rx * 1.15, 1)
    hy = (yy - (lcy - ry * 0.32)) / max(ry * 1.15, 1)
    hdist = np.clip(np.sqrt(hx * hx + hy * hy), 0, 1)
    out = np.zeros((h, w, 4), dtype=np.float32)
    for i in range(3):
        base = outer[i] + (inner[i] - outer[i]) * (1.0 - dist)
        if highlight is not None:
            base = base + (highlight[i] - base) * (1.0 - hdist) * 0.5
        out[..., i] = np.clip(base, 0, 255)
    edge = np.clip((1.04 - dist) / 0.06, 0, 1)
    out[..., 3] = np.where(mask, 255 * edge, 0)
    img = Image.fromarray(out.astype(np.uint8), "RGBA")
    return img, int(round(cx - lcx)), int(round(cy - lcy))


def paste_rad(base, cx, cy, rx, ry, inner, outer, highlight=None):
    img, x, y = radial(cx, cy, rx, ry, inner, outer, highlight)
    base.alpha_composite(img, (x, y))


def ring(base, cx, cy, r_out, r_in, col_in, col_out):
    """Annular ring with gradient."""
    paste_rad(base, cx, cy, r_out, r_out, lighter(col_in, 0.25), darker(col_out, 0.15), lighter(col_in, 0.55))
    # punch inner by drawing transparent? Use destination-out via mask
    punch = Image.new("L", base.size, 0)
    d = ImageDraw.Draw(punch)
    d.ellipse([cx - r_in, cy - r_in, cx + r_in, cy + r_in], fill=255)
    # soft punch
    arr = np.array(base)
    m = np.array(punch)
    # keep alpha outside, zero inside
    fade = np.clip((np.sqrt(((np.mgrid[0:base.size[1], 0:base.size[0]][1] - cx) ** 2
                             + (np.mgrid[0:base.size[1], 0:base.size[0]][0] - cy) ** 2)) - r_in) / 2, 0, 1)
    # simpler: composite inner disc of fully transparent by cutting
    cut = Image.new("RGBA", base.size, (0, 0, 0, 0))
    # rebuild: outer disk already there; draw inner with clear using paste of empty with mask
    clear = Image.new("RGBA", base.size, (0, 0, 0, 0))
    # Use alpha multiply
    a = arr[..., 3].astype(np.float32)
    yy, xx = np.mgrid[0:base.size[1], 0:base.size[0]].astype(np.float32)
    dist = np.sqrt((xx - cx) ** 2 + (yy - cy) ** 2)
    a = np.where(dist < r_in - 1, 0, a)
    edge = np.clip((dist - (r_in - 1)) / 2.0, 0, 1)
    a = np.where((dist >= r_in - 1) & (dist < r_in + 1), a * edge, a)
    # only affect inside outer
    a = np.where(dist > r_out + 2, arr[..., 3], a)
    arr[..., 3] = a.astype(np.uint8)
    base.paste(Image.fromarray(arr, "RGBA"))


def soft_shadow(layer: Image.Image) -> Image.Image:
    a = layer.split()[-1]
    sh = Image.new("RGBA", layer.size, (0, 0, 0, 0))
    sh.paste(Image.new("RGBA", layer.size, (25, 20, 30, 60)), (10, 16), a)
    sh = sh.filter(ImageFilter.GaussianBlur(18))
    out = Image.new("RGBA", layer.size, (0, 0, 0, 0))
    out.alpha_composite(sh)
    out.alpha_composite(layer)
    return out


def text_center(d, box, text, fill, max_size, bold=True):
    x0, y0, x1, y1 = box
    cw, ch = x1 - x0, y1 - y0
    size = max_size
    while size >= 18:
        f = font(size, bold)
        b = d.textbbox((0, 0), text, font=f)
        tw, th = b[2] - b[0], b[3] - b[1]
        if tw <= cw * 0.88 and th <= ch * 0.8:
            d.text((x0 + (cw - tw) / 2, y0 + (ch - th) / 2 - th * 0.06), text, font=f, fill=fill)
            return
        size -= 4
    f = font(18, bold)
    b = d.textbbox((0, 0), text, font=f)
    tw, th = b[2] - b[0], b[3] - b[1]
    d.text((x0 + (cw - tw) / 2, y0 + (ch - th) / 2), text, font=f, fill=fill)


def star_pts(cx, cy, r, n=5, inner=0.42):
    pts = []
    for i in range(n * 2):
        ang = math.radians(-90 + i * (180 / n))
        rr = r if i % 2 == 0 else r * inner
        pts.append((cx + rr * math.cos(ang), cy + rr * math.sin(ang)))
    return pts


def scallop_circle(d, cx, cy, r, n, fill, outline=None, ow=3):
    pts = []
    for i in range(n * 2):
        ang = math.radians(i * (180 / n))
        rr = r if i % 2 == 0 else r * 0.88
        pts.append((cx + rr * math.cos(ang), cy + rr * math.sin(ang)))
    d.polygon(pts, fill=fill, outline=outline)
    if outline:
        d.line(pts + [pts[0]], fill=outline, width=ow)


PALETTES = [
    {"id": "burgundy", "name": "버건디", "main": (140, 42, 68), "accent": (210, 150, 90), "light": (255, 240, 245), "ink": (255, 255, 255)},
    {"id": "navy", "name": "네이비", "main": (35, 70, 130), "accent": (200, 170, 90), "light": (235, 242, 255), "ink": (255, 255, 255)},
    {"id": "gold", "name": "골드", "main": (200, 150, 45), "accent": (120, 70, 30), "light": (255, 248, 220), "ink": (70, 40, 15)},
    {"id": "emerald", "name": "에메랄드", "main": (30, 120, 85), "accent": (220, 185, 80), "light": (230, 250, 240), "ink": (255, 255, 255)},
    {"id": "rose", "name": "로즈", "main": (210, 80, 115), "accent": (255, 200, 160), "light": (255, 235, 240), "ink": (255, 255, 255)},
    {"id": "charcoal", "name": "차콜", "main": (55, 58, 68), "accent": (190, 195, 205), "light": (245, 246, 248), "ink": (255, 255, 255)},
    {"id": "coral", "name": "코랄", "main": (230, 105, 80), "accent": (255, 220, 160), "light": (255, 240, 230), "ink": (255, 255, 255)},
    {"id": "teal", "name": "틸", "main": (20, 130, 140), "accent": (180, 230, 220), "light": (230, 250, 250), "ink": (255, 255, 255)},
    {"id": "violet", "name": "바이올렛", "main": (105, 55, 160), "accent": (230, 190, 255), "light": (245, 235, 255), "ink": (255, 255, 255)},
    {"id": "ivory", "name": "아이보리", "main": (245, 235, 215), "accent": (160, 110, 60), "light": (255, 252, 245), "ink": (90, 60, 40)},
]


STYLES = [
    ("seal", "원형 실", "BEST"),
    ("scallop", "스캘럽 실", "NEW"),
    ("medal", "메달", "1st"),
    ("ribbon", "리본 배너", "SALE"),
    ("shield", "방패 뱃지", "PRO"),
    ("hex", "헥사 뱃지", "OK"),
    ("star", "스타 뱃지", "★"),
    ("rosette", "로제트", "WIN"),
    ("heart", "하트 뱃지", "♥"),
    ("check", "체크 뱃지", "✓"),
]


def draw_seal(layer, pal, label):
    cx = cy = HI / 2
    d = ImageDraw.Draw(layer)
    # outer metallic ring
    paste_rad(layer, cx, cy, 310, 310, lighter(pal["accent"], 0.35), darker(pal["accent"], 0.25), lighter(pal["accent"], 0.7))
    paste_rad(layer, cx, cy, 275, 275, lighter(pal["main"], 0.15), darker(pal["main"], 0.2), lighter(pal["main"], 0.45))
    # inner plate
    paste_rad(layer, cx, cy, 210, 210, pal["light"], mix(pal["light"], pal["main"], 0.15), (255, 255, 255))
    # decorative dots
    for i in range(24):
        ang = math.radians(i * 15)
        x = cx + 242 * math.cos(ang)
        y = cy + 242 * math.sin(ang)
        paste_rad(layer, x, y, 8, 8, lighter(pal["accent"], 0.5), pal["accent"])
    text_center(d, (cx - 160, cy - 70, cx + 160, cy + 70), label, rgba(pal["main"]), 96)


def draw_scallop(layer, pal, label):
    cx = cy = HI / 2
    d = ImageDraw.Draw(layer)
    scallop_circle(d, cx, cy, 320, 16, rgba(darker(pal["main"], 0.1)), rgba(darker(pal["main"], 0.35)), 6)
    scallop_circle(d, cx, cy, 290, 16, rgba(pal["main"]))
    paste_rad(layer, cx, cy, 210, 210, pal["light"], mix(pal["light"], pal["accent"], 0.2), (255, 255, 255))
    d.ellipse([cx - 200, cy - 200, cx + 200, cy + 200], outline=rgba(pal["accent"]), width=6)
    text_center(d, (cx - 150, cy - 65, cx + 150, cy + 65), label, rgba(pal["main"]), 92)


def draw_medal(layer, pal, label):
    cx = HI / 2
    d = ImageDraw.Draw(layer)
    # ribbon
    top = 120
    d.polygon([(cx - 70, top), (cx - 20, top), (cx - 45, 320)], fill=rgba(pal["main"]))
    d.polygon([(cx + 20, top), (cx + 70, top), (cx + 45, 320)], fill=rgba(darker(pal["main"], 0.12)))
    d.polygon([(cx - 55, top), (cx + 55, top), (cx + 40, 300), (cx - 40, 300)], fill=rgba(lighter(pal["main"], 0.1)))
    # medal disc
    cy = 520
    paste_rad(layer, cx, cy, 230, 230, lighter(pal["accent"], 0.4), darker(pal["accent"], 0.2), lighter(pal["accent"], 0.75))
    paste_rad(layer, cx, cy, 185, 185, lighter(pal["main"], 0.2), darker(pal["main"], 0.15), lighter(pal["main"], 0.5))
    paste_rad(layer, cx, cy, 140, 140, pal["light"], mix(pal["light"], pal["accent"], 0.15), (255, 255, 255))
    text_center(d, (cx - 110, cy - 50, cx + 110, cy + 50), label, rgba(pal["main"]), 78)


def draw_ribbon(layer, pal, label):
    d = ImageDraw.Draw(layer)
    cy = HI / 2
    # tails
    d.polygon([(120, cy - 40), (220, cy), (220, cy + 90), (100, cy + 130), (160, cy + 40)], fill=rgba(darker(pal["main"], 0.15)))
    d.polygon([(HI - 120, cy - 40), (HI - 220, cy), (HI - 220, cy + 90), (HI - 100, cy + 130), (HI - 160, cy + 40)], fill=rgba(darker(pal["main"], 0.15)))
    # main banner with gradient simulation via stacked rects
    x0, x1 = 180, HI - 180
    y0, y1 = cy - 110, cy + 110
    for i in range(12):
        t = i / 11
        col = mix(lighter(pal["main"], 0.25), darker(pal["main"], 0.2), t)
        yy0 = y0 + (y1 - y0) * i / 12
        yy1 = y0 + (y1 - y0) * (i + 1) / 12
        d.rectangle([x0, yy0, x1, yy1], fill=rgba(col))
    # folds
    d.polygon([(x0, y0), (x0 - 40, cy), (x0, y1)], fill=rgba(darker(pal["main"], 0.25)))
    d.polygon([(x1, y0), (x1 + 40, cy), (x1, y1)], fill=rgba(darker(pal["main"], 0.25)))
    # highlight edge
    d.rectangle([x0, y0, x1, y0 + 14], fill=rgba(lighter(pal["main"], 0.45), 160))
    d.rounded_rectangle([x0 + 8, y0 + 8, x1 - 8, y1 - 8], radius=12, outline=rgba(pal["accent"], 180), width=4)
    text_center(d, (x0 + 40, y0 + 20, x1 - 40, y1 - 20), label, rgba(pal["ink"]), 100)


def draw_shield(layer, pal, label):
    cx = HI / 2
    d = ImageDraw.Draw(layer)
    pts = [
        (cx - 260, 220), (cx + 260, 220), (cx + 280, 520), (cx, HI - 180), (cx - 280, 520),
    ]
    d.polygon(pts, fill=rgba(darker(pal["main"], 0.15)))
    pts2 = [
        (cx - 230, 250), (cx + 230, 250), (cx + 245, 500), (cx, HI - 230), (cx - 245, 500),
    ]
    d.polygon(pts2, fill=rgba(pal["main"]))
    # inner plate
    pts3 = [
        (cx - 160, 320), (cx + 160, 320), (cx + 170, 480), (cx, HI - 320), (cx - 170, 480),
    ]
    d.polygon(pts3, fill=rgba(pal["light"]))
    d.line(pts + [pts[0]], fill=rgba(pal["accent"]), width=8)
    text_center(d, (cx - 130, 380, cx + 130, 520), label, rgba(pal["main"]), 84)


def draw_hex(layer, pal, label):
    cx = cy = HI / 2
    d = ImageDraw.Draw(layer)
    r = 300

    def hex_pts(rr):
        return [(cx + rr * math.cos(math.radians(60 * i - 30)), cy + rr * math.sin(math.radians(60 * i - 30))) for i in range(6)]

    d.polygon(hex_pts(r), fill=rgba(darker(pal["main"], 0.12)))
    d.polygon(hex_pts(r - 28), fill=rgba(pal["main"]))
    d.polygon(hex_pts(r - 70), fill=rgba(pal["light"]))
    d.line(hex_pts(r) + [hex_pts(r)[0]], fill=rgba(pal["accent"]), width=7)
    text_center(d, (cx - 140, cy - 60, cx + 140, cy + 60), label, rgba(pal["main"]), 90)


def draw_star(layer, pal, label):
    cx = cy = HI / 2
    d = ImageDraw.Draw(layer)
    d.polygon(star_pts(cx, cy, 340, 5, 0.45), fill=rgba(darker(pal["accent"], 0.1)))
    d.polygon(star_pts(cx, cy, 300, 5, 0.45), fill=rgba(pal["accent"]))
    paste_rad(layer, cx, cy, 130, 130, pal["light"], mix(pal["light"], pal["main"], 0.2), (255, 255, 255))
    d.ellipse([cx - 120, cy - 120, cx + 120, cy + 120], outline=rgba(pal["main"]), width=5)
    text_center(d, (cx - 95, cy - 45, cx + 95, cy + 45), label, rgba(pal["main"]), 72)


def draw_rosette(layer, pal, label):
    cx = cy = HI / 2 - 40
    d = ImageDraw.Draw(layer)
    # petals
    for i in range(12):
        ang = math.radians(i * 30)
        px = cx + 160 * math.cos(ang)
        py = cy + 160 * math.sin(ang)
        paste_rad(layer, px, py, 95, 95, lighter(pal["main"], 0.2), darker(pal["main"], 0.15), lighter(pal["main"], 0.5))
    paste_rad(layer, cx, cy, 150, 150, lighter(pal["accent"], 0.45), darker(pal["accent"], 0.15), lighter(pal["accent"], 0.7))
    paste_rad(layer, cx, cy, 105, 105, pal["light"], mix(pal["light"], pal["main"], 0.1), (255, 255, 255))
    # ribbons bottom
    d.polygon([(cx - 30, cy + 140), (cx - 120, HI - 160), (cx - 40, HI - 160), (cx + 10, cy + 160)], fill=rgba(pal["main"]))
    d.polygon([(cx + 30, cy + 140), (cx + 120, HI - 160), (cx + 40, HI - 160), (cx - 10, cy + 160)], fill=rgba(darker(pal["main"], 0.12)))
    text_center(d, (cx - 80, cy - 40, cx + 80, cy + 40), label, rgba(pal["main"]), 64)


def draw_heart(layer, pal, label):
    cx, cy = HI / 2, HI / 2 + 40
    d = ImageDraw.Draw(layer)
    # heart via circles + triangle
    s = 1.0
    paste_rad(layer, cx - 120, cy - 80, 150, 150, lighter(pal["main"], 0.2), darker(pal["main"], 0.15), lighter(pal["main"], 0.5))
    paste_rad(layer, cx + 120, cy - 80, 150, 150, lighter(pal["main"], 0.2), darker(pal["main"], 0.15), lighter(pal["main"], 0.5))
    d.polygon([(cx - 255, cy - 40), (cx + 255, cy - 40), (cx, cy + 280)], fill=rgba(pal["main"]))
    # fill gap
    paste_rad(layer, cx, cy + 40, 180, 160, lighter(pal["main"], 0.15), pal["main"], lighter(pal["main"], 0.4))
    # inner
    paste_rad(layer, cx, cy + 20, 90, 80, pal["light"], mix(pal["light"], pal["main"], 0.2), (255, 255, 255))
    text_center(d, (cx - 80, cy - 20, cx + 80, cy + 60), label, rgba(pal["main"]), 64)


def draw_check(layer, pal, label):
    cx = cy = HI / 2
    d = ImageDraw.Draw(layer)
    paste_rad(layer, cx, cy, 300, 300, lighter(pal["main"], 0.2), darker(pal["main"], 0.18), lighter(pal["main"], 0.5))
    paste_rad(layer, cx, cy, 240, 240, pal["light"], mix(pal["light"], pal["accent"], 0.1), (255, 255, 255))
    # check mark
    check = [(cx - 110, cy), (cx - 40, cy + 80), (cx + 130, cy - 90)]
    d.line(check, fill=rgba(pal["main"]), width=42)
    # round joints
    for p in check:
        d.ellipse([p[0] - 20, p[1] - 20, p[0] + 20, p[1] + 20], fill=rgba(pal["main"]))
    if label and label != "✓":
        text_center(d, (cx - 100, cy + 120, cx + 100, cy + 200), label, rgba(pal["main"]), 48)


DRAWERS = {
    "seal": draw_seal,
    "scallop": draw_scallop,
    "medal": draw_medal,
    "ribbon": draw_ribbon,
    "shield": draw_shield,
    "hex": draw_hex,
    "star": draw_star,
    "rosette": draw_rosette,
    "heart": draw_heart,
    "check": draw_check,
}


def render_badge(style_id: str, pal: dict, label: str) -> Image.Image:
    layer = Image.new("RGBA", (HI, HI), (0, 0, 0, 0))
    DRAWERS[style_id](layer, pal, label)
    layer = soft_shadow(layer)
    layer = layer.filter(ImageFilter.SMOOTH)
    return layer.resize((OUT, OUT), Image.Resampling.LANCZOS)


def build_catalog():
    items = []
    n = 0
    for style_id, style_name, label in STYLES:
        for pal in PALETTES:
            n += 1
            items.append({
                "n": n,
                "style": style_id,
                "style_name": style_name,
                "pal": pal,
                "label": label,
                "title": f"{style_name} · {pal['name']}",
                "id": f"{style_id}_{pal['id']}",
            })
    return items


def main() -> int:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    MANIFEST.parent.mkdir(parents=True, exist_ok=True)
    items = build_catalog()
    assert len(items) == 100, len(items)

    manifest = []
    written = skipped = 0
    for item in items:
        fname = f"hq_badge_{item['n']:03d}_{item['id']}.png"
        path = OUT_DIR / fname
        rel = f"/assets/cliparts/{fname}"
        if path.is_file() and path.stat().st_size > 12000:
            skipped += 1
            print(f"[skip] {fname}", flush=True)
        else:
            img = render_badge(item["style"], item["pal"], item["label"])
            img.save(path, "PNG", optimize=True)
            written += 1
            print(f"[ok] {fname}", flush=True)

        manifest.append({
            "title": item["title"],
            "category_slug": "badge",
            "image_path": rel,
            "hashtags": f"#뱃지 #실 #리본 #메달 #라벨 #클립아트 #고품질 #{item['style']} #{item['pal']['name']}",
            "description": f"{item['style_name']} 고품질 뱃지 · {item['pal']['name']}",
            "sort_order": 5000 + item["n"],
        })

    MANIFEST.write_text(json.dumps({"count": len(manifest), "items": manifest}, ensure_ascii=False, indent=2), encoding="utf-8")
    print(json.dumps({"written": written, "skipped": skipped, "total": len(manifest), "manifest": str(MANIFEST)}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
