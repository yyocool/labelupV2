#!/usr/bin/env python3
"""Generate 50 high-quality logistics/warehouse phrase cliparts (transparent PNG).

Outputs:
  public/assets/cliparts/hq_logiphrase_{NN}_{id}.png
  storage/imports/clipart_logistics_phrase_manifest.json
"""
from __future__ import annotations

import json
import math
import re
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "cliparts"
MANIFEST = ROOT / "storage" / "imports" / "clipart_logistics_phrase_manifest.json"
SIZE = 768
TARGET = 50
CATEGORY = "logistics-phrase"

FONT_BOLD = Path(r"C:\Windows\Fonts\malgunbd.ttf")
FONT_REG = Path(r"C:\Windows\Fonts\malgun.ttf")
FONT_FALLBACK = ROOT / "public" / "editor" / "fonts" / "Pretendard-Bold.otf"

PALETTES = [
    ((196, 70, 30), (140, 45, 15), (255, 248, 240), "orange"),
    ((28, 70, 130), (18, 48, 95), (240, 248, 255), "navy"),
    ((50, 52, 60), (30, 32, 38), (248, 248, 250), "charcoal"),
    ((180, 40, 40), (120, 25, 25), (255, 244, 242), "red"),
    ((35, 110, 80), (22, 75, 55), (240, 252, 245), "forest"),
    ((15, 125, 135), (10, 90, 98), (240, 252, 252), "teal"),
    ((166, 27, 55), (124, 16, 39), (255, 250, 251), "burgundy"),
    ((180, 140, 20), (130, 95, 10), (255, 252, 235), "amber"),
]

# 물류·창고·배송 현장 라벨 문구
PHRASES: list[tuple[str, str]] = [
    ("취급주의", "handle"),
    ("파손주의", "fragile"),
    ("습기주의", "keepdry"),
    ("이 면이 위로", "thiswayup"),
    ("직사광선금지", "nosun"),
    ("냉장보관", "chill"),
    ("냉동보관", "frozen"),
    ("개봉금지", "donotopen"),
    ("중량물", "heavy"),
    ("위험물", "hazard"),
    ("쌓지마세요", "nostack"),
    ("뒤집지마세요", "donotflip"),
    ("출고", "outbound"),
    ("입고", "inbound"),
    ("피킹", "picking"),
    ("패킹완료", "packed"),
    ("검수완료", "inspected"),
    ("보류", "hold"),
    ("반품", "return"),
    ("교환", "exchange"),
    ("긴급출고", "rushout"),
    ("우선배송", "priority"),
    ("당일배송", "sameday"),
    ("예약배송", "scheduled"),
    ("부분출고", "partial"),
    ("완납", "paid"),
    ("미납", "unpaid"),
    ("송장부착", "waybill"),
    ("바코드", "barcode"),
    ("LOT번호", "lot"),
    ("유통기한", "expiry"),
    ("제조일자", "mfgdate"),
    ("박스번호", "boxno"),
    ("주문번호", "orderno"),
    ("보관중", "instore"),
    ("운송중", "intransit"),
    ("도착", "arrived"),
    ("부재중", "absent"),
    ("재배송", "redeliver"),
    ("폐기", "dispose"),
    ("FRAGILE", "fragileeng"),
    ("THIS SIDE UP", "thisupeng"),
    ("KEEP DRY", "keepdryeng"),
    ("HANDLE WITH CARE", "hwc"),
    ("DO NOT STACK", "nostackeng"),
    ("PERISHABLE", "perish"),
    ("URGENT SHIP", "urgentship"),
    ("QC PASS", "qcpass"),
    ("QC HOLD", "qchold"),
    ("MIXED SKU", "mixedsku"),
]


def rgba(c: tuple[int, int, int], a: int = 255) -> tuple[int, int, int, int]:
    return (c[0], c[1], c[2], a)


def slugify(s: str) -> str:
    s = re.sub(r"[^a-zA-Z0-9]+", "", s.lower())
    return s or "x"


def load_font(size: int, bold: bool = True) -> ImageFont.ImageFont:
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


