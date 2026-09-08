#!/usr/bin/env python3
"""Generate 3 cute mascot characters × 30 expressions/actions = 90 cliparts.

Characters:
  mochi  — 모찌 (크림 토끼)
  kong   — 콩이 (꿀색 곰)
  byeol  — 별이 (라벤더 고양이)

Outputs:
  public/assets/cliparts/hq_char_{NNN}_{char}_{pose}.png
  storage/imports/clipart_character_manifest.json
"""
from __future__ import annotations

import json
import math
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "cliparts"
MANIFEST = ROOT / "storage" / "imports" / "clipart_character_manifest.json"
SIZE = 768
FORCE = False


def rgba(c: tuple[int, int, int], a: int = 255) -> tuple[int, int, int, int]:
    return (c[0], c[1], c[2], a)


def lerp(a: float, b: float, t: float) -> float:
    return a + (b - a) * t


def shadow(base: Image.Image, layer: Image.Image, dx: int = 4, dy: int = 8, blur: int = 8, alpha: int = 55) -> None:
    alpha_m = layer.split()[-1]
    sh = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    black = Image.new("RGBA", (SIZE, SIZE), (30, 25, 40, alpha))
    sh.paste(black, (dx, dy), alpha_m)
    sh = sh.filter(ImageFilter.GaussianBlur(blur))
    base.alpha_composite(sh)
    base.alpha_composite(layer)


# --- character palettes ---
CHARS = {
    "mochi": {
        "name": "모찌",
        "name_en": "mochi",
        "body": (255, 236, 220),
        "body_dark": (245, 210, 190),
        "accent": (255, 150, 170),
        "ear_in": (255, 190, 200),
        "outline": (90, 60, 70),
        "kind": "bunny",
    },
    "kong": {
        "name": "콩이",
        "name_en": "kong",
        "body": (255, 196, 120),
        "body_dark": (235, 160, 85),
        "accent": (255, 120, 90),
        "ear_in": (255, 170, 130),
        "outline": (90, 55, 35),
        "kind": "bear",
    },
    "byeol": {
        "name": "별이",
        "name_en": "byeol",
        "body": (210, 195, 245),
        "body_dark": (175, 155, 220),
        "accent": (255, 170, 210),
        "ear_in": (235, 200, 240),
        "outline": (70, 55, 100),
        "kind": "cat",
    },
}


POSES: list[tuple[str, str]] = [
    ("smile", "방긋 미소"),
    ("happy", "활짝 웃음"),
    ("wink", "윙크"),
    ("love", "하트눈"),
    ("surprise", "깜짝"),
    ("sleepy", "졸린"),
    ("cry", "울먹"),
    ("pout", "뾰로통"),
    ("shy", "수줍은"),
    ("think", "생각중"),
    ("wave", "손인사"),
    ("cheer", "만세"),
    ("jump", "점프"),
    ("sit", "앉기"),
    ("run", "달리기"),
    ("eat", "간식먹기"),
    ("drink", "음료마시기"),
    ("hug", "하트안기"),
    ("peek", "빼꼼"),
    ("dance", "댄스"),
    ("bow", "인사"),
    ("stretch", "기지개"),
    ("cold", "추워"),
    ("hot", "더워"),
    ("cake", "생일케이크"),
    ("gift", "선물"),
    ("phone", "전화"),
    ("read", "독서"),
    ("sleep", "꿀잠"),
    ("yay", "야호"),
]


def oval(d: ImageDraw.ImageDraw, cx: float, cy: float, rx: float, ry: float, fill=None, outline=None, width: int = 1) -> None:
    d.ellipse([cx - rx, cy - ry, cx + rx, cy + ry], fill=fill, outline=outline, width=width)


