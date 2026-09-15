#!/usr/bin/env python3
"""Generate 100 high-quality 2D traffic/transportation cliparts (transparent PNG).

Flat illustration style: bold outline, soft shadow, no background fill.

Outputs:
  public/assets/cliparts/hq_traffic_{NNN}_{id}.png
  storage/imports/clipart_traffic_manifest.json
"""
from __future__ import annotations

import json
import math
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "cliparts"
MANIFEST = ROOT / "storage" / "imports" / "clipart_traffic_manifest.json"
SIZE = 768
TARGET = 100
CATEGORY = "traffic"
OUTLINE = (45, 48, 58)


def rgba(c, a=255):
    return (int(c[0]), int(c[1]), int(c[2]), int(a))


def mix(a, b, t):
    return (
        int(a[0] + (b[0] - a[0]) * t),
        int(a[1] + (b[1] - a[1]) * t),
        int(a[2] + (b[2] - a[2]) * t),
    )


def soft_shadow(layer: Image.Image, blur: int = 14, alpha: int = 55, dy: int = 10) -> Image.Image:
    a = layer.split()[-1]
    sh = Image.new("RGBA", layer.size, (0, 0, 0, 0))
    sh.paste(Image.new("RGBA", layer.size, (25, 28, 35, alpha)), (6, dy), a)
    sh = sh.filter(ImageFilter.GaussianBlur(blur))
    out = Image.new("RGBA", layer.size, (0, 0, 0, 0))
    out.alpha_composite(sh)
    out.alpha_composite(layer)
    return out


def canvas() -> Image.Image:
    return Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))


def finish(layer: Image.Image) -> Image.Image:
    return soft_shadow(layer).filter(ImageFilter.SMOOTH)


def wheel(d, cx, cy, r, tire=(40, 42, 50), hub=(220, 225, 235)):
    d.ellipse([cx - r, cy - r, cx + r, cy + r], fill=rgba(tire), outline=rgba(OUTLINE), width=5)
    d.ellipse([cx - r * 0.45, cy - r * 0.45, cx + r * 0.45, cy + r * 0.45], fill=rgba(hub), outline=rgba(OUTLINE), width=3)


def round_rect(d, box, r, fill=None, outline=None, width=4):
    d.rounded_rectangle(box, radius=r, fill=fill, outline=outline, width=width)


# ---------- vehicles ----------

def car(color=(70, 140, 230)):
    im = canvas()
    d = ImageDraw.Draw(im)
    # body
    round_rect(d, (120, 360, 650, 520), 40, rgba(color), rgba(OUTLINE), 6)
    # cabin
    d.polygon([(220, 360), (300, 250), (500, 250), (580, 360)], fill=rgba(mix(color, (255, 255, 255), 0.25)), outline=rgba(OUTLINE), width=6)
    d.polygon([(300, 250), (500, 250), (480, 350), (320, 350)], fill=rgba((180, 220, 245)), outline=rgba(OUTLINE), width=4)
    # lights
    round_rect(d, (125, 410, 175, 455), 10, rgba((255, 230, 120)), rgba(OUTLINE), 3)
    round_rect(d, (595, 410, 645, 455), 10, rgba((255, 90, 90)), rgba(OUTLINE), 3)
    wheel(d, 240, 520, 55)
    wheel(d, 530, 520, 55)
    return finish(im)


def taxi():
    im = car((255, 205, 50))
    d = ImageDraw.Draw(im)
    round_rect(d, (330, 210, 440, 255), 8, rgba((40, 42, 50)), rgba(OUTLINE), 3)
    return finish(im.split()[0].convert("RGBA") if False else im)  # already finished; redraw badge on new layer
    # Actually car() already finished - recreate properly


def taxi2():
    im = canvas()
    d = ImageDraw.Draw(im)
    color = (255, 205, 50)
    round_rect(d, (120, 360, 650, 520), 40, rgba(color), rgba(OUTLINE), 6)
    d.polygon([(220, 360), (300, 250), (500, 250), (580, 360)], fill=rgba(mix(color, (255, 255, 255), 0.2)), outline=rgba(OUTLINE), width=6)
    d.polygon([(300, 250), (500, 250), (480, 350), (320, 350)], fill=rgba((180, 220, 245)), outline=rgba(OUTLINE), width=4)
    round_rect(d, (320, 200, 450, 250), 8, rgba((40, 42, 50)), rgba(OUTLINE), 3)
    round_rect(d, (125, 410, 175, 455), 10, rgba((255, 230, 120)), rgba(OUTLINE), 3)
    round_rect(d, (595, 410, 645, 455), 10, rgba((255, 90, 90)), rgba(OUTLINE), 3)
    wheel(d, 240, 520, 55)
    wheel(d, 530, 520, 55)
    return finish(im)


def bus(color=(80, 170, 110)):
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (90, 220, 680, 500), 36, rgba(color), rgba(OUTLINE), 7)
    # windows
    for x in (140, 250, 360, 470):
        round_rect(d, (x, 260, x + 90, 360), 12, rgba((180, 220, 245)), rgba(OUTLINE), 4)
    round_rect(d, (580, 260, 650, 400), 12, rgba((160, 210, 240)), rgba(OUTLINE), 4)
    # door line
    d.line([(560, 250), (560, 490)], fill=rgba(OUTLINE), width=5)
    # stripe
    d.rectangle([90, 400, 680, 430], fill=rgba(mix(color, (255, 255, 255), 0.35)))
    wheel(d, 220, 510, 58)
    wheel(d, 560, 510, 58)
    return finish(im)


def truck(color=(230, 95, 70)):
    im = canvas()
    d = ImageDraw.Draw(im)
    # cargo
    round_rect(d, (100, 250, 480, 500), 20, fill=rgba((235, 225, 210)), outline=rgba(OUTLINE), width=6)
    d.line([(100, 370), (480, 370)], fill=rgba(OUTLINE), width=4)
    # cab
    round_rect(d, (480, 300, 680, 500), 28, fill=rgba(color), outline=rgba(OUTLINE), width=6)
    round_rect(d, (510, 320, 650, 410), 14, fill=rgba((180, 220, 245)), outline=rgba(OUTLINE), width=4)
    round_rect(d, (640, 430, 675, 470), 8, rgba((255, 230, 120)), rgba(OUTLINE), 3)
    wheel(d, 200, 520, 55)
    wheel(d, 380, 520, 55)
    wheel(d, 580, 520, 55)
    return finish(im)


def van(color=(90, 120, 200)):
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (110, 280, 670, 510), 34, fill=rgba(color), outline=rgba(OUTLINE), width=6)
    round_rect(d, (150, 310, 420, 420), 16, fill=rgba((180, 220, 245)), outline=rgba(OUTLINE), width=4)
    round_rect(d, (450, 310, 640, 420), 16, fill=rgba((170, 210, 240)), outline=rgba(OUTLINE), width=4)
    d.line([(440, 290), (440, 500)], fill=rgba(OUTLINE), width=5)
    wheel(d, 240, 520, 55)
    wheel(d, 560, 520, 55)
    return finish(im)


def motorcycle(color=(55, 60, 75)):
    im = canvas()
    d = ImageDraw.Draw(im)
    wheel(d, 230, 480, 70)
    wheel(d, 540, 480, 70)
    # body
    d.polygon([(260, 420), (400, 300), (500, 320), (520, 400), (300, 450)], fill=rgba(color), outline=rgba(OUTLINE))
    d.line([(400, 300), (400, 240), (360, 220)], fill=rgba(OUTLINE), width=8)
    d.ellipse([340, 200, 420, 250], fill=rgba((200, 80, 70)), outline=rgba(OUTLINE), width=4)
    # seat
    round_rect(d, (360, 340, 480, 390), 16, rgba((40, 42, 50)), rgba(OUTLINE), 3)
    return finish(im)


