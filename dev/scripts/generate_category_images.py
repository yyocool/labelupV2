#!/usr/bin/env python3
"""Generate photorealistic-style category product images on white background."""
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
OUT_DIR = ROOT / "public" / "assets" / "categories"
MANIFEST = ROOT / "storage" / "imports" / "categories_export.json"

SIZE = 1024
BG = (252, 252, 253)
CORE = (166, 124, 82)
CORE_DARK = (120, 86, 54)
PAPER = (255, 255, 255)
PAPER_EDGE = (228, 228, 234)
INK = (28, 28, 36)
SHADOW = (0, 0, 0, 55)


def load_font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    paths = [
        "C:/Windows/Fonts/malgunbd.ttf" if bold else "C:/Windows/Fonts/malgun.ttf",
        "C:/Windows/Fonts/arialbd.ttf" if bold else "C:/Windows/Fonts/arial.ttf",
        "/usr/share/fonts/truetype/nanum/NanumGothicBold.ttf" if bold else "/usr/share/fonts/truetype/nanum/NanumGothic.ttf",
    ]
    for path in paths:
        if Path(path).exists():
            return ImageFont.truetype(path, size=size)
    return ImageFont.load_default()


def new_canvas() -> Image.Image:
    return Image.new("RGBA", (SIZE, SIZE), BG + (255,))


def add_shadow(base: Image.Image, layer: Image.Image, xy: tuple[int, int], blur: int = 22, offset: tuple[int, int] = (0, 18)) -> None:
    alpha = layer.split()[3]
    shadow = Image.new("RGBA", base.size, (0, 0, 0, 0))
    shadow.paste((0, 0, 0, SHADOW[3]), (xy[0] + offset[0], xy[1] + offset[1]), alpha)
    shadow = shadow.filter(ImageFilter.GaussianBlur(blur))
    base.alpha_composite(shadow)
    base.alpha_composite(layer, xy)


def linear_gradient(size: tuple[int, int], top: tuple[int, int, int], bottom: tuple[int, int, int]) -> Image.Image:
    w, h = size
    img = Image.new("RGB", size)
    draw = ImageDraw.Draw(img)
    for y in range(h):
        t = y / max(h - 1, 1)
        color = tuple(int(top[i] * (1 - t) + bottom[i] * t) for i in range(3))
        draw.line([(0, y), (w, y)], fill=color)
    return img


def draw_roll_core(draw: ImageDraw.ImageDraw, cx: int, top: int, rx: int, ry: int) -> None:
    draw.ellipse((cx - rx, top - ry, cx + rx, top + ry), fill=CORE_DARK, outline=(95, 68, 42))
    draw.ellipse((cx - rx + 8, top - ry + 5, cx + rx - 8, top + ry - 5), fill=CORE, outline=(145, 105, 68))