def draw_ears(d: ImageDraw.ImageDraw, ch: dict, cx: float, cy: float, scale: float, kind: str) -> None:
    ol, body, ear_in = ch["outline"], ch["body"], ch["ear_in"]
    if kind == "bunny":
        for side in (-1, 1):
            tip_x = cx + side * 70 * scale
            tip_y = cy - 260 * scale
            base_l = (cx + side * 40 * scale, cy - 120 * scale)
            base_r = (cx + side * 100 * scale, cy - 130 * scale)
            d.polygon([base_l, (tip_x, tip_y), base_r], fill=rgba(body), outline=rgba(ol))
            d.polygon([
                (cx + side * 52 * scale, cy - 140 * scale),
                (tip_x, tip_y + 30 * scale),
                (cx + side * 85 * scale, cy - 145 * scale),
            ], fill=rgba(ear_in))
    elif kind == "bear":
        for side in (-1, 1):
            ex = cx + side * 115 * scale
            ey = cy - 145 * scale
            oval(d, ex, ey, 48 * scale, 48 * scale, fill=rgba(body), outline=rgba(ol), width=max(2, int(4 * scale)))
            oval(d, ex, ey, 24 * scale, 24 * scale, fill=rgba(ear_in))
    else:  # cat
        for side in (-1, 1):
            tip = (cx + side * 95 * scale, cy - 230 * scale)
            b1 = (cx + side * 35 * scale, cy - 120 * scale)
            b2 = (cx + side * 130 * scale, cy - 135 * scale)
            d.polygon([b1, tip, b2], fill=rgba(body), outline=rgba(ol))
            d.polygon([
                (cx + side * 55 * scale, cy - 145 * scale),
                (tip[0], tip[1] + 35 * scale),
                (cx + side * 105 * scale, cy - 150 * scale),
            ], fill=rgba(ear_in))


