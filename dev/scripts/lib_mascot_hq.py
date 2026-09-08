#!/usr/bin/env python3
"""High-quality chibi mascot renderer (Kakao/LINE Friends sticker style).

Uses 2x supersampling, soft gradients, sausage limbs (no puppet joints),
blurred blush, and clean outlines.
"""
from __future__ import annotations

import math
from typing import Any

import numpy as np
from PIL import Image, ImageDraw, ImageFilter


def rgba(c: tuple[int, int, int], a: int = 255) -> tuple[int, int, int, int]:
    return (c[0], c[1], c[2], a)


def mix(a: tuple[int, int, int], b: tuple[int, int, int], t: float) -> tuple[int, int, int]:
    return (
        int(a[0] + (b[0] - a[0]) * t),
        int(a[1] + (b[1] - a[1]) * t),
        int(a[2] + (b[2] - a[2]) * t),
    )


def darker(c: tuple[int, int, int], amount: float = 0.28) -> tuple[int, int, int]:
    return mix(c, (40, 30, 25), amount)


def lighter(c: tuple[int, int, int], amount: float = 0.35) -> tuple[int, int, int]:
    return mix(c, (255, 255, 255), amount)


def radial_ellipse(
    size: int,
    cx: float,
    cy: float,
    rx: float,
    ry: float,
    inner: tuple[int, int, int],
    outer: tuple[int, int, int],
    highlight: tuple[int, int, int] | None = None,
) -> tuple[Image.Image, int, int]:
    """Soft shaded ellipse as a small crop; returns (img, paste_x, paste_y)."""
    pad = 2
    w = max(4, int(math.ceil(rx * 2)) + pad * 2)
    h = max(4, int(math.ceil(ry * 2)) + pad * 2)
    lcx, lcy = rx + pad, ry + pad
    yy, xx = np.mgrid[0:h, 0:w].astype(np.float32)
    nx = (xx - lcx) / max(rx, 1.0)
    ny = (yy - lcy) / max(ry, 1.0)
    dist = np.sqrt(nx * nx + ny * ny)
    mask = dist <= 1.0
    hx = (xx - (lcx - rx * 0.25)) / max(rx * 1.2, 1.0)
    hy = (yy - (lcy - ry * 0.35)) / max(ry * 1.2, 1.0)
    hdist = np.clip(np.sqrt(hx * hx + hy * hy), 0, 1)

    out = np.zeros((h, w, 4), dtype=np.float32)
    for i in range(3):
        base = outer[i] + (inner[i] - outer[i]) * (1.0 - dist)
        if highlight is not None:
            base = base + (highlight[i] - base) * (1.0 - hdist) * 0.45
        out[..., i] = np.clip(base, 0, 255)
    edge = np.clip((1.03 - dist) / 0.05, 0, 1)
    out[..., 3] = np.where(mask, 255.0 * edge, 0.0)
    img = Image.fromarray(out.astype(np.uint8), "RGBA")
    px = int(round(cx - lcx))
    py = int(round(cy - lcy))
    return img, px, py


def paste_radial(
    base: Image.Image,
    cx: float,
    cy: float,
    rx: float,
    ry: float,
    inner: tuple[int, int, int],
    outer: tuple[int, int, int],
    highlight: tuple[int, int, int] | None = None,
) -> None:
    img, px, py = radial_ellipse(base.size[0], cx, cy, rx, ry, inner, outer, highlight)
    base.alpha_composite(img, (px, py))


def outline_ellipse(layer: Image.Image, cx, cy, rx, ry, color, width: int) -> None:
    d = ImageDraw.Draw(layer)
    for w in range(width, 0, -1):
        a = 255 if w == width else 90
        d.ellipse([cx - rx - w, cy - ry - w, cx + rx + w, cy + ry + w], outline=rgba(color, a), width=1)


def sausage(
    layer: Image.Image,
    x0: float,
    y0: float,
    x1: float,
    y1: float,
    radius: float,
    fill: tuple[int, int, int],
    outline: tuple[int, int, int],
) -> None:
    """Short stubby limb — filled capsule only, soft rim outline."""
    d = ImageDraw.Draw(layer)
    dx, dy = x1 - x0, y1 - y0
    length = math.hypot(dx, dy) or 1.0
    ux, uy = dx / length, dy / length
    px, py = -uy, ux
    r = max(8.0, float(radius))
    ro = r + 2.8

    def pts(rad: float) -> list[tuple[float, float]]:
        return [
            (x0 + px * rad, y0 + py * rad),
            (x1 + px * rad, y1 + py * rad),
            (x1 - px * rad, y1 - py * rad),
            (x0 - px * rad, y0 - py * rad),
        ]

    d.polygon(pts(ro), fill=rgba(outline))
    d.ellipse([x0 - ro, y0 - ro, x0 + ro, y0 + ro], fill=rgba(outline))
    d.ellipse([x1 - ro * 1.05, y1 - ro * 1.05, x1 + ro * 1.05, y1 + ro * 1.05], fill=rgba(outline))
    d.polygon(pts(r), fill=rgba(fill))
    d.ellipse([x0 - r, y0 - r, x0 + r, y0 + r], fill=rgba(fill))
    d.ellipse([x1 - r * 1.08, y1 - r * 1.08, x1 + r * 1.08, y1 + r * 1.08], fill=rgba(fill))
    # tiny paw highlight (same-family color, not grey pad)
    d.ellipse(
        [x1 - r * 0.4, y1 - r * 0.5, x1 + r * 0.2, y1 - r * 0.05],
        fill=rgba(lighter(fill, 0.45)),
    )