def bicycle(color=(50, 160, 140)):
    im = canvas()
    d = ImageDraw.Draw(im)
    wheel(d, 220, 480, 75, tire=(50, 52, 60), hub=(230, 235, 240))
    wheel(d, 560, 480, 75, tire=(50, 52, 60), hub=(230, 235, 240))
    # frame
    d.line([(220, 480), (380, 300), (520, 300), (560, 480)], fill=rgba(color), width=12)
    d.line([(380, 300), (380, 480)], fill=rgba(color), width=10)
    d.line([(380, 300), (300, 480)], fill=rgba(color), width=10)
    d.line([(520, 300), (500, 220)], fill=rgba(OUTLINE), width=8)
    d.arc([450, 190, 560, 260], 200, 340, fill=rgba(OUTLINE), width=8)
    d.ellipse([360, 280, 400, 320], fill=rgba((255, 200, 60)), outline=rgba(OUTLINE), width=3)
    return finish(im)


def scooter(color=(255, 120, 90)):
    im = canvas()
    d = ImageDraw.Draw(im)
    wheel(d, 250, 500, 55)
    wheel(d, 540, 500, 55)
    round_rect(d, (240, 430, 560, 480), 20, rgba(color), rgba(OUTLINE), 5)
    d.line([(300, 430), (300, 260), (360, 220)], fill=rgba(OUTLINE), width=10)
    round_rect(d, (330, 200, 420, 250), 14, rgba((40, 42, 50)), rgba(OUTLINE), 3)
    round_rect(d, (400, 340, 520, 400), 18, rgba(mix(color, (255, 255, 255), 0.2)), rgba(OUTLINE), 4)
    return finish(im)


def ambulance():
    im = canvas()
    d = ImageDraw.Draw(im)
    color = (250, 250, 252)
    round_rect(d, (100, 260, 680, 500), 30, fill=rgba(color), outline=rgba(OUTLINE), width=6)
    round_rect(d, (140, 290, 360, 400), 14, rgba((180, 220, 245)), rgba(OUTLINE), 4)
    # red cross
    d.rectangle([470, 310, 610, 350], fill=rgba((220, 50, 60)))
    d.rectangle([520, 270, 560, 390], fill=rgba((220, 50, 60)))
    round_rect(d, (100, 430, 680, 470), 0, fill=rgba((220, 50, 60)))
    wheel(d, 230, 520, 55)
    wheel(d, 560, 520, 55)
    return finish(im)


def police_car():
    im = canvas()
    d = ImageDraw.Draw(im)
    color = (70, 110, 200)
    round_rect(d, (120, 360, 650, 520), 40, rgba(color), rgba(OUTLINE), 6)
    d.polygon([(220, 360), (300, 250), (500, 250), (580, 360)], fill=rgba(mix(color, (255, 255, 255), 0.2)), outline=rgba(OUTLINE), width=6)
    d.polygon([(300, 250), (500, 250), (480, 350), (320, 350)], fill=rgba((180, 220, 245)), outline=rgba(OUTLINE), width=4)
    # light bar
    round_rect(d, (320, 210, 450, 250), 8, rgba((240, 240, 245)), rgba(OUTLINE), 3)
    d.rectangle([320, 210, 385, 250], fill=rgba((70, 130, 255)))
    d.rectangle([385, 210, 450, 250], fill=rgba((255, 70, 70)))
    wheel(d, 240, 520, 55)
    wheel(d, 530, 520, 55)
    return finish(im)


def fire_truck():
    im = canvas()
    d = ImageDraw.Draw(im)
    color = (220, 55, 55)
    round_rect(d, (90, 280, 500, 500), 18, rgba(color), rgba(OUTLINE), 6)
    round_rect(d, (500, 320, 690, 500), 24, rgba(color), rgba(OUTLINE), 6)
    round_rect(d, (530, 340, 660, 420), 12, rgba((180, 220, 245)), rgba(OUTLINE), 4)
    # ladder
    d.rectangle([130, 230, 470, 270], fill=rgba((230, 230, 235)), outline=rgba(OUTLINE), width=4)
    for x in range(160, 450, 40):
        d.line([(x, 230), (x, 270)], fill=rgba(OUTLINE), width=3)
    wheel(d, 200, 520, 52)
    wheel(d, 360, 520, 52)
    wheel(d, 600, 520, 52)
    return finish(im)


def train(color=(70, 130, 200)):
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (100, 240, 680, 480), 50, rgba(color), rgba(OUTLINE), 7)
    for x in (150, 290, 430):
        round_rect(d, (x, 280, x + 110, 380), 16, rgba((180, 220, 245)), rgba(OUTLINE), 4)
    round_rect(d, (560, 280, 650, 400), 16, rgba((160, 210, 240)), rgba(OUTLINE), 4)
    # coupler / front
    d.ellipse([650, 340, 720, 410], fill=rgba(mix(color, (0, 0, 0), 0.15)), outline=rgba(OUTLINE), width=4)
    # tracks wheels
    for x in (200, 350, 520):
        d.ellipse([x - 35, 470, x + 35, 540], fill=rgba((40, 42, 50)), outline=rgba(OUTLINE), width=4)
    d.rectangle([80, 540, 700, 560], fill=rgba((90, 95, 105)), outline=rgba(OUTLINE), width=3)
    return finish(im)


def subway():
    return train((55, 60, 75))


def airplane(color=(235, 240, 248)):
    im = canvas()
    d = ImageDraw.Draw(im)
    # fuselage
    d.ellipse([140, 320, 640, 450], fill=rgba(color), outline=rgba(OUTLINE), width=6)
    # wings
    d.polygon([(280, 380), (120, 520), (180, 540), (380, 420)], fill=rgba(mix(color, (70, 130, 200), 0.25)), outline=rgba(OUTLINE))
    d.polygon([(480, 380), (650, 520), (600, 540), (400, 420)], fill=rgba(mix(color, (70, 130, 200), 0.25)), outline=rgba(OUTLINE))
    # tail
    d.polygon([(560, 300), (640, 200), (660, 220), (600, 340)], fill=rgba((70, 130, 200)), outline=rgba(OUTLINE))
    # windows
    for x in range(260, 520, 45):
        d.ellipse([x, 350, x + 28, 380], fill=rgba((140, 190, 230)), outline=rgba(OUTLINE), width=2)
    return finish(im)


def helicopter(color=(90, 170, 120)):
    im = canvas()
    d = ImageDraw.Draw(im)
    # rotors
    d.ellipse([120, 160, 650, 220], fill=rgba((80, 85, 95), 180), outline=rgba(OUTLINE), width=4)
    d.line([(384, 140), (384, 280)], fill=rgba(OUTLINE), width=8)
    # body
    d.ellipse([240, 280, 530, 480], fill=rgba(color), outline=rgba(OUTLINE), width=6)
    round_rect(d, (290, 320, 480, 400), 20, rgba((180, 220, 245)), rgba(OUTLINE), 4)
    # tail
    d.polygon([(500, 360), (680, 300), (680, 340), (530, 400)], fill=rgba(mix(color, (0, 0, 0), 0.1)), outline=rgba(OUTLINE))
    d.ellipse([650, 270, 720, 340], outline=rgba(OUTLINE), width=5)
    # skids
    d.line([(260, 500), (500, 500)], fill=rgba(OUTLINE), width=8)
    d.line([(280, 460), (280, 500)], fill=rgba(OUTLINE), width=6)
    d.line([(480, 460), (480, 500)], fill=rgba(OUTLINE), width=6)
    return finish(im)