def draw_face(d: ImageDraw.ImageDraw, ch: dict, cx: float, cy: float, scale: float, expression: str) -> None:
    ol = ch["outline"]
    accent = ch["accent"]
    # blush (solid soft pink — alpha on transparent reads muddy)
    if expression not in ("angry",):
        blush = (
            int(lerp(255, accent[0], 0.4)),
            int(lerp(255, accent[1], 0.4)),
            int(lerp(255, accent[2], 0.4)),
        )
        oval(d, cx - 70 * scale, cy + 35 * scale, 28 * scale, 16 * scale, fill=rgba(blush))
        oval(d, cx + 70 * scale, cy + 35 * scale, 28 * scale, 16 * scale, fill=rgba(blush))

    eye_y = cy - 15 * scale
    eye_dx = 48 * scale
    eye_r = 12 * scale

    if expression == "love":
        for side in (-1, 1):
            hx, hy = cx + side * eye_dx, eye_y
            # heart eye
            oval(d, hx - 8 * scale, hy - 4 * scale, 10 * scale, 10 * scale, fill=rgba(accent))
            oval(d, hx + 8 * scale, hy - 4 * scale, 10 * scale, 10 * scale, fill=rgba(accent))
            d.polygon([
                (hx - 16 * scale, hy),
                (hx, hy + 18 * scale),
                (hx + 16 * scale, hy),
            ], fill=rgba(accent))
    elif expression == "wink":
        oval(d, cx - eye_dx, eye_y, eye_r, eye_r * 1.15, fill=rgba(ol))
        oval(d, cx - eye_dx + 3 * scale, eye_y - 3 * scale, 4 * scale, 4 * scale, fill=rgba((255, 255, 255)))
        # wink line
        d.arc([cx + eye_dx - 16 * scale, eye_y - 8 * scale, cx + eye_dx + 16 * scale, eye_y + 12 * scale], 200, 340, fill=rgba(ol), width=max(2, int(5 * scale)))
    elif expression == "sleepy":
        for side in (-1, 1):
            d.arc([cx + side * eye_dx - 16 * scale, eye_y - 6 * scale, cx + side * eye_dx + 16 * scale, eye_y + 14 * scale], 200, 340, fill=rgba(ol), width=max(2, int(5 * scale)))
    elif expression == "surprise":
        for side in (-1, 1):
            oval(d, cx + side * eye_dx, eye_y, eye_r * 1.35, eye_r * 1.45, fill=rgba(ol))
            oval(d, cx + side * eye_dx + 4 * scale, eye_y - 4 * scale, 5 * scale, 5 * scale, fill=rgba((255, 255, 255)))
    elif expression == "cry":
        for side in (-1, 1):
            oval(d, cx + side * eye_dx, eye_y, eye_r, eye_r * 1.1, fill=rgba(ol))
            # tears
            oval(d, cx + side * eye_dx, eye_y + 28 * scale, 7 * scale, 14 * scale, fill=rgba((120, 180, 255), 200))
    elif expression == "pout":
        for side in (-1, 1):
            # angled brows + small eyes
            d.line([
                (cx + side * eye_dx - 14 * scale, eye_y - 22 * scale),
                (cx + side * eye_dx + 14 * scale, eye_y - 14 * scale if side < 0 else eye_y - 22 * scale),
            ], fill=rgba(ol), width=max(2, int(4 * scale)))
            oval(d, cx + side * eye_dx, eye_y + 2 * scale, eye_r * 0.85, eye_r * 0.9, fill=rgba(ol))
    elif expression == "think":
        oval(d, cx - eye_dx, eye_y, eye_r, eye_r * 1.1, fill=rgba(ol))
        oval(d, cx - eye_dx + 3 * scale, eye_y - 3 * scale, 4 * scale, 4 * scale, fill=rgba((255, 255, 255)))
        d.arc([cx + eye_dx - 16 * scale, eye_y - 8 * scale, cx + eye_dx + 16 * scale, eye_y + 12 * scale], 200, 340, fill=rgba(ol), width=max(2, int(5 * scale)))
        # thought bubble
        oval(d, cx + 130 * scale, cy - 160 * scale, 28 * scale, 22 * scale, fill=rgba((255, 255, 255)), outline=rgba(ol), width=3)
        oval(d, cx + 105 * scale, cy - 120 * scale, 10 * scale, 8 * scale, fill=rgba((255, 255, 255)), outline=rgba(ol), width=2)
        oval(d, cx + 90 * scale, cy - 95 * scale, 6 * scale, 5 * scale, fill=rgba((255, 255, 255)), outline=rgba(ol), width=2)
    elif expression == "sleep":
        for side in (-1, 1):
            d.arc([cx + side * eye_dx - 16 * scale, eye_y - 4 * scale, cx + side * eye_dx + 16 * scale, eye_y + 16 * scale], 200, 340, fill=rgba(ol), width=max(2, int(5 * scale)))
        # zzz
        font_y = cy - 180 * scale
        for i, chz in enumerate("Zz"):
            # simple Z with lines
            zx = cx + 100 * scale + i * 28 * scale
            zy = font_y - i * 30 * scale
            d.line([(zx, zy), (zx + 22 * scale, zy)], fill=rgba(ol), width=3)
            d.line([(zx + 22 * scale, zy), (zx, zy + 22 * scale)], fill=rgba(ol), width=3)
            d.line([(zx, zy + 22 * scale), (zx + 22 * scale, zy + 22 * scale)], fill=rgba(ol), width=3)
    else:
        # default open eyes (smile, happy, shy, wave, etc.)
        for side in (-1, 1):
            if expression == "happy":
                d.arc([cx + side * eye_dx - 16 * scale, eye_y - 10 * scale, cx + side * eye_dx + 16 * scale, eye_y + 14 * scale], 200, 340, fill=rgba(ol), width=max(2, int(5 * scale)))
            else:
                oval(d, cx + side * eye_dx, eye_y, eye_r, eye_r * 1.15, fill=rgba(ol))
                oval(d, cx + side * eye_dx + 3 * scale, eye_y - 3 * scale, 4 * scale, 4 * scale, fill=rgba((255, 255, 255)))

    # nose / muzzle
    if ch["kind"] == "bear":
        oval(d, cx, cy + 25 * scale, 22 * scale, 16 * scale, fill=rgba(ch["body_dark"]))
        oval(d, cx, cy + 18 * scale, 10 * scale, 7 * scale, fill=rgba(ol))
    elif ch["kind"] == "cat":
        d.polygon([
            (cx, cy + 10 * scale),
            (cx - 10 * scale, cy + 22 * scale),
            (cx + 10 * scale, cy + 22 * scale),
        ], fill=rgba(ol))
        # whiskers
        for side in (-1, 1):
            for dy in (-8, 0, 8):
                d.line([
                    (cx + side * 25 * scale, cy + 20 * scale + dy * scale * 0.3),
                    (cx + side * 85 * scale, cy + 15 * scale + dy * scale),
                ], fill=rgba(ol, 160), width=2)
    else:
        oval(d, cx, cy + 18 * scale, 8 * scale, 6 * scale, fill=rgba(ol))

    # mouth
    my = cy + 55 * scale
    if expression in ("happy", "yay", "cheer", "dance", "jump"):
        d.arc([cx - 28 * scale, my - 18 * scale, cx + 28 * scale, my + 22 * scale], 20, 160, fill=rgba(ol), width=max(2, int(5 * scale)))
        oval(d, cx, my + 8 * scale, 12 * scale, 8 * scale, fill=rgba(accent, 160))
    elif expression == "surprise":
        oval(d, cx, my + 5 * scale, 12 * scale, 16 * scale, fill=rgba(ol))
    elif expression == "pout":
        d.arc([cx - 18 * scale, my - 5 * scale, cx + 18 * scale, my + 20 * scale], 200, 340, fill=rgba(ol), width=max(2, int(4 * scale)))
    elif expression == "cry":
        d.arc([cx - 20 * scale, my - 5 * scale, cx + 20 * scale, my + 22 * scale], 200, 340, fill=rgba(ol), width=max(2, int(4 * scale)))
    elif expression in ("sleepy", "sleep"):
        d.arc([cx - 16 * scale, my - 8 * scale, cx + 16 * scale, my + 12 * scale], 20, 160, fill=rgba(ol), width=max(2, int(3 * scale)))
    elif expression == "shy":
        d.arc([cx - 14 * scale, my - 6 * scale, cx + 14 * scale, my + 14 * scale], 20, 160, fill=rgba(ol), width=max(2, int(3 * scale)))
        # hands on cheeks drawn in pose
    else:
        d.arc([cx - 22 * scale, my - 12 * scale, cx + 22 * scale, my + 16 * scale], 20, 160, fill=rgba(ol), width=max(2, int(4 * scale)))