def soft_blush(base: Image.Image, cx: float, cy: float, rx: float, ry: float, color: tuple[int, int, int]) -> None:
    img, px, py = radial_ellipse(base.size[0], cx, cy, rx, ry, lighter(color, 0.15), color)
    arr = np.array(img)
    arr[..., 3] = (arr[..., 3].astype(np.float32) * 0.55).astype(np.uint8)
    img = Image.fromarray(arr, "RGBA").filter(ImageFilter.GaussianBlur(max(2, int(rx * 0.15))))
    base.alpha_composite(img, (px, py))


def paste(base: Image.Image, overlay: Image.Image, ox: int = 0, oy: int = 0) -> None:
    base.alpha_composite(overlay, (ox, oy))


def star_poly(cx: float, cy: float, r: float) -> list[tuple[float, float]]:
    pts = []
    for i in range(10):
        ang = math.radians(-90 + i * 36)
        rr = r if i % 2 == 0 else r * 0.42
        pts.append((cx + rr * math.cos(ang), cy + rr * math.sin(ang)))
    return pts


# ---------- character definitions ----------

FRIENDS: dict[str, dict[str, Any]] = {
    "hoya": {
        "name": "호야",
        "kind": "bear",
        "body": (255, 208, 72),
        "shadow": (230, 170, 40),
        "belly": (255, 236, 160),
        "blush": (255, 140, 150),
        "accent": (255, 110, 90),
        "outline": (95, 65, 35),
    },
    "boksoong": {
        "name": "복숭",
        "kind": "peach",
        "body": (255, 168, 186),
        "shadow": (235, 125, 150),
        "belly": (255, 220, 225),
        "blush": (255, 105, 135),
        "accent": (255, 90, 120),
        "outline": (110, 55, 70),
    },
    "mocha": {
        "name": "모카",
        "kind": "bear",
        "body": (156, 108, 78),
        "shadow": (120, 78, 52),
        "belly": (210, 170, 135),
        "blush": (235, 135, 125),
        "accent": (255, 170, 90),
        "outline": (55, 35, 28),
    },
    "torong": {
        "name": "토롱",
        "kind": "bunny",
        "body": (255, 246, 242),
        "shadow": (235, 215, 208),
        "belly": (255, 255, 255),
        "blush": (255, 155, 175),
        "accent": (255, 130, 160),
        "outline": (100, 70, 75),
    },
    "bbiya": {
        "name": "삐야",
        "kind": "chick",
        "body": (255, 218, 64),
        "shadow": (235, 185, 35),
        "belly": (255, 242, 150),
        "blush": (255, 145, 130),
        "accent": (255, 140, 70),
        "outline": (95, 65, 30),
    },
}

CHARS_V1: dict[str, dict[str, Any]] = {
    "mochi": {
        "name": "모찌",
        "kind": "bunny",
        "body": (255, 236, 220),
        "shadow": (240, 205, 185),
        "belly": (255, 250, 245),
        "blush": (255, 150, 170),
        "accent": (255, 130, 150),
        "outline": (100, 70, 75),
    },
    "kong": {
        "name": "콩이",
        "kind": "bear",
        "body": (255, 190, 110),
        "shadow": (230, 150, 75),
        "belly": (255, 230, 185),
        "blush": (255, 130, 120),
        "accent": (255, 115, 85),
        "outline": (95, 60, 40),
    },
    "byeol": {
        "name": "별이",
        "kind": "cat",
        "body": (205, 190, 245),
        "shadow": (165, 145, 215),
        "belly": (235, 225, 255),
        "blush": (255, 155, 195),
        "accent": (255, 140, 190),
        "outline": (75, 55, 110),
    },
}


FRIEND_POSES: list[tuple[str, str]] = [
    ("hello", "안녕"), ("smile", "방긋"), ("laugh", "킥킥"), ("wink", "윙크"),
    ("love", "사랑해"), ("heart", "하트뿅"), ("blush", "새침"), ("shy", "수줍"),
    ("surprise", "깜짝"), ("wow", "대박"), ("cry", "흑흑"), ("sad", "시무룩"),
    ("angry", "화남"), ("pout", "뾰로통"), ("sleepy", "졸려"), ("sleep", "쿨쿨"),
    ("yawn", "하품"), ("think", "음…"), ("ok", "OK"), ("thumbs", "최고"),
    ("wave", "손흔들"), ("cheer", "만세"), ("jump", "폴짝"), ("dance", "춤춰"),
    ("run", "달려"), ("sit", "쪼그려"), ("peek", "빼꼼"), ("hug", "꼭안아"),
    ("kiss", "쪽"), ("eat", "냠냠"), ("drink", "쪽쪽"), ("coffee", "커피타임"),
    ("cake", "축하해"), ("gift", "선물이야"), ("flower", "꽃선물"), ("rain", "비와요"),
    ("sun", "날씨좋아"), ("work", "열심"), ("phone", "전화중"), ("bye", "잘가"),
]