def draw_barcode_block(draw: ImageDraw.ImageDraw, box: tuple[int, int, int, int]) -> None:
    x0, y0, x1, y1 = box
    x = x0
    while x < x1:
        w = 5 if (int(x) // 3) % 2 == 0 else 9
        draw.rectangle((x, y0, min(x + w, x1), y1), fill=INK)
        x += w + 2


def scene_thermal_roll(title: str, subtitle: str) -> Image.Image:
    base = new_canvas()
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    cx, top = SIZE // 2 + 20, 170
    w, h = 250, 560
    x0, y0, x1, y1 = cx - w // 2, top, cx + w // 2, top + h
    draw.rounded_rectangle((x0, y0, x1, y1), radius=18, fill=PAPER, outline=PAPER_EDGE, width=2)
    for shade in range(8):
        draw.line([(x1 - 8 - shade, y0 + 20), (x1 - 8 - shade, y1 - 20)], fill=(240, 240, 244), width=1)
    draw_roll_core(draw, cx, top + 8, 78, 24)
    label_box = (x0 + 34, top + 120, x1 - 34, top + 250)
    draw.rectangle(label_box, fill=(248, 248, 250), outline=(220, 220, 228))
    f1, f2 = load_font(28, True), load_font(20)
    draw.text((label_box[0] + 18, label_box[1] + 18), title, fill=INK, font=f1)
    draw.text((label_box[0] + 18, label_box[1] + 58), subtitle, fill=(90, 90, 102), font=f2)
    draw.text((label_box[0] + 18, label_box[1] + 92), "PREMIUM QUALITY", fill=(120, 120, 132), font=load_font(16))
    add_shadow(base, layer, (0, 0))
    return base.convert("RGB")


def scene_circular_roll() -> Image.Image:
    base = new_canvas()
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    cx, cy = SIZE // 2 - 10, 520
    rx, ry = 150, 46
    draw.ellipse((cx - rx, cy - ry, cx + rx, cy + ry), fill=(235, 235, 240), outline=PAPER_EDGE)
    draw.ellipse((cx - rx + 10, cy - ry + 8, cx + rx - 10, cy + ry - 8), fill=PAPER)
    for i in range(5):
        ox = cx - 95 + i * 42
        draw.ellipse((ox, cy - 28, ox + 36, cy + 10), fill=PAPER, outline=(214, 214, 222), width=2)
    draw_roll_core(draw, cx, cy - 180, 72, 22)
    body = (cx - 110, cy - 170, cx + 110, cy + 20)
    draw.rounded_rectangle(body, radius=16, fill=PAPER, outline=PAPER_EDGE, width=2)
    add_shadow(base, layer, (0, 0))
    return base.convert("RGB")


def scene_a4_sheets(cols: int = 2, rows: int = 5, accent: tuple[int, int, int] = PAPER, title: str = "A4 LABEL SHEET") -> Image.Image:
    base = new_canvas()
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)

    def sheet(x: int, y: int, angle_offset: int = 0) -> None:
        w, h = 300, 420
        box = (x, y, x + w, y + h)
        draw.rounded_rectangle(box, radius=10, fill=PAPER, outline=PAPER_EDGE, width=2)
        pad = 24
        inner = (x + pad, y + pad, x + w - pad, y + h - pad)
        cw = (inner[2] - inner[0]) / cols
        ch = (inner[3] - inner[1]) / rows
        for r in range(rows):
            for c in range(cols):
                lx0 = inner[0] + c * cw + 4
                ly0 = inner[1] + r * ch + 4
                lx1 = inner[0] + (c + 1) * cw - 4
                ly1 = inner[1] + (r + 1) * ch - 4
                draw.rounded_rectangle((lx0, ly0, lx1, ly1), radius=6, fill=accent, outline=(220, 220, 228))

    sheet(250, 190)
    sheet(470, 230)
    stack = (690, 360, 820, 520)
    for i in range(8):
        off = i * 3
        draw.rounded_rectangle((stack[0] + off, stack[1] - off, stack[2] + off, stack[3] - off), radius=8, fill=PAPER, outline=PAPER_EDGE)
    draw.text((260, 130), title, fill=INK, font=load_font(24, True))
    add_shadow(base, layer, (0, 0))
    return base.convert("RGB")


def scene_shipping_roll() -> Image.Image:
    base = new_canvas()
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    cx, top = SIZE // 2 + 30, 180
    w, h = 290, 560
    x0, y0, x1, y1 = cx - w // 2, top, cx + w // 2, top + h
    draw.rounded_rectangle((x0, y0, x1, y1), radius=18, fill=PAPER, outline=PAPER_EDGE, width=2)
    draw_roll_core(draw, cx, top + 8, 84, 24)
    lb = (x0 + 24, top + 90, x1 - 24, top + 360)
    draw.rectangle(lb, fill=PAPER, outline=(205, 205, 214), width=2)
    draw.text((lb[0] + 20, lb[1] + 16), "PRIORITY", fill=INK, font=load_font(34, True))
    draw.text((lb[0] + 20, lb[1] + 58), "SHIPPING LABEL", fill=(70, 70, 82), font=load_font(22, True))
    draw.rectangle((lb[0] + 20, lb[1] + 110, lb[2] - 20, lb[1] + 150), outline=(210, 210, 218))
    draw.text((lb[0] + 24, lb[1] + 118), "TO:", fill=INK, font=load_font(18, True))
    draw_barcode_block(draw, (lb[0] + 20, lb[1] + 250, lb[2] - 20, lb[1] + 310))
    add_shadow(base, layer, (0, 0))
    return base.convert("RGB")


