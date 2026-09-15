#!/usr/bin/env python3
"""Generate 100 high-quality LABI (라비) mascot cliparts — transparent PNG.

Matches LABELUP AI character sheet:
  glossy maroon rounded frame, peeled sticker corner, chevron antenna,
  white face screen, simple oval eyes, soft 3D shading.

Outputs:
  public/assets/cliparts/hq_labi_{NNN}_{id}.png
  storage/imports/clipart_labi_manifest.json
"""
from __future__ import annotations

import json
import math
from pathlib import Path

import numpy as np
from PIL import Image, ImageChops, ImageDraw, ImageFilter

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "cliparts"
MANIFEST = ROOT / "storage" / "imports" / "clipart_labi_manifest.json"
SIZE = 768
HI = 1536  # supersample
CATEGORY = "labi"

# Character sheet palette
MAROON = (135, 52, 72)       # #873448
MAROON_DARK = (95, 32, 48)
MAROON_LIGHT = (175, 85, 105)
FACE = (255, 255, 255)
PEEL = (245, 241, 236)       # #F5F1EC
EYE = (56, 51, 51)           # #383333
CREAM = (250, 245, 238)
ACCENT_GOLD = (240, 190, 70)
ACCENT_BLUE = (90, 160, 220)
ACCENT_GREEN = (90, 180, 130)
OUTLINE = (70, 28, 42)


def rgba(c, a=255):
    return (int(c[0]), int(c[1]), int(c[2]), int(a))


def mix(a, b, t):
    return (
        int(a[0] + (b[0] - a[0]) * t),
        int(a[1] + (b[1] - a[1]) * t),
        int(a[2] + (b[2] - a[2]) * t),
    )


def soft_shadow(layer: Image.Image, blur=18, alpha=70, dy=14) -> Image.Image:
    a = layer.split()[-1]
    sh = Image.new("RGBA", layer.size, (0, 0, 0, 0))
    sh.paste(Image.new("RGBA", layer.size, (30, 20, 25, alpha)), (8, dy), a)
    sh = sh.filter(ImageFilter.GaussianBlur(blur))
    out = Image.new("RGBA", layer.size, (0, 0, 0, 0))
    out.alpha_composite(sh)
    out.alpha_composite(layer)
    return out


def rounded_mask(w, h, r):
    m = Image.new("L", (w, h), 0)
    ImageDraw.Draw(m).rounded_rectangle([0, 0, w - 1, h - 1], radius=r, fill=255)
    return m


def glossy_body(w, h, r) -> Image.Image:
    """Maroon rounded body with soft 3D gloss."""
    yy, xx = np.mgrid[0:h, 0:w].astype(np.float32)
    nx = (xx / max(w - 1, 1)) * 2 - 1
    ny = (yy / max(h - 1, 1)) * 2 - 1
    # top-left highlight bias
    gloss = np.clip(1.0 - np.sqrt((nx + 0.35) ** 2 + (ny + 0.45) ** 2) * 0.85, 0, 1)
    shade = np.clip(np.sqrt((nx - 0.4) ** 2 + (ny - 0.55) ** 2) * 0.55, 0, 1)
    out = np.zeros((h, w, 4), dtype=np.float32)
    for i in range(3):
        base = MAROON[i] + (MAROON_LIGHT[i] - MAROON[i]) * gloss * 0.55
        base = base + (MAROON_DARK[i] - base) * shade * 0.55
        out[..., i] = np.clip(base, 0, 255)
    out[..., 3] = 255
    img = Image.fromarray(out.astype(np.uint8), "RGBA")
    mask = rounded_mask(w, h, r)
    img.putalpha(mask)
    # rim highlight
    rim = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    d = ImageDraw.Draw(rim)
    d.rounded_rectangle([3, 3, w - 4, h - 4], radius=max(4, r - 2), outline=rgba(mix(MAROON_LIGHT, (255, 255, 255), 0.35), 90), width=3)
    d.rounded_rectangle([0, 0, w - 1, h - 1], radius=r, outline=rgba(OUTLINE, 180), width=4)
    img = Image.alpha_composite(img, rim)
    return img


def draw_peel(d: ImageDraw.ImageDraw, x1, y1, curl=0.18):
    """Bottom-right peeled sticker corner."""
    bw = x1 - 40  # unused; use box coords from caller via globals in draw_labi
    pass