def ship(color=(70, 120, 190)):
    im = canvas()
    d = ImageDraw.Draw(im)
    # hull
    d.polygon([(120, 380), (660, 380), (600, 540), (180, 540)], fill=rgba(color), outline=rgba(OUTLINE))
    # cabin
    round_rect(d, (280, 250, 500, 380), 16, rgba((240, 245, 250)), rgba(OUTLINE), 5)
    for x in (310, 370, 430):
        round_rect(d, (x, 280, x + 45, 340), 8, rgba((160, 210, 240)), rgba(OUTLINE), 3)
    # chimney
    round_rect(d, (520, 200, 580, 380), 8, rgba((200, 70, 70)), rgba(OUTLINE), 4)
    # waves
    for x in range(140, 620, 60):
        d.arc([x, 560, x + 50, 620], 200, 340, fill=rgba((100, 170, 220)), width=5)
    return finish(im)


def sailboat():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.polygon([(180, 480), (600, 480), (520, 560), (260, 560)], fill=rgba((200, 90, 70)), outline=rgba(OUTLINE))
    d.line([(384, 480), (384, 160)], fill=rgba(OUTLINE), width=8)
    d.polygon([(390, 170), (390, 450), (620, 420)], fill=rgba((245, 248, 255)), outline=rgba(OUTLINE))
    d.polygon([(378, 220), (378, 450), (180, 420)], fill=rgba((255, 210, 80)), outline=rgba(OUTLINE))
    for x in range(160, 600, 55):
        d.arc([x, 570, x + 45, 630], 200, 340, fill=rgba((100, 170, 220)), width=4)
    return finish(im)


# ---------- signs & signals ----------

def traffic_light():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (280, 120, 490, 620), 40, rgba((55, 58, 68)), rgba(OUTLINE), 7)
    for i, col in enumerate([(230, 60, 60), (240, 190, 40), (60, 190, 90)]):
        cy = 200 + i * 140
        d.ellipse([320, cy - 50, 450, cy + 50], fill=rgba(col), outline=rgba(OUTLINE), width=5)
        d.ellipse([340, cy - 35, 380, cy - 5], fill=rgba((255, 255, 255), 90))
    return finish(im)


def stop_sign():
    im = canvas()
    d = ImageDraw.Draw(im)
    cx = cy = 360
    r = 220
    pts = [(cx + r * math.cos(math.radians(-90 + i * 45)), cy + r * math.sin(math.radians(-90 + i * 45))) for i in range(8)]
    d.polygon(pts, fill=rgba((210, 45, 55)), outline=rgba(OUTLINE))
    d.line([(384, 560), (384, 700)], fill=rgba((120, 125, 135)), width=28)
    # STOP text as bars
    round_rect(d, (250, 320, 520, 400), 8, fill=rgba((255, 255, 255)))
    return finish(im)


def yield_sign():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.polygon([(384, 580), (140, 200), (628, 200)], fill=rgba((240, 200, 40)), outline=rgba(OUTLINE))
    d.polygon([(384, 500), (220, 250), (548, 250)], fill=rgba((255, 255, 255)), outline=rgba(OUTLINE))
    d.line([(384, 580), (384, 700)], fill=rgba((120, 125, 135)), width=24)
    return finish(im)


def speed_sign(num_style=0):
    im = canvas()
    d = ImageDraw.Draw(im)
    d.ellipse([160, 140, 610, 590], fill=rgba((250, 250, 252)), outline=rgba((210, 50, 55)), width=28)
    d.ellipse([160, 140, 610, 590], outline=rgba(OUTLINE), width=6)
    # digits as simple shapes
    cx, cy = 384, 365
    if num_style == 0:  # 30
        round_rect(d, (250, 300, 340, 430), 12, outline=rgba(OUTLINE), width=14)
        d.ellipse([370, 300, 500, 430], outline=rgba(OUTLINE), width=14)
    elif num_style == 1:  # 50
        round_rect(d, (260, 300, 360, 360), 8, fill=rgba(OUTLINE))
        d.arc([260, 340, 400, 450], 200, 90, fill=rgba(OUTLINE), width=14)
        d.ellipse([420, 300, 540, 430], outline=rgba(OUTLINE), width=14)
    else:  # 80
        d.ellipse([250, 300, 370, 430], outline=rgba(OUTLINE), width=14)
        d.ellipse([400, 300, 520, 430], outline=rgba(OUTLINE), width=14)
    d.line([(384, 590), (384, 700)], fill=rgba((120, 125, 135)), width=24)
    return finish(im)


def no_entry():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.ellipse([150, 150, 620, 620], fill=rgba((210, 45, 55)), outline=rgba(OUTLINE), width=8)
    round_rect(d, (230, 340, 540, 420), 12, fill=rgba((255, 255, 255)))
    return finish(im)


def parking_sign():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (200, 140, 570, 580), 36, rgba((55, 110, 210)), rgba(OUTLINE), 7)
    # P
    round_rect(d, (300, 230, 360, 480), 8, fill=rgba((255, 255, 255)))
    d.ellipse([300, 230, 470, 380], outline=rgba((255, 255, 255)), width=40)
    d.ellipse([340, 270, 430, 340], fill=rgba((55, 110, 210)))
    d.line([(384, 580), (384, 700)], fill=rgba((120, 125, 135)), width=24)
    return finish(im)


def pedestrian_sign():
    im = canvas()
    d = ImageDraw.Draw(im)
    # blue square
    round_rect(d, (170, 140, 600, 580), 28, rgba((50, 110, 200)), rgba(OUTLINE), 6)
    # person
    d.ellipse([340, 200, 430, 290], fill=rgba((255, 255, 255)), outline=rgba(OUTLINE), width=4)
    d.polygon([(360, 300), (420, 300), (450, 420), (400, 420), (390, 500), (370, 500), (360, 420), (310, 420)], fill=rgba((255, 255, 255)), outline=rgba(OUTLINE))
    d.line([(384, 580), (384, 700)], fill=rgba((120, 125, 135)), width=24)
    return finish(im)


def bike_lane_sign():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (170, 140, 600, 580), 28, rgba((50, 110, 200)), rgba(OUTLINE), 6)
    # simple bike glyph
    d.ellipse([240, 360, 340, 460], outline=rgba((255, 255, 255)), width=10)
    d.ellipse([430, 360, 530, 460], outline=rgba((255, 255, 255)), width=10)
    d.line([(290, 410), (360, 300), (450, 300), (480, 410)], fill=rgba((255, 255, 255)), width=10)
    d.line([(360, 300), (360, 410)], fill=rgba((255, 255, 255)), width=8)
    d.line([(384, 580), (384, 700)], fill=rgba((120, 125, 135)), width=24)
    return finish(im)


def one_way():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (120, 280, 650, 480), 24, rgba((50, 110, 200)), rgba(OUTLINE), 6)
    d.polygon([(200, 300), (200, 460), (420, 460), (420, 520), (620, 380), (420, 240), (420, 300)], fill=rgba((255, 255, 255)), outline=rgba(OUTLINE))
    return finish(im)


def crosswalk():
    im = canvas()
    d = ImageDraw.Draw(im)
    # road
    round_rect(d, (80, 200, 690, 570), 20, rgba((70, 75, 85)), rgba(OUTLINE), 5)
    for i in range(6):
        x = 140 + i * 90
        d.rectangle([x, 240, x + 50, 530], fill=rgba((245, 245, 248)), outline=rgba(OUTLINE), width=3)
    return finish(im)