CHAR_POSES: list[tuple[str, str]] = [
    ("smile", "방긋 미소"), ("happy", "활짝 웃음"), ("wink", "윙크"), ("love", "하트눈"),
    ("surprise", "깜짝"), ("sleepy", "졸린"), ("cry", "울먹"), ("pout", "뾰로통"),
    ("shy", "수줍은"), ("think", "생각중"), ("wave", "손인사"), ("cheer", "만세"),
    ("jump", "점프"), ("sit", "앉기"), ("run", "달리기"), ("eat", "간식먹기"),
    ("drink", "음료마시기"), ("hug", "하트안기"), ("peek", "빼꼼"), ("dance", "댄스"),
    ("bow", "인사"), ("stretch", "기지개"), ("cold", "추워"), ("hot", "더워"),
    ("cake", "생일케이크"), ("gift", "선물"), ("phone", "전화"), ("read", "독서"),
    ("sleep", "꿀잠"), ("yay", "야호"),
]


def pose_layout(pose: str) -> dict[str, Any]:
    """Chibi blob layout — short stubby limbs like Kakao/LINE stickers."""
    base: dict[str, Any] = {
        "expr": "smile",
        "body_cy": 0.60,
        "head_cy": 0.38,
        # short stubs from shoulder/hip
        "arm_l": (-0.14, 0.55, -0.22, 0.62),
        "arm_r": (0.14, 0.55, 0.22, 0.62),
        "leg_l": (-0.07, 0.72, -0.09, 0.82),
        "leg_r": (0.07, 0.72, 0.09, 0.82),
        "tilt": 0,
        "hide_legs": False,
        "arm_r_w": 0.048,
        "leg_r_w": 0.052,
    }
    alias = {
        "happy": "laugh", "yay": "cheer", "bow": "shy", "stretch": "yawn",
        "cold": "pout", "hot": "surprise", "read": "think",
    }
    pose = alias.get(pose, pose)
    overrides = {
        "hello": {"expr": "laugh", "arm_r": (0.14, 0.52, 0.24, 0.40)},
        "smile": {"expr": "smile"},
        "laugh": {"expr": "laugh", "arm_l": (-0.16, 0.54, -0.22, 0.48), "arm_r": (0.16, 0.54, 0.22, 0.48)},
        "wink": {"expr": "wink"},
        "love": {"expr": "love", "arm_l": (-0.12, 0.56, -0.04, 0.62), "arm_r": (0.12, 0.56, 0.04, 0.62)},
        "heart": {"expr": "love", "arm_r": (0.14, 0.54, 0.24, 0.58)},
        "blush": {"expr": "shy", "arm_l": (-0.12, 0.55, -0.08, 0.48), "arm_r": (0.12, 0.55, 0.08, 0.48)},
        "shy": {"expr": "shy", "arm_l": (-0.12, 0.55, -0.08, 0.48), "arm_r": (0.12, 0.55, 0.08, 0.48)},
        "surprise": {"expr": "surprise", "arm_l": (-0.16, 0.52, -0.22, 0.42), "arm_r": (0.16, 0.52, 0.22, 0.42)},
        "wow": {"expr": "wow", "arm_l": (-0.16, 0.52, -0.22, 0.42), "arm_r": (0.16, 0.52, 0.22, 0.42)},
        "cry": {"expr": "cry", "arm_l": (-0.12, 0.55, -0.08, 0.48), "arm_r": (0.12, 0.55, 0.08, 0.48)},
        "sad": {"expr": "sad", "tilt": -3},
        "angry": {"expr": "angry", "arm_l": (-0.16, 0.56, -0.22, 0.64), "arm_r": (0.16, 0.56, 0.22, 0.64)},
        "pout": {"expr": "pout", "arm_l": (-0.12, 0.58, -0.05, 0.64), "arm_r": (0.12, 0.58, 0.05, 0.64)},
        "sleepy": {"expr": "sleepy", "tilt": -4},
        "sleep": {"expr": "sleep", "tilt": -14, "body_cy": 0.64, "head_cy": 0.48, "hide_legs": True,
                  "arm_l": (-0.10, 0.62, -0.04, 0.66), "arm_r": (0.12, 0.60, 0.18, 0.66)},
        "yawn": {"expr": "yawn", "arm_l": (-0.15, 0.52, -0.20, 0.42)},
        "think": {"expr": "think", "arm_l": (-0.12, 0.55, -0.05, 0.48)},
        "ok": {"expr": "laugh", "arm_r": (0.15, 0.52, 0.23, 0.42)},
        "thumbs": {"expr": "laugh", "arm_r": (0.15, 0.50, 0.22, 0.40)},
        "wave": {"expr": "laugh", "arm_r": (0.15, 0.50, 0.25, 0.36)},
        "cheer": {"expr": "laugh", "arm_l": (-0.16, 0.50, -0.20, 0.36), "arm_r": (0.16, 0.50, 0.20, 0.36)},
        "jump": {"expr": "laugh", "body_cy": 0.52, "head_cy": 0.30,
                 "leg_l": (-0.08, 0.66, -0.12, 0.74), "leg_r": (0.08, 0.66, 0.12, 0.74),
                 "arm_l": (-0.16, 0.48, -0.22, 0.38), "arm_r": (0.16, 0.48, 0.22, 0.38)},
        "dance": {"expr": "laugh", "tilt": -6, "arm_l": (-0.18, 0.50, -0.24, 0.42), "arm_r": (0.14, 0.52, 0.10, 0.40),
                  "leg_l": (-0.04, 0.72, 0.02, 0.82), "leg_r": (0.09, 0.70, 0.14, 0.80)},
        "run": {"expr": "laugh", "tilt": 7, "arm_l": (-0.10, 0.52, -0.04, 0.46), "arm_r": (0.14, 0.54, 0.22, 0.62),
                "leg_l": (-0.05, 0.70, -0.12, 0.80), "leg_r": (0.07, 0.70, 0.13, 0.78)},
        "sit": {"expr": "smile", "body_cy": 0.64, "head_cy": 0.42, "hide_legs": True,
                "arm_l": (-0.15, 0.62, -0.20, 0.70), "arm_r": (0.15, 0.62, 0.20, 0.70)},
        "peek": {"expr": "wink", "body_cy": 0.60, "head_cy": 0.40, "hide_legs": True,
                 "arm_l": (-0.08, 0.56, -0.02, 0.50), "arm_r": (0.05, 0.56, 0.02, 0.50)},
        "hug": {"expr": "love", "arm_l": (-0.10, 0.56, -0.02, 0.60), "arm_r": (0.10, 0.56, 0.02, 0.60)},
        "kiss": {"expr": "kiss", "arm_r": (0.14, 0.54, 0.23, 0.58)},
        "eat": {"expr": "laugh", "arm_r": (0.13, 0.55, 0.22, 0.58)},
        "drink": {"expr": "smile", "arm_r": (0.13, 0.54, 0.22, 0.57)},
        "coffee": {"expr": "smile", "arm_r": (0.13, 0.54, 0.22, 0.57)},
        "cake": {"expr": "laugh", "arm_r": (0.13, 0.55, 0.22, 0.60)},
        "gift": {"expr": "love", "arm_r": (0.12, 0.56, 0.22, 0.64), "arm_l": (-0.12, 0.56, -0.04, 0.62)},
        "flower": {"expr": "shy", "arm_r": (0.14, 0.52, 0.23, 0.44)},
        "rain": {"expr": "sad"},
        "sun": {"expr": "laugh", "arm_l": (-0.16, 0.50, -0.20, 0.38), "arm_r": (0.16, 0.50, 0.20, 0.38)},
        "work": {"expr": "think", "arm_l": (-0.05, 0.60, 0.05, 0.64), "arm_r": (0.08, 0.60, 0.18, 0.64)},
        "phone": {"expr": "smile", "arm_r": (0.14, 0.50, 0.22, 0.44)},
        "bye": {"expr": "wink", "arm_r": (0.15, 0.50, 0.26, 0.36)},
    }
    base.update(overrides.get(pose, {}))
    return base