def draw_limb_arm(d: ImageDraw.ImageDraw, ch: dict, x0, y0, x1, y1, thick: float) -> None:
    ol, body = ch["outline"], ch["body"]
    tw = max(12, int(thick))
    d.line([(x0, y0), (x1, y1)], fill=rgba(ol), width=tw + 6)
    d.line([(x0, y0), (x1, y1)], fill=rgba(body), width=tw)
    oval(d, x0, y0, tw * 0.55, tw * 0.55, fill=rgba(body), outline=rgba(ol), width=3)
    oval(d, x1, y1, tw * 0.7, tw * 0.7, fill=rgba(body), outline=rgba(ol), width=3)


def draw_leg(d: ImageDraw.ImageDraw, ch: dict, x0, y0, x1, y1, thick: float) -> None:
    draw_limb_arm(d, ch, x0, y0, x1, y1, thick)


def draw_props(d: ImageDraw.ImageDraw, pose: str, cx: float, cy: float, scale: float, ch: dict) -> None:
    ol = ch["outline"]
    if pose == "hug":
        # big heart
        hx, hy = cx, cy + 40 * scale
        oval(d, hx - 35 * scale, hy - 20 * scale, 32 * scale, 30 * scale, fill=rgba((255, 105, 140)))
        oval(d, hx + 35 * scale, hy - 20 * scale, 32 * scale, 30 * scale, fill=rgba((255, 105, 140)))
        d.polygon([(hx - 62 * scale, hy - 5 * scale), (hx, hy + 55 * scale), (hx + 62 * scale, hy - 5 * scale)], fill=rgba((255, 105, 140)))
    elif pose == "eat":
        # cookie
        oval(d, cx + 110 * scale, cy + 40 * scale, 36 * scale, 36 * scale, fill=rgba((210, 150, 90)), outline=rgba(ol), width=3)
        for a in range(0, 360, 60):
            px = cx + 110 * scale + 16 * scale * math.cos(math.radians(a))
            py = cy + 40 * scale + 16 * scale * math.sin(math.radians(a))
            oval(d, px, py, 4 * scale, 4 * scale, fill=rgba((140, 90, 50)))
    elif pose == "drink":
        # cup
        x = cx + 105 * scale
        d.rectangle([x - 28 * scale, cy + 10 * scale, x + 28 * scale, cy + 90 * scale], fill=rgba((255, 255, 255)), outline=rgba(ol), width=3)
        d.polygon([(x - 28 * scale, cy + 90 * scale), (x + 28 * scale, cy + 90 * scale), (x + 20 * scale, cy + 120 * scale), (x - 20 * scale, cy + 120 * scale)], fill=rgba(ch["accent"]), outline=rgba(ol))
        d.arc([x + 20 * scale, cy + 30 * scale, x + 50 * scale, cy + 70 * scale], -70, 70, fill=rgba(ol), width=3)
        # straw
        d.line([(x + 8 * scale, cy - 10 * scale), (x + 8 * scale, cy + 40 * scale)], fill=rgba((255, 120, 140)), width=4)
    elif pose == "cake":
        x, y = cx + 100 * scale, cy + 50 * scale
        d.rectangle([x - 40 * scale, y, x + 40 * scale, y + 55 * scale], fill=rgba((255, 230, 240)), outline=rgba(ol), width=3)
        d.ellipse([x - 40 * scale, y - 12 * scale, x + 40 * scale, y + 12 * scale], fill=rgba((255, 200, 220)), outline=rgba(ol), width=2)
        d.rectangle([x - 4 * scale, y - 40 * scale, x + 4 * scale, y - 8 * scale], fill=rgba((255, 220, 100)))
        oval(d, x, y - 45 * scale, 8 * scale, 10 * scale, fill=rgba((255, 90, 70)))
    elif pose == "gift":
        x, y = cx + 100 * scale, cy + 40 * scale
        d.rectangle([x - 40 * scale, y, x + 40 * scale, y + 70 * scale], fill=rgba((255, 140, 160)), outline=rgba(ol), width=3)
        d.rectangle([x - 8 * scale, y, x + 8 * scale, y + 70 * scale], fill=rgba((255, 220, 100)))
        d.rectangle([x - 40 * scale, y + 25 * scale, x + 40 * scale, y + 40 * scale], fill=rgba((255, 220, 100)))
        oval(d, x, y - 5 * scale, 18 * scale, 12 * scale, fill=rgba((255, 220, 100)))
    elif pose == "phone":
        x, y = cx + 115 * scale, cy + 20 * scale
        round_box = [x - 22 * scale, y - 40 * scale, x + 22 * scale, y + 45 * scale]
        d.rounded_rectangle(round_box, radius=10 * scale, fill=rgba((60, 65, 80)), outline=rgba(ol), width=3)
        d.rounded_rectangle([x - 16 * scale, y - 30 * scale, x + 16 * scale, y + 28 * scale], radius=4 * scale, fill=rgba((160, 210, 255)))
    elif pose == "read":
        x, y = cx + 95 * scale, cy + 50 * scale
        d.polygon([
            (x - 50 * scale, y - 10 * scale), (x, y - 25 * scale), (x, y + 45 * scale), (x - 50 * scale, y + 35 * scale)
        ], fill=rgba((255, 245, 220)), outline=rgba(ol))
        d.polygon([
            (x + 50 * scale, y - 10 * scale), (x, y - 25 * scale), (x, y + 45 * scale), (x + 50 * scale, y + 35 * scale)
        ], fill=rgba((255, 235, 200)), outline=rgba(ol))
    elif pose == "cold":
        # scarf
        d.arc([cx - 90 * scale, cy + 40 * scale, cx + 90 * scale, cy + 140 * scale], 200, 340, fill=rgba(ch["accent"]), width=int(22 * scale))
        d.rectangle([cx + 40 * scale, cy + 90 * scale, cx + 70 * scale, cy + 160 * scale], fill=rgba(ch["accent"]), outline=rgba(ol), width=2)
    elif pose == "hot":
        # sweat drops
        for i, (dx, dy) in enumerate(((-90, -40), (95, -60), (80, -20))):
            oval(d, cx + dx * scale, cy + dy * scale, 8 * scale, 14 * scale, fill=rgba((120, 190, 255), 200))
        # fan
        fx, fy = cx + 120 * scale, cy + 30 * scale
        for a in range(0, 360, 90):
            d.pieslice([fx - 35 * scale, fy - 35 * scale, fx + 35 * scale, fy + 35 * scale], a, a + 50, fill=rgba((255, 230, 150)), outline=rgba(ol))
        oval(d, fx, fy, 8 * scale, 8 * scale, fill=rgba(ol))
    elif pose == "think":
        pass  # bubble in face
    elif pose == "peek":
        # wall edge
        d.rectangle([0, 200, cx - 40 * scale, SIZE], fill=rgba((230, 225, 235)), outline=rgba(ol), width=3)


