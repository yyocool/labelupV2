#!/usr/bin/env python3
"""Generate 100 high-quality storefront/signboard cliparts.

Outputs:
  public/assets/cliparts/hq_sign_{NNN}_{id}.png
  storage/imports/clipart_signboard_manifest.json

Existing cliparts are left untouched (unique image_path prefix).
"""
from __future__ import annotations

import json
import math
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "cliparts"
MANIFEST = ROOT / "storage" / "imports" / "clipart_signboard_manifest.json"
SIZE = 768
TARGET = 100

FONT_BOLD = Path(r"C:\Windows\Fonts\malgunbd.ttf")
FONT_REG = Path(r"C:\Windows\Fonts\malgun.ttf")
FONT_FALLBACK = ROOT / "public" / "editor" / "fonts" / "Pretendard-Bold.otf"

PALETTES: list[tuple[tuple[int, int, int], tuple[int, int, int], tuple[int, int, int], str]] = [
    ((123, 40, 64), (196, 90, 120), (255, 245, 248), "burgundy"),
    ((28, 70, 130), (70, 130, 200), (240, 248, 255), "navy"),
    ((30, 110, 75), (70, 165, 115), (240, 252, 245), "forest"),
    ((180, 95, 30), (230, 150, 60), (255, 248, 235), "amber"),
    ((95, 45, 145), (150, 100, 205), (248, 242, 255), "violet"),
    ((40, 42, 50), (95, 100, 115), (245, 245, 247), "charcoal"),
    ((190, 55, 90), (235, 115, 145), (255, 242, 246), "rose"),
    ((15, 125, 135), (45, 175, 180), (240, 252, 252), "teal"),
    ((145, 40, 45), (205, 85, 75), (255, 244, 242), "crimson"),
    ((70, 55, 45), (140, 110, 85), (250, 244, 235), "mocha"),
]


def rgba(c: tuple[int, int, int], a: int = 255) -> tuple[int, int, int, int]:
    return (c[0], c[1], c[2], a)


def load_font(size: int, bold: bool = True) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    paths = [FONT_BOLD if bold else FONT_REG, FONT_FALLBACK, FONT_REG]
    for p in paths:
        if p.is_file():
            try:
                return ImageFont.truetype(str(p), size=size)
            except OSError:
                continue
    return ImageFont.load_default()


def text_size(draw: ImageDraw.ImageDraw, text: str, font: ImageFont.ImageFont) -> tuple[int, int]:
    box = draw.textbbox((0, 0), text, font=font)
    return box[2] - box[0], box[3] - box[1]


def draw_centered_text(
    draw: ImageDraw.ImageDraw,
    xy: tuple[float, float, float, float],
    text: str,
    fill: tuple[int, int, int, int],
    max_size: int = 72,
    bold: bool = True,
) -> None:
    x0, y0, x1, y1 = xy
    cw, ch = x1 - x0, y1 - y0
    size = max_size
    font = load_font(size, bold)
    while size >= 18:
        font = load_font(size, bold)
        tw, th = text_size(draw, text, font)
        if tw <= cw * 0.88 and th <= ch * 0.78:
            draw.text((x0 + (cw - tw) / 2, y0 + (ch - th) / 2 - th * 0.05), text, font=font, fill=fill)
            return
        size -= 4
    font = load_font(18, bold)
    tw, th = text_size(draw, text, font)
    draw.text((x0 + (cw - tw) / 2, y0 + (ch - th) / 2), text, font=font, fill=fill)


def round_rect(
    draw: ImageDraw.ImageDraw,
    box: tuple[float, float, float, float],
    radius: float,
    fill=None,
    outline=None,
    width: int = 1,
) -> None:
    draw.rounded_rectangle(box, radius=radius, fill=fill, outline=outline, width=width)


def shadow_layer(mask: Image.Image, blur: int = 10, alpha: int = 70) -> Image.Image:
    sh = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    alpha_m = mask.split()[-1]
    black = Image.new("RGBA", (SIZE, SIZE), (20, 20, 25, alpha))
    sh.paste(black, (6, 10), alpha_m)
    return sh.filter(ImageFilter.GaussianBlur(blur))