def draw_ears(layer: Image.Image, ch: dict, cx: float, cy: float, s: float) -> None:
    d = ImageDraw.Draw(layer)
    kind, body, shadow, ol = ch["kind"], ch["body"], ch["shadow"], ch["outline"]
    if kind == "bear":
        for side in (-1, 1):
            ex, ey = cx + side * 0.155 * s, cy - 0.17 * s
            paste_radial(layer, ex, ey, 0.065 * s, 0.065 * s, lighter(body, 0.1), shadow)
            outline_ellipse(layer, ex, ey, 0.065 * s, 0.065 * s, ol, 3)
            paste_radial(layer, ex, ey, 0.032 * s, 0.032 * s, ch["blush"], mix(ch["blush"], shadow, 0.3))
    elif kind == "bunny":
        for side in (-1, 1):
            tip = (cx + side * 0.08 * s, cy - 0.34 * s)
            b1 = (cx + side * 0.035 * s, cy - 0.14 * s)
            b2 = (cx + side * 0.13 * s, cy - 0.15 * s)
            d.polygon([b1, tip, b2], fill=rgba(body))
            d.line([b1, tip, b2], fill=rgba(ol), width=4)
            d.polygon([
                (cx + side * 0.055 * s, cy - 0.18 * s),
                (tip[0], tip[1] + 0.06 * s),
                (cx + side * 0.105 * s, cy - 0.185 * s),
            ], fill=rgba(ch["blush"]))
    elif kind == "peach":
        for side in (-1, 1):
            ex, ey = cx + side * 0.145 * s, cy - 0.15 * s
            paste_radial(layer, ex, ey, 0.05 * s, 0.058 * s, lighter(body, 0.1), shadow)
            outline_ellipse(layer, ex, ey, 0.05 * s, 0.058 * s, ol, 3)
        # leaf
        d.ellipse([cx - 0.01 * s, cy - 0.27 * s, cx + 0.08 * s, cy - 0.18 * s], fill=rgba((100, 185, 95)), outline=rgba(ol), width=3)
        d.line([(cx + 0.02 * s, cy - 0.22 * s), (cx + 0.05 * s, cy - 0.19 * s)], fill=rgba((60, 130, 70)), width=2)
    elif kind == "chick":
        for dx in (-0.025, 0, 0.025):
            paste_radial(layer, cx + dx * s, cy - 0.20 * s, 0.022 * s, 0.032 * s, lighter(ch["accent"], 0.2), ch["accent"])
            outline_ellipse(layer, cx + dx * s, cy - 0.20 * s, 0.022 * s, 0.032 * s, ol, 2)
    elif kind == "cat":
        for side in (-1, 1):
            tip = (cx + side * 0.13 * s, cy - 0.30 * s)
            b1 = (cx + side * 0.04 * s, cy - 0.14 * s)
            b2 = (cx + side * 0.17 * s, cy - 0.16 * s)
            d.polygon([b1, tip, b2], fill=rgba(body))
            d.line([b1, tip, b2], fill=rgba(ol), width=4)
            d.polygon([
                (cx + side * 0.07 * s, cy - 0.18 * s),
                (tip[0], tip[1] + 0.05 * s),
                (cx + side * 0.14 * s, cy - 0.185 * s),
            ], fill=rgba(ch["blush"]))