def road_cone():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.polygon([(384, 140), (560, 560), (208, 560)], fill=rgba((240, 110, 40)), outline=rgba(OUTLINE))
    d.polygon([(384, 260), (500, 420), (268, 420)], fill=rgba((250, 250, 252)))
    round_rect(d, (180, 540, 590, 600), 12, rgba((60, 62, 70)), rgba(OUTLINE), 4)
    return finish(im)


def barrier():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (100, 300, 670, 420), 16, rgba((240, 200, 40)), rgba(OUTLINE), 6)
    for i in range(5):
        x0 = 120 + i * 110
        d.polygon([(x0, 300), (x0 + 55, 300), (x0 + 95, 420), (x0 + 40, 420)], fill=rgba((40, 42, 50)))
    d.line([(150, 420), (150, 580)], fill=rgba(OUTLINE), width=14)
    d.line([(620, 420), (620, 580)], fill=rgba(OUTLINE), width=14)
    return finish(im)


def gas_pump():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (200, 160, 480, 580), 24, rgba((240, 245, 250)), rgba(OUTLINE), 6)
    round_rect(d, (230, 200, 450, 340), 14, rgba((60, 70, 90)), rgba(OUTLINE), 4)
    round_rect(d, (250, 380, 430, 520), 12, rgba((70, 140, 220)), rgba(OUTLINE), 4)
    # hose
    d.arc([450, 220, 620, 420], 270, 90, fill=rgba(OUTLINE), width=12)
    round_rect(d, (580, 400, 640, 500), 10, rgba((50, 55, 65)), rgba(OUTLINE), 4)
    round_rect(d, (180, 560, 500, 610), 10, rgba((70, 75, 85)), rgba(OUTLINE), 4)
    return finish(im)


def charging_station():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (220, 150, 520, 580), 28, rgba((240, 250, 245)), rgba(OUTLINE), 6)
    round_rect(d, (260, 200, 480, 360), 16, rgba((40, 160, 120)), rgba(OUTLINE), 4)
    # bolt
    d.polygon([(390, 220), (330, 300), (375, 300), (350, 350), (440, 260), (390, 260), (420, 220)], fill=rgba((255, 230, 80)), outline=rgba(OUTLINE))
    d.arc([500, 280, 650, 450], 270, 90, fill=rgba(OUTLINE), width=12)
    round_rect(d, (200, 560, 540, 610), 10, rgba((70, 75, 85)), rgba(OUTLINE), 4)
    return finish(im)


def parking_meter():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (280, 160, 490, 400), 30, rgba((90, 100, 120)), rgba(OUTLINE), 6)
    d.ellipse([310, 200, 460, 350], fill=rgba((235, 240, 245)), outline=rgba(OUTLINE), width=5)
    d.line([(384, 400), (384, 620)], fill=rgba((120, 125, 135)), width=28)
    round_rect(d, (300, 600, 470, 650), 10, rgba((70, 75, 85)), rgba(OUTLINE), 4)
    return finish(im)


def toll_gate():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.rectangle([160, 180, 220, 600], fill=rgba((90, 100, 120)), outline=rgba(OUTLINE), width=4)
    d.rectangle([550, 180, 610, 600], fill=rgba((90, 100, 120)), outline=rgba(OUTLINE), width=4)
    round_rect(d, (160, 160, 610, 240), 12, rgba((70, 140, 220)), rgba(OUTLINE), 5)
    # boom
    round_rect(d, (220, 320, 650, 370), 8, rgba((240, 200, 40)), rgba(OUTLINE), 4)
    for i in range(6):
        x = 240 + i * 70
        d.rectangle([x, 320, x + 35, 370], fill=rgba((40, 42, 50)))
    return finish(im)


def bridge():
    im = canvas()
    d = ImageDraw.Draw(im)
    # arches
    d.arc([80, 200, 400, 520], 200, 340, fill=rgba((90, 110, 140)), width=18)
    d.arc([370, 200, 690, 520], 200, 340, fill=rgba((90, 110, 140)), width=18)
    d.rectangle([80, 360, 690, 400], fill=rgba((70, 75, 85)), outline=rgba(OUTLINE), width=4)
    # water
    for x in range(100, 650, 50):
        d.arc([x, 520, x + 40, 580], 200, 340, fill=rgba((100, 170, 220)), width=4)
    # pillars
    d.rectangle([220, 400, 270, 560], fill=rgba((110, 120, 140)), outline=rgba(OUTLINE), width=3)
    d.rectangle([500, 400, 550, 560], fill=rgba((110, 120, 140)), outline=rgba(OUTLINE), width=3)
    return finish(im)


def tunnel():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.ellipse([100, 140, 670, 700], fill=rgba((90, 100, 120)), outline=rgba(OUTLINE), width=8)
    d.ellipse([180, 220, 590, 700], fill=rgba((35, 38, 48)), outline=rgba(OUTLINE), width=5)
    # road perspective
    d.polygon([(300, 700), (470, 700), (420, 400), (350, 400)], fill=rgba((70, 75, 85)))
    d.line([(384, 400), (384, 700)], fill=rgba((240, 200, 40)), width=6)
    return finish(im)


def traffic_camera():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.line([(384, 200), (384, 620)], fill=rgba((120, 125, 135)), width=22)
    round_rect(d, (300, 620, 470, 670), 10, rgba((70, 75, 85)), rgba(OUTLINE), 4)
    round_rect(d, (250, 180, 520, 320), 24, rgba((60, 65, 80)), rgba(OUTLINE), 6)
    d.ellipse([310, 200, 460, 300], fill=rgba((40, 120, 180)), outline=rgba(OUTLINE), width=5)
    d.ellipse([340, 220, 400, 270], fill=rgba((200, 230, 255), 160))
    return finish(im)


def gps_pin():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.ellipse([220, 120, 550, 450], fill=rgba((220, 55, 70)), outline=rgba(OUTLINE), width=7)
    d.polygon([(250, 360), (520, 360), (384, 620)], fill=rgba((220, 55, 70)), outline=rgba(OUTLINE))
    d.ellipse([300, 200, 470, 370], fill=rgba((255, 255, 255)), outline=rgba(OUTLINE), width=5)
    return finish(im)


def compass():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.ellipse([140, 140, 630, 630], fill=rgba((245, 248, 252)), outline=rgba(OUTLINE), width=8)
    d.ellipse([200, 200, 570, 570], outline=rgba((70, 130, 200)), width=6)
    d.polygon([(384, 180), (420, 384), (384, 360), (348, 384)], fill=rgba((220, 55, 70)), outline=rgba(OUTLINE))
    d.polygon([(384, 590), (420, 384), (384, 410), (348, 384)], fill=rgba((70, 100, 160)), outline=rgba(OUTLINE))
    return finish(im)


def steering_wheel():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.ellipse([140, 140, 630, 630], outline=rgba((40, 42, 50)), width=48)
    d.ellipse([140, 140, 630, 630], outline=rgba(OUTLINE), width=8)
    d.ellipse([300, 300, 470, 470], fill=rgba((60, 65, 80)), outline=rgba(OUTLINE), width=5)
    d.rectangle([200, 360, 570, 410], fill=rgba((40, 42, 50)), outline=rgba(OUTLINE), width=4)
    d.rectangle([360, 200, 410, 570], fill=rgba((40, 42, 50)), outline=rgba(OUTLINE), width=4)
    return finish(im)


def key_fob():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (260, 160, 510, 580), 40, rgba((50, 55, 70)), rgba(OUTLINE), 6)
    d.ellipse([320, 200, 450, 330], fill=rgba((70, 140, 220)), outline=rgba(OUTLINE), width=4)
    for y in (380, 450, 520):
        round_rect(d, (310, y, 460, y + 40), 12, rgba((90, 100, 120)), rgba(OUTLINE), 3)
    d.ellipse([360, 120, 420, 180], outline=rgba(OUTLINE), width=8)
    return finish(im)