def pose_layout(pose: str) -> dict:
    """Body transform parameters."""
    base = {
        "body_cy": 400,
        "body_rx": 150,
        "body_ry": 160,
        "head_cy": 280,
        "head_r": 145,
        "scale": 1.0,
        "arm_l": (-130, 380, -180, 480),
        "arm_r": (130, 380, 180, 480),
        "leg_l": (-55, 530, -70, 640),
        "leg_r": (55, 530, 70, 640),
        "expr": "smile",
        "tilt": 0,
        "hide_legs": False,
    }
    overrides = {
        "smile": {"expr": "smile"},
        "happy": {"expr": "happy", "arm_l": (-140, 360, -160, 280), "arm_r": (140, 360, 160, 280)},
        "wink": {"expr": "wink"},
        "love": {"expr": "love", "arm_l": (-120, 400, -40, 470), "arm_r": (120, 400, 40, 470)},
        "surprise": {"expr": "surprise", "arm_l": (-150, 350, -170, 250), "arm_r": (150, 350, 170, 250)},
        "sleepy": {"expr": "sleepy", "tilt": -6},
        "cry": {"expr": "cry", "arm_l": (-100, 390, -50, 330), "arm_r": (100, 390, 50, 330)},
        "pout": {"expr": "pout", "arm_l": (-110, 420, -40, 470), "arm_r": (110, 420, 40, 470)},
        "shy": {"expr": "shy", "arm_l": (-90, 380, -55, 320), "arm_r": (90, 380, 55, 320)},
        "think": {"expr": "think", "arm_l": (-100, 400, -40, 340), "arm_r": (130, 390, 170, 470)},
        "wave": {"expr": "happy", "arm_r": (140, 340, 190, 220), "arm_l": (-130, 390, -170, 480)},
        "cheer": {"expr": "happy", "arm_l": (-140, 340, -160, 200), "arm_r": (140, 340, 160, 200)},
        "jump": {"expr": "happy", "body_cy": 360, "head_cy": 240, "leg_l": (-70, 500, -100, 560), "leg_r": (70, 500, 100, 560),
                 "arm_l": (-150, 330, -180, 250), "arm_r": (150, 330, 180, 250)},
        "sit": {"expr": "smile", "body_cy": 450, "body_ry": 130, "head_cy": 310, "hide_legs": True,
                "arm_l": (-130, 430, -160, 520), "arm_r": (130, 430, 160, 520)},
        "run": {"expr": "happy", "tilt": 8, "arm_l": (-100, 360, -40, 300), "arm_r": (120, 380, 180, 460),
                "leg_l": (-40, 520, -110, 620), "leg_r": (60, 520, 120, 600)},
        "eat": {"expr": "happy", "arm_r": (100, 380, 150, 420), "arm_l": (-120, 400, -150, 480)},
        "drink": {"expr": "smile", "arm_r": (90, 360, 140, 400), "arm_l": (-120, 400, -150, 480)},
        "hug": {"expr": "love", "arm_l": (-80, 380, -20, 430), "arm_r": (80, 380, 20, 430)},
        "peek": {"expr": "wink", "body_cy": 420, "head_cy": 300, "arm_l": (-80, 400, -20, 360),
                 "arm_r": (40, 400, 20, 360), "hide_legs": True},
        "dance": {"expr": "happy", "tilt": -10, "arm_l": (-150, 340, -200, 280), "arm_r": (120, 360, 80, 250),
                  "leg_l": (-40, 530, 10, 640), "leg_r": (70, 520, 130, 620)},
        "bow": {"expr": "shy", "body_cy": 430, "head_cy": 360, "tilt": 25,
                "arm_l": (-100, 450, -130, 520), "arm_r": (100, 450, 130, 520)},
        "stretch": {"expr": "sleepy", "arm_l": (-140, 300, -180, 200), "arm_r": (140, 300, 180, 200)},
        "cold": {"expr": "pout", "arm_l": (-90, 400, -40, 450), "arm_r": (90, 400, 40, 450)},
        "hot": {"expr": "surprise", "arm_r": (120, 360, 170, 320), "arm_l": (-130, 400, -160, 480)},
        "cake": {"expr": "happy", "arm_r": (90, 380, 140, 430), "arm_l": (-120, 400, -150, 480)},
        "gift": {"expr": "love", "arm_r": (90, 390, 140, 450), "arm_l": (-100, 390, -40, 450)},
        "phone": {"expr": "smile", "arm_r": (100, 340, 150, 300), "arm_l": (-120, 400, -150, 480)},
        "read": {"expr": "smile", "arm_l": (-60, 420, 40, 460), "arm_r": (60, 420, 140, 460)},
        "sleep": {"expr": "sleep", "tilt": -20, "body_cy": 460, "head_cy": 380, "hide_legs": True,
                  "arm_l": (-80, 460, -40, 500), "arm_r": (100, 450, 150, 500)},
        "yay": {"expr": "happy", "arm_l": (-150, 320, -190, 200), "arm_r": (150, 320, 190, 200),
                "leg_l": (-60, 530, -40, 640), "leg_r": (60, 530, 40, 640)},
    }
    base.update(overrides.get(pose, {}))
    return base