def draw_face(layer: Image.Image, ch: dict, cx: float, cy: float, s: float, expr: str) -> None:
    d = ImageDraw.Draw(layer)
    ol, accent = ch["outline"], ch["accent"]
    # blush
    soft_blush(layer, cx - 0.095 * s, cy + 0.04 * s, 0.045 * s, 0.028 * s, ch["blush"])
    soft_blush(layer, cx + 0.095 * s, cy + 0.04 * s, 0.045 * s, 0.028 * s, ch["blush"])

    ey = cy - 0.005 * s
    edx = 0.065 * s
    er = 0.022 * s

    def eye_open(ex: float) -> None:
        d.ellipse([ex - er, ey - er * 1.15, ex + er, ey + er * 1.15], fill=rgba(ol))
        d.ellipse([ex - er * 0.35, ey - er * 0.7, ex + er * 0.15, ey - er * 0.15], fill=rgba((255, 255, 255)))

    def eye_happy(ex: float) -> None:
        d.arc([ex - er * 1.4, ey - er, ex + er * 1.4, ey + er * 1.5], 200, 340, fill=rgba(ol), width=max(3, int(s * 0.007)))

    if expr == "love":
        for side in (-1, 1):
            hx, hy = cx + side * edx, ey
            d.ellipse([hx - 0.016 * s, hy - 0.012 * s, hx - 0.002 * s, hy + 0.004 * s], fill=rgba(accent))
            d.ellipse([hx + 0.002 * s, hy - 0.012 * s, hx + 0.016 * s, hy + 0.004 * s], fill=rgba(accent))
            d.polygon([(hx - 0.018 * s, hy), (hx, hy + 0.028 * s), (hx + 0.018 * s, hy)], fill=rgba(accent))
    elif expr == "wink":
        eye_open(cx - edx)
        d.arc([cx + edx - er * 1.4, ey - er, cx + edx + er * 1.4, ey + er * 1.5], 200, 340, fill=rgba(ol), width=max(3, int(s * 0.007)))
    elif expr in ("laugh", "cheer"):
        eye_happy(cx - edx)
        eye_happy(cx + edx)
    elif expr in ("sleepy", "sleep"):
        eye_happy(cx - edx)
        eye_happy(cx + edx)
        if expr == "sleep":
            for i in range(3):
                zx = cx + 0.14 * s + i * 0.03 * s
                zy = cy - 0.18 * s - i * 0.04 * s
                w = max(2, int(s * 0.004))
                d.line([(zx, zy), (zx + 0.025 * s, zy)], fill=rgba(ol), width=w)
                d.line([(zx + 0.025 * s, zy), (zx, zy + 0.025 * s)], fill=rgba(ol), width=w)
                d.line([(zx, zy + 0.025 * s), (zx + 0.025 * s, zy + 0.025 * s)], fill=rgba(ol), width=w)
    elif expr == "cry":
        eye_open(cx - edx)
        eye_open(cx + edx)
        for side in (-1, 1):
            paste_radial(layer, cx + side * edx, ey + 0.045 * s, 0.012 * s, 0.022 * s, (160, 205, 255), (100, 160, 240))
    elif expr == "sad":
        eye_open(cx - edx)
        eye_open(cx + edx)
        w = max(3, int(s * 0.005))
        d.line([(cx - edx - 0.02 * s, ey - 0.03 * s), (cx - edx + 0.02 * s, ey - 0.018 * s)], fill=rgba(ol), width=w)
        d.line([(cx + edx - 0.02 * s, ey - 0.018 * s), (cx + edx + 0.02 * s, ey - 0.03 * s)], fill=rgba(ol), width=w)
    elif expr == "angry":
        w = max(3, int(s * 0.006))
        d.line([(cx - edx - 0.022 * s, ey - 0.032 * s), (cx - edx + 0.022 * s, ey - 0.018 * s)], fill=rgba(ol), width=w)
        d.line([(cx + edx - 0.022 * s, ey - 0.018 * s), (cx + edx + 0.022 * s, ey - 0.032 * s)], fill=rgba(ol), width=w)
        eye_open(cx - edx)
        eye_open(cx + edx)
    elif expr in ("surprise", "wow", "yawn"):
        for side in (-1, 1):
            ex = cx + side * edx
            d.ellipse([ex - er * 1.25, ey - er * 1.35, ex + er * 1.25, ey + er * 1.35], fill=rgba(ol))
            d.ellipse([ex - er * 0.3, ey - er * 0.8, ex + er * 0.25, ey - er * 0.2], fill=rgba((255, 255, 255)))
    elif expr == "think":
        eye_open(cx - edx)
        eye_happy(cx + edx)
        paste_radial(layer, cx + 0.17 * s, cy - 0.18 * s, 0.035 * s, 0.028 * s, (255, 255, 255), (245, 245, 250))
        outline_ellipse(layer, cx + 0.17 * s, cy - 0.18 * s, 0.035 * s, 0.028 * s, ol, 2)
        paste_radial(layer, cx + 0.135 * s, cy - 0.13 * s, 0.012 * s, 0.01 * s, (255, 255, 255), (245, 245, 250))
    elif expr == "kiss":
        eye_happy(cx - edx)
        eye_happy(cx + edx)
    else:
        eye_open(cx - edx)
        eye_open(cx + edx)

    # nose / beak
    if ch["kind"] == "chick":
        d.polygon([
            (cx - 0.02 * s, cy + 0.025 * s),
            (cx + 0.02 * s, cy + 0.025 * s),
            (cx, cy + 0.055 * s),
        ], fill=rgba(accent), outline=rgba(ol))
    elif ch["kind"] == "bear":
        paste_radial(layer, cx, cy + 0.035 * s, 0.035 * s, 0.025 * s, lighter(ch["shadow"], 0.15), ch["shadow"])
        d.ellipse([cx - 0.012 * s, cy + 0.022 * s, cx + 0.012 * s, cy + 0.038 * s], fill=rgba(ol))
    elif ch["kind"] == "cat":
        d.polygon([(cx, cy + 0.015 * s), (cx - 0.012 * s, cy + 0.032 * s), (cx + 0.012 * s, cy + 0.032 * s)], fill=rgba(ol))
        for side in (-1, 1):
            for dy in (-0.01, 0, 0.01):
                d.line([
                    (cx + side * 0.035 * s, cy + 0.028 * s + dy * s),
                    (cx + side * 0.11 * s, cy + 0.022 * s + dy * s * 1.5),
                ], fill=rgba(ol, 150), width=max(2, int(s * 0.0025)))
        # cheek stars
        for side in (-1, 1):
            d.polygon(star_poly(cx + side * 0.11 * s, cy + 0.055 * s, 0.014 * s), fill=rgba(accent, 200))
    else:
        d.ellipse([cx - 0.01 * s, cy + 0.02 * s, cx + 0.01 * s, cy + 0.035 * s], fill=rgba(ol))

    # mouth
    my = cy + 0.075 * s
    w = max(3, int(s * 0.005))
    if expr in ("laugh", "hello"):
        d.pieslice([cx - 0.035 * s, my - 0.01 * s, cx + 0.035 * s, my + 0.04 * s], 15, 165, fill=rgba(ol))
        d.ellipse([cx - 0.016 * s, my + 0.01 * s, cx + 0.016 * s, my + 0.03 * s], fill=rgba((255, 120, 145)))
    elif expr in ("surprise", "wow", "yawn"):
        d.ellipse([cx - 0.016 * s, my - 0.005 * s, cx + 0.016 * s, my + 0.03 * s], fill=rgba(ol))
    elif expr in ("cry", "sad", "pout", "angry"):
        d.arc([cx - 0.025 * s, my - 0.005 * s, cx + 0.025 * s, my + 0.03 * s], 200, 340, fill=rgba(ol), width=w)
    elif expr == "kiss":
        d.ellipse([cx - 0.012 * s, my, cx + 0.012 * s, my + 0.02 * s], fill=rgba(accent))
    elif expr in ("sleepy", "sleep"):
        d.arc([cx - 0.018 * s, my - 0.005 * s, cx + 0.018 * s, my + 0.018 * s], 20, 160, fill=rgba(ol), width=max(2, w - 1))
    else:
        d.arc([cx - 0.028 * s, my - 0.01 * s, cx + 0.028 * s, my + 0.025 * s], 20, 160, fill=rgba(ol), width=w)