def tire():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.ellipse([120, 120, 650, 650], fill=rgba((40, 42, 50)), outline=rgba(OUTLINE), width=8)
    d.ellipse([220, 220, 550, 550], fill=rgba((200, 205, 215)), outline=rgba(OUTLINE), width=6)
    d.ellipse([300, 300, 470, 470], fill=rgba((70, 75, 85)), outline=rgba(OUTLINE), width=5)
    for i in range(8):
        ang = math.radians(i * 45)
        x0, y0 = 384 + 70 * math.cos(ang), 384 + 70 * math.sin(ang)
        x1, y1 = 384 + 140 * math.cos(ang), 384 + 140 * math.sin(ang)
        d.line([(x0, y0), (x1, y1)], fill=rgba(OUTLINE), width=8)
    return finish(im)


def helmet():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.ellipse([150, 180, 620, 560], fill=rgba((70, 140, 230)), outline=rgba(OUTLINE), width=7)
    d.chord([150, 180, 620, 560], 0, 180, fill=rgba((40, 42, 50)), outline=rgba(OUTLINE))
    round_rect(d, (200, 400, 570, 480), 20, rgba((50, 55, 65)), rgba(OUTLINE), 4)
    d.ellipse([250, 220, 350, 300], fill=rgba((255, 255, 255), 100))
    return finish(im)


def seatbelt():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.polygon([(200, 140), (320, 140), (520, 620), (400, 620)], fill=rgba((70, 75, 90)), outline=rgba(OUTLINE))
    round_rect(d, (240, 300, 520, 400), 16, rgba((180, 185, 195)), rgba(OUTLINE), 5)
    round_rect(d, (300, 320, 460, 380), 10, rgba((90, 100, 120)), rgba(OUTLINE), 3)
    return finish(im)


def airbag():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.ellipse([140, 180, 630, 580], fill=rgba((245, 248, 252)), outline=rgba(OUTLINE), width=7)
    d.ellipse([220, 250, 550, 510], fill=rgba((220, 230, 240)), outline=rgba(OUTLINE), width=4)
    # steering hint
    d.ellipse([300, 320, 470, 450], outline=rgba((70, 80, 100)), width=10)
    return finish(im)


def map_route():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (120, 120, 650, 650), 28, rgba((235, 245, 235)), rgba(OUTLINE), 6)
    # roads
    d.line([(180, 500), (400, 300), (600, 220)], fill=rgba((90, 100, 120)), width=22)
    d.line([(180, 500), (400, 300), (600, 220)], fill=rgba((240, 200, 40)), width=6)
    d.ellipse([150, 470, 230, 550], fill=rgba((70, 140, 230)), outline=rgba(OUTLINE), width=4)
    d.ellipse([560, 180, 640, 260], fill=rgba((220, 55, 70)), outline=rgba(OUTLINE), width=4)
    return finish(im)


def parking_lot():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (90, 180, 680, 600), 20, rgba((75, 80, 90)), rgba(OUTLINE), 5)
    for i in range(4):
        x = 140 + i * 135
        d.rectangle([x, 230, x + 100, 550], outline=rgba((245, 245, 248)), width=5)
        # mini car
        col = [(70, 140, 230), (230, 95, 70), (80, 170, 110), (255, 205, 50)][i]
        round_rect(d, (x + 15, 320, x + 85, 430), 12, rgba(col), rgba(OUTLINE), 3)
    return finish(im)


def traffic_jam():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (80, 280, 690, 500), 16, rgba((70, 75, 85)), rgba(OUTLINE), 4)
    d.line([(80, 390), (690, 390)], fill=rgba((240, 200, 40)), width=5)
    colors = [(70, 140, 230), (230, 95, 70), (255, 205, 50), (80, 170, 110), (90, 120, 200)]
    for i, col in enumerate(colors):
        x = 100 + i * 115
        round_rect(d, (x, 300, x + 95, 370), 14, rgba(col), rgba(OUTLINE), 3)
        round_rect(d, (x + 10, 420, x + 85, 480), 14, rgba(mix(col, (0, 0, 0), 0.1)), rgba(OUTLINE), 3)
    return finish(im)


def roundabout():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.ellipse([120, 120, 650, 650], fill=rgba((70, 75, 85)), outline=rgba(OUTLINE), width=6)
    d.ellipse([250, 250, 520, 520], fill=rgba((90, 160, 100)), outline=rgba(OUTLINE), width=5)
    # arrows
    for ang in (20, 140, 260):
        a = math.radians(ang)
        x = 384 + 200 * math.cos(a)
        y = 384 + 200 * math.sin(a)
        d.polygon([(x, y), (x - 30, y - 40), (x + 30, y - 40)], fill=rgba((240, 200, 40)), outline=rgba(OUTLINE))
    return finish(im)


def overpass():
    im = canvas()
    d = ImageDraw.Draw(im)
    # lower road
    round_rect(d, (80, 420, 690, 520), 12, rgba((70, 75, 85)), rgba(OUTLINE), 4)
    # pillars
    d.rectangle([250, 300, 310, 420], fill=rgba((110, 120, 140)), outline=rgba(OUTLINE), width=3)
    d.rectangle([460, 300, 520, 420], fill=rgba((110, 120, 140)), outline=rgba(OUTLINE), width=3)
    # upper
    round_rect(d, (120, 220, 650, 310), 12, rgba((70, 75, 85)), rgba(OUTLINE), 4)
    d.line([(120, 265), (650, 265)], fill=rgba((240, 200, 40)), width=4)
    return finish(im)


def railway_crossing():
    im = canvas()
    d = ImageDraw.Draw(im)
    # crossbuck
    d.rectangle([200, 280, 580, 340], fill=rgba((245, 245, 248)), outline=rgba(OUTLINE), width=5)
    d.rectangle([200, 400, 580, 460], fill=rgba((245, 245, 248)), outline=rgba(OUTLINE), width=5)
    # rotate look via X
    d.line([(220, 200), (560, 540)], fill=rgba((210, 45, 55)), width=36)
    d.line([(560, 200), (220, 540)], fill=rgba((210, 45, 55)), width=36)
    d.line([(220, 200), (560, 540)], fill=rgba(OUTLINE), width=6)
    d.line([(560, 200), (220, 540)], fill=rgba(OUTLINE), width=6)
    d.line([(384, 540), (384, 700)], fill=rgba((120, 125, 135)), width=26)
    return finish(im)


def lighthouse():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.polygon([(300, 560), (480, 560), (440, 180), (340, 180)], fill=rgba((245, 248, 252)), outline=rgba(OUTLINE))
    d.rectangle([340, 280, 440, 340], fill=rgba((220, 55, 70)))
    d.rectangle([340, 400, 440, 460], fill=rgba((220, 55, 70)))
    round_rect(d, (320, 120, 460, 190), 10, rgba((255, 220, 80)), rgba(OUTLINE), 4)
    # rays
    d.polygon([(460, 140), (700, 80), (700, 160)], fill=rgba((255, 230, 120), 120))
    d.polygon([(320, 140), (70, 80), (70, 160)], fill=rgba((255, 230, 120), 120))
    round_rect(d, (240, 540, 540, 600), 12, rgba((90, 100, 120)), rgba(OUTLINE), 4)
    return finish(im)


def hot_air_balloon():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.ellipse([180, 100, 590, 480], fill=rgba((230, 90, 90)), outline=rgba(OUTLINE), width=6)
    d.chord([180, 100, 590, 480], 200, 340, fill=rgba((70, 140, 230)))
    d.chord([180, 100, 590, 480], 20, 160, fill=rgba((255, 200, 60)))
    d.line([(300, 450), (320, 560)], fill=rgba(OUTLINE), width=5)
    d.line([(470, 450), (450, 560)], fill=rgba(OUTLINE), width=5)
    round_rect(d, (310, 540, 460, 640), 12, rgba((160, 110, 70)), rgba(OUTLINE), 4)
    return finish(im)