def render_character(char_key: str, pose: str) -> Image.Image:
    ch = CHARS[char_key]
    layout = pose_layout(pose)
    scale = layout["scale"]
    img = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)

    cx = SIZE / 2
    body_cy = layout["body_cy"]
    head_cy = layout["head_cy"]
    head_r = layout["head_r"] * scale
    brx, bry = layout["body_rx"] * scale, layout["body_ry"] * scale

    # props behind for peek wall already drawn first
    if pose == "peek":
        draw_props(d, pose, cx, body_cy, scale, ch)

    # legs
    if not layout["hide_legs"]:
        ll = layout["leg_l"]
        lr = layout["leg_r"]
        draw_leg(d, ch, cx + ll[0] * scale, ll[1], cx + ll[2] * scale, ll[3], 42 * scale)
        draw_leg(d, ch, cx + lr[0] * scale, lr[1], cx + lr[2] * scale, lr[3], 42 * scale)
        # feet
        oval(d, cx + ll[2] * scale, ll[3], 28 * scale, 16 * scale, fill=rgba(ch["body"]), outline=rgba(ch["outline"]), width=3)
        oval(d, cx + lr[2] * scale, lr[3], 28 * scale, 16 * scale, fill=rgba(ch["body"]), outline=rgba(ch["outline"]), width=3)

    # body
    oval(d, cx, body_cy, brx, bry, fill=rgba(ch["body"]), outline=rgba(ch["outline"]), width=5)
    # belly patch (tinted body, not translucent white)
    belly = (
        int(lerp(ch["body"][0], 255, 0.35)),
        int(lerp(ch["body"][1], 255, 0.35)),
        int(lerp(ch["body"][2], 255, 0.35)),
    )
    oval(d, cx, body_cy + 20 * scale, brx * 0.55, bry * 0.5, fill=rgba(belly))

    # arms
    al, ar = layout["arm_l"], layout["arm_r"]
    draw_limb_arm(d, ch, cx + al[0] * scale, al[1], cx + al[2] * scale, al[3], 40 * scale)
    draw_limb_arm(d, ch, cx + ar[0] * scale, ar[1], cx + ar[2] * scale, ar[3], 40 * scale)

    # head + ears
    draw_ears(d, ch, cx, head_cy, scale, ch["kind"])
    oval(d, cx, head_cy, head_r, head_r * 0.98, fill=rgba(ch["body"]), outline=rgba(ch["outline"]), width=5)

    # face
    draw_face(d, ch, cx, head_cy, scale, layout["expr"])

    # cheek star for byeol
    if ch["kind"] == "cat":
        for side in (-1, 1):
            sx = cx + side * 85 * scale
            sy = head_cy + 40 * scale
            star(d, sx, sy, 10 * scale, rgba(ch["accent"], 200))

    # sit base
    if pose == "sit":
        oval(d, cx, 620, 120 * scale, 28 * scale, fill=rgba(ch["body"]), outline=rgba(ch["outline"]), width=3)

    if pose != "peek":
        draw_props(d, pose, cx, body_cy, scale, ch)

    # cold scarf drawn as prop over neck — redraw scarf on top
    if pose == "cold":
        d.arc([cx - 90 * scale, head_cy + 80 * scale, cx + 90 * scale, head_cy + 180 * scale], 200, 340, fill=rgba(ch["accent"]), width=int(20 * scale))

    tilt = layout["tilt"]
    if abs(tilt) > 0.5:
        layer = layer.rotate(tilt, resample=Image.BICUBIC, center=(cx, body_cy))

    shadow(img, layer)
    return img.filter(ImageFilter.SMOOTH)