def fit_text(
    draw: ImageDraw.ImageDraw,
    box: tuple[float, float, float, float],
    text: str,
    fill: tuple[int, int, int, int],
    max_size: int = 96,
    bold: bool = True,
) -> None:
    x0, y0, x1, y1 = box
    cw, ch = x1 - x0, y1 - y0
    # multi-line if long english
    lines = [text]
    if " " in text and len(text) > 12:
        parts = text.split(" ")
        mid = max(1, len(parts) // 2)
        lines = [" ".join(parts[:mid]), " ".join(parts[mid:])]
    size = max_size
    while size >= 14:
        font = load_font(size, bold)
        widths, heights = [], []
        for ln in lines:
            tw, th = text_size(draw, ln, font)
            widths.append(tw)
            heights.append(th)
        total_h = sum(heights) + (len(lines) - 1) * int(size * 0.18)
        if max(widths) <= cw * 0.9 and total_h <= ch * 0.82:
            y = y0 + (ch - total_h) / 2 - heights[0] * 0.06
            for i, ln in enumerate(lines):
                tw, th = widths[i], heights[i]
                draw.text((x0 + (cw - tw) / 2, y), ln, font=font, fill=fill)
                y += th + int(size * 0.18)
            return
        size -= 3
    font = load_font(14, bold)
    tw, th = text_size(draw, text, font)
    draw.text((x0 + (cw - tw) / 2, y0 + (ch - th) / 2), text, font=font, fill=fill)


def round_rect(d, box, radius, fill=None, outline=None, width: int = 1) -> None:
    d.rounded_rectangle(box, radius=radius, fill=fill, outline=outline, width=width)


def soft_shadow(layer: Image.Image, blur: int = 12, alpha: int = 55) -> Image.Image:
    a = layer.split()[-1]
    sh = Image.new("RGBA", layer.size, (0, 0, 0, 0))
    sh.paste(Image.new("RGBA", layer.size, (20, 18, 25, alpha)), (8, 12), a)
    sh = sh.filter(ImageFilter.GaussianBlur(blur))
    out = Image.new("RGBA", layer.size, (0, 0, 0, 0))
    out.alpha_composite(sh)
    out.alpha_composite(layer)
    return out


def compose(base: Image.Image, layer: Image.Image) -> None:
    base.alpha_composite(soft_shadow(layer))


def paint_capsule(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    round_rect(d, (70, 270, 698, 500), 110, fill=rgba(fill), outline=rgba(accent), width=8)
    fit_text(d, (110, 300, 658, 470), label, rgba(textc), 86)
    compose(img, layer)


def paint_rounded_banner(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    round_rect(d, (80, 250, 688, 520), 36, fill=rgba(fill), outline=rgba(accent), width=10)
    round_rect(d, (105, 275, 663, 495), 24, outline=rgba(textc, 120), width=3)
    fit_text(d, (130, 300, 638, 470), label, rgba(textc), 82)
    compose(img, layer)


def paint_warning_stripe(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    round_rect(d, (70, 240, 698, 530), 28, fill=rgba(fill), outline=rgba(accent), width=8)
    # diagonal caution stripes top/bottom
    for i in range(-2, 14):
        x = 70 + i * 55
        d.polygon([(x, 240), (x + 28, 240), (x + 28 + 40, 290), (x + 40, 290)], fill=rgba(textc, 55))
        d.polygon([(x, 480), (x + 28, 480), (x + 28 + 40, 530), (x + 40, 530)], fill=rgba(textc, 55))
    fit_text(d, (110, 310, 658, 460), label, rgba(textc), 78)
    compose(img, layer)


def paint_stamp(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.ellipse([110, 110, 658, 658], outline=rgba(fill), width=18)
    d.ellipse([145, 145, 623, 623], outline=rgba(fill, 180), width=6)
    round_rect(d, (170, 310, 598, 460), 12, outline=rgba(fill), width=8)
    fit_text(d, (190, 325, 578, 445), label, rgba(fill), 64)
    compose(img, layer)


def paint_arrow_banner(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    pts = [(60, 260), (560, 260), (700, 384), (560, 508), (60, 508)]
    d.polygon(pts, fill=rgba(fill))
    d.line(pts + [pts[0]], fill=rgba(accent), width=7)
    fit_text(d, (100, 300, 540, 470), label, rgba(textc), 74)
    compose(img, layer)


def paint_tag(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    pts = [(120, 220), (620, 220), (660, 384), (620, 548), (120, 548), (80, 384)]
    d.polygon(pts, fill=rgba(fill))
    d.line(pts + [pts[0]], fill=rgba(accent), width=7)
    d.ellipse([340, 250, 420, 330], fill=rgba(textc), outline=rgba(accent), width=4)
    d.ellipse([360, 270, 400, 310], fill=rgba(fill))
    fit_text(d, (150, 340, 610, 520), label, rgba(textc), 68)
    compose(img, layer)


def paint_outline(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    round_rect(d, (90, 260, 678, 510), 40, outline=rgba(fill), width=14)
    fit_text(d, (130, 300, 638, 470), label, rgba(fill), 80)
    compose(img, layer)


def paint_hex(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    cx = cy = 384
    r = 280
    pts = [
        (cx + r * math.cos(math.radians(60 * i - 30)), cy + r * math.sin(math.radians(60 * i - 30)))
        for i in range(6)
    ]
    d.polygon(pts, fill=rgba(fill))
    d.line(pts + [pts[0]], fill=rgba(accent), width=8)
    fit_text(d, (200, 300, 568, 470), label, rgba(textc), 58)
    compose(img, layer)


def paint_shield(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    pts = [(384, 90), (640, 180), (620, 420), (384, 670), (148, 420), (128, 180)]
    d.polygon(pts, fill=rgba(fill))
    d.line(pts + [pts[0]], fill=rgba(accent), width=8)
    fit_text(d, (190, 280, 578, 470), label, rgba(textc), 58)
    compose(img, layer)


def paint_ribbon(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    pts = [(60, 280), (708, 280), (668, 384), (708, 488), (60, 488), (100, 384)]
    d.polygon(pts, fill=rgba(fill))
    d.line(pts + [pts[0]], fill=rgba(accent), width=6)
    fit_text(d, (130, 310, 640, 460), label, rgba(textc), 74)
    compose(img, layer)


def paint_ticket(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    round_rect(d, (70, 250, 698, 520), 28, fill=rgba(fill), outline=rgba(accent), width=8)
    for y in range(280, 500, 28):
        d.ellipse([58, y, 82, y + 24], fill=(0, 0, 0, 0))
        d.ellipse([686, y, 710, y + 24], fill=(0, 0, 0, 0))
    for x in range(200, 560, 24):
        d.rectangle([x, 380, x + 12, 388], fill=rgba(textc, 140))
    fit_text(d, (120, 270, 650, 370), label, rgba(textc), 64)
    compose(img, layer)


def paint_double_line(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.rectangle([100, 240, 668, 258], fill=rgba(fill))
    d.rectangle([100, 510, 668, 528], fill=rgba(fill))
    fit_text(d, (110, 290, 658, 470), label, rgba(fill), 84)
    compose(img, layer)


def paint_softpill(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    soft = tuple(min(255, int(c + (255 - c) * 0.82)) for c in fill)
    round_rect(d, (70, 280, 698, 490), 100, fill=rgba(soft), outline=rgba(fill), width=7)
    fit_text(d, (110, 310, 658, 460), label, rgba(fill), 78)
    compose(img, layer)


def paint_box_mark(img, fill, accent, textc, label: str) -> None:
    """Warehouse box-style plate with corner marks."""
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    round_rect(d, (90, 230, 678, 540), 18, fill=rgba(fill), outline=rgba(accent), width=10)

    def corner(x: int, y: int, right: bool, down: bool) -> None:
        hx = 36 if right else -36
        hy = 36 if down else -36
        # horizontal bar
        d.rectangle([min(x, x + hx), y - 4, max(x, x + hx), y + 4], fill=rgba(textc))
        # vertical bar
        d.rectangle([x - 4, min(y, y + hy), x + 4, max(y, y + hy)], fill=rgba(textc))

    corner(130, 270, True, True)
    corner(638, 270, False, True)
    corner(130, 500, True, False)
    corner(638, 500, False, False)
    fit_text(d, (140, 300, 628, 470), label, rgba(textc), 72)
    compose(img, layer)


STYLES: list[tuple[str, str, object]] = [
    ("capsule", "캡슐 배너", paint_capsule),
    ("banner", "라운드 배너", paint_rounded_banner),
    ("stripe", "주의 스트라이프", paint_warning_stripe),
    ("stamp", "스탬프", paint_stamp),
    ("arrow", "화살배너", paint_arrow_banner),
    ("tag", "태그", paint_tag),
    ("outline", "아웃라인", paint_outline),
    ("hex", "헥사", paint_hex),
    ("shield", "실드", paint_shield),
    ("ribbon", "리본 문구", paint_ribbon),
    ("ticket", "티켓", paint_ticket),
    ("double", "더블라인", paint_double_line),
    ("softpill", "소프트필", paint_softpill),
    ("boxmark", "박스마크", paint_box_mark),
]


def build_catalog() -> list[dict]:
    items: list[dict] = []
    n = 0
    while n < TARGET:
        style_id, style_name, painter = STYLES[n % len(STYLES)]
        phrase, pid = PHRASES[n % len(PHRASES)]
        fill, accent, textc, pname = PALETTES[n % len(PALETTES)]
        n += 1
        items.append({
            "n": n,
            "id": f"{style_id}_{pid}_{pname}",
            "title": f"{phrase} · {style_name}",
            "label": phrase,
            "tags": f"#물류용문구 #창고 #배송 #{style_id} #{pname} #{phrase}",
            "fill": fill,
            "accent": accent,
            "textc": textc,
            "painter": painter,
            "style_name": style_name,
        })
    return items


def render_item(item: dict) -> Image.Image:
    img = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    item["painter"](img, item["fill"], item["accent"], item["textc"], item["label"])
    return img.filter(ImageFilter.SMOOTH_MORE)


def main() -> int:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    MANIFEST.parent.mkdir(parents=True, exist_ok=True)
    catalog = build_catalog()
    manifest_items = []
    for item in catalog:
        n = item["n"]
        fname = f"hq_logiphrase_{n:03d}_{slugify(item['id'])[:40]}.png"
        path = OUT_DIR / fname
        render_item(item).save(path, "PNG", optimize=True)
        with Image.open(path) as im:
            assert im.mode == "RGBA"
            mn, _ = im.getchannel("A").getextrema()
            assert mn == 0, f"{fname} not transparent"
        rel = f"/assets/cliparts/{fname}"
        manifest_items.append({
            "title": item["title"],
            "category_slug": CATEGORY,
            "image_path": rel,
            "hashtags": item["tags"],
            "description": f"물류용 문구 클립아트 — {item['label']} ({item['style_name']})",
            "sort_order": 9200 + n,
        })
        print(f"[{n}/{TARGET}] {fname} · {item['label']}")

    MANIFEST.write_text(
        json.dumps({"items": manifest_items}, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    print(f"Wrote {len(manifest_items)} items → {MANIFEST}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