def skateboard():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (100, 320, 670, 420), 40, rgba((70, 160, 200)), rgba(OUTLINE), 6)
    d.ellipse([180, 430, 260, 490], fill=rgba((40, 42, 50)), outline=rgba(OUTLINE), width=4)
    d.ellipse([510, 430, 590, 490], fill=rgba((40, 42, 50)), outline=rgba(OUTLINE), width=4)
    for x in (220, 384, 550):
        d.ellipse([x - 18, 345, x + 18, 395], fill=rgba((255, 200, 60)), outline=rgba(OUTLINE), width=3)
    return finish(im)


def roller_skates():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (220, 220, 520, 420), 30, rgba((220, 80, 120)), rgba(OUTLINE), 6)
    round_rect(d, (240, 250, 500, 340), 16, rgba((255, 200, 220)), rgba(OUTLINE), 3)
    for x in (260, 340, 420, 490):
        d.ellipse([x - 28, 430, x + 28, 500], fill=rgba((40, 42, 50)), outline=rgba(OUTLINE), width=4)
    return finish(im)


def cable_car():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.line([(80, 180), (700, 180)], fill=rgba(OUTLINE), width=10)
    d.line([(384, 180), (384, 260)], fill=rgba(OUTLINE), width=8)
    round_rect(d, (240, 250, 530, 520), 28, rgba((230, 90, 80)), rgba(OUTLINE), 6)
    round_rect(d, (270, 290, 500, 400), 16, rgba((180, 220, 245)), rgba(OUTLINE), 4)
    d.line([(384, 290), (384, 400)], fill=rgba(OUTLINE), width=4)
    return finish(im)


def monorail():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.rectangle([80, 480, 700, 530], fill=rgba((90, 100, 120)), outline=rgba(OUTLINE), width=4)
    round_rect(d, (140, 250, 640, 470), 48, rgba((70, 140, 220)), rgba(OUTLINE), 6)
    for x in (190, 310, 430):
        round_rect(d, (x, 290, x + 90, 390), 14, rgba((180, 220, 245)), rgba(OUTLINE), 3)
    return finish(im)


def ferry():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.polygon([(100, 400), (680, 400), (620, 540), (160, 540)], fill=rgba((70, 130, 190)), outline=rgba(OUTLINE))
    round_rect(d, (220, 260, 560, 400), 18, rgba((245, 248, 252)), rgba(OUTLINE), 5)
    for x in (260, 340, 420, 500):
        round_rect(d, (x, 290, x + 50, 350), 8, rgba((160, 210, 240)), rgba(OUTLINE), 3)
    for x in range(120, 640, 55):
        d.arc([x, 560, x + 45, 620], 200, 340, fill=rgba((100, 170, 220)), width=4)
    return finish(im)


def rocket():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.ellipse([300, 80, 470, 220], fill=rgba((240, 245, 250)), outline=rgba(OUTLINE), width=5)
    d.rectangle([300, 180, 470, 480], fill=rgba((240, 245, 250)), outline=rgba(OUTLINE), width=5)
    d.polygon([(300, 400), (220, 560), (300, 520)], fill=rgba((220, 70, 70)), outline=rgba(OUTLINE))
    d.polygon([(470, 400), (550, 560), (470, 520)], fill=rgba((220, 70, 70)), outline=rgba(OUTLINE))
    d.ellipse([340, 260, 430, 350], fill=rgba((120, 190, 230)), outline=rgba(OUTLINE), width=4)
    d.polygon([(330, 480), (384, 640), (440, 480)], fill=rgba((255, 160, 50)), outline=rgba(OUTLINE))
    d.polygon([(350, 500), (384, 600), (420, 500)], fill=rgba((255, 220, 80)))
    return finish(im)


def ufo():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.ellipse([280, 200, 490, 360], fill=rgba((180, 230, 200)), outline=rgba(OUTLINE), width=5)
    d.ellipse([140, 280, 630, 450], fill=rgba((90, 100, 130)), outline=rgba(OUTLINE), width=6)
    for x in (240, 340, 440, 540):
        d.ellipse([x, 360, x + 30, 390], fill=rgba((255, 220, 80)), outline=rgba(OUTLINE), width=2)
    d.polygon([(300, 440), (250, 620), (350, 450)], fill=rgba((180, 220, 255), 100))
    d.polygon([(420, 450), (520, 620), (470, 440)], fill=rgba((180, 220, 255), 100))
    return finish(im)


def delivery_scooter():
    im = canvas()
    d = ImageDraw.Draw(im)
    wheel(d, 240, 520, 50)
    wheel(d, 560, 520, 50)
    round_rect(d, (230, 430, 520, 490), 18, rgba((50, 55, 70)), rgba(OUTLINE), 5)
    # box
    round_rect(d, (400, 280, 600, 430), 16, rgba((230, 90, 70)), rgba(OUTLINE), 5)
    d.line([(300, 430), (300, 280), (360, 240)], fill=rgba(OUTLINE), width=9)
    round_rect(d, (330, 210, 410, 260), 12, rgba((40, 42, 50)), rgba(OUTLINE), 3)
    return finish(im)


def container_ship():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.polygon([(80, 420), (700, 420), (640, 560), (140, 560)], fill=rgba((60, 90, 140)), outline=rgba(OUTLINE))
    colors = [(220, 80, 70), (70, 140, 220), (240, 190, 50), (80, 170, 110), (150, 90, 180)]
    for i, col in enumerate(colors):
        x = 160 + i * 95
        round_rect(d, (x, 280, x + 85, 420), 6, rgba(col), rgba(OUTLINE), 3)
        round_rect(d, (x, 160, x + 85, 280), 6, rgba(mix(col, (0, 0, 0), 0.1)), rgba(OUTLINE), 3)
    return finish(im)


def tow_truck():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (100, 340, 420, 500), 20, rgba((255, 200, 50)), rgba(OUTLINE), 5)
    round_rect(d, (420, 360, 650, 500), 24, rgba((255, 200, 50)), rgba(OUTLINE), 5)
    round_rect(d, (460, 380, 620, 450), 12, rgba((180, 220, 245)), rgba(OUTLINE), 3)
    # boom
    d.line([(200, 340), (200, 200), (380, 220)], fill=rgba(OUTLINE), width=14)
    d.line([(380, 220), (380, 320)], fill=rgba((120, 125, 135)), width=6)
    wheel(d, 200, 520, 50)
    wheel(d, 340, 520, 50)
    wheel(d, 560, 520, 50)
    return finish(im)


def forklift():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (280, 280, 560, 500), 20, rgba((240, 180, 40)), rgba(OUTLINE), 5)
    round_rect(d, (320, 300, 480, 400), 12, rgba((180, 220, 245)), rgba(OUTLINE), 3)
    # mast
    d.rectangle([180, 160, 230, 520], fill=rgba((120, 130, 145)), outline=rgba(OUTLINE), width=4)
    d.rectangle([100, 200, 280, 230], fill=rgba((180, 185, 195)), outline=rgba(OUTLINE), width=3)
    d.rectangle([100, 300, 280, 330], fill=rgba((180, 185, 195)), outline=rgba(OUTLINE), width=3)
    wheel(d, 360, 530, 48)
    wheel(d, 500, 530, 48)
    return finish(im)