def draw_eyes(d, cx, cy, expr: str, s: float):
    edx = 0.085 * s
    ey = cy
    er = 0.038 * s
    w = max(4, int(s * 0.012))

    def oval(ex, rx=er, ry=er * 1.35):
        d.ellipse([ex - rx, ey - ry, ex + rx, ey + ry], fill=rgba(EYE))

    def happy(ex):
        d.arc([ex - er * 1.5, ey - er * 0.6, ex + er * 1.5, ey + er * 1.8], 210, 330, fill=rgba(EYE), width=w)

    def line_eye(ex):
        d.line([(ex - er * 1.2, ey), (ex + er * 1.2, ey)], fill=rgba(EYE), width=w)

    def look_up(ex):
        d.ellipse([ex - er * 0.85, ey - er * 1.5, ex + er * 0.85, ey + er * 0.4], fill=rgba(EYE))

    if expr == "happy":
        happy(cx - edx)
        happy(cx + edx)
    elif expr == "wink":
        oval(cx - edx)
        happy(cx + edx)
    elif expr == "surprise":
        oval(cx - edx, er * 1.15, er * 1.55)
        oval(cx + edx, er * 1.15, er * 1.55)
    elif expr == "think":
        look_up(cx - edx)
        look_up(cx + edx)
    elif expr == "sleepy":
        line_eye(cx - edx)
        line_eye(cx + edx)
    elif expr == "love":
        for side in (-1, 1):
            hx = cx + side * edx
            d.ellipse([hx - 0.028 * s, ey - 0.02 * s, hx - 0.002 * s, ey + 0.01 * s], fill=rgba(MAROON))
            d.ellipse([hx + 0.002 * s, ey - 0.02 * s, hx + 0.028 * s, ey + 0.01 * s], fill=rgba(MAROON))
            d.polygon([(hx - 0.032 * s, ey), (hx, ey + 0.04 * s), (hx + 0.032 * s, ey)], fill=rgba(MAROON))
    elif expr == "cry":
        oval(cx - edx)
        oval(cx + edx)
        for side in (-1, 1):
            d.ellipse([cx + side * edx - 0.012 * s, ey + 0.05 * s, cx + side * edx + 0.012 * s, ey + 0.09 * s], fill=rgba((140, 190, 240)))
    elif expr == "angry":
        d.line([(cx - edx - er, ey - er * 1.2), (cx - edx + er, ey - er * 0.5)], fill=rgba(EYE), width=w)
        d.line([(cx + edx - er, ey - er * 0.5), (cx + edx + er, ey - er * 1.2)], fill=rgba(EYE), width=w)
        oval(cx - edx, er * 0.9, er * 1.1)
        oval(cx + edx, er * 0.9, er * 1.1)
    elif expr == "shy":
        oval(cx - edx, er * 0.85, er * 1.1)
        oval(cx + edx, er * 0.85, er * 1.1)
        # blush
        d.ellipse([cx - edx - 0.05 * s, ey + 0.05 * s, cx - edx + 0.02 * s, ey + 0.09 * s], fill=rgba((255, 170, 180), 140))
        d.ellipse([cx + edx - 0.02 * s, ey + 0.05 * s, cx + edx + 0.05 * s, ey + 0.09 * s], fill=rgba((255, 170, 180), 140))
    elif expr == "dizzy":
        for side in (-1, 1):
            ex = cx + side * edx
            d.arc([ex - er, ey - er, ex + er, ey + er], 0, 270, fill=rgba(EYE), width=w)
    elif expr == "sparkle":
        oval(cx - edx)
        oval(cx + edx)
        for ang in (0, 90, 180, 270):
            rad = math.radians(ang)
            d.line([(cx + 0.16 * s * math.cos(rad), cy - 0.12 * s + 0.16 * s * math.sin(rad)),
                    (cx + 0.22 * s * math.cos(rad), cy - 0.12 * s + 0.22 * s * math.sin(rad))], fill=rgba(ACCENT_GOLD), width=3)
    else:  # normal
        oval(cx - edx)
        oval(cx + edx)


def draw_antenna(layer, cx, top_y, s, color=MAROON):
    d = ImageDraw.Draw(layer)
    # chevron / triangle
    tip = (cx, top_y - 0.07 * s)
    left = (cx - 0.055 * s, top_y + 0.01 * s)
    right = (cx + 0.055 * s, top_y + 0.01 * s)
    d.polygon([tip, left, right], fill=rgba(mix(color, (255, 255, 255), 0.12)))
    d.line([tip, left, right, tip], fill=rgba(OUTLINE), width=3)
    # soft highlight on left edge
    mid = (cx - 0.018 * s, top_y - 0.03 * s)
    d.line([tip, mid], fill=rgba(mix(color, (255, 255, 255), 0.4), 160), width=2)


def draw_motion(d, x, y, s, n=2):
    for i in range(n):
        yy = y + i * 0.035 * s
        d.line([(x, yy), (x + 0.06 * s, yy)], fill=rgba(MAROON, 200), width=max(3, int(s * 0.01)))