def scene_barcode_sheet() -> Image.Image:
    base = new_canvas()
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    sheet = (250, 170, 760, 760)
    draw.rounded_rectangle(sheet, radius=12, fill=PAPER, outline=PAPER_EDGE, width=2)
    x, y = sheet[0] + 40, sheet[1] + 40
    for row in range(4):
        for col in range(2):
            bx0 = x + col * 220
            by0 = y + row * 145
            bx1, by1 = bx0 + 190, by0 + 110
            draw.rounded_rectangle((bx0, by0, bx1, by1), radius=8, fill=(248, 248, 250), outline=(220, 220, 228))
            draw_barcode_block(draw, (bx0 + 16, by0 + 52, bx1 - 16, by0 + 92))
            draw.text((bx0 + 16, by0 + 16), "123456789012", fill=INK, font=load_font(16))
    add_shadow(base, layer, (0, 0))
    return base.convert("RGB")


def scene_colored_sheets(colors: list[tuple[int, int, int]], title: str = "COLOR LABEL") -> Image.Image:
    base = new_canvas()
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    x = 250
    for i, color in enumerate(colors):
        y = 200 + i * 18
        draw.rounded_rectangle((x + i * 14, y, x + 360 + i * 14, y + 420), radius=10, fill=color, outline=(210, 210, 218))
    draw.text((250, 130), title, fill=INK, font=load_font(24, True))
    add_shadow(base, layer, (0, 0))
    return base.convert("RGB")


def scene_gloss_sheet() -> Image.Image:
    img = scene_a4_sheets(3, 4, (252, 252, 255), "GLOSS LABEL")
    overlay = Image.new("RGBA", img.size, (255, 255, 255, 0))
    draw = ImageDraw.Draw(overlay)
    draw.polygon([(420, 180), (760, 320), (700, 360), (360, 220)], fill=(255, 255, 255, 90))
    img = Image.alpha_composite(img.convert("RGBA"), overlay).convert("RGB")
    return img


def scene_clear_sheet() -> Image.Image:
    base = new_canvas()
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    sheet = (280, 180, 740, 780)
    draw.rounded_rectangle(sheet, radius=12, fill=(245, 248, 252), outline=(210, 220, 230), width=2)
    for r in range(4):
        for c in range(2):
            x0 = sheet[0] + 40 + c * 190
            y0 = sheet[1] + 40 + r * 160
            draw.rounded_rectangle((x0, y0, x0 + 160, y0 + 120), radius=8, fill=(255, 255, 255, 120), outline=(200, 215, 230, 180))
    add_shadow(base, layer, (0, 0))
    return base.convert("RGB")


def scene_kraft_sheet() -> Image.Image:
    return scene_a4_sheets(2, 4, (196, 164, 132), "KRAFT LABEL")


def scene_film_roll() -> Image.Image:
    base = new_canvas()
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    cx, cy = SIZE // 2, 500
    for i in range(28):
        rr = 190 - i * 5
        shade = 250 - i * 2
        draw.ellipse((cx - rr, cy - rr * 0.34, cx + rr, cy + rr * 0.34), outline=(shade, shade + 1, shade + 2))
    draw_roll_core(draw, cx, cy - 150, 70, 20)
    draw.rounded_rectangle((cx - 120, cy - 140, cx + 120, cy + 20), radius=14, fill=(248, 250, 252), outline=(220, 226, 234), width=2)
    add_shadow(base, layer, (0, 0))
    return base.convert("RGB")


def scene_packaging() -> Image.Image:
    base = new_canvas()
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    box = (300, 260, 720, 680)
    draw.rounded_rectangle(box, radius=8, fill=(238, 226, 210), outline=(210, 190, 168), width=2)
    draw.rectangle((box[0] + 120, box[1], box[0] + 180, box[3]), fill=(220, 205, 186))
    draw.rectangle((box[0], box[1] + 180, box[2], box[1] + 240), fill=(220, 205, 186))
    draw.rounded_rectangle((box[0] + 180, box[1] + 90, box[0] + 420, box[1] + 210), radius=6, fill=PAPER, outline=(220, 220, 228))
    draw.text((box[0] + 200, box[1] + 130), "PACKAGING", fill=INK, font=load_font(24, True))
    add_shadow(base, layer, (0, 0))
    return base.convert("RGB")


def scene_supplies() -> Image.Image:
    base = new_canvas()
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    cart = (330, 220, 690, 700)
    draw.rounded_rectangle(cart, radius=18, fill=(58, 58, 68), outline=(40, 40, 48), width=2)
    draw.rounded_rectangle((cart[0] + 40, cart[1] + 80, cart[2] - 40, cart[3] - 60), radius=10, fill=(88, 88, 98))
    draw.text((cart[0] + 120, cart[1] + 300), "TONER", fill=(230, 230, 235), font=load_font(34, True))
    draw.ellipse((cart[0] + 70, cart[1] + 30, cart[0] + 130, cart[1] + 70), fill=(120, 120, 130))
    add_shadow(base, layer, (0, 0))
    return base.convert("RGB")