# --- sign painters ---

def paint_hanging_rect(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    # chains / hooks
    for x in (220, 548):
        d.ellipse([x - 14, 70, x + 14, 98], fill=rgba(accent))
        d.rectangle([x - 4, 90, x + 4, 150], fill=rgba(accent))
    board = (120, 150, 648, 560)
    round_rect(d, board, 28, fill=rgba(fill), outline=rgba(accent), width=8)
    round_rect(d, (145, 175, 623, 535), 20, outline=rgba(soft, 200), width=4)
    draw_centered_text(d, (160, 250, 608, 460), label, rgba(soft), 78)
    compose(img, layer)


def paint_hanging_round(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.ellipse([360 - 16, 55, 360 + 16, 87], fill=rgba(accent))
    d.rectangle([356, 80, 364, 140], fill=rgba(accent))
    d.ellipse([140, 130, 628, 618], fill=rgba(fill), outline=rgba(accent), width=10)
    d.ellipse([175, 165, 593, 583], outline=rgba(soft, 180), width=5)
    draw_centered_text(d, (200, 300, 568, 470), label, rgba(soft), 70)
    compose(img, layer)


def paint_vertical_blade(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    # wall mount
    d.rectangle([250, 90, 290, 680], fill=rgba(accent))
    for y in (140, 300, 460, 620):
        d.ellipse([248, y - 10, 292, y + 10], fill=rgba(fill))
    round_rect(d, (300, 110, 560, 660), 18, fill=rgba(fill), outline=rgba(accent), width=7)
    # vertical text char by char
    chars = list(label[:4]) if len(label) <= 4 else list(label[:3])
    font = load_font(56, True)
    total_h = len(chars) * 70
    y = 180 + (400 - total_h) / 2
    for ch in chars:
        tw, th = text_size(d, ch, font)
        d.text((430 - tw / 2, y), ch, font=font, fill=rgba(soft))
        y += 72
    compose(img, layer)


def paint_neon(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    # dark plate
    round_rect(d, (110, 200, 658, 560), 40, fill=rgba((25, 28, 36)))
    # glow rings
    for i, a in enumerate((40, 70, 110)):
        pad = 18 - i * 4
        round_rect(d, (130 - pad, 220 - pad, 638 + pad, 540 + pad), 36 + pad // 2, outline=rgba(accent, a), width=6)
    round_rect(d, (140, 230, 628, 530), 32, outline=rgba(accent), width=6)
    draw_centered_text(d, (170, 280, 598, 480), label, rgba(soft), 74)
    # neon tip dots
    for x in range(180, 600, 40):
        d.ellipse([x - 4, 250, x + 4, 258], fill=rgba(accent, 200))
        d.ellipse([x - 4, 502, x + 4, 510], fill=rgba(accent, 200))
    compose(img, layer)


def paint_wood_plaque(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    wood = (max(fill[0] - 20, 30), max(fill[1] - 15, 25), max(fill[2] - 10, 20))
    round_rect(d, (100, 210, 668, 560), 16, fill=rgba(wood))
    # grain lines
    for i, y in enumerate(range(240, 540, 28)):
        col = accent if i % 2 == 0 else soft
        d.line([(130, y), (638, y)], fill=rgba(col, 55), width=2)
    # screws
    for x, y in ((140, 245), (628, 245), (140, 525), (628, 525)):
        d.ellipse([x - 10, y - 10, x + 10, y + 10], fill=rgba(accent))
        d.line([(x - 5, y), (x + 5, y)], fill=rgba(soft), width=2)
    draw_centered_text(d, (160, 280, 608, 490), label, rgba(soft), 70)
    compose(img, layer)


def paint_arrow(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    # left-pointing arrow body
    pts = [
        (80, 384),
        (230, 200),
        (230, 280),
        (660, 280),
        (660, 488),
        (230, 488),
        (230, 568),
    ]
    d.polygon(pts, fill=rgba(fill), outline=rgba(accent))
    # outline stroke by redrawing thicker via offset is hard; add border rect tip
    d.line(pts + [pts[0]], fill=rgba(accent), width=6)
    draw_centered_text(d, (260, 300, 640, 470), label, rgba(soft), 64)
    compose(img, layer)


def paint_open_badge(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.ellipse([90, 90, 678, 678], fill=rgba(fill))
    d.ellipse([125, 125, 643, 643], outline=rgba(soft, 220), width=8)
    d.ellipse([155, 155, 613, 613], outline=rgba(accent), width=5)
    # top arc caption ring text simplified as dashes
    for i in range(24):
        ang = math.radians(-60 + i * 10)
        x = 384 + 250 * math.cos(ang)
        y = 384 + 250 * math.sin(ang)
        d.ellipse([x - 4, y - 4, x + 4, y + 4], fill=rgba(soft, 180))
    draw_centered_text(d, (200, 310, 568, 470), label, rgba(soft), 72)
    compose(img, layer)


def paint_awning(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    # scalloped awning
    top = 160
    bottom = 320
    stripes = 8
    w = (580) / stripes
    x0 = 94
    for i in range(stripes):
        col = fill if i % 2 == 0 else accent
        x = x0 + i * w
        d.polygon([(x, top), (x + w, top), (x + w, bottom - 20), (x + w / 2, bottom), (x, bottom - 20)], fill=rgba(col))
    # board under
    round_rect(d, (130, 340, 638, 600), 14, fill=rgba((250, 250, 252)), outline=rgba(fill), width=6)
    draw_centered_text(d, (160, 380, 608, 560), label, rgba(fill), 68)
    compose(img, layer)


def paint_marquee(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    round_rect(d, (90, 220, 678, 560), 24, fill=rgba(fill), outline=rgba(accent), width=10)
    # light bulbs
    for x in range(130, 650, 36):
        for y in (240, 540):
            d.ellipse([x - 8, y - 8, x + 8, y + 8], fill=rgba(soft))
            d.ellipse([x - 4, y - 4, x + 4, y + 4], fill=rgba(accent, 200))
    for y in range(280, 520, 40):
        for x in (110, 658):
            d.ellipse([x - 8, y - 8, x + 8, y + 8], fill=rgba(soft))
    round_rect(d, (150, 290, 618, 490), 12, fill=rgba((20, 22, 28)), outline=rgba(accent), width=3)
    draw_centered_text(d, (170, 320, 598, 460), label, rgba(soft), 68)
    compose(img, layer)


def paint_diamond(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    cx = cy = 384
    r = 260
    pts = [(cx, cy - r), (cx + r, cy), (cx, cy + r), (cx - r, cy)]
    d.polygon(pts, fill=rgba(fill), outline=rgba(accent))
    d.line(pts + [pts[0]], fill=rgba(accent), width=8)
    inner = [(cx, cy - r + 40), (cx + r - 40, cy), (cx, cy + r - 40), (cx - r + 40, cy)]
    d.line(inner + [inner[0]], fill=rgba(soft, 160), width=4)
    draw_centered_text(d, (250, 320, 518, 450), label, rgba(soft), 56)
    compose(img, layer)


def paint_crest(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    # shield
    shield = [
        (220, 140), (548, 140), (580, 360), (384, 640), (188, 360),
    ]
    d.polygon(shield, fill=rgba(fill))
    d.line(shield + [shield[0]], fill=rgba(accent), width=8)
    d.arc([250, 170, 518, 360], 200, 340, fill=rgba(soft, 180), width=4)
    draw_centered_text(d, (240, 280, 528, 430), label, rgba(soft), 58)
    # crown tip
    d.polygon([(344, 120), (384, 70), (424, 120)], fill=rgba(accent))
    compose(img, layer)


def paint_chalkboard(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    frame = accent
    round_rect(d, (110, 140, 658, 640), 12, fill=rgba(frame))
    round_rect(d, (140, 170, 628, 580), 6, fill=rgba((40, 48, 42)))
    # chalk dust dots
    for i in range(18):
        x = 180 + (i * 37) % 400
        y = 520 + (i % 3) * 8
        d.ellipse([x, y, x + 3, y + 3], fill=rgba(soft, 90))
    draw_centered_text(d, (170, 260, 598, 480), label, rgba(soft), 68)
    # chalk ledge
    d.rectangle([140, 590, 628, 620], fill=rgba(frame))
    d.rectangle([200, 600, 280, 612], fill=rgba(soft, 200))
    compose(img, layer)


def paint_metal_plate(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    round_rect(d, (120, 220, 648, 560), 8, fill=rgba(fill), outline=rgba(accent), width=5)
    for x, y in ((150, 250), (618, 250), (150, 530), (618, 530)):
        d.ellipse([x - 12, y - 12, x + 12, y + 12], fill=rgba(accent))
        d.ellipse([x - 5, y - 5, x + 5, y + 5], fill=rgba(soft, 180))
    # brushed lines
    for y in range(280, 500, 10):
        d.line([(170, y), (598, y)], fill=rgba(soft, 25), width=1)
    draw_centered_text(d, (180, 300, 588, 480), label, rgba(soft), 66)
    compose(img, layer)


def paint_ribbon(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    # tails
    d.polygon([(80, 260), (180, 320), (180, 460), (80, 520), (140, 390)], fill=rgba(accent))
    d.polygon([(688, 260), (588, 320), (588, 460), (688, 520), (628, 390)], fill=rgba(accent))
    round_rect(d, (150, 280, 618, 500), 10, fill=rgba(fill), outline=rgba(accent), width=6)
    draw_centered_text(d, (180, 320, 588, 460), label, rgba(soft), 68)
    compose(img, layer)


def paint_a_frame(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    # A-board perspective-ish flat
    d.polygon([(200, 160), (568, 160), (620, 640), (148, 640)], fill=rgba(fill), outline=rgba(accent))
    d.line([(200, 160), (568, 160), (620, 640), (148, 640), (200, 160)], fill=rgba(accent), width=8)
    round_rect(d, (230, 220, 538, 520), 8, fill=rgba(soft), outline=rgba(accent), width=4)
    draw_centered_text(d, (250, 280, 518, 460), label, rgba(fill), 60)
    # feet
    d.rectangle([160, 640, 220, 670], fill=rgba(accent))
    d.rectangle([548, 640, 608, 670], fill=rgba(accent))
    compose(img, layer)


def paint_capsule(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    round_rect(d, (80, 280, 688, 500), 110, fill=rgba(fill), outline=rgba(accent), width=8)
    round_rect(d, (110, 305, 658, 475), 90, outline=rgba(soft, 160), width=3)
    draw_centered_text(d, (140, 320, 628, 460), label, rgba(soft), 70)
    compose(img, layer)


def paint_hex(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    cx = cy = 384
    r = 270
    pts = [(cx + r * math.cos(math.radians(60 * i - 30)), cy + r * math.sin(math.radians(60 * i - 30))) for i in range(6)]
    d.polygon(pts, fill=rgba(fill))
    d.line(pts + [pts[0]], fill=rgba(accent), width=8)
    r2 = 230
    pts2 = [(cx + r2 * math.cos(math.radians(60 * i - 30)), cy + r2 * math.sin(math.radians(60 * i - 30))) for i in range(6)]
    d.line(pts2 + [pts2[0]], fill=rgba(soft, 150), width=4)
    draw_centered_text(d, (220, 320, 548, 450), label, rgba(soft), 58)
    compose(img, layer)


def paint_speech(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    round_rect(d, (100, 140, 668, 520), 48, fill=rgba(fill), outline=rgba(accent), width=8)
    d.polygon([(300, 510), (360, 510), (280, 640)], fill=rgba(fill))
    d.line([(300, 510), (280, 640), (360, 510)], fill=rgba(accent), width=6)
    draw_centered_text(d, (140, 220, 628, 440), label, rgba(soft), 68)
    compose(img, layer)


def paint_price_tag(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    pts = [(180, 160), (620, 160), (620, 520), (400, 640), (180, 520)]
    d.polygon(pts, fill=rgba(fill))
    d.line(pts + [pts[0]], fill=rgba(accent), width=7)
    d.ellipse([360, 210, 440, 290], fill=rgba(soft), outline=rgba(accent), width=4)
    d.ellipse([380, 230, 420, 270], fill=rgba(fill))
    draw_centered_text(d, (210, 320, 590, 500), label, rgba(soft), 64)
    compose(img, layer)


def paint_double_bar(img: Image.Image, fill, accent, soft, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.rectangle([100, 250, 668, 290], fill=rgba(accent))
    d.rectangle([100, 500, 668, 540], fill=rgba(accent))
    round_rect(d, (130, 300, 638, 490), 6, fill=rgba(fill), outline=rgba(accent), width=5)
    draw_centered_text(d, (160, 330, 608, 460), label, rgba(soft), 68)
    compose(img, layer)


def compose(base: Image.Image, layer: Image.Image) -> None:
    sh = shadow_layer(layer)
    base.alpha_composite(sh)
    base.alpha_composite(layer)


STYLES: list[tuple[str, str, object]] = [
    ("hang_rect", "가로 걸이간판", paint_hanging_rect),
    ("hang_round", "원형 걸이간판", paint_hanging_round),
    ("blade", "세로 돌출간판", paint_vertical_blade),
    ("neon", "네온 간판", paint_neon),
    ("wood", "우드 플래크", paint_wood_plaque),
    ("arrow", "화살표 안내판", paint_arrow),
    ("open_badge", "오픈 뱃지", paint_open_badge),
    ("awning", "어닝 간판", paint_awning),
    ("marquee", "마키 간판", paint_marquee),
    ("diamond", "다이아 간판", paint_diamond),
    ("crest", "엠블럼 간판", paint_crest),
    ("chalkboard", "칠판 메뉴판", paint_chalkboard),
    ("metal", "메탈 플레이트", paint_metal_plate),
    ("ribbon", "리본 현수막", paint_ribbon),
    ("aframe", "A보드 입간판", paint_a_frame),
    ("capsule", "캡슐 배너", paint_capsule),
    ("hex", "헥사 모던간판", paint_hex),
    ("speech", "말풍선 간판", paint_speech),
    ("pricetag", "프라이스 태그", paint_price_tag),
    ("doublebar", "더블바 간판", paint_double_bar),
]

LABELS = [
    "OPEN", "CAFE", "SALE", "SHOP", "MENU",
    "BAR", "HOTEL", "맛집", "할인", "영업중",
]


def build_catalog() -> list[dict]:
    items: list[dict] = []
    n = 0
    # 20 styles × 5 palettes = 100
    palettes = PALETTES[:5]
    for style_id, style_name, painter in STYLES:
        for fill, accent, soft, pname in palettes:
            n += 1
            label = LABELS[(n - 1) % len(LABELS)]
            pid = f"{style_id}_{pname}"
            items.append({
                "n": n,
                "id": pid,
                "title": f"{style_name} · {pname}",
                "label": label,
                "tags": f"#간판 #매장 #{style_id} #{pname} #{label}",
                "fill": fill,
                "accent": accent,
                "soft": soft,
                "painter": painter,
            })
            if n >= TARGET:
                return items
    return items


def render_item(item: dict) -> Image.Image:
    img = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    painter = item["painter"]
    painter(img, item["fill"], item["accent"], item["soft"], item["label"])
    # slight polish
    return img.filter(ImageFilter.SMOOTH)


def main() -> int:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    MANIFEST.parent.mkdir(parents=True, exist_ok=True)

    items = build_catalog()
    assert len(items) == TARGET, len(items)
    manifest_items = []
    written = 0
    skipped = 0

    for item in items:
        n = item["n"]
        pid = item["id"]
        fname = f"hq_sign_{n:03d}_{pid}.png"
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
            "category_slug": "signboard",
            "image_path": rel,
            "hashtags": f"#간판 #매장 #라벨 #클립아트 #고품질 {item['tags']}",
            "description": f"매장·간판용 {item['title']} 클립아트 ({item['label']})",
            "sort_order": 2000 + n,
        })

    payload = {"count": len(manifest_items), "items": manifest_items}
    MANIFEST.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(json.dumps({
        "written": written,
        "skipped": skipped,
        "total": len(manifest_items),
        "manifest": str(MANIFEST),
    }, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
