#!/usr/bin/env python3
"""High-quality transparent illustration drawers for sticker pack 60."""
from __future__ import annotations

import math
from typing import Callable

import numpy as np
from PIL import Image, ImageChops, ImageDraw, ImageFilter


def rgba(c, a=255):
    return (int(c[0]), int(c[1]), int(c[2]), int(a))


def mix(a, b, t):
    return (
        int(a[0] + (b[0] - a[0]) * t),
        int(a[1] + (b[1] - a[1]) * t),
        int(a[2] + (b[2] - a[2]) * t),
    )


def soft_ellipse(draw, box, fill, outline=None, width=2):
    draw.ellipse(box, fill=fill, outline=outline, width=width)


def soft_shadow(base: Image.Image, blur=10, alpha=70, dy=6) -> Image.Image:
    sh = Image.new("RGBA", base.size, (0, 0, 0, 0))
    alpha_ch = base.split()[-1].point(lambda p: int(p * alpha / 255))
    sh.paste((0, 0, 0, 255), (0, dy), alpha_ch)
    sh = sh.filter(ImageFilter.GaussianBlur(blur))
    out = Image.new("RGBA", base.size, (0, 0, 0, 0))
    out.alpha_composite(sh)
    out.alpha_composite(base)
    return out


def canvas(size: int) -> Image.Image:
    return Image.new("RGBA", (size, size), (0, 0, 0, 0))