def tractor():
    im = canvas()
    d = ImageDraw.Draw(im)
    # big rear wheel
    wheel(d, 280, 420, 120, tire=(45, 48, 55), hub=(200, 160, 40))
    wheel(d, 560, 480, 70)
    round_rect(d, (300, 260, 560, 420), 24, rgba((70, 150, 80)), rgba(OUTLINE), 5)
    round_rect(d, (360, 200, 520, 300), 18, rgba((180, 220, 245)), rgba(OUTLINE), 4)
    round_rect(d, (520, 340, 640, 430), 16, rgba((90, 160, 90)), rgba(OUTLINE), 4)
    return finish(im)


def bulldozer():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (220, 280, 580, 480), 20, rgba((240, 180, 40)), rgba(OUTLINE), 5)
    round_rect(d, (300, 200, 500, 300), 16, rgba((180, 220, 245)), rgba(OUTLINE), 4)
    # blade
    round_rect(d, (80, 340, 240, 500), 12, rgba((140, 150, 160)), rgba(OUTLINE), 5)
    d.line([(220, 360), (300, 340)], fill=rgba(OUTLINE), width=10)
    # tracks
    round_rect(d, (240, 480, 560, 580), 30, rgba((50, 55, 65)), rgba(OUTLINE), 5)
    return finish(im)


def crane():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.rectangle([340, 400, 430, 620], fill=rgba((240, 180, 40)), outline=rgba(OUTLINE), width=5)
    round_rect(d, (280, 580, 490, 650), 12, rgba((70, 75, 85)), rgba(OUTLINE), 4)
    # boom
    d.line([(384, 400), (620, 160)], fill=rgba((240, 180, 40)), width=18)
    d.line([(384, 400), (620, 160)], fill=rgba(OUTLINE), width=5)
    d.line([(620, 160), (620, 320)], fill=rgba((120, 125, 135)), width=5)
    round_rect(d, (580, 320, 660, 380), 8, rgba((90, 100, 120)), rgba(OUTLINE), 3)
    return finish(im)


def traffic_cone_pair():
    return road_cone()


def speed_bump():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (80, 360, 690, 480), 40, rgba((240, 200, 40)), rgba(OUTLINE), 6)
    for i in range(7):
        x = 120 + i * 80
        d.polygon([(x, 360), (x + 40, 360), (x + 55, 480), (x + 15, 480)], fill=rgba((40, 42, 50)))
    return finish(im)


def manhole():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.ellipse([140, 180, 630, 620], fill=rgba((90, 100, 115)), outline=rgba(OUTLINE), width=8)
    d.ellipse([200, 240, 570, 560], outline=rgba((60, 70, 85)), width=10)
    for i in range(12):
        ang = math.radians(i * 30)
        x0 = 384 + 80 * math.cos(ang)
        y0 = 400 + 80 * math.sin(ang)
        x1 = 384 + 160 * math.cos(ang)
        y1 = 400 + 160 * math.sin(ang)
        d.line([(x0, y0), (x1, y1)], fill=rgba((60, 70, 85)), width=6)
    return finish(im)


def bus_stop():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.line([(220, 200), (220, 620)], fill=rgba((120, 125, 135)), width=20)
    round_rect(d, (200, 600, 360, 650), 10, rgba((70, 75, 85)), rgba(OUTLINE), 3)
    round_rect(d, (160, 140, 520, 280), 20, rgba((50, 120, 210)), rgba(OUTLINE), 5)
    # shelter
    round_rect(d, (280, 280, 650, 520), 16, rgba((200, 220, 235), 160), rgba(OUTLINE), 5)
    d.rectangle([280, 500, 650, 520], fill=rgba((90, 100, 120)))
    return finish(im)


def taxi_stand():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (150, 200, 620, 480), 24, rgba((255, 205, 50)), rgba(OUTLINE), 6)
    round_rect(d, (200, 260, 570, 420), 16, rgba((40, 42, 50)), rgba(OUTLINE), 4)
    # TAXI as bars
    d.rectangle([250, 300, 290, 380], fill=rgba((255, 205, 50)))
    d.rectangle([320, 300, 420, 340], fill=rgba((255, 205, 50)))
    d.rectangle([450, 300, 520, 380], fill=rgba((255, 205, 50)))
    d.line([(384, 480), (384, 620)], fill=rgba((120, 125, 135)), width=22)
    return finish(im)


def warning_triangle():
    im = canvas()
    d = ImageDraw.Draw(im)
    tri = [(384, 120), (660, 600), (108, 600)]
    d.polygon(tri, fill=rgba((255, 210, 50)), outline=rgba(OUTLINE), width=8)
    d.polygon([(384, 200), (580, 540), (188, 540)], fill=rgba((255, 230, 120)))
    d.rectangle([360, 280, 408, 440], fill=rgba(OUTLINE))
    d.ellipse([360, 470, 408, 518], fill=rgba(OUTLINE))
    return finish(im)


def school_zone():
    im = canvas()
    d = ImageDraw.Draw(im)
    d.polygon([(384, 100), (680, 420), (384, 640), (88, 420)], fill=rgba((255, 205, 50)), outline=rgba(OUTLINE), width=8)
    # two stick figures
    for cx in (300, 460):
        d.ellipse([cx - 28, 250, cx + 28, 306], fill=rgba((40, 42, 50)), outline=rgba(OUTLINE), width=3)
        d.line([(cx, 306), (cx, 420)], fill=rgba((40, 42, 50)), width=10)
        d.line([(cx, 340), (cx - 40, 390)], fill=rgba((40, 42, 50)), width=8)
        d.line([(cx, 340), (cx + 40, 390)], fill=rgba((40, 42, 50)), width=8)
        d.line([(cx, 420), (cx - 30, 500)], fill=rgba((40, 42, 50)), width=8)
        d.line([(cx, 420), (cx + 30, 500)], fill=rgba((40, 42, 50)), width=8)
    return finish(im)


def wheelchair_sign():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (140, 120, 640, 640), 36, rgba((50, 110, 210)), rgba(OUTLINE), 7)
    d.ellipse([330, 180, 430, 280], fill=rgba((250, 250, 252)), outline=rgba(OUTLINE), width=4)
    d.line([(380, 280), (380, 420)], fill=rgba((250, 250, 252)), width=18)
    d.line([(380, 340), (500, 340)], fill=rgba((250, 250, 252)), width=14)
    d.ellipse([250, 400, 450, 600], outline=rgba((250, 250, 252)), width=22)
    d.ellipse([430, 430, 560, 560], outline=rgba((250, 250, 252)), width=16)
    return finish(im)


def rest_area():
    im = canvas()
    d = ImageDraw.Draw(im)
    round_rect(d, (160, 140, 620, 580), 28, rgba((50, 140, 90)), rgba(OUTLINE), 7)
    # picnic table
    d.rectangle([240, 300, 540, 340], fill=rgba((240, 245, 250)), outline=rgba(OUTLINE), width=4)
    d.line([(300, 340), (300, 460)], fill=rgba((240, 245, 250)), width=14)
    d.line([(480, 340), (480, 460)], fill=rgba((240, 245, 250)), width=14)
    d.rectangle([220, 460, 560, 500], fill=rgba((240, 245, 250)), outline=rgba(OUTLINE), width=4)
    # tree
    d.ellipse([470, 180, 590, 300], fill=rgba((90, 190, 110)), outline=rgba(OUTLINE), width=4)
    d.rectangle([520, 290, 545, 360], fill=rgba((140, 100, 60)), outline=rgba(OUTLINE), width=3)
    return finish(im)