def scene_waterproof() -> Image.Image:
    img = scene_a4_sheets(2, 3, (236, 248, 255), "WATERPROOF LABEL").convert("RGBA")
    draw = ImageDraw.Draw(img)
    for ox, oy in ((540, 260), (620, 420), (480, 520)):
        draw.ellipse((ox, oy, ox + 36, oy + 48), fill=(56, 189, 248, 180), outline=(14, 165, 233))
    return img.convert("RGB")


def scene_index_tabs() -> Image.Image:
    base = new_canvas()
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    colors = [(255, 220, 220), (255, 240, 200), (220, 240, 255), (230, 255, 220), (240, 220, 255)]
    for i, color in enumerate(colors):
        x = 280 + i * 70
        draw.rounded_rectangle((x, 220, x + 58, 720), radius=8, fill=color, outline=(210, 210, 218))
        draw.polygon([(x, 220), (x + 29, 180), (x + 58, 220)], fill=color, outline=(210, 210, 218))
        draw.text((x + 10, 760), f"{i + 1}", fill=INK, font=load_font(18, True))
    add_shadow(base, layer, (0, 0))
    return base.convert("RGB")


def scene_government() -> Image.Image:
    return scene_a4_sheets(1, 6, (252, 252, 252), "DOCUMENT LABEL")


def build_scene(slug: str, name: str) -> Image.Image:
    key = slug.lower()
    if "thermal" in key or "감열" in name:
        return scene_thermal_roll("THERMAL LABEL ROLL", "100mm x 150mm")
    if "barcode" in key or "바코드" in name:
        return scene_barcode_sheet()
    if "logistics" in key or "물류" in name:
        return scene_shipping_roll()
    if "address" in key or "주소" in name:
        return scene_a4_sheets(2, 4, PAPER, "ADDRESS LABEL")
    if "index" in key or "인덱스" in name:
        return scene_index_tabs()
    if "round" in key or "원형" in name:
        return scene_circular_roll()
    if "waterproof" in key or "방수" in name:
        return scene_waterproof()
    if "gloss" in key or "광택" in name:
        return scene_gloss_sheet()
    if "translucent" in key or "반투명" in name:
        return scene_clear_sheet()
    if "clear" in key or "투명" in name:
        return scene_clear_sheet()
    if "kraft" in key or "크라프트" in name:
        return scene_kraft_sheet()
    if "film" in key or "필름" in name:
        return scene_film_roll()
    if "packaging" in key or "포장" in name or "봉투" in name:
        return scene_packaging()
    if "supplies" in key or "소모품" in name or "프린터" in name:
        return scene_supplies()
    if "government" in key or "정부" in name:
        return scene_government()
    if "pastel" in key or "파스텔" in name:
        return scene_colored_sheets([(255, 214, 224), (214, 236, 255), (255, 244, 194), (214, 245, 214)], "PASTEL LABEL")
    if "color" in key or "컬러" in name:
        return scene_colored_sheets([(255, 120, 120), (255, 196, 84), (98, 196, 255), (140, 220, 140)], "COLOR LABEL")
    if "label-paper" in key or name == "라벨지":
        return scene_a4_sheets(2, 5, PAPER, "A4 LABEL SHEET")
    return scene_a4_sheets(2, 4, PAPER, name[:12].upper())


def main() -> int:
    manifest_path = Path(sys.argv[1]) if len(sys.argv) > 1 else MANIFEST
    if not manifest_path.exists():
        print(f"Manifest not found: {manifest_path}", file=sys.stderr)
        return 1

    categories = json.loads(manifest_path.read_text(encoding="utf-8"))
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    created = 0
    for row in categories:
        slug = str(row.get("slug") or "").strip()
        name = str(row.get("name") or slug).strip()
        if not slug:
            continue
        img = build_scene(slug, name)
        out = OUT_DIR / f"cat_{slug}.webp"
        img.save(out, format="WEBP", quality=92, method=6)
        png = OUT_DIR / f"cat_{slug}.png"
        img.save(png, format="PNG", optimize=True)
        created += 1
        print(f"created {out.name}")

    print(f"Generated {created} category images at {SIZE}px -> {OUT_DIR}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
