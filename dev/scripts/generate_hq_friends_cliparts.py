#!/usr/bin/env python3
"""Generate ~200 Kakao/LINE Friends–inspired ORIGINAL mascot cliparts.

Style cues only (chibi, thick limbs, sticker poses) — not copies of trademarked IPs.

Characters (5 × 40 = 200):
  hoya     — 호야 (햇살 노란 곰)
  boksoong — 복숭 (복숭아 핑크)
  mocha    — 모카 (초코 브라운)
  torong   — 토롱 (크림 토끼)
  bbiya    — 삐야 (노란 병아리)

Outputs:
  public/assets/cliparts/hq_friend_{NNN}_{char}_{pose}.png
  storage/imports/clipart_friends_manifest.json
"""
from __future__ import annotations

import json
import math
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "cliparts"
MANIFEST = ROOT / "storage" / "imports" / "clipart_friends_manifest.json"
SIZE = 768
FORCE = False


def rgba(c: tuple[int, int, int], a: int = 255) -> tuple[int, int, int, int]:
    return (c[0], c[1], c[2], a)


def lerp(a: float, b: float, t: float) -> float:
    return a + (b - a) * t


def mix(c1: tuple[int, int, int], c2: tuple[int, int, int], t: float) -> tuple[int, int, int]:
    return (
        int(lerp(c1[0], c2[0], t)),
        int(lerp(c1[1], c2[1], t)),
        int(lerp(c1[2], c2[2], t)),
    )


def oval(d: ImageDraw.ImageDraw, cx, cy, rx, ry, fill=None, outline=None, width: int = 1) -> None:
    d.ellipse([cx - rx, cy - ry, cx + rx, cy + ry], fill=fill, outline=outline, width=width)