def smile(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    m = int(size * 0.08)
    soft_ellipse(d, [m, m, size - m, size - m], rgba((255, 214, 70)), rgba((230, 170, 20)), max(2, size // 90))
    eye_y = int(size * 0.38)
    er = max(4, size // 28)
    for ex in (0.35, 0.65):
        soft_ellipse(d, [int(size * ex) - er, eye_y - er, int(size * ex) + er, eye_y + er], rgba((55, 40, 30)))
    # smile arc
    box = [int(size * 0.28), int(size * 0.42), int(size * 0.72), int(size * 0.78)]
    d.arc(box, 20, 160, fill=rgba((55, 40, 30)), width=max(3, size // 45))
    return soft_shadow(im, 8, 60, 4)


def heart(size: int, color=(232, 74, 95)) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2 + size * 0.04
    s = size * 0.38
    pts = []
    for t in np.linspace(0, 2 * math.pi, 180):
        x = 16 * math.sin(t) ** 3
        y = -(13 * math.cos(t) - 5 * math.cos(2 * t) - 2 * math.cos(3 * t) - math.cos(4 * t))
        pts.append((cx + x * s / 16, cy + y * s / 16))
    d.polygon(pts, fill=rgba(color))
    # highlight
    d.ellipse([cx - s * 0.35, cy - s * 0.55, cx - s * 0.05, cy - s * 0.25], fill=rgba((255, 255, 255), 90))
    return soft_shadow(im, 10, 70, 5)


def leaf(size: int, color=(76, 140, 74)) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    pts = []
    for t in np.linspace(0, 2 * math.pi, 120):
        r = size * 0.34 * (0.55 + 0.45 * abs(math.sin(t)))
        # leaf-ish teardrop
        ang = t - math.pi / 2
        rr = size * 0.36 * (0.35 + 0.65 * ((math.cos(t) + 1) / 2) ** 0.7)
        if math.cos(t) < 0:
            rr *= 0.55
        pts.append((cx + rr * math.sin(ang), cy - rr * math.cos(ang) * 1.15))
    d.polygon(pts, fill=rgba(color))
    d.line([(cx, cy + size * 0.32), (cx, cy - size * 0.28)], fill=rgba(mix(color, (20, 40, 20), 0.35)), width=max(2, size // 80))
    return soft_shadow(im, 8, 55, 4)


def olive_branch(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    d.line([(cx - size * 0.28, cy + size * 0.08), (cx + size * 0.3, cy - size * 0.12)], fill=rgba((90, 120, 55)), width=max(3, size // 70))
    for i, (ox, oy, sc) in enumerate([(-0.18, 0.02, 0.9), (-0.02, -0.06, 1.0), (0.14, -0.12, 0.85)]):
        ox, oy = cx + size * ox, cy + size * oy
        r = size * 0.09 * sc
        soft_ellipse(d, [ox - r * 0.7, oy - r, ox + r * 0.7, oy + r], rgba((70, 130, 55)))
        soft_ellipse(d, [ox + r * 0.9, oy - r * 0.2, ox + r * 2.2, oy + r * 1.0], rgba((110, 150, 50), 210))
    for i in range(3):
        ox = cx + size * (0.02 + i * 0.08)
        oy = cy + size * (0.02 - i * 0.05)
        soft_ellipse(d, [ox, oy, ox + size * 0.08, oy + size * 0.1], rgba((90, 145, 45)))
    return soft_shadow(im, 9, 60, 4)


def coffee_beans(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    beans = [(0.32, 0.42, -25), (0.55, 0.38, 18), (0.44, 0.58, 5)]
    for bx, by, rot in beans:
        layer = canvas(size)
        ld = ImageDraw.Draw(layer)
        cx, cy = size * bx, size * by
        rx, ry = size * 0.14, size * 0.1
        soft_ellipse(ld, [cx - rx, cy - ry, cx + rx, cy + ry], rgba((92, 52, 28)), rgba((55, 28, 12)), 2)
        ld.arc([cx - rx * 0.35, cy - ry * 0.7, cx + rx * 0.35, cy + ry * 0.7], 200, 340, fill=rgba((55, 28, 12)), width=max(2, size // 90))
        layer = layer.rotate(rot, resample=Image.BICUBIC, center=(cx, cy))
        im.alpha_composite(layer)
    return soft_shadow(im, 8, 55, 4)


def bee(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    # wings
    soft_ellipse(d, [cx - size * 0.32, cy - size * 0.28, cx - size * 0.02, cy + size * 0.02], rgba((210, 235, 255), 180))
    soft_ellipse(d, [cx + size * 0.02, cy - size * 0.28, cx + size * 0.32, cy + size * 0.02], rgba((210, 235, 255), 180))
    soft_ellipse(d, [cx - size * 0.2, cy - size * 0.12, cx + size * 0.2, cy + size * 0.2], rgba((245, 200, 55)))
    for i, y in enumerate([0.0, 0.08]):
        d.rectangle([cx - size * 0.18, cy + size * (y - 0.02), cx + size * 0.18, cy + size * (y + 0.04)], fill=rgba((45, 35, 25)))
    soft_ellipse(d, [cx - size * 0.08, cy - size * 0.2, cx + size * 0.08, cy - size * 0.02], rgba((45, 35, 25)))
    return soft_shadow(im, 8, 55, 4)


def strawberry(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2 + size * 0.04
    body = []
    for t in np.linspace(0, 2 * math.pi, 80):
        rx = size * 0.22 * (1.0 if math.sin(t) > 0 else 0.85)
        ry = size * 0.26
        body.append((cx + rx * math.sin(t), cy + ry * math.cos(t) * 0.95 + size * 0.02))
    d.polygon(body, fill=rgba((220, 55, 70)))
    # seeds
    for sx, sy in [(-0.08, -0.05), (0.0, 0.02), (0.09, -0.04), (-0.05, 0.1), (0.06, 0.1), (0.0, -0.12)]:
        soft_ellipse(d, [cx + size * sx - 3, cy + size * sy - 5, cx + size * sx + 3, cy + size * sy + 5], rgba((255, 210, 80)))
    # leaves
    for ang in (-35, 0, 35):
        lx = cx + math.sin(math.radians(ang)) * size * 0.02
        ly = cy - size * 0.28
        d.polygon([(lx, ly), (lx - size * 0.08, ly - size * 0.12), (lx + size * 0.08, ly - size * 0.12)], fill=rgba((70, 145, 60)))
    return soft_shadow(im, 9, 60, 4)


def croissant(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    for i, (sc, col) in enumerate([(1.0, (210, 150, 70)), (0.78, (235, 185, 110)), (0.55, (245, 210, 150))]):
        box = [cx - size * 0.32 * sc, cy - size * 0.18 * sc, cx + size * 0.32 * sc, cy + size * 0.22 * sc]
        d.arc(box, 200, 340, fill=rgba(col), width=max(8, int(size * 0.06 * sc)))
    return soft_shadow(im, 8, 55, 4)


def cake(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx = size / 2
    # tiers
    d.rounded_rectangle([cx - size * 0.28, size * 0.52, cx + size * 0.28, size * 0.72], radius=8, fill=rgba((255, 180, 200)))
    d.rounded_rectangle([cx - size * 0.22, size * 0.38, cx + size * 0.22, size * 0.54], radius=8, fill=rgba((255, 150, 180)))
    d.rounded_rectangle([cx - size * 0.15, size * 0.26, cx + size * 0.15, size * 0.4], radius=6, fill=rgba((255, 210, 220)))
    for i, x in enumerate([0.38, 0.5, 0.62]):
        d.rectangle([size * x - 2, size * 0.14, size * x + 2, size * 0.28], fill=rgba((255, 230, 150)))
        soft_ellipse(d, [size * x - 5, size * 0.1, size * x + 5, size * 0.18], rgba((255, 120, 90)))
    return soft_shadow(im, 9, 60, 4)


def bow(size: int, color=(230, 90, 130)) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    d.polygon([(cx, cy), (cx - size * 0.32, cy - size * 0.18), (cx - size * 0.32, cy + size * 0.18)], fill=rgba(color))
    d.polygon([(cx, cy), (cx + size * 0.32, cy - size * 0.18), (cx + size * 0.32, cy + size * 0.18)], fill=rgba(color))
    soft_ellipse(d, [cx - size * 0.08, cy - size * 0.08, cx + size * 0.08, cy + size * 0.08], rgba(mix(color, (255, 255, 255), 0.2)))
    d.polygon([(cx - size * 0.06, cy + size * 0.06), (cx - size * 0.14, cy + size * 0.28), (cx, cy + size * 0.12)], fill=rgba(mix(color, (80, 20, 40), 0.2)))
    d.polygon([(cx + size * 0.06, cy + size * 0.06), (cx + size * 0.14, cy + size * 0.28), (cx, cy + size * 0.12)], fill=rgba(mix(color, (80, 20, 40), 0.2)))
    return soft_shadow(im, 8, 55, 4)


def clover(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2 + size * 0.02
    r = size * 0.14
    for ang in (45, 135, 225, 315):
        lx = cx + math.cos(math.radians(ang)) * r * 0.95
        ly = cy + math.sin(math.radians(ang)) * r * 0.95
        soft_ellipse(d, [lx - r, ly - r, lx + r, ly + r], rgba((70, 160, 80)))
    d.line([(cx, cy + r * 0.6), (cx, cy + size * 0.32)], fill=rgba((50, 120, 55)), width=max(3, size // 70))
    return soft_shadow(im, 8, 55, 4)


def tree_xmas(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx = size / 2
    for i, (top, bot, half) in enumerate([(0.18, 0.4, 0.16), (0.32, 0.58, 0.24), (0.48, 0.76, 0.32)]):
        d.polygon([(cx, size * top), (cx - size * half, size * bot), (cx + size * half, size * bot)], fill=rgba((240, 248, 255)))
    d.rectangle([cx - size * 0.04, size * 0.74, cx + size * 0.04, size * 0.86], fill=rgba((210, 220, 230)))
    soft_ellipse(d, [cx - 6, size * 0.12, cx + 6, size * 0.2], fill=rgba((255, 230, 120)))
    return soft_shadow(im, 8, 50, 3)


def pumpkin(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2 + size * 0.05
    soft_ellipse(d, [cx - size * 0.28, cy - size * 0.2, cx + size * 0.28, cy + size * 0.24], rgba((240, 130, 40)))
    soft_ellipse(d, [cx - size * 0.18, cy - size * 0.22, cx + size * 0.18, cy + size * 0.22], rgba((255, 155, 55)))
    d.rectangle([cx - 4, cy - size * 0.32, cx + 4, cy - size * 0.18], fill=rgba((70, 120, 50)))
    # face
    d.polygon([(cx - size * 0.12, cy - size * 0.04), (cx - size * 0.04, cy + size * 0.02), (cx - size * 0.14, cy + size * 0.02)], fill=rgba((40, 25, 15)))
    d.polygon([(cx + size * 0.12, cy - size * 0.04), (cx + size * 0.04, cy + size * 0.02), (cx + size * 0.14, cy + size * 0.02)], fill=rgba((40, 25, 15)))
    d.polygon([(cx - size * 0.08, cy + size * 0.08), (cx + size * 0.08, cy + size * 0.08), (cx, cy + size * 0.16)], fill=rgba((40, 25, 15)))
    return soft_shadow(im, 9, 60, 4)


def apple(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2 + size * 0.04
    soft_ellipse(d, [cx - size * 0.24, cy - size * 0.22, cx + size * 0.24, cy + size * 0.26], rgba((210, 45, 45)))
    soft_ellipse(d, [cx - size * 0.12, cy - size * 0.18, cx + size * 0.02, cy - size * 0.02], rgba((255, 255, 255), 70))
    d.rectangle([cx - 2, cy - size * 0.32, cx + 2, cy - size * 0.18], fill=rgba((90, 55, 30)))
    soft_ellipse(d, [cx + 2, cy - size * 0.34, cx + size * 0.14, cy - size * 0.2], rgba((70, 145, 55)))
    return soft_shadow(im, 8, 55, 4)


def tomato(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    for i, (ox, oy, sc) in enumerate([(-0.12, 0.06, 0.9), (0.12, 0.04, 0.95), (0.0, -0.06, 1.0)]):
        cx, cy = size * (0.5 + ox), size * (0.52 + oy)
        r = size * 0.14 * sc
        soft_ellipse(d, [cx - r, cy - r, cx + r, cy + r], rgba((220, 55, 45)))
    d.line([(size * 0.35, size * 0.4), (size * 0.65, size * 0.36)], fill=rgba((70, 130, 50)), width=max(3, size // 80))
    return soft_shadow(im, 8, 55, 4)


def veggies(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    for ang, col in [( -20, (60, 140, 70)), (10, (90, 170, 80)), (40, (50, 120, 60))]:
        layer = canvas(size)
        ld = ImageDraw.Draw(layer)
        soft_ellipse(ld, [cx - size * 0.08, cy - size * 0.28, cx + size * 0.08, cy + size * 0.2], rgba(col))
        layer = layer.rotate(ang, resample=Image.BICUBIC, center=(cx, cy))
        im.alpha_composite(layer)
    return soft_shadow(im, 8, 55, 4)


def cow(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    # body silhouette
    soft_ellipse(d, [cx - size * 0.22, cy - size * 0.08, cx + size * 0.28, cy + size * 0.22], rgba((45, 40, 35)))
    soft_ellipse(d, [cx - size * 0.2, cy - size * 0.28, cx + size * 0.12, cy + size * 0.02], rgba((45, 40, 35)))
    soft_ellipse(d, [cx - size * 0.28, cy - size * 0.32, cx - size * 0.14, cy - size * 0.16], rgba((45, 40, 35)))
    soft_ellipse(d, [cx + size * 0.0, cy - size * 0.3, cx + size * 0.14, cy - size * 0.14], rgba((45, 40, 35)))
    return soft_shadow(im, 8, 55, 4)


def cookie(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    soft_ellipse(d, [cx - size * 0.3, cy - size * 0.3, cx + size * 0.3, cy + size * 0.3], rgba((210, 155, 85)), rgba((170, 110, 50)), 3)
    for sx, sy in [(-0.12, -0.1), (0.1, -0.08), (-0.02, 0.08), (0.12, 0.1), (-0.14, 0.1), (0.0, -0.18)]:
        soft_ellipse(d, [cx + size * sx - 6, cy + size * sy - 6, cx + size * sx + 6, cy + size * sy + 6], rgba((90, 50, 30)))
    return soft_shadow(im, 8, 55, 4)


def macaron(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    colors = [(255, 150, 180), (150, 210, 150), (180, 150, 220)]
    for i, col in enumerate(colors):
        cy = size * (0.68 - i * 0.16)
        soft_ellipse(d, [size * 0.28, cy - size * 0.07, size * 0.72, cy + size * 0.07], rgba(mix(col, (255, 255, 255), 0.15)))
        soft_ellipse(d, [size * 0.3, cy - size * 0.12, size * 0.7, cy], rgba(col))
        soft_ellipse(d, [size * 0.3, cy, size * 0.7, cy + size * 0.12], rgba(col))
    return soft_shadow(im, 8, 55, 4)


def salad(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size * 0.55
    soft_ellipse(d, [cx - size * 0.3, cy - size * 0.08, cx + size * 0.3, cy + size * 0.22], rgba((230, 230, 225)), rgba((180, 180, 170), 3))
    for ox, oy, col in [(-0.08, -0.08, (80, 160, 70)), (0.1, -0.1, (100, 180, 80)), (0.0, 0.0, (60, 140, 60)), (-0.12, 0.02, (200, 80, 70))]:
        soft_ellipse(d, [cx + size * ox - size * 0.1, cy + size * oy - size * 0.08, cx + size * ox + size * 0.1, cy + size * oy + size * 0.08], rgba(col))
    return soft_shadow(im, 8, 55, 4)


def utensils(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # spoon
    soft_ellipse(d, [size * 0.22, size * 0.22, size * 0.42, size * 0.42], rgba((160, 110, 60)))
    d.rectangle([size * 0.30, size * 0.4, size * 0.34, size * 0.78], fill=rgba((160, 110, 60)))
    # fork
    d.rectangle([size * 0.62, size * 0.38, size * 0.66, size * 0.78], fill=rgba((160, 110, 60)))
    for fx in (0.58, 0.62, 0.66):
        d.rectangle([size * fx, size * 0.22, size * (fx + 0.025), size * 0.4], fill=rgba((160, 110, 60)))
    return soft_shadow(im, 8, 55, 4)


def kimchi_bowl(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size * 0.58
    soft_ellipse(d, [cx - size * 0.28, cy - size * 0.1, cx + size * 0.28, cy + size * 0.2], rgba((240, 235, 225)), rgba((180, 170, 160)), 3)
    soft_ellipse(d, [cx - size * 0.22, cy - size * 0.18, cx + size * 0.22, cy + size * 0.08], rgba((200, 60, 50)))
    for ox in (-0.08, 0.0, 0.08):
        soft_ellipse(d, [cx + size * ox - 8, cy - size * 0.12, cx + size * ox + 8, cy], rgba((230, 100, 70)))
    return soft_shadow(im, 8, 55, 4)


def honey_dipper(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    d.rectangle([size * 0.46, size * 0.18, size * 0.54, size * 0.55], fill=rgba((180, 120, 50)))
    soft_ellipse(d, [size * 0.32, size * 0.48, size * 0.68, size * 0.78], rgba((210, 150, 60)))
    for y in (0.55, 0.62, 0.69):
        d.line([(size * 0.36, size * y), (size * 0.64, size * y)], fill=rgba((150, 95, 35)), width=2)
    d.ellipse([size * 0.48, size * 0.78, size * 0.56, size * 0.9], fill=rgba((230, 170, 50), 200))
    return soft_shadow(im, 8, 55, 4)


def recycle(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    for i in range(3):
        ang0 = i * 120 - 90
        pts = []
        for t in range(ang0, ang0 + 100, 4):
            r = size * 0.28
            pts.append((cx + r * math.cos(math.radians(t)), cy + r * math.sin(math.radians(t))))
        for t in range(ang0 + 100, ang0, -4):
            r = size * 0.16
            pts.append((cx + r * math.cos(math.radians(t)), cy + r * math.sin(math.radians(t))))
        if len(pts) > 3:
            d.polygon(pts, fill=rgba((70, 160, 80)))
        tip = ang0 + 100
        ax = cx + size * 0.28 * math.cos(math.radians(tip))
        ay = cy + size * 0.28 * math.sin(math.radians(tip))
        d.polygon([
            (ax, ay),
            (ax - size * 0.08 * math.cos(math.radians(tip - 70)), ay - size * 0.08 * math.sin(math.radians(tip - 70))),
            (ax - size * 0.08 * math.cos(math.radians(tip + 70)), ay - size * 0.08 * math.sin(math.radians(tip + 70))),
        ], fill=rgba((70, 160, 80)))
    return soft_shadow(im, 6, 40, 2)


def fragile_glass(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx = size / 2
    d.polygon([(cx - size * 0.16, size * 0.22), (cx + size * 0.16, size * 0.22), (cx + size * 0.1, size * 0.55), (cx - size * 0.1, size * 0.55)], fill=rgba((220, 60, 60), 40), outline=rgba((200, 40, 40)))
    d.line([(cx - size * 0.1, size * 0.55), (cx - size * 0.14, size * 0.72)], fill=rgba((200, 40, 40)), width=3)
    d.line([(cx + size * 0.1, size * 0.55), (cx + size * 0.14, size * 0.72)], fill=rgba((200, 40, 40)), width=3)
    d.line([(cx - size * 0.16, size * 0.72), (cx + size * 0.16, size * 0.72)], fill=rgba((200, 40, 40)), width=3)
    d.line([(cx - size * 0.05, size * 0.35), (cx + size * 0.12, size * 0.5)], fill=rgba((200, 40, 40)), width=3)
    return soft_shadow(im, 6, 40, 2)


def arrows_up(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    for ox in (-0.12, 0.12):
        cx = size * (0.5 + ox)
        d.polygon([(cx, size * 0.22), (cx - size * 0.1, size * 0.42), (cx + size * 0.1, size * 0.42)], fill=rgba((40, 40, 40)))
        d.rectangle([cx - size * 0.04, size * 0.4, cx + size * 0.04, size * 0.75], fill=rgba((40, 40, 40)))
    return soft_shadow(im, 6, 40, 2)


def umbrella(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx = size / 2
    d.pieslice([cx - size * 0.28, size * 0.22, cx + size * 0.28, size * 0.7], 180, 360, fill=rgba((70, 130, 210)))
    d.arc([cx + size * 0.02, size * 0.55, cx + size * 0.16, size * 0.78], 0, 180, fill=rgba((50, 50, 50)), width=max(3, size // 70))
    d.line([(cx, size * 0.45), (cx, size * 0.7)], fill=rgba((50, 50, 50)), width=max(3, size // 70))
    for dx in (-0.18, 0.0, 0.18):
        d.line([(cx + size * dx, size * 0.18), (cx + size * dx, size * 0.28)], fill=rgba((70, 130, 210)), width=2)
    return soft_shadow(im, 6, 40, 2)


def caution(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    d.polygon([(cx, size * 0.14), (size * 0.86, size * 0.82), (size * 0.14, size * 0.82)], fill=rgba((250, 200, 40)), outline=rgba((30, 30, 30)), width=max(4, size // 60))
    d.rectangle([cx - 6, size * 0.38, cx + 6, size * 0.58], fill=rgba((30, 30, 30)))
    soft_ellipse(d, [cx - 7, size * 0.64, cx + 7, size * 0.74], rgba((30, 30, 30)))
    return soft_shadow(im, 6, 40, 2)


def hands_care(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # box
    d.rounded_rectangle([size * 0.32, size * 0.28, size * 0.68, size * 0.55], radius=6, fill=rgba((180, 140, 90)), outline=rgba((120, 90, 50)), width=2)
    # hands
    soft_ellipse(d, [size * 0.18, size * 0.5, size * 0.48, size * 0.78], rgba((240, 200, 160)))
    soft_ellipse(d, [size * 0.52, size * 0.5, size * 0.82, size * 0.78], rgba((240, 200, 160)))
    return soft_shadow(im, 6, 40, 2)


def truck(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    d.rounded_rectangle([size * 0.18, size * 0.35, size * 0.62, size * 0.62], radius=6, fill=rgba((70, 140, 220)))
    d.rounded_rectangle([size * 0.58, size * 0.42, size * 0.82, size * 0.62], radius=4, fill=rgba((50, 110, 190)))
    soft_ellipse(d, [size * 0.28, size * 0.58, size * 0.42, size * 0.72], rgba((40, 40, 40)))
    soft_ellipse(d, [size * 0.62, size * 0.58, size * 0.76, size * 0.72], rgba((40, 40, 40)))
    return soft_shadow(im, 6, 40, 2)


def envelope(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    d.rounded_rectangle([size * 0.18, size * 0.32, size * 0.82, size * 0.72], radius=8, fill=rgba((255, 250, 245)), outline=rgba((200, 180, 170)), width=3)
    d.polygon([(size * 0.18, size * 0.32), (size * 0.5, size * 0.52), (size * 0.82, size * 0.32)], fill=rgba((255, 245, 240)), outline=rgba((200, 180, 170)))
    soft_ellipse(d, [size * 0.42, size * 0.48, size * 0.58, size * 0.62], rgba((230, 70, 90)))
    return soft_shadow(im, 6, 40, 2)


def crown(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx = size / 2
    d.polygon([
        (size * 0.2, size * 0.62), (size * 0.2, size * 0.38), (size * 0.35, size * 0.5),
        (cx, size * 0.28), (size * 0.65, size * 0.5), (size * 0.8, size * 0.38), (size * 0.8, size * 0.62)
    ], fill=rgba((230, 180, 50)))
    for x in (0.28, 0.5, 0.72):
        soft_ellipse(d, [size * x - 6, size * 0.22, size * x + 6, size * 0.34], rgba((255, 210, 80)))
    return soft_shadow(im, 8, 55, 3)


def starburst(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    pts = []
    spikes = 12
    for i in range(spikes * 2):
        ang = math.radians(i * 180 / spikes - 90)
        r = size * (0.42 if i % 2 == 0 else 0.24)
        pts.append((cx + r * math.cos(ang), cy + r * math.sin(ang)))
    d.polygon(pts, fill=rgba((240, 120, 40)))
    return soft_shadow(im, 8, 55, 3)


def wreath(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    for i in range(18):
        ang = i * 20
        r = size * 0.34
        x = cx + r * math.cos(math.radians(ang))
        y = cy + r * math.sin(math.radians(ang))
        col = (230, 120, 90) if i % 2 == 0 else (90, 150, 200)
        soft_ellipse(d, [x - size * 0.05, y - size * 0.05, x + size * 0.05, y + size * 0.05], rgba(col))
    return soft_shadow(im, 6, 40, 2)


def floral_corner(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    for x, y, col in [(0.2, 0.2, (240, 140, 160)), (0.32, 0.28, (255, 180, 120)), (0.22, 0.34, (180, 210, 140))]:
        soft_ellipse(d, [size * x - 10, size * y - 10, size * x + 10, size * y + 10], rgba(col))
    for x, y, col in [(0.78, 0.78, (240, 140, 160)), (0.68, 0.72, (255, 180, 120)), (0.8, 0.66, (180, 210, 140))]:
        soft_ellipse(d, [size * x - 10, size * y - 10, size * x + 10, size * y + 10], rgba(col))
    return im


def snow_forest(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    for i, (x, h) in enumerate([(0.25, 0.35), (0.5, 0.45), (0.72, 0.32)]):
        top = size * (0.7 - h)
        d.polygon([(size * x, top), (size * x - size * 0.1, size * 0.72), (size * x + size * 0.1, size * 0.72)], fill=rgba((200, 220, 240)))
    for _ in range(18):
        sx = int(size * (0.1 + 0.8 * np.random.random()))
        sy = int(size * (0.15 + 0.5 * np.random.random()))
        soft_ellipse(d, [sx, sy, sx + 4, sy + 4], rgba((255, 255, 255), 200))
    return soft_shadow(im, 6, 40, 2)


def handmade_wreath(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    for i in range(24):
        ang = i * 15
        r = size * 0.36
        x = cx + r * math.cos(math.radians(ang))
        y = cy + r * math.sin(math.radians(ang))
        soft_ellipse(d, [x - 5, y - 10, x + 5, y + 10], rgba((120, 90, 55), 180))
    soft_ellipse(d, [cx - 8, cy + size * 0.28, cx + 8, cy + size * 0.38], rgba((210, 60, 70)))
    return soft_shadow(im, 6, 40, 2)


def laurel_heart(size: int) -> Image.Image:
    im = canvas(size)
    heart_img = heart(int(size * 0.55), (220, 60, 80))
    im.alpha_composite(heart_img, ((size - heart_img.width) // 2, int(size * 0.22)))
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size * 0.62
    for side in (-1, 1):
        for i in range(6):
            ang = 60 + i * 18
            x = cx + side * size * 0.08 * (1 + i * 0.35)
            y = cy - size * 0.02 * i
            soft_ellipse(d, [x - 8, y - 12, x + 8, y + 12], rgba((80, 150, 70)))
    return soft_shadow(im, 8, 50, 3)


def barcode_art(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    x0, y0, y1 = size * 0.15, size * 0.28, size * 0.72
    x = x0
    widths = [3, 2, 4, 2, 3, 5, 2, 3, 2, 4, 3, 2, 5, 2, 3, 4, 2, 3, 2, 4, 3, 2]
    for i, w in enumerate(widths):
        if i % 2 == 0:
            d.rectangle([x, y0, x + w * size / 180, y1], fill=rgba((30, 30, 30)))
        x += w * size / 180 + size * 0.008
    return im


def qr_art(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    n = 21
    cell = size * 0.7 / n
    ox, oy = size * 0.15, size * 0.12
    rng = np.random.default_rng(42)
    grid = rng.integers(0, 2, size=(n, n))
    for i in range(7):
        for j in range(7):
            grid[i, j] = 1 if (i in (0, 6) or j in (0, 6) or (2 <= i <= 4 and 2 <= j <= 4)) else 0
            grid[i, n - 7 + j] = grid[i, j]
            grid[n - 7 + i, j] = grid[i, j]
    for r in range(n):
        for c in range(n):
            if grid[r, c]:
                d.rectangle([ox + c * cell, oy + r * cell, ox + (c + 1) * cell - 1, oy + (r + 1) * cell - 1], fill=rgba((20, 20, 20)))
    return im


def orange(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2 + size * 0.04
    soft_ellipse(d, [cx - size * 0.28, cy - size * 0.26, cx + size * 0.28, cy + size * 0.28], rgba((245, 140, 45)))
    soft_ellipse(d, [cx - size * 0.14, cy - size * 0.2, cx + size * 0.04, cy - size * 0.02], rgba((255, 200, 120), 90))
    # navel dimple
    soft_ellipse(d, [cx - size * 0.04, cy + size * 0.12, cx + size * 0.04, cy + size * 0.2], rgba((210, 110, 30)))
    d.rectangle([cx - 3, cy - size * 0.34, cx + 3, cy - size * 0.22], fill=rgba((80, 130, 50)))
    soft_ellipse(d, [cx + 2, cy - size * 0.36, cx + size * 0.14, cy - size * 0.22], rgba((70, 145, 55)))
    return soft_shadow(im, 8, 55, 4)


def lemon(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    pts = []
    for t in np.linspace(0, 2 * math.pi, 100):
        rx = size * 0.22 * (1.0 + 0.18 * math.cos(2 * t))
        ry = size * 0.3
        pts.append((cx + rx * math.sin(t), cy + ry * math.cos(t)))
    d.polygon(pts, fill=rgba((250, 220, 70)))
    soft_ellipse(d, [cx - size * 0.1, cy - size * 0.18, cx + size * 0.02, cy - size * 0.02], rgba((255, 255, 200), 100))
    soft_ellipse(d, [cx - size * 0.04, cy - size * 0.34, cx + size * 0.04, cy - size * 0.26], rgba((90, 150, 55)))
    return soft_shadow(im, 8, 55, 4)


def bear(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2 + size * 0.04
    # ears
    for ex in (-0.22, 0.22):
        soft_ellipse(d, [cx + size * ex - size * 0.1, cy - size * 0.34, cx + size * ex + size * 0.1, cy - size * 0.12], rgba((150, 100, 60)))
        soft_ellipse(d, [cx + size * ex - size * 0.05, cy - size * 0.28, cx + size * ex + size * 0.05, cy - size * 0.16], rgba((200, 150, 110)))
    soft_ellipse(d, [cx - size * 0.28, cy - size * 0.22, cx + size * 0.28, cy + size * 0.28], rgba((160, 110, 65)))
    soft_ellipse(d, [cx - size * 0.14, cy + size * 0.02, cx + size * 0.14, cy + size * 0.22], rgba((220, 185, 145)))
    for ex in (-0.12, 0.12):
        soft_ellipse(d, [cx + size * ex - 5, cy - size * 0.06, cx + size * ex + 5, cy + size * 0.04], rgba((50, 35, 25)))
    soft_ellipse(d, [cx - 6, cy + size * 0.06, cx + 6, cy + size * 0.14], rgba((50, 35, 25)))
    d.arc([cx - size * 0.1, cy + size * 0.1, cx + size * 0.1, cy + size * 0.2], 20, 160, fill=rgba((50, 35, 25)), width=max(2, size // 80))
    return soft_shadow(im, 8, 55, 4)


def house(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx = size / 2
    d.polygon([(cx, size * 0.18), (size * 0.18, size * 0.42), (size * 0.82, size * 0.42)], fill=rgba((170, 90, 55)))
    d.rounded_rectangle([size * 0.24, size * 0.4, size * 0.76, size * 0.78], radius=4, fill=rgba((210, 160, 110)))
    d.rectangle([cx - size * 0.06, size * 0.55, cx + size * 0.06, size * 0.78], fill=rgba((130, 70, 40)))
    soft_ellipse(d, [size * 0.52, size * 0.64, size * 0.58, size * 0.7], rgba((230, 190, 90)))
    soft_ellipse(d, [size * 0.32, size * 0.5, size * 0.44, size * 0.62], rgba((160, 210, 230)))
    return soft_shadow(im, 8, 55, 4)


def sun_leaves(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # leaves cluster bottom-leftish
    for ox, oy, ang, col in [
        (0.42, 0.58, -25, (70, 145, 70)),
        (0.52, 0.55, 10, (90, 165, 80)),
        (0.48, 0.66, 35, (60, 130, 60)),
        (0.58, 0.62, -5, (100, 170, 85)),
    ]:
        layer = canvas(size)
        ld = ImageDraw.Draw(layer)
        lx, ly = size * ox, size * oy
        soft_ellipse(ld, [lx - size * 0.08, ly - size * 0.18, lx + size * 0.08, ly + size * 0.18], rgba(col))
        layer = layer.rotate(ang, resample=Image.BICUBIC, center=(lx, ly))
        im.alpha_composite(layer)
    # small sun top-right
    sx, sy = size * 0.72, size * 0.28
    soft_ellipse(d, [sx - size * 0.1, sy - size * 0.1, sx + size * 0.1, sy + size * 0.1], rgba((255, 210, 70)))
    for i in range(8):
        ang = math.radians(i * 45)
        d.line(
            [(sx + size * 0.12 * math.cos(ang), sy + size * 0.12 * math.sin(ang)),
             (sx + size * 0.18 * math.cos(ang), sy + size * 0.18 * math.sin(ang))],
            fill=rgba((255, 190, 50)), width=max(2, size // 90),
        )
    return soft_shadow(im, 8, 50, 3)


def lavender(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    for i, (bx, tip_y, lean) in enumerate([(0.32, 0.2, -8), (0.5, 0.14, 0), (0.68, 0.22, 10)]):
        layer = canvas(size)
        ld = ImageDraw.Draw(layer)
        x0 = size * bx
        ld.line([(x0, size * 0.78), (x0, size * tip_y)], fill=rgba((70, 120, 70)), width=max(2, size // 90))
        for j in range(7):
            y = size * (tip_y + 0.04 + j * 0.05)
            soft_ellipse(ld, [x0 - size * 0.05, y - size * 0.04, x0 + size * 0.05, y + size * 0.04], rgba((160, 110, 200)))
            soft_ellipse(ld, [x0 - size * 0.03, y - size * 0.02, x0 + size * 0.03, y + size * 0.02], rgba((190, 150, 220)))
        layer = layer.rotate(lean, resample=Image.BICUBIC, center=(x0, size * 0.5))
        im.alpha_composite(layer)
    return soft_shadow(im, 8, 50, 3)


def flower_pink(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    for i in range(5):
        ang = math.radians(i * 72 - 90)
        px = cx + size * 0.16 * math.cos(ang)
        py = cy + size * 0.16 * math.sin(ang)
        soft_ellipse(d, [px - size * 0.12, py - size * 0.12, px + size * 0.12, py + size * 0.12], rgba((245, 140, 175)))
    soft_ellipse(d, [cx - size * 0.08, cy - size * 0.08, cx + size * 0.08, cy + size * 0.08], rgba((255, 220, 90)))
    return soft_shadow(im, 8, 55, 3)


def flower_white(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    for i in range(6):
        ang = math.radians(i * 60)
        px = cx + size * 0.12 * math.cos(ang)
        py = cy + size * 0.12 * math.sin(ang)
        soft_ellipse(d, [px - size * 0.09, py - size * 0.09, px + size * 0.09, py + size * 0.09], rgba((250, 250, 248)))
    soft_ellipse(d, [cx - size * 0.05, cy - size * 0.05, cx + size * 0.05, cy + size * 0.05], rgba((255, 200, 80)))
    return soft_shadow(im, 7, 45, 3)


def candle(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx = size / 2
    # jar
    d.rounded_rectangle([size * 0.3, size * 0.42, size * 0.7, size * 0.82], radius=10, fill=rgba((230, 245, 250), 180), outline=rgba((170, 200, 210)), width=2)
    soft_ellipse(d, [size * 0.3, size * 0.38, size * 0.7, size * 0.5], rgba((200, 230, 235)))
    # wax
    soft_ellipse(d, [size * 0.34, size * 0.48, size * 0.66, size * 0.58], rgba((255, 245, 220)))
    d.rectangle([size * 0.34, size * 0.52, size * 0.66, size * 0.78], fill=rgba((255, 240, 210)))
    # wick + flame
    d.line([(cx, size * 0.42), (cx, size * 0.52)], fill=rgba((60, 40, 30)), width=max(2, size // 100))
    soft_ellipse(d, [cx - size * 0.05, size * 0.22, cx + size * 0.05, size * 0.42], rgba((255, 170, 50)))
    soft_ellipse(d, [cx - size * 0.025, size * 0.26, cx + size * 0.025, size * 0.38], rgba((255, 240, 150)))
    return soft_shadow(im, 8, 55, 4)


def diffuser(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx = size / 2
    # bottle
    d.rounded_rectangle([size * 0.36, size * 0.42, size * 0.64, size * 0.82], radius=8, fill=rgba((210, 230, 220)), outline=rgba((140, 170, 160)), width=2)
    soft_ellipse(d, [size * 0.38, size * 0.55, size * 0.62, size * 0.78], rgba((180, 210, 200), 160))
    d.rectangle([size * 0.44, size * 0.34, size * 0.56, size * 0.44], fill=rgba((120, 100, 80)))
    # reeds
    for ox, top in [(-0.08, 0.12), (-0.02, 0.08), (0.04, 0.1), (0.1, 0.14)]:
        d.line([(cx + size * ox * 0.3, size * 0.42), (cx + size * ox, size * top)], fill=rgba((160, 130, 90)), width=max(2, size // 100))
    return soft_shadow(im, 8, 50, 3)


def dog(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2 + size * 0.04
    # floppy ears
    for ex, lean in [(-0.28, 15), (0.28, -15)]:
        layer = canvas(size)
        ld = ImageDraw.Draw(layer)
        soft_ellipse(ld, [cx + size * ex - size * 0.1, cy - size * 0.2, cx + size * ex + size * 0.1, cy + size * 0.18], rgba((200, 150, 80)))
        layer = layer.rotate(lean, resample=Image.BICUBIC, center=(cx + size * ex, cy))
        im.alpha_composite(layer)
    soft_ellipse(d, [cx - size * 0.26, cy - size * 0.24, cx + size * 0.26, cy + size * 0.26], rgba((230, 185, 110)))
    soft_ellipse(d, [cx - size * 0.12, cy + size * 0.02, cx + size * 0.12, cy + size * 0.2], rgba((240, 210, 160)))
    for ex in (-0.1, 0.1):
        soft_ellipse(d, [cx + size * ex - 5, cy - size * 0.06, cx + size * ex + 5, cy + size * 0.04], rgba((50, 35, 25)))
    soft_ellipse(d, [cx - 7, cy + size * 0.06, cx + 7, cy + size * 0.14], rgba((40, 30, 25)))
    d.arc([cx - size * 0.1, cy + size * 0.1, cx + size * 0.1, cy + size * 0.2], 10, 170, fill=rgba((50, 35, 25)), width=max(2, size // 80))
    return soft_shadow(im, 8, 55, 4)


def cat(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2 + size * 0.04
    # ears
    d.polygon([(cx - size * 0.22, cy - size * 0.08), (cx - size * 0.28, cy - size * 0.32), (cx - size * 0.06, cy - size * 0.18)], fill=rgba((200, 150, 90)))
    d.polygon([(cx + size * 0.22, cy - size * 0.08), (cx + size * 0.28, cy - size * 0.32), (cx + size * 0.06, cy - size * 0.18)], fill=rgba((200, 150, 90)))
    soft_ellipse(d, [cx - size * 0.26, cy - size * 0.18, cx + size * 0.26, cy + size * 0.28], rgba((220, 170, 100)))
    # tabby stripes
    for sy in (-0.08, 0.0, 0.08):
        d.arc([cx - size * 0.18, cy + size * sy - size * 0.08, cx + size * 0.18, cy + size * sy + size * 0.08], 200, 340, fill=rgba((170, 120, 60)), width=max(2, size // 90))
    for ex in (-0.1, 0.1):
        soft_ellipse(d, [cx + size * ex - 5, cy - size * 0.02, cx + size * ex + 5, cy + size * 0.08], rgba((50, 120, 70)))
    soft_ellipse(d, [cx - 5, cy + size * 0.1, cx + 5, cy + size * 0.18], rgba((240, 140, 120)))
    # whiskers
    for side in (-1, 1):
        for wy in (0.1, 0.14, 0.18):
            d.line([(cx + side * size * 0.04, cy + size * 0.12), (cx + side * size * 0.28, cy + size * wy)], fill=rgba((80, 60, 40), 180), width=1)
    return soft_shadow(im, 8, 55, 4)


def snowflake(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    col = rgba((110, 170, 230))
    w = max(2, size // 70)
    for i in range(6):
        ang = math.radians(i * 60)
        x1 = cx + size * 0.34 * math.cos(ang)
        y1 = cy + size * 0.34 * math.sin(ang)
        d.line([(cx, cy), (x1, y1)], fill=col, width=w)
        for branch in (-0.35, 0.35):
            bx = cx + size * 0.2 * math.cos(ang)
            by = cy + size * 0.2 * math.sin(ang)
            bang = ang + branch
            d.line([(bx, by), (bx + size * 0.1 * math.cos(bang), by + size * 0.1 * math.sin(bang))], fill=col, width=max(2, w - 1))
    soft_ellipse(d, [cx - size * 0.05, cy - size * 0.05, cx + size * 0.05, cy + size * 0.05], rgba((160, 200, 245)))
    return soft_shadow(im, 6, 40, 2)


def thermometer(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx = size / 2
    d.rounded_rectangle([cx - size * 0.08, size * 0.14, cx + size * 0.08, size * 0.62], radius=10, fill=rgba((230, 245, 255)), outline=rgba((80, 140, 200)), width=max(2, size // 80))
    soft_ellipse(d, [cx - size * 0.14, size * 0.56, cx + size * 0.14, size * 0.84], rgba((70, 150, 220)), rgba((50, 110, 180)), max(2, size // 90))
    d.rectangle([cx - size * 0.035, size * 0.32, cx + size * 0.035, size * 0.64], fill=rgba((70, 150, 220)))
    for y in (0.22, 0.3, 0.38, 0.46):
        d.line([(cx + size * 0.09, size * y), (cx + size * 0.14, size * y)], fill=rgba((80, 140, 200)), width=2)
    return soft_shadow(im, 7, 45, 3)


def premium_seal(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    soft_ellipse(d, [cx - size * 0.38, cy - size * 0.38, cx + size * 0.38, cy + size * 0.38], rgba((220, 170, 45)))
    soft_ellipse(d, [cx - size * 0.3, cy - size * 0.3, cx + size * 0.3, cy + size * 0.3], rgba((245, 205, 90)))
    for i in range(8):
        ang = math.radians(i * 45 - 90)
        sx = cx + size * 0.34 * math.cos(ang)
        sy = cy + size * 0.34 * math.sin(ang)
        soft_ellipse(d, [sx - 4, sy - 4, sx + 4, sy + 4], rgba((255, 240, 160)))
    mask = Image.new("L", (size, size), 255)
    md = ImageDraw.Draw(mask)
    md.ellipse([cx - size * 0.2, cy - size * 0.2, cx + size * 0.2, cy + size * 0.2], fill=0)
    im.putalpha(ImageChops.multiply(im.split()[-1], mask))
    return soft_shadow(im, 8, 50, 3)


def confetti(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    rng = np.random.default_rng(7)
    colors = [(240, 90, 110), (255, 190, 60), (90, 180, 220), (130, 210, 110), (200, 130, 230), (255, 140, 80)]
    for _ in range(36):
        x = float(rng.uniform(0.12, 0.88)) * size
        y = float(rng.uniform(0.12, 0.88)) * size
        r = float(rng.uniform(0.015, 0.035)) * size
        col = colors[int(rng.integers(0, len(colors)))]
        soft_ellipse(d, [x - r, y - r, x + r, y + r], rgba(col))
    return soft_shadow(im, 5, 35, 2)


def scissors(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    col = rgba((70, 160, 90))
    dark = rgba((40, 110, 60))
    d.polygon([(size * 0.55, size * 0.2), (size * 0.7, size * 0.28), (size * 0.42, size * 0.55)], fill=col)
    d.polygon([(size * 0.45, size * 0.2), (size * 0.3, size * 0.28), (size * 0.58, size * 0.55)], fill=rgba((90, 180, 110)))
    soft_ellipse(d, [size * 0.44, size * 0.48, size * 0.56, size * 0.6], rgba((50, 100, 55)))
    d.ellipse([size * 0.22, size * 0.58, size * 0.42, size * 0.82], outline=col, width=max(4, size // 50))
    d.ellipse([size * 0.58, size * 0.58, size * 0.78, size * 0.82], outline=col, width=max(4, size // 50))
    d.line([(size * 0.38, size * 0.62), (size * 0.48, size * 0.55)], fill=dark, width=max(3, size // 70))
    d.line([(size * 0.62, size * 0.62), (size * 0.52, size * 0.55)], fill=dark, width=max(3, size // 70))
    return soft_shadow(im, 7, 45, 3)


def bouquet(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # stems bottom
    for ox in (-0.08, 0.0, 0.08):
        d.line([(size * (0.5 + ox), size * 0.85), (size * (0.5 + ox * 0.5), size * 0.5)], fill=rgba((70, 130, 60)), width=max(2, size // 90))
    # wrap
    d.polygon([(size * 0.38, size * 0.55), (size * 0.62, size * 0.55), (size * 0.55, size * 0.82), (size * 0.45, size * 0.82)], fill=rgba((240, 180, 200)))
    # flowers clustered upper
    for x, y, col in [
        (0.38, 0.38, (245, 140, 170)),
        (0.5, 0.3, (255, 160, 185)),
        (0.62, 0.38, (240, 130, 160)),
        (0.45, 0.45, (255, 190, 200)),
        (0.55, 0.44, (235, 120, 150)),
    ]:
        soft_ellipse(d, [size * x - size * 0.09, size * y - size * 0.09, size * x + size * 0.09, size * y + size * 0.09], rgba(col))
        soft_ellipse(d, [size * x - 5, size * y - 5, size * x + 5, size * y + 5], rgba((255, 220, 100)))
    return soft_shadow(im, 8, 55, 4)


def carnation(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2 - size * 0.04
    # ruffled petals
    for i in range(12):
        ang = math.radians(i * 30)
        px = cx + size * 0.14 * math.cos(ang)
        py = cy + size * 0.12 * math.sin(ang)
        soft_ellipse(d, [px - size * 0.1, py - size * 0.08, px + size * 0.1, py + size * 0.08], rgba((210, 50, 70)))
    soft_ellipse(d, [cx - size * 0.1, cy - size * 0.08, cx + size * 0.1, cy + size * 0.08], rgba((230, 70, 90)))
    d.line([(cx, cy + size * 0.12), (cx, size * 0.82)], fill=rgba((60, 130, 55)), width=max(3, size // 70))
    soft_ellipse(d, [cx - size * 0.12, cy + size * 0.1, cx - size * 0.02, cy + size * 0.22], rgba((70, 145, 65)))
    soft_ellipse(d, [cx + size * 0.02, cy + size * 0.12, cx + size * 0.12, cy + size * 0.24], rgba((70, 145, 65)))
    return soft_shadow(im, 8, 55, 4)


def wedding_flowers(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # horizontal arrangement across middle
    for x, y, col, r in [
        (0.22, 0.5, (255, 240, 245), 0.08),
        (0.35, 0.42, (245, 160, 180), 0.09),
        (0.5, 0.48, (250, 250, 248), 0.1),
        (0.65, 0.42, (240, 150, 175), 0.09),
        (0.78, 0.5, (255, 235, 240), 0.08),
        (0.42, 0.58, (180, 210, 150), 0.06),
        (0.58, 0.58, (160, 200, 140), 0.06),
    ]:
        soft_ellipse(d, [size * x - size * r, size * y - size * r, size * x + size * r, size * y + size * r], rgba(col))
    soft_ellipse(d, [size * 0.48, size * 0.45, size * 0.52, size * 0.51], rgba((255, 200, 90)))
    return soft_shadow(im, 7, 45, 3)


def leaves_sprig(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # bottom-biased composition
    d.line([(size * 0.2, size * 0.72), (size * 0.8, size * 0.58)], fill=rgba((80, 130, 55)), width=max(3, size // 80))
    for ox, oy, sc in [(0.28, 0.68, 0.9), (0.42, 0.64, 1.0), (0.56, 0.6, 0.95), (0.7, 0.56, 0.85)]:
        lx, ly = size * ox, size * oy
        soft_ellipse(d, [lx - size * 0.07 * sc, ly - size * 0.12 * sc, lx + size * 0.07 * sc, ly + size * 0.12 * sc], rgba((90, 155, 70)))
    d.line([(size * 0.25, size * 0.8), (size * 0.75, size * 0.7)], fill=rgba((70, 120, 50)), width=max(2, size // 90))
    for ox, oy in [(0.35, 0.76), (0.5, 0.72), (0.65, 0.68)]:
        soft_ellipse(d, [size * ox - size * 0.05, size * oy - size * 0.09, size * ox + size * 0.05, size * oy + size * 0.09], rgba((70, 140, 65)))
    return soft_shadow(im, 7, 45, 3)


def kraft_circle(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    kraft = (180, 140, 90)
    soft_ellipse(d, [cx - size * 0.4, cy - size * 0.4, cx + size * 0.4, cy + size * 0.4], rgba(kraft, 55))
    mask = Image.new("L", (size, size), 255)
    md = ImageDraw.Draw(mask)
    md.ellipse([cx - size * 0.32, cy - size * 0.32, cx + size * 0.32, cy + size * 0.32], fill=0)
    im.putalpha(ImageChops.multiply(im.split()[-1], mask))
    d = ImageDraw.Draw(im)
    for i in range(48):
        ang = math.radians(i * 7.5)
        r = size * 0.36
        x = cx + r * math.cos(ang)
        y = cy + r * math.sin(ang)
        soft_ellipse(d, [x - 3, y - 3, x + 3, y + 3], rgba(kraft, 220 if i % 2 == 0 else 140))
    return soft_shadow(im, 6, 40, 2)


def cart(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    red = (220, 55, 55)
    dark = (160, 30, 30)
    w = max(3, size // 55)
    # basket
    d.polygon([
        (size * 0.22, size * 0.32), (size * 0.78, size * 0.32),
        (size * 0.72, size * 0.62), (size * 0.28, size * 0.62),
    ], fill=rgba(red), outline=rgba(dark), width=2)
    # handle
    d.line([(size * 0.22, size * 0.32), (size * 0.16, size * 0.2)], fill=rgba(dark), width=w)
    d.arc([size * 0.08, size * 0.14, size * 0.28, size * 0.32], 200, 340, fill=rgba(dark), width=w)
    # grid lines
    for x in (0.38, 0.5, 0.62):
        d.line([(size * x, size * 0.36), (size * (x - 0.02), size * 0.58)], fill=rgba((255, 180, 180), 160), width=max(2, size // 90))
    # wheels
    soft_ellipse(d, [size * 0.3, size * 0.64, size * 0.42, size * 0.76], rgba((50, 45, 45)))
    soft_ellipse(d, [size * 0.58, size * 0.64, size * 0.7, size * 0.76], rgba((50, 45, 45)))
    soft_ellipse(d, [size * 0.33, size * 0.67, size * 0.39, size * 0.73], rgba((120, 120, 120)))
    soft_ellipse(d, [size * 0.61, size * 0.67, size * 0.67, size * 0.73], rgba((120, 120, 120)))
    return soft_shadow(im, 8, 55, 4)


def baby(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2 + size * 0.02
    soft_ellipse(d, [cx - size * 0.3, cy - size * 0.32, cx + size * 0.3, cy + size * 0.32], rgba((255, 220, 190)))
    # hair tuft
    soft_ellipse(d, [cx - size * 0.08, cy - size * 0.38, cx + size * 0.08, cy - size * 0.22], rgba((90, 60, 40)))
    for ex in (-0.12, 0.12):
        soft_ellipse(d, [cx + size * ex - size * 0.04, cy - size * 0.06, cx + size * ex + size * 0.04, cy + size * 0.04], rgba((70, 45, 35)))
    soft_ellipse(d, [cx - size * 0.04, cy + size * 0.04, cx + size * 0.04, cy + size * 0.12], rgba((255, 170, 150)))
    d.arc([cx - size * 0.14, cy + size * 0.06, cx + size * 0.14, cy + size * 0.22], 20, 160, fill=rgba((200, 90, 90)), width=max(3, size // 60))
    # cheeks
    soft_ellipse(d, [cx - size * 0.22, cy + size * 0.02, cx - size * 0.1, cy + size * 0.12], rgba((255, 160, 150), 120))
    soft_ellipse(d, [cx + size * 0.1, cy + size * 0.02, cx + size * 0.22, cy + size * 0.12], rgba((255, 160, 150), 120))
    return soft_shadow(im, 8, 55, 4)


def microwave(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    d.rounded_rectangle([size * 0.14, size * 0.22, size * 0.86, size * 0.78], radius=10, fill=rgba((210, 215, 220)), outline=rgba((120, 130, 140)), width=max(2, size // 80))
    d.rounded_rectangle([size * 0.2, size * 0.3, size * 0.62, size * 0.7], radius=6, fill=rgba((40, 45, 55)), outline=rgba((80, 90, 100)), width=2)
    # window reflection
    soft_ellipse(d, [size * 0.26, size * 0.36, size * 0.4, size * 0.5], rgba((180, 200, 220), 80))
    # control panel
    d.rounded_rectangle([size * 0.66, size * 0.3, size * 0.8, size * 0.7], radius=4, fill=rgba((180, 185, 190)))
    for i, y in enumerate((0.36, 0.46, 0.56)):
        soft_ellipse(d, [size * 0.7, size * y, size * 0.76, size * (y + 0.05)], rgba((90, 160, 220) if i == 0 else (70, 75, 80)))
    return soft_shadow(im, 8, 50, 3)


def oven(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    d.rounded_rectangle([size * 0.18, size * 0.16, size * 0.82, size * 0.84], radius=8, fill=rgba((90, 95, 105)), outline=rgba((55, 60, 70)), width=max(2, size // 80))
    # knobs
    for x in (0.32, 0.5, 0.68):
        soft_ellipse(d, [size * x - size * 0.04, size * 0.22, size * x + size * 0.04, size * 0.3], rgba((200, 205, 210)))
    # door window
    d.rounded_rectangle([size * 0.26, size * 0.36, size * 0.74, size * 0.72], radius=6, fill=rgba((45, 50, 60)), outline=rgba((150, 155, 165)), width=max(3, size // 70))
    soft_ellipse(d, [size * 0.32, size * 0.42, size * 0.48, size * 0.56], rgba((200, 210, 230), 70))
    # handle
    d.rounded_rectangle([size * 0.3, size * 0.74, size * 0.7, size * 0.8], radius=4, fill=rgba((180, 185, 195)))
    return soft_shadow(im, 8, 50, 3)


def dishwasher(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    d.rounded_rectangle([size * 0.2, size * 0.14, size * 0.8, size * 0.86], radius=8, fill=rgba((200, 210, 220)), outline=rgba((120, 135, 150)), width=max(2, size // 80))
    d.rounded_rectangle([size * 0.26, size * 0.2, size * 0.74, size * 0.32], radius=4, fill=rgba((160, 175, 190)))
    soft_ellipse(d, [size * 0.58, size * 0.23, size * 0.68, size * 0.29], rgba((90, 150, 210)))
    # plates stacked inside door
    d.rounded_rectangle([size * 0.28, size * 0.38, size * 0.72, size * 0.78], radius=6, fill=rgba((230, 238, 245)), outline=rgba((140, 155, 170)), width=2)
    for y in (0.48, 0.58, 0.68):
        soft_ellipse(d, [size * 0.34, size * y, size * 0.66, size * (y + 0.06)], rgba((245, 250, 255)), rgba((160, 180, 200)), 2)
    return soft_shadow(im, 8, 50, 3)


def bpa_free(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    # leaf
    pts = []
    for t in np.linspace(0, 2 * math.pi, 100):
        ang = t - math.pi / 2
        rr = size * 0.34 * (0.35 + 0.65 * ((math.cos(t) + 1) / 2) ** 0.7)
        if math.cos(t) < 0:
            rr *= 0.55
        pts.append((cx + rr * math.sin(ang), cy - rr * math.cos(ang) * 1.1))
    d.polygon(pts, fill=rgba((70, 160, 80)))
    d.line([(cx, cy + size * 0.28), (cx, cy - size * 0.26)], fill=rgba((40, 110, 50)), width=max(2, size // 80))
    # checkmark
    w = max(4, size // 40)
    d.line([(size * 0.38, size * 0.52), (size * 0.48, size * 0.62), (size * 0.66, size * 0.38)], fill=rgba((255, 255, 255)), width=w)
    return soft_shadow(im, 8, 55, 4)


def gluten_free(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2 + size * 0.02
    # wheat ear
    d.line([(cx, size * 0.78), (cx, size * 0.22)], fill=rgba((180, 140, 50)), width=max(3, size // 70))
    for i, y in enumerate(np.linspace(0.28, 0.68, 6)):
        sc = 1.0 - abs(i - 2.5) * 0.08
        soft_ellipse(d, [cx - size * 0.14 * sc, size * y - size * 0.05, cx - size * 0.02, size * y + size * 0.05], rgba((220, 180, 70)))
        soft_ellipse(d, [cx + size * 0.02, size * y - size * 0.05, cx + size * 0.14 * sc, size * y + size * 0.05], rgba((210, 165, 55)))
    # red X
    w = max(5, size // 35)
    d.line([(size * 0.28, size * 0.28), (size * 0.72, size * 0.72)], fill=rgba((220, 50, 50)), width=w)
    d.line([(size * 0.72, size * 0.28), (size * 0.28, size * 0.72)], fill=rgba((220, 50, 50)), width=w)
    return soft_shadow(im, 8, 55, 4)


def non_gmo(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx, cy = size / 2, size / 2
    # shield
    shield = [
        (cx, cy - size * 0.36),
        (cx + size * 0.3, cy - size * 0.22),
        (cx + size * 0.28, cy + size * 0.1),
        (cx, cy + size * 0.36),
        (cx - size * 0.28, cy + size * 0.1),
        (cx - size * 0.3, cy - size * 0.22),
    ]
    d.polygon(shield, fill=rgba((70, 150, 90)), outline=rgba((40, 110, 55)), width=max(2, size // 80))
    soft_ellipse(d, [cx - size * 0.12, cy - size * 0.18, cx + size * 0.12, cy + size * 0.1], rgba((120, 190, 110)))
    # small leaf inside
    soft_ellipse(d, [cx - size * 0.06, cy - size * 0.08, cx + size * 0.1, cy + size * 0.08], rgba((50, 130, 60)))
    d.line([(cx, cy + size * 0.06), (cx, cy - size * 0.1)], fill=rgba((30, 90, 40)), width=max(2, size // 100))
    return soft_shadow(im, 8, 55, 4)


def kids(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # boy (left)
    bx, by = size * 0.34, size * 0.5
    soft_ellipse(d, [bx - size * 0.18, by - size * 0.2, bx + size * 0.18, by + size * 0.2], rgba((255, 210, 170)))
    soft_ellipse(d, [bx - size * 0.16, by - size * 0.28, bx + size * 0.16, by - size * 0.08], rgba((70, 110, 180)))
    for ex in (-0.06, 0.06):
        soft_ellipse(d, [bx + size * ex - 4, by - size * 0.02, bx + size * ex + 4, by + size * 0.06], rgba((50, 40, 30)))
    d.arc([bx - size * 0.08, by + size * 0.04, bx + size * 0.08, by + size * 0.14], 20, 160, fill=rgba((50, 40, 30)), width=max(2, size // 80))
    # girl (right)
    gx, gy = size * 0.66, size * 0.5
    soft_ellipse(d, [gx - size * 0.18, gy - size * 0.2, gx + size * 0.18, gy + size * 0.2], rgba((255, 210, 175)))
    soft_ellipse(d, [gx - size * 0.18, gy - size * 0.28, gx + size * 0.18, gy - size * 0.02], rgba((230, 120, 160)))
    soft_ellipse(d, [gx - size * 0.2, gy - size * 0.05, gx - size * 0.1, gy + size * 0.18], rgba((230, 120, 160)))
    soft_ellipse(d, [gx + size * 0.1, gy - size * 0.05, gx + size * 0.2, gy + size * 0.18], rgba((230, 120, 160)))
    for ex in (-0.06, 0.06):
        soft_ellipse(d, [gx + size * ex - 4, gy - size * 0.02, gx + size * ex + 4, gy + size * 0.06], rgba((50, 40, 30)))
    d.arc([gx - size * 0.08, gy + size * 0.04, gx + size * 0.08, gy + size * 0.14], 20, 160, fill=rgba((50, 40, 30)), width=max(2, size // 80))
    return soft_shadow(im, 8, 55, 4)


def wedding_rings(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    gold = (220, 175, 55)
    dark = (170, 125, 30)
    w = max(5, size // 40)
    # left ring
    d.ellipse([size * 0.18, size * 0.32, size * 0.58, size * 0.72], outline=rgba(gold), width=w)
    d.ellipse([size * 0.22, size * 0.36, size * 0.54, size * 0.68], outline=rgba(dark), width=max(2, size // 100))
    # right ring (overlapping)
    d.ellipse([size * 0.42, size * 0.28, size * 0.82, size * 0.68], outline=rgba((235, 195, 70)), width=w)
    # small heart
    hx, hy = size * 0.5, size * 0.22
    hs = size * 0.06
    pts = []
    for t in np.linspace(0, 2 * math.pi, 60):
        x = 16 * math.sin(t) ** 3
        y = -(13 * math.cos(t) - 5 * math.cos(2 * t) - 2 * math.cos(3 * t) - math.cos(4 * t))
        pts.append((hx + x * hs / 16, hy + y * hs / 16))
    d.polygon(pts, fill=rgba((230, 80, 100)))
    return soft_shadow(im, 8, 50, 3)


def fireworks(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    bursts = [
        (0.32, 0.35, (240, 90, 110), 0.22),
        (0.68, 0.3, (255, 190, 60), 0.2),
        (0.5, 0.58, (90, 170, 230), 0.24),
        (0.28, 0.65, (180, 120, 230), 0.16),
        (0.72, 0.62, (100, 210, 130), 0.18),
    ]
    for bx, by, col, rad in bursts:
        cx, cy = size * bx, size * by
        soft_ellipse(d, [cx - size * 0.03, cy - size * 0.03, cx + size * 0.03, cy + size * 0.03], rgba(col))
        for i in range(10):
            ang = math.radians(i * 36 + (bx * 40))
            x2 = cx + size * rad * math.cos(ang)
            y2 = cy + size * rad * math.sin(ang)
            d.line([(cx, cy), (x2, y2)], fill=rgba(col, 220), width=max(2, size // 90))
            soft_ellipse(d, [x2 - 3, y2 - 3, x2 + 3, y2 + 3], rgba(col))
    return soft_shadow(im, 6, 40, 2)


def cherry_blossom(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # branch
    d.line([(size * 0.12, size * 0.7), (size * 0.55, size * 0.4), (size * 0.88, size * 0.28)], fill=rgba((120, 80, 55)), width=max(4, size // 55))
    d.line([(size * 0.55, size * 0.4), (size * 0.48, size * 0.18)], fill=rgba((120, 80, 55)), width=max(3, size // 70))

    def blossom(ox, oy, sc=1.0):
        for i in range(5):
            ang = math.radians(i * 72 - 90)
            px = ox + size * 0.055 * sc * math.cos(ang)
            py = oy + size * 0.055 * sc * math.sin(ang)
            soft_ellipse(d, [px - size * 0.045 * sc, py - size * 0.04 * sc, px + size * 0.045 * sc, py + size * 0.04 * sc], rgba((255, 180, 200)))
        soft_ellipse(d, [ox - size * 0.025 * sc, oy - size * 0.025 * sc, ox + size * 0.025 * sc, oy + size * 0.025 * sc], rgba((255, 210, 100)))

    for x, y, sc in [(0.35, 0.52, 1.0), (0.52, 0.35, 0.9), (0.7, 0.32, 1.05), (0.48, 0.2, 0.75), (0.78, 0.42, 0.8)]:
        blossom(size * x, size * y, sc)
    return soft_shadow(im, 8, 50, 3)


def summer_beach(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # sun
    soft_ellipse(d, [size * 0.58, size * 0.12, size * 0.88, size * 0.42], rgba((255, 200, 60)))
    soft_ellipse(d, [size * 0.64, size * 0.18, size * 0.82, size * 0.36], rgba((255, 230, 120)))
    # palm trunk
    d.polygon([(size * 0.28, size * 0.78), (size * 0.36, size * 0.78), (size * 0.34, size * 0.38), (size * 0.3, size * 0.38)], fill=rgba((160, 110, 60)))
    # fronds
    for ang, length in [(-50, 0.28), (-20, 0.32), (20, 0.3), (50, 0.26)]:
        rad = math.radians(ang - 90)
        x2 = size * 0.32 + size * length * math.cos(rad)
        y2 = size * 0.38 + size * length * math.sin(rad)
        d.line([(size * 0.32, size * 0.38), (x2, y2)], fill=rgba((50, 140, 70)), width=max(4, size // 50))
        soft_ellipse(d, [x2 - size * 0.06, y2 - size * 0.04, x2 + size * 0.06, y2 + size * 0.04], rgba((70, 170, 80)))
    # waves
    for i, y in enumerate((0.72, 0.8, 0.88)):
        col = (70, 160, 220) if i % 2 == 0 else (100, 190, 235)
        d.arc([size * 0.05, size * (y - 0.08), size * 0.95, size * (y + 0.08)], 200, 340, fill=rgba(col), width=max(3, size // 60))
    return soft_shadow(im, 8, 50, 3)


def maple_leaves(size: int) -> Image.Image:
    im = canvas(size)

    def maple(cx, cy, sc, col, rot):
        layer = canvas(size)
        ld = ImageDraw.Draw(layer)
        pts = []
        for i in range(5):
            ang = math.radians(i * 72 - 90)
            tip = (cx + size * 0.22 * sc * math.cos(ang), cy + size * 0.22 * sc * math.sin(ang))
            pts.append(tip)
            a1 = ang + math.radians(20)
            a2 = ang - math.radians(20)
            pts.append((cx + size * 0.1 * sc * math.cos(a1), cy + size * 0.1 * sc * math.sin(a1)))
            pts.append((cx + size * 0.1 * sc * math.cos(a2), cy + size * 0.1 * sc * math.sin(a2)))
        ld.polygon(pts, fill=rgba(col))
        ld.line([(cx, cy), (cx, cy + size * 0.18 * sc)], fill=rgba(mix(col, (80, 30, 10), 0.4)), width=max(2, size // 90))
        return layer.rotate(rot, resample=Image.BICUBIC, center=(cx, cy))

    for cx, cy, sc, col, rot in [
        (size * 0.38, size * 0.42, 0.95, (220, 90, 40), -25),
        (size * 0.62, size * 0.4, 1.0, (230, 140, 40), 20),
        (size * 0.5, size * 0.62, 0.8, (200, 60, 40), 5),
    ]:
        im.alpha_composite(maple(cx, cy, sc, col, rot))
    return soft_shadow(im, 8, 55, 4)


def snowman(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # flakes
    for fx, fy, r in [(0.15, 0.2, 0.02), (0.85, 0.25, 0.025), (0.2, 0.7, 0.018), (0.8, 0.65, 0.022), (0.12, 0.45, 0.015), (0.88, 0.48, 0.02)]:
        soft_ellipse(d, [size * fx - size * r, size * fy - size * r, size * fx + size * r, size * fy + size * r], rgba((180, 220, 250)))
    # body
    soft_ellipse(d, [size * 0.28, size * 0.48, size * 0.72, size * 0.88], rgba((245, 250, 255)), rgba((180, 200, 220)), 2)
    soft_ellipse(d, [size * 0.34, size * 0.28, size * 0.66, size * 0.58], rgba((245, 250, 255)))
    soft_ellipse(d, [size * 0.38, size * 0.12, size * 0.62, size * 0.36], rgba((245, 250, 255)))
    # eyes / smile / nose
    for ex in (-0.06, 0.06):
        soft_ellipse(d, [size * (0.5 + ex) - 4, size * 0.22, size * (0.5 + ex) + 4, size * 0.28], rgba((50, 45, 40)))
    d.polygon([(size * 0.5, size * 0.26), (size * 0.62, size * 0.28), (size * 0.5, size * 0.3)], fill=rgba((240, 130, 50)))
    d.arc([size * 0.42, size * 0.26, size * 0.58, size * 0.34], 20, 160, fill=rgba((50, 45, 40)), width=max(2, size // 80))
    # scarf
    d.rounded_rectangle([size * 0.36, size * 0.34, size * 0.64, size * 0.42], radius=4, fill=rgba((220, 70, 70)))
    d.rectangle([size * 0.54, size * 0.4, size * 0.62, size * 0.55], fill=rgba((220, 70, 70)))
    # buttons
    for y in (0.55, 0.65):
        soft_ellipse(d, [size * 0.48, size * y, size * 0.52, size * (y + 0.04)], rgba((40, 40, 40)))
    return soft_shadow(im, 8, 50, 3)


def tulips(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    colors = [(230, 70, 100), (255, 190, 50), (150, 100, 200)]
    for i, (ox, col) in enumerate([(-0.18, colors[0]), (0.0, colors[1]), (0.18, colors[2])]):
        sx = size * (0.5 + ox)
        d.line([(sx, size * 0.82), (sx, size * 0.42)], fill=rgba((60, 140, 60)), width=max(3, size // 70))
        soft_ellipse(d, [sx - size * 0.08, size * 0.55, sx - size * 0.02, size * 0.68], rgba((70, 160, 70)))
        # tulip bloom
        soft_ellipse(d, [sx - size * 0.1, size * 0.28, sx + size * 0.1, size * 0.48], rgba(col))
        d.polygon([(sx - size * 0.1, size * 0.36), (sx, size * 0.2), (sx + size * 0.1, size * 0.36)], fill=rgba(col))
        soft_ellipse(d, [sx - size * 0.03, size * 0.32, sx + size * 0.03, size * 0.42], rgba(mix(col, (255, 255, 255), 0.25)))
    return soft_shadow(im, 8, 55, 4)


def coffee_cup(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # cup body
    d.polygon([
        (size * 0.28, size * 0.38), (size * 0.72, size * 0.38),
        (size * 0.66, size * 0.78), (size * 0.34, size * 0.78),
    ], fill=rgba((245, 245, 250)), outline=rgba((160, 160, 170)), width=2)
    soft_ellipse(d, [size * 0.28, size * 0.32, size * 0.72, size * 0.44], rgba((90, 55, 30)))
    soft_ellipse(d, [size * 0.32, size * 0.34, size * 0.68, size * 0.42], rgba((120, 75, 40)))
    # handle
    d.arc([size * 0.66, size * 0.44, size * 0.86, size * 0.68], 280, 90, fill=rgba((160, 160, 170)), width=max(4, size // 55))
    # steam
    for ox, top in [(-0.08, 0.14), (0.0, 0.1), (0.08, 0.16)]:
        d.arc([size * (0.45 + ox), size * top, size * (0.55 + ox), size * 0.34], 200, 340, fill=rgba((180, 190, 200), 180), width=max(2, size // 90))
    return soft_shadow(im, 8, 55, 4)


def teapot(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    cx = size / 2
    # body
    soft_ellipse(d, [size * 0.22, size * 0.38, size * 0.72, size * 0.78], rgba((250, 250, 248)), rgba((180, 185, 180)), max(2, size // 90))
    # lid
    soft_ellipse(d, [size * 0.32, size * 0.3, size * 0.62, size * 0.44], rgba((245, 245, 242)), rgba((170, 175, 170)), 2)
    soft_ellipse(d, [cx - size * 0.04, size * 0.24, cx + size * 0.04, size * 0.34], rgba((200, 205, 200)))
    # spout
    d.polygon([(size * 0.68, size * 0.48), (size * 0.88, size * 0.4), (size * 0.86, size * 0.48), (size * 0.7, size * 0.58)], fill=rgba((250, 250, 248)), outline=rgba((180, 185, 180)))
    # handle
    d.arc([size * 0.08, size * 0.42, size * 0.32, size * 0.7], 90, 270, fill=rgba((170, 175, 170)), width=max(4, size // 55))
    # green sprig
    d.line([(cx, size * 0.22), (cx + size * 0.08, size * 0.1)], fill=rgba((70, 140, 60)), width=max(2, size // 100))
    soft_ellipse(d, [cx + size * 0.02, size * 0.08, cx + size * 0.12, size * 0.18], rgba((80, 160, 70)))
    soft_ellipse(d, [cx - size * 0.04, size * 0.12, cx + size * 0.04, size * 0.2], rgba((70, 150, 65)))
    return soft_shadow(im, 8, 55, 4)


def soap_bar(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    d.rounded_rectangle([size * 0.22, size * 0.38, size * 0.78, size * 0.72], radius=14, fill=rgba((180, 220, 230)), outline=rgba((120, 170, 185)), width=2)
    soft_ellipse(d, [size * 0.3, size * 0.42, size * 0.5, size * 0.52], rgba((255, 255, 255), 120))
    # leaves
    soft_ellipse(d, [size * 0.28, size * 0.22, size * 0.42, size * 0.42], rgba((80, 160, 80)))
    soft_ellipse(d, [size * 0.4, size * 0.18, size * 0.58, size * 0.38], rgba((100, 180, 90)))
    soft_ellipse(d, [size * 0.55, size * 0.24, size * 0.7, size * 0.4], rgba((70, 150, 70)))
    return soft_shadow(im, 8, 55, 4)


def pot_plant(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # pot
    d.polygon([
        (size * 0.3, size * 0.58), (size * 0.7, size * 0.58),
        (size * 0.64, size * 0.86), (size * 0.36, size * 0.86),
    ], fill=rgba((200, 120, 80)), outline=rgba((150, 80, 50)), width=2)
    d.rounded_rectangle([size * 0.26, size * 0.52, size * 0.74, size * 0.62], radius=4, fill=rgba((220, 140, 95)))
    # snake plant leaves
    for ox, h, lean, col in [
        (-0.1, 0.42, -12, (50, 120, 70)),
        (-0.02, 0.5, 5, (60, 140, 80)),
        (0.08, 0.45, 15, (45, 110, 65)),
        (0.0, 0.38, -5, (70, 150, 85)),
    ]:
        layer = canvas(size)
        ld = ImageDraw.Draw(layer)
        lx = size * (0.5 + ox)
        ld.polygon([
            (lx - size * 0.05, size * 0.58),
            (lx + size * 0.05, size * 0.58),
            (lx + size * 0.02, size * (0.58 - h)),
            (lx - size * 0.02, size * (0.58 - h)),
        ], fill=rgba(col))
        # stripe
        ld.line([(lx, size * 0.56), (lx, size * (0.58 - h + 0.04))], fill=rgba(mix(col, (200, 230, 160), 0.45)), width=max(2, size // 100))
        layer = layer.rotate(lean, resample=Image.BICUBIC, center=(lx, size * 0.58))
        im.alpha_composite(layer)
    return soft_shadow(im, 8, 55, 4)


def yellow_flowers(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # leaves behind
    for ox, oy in [(0.28, 0.55), (0.7, 0.58), (0.48, 0.7)]:
        soft_ellipse(d, [size * ox - size * 0.1, size * oy - size * 0.06, size * ox + size * 0.1, size * oy + size * 0.06], rgba((70, 150, 70)))
    for x, y, sc in [(0.35, 0.38, 1.0), (0.55, 0.3, 1.1), (0.68, 0.45, 0.9), (0.45, 0.52, 0.85)]:
        for i in range(6):
            ang = math.radians(i * 60)
            px = size * x + size * 0.07 * sc * math.cos(ang)
            py = size * y + size * 0.07 * sc * math.sin(ang)
            soft_ellipse(d, [px - size * 0.045 * sc, py - size * 0.04 * sc, px + size * 0.045 * sc, py + size * 0.04 * sc], rgba((255, 210, 60)))
        soft_ellipse(d, [size * x - size * 0.03 * sc, size * y - size * 0.03 * sc, size * x + size * 0.03 * sc, size * y + size * 0.03 * sc], rgba((240, 150, 40)))
    return soft_shadow(im, 8, 55, 4)


def veggies_mix(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # tomato
    soft_ellipse(d, [size * 0.18, size * 0.28, size * 0.48, size * 0.58], rgba((220, 60, 55)))
    soft_ellipse(d, [size * 0.28, size * 0.24, size * 0.4, size * 0.34], rgba((60, 140, 60)))
    # broccoli
    soft_ellipse(d, [size * 0.48, size * 0.22, size * 0.78, size * 0.5], rgba((70, 150, 70)))
    soft_ellipse(d, [size * 0.52, size * 0.18, size * 0.66, size * 0.32], rgba((90, 170, 85)))
    soft_ellipse(d, [size * 0.62, size * 0.2, size * 0.76, size * 0.34], rgba((55, 130, 60)))
    d.rectangle([size * 0.58, size * 0.45, size * 0.68, size * 0.62], fill=rgba((180, 200, 90)))
    # carrot
    d.polygon([(size * 0.35, size * 0.55), (size * 0.55, size * 0.55), (size * 0.45, size * 0.88)], fill=rgba((240, 130, 40)))
    for i, ox in enumerate((-0.04, 0.0, 0.04)):
        d.line([(size * (0.45 + ox), size * 0.55), (size * (0.42 + ox * 2), size * 0.42)], fill=rgba((60, 150, 60)), width=max(2, size // 90))
    return soft_shadow(im, 8, 55, 4)


def skin_profile(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # woman profile silhouette (facing right)
    cx, cy = size * 0.42, size * 0.5
    pts = [
        (cx - size * 0.08, cy - size * 0.32),
        (cx + size * 0.12, cy - size * 0.3),
        (cx + size * 0.18, cy - size * 0.18),  # forehead
        (cx + size * 0.2, cy - size * 0.08),   # nose tip
        (cx + size * 0.14, cy - size * 0.02),
        (cx + size * 0.16, cy + size * 0.04),  # lips
        (cx + size * 0.12, cy + size * 0.1),
        (cx + size * 0.08, cy + size * 0.22),   # chin/neck
        (cx - size * 0.02, cy + size * 0.35),
        (cx - size * 0.18, cy + size * 0.32),
        (cx - size * 0.22, cy - size * 0.05),  # hair back
        (cx - size * 0.16, cy - size * 0.28),
    ]
    d.polygon(pts, fill=rgba((90, 70, 80)))
    # leaf accent
    soft_ellipse(d, [size * 0.62, size * 0.42, size * 0.82, size * 0.62], rgba((100, 170, 90)))
    d.line([(size * 0.72, size * 0.58), (size * 0.72, size * 0.44)], fill=rgba((50, 120, 50)), width=max(2, size // 100))
    return soft_shadow(im, 8, 50, 3)


def lily_bouquet(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    # stems
    for ox in (-0.08, 0.0, 0.08):
        d.line([(size * (0.5 + ox * 0.4), size * 0.85), (size * (0.5 + ox), size * 0.4)], fill=rgba((70, 140, 60)), width=max(2, size // 90))
    # wrap
    d.polygon([(size * 0.38, size * 0.55), (size * 0.62, size * 0.55), (size * 0.56, size * 0.82), (size * 0.44, size * 0.82)], fill=rgba((230, 240, 230)))
    # white lily-like blooms
    for x, y, sc in [(0.38, 0.36, 0.9), (0.5, 0.28, 1.05), (0.62, 0.36, 0.9), (0.45, 0.44, 0.75), (0.55, 0.44, 0.75)]:
        for i in range(6):
            ang = math.radians(i * 60 - 90)
            px = size * x + size * 0.07 * sc * math.cos(ang)
            py = size * y + size * 0.07 * sc * math.sin(ang)
            soft_ellipse(d, [px - size * 0.04 * sc, py - size * 0.05 * sc, px + size * 0.04 * sc, py + size * 0.05 * sc], rgba((250, 250, 248)))
        soft_ellipse(d, [size * x - 4, size * y - 4, size * x + 4, size * y + 4], rgba((255, 220, 100)))
    return soft_shadow(im, 8, 55, 4)


def paw(size: int) -> Image.Image:
    im = canvas(size)
    d = ImageDraw.Draw(im)
    col = (90, 70, 55)

    def one_paw(ox, oy, sc=1.0):
        # pad
        soft_ellipse(d, [ox - size * 0.12 * sc, oy - size * 0.08 * sc, ox + size * 0.12 * sc, oy + size * 0.12 * sc], rgba(col))
        # toes
        for tx, ty in [(-0.1, -0.14), (-0.03, -0.18), (0.04, -0.18), (0.11, -0.14)]:
            soft_ellipse(
                d,
                [ox + size * tx * sc - size * 0.045 * sc, oy + size * ty * sc - size * 0.045 * sc,
                 ox + size * tx * sc + size * 0.045 * sc, oy + size * ty * sc + size * 0.045 * sc],
                rgba(col),
            )

    one_paw(size * 0.34, size * 0.42, 0.95)
    one_paw(size * 0.66, size * 0.58, 1.0)
    return soft_shadow(im, 8, 55, 4)


ARTISTS: dict[str, Callable[[int], Image.Image]] = {
    "smile": smile,
    "heart": heart,
    "leaf": leaf,
    "olive": olive_branch,
    "coffee": coffee_beans,
    "bee": bee,
    "strawberry": strawberry,
    "croissant": croissant,
    "cake": cake,
    "bow": bow,
    "clover": clover,
    "tree_xmas": tree_xmas,
    "pumpkin": pumpkin,
    "apple": apple,
    "tomato": tomato,
    "veggies": veggies,
    "cow": cow,
    "cookie": cookie,
    "macaron": macaron,
    "salad": salad,
    "utensils": utensils,
    "kimchi": kimchi_bowl,
    "honey_dipper": honey_dipper,
    "recycle": recycle,
    "fragile": fragile_glass,
    "arrows_up": arrows_up,
    "umbrella": umbrella,
    "caution": caution,
    "hands": hands_care,
    "truck": truck,
    "envelope": envelope,
    "crown": crown,
    "starburst": starburst,
    "wreath": wreath,
    "floral": floral_corner,
    "snow": snow_forest,
    "handmade": handmade_wreath,
    "laurel_heart": laurel_heart,
    "barcode": barcode_art,
    "qr": qr_art,
    "orange": orange,
    "lemon": lemon,
    "bear": bear,
    "house": house,
    "sun_leaves": sun_leaves,
    "lavender": lavender,
    "flower_pink": flower_pink,
    "flower_white": flower_white,
    "candle": candle,
    "diffuser": diffuser,
    "dog": dog,
    "cat": cat,
    "snowflake": snowflake,
    "thermometer": thermometer,
    "premium_seal": premium_seal,
    "confetti": confetti,
    "scissors": scissors,
    "bouquet": bouquet,
    "carnation": carnation,
    "wedding_flowers": wedding_flowers,
    "leaves_sprig": leaves_sprig,
    "kraft_circle": kraft_circle,
    "cart": cart,
    "baby": baby,
    "microwave": microwave,
    "oven": oven,
    "dishwasher": dishwasher,
    "bpa_free": bpa_free,
    "gluten_free": gluten_free,
    "non_gmo": non_gmo,
    "kids": kids,
    "wedding_rings": wedding_rings,
    "fireworks": fireworks,
    "cherry_blossom": cherry_blossom,
    "summer_beach": summer_beach,
    "maple_leaves": maple_leaves,
    "snowman": snowman,
    "tulips": tulips,
    "coffee_cup": coffee_cup,
    "teapot": teapot,
    "soap_bar": soap_bar,
    "pot_plant": pot_plant,
    "yellow_flowers": yellow_flowers,
    "veggies_mix": veggies_mix,
    "skin_profile": skin_profile,
    "lily_bouquet": lily_bouquet,
    "paw": paw,
}


def render_art(kind: str, size: int = 768) -> Image.Image:
    fn = ARTISTS.get(kind)
    if not fn:
        im = canvas(size)
        d = ImageDraw.Draw(im)
        soft_ellipse(d, [size * 0.2, size * 0.2, size * 0.8, size * 0.8], rgba((200, 200, 200)))
        return im
    # supersample
    hi = size * 2
    art = fn(hi)
    return art.resize((size, size), Image.LANCZOS)