def draw_prop(layer: Image.Image, pose: str, cx: float, cy: float, s: float, ch: dict) -> None:
    d = ImageDraw.Draw(layer)
    ol = ch["outline"]
    if pose in ("love", "heart", "hug", "kiss"):
        hx = cx if pose == "hug" else cx + 0.16 * s
        hy = cy + (0.06 * s if pose == "hug" else 0.02 * s)
        if pose == "hug":
            hy = cy + 0.08 * s
        col = (255, 105, 140)
        d.ellipse([hx - 0.055 * s, hy - 0.035 * s, hx - 0.005 * s, hy + 0.02 * s], fill=rgba(col))
        d.ellipse([hx + 0.005 * s, hy - 0.035 * s, hx + 0.055 * s, hy + 0.02 * s], fill=rgba(col))
        d.polygon([(hx - 0.06 * s, hy), (hx, hy + 0.07 * s), (hx + 0.06 * s, hy)], fill=rgba(col))
    elif pose == "eat":
        paste_radial(layer, cx + 0.17 * s, cy + 0.05 * s, 0.045 * s, 0.045 * s, (230, 175, 110), (190, 130, 70))
        outline_ellipse(layer, cx + 0.17 * s, cy + 0.05 * s, 0.045 * s, 0.045 * s, ol, 3)
    elif pose in ("drink", "coffee"):
        x = cx + 0.16 * s
        col = (115, 70, 40) if pose == "coffee" else ch["accent"]
        d.rounded_rectangle([x - 0.04 * s, cy - 0.01 * s, x + 0.04 * s, cy + 0.12 * s], radius=0.012 * s, fill=rgba((255, 255, 255)), outline=rgba(ol), width=3)
        d.rectangle([x - 0.035 * s, cy + 0.05 * s, x + 0.035 * s, cy + 0.11 * s], fill=rgba(col))
        d.arc([x + 0.03 * s, cy + 0.02 * s, x + 0.07 * s, cy + 0.08 * s], -80, 80, fill=rgba(ol), width=3)
    elif pose == "cake":
        x, y = cx + 0.15 * s, cy + 0.06 * s
        d.rectangle([x - 0.05 * s, y, x + 0.05 * s, y + 0.07 * s], fill=rgba((255, 220, 230)), outline=rgba(ol), width=3)
        d.ellipse([x - 0.05 * s, y - 0.015 * s, x + 0.05 * s, y + 0.015 * s], fill=rgba((255, 190, 210)), outline=rgba(ol), width=2)
        d.rectangle([x - 0.006 * s, y - 0.05 * s, x + 0.006 * s, y - 0.01 * s], fill=rgba((255, 220, 100)))
        d.ellipse([x - 0.01 * s, y - 0.06 * s, x + 0.01 * s, y - 0.04 * s], fill=rgba((255, 90, 70)))
    elif pose == "gift":
        x, y = cx + 0.15 * s, cy + 0.04 * s
        d.rounded_rectangle([x - 0.055 * s, y, x + 0.055 * s, y + 0.095 * s], radius=0.01 * s, fill=rgba((255, 125, 150)), outline=rgba(ol), width=3)
        d.rectangle([x - 0.01 * s, y, x + 0.01 * s, y + 0.095 * s], fill=rgba((255, 220, 90)))
        d.rectangle([x - 0.055 * s, y + 0.035 * s, x + 0.055 * s, y + 0.055 * s], fill=rgba((255, 220, 90)))
    elif pose == "flower":
        fx, fy = cx + 0.17 * s, cy + 0.02 * s
        for a in range(0, 360, 60):
            d.ellipse([
                fx + 0.03 * s * math.cos(math.radians(a)) - 0.018 * s,
                fy + 0.03 * s * math.sin(math.radians(a)) - 0.018 * s,
                fx + 0.03 * s * math.cos(math.radians(a)) + 0.018 * s,
                fy + 0.03 * s * math.sin(math.radians(a)) + 0.018 * s,
            ], fill=rgba((255, 150, 180)))
        d.ellipse([fx - 0.015 * s, fy - 0.015 * s, fx + 0.015 * s, fy + 0.015 * s], fill=rgba((255, 220, 80)))
        d.line([(fx, fy + 0.025 * s), (fx, fy + 0.12 * s)], fill=rgba((70, 155, 85)), width=4)
    elif pose == "rain":
        paste_radial(layer, cx - 0.05 * s, cy - 0.22 * s, 0.07 * s, 0.04 * s, (235, 240, 248), (200, 210, 225))
        paste_radial(layer, cx + 0.04 * s, cy - 0.23 * s, 0.06 * s, 0.038 * s, (235, 240, 248), (200, 210, 225))
        for dx, dy in ((-0.14, -0.08), (0.14, -0.12), (0.10, -0.04), (-0.09, -0.02)):
            d.line([(cx + dx * s, cy + dy * s), (cx + dx * s - 0.008 * s, cy + dy * s + 0.04 * s)], fill=rgba((100, 160, 230)), width=3)
    elif pose == "sun":
        sx, sy = cx + 0.18 * s, cy - 0.18 * s
        paste_radial(layer, sx, sy, 0.04 * s, 0.04 * s, (255, 230, 120), (255, 185, 50))
        for a in range(0, 360, 45):
            d.line([
                (sx + 0.048 * s * math.cos(math.radians(a)), sy + 0.048 * s * math.sin(math.radians(a))),
                (sx + 0.068 * s * math.cos(math.radians(a)), sy + 0.068 * s * math.sin(math.radians(a))),
            ], fill=rgba((255, 175, 40)), width=3)
    elif pose == "phone":
        x, y = cx + 0.17 * s, cy + 0.01 * s
        d.rounded_rectangle([x - 0.03 * s, y - 0.055 * s, x + 0.03 * s, y + 0.06 * s], radius=0.01 * s, fill=rgba((50, 55, 70)), outline=rgba(ol), width=3)
        d.rounded_rectangle([x - 0.022 * s, y - 0.04 * s, x + 0.022 * s, y + 0.04 * s], radius=0.005 * s, fill=rgba((150, 210, 255)))
    elif pose == "work":
        x, y = cx + 0.12 * s, cy + 0.10 * s
        d.rounded_rectangle([x - 0.08 * s, y - 0.06 * s, x + 0.08 * s, y + 0.01 * s], radius=0.006 * s, fill=rgba((70, 80, 100)), outline=rgba(ol), width=3)
        d.polygon([(x - 0.085 * s, y + 0.01 * s), (x + 0.085 * s, y + 0.01 * s), (x + 0.1 * s, y + 0.035 * s), (x - 0.1 * s, y + 0.035 * s)], fill=rgba((175, 180, 190)), outline=rgba(ol))
    elif pose == "peek":
        d.rectangle([0, int(0.22 * s), int(cx - 0.06 * s), layer.size[0]], fill=rgba((232, 228, 238)), outline=rgba(ol), width=3)
    elif pose == "read":
        x, y = cx + 0.13 * s, cy + 0.07 * s
        d.polygon([(x - 0.07 * s, y), (x, y - 0.03 * s), (x, y + 0.07 * s), (x - 0.07 * s, y + 0.05 * s)], fill=rgba((255, 245, 220)), outline=rgba(ol))
        d.polygon([(x + 0.07 * s, y), (x, y - 0.03 * s), (x, y + 0.07 * s), (x + 0.07 * s, y + 0.05 * s)], fill=rgba((255, 235, 200)), outline=rgba(ol))
    elif pose == "cold":
        d.arc([cx - 0.12 * s, cy - 0.02 * s, cx + 0.12 * s, cy + 0.14 * s], 200, 340, fill=rgba(ch["accent"]), width=max(8, int(s * 0.025)))
    elif pose == "hot":
        for dx, dy in ((-0.12, -0.05), (0.13, -0.08), (0.11, -0.02)):
            paste_radial(layer, cx + dx * s, cy + dy * s, 0.012 * s, 0.02 * s, (160, 210, 255), (100, 170, 240))