def snow_plow():
    im = canvas()
    d = ImageDraw.Draw(im)
    color = (70, 130, 200)
    round_rect(d, (180, 300, 620, 480), 24, rgba(color), rgba(OUTLINE), 6)
    round_rect(d, (360, 220, 560, 320), 16, rgba(mix(color, (255, 255, 255), 0.2)), rgba(OUTLINE), 5)
    round_rect(d, (390, 240, 530, 300), 10, rgba((180, 220, 245)), rgba(OUTLINE), 3)
    # plow blade
    d.polygon([(80, 360), (200, 320), (200, 500), (80, 460)], fill=rgba((200, 205, 215)), outline=rgba(OUTLINE), width=5)
    wheel(d, 280, 480, 50)
    wheel(d, 520, 480, 50)
    round_rect(d, (430, 180, 500, 230), 8, rgba((255, 200, 50)), rgba(OUTLINE), 3)
    return finish(im)


# Catalog of 100 items
ITEMS: list[tuple[str, str, object]] = [
    ("car_blue", "파란 승용차", car),
    ("car_red", "빨간 승용차", lambda: car((220, 70, 70))),
    ("car_green", "초록 승용차", lambda: car((70, 170, 110))),
    ("taxi", "택시", taxi2),
    ("bus_green", "시내버스", bus),
    ("bus_blue", "광역버스", lambda: bus((70, 120, 210))),
    ("truck", "트럭", truck),
    ("van", "밴", van),
    ("motorcycle", "오토바이", motorcycle),
    ("bicycle", "자전거", bicycle),
    ("scooter", "킥보드", scooter),
    ("ambulance", "앰뷸런스", ambulance),
    ("police", "경찰차", police_car),
    ("firetruck", "소방차", fire_truck),
    ("train", "기차", train),
    ("subway", "지하철", subway),
    ("airplane", "비행기", airplane),
    ("helicopter", "헬리콥터", helicopter),
    ("ship", "배", ship),
    ("sailboat", "요트", sailboat),
    ("traffic_light", "신호등", traffic_light),
    ("stop", "정지 표지", stop_sign),
    ("yield", "양보 표지", yield_sign),
    ("speed30", "제한속도 30", lambda: speed_sign(0)),
    ("speed50", "제한속도 50", lambda: speed_sign(1)),
    ("speed80", "제한속도 80", lambda: speed_sign(2)),
    ("no_entry", "진입금지", no_entry),
    ("parking", "주차 표지", parking_sign),
    ("pedestrian", "보행자 표지", pedestrian_sign),
    ("bikelane", "자전거도로", bike_lane_sign),
    ("oneway", "일방통행", one_way),
    ("crosswalk", "횡단보도", crosswalk),
    ("cone", "안전삼각콘", road_cone),
    ("barrier", "공사 바리케이드", barrier),
    ("gas", "주유소", gas_pump),
    ("evcharge", "전기차 충전", charging_station),
    ("meter", "주차미터기", parking_meter),
    ("toll", "톨게이트", toll_gate),
    ("bridge", "다리", bridge),
    ("tunnel", "터널", tunnel),
    ("camera", "교통카메라", traffic_camera),
    ("pin", "위치핀", gps_pin),
    ("compass", "나침반", compass),
    ("steering", "핸들", steering_wheel),
    ("keyfob", "스마트키", key_fob),
    ("tire", "타이어", tire),
    ("helmet", "헬멧", helmet),
    ("seatbelt", "안전벨트", seatbelt),
    ("airbag", "에어백", airbag),
    ("map", "경로지도", map_route),
    ("lot", "주차장", parking_lot),
    ("jam", "교통체증", traffic_jam),
    ("roundabout", "로터리", roundabout),
    ("overpass", "고가도로", overpass),
    ("railcross", "건널목", railway_crossing),
    ("lighthouse", "등대", lighthouse),
    ("balloon", "열기구", hot_air_balloon),
    ("skateboard", "스케이트보드", skateboard),
    ("rollerskate", "롤러스케이트", roller_skates),
    ("cablecar", "케이블카", cable_car),
    ("monorail", "모노레일", monorail),
    ("ferry", "페리", ferry),
    ("rocket", "로켓", rocket),
    ("ufo", "UFO", ufo),
    ("delivery", "배달스쿠터", delivery_scooter),
    ("containership", "컨테이너선", container_ship),
    ("tow", "견인차", tow_truck),
    ("forklift", "지게차", forklift),
    ("tractor", "트랙터", tractor),
    ("bulldozer", "불도저", bulldozer),
    ("crane", "크레인", crane),
    ("speedbump", "과속방지턱", speed_bump),
    ("manhole", "맨홀", manhole),
    ("busstop", "버스정류장", bus_stop),
    ("taxistand", "택시승강장", taxi_stand),
    ("car_orange", "주황 승용차", lambda: car((240, 130, 50))),
    ("car_purple", "보라 승용차", lambda: car((140, 90, 200))),
    ("car_pink", "분홍 승용차", lambda: car((240, 120, 160))),
    ("car_gray", "회색 승용차", lambda: car((140, 145, 155))),
    ("truck_blue", "파란 트럭", lambda: truck((70, 120, 210))),
    ("truck_green", "초록 트럭", lambda: truck((70, 160, 100))),
    ("van_red", "빨간 밴", lambda: van((210, 70, 80))),
    ("van_yellow", "노란 밴", lambda: van((240, 190, 50))),
    ("bike_red", "빨간 자전거", lambda: bicycle((210, 70, 80))),
    ("bike_orange", "주황 자전거", lambda: bicycle((240, 130, 50))),
    ("scooter_blue", "파란 킥보드", lambda: scooter((70, 130, 220))),
    ("scooter_green", "초록 킥보드", lambda: scooter((70, 170, 110))),
    ("moto_red", "빨간 오토바이", lambda: motorcycle((200, 60, 70))),
    ("bus_yellow", "노란 버스", lambda: bus((240, 190, 50))),
    ("bus_red", "빨간 버스", lambda: bus((210, 70, 80))),
    ("plane_blue", "파란 비행기", lambda: airplane((180, 210, 240))),
    ("heli_red", "빨간 헬기", lambda: helicopter((210, 70, 80))),
    ("ship_green", "초록 배", lambda: ship((50, 140, 110))),
    ("train_red", "빨간 기차", lambda: train((200, 60, 70))),
    ("train_green", "초록 기차", lambda: train((60, 150, 100))),
    ("warning", "경고 표지", warning_triangle),
    ("school", "스쿨존", school_zone),
    ("wheelchair", "장애인주차", wheelchair_sign),
    ("restarea", "휴게소", rest_area),
    ("snowplow", "제설차", snow_plow),
]


def main() -> int:
    assert len(ITEMS) >= TARGET, f"need {TARGET} items, got {len(ITEMS)}"
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    MANIFEST.parent.mkdir(parents=True, exist_ok=True)
    manifest_items = []
    for i, (pid, title, painter) in enumerate(ITEMS[:TARGET], 1):
        fname = f"hq_traffic_{i:03d}_{pid}.png"
        path = OUT_DIR / fname
        img = painter()
        img.save(path, "PNG", optimize=True)
        with Image.open(path) as im:
            assert im.mode == "RGBA"
            mn, _ = im.getchannel("A").getextrema()
            assert mn == 0, f"{fname} opaque bg"
        manifest_items.append({
            "title": title,
            "category_slug": CATEGORY,
            "image_path": f"/assets/cliparts/{fname}",
            "hashtags": f"#교통 #탈것 #2D일러스트 #{pid}",
            "description": f"교통 2D 일러스트 — {title}",
            "sort_order": 9400 + i,
        })
        print(f"[{i}/{TARGET}] {fname} · {title}")

    MANIFEST.write_text(json.dumps({"items": manifest_items}, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Wrote {len(manifest_items)} → {MANIFEST}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