def capsule(d: ImageDraw.ImageDraw, x0, y0, x1, y1, r: float, fill, outline, ow: int = 4) -> None:
    """Thick filled limb between two points."""
    dx, dy = x1 - x0, y1 - y0
    length = math.hypot(dx, dy) or 1
    ux, uy = dx / length, dy / length
    px, py = -uy, ux
    pts = [
        (x0 + px * r, y0 + py * r),
        (x1 + px * r, y1 + py * r),
        (x1 - px * r, y1 - py * r),
        (x0 - px * r, y0 - py * r),
    ]
    d.polygon(pts, fill=fill)
    oval(d, x0, y0, r, r, fill=fill)
    oval(d, x1, y1, r * 1.05, r * 1.05, fill=fill)
    # soft outline
    d.line([(x0, y0), (x1, y1)], fill=outline, width=max(2, ow // 2))
    oval(d, x0, y0, r, r, outline=outline, width=ow)
    oval(d, x1, y1, r * 1.05, r * 1.05, outline=outline, width=ow)


def shadow_composite(base: Image.Image, layer: Image.Image) -> None:
    a = layer.split()[-1]
    sh = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    sh.paste(Image.new("RGBA", (SIZE, SIZE), (40, 30, 35, 50)), (5, 10), a)
    sh = sh.filter(ImageFilter.GaussianBlur(10))
    base.alpha_composite(sh)
    base.alpha_composite(layer)


CHARS = {
    "hoya": {
        "name": "호야",
        "kind": "bear",
        "body": (255, 214, 90),
        "dark": (235, 180, 50),
        "blush": (255, 160, 140),
        "outline": (70, 50, 30),
        "accent": (255, 120, 90),
        "belly": (255, 235, 160),
    },
    "boksoong": {
        "name": "복숭",
        "kind": "peach",
        "body": (255, 170, 185),
        "dark": (240, 130, 150),
        "blush": (255, 110, 140),
        "outline": (90, 45, 60),
        "accent": (255, 95, 130),
        "belly": (255, 210, 215),
    },
    "mocha": {
        "name": "모카",
        "kind": "bear",
        "body": (140, 95, 70),
        "dark": (110, 70, 50),
        "blush": (230, 140, 130),
        "outline": (45, 30, 25),
        "accent": (255, 180, 100),
        "belly": (190, 150, 120),
    },
    "torong": {
        "name": "토롱",
        "kind": "bunny",
        "body": (255, 245, 240),
        "dark": (235, 215, 210),
        "blush": (255, 160, 180),
        "outline": (80, 55, 60),
        "accent": (255, 140, 170),
        "belly": (255, 255, 255),
    },
    "bbiya": {
        "name": "삐야",
        "kind": "chick",
        "body": (255, 220, 70),
        "dark": (240, 190, 40),
        "blush": (255, 150, 130),
        "outline": (80, 55, 25),
        "accent": (255, 130, 80),
        "belly": (255, 240, 150),
    },
}


# Sticker-pack style poses (40) — greetings / emotion / daily / celebration
POSES: list[tuple[str, str]] = [
    ("hello", "안녕"),
    ("smile", "방긋"),
    ("laugh", "킥킥"),
    ("wink", "윙크"),
    ("love", "사랑해"),
    ("heart", "하트뿅"),
    ("blush", "새침"),
    ("shy", "수줍"),
    ("surprise", "깜짝"),
    ("wow", "대박"),
    ("cry", "흑흑"),
    ("sad", "시무룩"),
    ("angry", "화남"),
    ("pout", "뾰로통"),
    ("sleepy", "졸려"),
    ("sleep", "쿨쿨"),
    ("yawn", "하품"),
    ("think", "음…"),
    ("ok", "OK"),
    ("thumbs", "최고"),
    ("wave", "손흔들"),
    ("cheer", "만세"),
    ("jump", "폴짝"),
    ("dance", "춤춰"),
    ("run", "달려"),
    ("sit", "쪼그려"),
    ("peek", "빼꼼"),
    ("hug", "꼭안아"),
    ("kiss", "쪽"),
    ("eat", "냠냠"),
    ("drink", "쪽쪽"),
    ("coffee", "커피타임"),
    ("cake", "축하해"),
    ("gift", "선물이야"),
    ("flower", "꽃선물"),
    ("rain", "비와요"),
    ("sun", "날씨좋아"),
    ("work", "열심"),
    ("phone", "전화중"),
    ("bye", "잘가"),
]


def draw_ears(d: ImageDraw.ImageDraw, ch: dict, cx: float, cy: float, s: float) -> None:
    kind, body, dark, ol = ch["kind"], ch["body"], ch["dark"], ch["outline"]
    if kind == "bear":
        for side in (-1, 1):
            oval(d, cx + side * 118 * s, cy - 130 * s, 48 * s, 48 * s, fill=rgba(body), outline=rgba(ol), width=4)
            oval(d, cx + side * 118 * s, cy - 130 * s, 24 * s, 24 * s, fill=rgba(dark))
    elif kind == "bunny":
        for side in (-1, 1):
            tip = (cx + side * 55 * s, cy - 250 * s)
            b1 = (cx + side * 25 * s, cy - 110 * s)
            b2 = (cx + side * 95 * s, cy - 120 * s)
            d.polygon([b1, tip, b2], fill=rgba(body))
            d.line([b1, tip, b2, b1], fill=rgba(ol), width=4)
            d.polygon([
                (cx + side * 42 * s, cy - 140 * s),
                (tip[0], tip[1] + 40 * s),
                (cx + side * 78 * s, cy - 145 * s),
            ], fill=rgba(ch["blush"]))
    elif kind == "peach":
        # leaf + soft peach cheeks ears as nubs
        for side in (-1, 1):
            oval(d, cx + side * 110 * s, cy - 120 * s, 36 * s, 42 * s, fill=rgba(body), outline=rgba(ol), width=4)
            oval(d, cx + side * 110 * s, cy - 120 * s, 16 * s, 20 * s, fill=rgba(ch["blush"]))
        # leaf on top
        d.ellipse([cx - 10 * s, cy - 200 * s, cx + 55 * s, cy - 145 * s], fill=rgba((110, 190, 100)), outline=rgba(ol), width=3)
    elif kind == "chick":
        # tiny crest
        for i, dx in enumerate((-18, 0, 18)):
            oval(d, cx + dx * s, cy - 155 * s - abs(dx) * 0.2 * s, 16 * s, 22 * s, fill=rgba(ch["accent"]), outline=rgba(ol), width=3)


def draw_face(d: ImageDraw.ImageDraw, ch: dict, cx: float, cy: float, s: float, expr: str) -> None:
    ol, blush, accent = ch["outline"], ch["blush"], ch["accent"]
    # cheeks
    if expr not in ("angry",):
        oval(d, cx - 72 * s, cy + 28 * s, 30 * s, 18 * s, fill=rgba(blush))
        oval(d, cx + 72 * s, cy + 28 * s, 30 * s, 18 * s, fill=rgba(blush))

    ey = cy - 8 * s
    edx = 46 * s

    if expr == "love":
        for side in (-1, 1):
            hx, hy = cx + side * edx, ey
            oval(d, hx - 10 * s, hy - 4 * s, 12 * s, 12 * s, fill=rgba(accent))
            oval(d, hx + 10 * s, hy - 4 * s, 12 * s, 12 * s, fill=rgba(accent))
            d.polygon([(hx - 20 * s, hy), (hx, hy + 22 * s), (hx + 20 * s, hy)], fill=rgba(accent))
    elif expr == "wink":
        oval(d, cx - edx, ey, 14 * s, 16 * s, fill=rgba(ol))
        oval(d, cx - edx + 4 * s, ey - 4 * s, 5 * s, 5 * s, fill=rgba((255, 255, 255)))
        d.arc([cx + edx - 18 * s, ey - 6 * s, cx + edx + 18 * s, ey + 16 * s], 200, 340, fill=rgba(ol), width=5)
    elif expr in ("laugh", "cheer", "dance", "jump", "yay"):
        for side in (-1, 1):
            d.arc([cx + side * edx - 18 * s, ey - 8 * s, cx + side * edx + 18 * s, ey + 16 * s], 200, 340, fill=rgba(ol), width=5)
    elif expr in ("sleepy", "sleep", "yawn"):
        for side in (-1, 1):
            d.arc([cx + side * edx - 16 * s, ey - 4 * s, cx + side * edx + 16 * s, ey + 16 * s], 200, 340, fill=rgba(ol), width=5)
        if expr == "sleep":
            for i in range(3):
                zx = cx + 105 * s + i * 22 * s
                zy = cy - 150 * s - i * 28 * s
                d.line([(zx, zy), (zx + 18 * s, zy)], fill=rgba(ol), width=3)
                d.line([(zx + 18 * s, zy), (zx, zy + 18 * s)], fill=rgba(ol), width=3)
                d.line([(zx, zy + 18 * s), (zx + 18 * s, zy + 18 * s)], fill=rgba(ol), width=3)
    elif expr == "cry":
        for side in (-1, 1):
            oval(d, cx + side * edx, ey, 12 * s, 14 * s, fill=rgba(ol))
            oval(d, cx + side * edx, ey + 32 * s, 8 * s, 16 * s, fill=rgba((130, 190, 255)))
    elif expr == "sad":
        for side in (-1, 1):
            oval(d, cx + side * edx, ey + 4 * s, 12 * s, 14 * s, fill=rgba(ol))
            d.line([
                (cx + side * edx - 14 * s, ey - 18 * s),
                (cx + side * edx + 14 * s, ey - 10 * s),
            ], fill=rgba(ol), width=4)
    elif expr == "angry":
        for side in (-1, 1):
            d.line([
                (cx + side * edx - 16 * s, ey - 22 * s if side > 0 else ey - 12 * s),
                (cx + side * edx + 16 * s, ey - 12 * s if side > 0 else ey - 22 * s),
            ], fill=rgba(ol), width=5)
            oval(d, cx + side * edx, ey + 2 * s, 12 * s, 13 * s, fill=rgba(ol))
    elif expr == "surprise" or expr == "wow":
        for side in (-1, 1):
            oval(d, cx + side * edx, ey, 16 * s, 18 * s, fill=rgba(ol))
            oval(d, cx + side * edx + 4 * s, ey - 5 * s, 5 * s, 5 * s, fill=rgba((255, 255, 255)))
    elif expr == "think":
        oval(d, cx - edx, ey, 13 * s, 15 * s, fill=rgba(ol))
        oval(d, cx - edx + 4 * s, ey - 4 * s, 4 * s, 4 * s, fill=rgba((255, 255, 255)))
        d.arc([cx + edx - 16 * s, ey - 6 * s, cx + edx + 16 * s, ey + 14 * s], 200, 340, fill=rgba(ol), width=5)
        oval(d, cx + 130 * s, cy - 150 * s, 26 * s, 20 * s, fill=rgba((255, 255, 255)), outline=rgba(ol), width=3)
        oval(d, cx + 105 * s, cy - 115 * s, 10 * s, 8 * s, fill=rgba((255, 255, 255)), outline=rgba(ol), width=2)
    else:
        for side in (-1, 1):
            oval(d, cx + side * edx, ey, 13 * s, 15 * s, fill=rgba(ol))
            oval(d, cx + side * edx + 4 * s, ey - 4 * s, 4 * s, 4 * s, fill=rgba((255, 255, 255)))

    # nose / beak
    if ch["kind"] == "chick":
        d.polygon([
            (cx - 14 * s, cy + 18 * s),
            (cx + 14 * s, cy + 18 * s),
            (cx, cy + 38 * s),
        ], fill=rgba(accent), outline=rgba(ol))
    elif ch["kind"] == "bear":
        oval(d, cx, cy + 28 * s, 26 * s, 18 * s, fill=rgba(ch["dark"]))
        oval(d, cx, cy + 20 * s, 10 * s, 7 * s, fill=rgba(ol))
    elif ch["kind"] == "peach":
        oval(d, cx, cy + 16 * s, 7 * s, 5 * s, fill=rgba(ol))
    else:
        oval(d, cx, cy + 16 * s, 8 * s, 6 * s, fill=rgba(ol))

    # mouth
    my = cy + 55 * s
    if expr in ("laugh", "cheer", "dance", "jump", "hello", "yay", "ok"):
        d.pieslice([cx - 28 * s, my - 10 * s, cx + 28 * s, my + 30 * s], 10, 170, fill=rgba(ol))
        oval(d, cx, my + 12 * s, 14 * s, 10 * s, fill=rgba((255, 120, 140)))
    elif expr in ("surprise", "wow", "yawn"):
        oval(d, cx, my + 6 * s, 14 * s, 18 * s, fill=rgba(ol))
    elif expr in ("cry", "sad", "pout", "angry"):
        d.arc([cx - 20 * s, my - 2 * s, cx + 20 * s, my + 24 * s], 200, 340, fill=rgba(ol), width=4)
    elif expr == "kiss":
        oval(d, cx, my + 4 * s, 10 * s, 8 * s, fill=rgba(accent))
    elif expr in ("sleepy", "sleep"):
        d.arc([cx - 14 * s, my - 4 * s, cx + 14 * s, my + 14 * s], 20, 160, fill=rgba(ol), width=3)
    else:
        d.arc([cx - 22 * s, my - 8 * s, cx + 22 * s, my + 18 * s], 20, 160, fill=rgba(ol), width=4)


def draw_prop(d: ImageDraw.ImageDraw, pose: str, cx: float, cy: float, s: float, ch: dict) -> None:
    ol = ch["outline"]
    if pose in ("love", "heart", "hug", "kiss"):
        hx, hy = cx + (0 if pose == "hug" else 115 * s), cy + (30 * s if pose == "hug" else 20 * s)
        if pose == "hug":
            hx, hy = cx, cy + 50 * s
        oval(d, hx - 32 * s, hy - 18 * s, 30 * s, 28 * s, fill=rgba((255, 100, 130)))
        oval(d, hx + 32 * s, hy - 18 * s, 30 * s, 28 * s, fill=rgba((255, 100, 130)))
        d.polygon([(hx - 58 * s, hy - 5 * s), (hx, hy + 50 * s), (hx + 58 * s, hy - 5 * s)], fill=rgba((255, 100, 130)))
    elif pose == "eat":
        oval(d, cx + 120 * s, cy + 40 * s, 34 * s, 34 * s, fill=rgba((210, 150, 90)), outline=rgba(ol), width=3)
    elif pose == "drink" or pose == "coffee":
        x = cx + 115 * s
        col = (120, 75, 45) if pose == "coffee" else ch["accent"]
        d.rounded_rectangle([x - 30 * s, cy + 5 * s, x + 30 * s, cy + 95 * s], radius=8 * s, fill=rgba((255, 255, 255)), outline=rgba(ol), width=3)
        d.rectangle([x - 26 * s, cy + 50 * s, x + 26 * s, cy + 90 * s], fill=rgba(col))
        d.arc([x + 22 * s, cy + 25 * s, x + 52 * s, cy + 65 * s], -80, 80, fill=rgba(ol), width=3)
    elif pose == "cake":
        x, y = cx + 110 * s, cy + 45 * s
        d.rectangle([x - 38 * s, y, x + 38 * s, y + 50 * s], fill=rgba((255, 220, 230)), outline=rgba(ol), width=3)
        d.ellipse([x - 38 * s, y - 12 * s, x + 38 * s, y + 12 * s], fill=rgba((255, 190, 210)), outline=rgba(ol), width=2)
        d.rectangle([x - 4 * s, y - 38 * s, x + 4 * s, y - 8 * s], fill=rgba((255, 220, 100)))
        oval(d, x, y - 42 * s, 7 * s, 9 * s, fill=rgba((255, 90, 70)))
    elif pose == "gift":
        x, y = cx + 110 * s, cy + 35 * s
        d.rounded_rectangle([x - 40 * s, y, x + 40 * s, y + 70 * s], radius=6 * s, fill=rgba((255, 130, 150)), outline=rgba(ol), width=3)
        d.rectangle([x - 8 * s, y, x + 8 * s, y + 70 * s], fill=rgba((255, 220, 90)))
        d.rectangle([x - 40 * s, y + 25 * s, x + 40 * s, y + 40 * s], fill=rgba((255, 220, 90)))
    elif pose == "flower":
        fx, fy = cx + 120 * s, cy + 20 * s
        for a in range(0, 360, 60):
            oval(d, fx + 22 * s * math.cos(math.radians(a)), fy + 22 * s * math.sin(math.radians(a)), 14 * s, 14 * s, fill=rgba((255, 150, 180)))
        oval(d, fx, fy, 12 * s, 12 * s, fill=rgba((255, 220, 80)))
        d.line([(fx, fy + 20 * s), (fx, fy + 90 * s)], fill=rgba((80, 160, 90)), width=5)
    elif pose == "rain":
        for i, (dx, dy) in enumerate(((-100, -80), (100, -100), (80, -40), (-70, -30))):
            d.line([(cx + dx * s, cy + dy * s), (cx + dx * s - 6 * s, cy + dy * s + 28 * s)], fill=rgba((100, 160, 230)), width=4)
        # cloud
        oval(d, cx - 40 * s, cy - 160 * s, 50 * s, 30 * s, fill=rgba((220, 230, 240)), outline=rgba(ol), width=2)
        oval(d, cx + 20 * s, cy - 165 * s, 45 * s, 28 * s, fill=rgba((220, 230, 240)), outline=rgba(ol), width=2)
    elif pose == "sun":
        sx, sy = cx + 130 * s, cy - 140 * s
        oval(d, sx, sy, 28 * s, 28 * s, fill=rgba((255, 200, 60)), outline=rgba(ol), width=3)
        for a in range(0, 360, 45):
            d.line([
                (sx + 34 * s * math.cos(math.radians(a)), sy + 34 * s * math.sin(math.radians(a))),
                (sx + 48 * s * math.cos(math.radians(a)), sy + 48 * s * math.sin(math.radians(a))),
            ], fill=rgba((255, 180, 40)), width=4)
    elif pose == "phone":
        x, y = cx + 120 * s, cy + 10 * s
        d.rounded_rectangle([x - 22 * s, y - 40 * s, x + 22 * s, y + 45 * s], radius=8 * s, fill=rgba((55, 60, 75)), outline=rgba(ol), width=3)
        d.rounded_rectangle([x - 16 * s, y - 30 * s, x + 16 * s, y + 28 * s], radius=4 * s, fill=rgba((150, 210, 255)))
    elif pose == "work":
        # laptop
        x, y = cx + 90 * s, cy + 70 * s
        d.rounded_rectangle([x - 55 * s, y - 45 * s, x + 55 * s, y + 5 * s], radius=4 * s, fill=rgba((80, 90, 110)), outline=rgba(ol), width=3)
        d.polygon([(x - 60 * s, y + 5 * s), (x + 60 * s, y + 5 * s), (x + 70 * s, y + 25 * s), (x - 70 * s, y + 25 * s)], fill=rgba((180, 185, 195)), outline=rgba(ol))
    elif pose == "thumbs" or pose == "ok":
        pass  # arm pose handles
    elif pose == "peek":
        d.rectangle([0, 180, cx - 50 * s, SIZE], fill=rgba((235, 230, 240)), outline=rgba(ol), width=3)


def pose_layout(pose: str) -> dict:
    base = {
        "expr": "smile",
        "body_cy": 430,
        "head_cy": 275,
        "arm_l": (-125, 400, -155, 520),
        "arm_r": (125, 400, 155, 520),
        "leg_l": (-50, 560, -55, 655),
        "leg_r": (50, 560, 55, 655),
        "tilt": 0,
        "hide_legs": False,
        "arm_thick": 36,
        "leg_thick": 38,
    }
    o = {
        "hello": {"expr": "laugh", "arm_r": (130, 360, 180, 250)},
        "smile": {"expr": "smile"},
        "laugh": {"expr": "laugh", "arm_l": (-130, 380, -150, 300), "arm_r": (130, 380, 150, 300)},
        "wink": {"expr": "wink"},
        "love": {"expr": "love", "arm_l": (-100, 400, -30, 460), "arm_r": (100, 400, 30, 460)},
        "heart": {"expr": "love", "arm_r": (110, 380, 160, 420)},
        "blush": {"expr": "shy", "arm_l": (-90, 390, -50, 330), "arm_r": (90, 390, 50, 330)},
        "shy": {"expr": "shy", "arm_l": (-85, 390, -45, 330), "arm_r": (85, 390, 45, 330)},
        "surprise": {"expr": "surprise", "arm_l": (-150, 340, -175, 240), "arm_r": (150, 340, 175, 240)},
        "wow": {"expr": "wow", "arm_l": (-145, 350, -170, 260), "arm_r": (145, 350, 170, 260)},
        "cry": {"expr": "cry", "arm_l": (-95, 390, -45, 330), "arm_r": (95, 390, 45, 330)},
        "sad": {"expr": "sad", "tilt": -4},
        "angry": {"expr": "angry", "arm_l": (-140, 400, -170, 480), "arm_r": (140, 400, 170, 480)},
        "pout": {"expr": "pout", "arm_l": (-100, 420, -40, 470), "arm_r": (100, 420, 40, 470)},
        "sleepy": {"expr": "sleepy", "tilt": -6},
        "sleep": {"expr": "sleep", "tilt": -18, "body_cy": 480, "head_cy": 380, "hide_legs": True,
                  "arm_l": (-70, 470, -30, 510), "arm_r": (90, 460, 140, 510)},
        "yawn": {"expr": "yawn", "arm_l": (-130, 340, -150, 260)},
        "think": {"expr": "think", "arm_l": (-90, 400, -30, 340)},
        "ok": {"expr": "laugh", "arm_r": (120, 360, 170, 280)},
        "thumbs": {"expr": "laugh", "arm_r": (120, 350, 165, 260)},
        "wave": {"expr": "laugh", "arm_r": (135, 340, 185, 220)},
        "cheer": {"expr": "cheer", "arm_l": (-140, 330, -165, 200), "arm_r": (140, 330, 165, 200)},
        "jump": {"expr": "jump", "body_cy": 380, "head_cy": 225,
                 "leg_l": (-65, 510, -95, 575), "leg_r": (65, 510, 95, 575),
                 "arm_l": (-145, 320, -175, 240), "arm_r": (145, 320, 175, 240)},
        "dance": {"expr": "dance", "tilt": -8, "arm_l": (-150, 330, -195, 270), "arm_r": (110, 360, 70, 250),
                  "leg_l": (-30, 560, 20, 650), "leg_r": (70, 550, 120, 640)},
        "run": {"expr": "laugh", "tilt": 10, "arm_l": (-90, 360, -30, 300), "arm_r": (110, 390, 170, 470),
                "leg_l": (-35, 550, -100, 640), "leg_r": (55, 545, 115, 620)},
        "sit": {"expr": "smile", "body_cy": 470, "head_cy": 320, "hide_legs": True,
                "arm_l": (-120, 450, -150, 540), "arm_r": (120, 450, 150, 540)},
        "peek": {"expr": "wink", "body_cy": 450, "head_cy": 310, "hide_legs": True,
                 "arm_l": (-70, 420, -20, 370), "arm_r": (30, 420, 10, 370)},
        "hug": {"expr": "love", "arm_l": (-70, 400, -15, 450), "arm_r": (70, 400, 15, 450)},
        "kiss": {"expr": "kiss", "arm_l": (-100, 400, -40, 450), "arm_r": (110, 380, 160, 420)},
        "eat": {"expr": "laugh", "arm_r": (95, 390, 145, 430)},
        "drink": {"expr": "smile", "arm_r": (95, 370, 145, 410)},
        "coffee": {"expr": "smile", "arm_r": (95, 370, 145, 410)},
        "cake": {"expr": "laugh", "arm_r": (95, 390, 145, 440)},
        "gift": {"expr": "love", "arm_r": (90, 400, 145, 460), "arm_l": (-100, 400, -40, 460)},
        "flower": {"expr": "blush", "arm_r": (100, 370, 155, 330)},
        "rain": {"expr": "sad", "arm_l": (-110, 400, -150, 480), "arm_r": (110, 400, 150, 480)},
        "sun": {"expr": "laugh", "arm_l": (-140, 340, -165, 250), "arm_r": (140, 340, 165, 250)},
        "work": {"expr": "think", "arm_l": (-40, 430, 40, 470), "arm_r": (60, 430, 130, 470)},
        "phone": {"expr": "smile", "arm_r": (105, 340, 155, 300)},
        "bye": {"expr": "wink", "arm_r": (135, 340, 190, 230), "arm_l": (-125, 410, -155, 510)},
    }
    base.update(o.get(pose, {}))
    # map yay-like
    if base["expr"] in ("cheer", "jump", "dance"):
        pass
    return base


def render(char_key: str, pose: str) -> Image.Image:
    ch = CHARS[char_key]
    L = pose_layout(pose)
    s = 1.0
    img = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    cx = SIZE / 2
    body_cy, head_cy = L["body_cy"], L["head_cy"]
    ol, body = ch["outline"], ch["body"]

    if pose == "peek":
        draw_prop(d, pose, cx, body_cy, s, ch)

    # legs
    if not L["hide_legs"]:
        ll, lr = L["leg_l"], L["leg_r"]
        capsule(d, cx + ll[0] * s, ll[1], cx + ll[2] * s, ll[3], L["leg_thick"] * s * 0.55, rgba(body), rgba(ol), 4)
        capsule(d, cx + lr[0] * s, lr[1], cx + lr[2] * s, lr[3], L["leg_thick"] * s * 0.55, rgba(body), rgba(ol), 4)
        oval(d, cx + ll[2] * s, ll[3] + 4 * s, 32 * s, 18 * s, fill=rgba(body), outline=rgba(ol), width=4)
        oval(d, cx + lr[2] * s, lr[3] + 4 * s, 32 * s, 18 * s, fill=rgba(body), outline=rgba(ol), width=4)

    # body (chibi oval)
    brx, bry = 145 * s, 155 * s
    if ch["kind"] == "peach":
        brx, bry = 155 * s, 165 * s
    elif ch["kind"] == "chick":
        brx, bry = 135 * s, 145 * s
    oval(d, cx, body_cy, brx, bry, fill=rgba(body), outline=rgba(ol), width=5)
    oval(d, cx, body_cy + 25 * s, brx * 0.55, bry * 0.48, fill=rgba(ch["belly"]))

    # chick wing accents
    if ch["kind"] == "chick":
        for side in (-1, 1):
            oval(d, cx + side * 120 * s, body_cy + 10 * s, 40 * s, 28 * s, fill=rgba(ch["dark"]), outline=rgba(ol), width=3)

    # arms
    al, ar = L["arm_l"], L["arm_r"]
    at = L["arm_thick"] * s * 0.55
    capsule(d, cx + al[0] * s, al[1], cx + al[2] * s, al[3], at, rgba(body), rgba(ol), 4)
    capsule(d, cx + ar[0] * s, ar[1], cx + ar[2] * s, ar[3], at, rgba(body), rgba(ol), 4)

    # head
    draw_ears(d, ch, cx, head_cy, s)
    hr = 148 * s
    oval(d, cx, head_cy, hr, hr * 0.98, fill=rgba(body), outline=rgba(ol), width=5)
    draw_face(d, ch, cx, head_cy, s, L["expr"])

    if pose == "sit":
        oval(d, cx, 630, 130 * s, 30 * s, fill=rgba(body), outline=rgba(ol), width=4)

    if pose != "peek":
        draw_prop(d, pose, cx, body_cy, s, ch)

    if abs(L["tilt"]) > 0.5:
        layer = layer.rotate(L["tilt"], resample=Image.BICUBIC, center=(cx, body_cy))

    shadow_composite(img, layer)
    return img.filter(ImageFilter.SMOOTH)


def build_catalog() -> list[dict]:
    items = []
    n = 0
    for key, meta in CHARS.items():
        for pose_id, pose_name in POSES:
            n += 1
            items.append({
                "n": n,
                "char": key,
                "char_name": meta["name"],
                "pose": pose_id,
                "pose_name": pose_name,
                "title": f"{meta['name']} · {pose_name}",
                "id": f"{key}_{pose_id}",
            })
    return items


def main() -> int:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    MANIFEST.parent.mkdir(parents=True, exist_ok=True)
    items = build_catalog()
    assert len(items) == 200, len(items)

    manifest_items = []
    written = skipped = 0
    for item in items:
        n = item["n"]
        fname = f"hq_friend_{n:03d}_{item['id']}.png"
        path = OUT_DIR / fname
        rel = f"/assets/cliparts/{fname}"
        if path.is_file() and path.stat().st_size > 8000 and not FORCE:
            skipped += 1
            print(f"[skip] {fname}")
        else:
            render(item["char"], item["pose"]).save(path, "PNG", optimize=True)
            written += 1
            print(f"[ok] {fname}")

        manifest_items.append({
            "title": item["title"],
            "category_slug": "character",
            "image_path": rel,
            "hashtags": f"#캐릭터 #프렌즈 #귀여운 #스티커 #라벨 #클립아트 #{item['char_name']} #{item['pose']}",
            "description": f"{item['char_name']} 프렌즈 스타일 · {item['pose_name']}",
            "sort_order": 4000 + n,
        })

    MANIFEST.write_text(json.dumps({"count": len(manifest_items), "items": manifest_items}, ensure_ascii=False, indent=2), encoding="utf-8")
    print(json.dumps({"written": written, "skipped": skipped, "total": len(manifest_items), "manifest": str(MANIFEST)}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