def star(d: ImageDraw.ImageDraw, cx: float, cy: float, r: float, fill) -> None:
    pts = []
    for i in range(10):
        ang = math.radians(-90 + i * 36)
        rr = r if i % 2 == 0 else r * 0.45
        pts.append((cx + rr * math.cos(ang), cy + rr * math.sin(ang)))
    d.polygon(pts, fill=fill)


def build_catalog() -> list[dict]:
    items = []
    n = 0
    for char_key, meta in CHARS.items():
        for pose_id, pose_name in POSES:
            n += 1
            items.append({
                "n": n,
                "char": char_key,
                "char_name": meta["name"],
                "pose": pose_id,
                "pose_name": pose_name,
                "title": f"{meta['name']} · {pose_name}",
                "id": f"{char_key}_{pose_id}",
            })
    return items


def main() -> int:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    MANIFEST.parent.mkdir(parents=True, exist_ok=True)

    items = build_catalog()
    assert len(items) == 90, len(items)

    manifest_items = []
    written = skipped = 0

    for item in items:
        n = item["n"]
        fname = f"hq_char_{n:03d}_{item['id']}.png"
        path = OUT_DIR / fname
        rel = f"/assets/cliparts/{fname}"

        if path.is_file() and path.stat().st_size > 8000 and not FORCE:
            skipped += 1
            print(f"[skip] {fname}")
        else:
            img = render_character(item["char"], item["pose"])
            img.save(path, "PNG", optimize=True)
            written += 1
            print(f"[ok] {fname}")

        manifest_items.append({
            "title": item["title"],
            "category_slug": "character",
            "image_path": rel,
            "hashtags": f"#캐릭터 #귀여운 #라벨 #클립아트 #{item['char_name']} #{item['pose']} #표정 #행동",
            "description": f"{item['char_name']} 캐릭터 · {item['pose_name']} 클립아트",
            "sort_order": 3000 + n,
        })

    payload = {"count": len(manifest_items), "items": manifest_items}
    MANIFEST.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(json.dumps({"written": written, "skipped": skipped, "total": len(manifest_items), "manifest": str(MANIFEST)}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