def render_mascot(ch: dict, pose: str, out_size: int = 768) -> Image.Image:
    """Render one sticker at high quality with 2× supersampling."""
    hi = out_size * 2
    s = float(hi)
    L = pose_layout(pose)
    # map char-pack pose aliases already in pose_layout
    layer = Image.new("RGBA", (hi, hi), (0, 0, 0, 0))
    cx = s * 0.5
    body_cy = L["body_cy"] * s
    head_cy = L["head_cy"] * s
    body, shadow, belly = ch["body"], ch["shadow"], ch["belly"]
    ol = darker(body, 0.42)

    if pose == "peek":
        draw_prop(layer, pose, cx, body_cy, s, ch)

    # legs under body
    if not L["hide_legs"]:
        for key in ("leg_l", "leg_r"):
            a = L[key]
            sausage(layer, cx + a[0] * s, a[1] * s, cx + a[2] * s, a[3] * s, L["leg_r_w"] * s, body, ol)

    # body — slightly smaller vs head for chibi
    brx, bry = 0.168 * s, 0.175 * s
    if ch["kind"] == "peach":
        brx, bry = 0.18 * s, 0.188 * s
    elif ch["kind"] == "chick":
        brx, bry = 0.158 * s, 0.165 * s
    paste_radial(layer, cx, body_cy, brx, bry, lighter(body, 0.2), shadow, lighter(body, 0.45))
    outline_ellipse(layer, cx, body_cy, brx, bry, ol, 6)
    paste_radial(layer, cx, body_cy + 0.025 * s, brx * 0.55, bry * 0.48, lighter(belly, 0.25), belly)

    if ch["kind"] == "chick":
        for side in (-1, 1):
            paste_radial(layer, cx + side * 0.145 * s, body_cy + 0.005 * s, 0.042 * s, 0.03 * s, lighter(shadow, 0.15), shadow)
            outline_ellipse(layer, cx + side * 0.145 * s, body_cy + 0.005 * s, 0.042 * s, 0.03 * s, ol, 3)

    # arms on top of body (sticker style)
    for key in ("arm_l", "arm_r"):
        a = L[key]
        sausage(layer, cx + a[0] * s, a[1] * s, cx + a[2] * s, a[3] * s, L["arm_r_w"] * s, body, ol)

    # head (larger — Friends style)
    draw_ears(layer, ch, cx, head_cy, s)
    hr = 0.225 * s
    paste_radial(layer, cx, head_cy, hr, hr * 0.98, lighter(body, 0.22), shadow, lighter(body, 0.5))
    outline_ellipse(layer, cx, head_cy, hr, hr * 0.98, ol, 6)
    draw_face(layer, ch, cx, head_cy, s, L["expr"])

    if pose == "sit":
        paste_radial(layer, cx, 0.86 * s, 0.17 * s, 0.04 * s, lighter(body, 0.1), shadow)
        outline_ellipse(layer, cx, 0.86 * s, 0.17 * s, 0.04 * s, ol, 3)

    if pose != "peek":
        draw_prop(layer, pose, cx, body_cy, s, ch)

    if abs(L["tilt"]) > 0.5:
        layer = layer.rotate(L["tilt"], resample=Image.BICUBIC, center=(cx, body_cy))

    # soft ground shadow
    final = Image.new("RGBA", (hi, hi), (0, 0, 0, 0))
    sh = Image.new("RGBA", (hi, hi), (0, 0, 0, 0))
    sd = ImageDraw.Draw(sh)
    sd.ellipse([cx - 0.16 * s, 0.90 * s, cx + 0.16 * s, 0.96 * s], fill=(30, 25, 35, 45))
    sh = sh.filter(ImageFilter.GaussianBlur(18))
    final.alpha_composite(sh)

    # character drop shadow
    alpha = layer.split()[-1]
    drop = Image.new("RGBA", (hi, hi), (0, 0, 0, 0))
    drop.paste(Image.new("RGBA", (hi, hi), (35, 25, 40, 55)), (8, 14), alpha)
    drop = drop.filter(ImageFilter.GaussianBlur(12))
    final.alpha_composite(drop)
    final.alpha_composite(layer)

    # mild polish + downscale
    final = final.filter(ImageFilter.SMOOTH)
    return final.resize((out_size, out_size), Image.Resampling.LANCZOS)