def prop_laptop(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    d.rounded_rectangle([x, y, x + 0.28 * s, y + 0.18 * s], radius=8, fill=rgba((70, 75, 85)), outline=rgba(OUTLINE), width=3)
    d.rounded_rectangle([x + 0.02 * s, y + 0.02 * s, x + 0.26 * s, y + 0.14 * s], radius=4, fill=rgba((180, 215, 240)))
    d.polygon([(x - 0.02 * s, y + 0.18 * s), (x + 0.30 * s, y + 0.18 * s), (x + 0.34 * s, y + 0.24 * s), (x - 0.06 * s, y + 0.24 * s)], fill=rgba((90, 95, 105)), outline=rgba(OUTLINE))


def prop_headset(layer, cx, cy, s):
    d = ImageDraw.Draw(layer)
    # band
    d.arc([cx - 0.22 * s, cy - 0.22 * s, cx + 0.22 * s, cy + 0.08 * s], 200, 340, fill=rgba(EYE), width=max(8, int(s * 0.025)))
    for side in (-1, 1):
        d.ellipse([cx + side * 0.20 * s - 0.04 * s, cy - 0.02 * s, cx + side * 0.20 * s + 0.04 * s, cy + 0.10 * s], fill=rgba(EYE), outline=rgba(OUTLINE), width=2)
    # mic
    d.line([(cx + 0.20 * s, cy + 0.05 * s), (cx + 0.12 * s, cy + 0.14 * s)], fill=rgba(EYE), width=4)
    d.ellipse([cx + 0.08 * s, cy + 0.12 * s, cx + 0.14 * s, cy + 0.18 * s], fill=rgba((120, 125, 135)))


def prop_bulb(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    d.ellipse([x, y, x + 0.14 * s, y + 0.16 * s], fill=rgba(ACCENT_GOLD), outline=rgba(OUTLINE), width=3)
    d.rectangle([x + 0.04 * s, y + 0.14 * s, x + 0.10 * s, y + 0.20 * s], fill=rgba((180, 185, 195)), outline=rgba(OUTLINE), width=2)
    for i in range(5):
        ang = math.radians(-90 + i * 45)
        d.line([(x + 0.07 * s + 0.09 * s * math.cos(ang), y + 0.07 * s + 0.09 * s * math.sin(ang)),
                (x + 0.07 * s + 0.13 * s * math.cos(ang), y + 0.07 * s + 0.13 * s * math.sin(ang))], fill=rgba(ACCENT_GOLD), width=3)


def prop_magnifier(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    d.ellipse([x, y, x + 0.16 * s, y + 0.16 * s], outline=rgba((90, 100, 120)), width=max(8, int(s * 0.022)))
    d.ellipse([x + 0.02 * s, y + 0.02 * s, x + 0.14 * s, y + 0.14 * s], fill=rgba((200, 230, 245), 160))
    d.line([(x + 0.13 * s, y + 0.13 * s), (x + 0.24 * s, y + 0.26 * s)], fill=rgba((90, 100, 120)), width=max(8, int(s * 0.02)))


def prop_labels(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    cols = [CREAM, (255, 230, 230), (230, 240, 255)]
    for i, c in enumerate(cols):
        ox, oy = i * 0.025 * s, i * 0.03 * s
        d.rounded_rectangle([x + ox, y + oy, x + 0.16 * s + ox, y + 0.10 * s + oy], radius=6, fill=rgba(c), outline=rgba(OUTLINE), width=2)
        d.line([(x + ox + 0.02 * s, y + oy + 0.035 * s), (x + ox + 0.12 * s, y + oy + 0.035 * s)], fill=rgba(MAROON, 120), width=2)


def prop_printer(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    d.rounded_rectangle([x, y, x + 0.26 * s, y + 0.16 * s], radius=10, fill=rgba((90, 95, 105)), outline=rgba(OUTLINE), width=3)
    d.rectangle([x + 0.04 * s, y + 0.05 * s, x + 0.22 * s, y + 0.10 * s], fill=rgba((50, 55, 65)))
    # paper coming out
    d.rounded_rectangle([x + 0.06 * s, y - 0.08 * s, x + 0.20 * s, y + 0.02 * s], radius=4, fill=rgba(FACE), outline=rgba(OUTLINE), width=2)


def prop_heart(layer, x, y, s, col=MAROON):
    d = ImageDraw.Draw(layer)
    d.ellipse([x, y, x + 0.07 * s, y + 0.07 * s], fill=rgba(col))
    d.ellipse([x + 0.05 * s, y, x + 0.12 * s, y + 0.07 * s], fill=rgba(col))
    d.polygon([(x, y + 0.04 * s), (x + 0.06 * s, y + 0.13 * s), (x + 0.12 * s, y + 0.04 * s)], fill=rgba(col))


def prop_coffee(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    d.rounded_rectangle([x, y, x + 0.12 * s, y + 0.14 * s], radius=6, fill=rgba(CREAM), outline=rgba(OUTLINE), width=3)
    d.rectangle([x + 0.015 * s, y + 0.02 * s, x + 0.105 * s, y + 0.06 * s], fill=rgba((140, 90, 50)))
    d.arc([x + 0.10 * s, y + 0.03 * s, x + 0.17 * s, y + 0.11 * s], -80, 80, fill=rgba(OUTLINE), width=3)


def prop_phone(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    d.rounded_rectangle([x, y, x + 0.10 * s, y + 0.18 * s], radius=8, fill=rgba((50, 55, 70)), outline=rgba(OUTLINE), width=3)
    d.rounded_rectangle([x + 0.012 * s, y + 0.02 * s, x + 0.088 * s, y + 0.14 * s], radius=4, fill=rgba((160, 210, 240)))


def prop_bag(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    d.rounded_rectangle([x, y + 0.04 * s, x + 0.16 * s, y + 0.20 * s], radius=8, fill=rgba(MAROON), outline=rgba(OUTLINE), width=3)
    d.arc([x + 0.02 * s, y, x + 0.14 * s, y + 0.10 * s], 200, 340, fill=rgba(OUTLINE), width=4)
    d.ellipse([x + 0.055 * s, y + 0.09 * s, x + 0.105 * s, y + 0.14 * s], fill=rgba(FACE))


def prop_star(layer, x, y, s, col=ACCENT_GOLD):
    d = ImageDraw.Draw(layer)
    pts = []
    for i in range(10):
        ang = math.radians(-90 + i * 36)
        rr = 0.07 * s if i % 2 == 0 else 0.03 * s
        pts.append((x + rr * math.cos(ang), y + rr * math.sin(ang)))
    d.polygon(pts, fill=rgba(col), outline=rgba(OUTLINE))


def prop_paint(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    d.polygon([(x, y + 0.16 * s), (x + 0.04 * s, y), (x + 0.08 * s, y + 0.16 * s)], fill=rgba((90, 160, 220)), outline=rgba(OUTLINE))
    d.rectangle([x + 0.02 * s, y + 0.14 * s, x + 0.06 * s, y + 0.22 * s], fill=rgba((180, 140, 90)), outline=rgba(OUTLINE), width=2)


def prop_scissors(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    d.ellipse([x, y, x + 0.06 * s, y + 0.06 * s], outline=rgba((90, 100, 120)), width=4)
    d.ellipse([x + 0.05 * s, y, x + 0.11 * s, y + 0.06 * s], outline=rgba((90, 100, 120)), width=4)
    d.line([(x + 0.03 * s, y + 0.05 * s), (x + 0.12 * s, y + 0.20 * s)], fill=rgba((140, 150, 160)), width=4)
    d.line([(x + 0.08 * s, y + 0.05 * s), (x - 0.01 * s, y + 0.20 * s)], fill=rgba((140, 150, 160)), width=4)


def prop_barcode(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    d.rounded_rectangle([x, y, x + 0.18 * s, y + 0.10 * s], radius=4, fill=rgba(FACE), outline=rgba(OUTLINE), width=2)
    for i, w in enumerate([3, 2, 4, 2, 3, 5, 2, 3, 2]):
        xx = x + 0.015 * s + i * 0.017 * s
        d.rectangle([xx, y + 0.015 * s, xx + w, y + 0.085 * s], fill=rgba(EYE))


def prop_gift(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    d.rounded_rectangle([x, y + 0.04 * s, x + 0.16 * s, y + 0.18 * s], radius=6, fill=rgba((220, 90, 110)), outline=rgba(OUTLINE), width=3)
    d.rectangle([x + 0.07 * s, y + 0.04 * s, x + 0.09 * s, y + 0.18 * s], fill=rgba(ACCENT_GOLD))
    d.rectangle([x, y + 0.09 * s, x + 0.16 * s, y + 0.11 * s], fill=rgba(ACCENT_GOLD))
    d.ellipse([x + 0.04 * s, y, x + 0.12 * s, y + 0.06 * s], fill=rgba(ACCENT_GOLD), outline=rgba(OUTLINE), width=2)


def prop_book(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    d.rounded_rectangle([x, y, x + 0.14 * s, y + 0.18 * s], radius=4, fill=rgba((70, 120, 180)), outline=rgba(OUTLINE), width=3)
    d.line([(x + 0.02 * s, y + 0.05 * s), (x + 0.12 * s, y + 0.05 * s)], fill=rgba(FACE, 180), width=2)
    d.line([(x + 0.02 * s, y + 0.09 * s), (x + 0.12 * s, y + 0.09 * s)], fill=rgba(FACE, 180), width=2)


def prop_camera(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    d.rounded_rectangle([x, y + 0.03 * s, x + 0.18 * s, y + 0.14 * s], radius=8, fill=rgba((60, 65, 75)), outline=rgba(OUTLINE), width=3)
    d.ellipse([x + 0.05 * s, y + 0.05 * s, x + 0.13 * s, y + 0.13 * s], fill=rgba((40, 45, 55)), outline=rgba((160, 170, 180)), width=3)
    d.rectangle([x + 0.12 * s, y, x + 0.16 * s, y + 0.04 * s], fill=rgba((90, 95, 105)), outline=rgba(OUTLINE), width=2)


def prop_speech(layer, x, y, s, dots=True):
    d = ImageDraw.Draw(layer)
    d.rounded_rectangle([x, y, x + 0.16 * s, y + 0.12 * s], radius=12, fill=rgba(FACE), outline=rgba(OUTLINE), width=3)
    d.polygon([(x + 0.04 * s, y + 0.12 * s), (x + 0.02 * s, y + 0.18 * s), (x + 0.08 * s, y + 0.12 * s)], fill=rgba(FACE), outline=rgba(OUTLINE))
    if dots:
        for i in range(3):
            d.ellipse([x + 0.035 * s + i * 0.04 * s, y + 0.04 * s, x + 0.055 * s + i * 0.04 * s, y + 0.06 * s], fill=rgba(EYE))


def prop_check(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    d.ellipse([x, y, x + 0.14 * s, y + 0.14 * s], fill=rgba(ACCENT_GREEN), outline=rgba(OUTLINE), width=3)
    d.line([(x + 0.03 * s, y + 0.07 * s), (x + 0.06 * s, y + 0.10 * s), (x + 0.11 * s, y + 0.04 * s)], fill=rgba(FACE), width=max(5, int(s * 0.015)))


def prop_zzz(layer, x, y, s):
    d = ImageDraw.Draw(layer)
    for i in range(3):
        zx = x + i * 0.04 * s
        zy = y - i * 0.05 * s
        w = max(2, int(s * 0.008))
        d.line([(zx, zy), (zx + 0.035 * s, zy)], fill=rgba(MAROON), width=w)
        d.line([(zx + 0.035 * s, zy), (zx, zy + 0.035 * s)], fill=rgba(MAROON), width=w)
        d.line([(zx, zy + 0.035 * s), (zx + 0.035 * s, zy + 0.035 * s)], fill=rgba(MAROON), width=w)


PROPS = {
    "laptop": prop_laptop,
    "headset": prop_headset,
    "bulb": prop_bulb,
    "magnifier": prop_magnifier,
    "labels": prop_labels,
    "printer": prop_printer,
    "heart": prop_heart,
    "coffee": prop_coffee,
    "phone": prop_phone,
    "bag": prop_bag,
    "star": prop_star,
    "paint": prop_paint,
    "scissors": prop_scissors,
    "barcode": prop_barcode,
    "gift": prop_gift,
    "book": prop_book,
    "camera": prop_camera,
    "speech": prop_speech,
    "check": prop_check,
    "zzz": prop_zzz,
}


def render_labi(
    expr: str = "normal",
    prop: str | None = None,
    tilt: float = 0,
    motion: bool = False,
    view: str = "front",  # front | back | side
    blush: bool = False,
) -> Image.Image:
    canvas = Image.new("RGBA", (HI, HI), (0, 0, 0, 0))
    s = HI * 0.72
    cx, cy = HI / 2, HI / 2 + 20

    body_w, body_h = int(s * 0.62), int(s * 0.72)
    r = int(s * 0.14)
    bx = int(cx - body_w / 2)
    by = int(cy - body_h / 2)

    body_layer = Image.new("RGBA", (HI, HI), (0, 0, 0, 0))

    if view == "back":
        body = glossy_body(body_w, body_h, r)
        body_layer.alpha_composite(body, (bx, by))
        draw_antenna(body_layer, cx, by, s)
        # subtle back panel lines
        d = ImageDraw.Draw(body_layer)
        d.rounded_rectangle([bx + 0.12 * s, by + 0.14 * s, bx + body_w - 0.12 * s, by + body_h - 0.14 * s],
                            radius=int(s * 0.06), outline=rgba(mix(MAROON, (0, 0, 0), 0.25), 100), width=3)
    else:
        body = glossy_body(body_w, body_h, r)
        # punch bottom-right peel cutout on body tile first
        peel_size = int(s * 0.18)
        punch = Image.new("L", (body_w, body_h), 255)
        ImageDraw.Draw(punch).polygon(
            [(body_w - peel_size, body_h), (body_w, body_h - peel_size), (body_w, body_h)],
            fill=0,
        )
        from PIL import ImageChops
        from PIL import ImageChops
        br, bg, bb, ba = body.split()
        body = Image.merge("RGBA", (br, bg, bb, ImageChops.multiply(ba, punch)))
        body_layer.alpha_composite(body, (bx, by))

        d = ImageDraw.Draw(body_layer)
        px1, py1 = bx + body_w, by + body_h
        # cream underside curl
        d.polygon([
            (px1 - peel_size, py1),
            (px1, py1 - peel_size),
            (px1 - int(0.02 * s), py1 - peel_size - int(0.09 * s)),
            (px1 - peel_size - int(0.10 * s), py1 - int(0.02 * s)),
        ], fill=rgba(PEEL), outline=rgba(OUTLINE), width=3)
        d.polygon([
            (px1 - peel_size, py1),
            (px1, py1 - peel_size),
            (px1 - int(0.07 * s), py1 - int(0.07 * s)),
        ], fill=rgba(mix(PEEL, (255, 255, 255), 0.35)))

        # face screen
        fx0 = bx + int(0.10 * s)
        fy0 = by + int(0.12 * s)
        fx1 = bx + body_w - int(0.10 * s)
        fy1 = by + body_h - int(0.18 * s)
        d.rounded_rectangle([fx0 + 4, fy0 + 6, fx1 + 2, fy1 + 4], radius=int(s * 0.08), fill=rgba(mix(MAROON_DARK, (0, 0, 0), 0.2), 80))
        d.rounded_rectangle([fx0, fy0, fx1, fy1], radius=int(s * 0.08), fill=rgba(FACE), outline=rgba(mix(MAROON, (0, 0, 0), 0.15), 40), width=2)

        face_cx = (fx0 + fx1) / 2
        face_cy = (fy0 + fy1) / 2 - 0.01 * s
        if blush or expr == "shy":
            d.ellipse([face_cx - 0.14 * s, face_cy + 0.06 * s, face_cx - 0.05 * s, face_cy + 0.11 * s], fill=rgba((255, 170, 180), 110))
            d.ellipse([face_cx + 0.05 * s, face_cy + 0.06 * s, face_cx + 0.14 * s, face_cy + 0.11 * s], fill=rgba((255, 170, 180), 110))
        draw_eyes(d, face_cx, face_cy, expr, s)

        draw_antenna(body_layer, cx, by, s)

    # props
    if prop == "headset":
        PROPS["headset"](body_layer, cx, by + 0.18 * s, s)
    elif prop and prop in PROPS:
        if prop in ("laptop", "printer"):
            PROPS[prop](body_layer, cx + 0.18 * s, cy + 0.05 * s, s)
        elif prop == "zzz":
            PROPS[prop](body_layer, cx + 0.22 * s, by + 0.08 * s, s)
        elif prop == "speech":
            PROPS[prop](body_layer, cx + 0.28 * s, by + 0.05 * s, s)
        elif prop == "magnifier":
            PROPS[prop](body_layer, cx + 0.22 * s, cy - 0.05 * s, s)
        elif prop == "bulb":
            PROPS[prop](body_layer, cx + 0.28 * s, by + 0.02 * s, s)
        else:
            PROPS[prop](body_layer, cx + 0.26 * s, cy - 0.02 * s, s)

    if motion:
        d2 = ImageDraw.Draw(body_layer)
        draw_motion(d2, bx + body_w + 0.02 * s, by + 0.08 * s, s)

    if tilt:
        body_layer = body_layer.rotate(tilt, resample=Image.BICUBIC, center=(cx, cy))

    canvas.alpha_composite(body_layer)
    out = soft_shadow(canvas).resize((SIZE, SIZE), Image.LANCZOS)
    return out.filter(ImageFilter.SMOOTH)


# 100 catalog entries: (id, title, kwargs)
ITEMS: list[tuple[str, str, dict]] = [
    ("normal", "기본 표정", {"expr": "normal"}),
    ("happy", "방긋 미소", {"expr": "happy", "motion": True}),
    ("wink", "윙크", {"expr": "wink", "motion": True}),
    ("surprise", "깜짝", {"expr": "surprise"}),
    ("think", "생각중", {"expr": "think", "prop": "speech"}),
    ("sleepy", "졸린", {"expr": "sleepy"}),
    ("love", "하트눈", {"expr": "love", "prop": "heart"}),
    ("cry", "울먹", {"expr": "cry"}),
    ("angry", "화남", {"expr": "angry"}),
    ("shy", "수줍", {"expr": "shy", "blush": True}),
    ("dizzy", "어질어질", {"expr": "dizzy"}),
    ("sparkle", "반짝", {"expr": "sparkle", "motion": True}),
    ("hello", "안녕", {"expr": "happy", "motion": True, "tilt": -6}),
    ("wave", "손인사", {"expr": "wink", "motion": True, "tilt": 8}),
    ("cheer", "만세", {"expr": "happy", "motion": True, "tilt": -4}),
    ("jump", "폴짝", {"expr": "happy", "motion": True, "tilt": 5}),
    ("dance", "댄스", {"expr": "happy", "tilt": -12, "motion": True}),
    ("tilt_left", "기울기 왼쪽", {"expr": "normal", "tilt": -15}),
    ("tilt_right", "기울기 오른쪽", {"expr": "normal", "tilt": 15}),
    ("back", "뒷모습", {"view": "back"}),
    ("laptop", "노트북", {"expr": "normal", "prop": "laptop"}),
    ("coding", "코딩중", {"expr": "think", "prop": "laptop"}),
    ("headset", "헤드셋", {"expr": "normal", "prop": "headset"}),
    ("cs", "상담중", {"expr": "happy", "prop": "headset"}),
    ("bulb", "아이디어", {"expr": "surprise", "prop": "bulb"}),
    ("idea_happy", "번쩍 아이디어", {"expr": "happy", "prop": "bulb", "motion": True}),
    ("search", "검색", {"expr": "think", "prop": "magnifier"}),
    ("inspect", "꼼꼼 확인", {"expr": "normal", "prop": "magnifier"}),
    ("labels", "라벨 뭉치", {"expr": "happy", "prop": "labels"}),
    ("print", "출력중", {"expr": "normal", "prop": "printer"}),
    ("print_happy", "출력 완료", {"expr": "happy", "prop": "printer", "motion": True}),
    ("coffee", "커피타임", {"expr": "happy", "prop": "coffee"}),
    ("phone", "전화중", {"expr": "normal", "prop": "phone"}),
    ("call_wink", "전화 윙크", {"expr": "wink", "prop": "phone"}),
    ("bag", "쇼핑백", {"expr": "happy", "prop": "bag"}),
    ("shop", "쇼핑", {"expr": "wink", "prop": "bag", "motion": True}),
    ("star", "별점", {"expr": "happy", "prop": "star"}),
    ("star_love", "별사랑", {"expr": "love", "prop": "star"}),
    ("paint", "페인팅", {"expr": "happy", "prop": "paint"}),
    ("design", "디자인중", {"expr": "think", "prop": "paint"}),
    ("scissors", "가위질", {"expr": "normal", "prop": "scissors"}),
    ("cut", "재단", {"expr": "wink", "prop": "scissors"}),
    ("barcode", "바코드", {"expr": "normal", "prop": "barcode"}),
    ("scan", "스캔", {"expr": "think", "prop": "barcode"}),
    ("gift", "선물", {"expr": "love", "prop": "gift"}),
    ("gift_surprise", "깜짝선물", {"expr": "surprise", "prop": "gift"}),
    ("book", "독서", {"expr": "think", "prop": "book"}),
    ("study", "공부", {"expr": "normal", "prop": "book"}),
    ("camera", "촬영", {"expr": "happy", "prop": "camera"}),
    ("selfie", "셀카", {"expr": "wink", "prop": "camera", "motion": True}),
    ("ok", "OK", {"expr": "happy", "prop": "check"}),
    ("done", "완료", {"expr": "wink", "prop": "check", "motion": True}),
    ("speech", "말풍선", {"expr": "normal", "prop": "speech"}),
    ("chat", "수다", {"expr": "happy", "prop": "speech", "motion": True}),
    ("sleep", "쿨쿨", {"expr": "sleepy", "prop": "zzz", "tilt": -10}),
    ("nap", "낮잠", {"expr": "sleepy", "prop": "zzz"}),
    ("heart", "하트", {"expr": "happy", "prop": "heart"}),
    ("heart_shy", "두근", {"expr": "shy", "prop": "heart", "blush": True}),
    ("blush", "발그레", {"expr": "shy", "blush": True, "tilt": -5}),
    ("proud", "뿌듯", {"expr": "happy", "tilt": -8, "motion": True}),
    ("curious", "궁금", {"expr": "think", "tilt": 8}),
    ("focus", "집중", {"expr": "think", "prop": "magnifier", "tilt": -4}),
    ("ai_work", "AI 작업", {"expr": "normal", "prop": "laptop", "motion": True}),
    ("support", "고객지원", {"expr": "happy", "prop": "headset", "motion": True}),
    ("inspire", "영감", {"expr": "sparkle", "prop": "bulb"}),
    ("ship_label", "배송라벨", {"expr": "normal", "prop": "labels", "tilt": 6}),
    ("pack", "포장", {"expr": "happy", "prop": "gift"}),
    ("celebrate", "축하", {"expr": "happy", "prop": "star", "motion": True, "tilt": -10}),
    ("party", "파티", {"expr": "sparkle", "prop": "star", "motion": True}),
    ("coffee_think", "커피 생각", {"expr": "think", "prop": "coffee"}),
    ("phone_surprise", "전화 놀람", {"expr": "surprise", "prop": "phone"}),
    ("bag_love", "쇼핑 러브", {"expr": "love", "prop": "bag"}),
    ("paint_shy", "그림 수줍", {"expr": "shy", "prop": "paint", "blush": True}),
    ("book_sleep", "책보다 졸림", {"expr": "sleepy", "prop": "book"}),
    ("camera_surprise", "찰칵 놀람", {"expr": "surprise", "prop": "camera"}),
    ("check_proud", "검수 OK", {"expr": "happy", "prop": "check", "tilt": -6}),
    ("speech_think", "음…", {"expr": "think", "prop": "speech"}),
    ("back_motion", "뒤돌아 신남", {"view": "back", "motion": True}),
    ("side_tilt", "살짝 옆", {"expr": "wink", "tilt": 22}),
    ("big_tilt", "크게 기울기", {"expr": "happy", "tilt": -22, "motion": True}),
    ("cry_comfort", "위로하트", {"expr": "cry", "prop": "heart"}),
    ("angry_work", "열받 작업", {"expr": "angry", "prop": "laptop"}),
    ("dizzy_spin", "빙글", {"expr": "dizzy", "motion": True, "tilt": 10}),
    ("sparkle_star", "반짝별", {"expr": "sparkle", "prop": "star"}),
    ("labels_wink", "라벨 윙크", {"expr": "wink", "prop": "labels"}),
    ("print_think", "출력 고민", {"expr": "think", "prop": "printer"}),
    ("scissors_happy", "재단 신남", {"expr": "happy", "prop": "scissors", "motion": True}),
    ("barcode_ok", "바코드 OK", {"expr": "happy", "prop": "barcode"}),
    ("gift_shy", "선물 수줍", {"expr": "shy", "prop": "gift", "blush": True}),
    ("book_love", "책 사랑", {"expr": "love", "prop": "book"}),
    ("coffee_wink", "커피 윙크", {"expr": "wink", "prop": "coffee"}),
    ("phone_love", "전화 하트", {"expr": "love", "prop": "phone"}),
    ("magnifier_surprise", "발견!", {"expr": "surprise", "prop": "magnifier", "motion": True}),
    ("bulb_think", "아이디어 구상", {"expr": "think", "prop": "bulb"}),
    ("headset_sleepy", "상담 졸림", {"expr": "sleepy", "prop": "headset"}),
    ("laptop_happy", "작업 완료", {"expr": "happy", "prop": "laptop", "motion": True}),
    ("bag_surprise", "득템", {"expr": "surprise", "prop": "bag", "motion": True}),
    ("camera_love", "예쁜 사진", {"expr": "love", "prop": "camera"}),
    ("check_wink", "합격 윙크", {"expr": "wink", "prop": "check"}),
    ("zzz_happy", "꿀잠 미소", {"expr": "happy", "prop": "zzz", "tilt": -8}),
    ("heart_sparkle", "반짝하트", {"expr": "sparkle", "prop": "heart", "motion": True}),
    ("paint_sparkle", "예술혼", {"expr": "sparkle", "prop": "paint"}),
    ("normal_motion", "살짝 흔들", {"expr": "normal", "motion": True}),
    ("happy_blush", "수줍 미소", {"expr": "happy", "blush": True}),
]


def main() -> int:
    assert len(ITEMS) >= 100, f"need 100 items, got {len(ITEMS)}"
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    MANIFEST.parent.mkdir(parents=True, exist_ok=True)
    items = []
    for i, (pid, title, kw) in enumerate(ITEMS[:100], 1):
        # filter unknown kwargs
        allowed = {"expr", "prop", "tilt", "motion", "view", "blush"}
        clean = {k: v for k, v in kw.items() if k in allowed}
        if "expr" not in clean and clean.get("view") != "back":
            clean.setdefault("expr", "normal")
        fname = f"hq_labi_{i:03d}_{pid}.png"
        path = OUT_DIR / fname
        img = render_labi(**clean)
        img.save(path, "PNG", optimize=True)
        with Image.open(path) as im:
            assert im.mode == "RGBA"
            assert im.getchannel("A").getextrema()[0] == 0, f"{fname} opaque bg"
        items.append({
            "title": f"라비 · {title}",
            "category_slug": CATEGORY,
            "image_path": f"/assets/cliparts/{fname}",
            "hashtags": f"#라비 #LABI #캐릭터 #마스코트 #라벨업 #클립아트 #{pid}",
            "description": f"라벨업 AI 마스코트 라비 — {title}",
            "sort_order": 9600 + i,
        })
        print(f"[{i}/100] {fname} · {title}", flush=True)

    MANIFEST.write_text(json.dumps({"items": items}, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Wrote {len(items)} → {MANIFEST}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
