#!/usr/bin/env python3
"""Generate 500 diverse transparent-PNG cliparts + seed manifest."""
from __future__ import annotations

import json
import math
import random
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "cliparts"
MANIFEST = ROOT / "storage" / "imports" / "clipart_seed_manifest.json"
SIZE = 512
TARGET = 500

# Distinct color families (no near-white fills that look like background)
PALETTES = [
    ((123, 40, 64, 255), (196, 90, 120, 255), (240, 190, 205, 255)),
    ((28, 70, 130, 255), (70, 130, 200, 255), (160, 200, 240, 255)),
    ((30, 120, 70, 255), (70, 170, 110, 255), (160, 220, 180, 255)),
    ((190, 95, 25, 255), (230, 150, 55, 255), (250, 210, 140, 255)),
    ((100, 45, 150, 255), (150, 95, 210, 255), (210, 180, 245, 255)),
    ((40, 42, 50, 255), (90, 95, 110, 255), (170, 175, 185, 255)),
    ((200, 55, 85, 255), (240, 110, 140, 255), (255, 185, 200, 255)),
    ((15, 130, 140, 255), (40, 180, 185, 255), (150, 230, 230, 255)),
    ((150, 40, 40, 255), (210, 80, 70, 255), (245, 170, 150, 255)),
    ((60, 90, 40, 255), (120, 150, 70, 255), (190, 210, 130, 255)),
]

STYLES = [
    "filled",
    "outline",
    "sticker",
    "geometric",
    "doodle",
    "gradient",
    "silhouette",
    "pastel",
    "stamp",
    "minimal",
]

CATEGORIES = {
    "food": {
        "name": "식품·카페",
        "titles": ["커피콩", "원두", "머그컵", "케이크", "빵", "딸기", "레몬", "밀크", "티백", "아이스크림",
                   "초코", "도넛", "와인", "맥주", "피자", "스시", "사과", "포도", "꿀", "잼"],
        "tags": ["식품", "카페", "맛집", "디저트", "라벨"],
        "motifs": ["bean", "mug", "cake", "fruit", "donut"],
    },
    "beauty": {
        "name": "뷰티·화장품",
        "titles": ["립스틱", "향수", "크림", "브러시", "미러", "네일", "세럼", "로션", "파우더", "아이섀도",
                   "마스크팩", "토너", "클렌저", "오일", "스프레이"],
        "tags": ["뷰티", "화장품", "스킨케어", "향수"],
        "motifs": ["lipstick", "bottle", "mirror", "drop", "spark"],
    },
    "shipping": {
        "name": "배송·물류",
        "titles": ["박스", "택배", "트럭", "핀", "지도핀", "주의", "깨짐주의", "취급주의", "방수", "냉장",
                   "운송", "바코드스캔", "체크리스트", "서류", "패킹"],
        "tags": ["배송", "물류", "택배", "주의"],
        "motifs": ["box", "pin", "truck", "warn", "barcode"],
    },
    "kids": {
        "name": "네임·키즈",
        "titles": ["별표", "웃는얼굴", "연필", "가방", "공", "블록", "리본키즈", "구름", "무지개", "사탕",
                   "학용품", "이름표", "스티커", "크레용", "책"],
        "tags": ["키즈", "네임", "어린이", "학용품"],
        "motifs": ["star", "face", "pencil", "cloud", "candy"],
    },
    "gift": {
        "name": "하트·선물",
        "titles": ["하트", "더블하트", "선물상자", "리본", "감사", "꽃다발", "반지", "풍선", "축하", "편지",
                   "다이아", "스마일하트", "기프트카드", "파티", "케이크초"],
        "tags": ["선물", "하트", "감사", "축하"],
        "motifs": ["heart", "gift", "ribbon", "balloon", "diamond"],
    },
    "business": {
        "name": "비즈니스",
        "titles": ["바코드", "가격표", "체크", "클립보드", "달력", "시계", "서류철", "그래프", "영수증", "QR",
                   "사무실", "태그", "스탬프", "펜", "노트북"],
        "tags": ["비즈니스", "오피스", "바코드", "가격"],
        "motifs": ["barcode", "check", "tag", "chart", "clock"],
    },
    "nature": {
        "name": "자연·식물",
        "titles": ["잎사귀", "꽃", "해바라기", "나무", "새싹", "클로버", "버섯", "산", "물결", "태양",
                   "달", "별똥별", "허브", "올리브", "파인"],
        "tags": ["자연", "식물", "친환경", "꽃"],
        "motifs": ["leaf", "flower", "tree", "sun", "wave"],
    },
    "animal": {
        "name": "동물",
        "titles": ["고양이", "강아지", "토끼", "곰", "새", "물고기", "나비", "벌", "펭귄", "판다",
                   "여우", "오리", "햄스터", "공룡", "유니콘"],
        "tags": ["동물", "귀여움", "캐릭터"],
        "motifs": ["cat", "bird", "fish", "bunny", "paw"],
    },
    "season": {
        "name": "시즌·기념일",
        "titles": ["눈꽃", "크리스마스", "할로윈", "벚꽃", "단풍", "불꽃", "폭죽", "달력기념", "생일모자", "종소리",
                   "발렌타인", "화이트데이", "추석", "설날", "여름"],
        "tags": ["시즌", "기념일", "이벤트"],
        "motifs": ["snow", "tree_xmas", "fire", "blossom", "hat"],
    },
    "shape": {
        "name": "기본 도형",
        "titles": ["원형", "사각형", "삼각형", "별", "다이아몬드", "육각형", "타원", "라운드사각", "포인트", "도트",
                   "라인", "프레임", "배지", "스탬프원", "체크마크"],
        "tags": ["도형", "기본", "아이콘", "프레임"],
        "motifs": ["circle", "square", "triangle", "hex", "frame"],
    },
}


def rgba(c, a=None):
    if len(c) == 4 and a is None:
        return c
    return (c[0], c[1], c[2], 255 if a is None else a)


def star_points(cx, cy, r, points=5, inner=0.45):
    coords = []
    for i in range(points * 2):
        ang = -math.pi / 2 + i * math.pi / points
        rad = r if i % 2 == 0 else r * inner
        coords.append((cx + rad * math.cos(ang), cy + rad * math.sin(ang)))
    return coords


def heart_poly(cx, cy, s):
    pts = []
    for t in range(0, 360, 4):
        a = math.radians(t)
        x = 16 * math.sin(a) ** 3
        y = 13 * math.cos(a) - 5 * math.cos(2 * a) - 2 * math.cos(3 * a) - math.cos(4 * a)
        pts.append((cx + x * s / 16, cy - y * s / 16))
    return pts


def draw_motif(draw: ImageDraw.ImageDraw, motif: str, style: str, c1, c2, c3, rng: random.Random):
    fill = c1
    accent = c2
    soft = c3
    line_w = {"outline": 14, "doodle": 10, "minimal": 8, "stamp": 12}.get(style, 0)

    def stroke_poly(pts, fill_color=None, outline_color=None, width=0):
        if style in ("outline", "doodle", "minimal", "stamp"):
            draw.line(pts + [pts[0]], fill=outline_color or fill, width=width or line_w, joint="curve")
        elif style == "silhouette":
            draw.polygon(pts, fill=fill)
        else:
            draw.polygon(pts, fill=fill_color or fill)

    cx = cy = SIZE // 2

    if motif in ("heart", "gift") or (motif == "heart"):
        pts = heart_poly(cx, cy - 10, 150)
        if style in ("outline", "doodle", "minimal", "stamp"):
            draw.line(pts + [pts[0]], fill=fill, width=line_w, joint="curve")
            if style == "doodle":
                pts2 = heart_poly(cx + 8, cy + 6, 120)
                draw.line(pts2 + [pts2[0]], fill=accent, width=6, joint="curve")
        elif style == "sticker":
            draw.polygon(heart_poly(cx, cy - 10, 165), fill=soft)
            draw.polygon(pts, fill=fill)
            draw.polygon(heart_poly(cx - 20, cy - 40, 40), fill=(255, 255, 255, 140))
        elif style == "pastel":
            draw.polygon(heart_poly(cx, cy - 10, 160), fill=soft)
            draw.polygon(heart_poly(cx, cy - 10, 120), fill=accent)
            draw.polygon(heart_poly(cx, cy - 10, 70), fill=fill)
        else:
            draw.polygon(pts, fill=fill)
            draw.polygon(heart_poly(cx - 18, cy - 36, 36), fill=(255, 255, 255, 110))
        return

    if motif in ("star", "spark", "diamond"):
        pts = star_points(cx, cy, 160 if motif != "diamond" else 140, 5 if motif != "diamond" else 4, 0.42)
        if style in ("outline", "doodle", "minimal", "stamp"):
            draw.line(pts + [pts[0]], fill=fill, width=line_w)
        elif style == "geometric":
            draw.polygon(pts, fill=fill)
            draw.polygon(star_points(cx, cy, 80, 5, 0.42), fill=accent)
        elif style == "sticker":
            draw.polygon(star_points(cx, cy, 175, 5, 0.45), fill=soft)
            draw.polygon(pts, fill=fill)
        else:
            draw.polygon(pts, fill=fill)
            if style != "silhouette":
                draw.polygon(star_points(cx, cy, 60, 5, 0.45), fill=soft)
        return

    if motif in ("circle", "sun", "face", "drop"):
        r = 150
        if style in ("outline", "doodle", "minimal", "stamp"):
            draw.ellipse((cx - r, cy - r, cx + r, cy + r), outline=fill, width=line_w)
            if motif == "sun":
                for i in range(12):
                    a = math.radians(i * 30)
                    draw.line(
                        (cx + math.cos(a) * 165, cy + math.sin(a) * 165, cx + math.cos(a) * 210, cy + math.sin(a) * 210),
                        fill=fill,
                        width=max(6, line_w - 2),
                    )
            if motif == "face":
                draw.ellipse((cx - 50, cy - 40, cx - 20, cy - 10), outline=fill, width=8)
                draw.ellipse((cx + 20, cy - 40, cx + 50, cy - 10), outline=fill, width=8)
                draw.arc((cx - 55, cy - 10, cx + 55, cy + 70), 20, 160, fill=fill, width=8)
        elif style == "gradient" or style == "pastel":
            for i, col in enumerate((soft, accent, fill)):
                rr = r - i * 40
                draw.ellipse((cx - rr, cy - rr, cx + rr, cy + rr), fill=col)
        elif style == "sticker":
            draw.ellipse((cx - r - 18, cy - r - 18, cx + r + 18, cy + r + 18), fill=soft)
            draw.ellipse((cx - r, cy - r, cx + r, cy + r), fill=fill)
        else:
            draw.ellipse((cx - r, cy - r, cx + r, cy + r), fill=fill)
            if motif == "sun":
                for i in range(10):
                    a = math.radians(i * 36)
                    draw.line(
                        (cx + math.cos(a) * 155, cy + math.sin(a) * 155, cx + math.cos(a) * 205, cy + math.sin(a) * 205),
                        fill=accent,
                        width=14,
                    )
            if motif == "face" and style != "silhouette":
                draw.ellipse((cx - 50, cy - 35, cx - 22, cy - 7), fill=accent)
                draw.ellipse((cx + 22, cy - 35, cx + 50, cy - 7), fill=accent)
                draw.arc((cx - 50, cy, cx + 50, cy + 70), 15, 165, fill=accent, width=10)
        return

    if motif in ("leaf", "flower", "blossom", "tree"):
        if motif == "leaf":
            box = (150, 80, 360, 430)
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.ellipse(box, outline=fill, width=line_w)
                draw.line((256, 100, 256, 400), fill=fill, width=line_w - 2)
            else:
                draw.ellipse(box, fill=fill)
                draw.line((256, 110, 256, 390), fill=accent, width=10)
                for y in range(160, 360, 45):
                    draw.line((256, y, 256 + (50 if y % 90 else -50), y + 25), fill=accent, width=7)
        elif motif == "tree" or motif == "tree_xmas":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.polygon([(256, 70), (120, 250), (392, 250)], outline=fill)
                draw.polygon([(256, 160), (140, 340), (372, 340)], outline=fill)
                draw.rectangle((230, 340, 282, 430), outline=fill, width=line_w)
            else:
                draw.polygon([(256, 70), (120, 250), (392, 250)], fill=fill)
                draw.polygon([(256, 150), (145, 330), (367, 330)], fill=accent)
                draw.rectangle((232, 330, 280, 430), fill=(110, 70, 40, 255))
        else:
            for i in range(6):
                a = math.radians(i * 60)
                px = cx + int(95 * math.cos(a))
                py = cy + int(95 * math.sin(a))
                if style in ("outline", "doodle", "minimal", "stamp"):
                    draw.ellipse((px - 55, py - 55, px + 55, py + 55), outline=fill, width=line_w - 2)
                else:
                    draw.ellipse((px - 55, py - 55, px + 55, py + 55), fill=soft if i % 2 else accent)
            draw.ellipse((cx - 50, cy - 50, cx + 50, cy + 50), fill=fill if style not in ("outline", "doodle", "minimal", "stamp") else None, outline=fill, width=line_w or 0)
        return

    if motif in ("box", "gift", "truck"):
        if style in ("outline", "doodle", "minimal", "stamp"):
            draw.rounded_rectangle((130, 160, 382, 390), radius=18, outline=fill, width=line_w)
            draw.line((130, 220, 382, 220), fill=fill, width=line_w - 2)
            draw.line((256, 220, 256, 390), fill=fill, width=line_w - 2)
            if motif == "gift":
                draw.ellipse((170, 90, 250, 170), outline=fill, width=line_w - 2)
                draw.ellipse((262, 90, 342, 170), outline=fill, width=line_w - 2)
        else:
            draw.rounded_rectangle((130, 160, 382, 390), radius=18, fill=fill)
            draw.polygon([(130, 180), (256, 110), (382, 180), (382, 220), (256, 160), (130, 220)], fill=accent)
            draw.rectangle((244, 160, 268, 390), fill=soft)
            if motif == "gift":
                draw.ellipse((175, 95, 245, 165), fill=accent)
                draw.ellipse((267, 95, 337, 165), fill=c1)
        return

    if motif in ("mug", "bottle", "lipstick"):
        if motif == "lipstick":
            body = (210, 200, 302, 420)
            tip = [(256, 90), (210, 200), (302, 200)]
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.polygon(tip, outline=fill)
                draw.rounded_rectangle(body, radius=12, outline=fill, width=line_w)
            else:
                draw.polygon(tip, fill=fill)
                draw.rounded_rectangle(body, radius=12, fill=accent)
        elif motif == "bottle":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.rounded_rectangle((210, 80, 302, 150), radius=8, outline=fill, width=line_w)
                draw.rounded_rectangle((180, 150, 332, 420), radius=30, outline=fill, width=line_w)
            else:
                draw.rounded_rectangle((210, 80, 302, 150), radius=8, fill=accent)
                draw.rounded_rectangle((180, 150, 332, 420), radius=30, fill=fill)
                draw.ellipse((210, 220, 300, 300), fill=soft)
        else:
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.rounded_rectangle((150, 150, 330, 390), radius=26, outline=fill, width=line_w)
                draw.arc((310, 190, 410, 320), 270, 90, fill=fill, width=line_w)
            else:
                draw.rounded_rectangle((150, 150, 330, 390), radius=26, fill=fill)
                draw.arc((315, 195, 405, 315), 270, 90, fill=accent, width=22)
                draw.ellipse((170, 140, 310, 190), fill=accent)
        return

    if motif in ("barcode", "tag", "chart", "check", "warn", "pin"):
        if motif == "barcode":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.rounded_rectangle((100, 150, 412, 360), radius=16, outline=fill, width=line_w)
            x = 130
            local = random.Random(rng.randint(1, 99999))
            while x < 380:
                w = local.choice([6, 10, 14, 8, 18])
                draw.rectangle((x, 175, x + w, 320), fill=fill if style not in ("outline", "minimal") else fill)
                x += w + local.choice([4, 6, 8])
        elif motif == "check":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.ellipse((110, 110, 402, 402), outline=fill, width=line_w)
                draw.line((175, 265, 235, 330), fill=fill, width=line_w + 4)
                draw.line((235, 330, 345, 185), fill=fill, width=line_w + 4)
            else:
                draw.ellipse((110, 110, 402, 402), fill=fill)
                draw.line((180, 265, 240, 335), fill=(255, 255, 255, 255), width=28)
                draw.line((240, 335, 350, 180), fill=(255, 255, 255, 255), width=28)
        elif motif == "pin":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.ellipse((186, 90, 326, 230), outline=fill, width=line_w)
                draw.polygon([(256, 430), (190, 220), (322, 220)], outline=fill)
            else:
                draw.ellipse((186, 90, 326, 230), fill=fill)
                draw.polygon([(256, 430), (190, 220), (322, 220)], fill=fill)
                draw.ellipse((226, 130, 286, 190), fill=soft)
        elif motif == "warn":
            pts = [(256, 80), (100, 400), (412, 400)]
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.polygon(pts, outline=fill)
                draw.line((256, 180, 256, 300), fill=fill, width=line_w + 2)
                draw.ellipse((240, 325, 272, 357), outline=fill, width=line_w)
            else:
                draw.polygon(pts, fill=fill)
                draw.line((256, 180, 256, 300), fill=(40, 40, 40, 255), width=22)
                draw.ellipse((238, 325, 274, 361), fill=(40, 40, 40, 255))
        elif motif == "chart":
            bars = [(140, 280, 190, 390), (220, 200, 270, 390), (300, 140, 350, 390)]
            for i, b in enumerate(bars):
                col = (fill, accent, soft)[i % 3]
                if style in ("outline", "doodle", "minimal", "stamp"):
                    draw.rectangle(b, outline=fill, width=line_w - 2)
                else:
                    draw.rectangle(b, fill=col)
        else:  # tag
            pts = [(120, 180), (340, 120), (390, 220), (170, 320)]
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.polygon(pts, outline=fill)
                draw.ellipse((150, 200, 190, 240), outline=fill, width=8)
            else:
                draw.polygon(pts, fill=fill)
                draw.ellipse((150, 200, 190, 240), fill=soft)
        return

    if motif in ("cat", "bunny", "bird", "fish", "paw"):
        if motif == "paw":
            pads = [(256, 280, 70), (170, 180, 40), (230, 140, 40), (300, 140, 40), (350, 190, 40)]
            for x, y, r in pads:
                box = (x - r, y - r, x + r, y + r)
                if style in ("outline", "doodle", "minimal", "stamp"):
                    draw.ellipse(box, outline=fill, width=line_w - 2)
                else:
                    draw.ellipse(box, fill=fill)
        elif motif == "fish":
            body = (140, 190, 360, 330)
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.ellipse(body, outline=fill, width=line_w)
                draw.polygon([(360, 260), (450, 180), (450, 340)], outline=fill)
            else:
                draw.ellipse(body, fill=fill)
                draw.polygon([(350, 260), (450, 185), (450, 335)], fill=accent)
                draw.ellipse((190, 230, 230, 270), fill=(30, 30, 40, 255))
        elif motif == "bird":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.ellipse((160, 180, 340, 340), outline=fill, width=line_w)
                draw.ellipse((280, 140, 360, 220), outline=fill, width=line_w)
                draw.polygon([(360, 180), (430, 170), (360, 210)], outline=fill)
            else:
                draw.ellipse((160, 180, 340, 340), fill=fill)
                draw.ellipse((280, 140, 360, 220), fill=accent)
                draw.polygon([(355, 175), (430, 165), (355, 215)], fill=soft)
                draw.ellipse((315, 165, 335, 185), fill=(30, 30, 40, 255))
        else:
            # cat / bunny face
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.ellipse((140, 160, 372, 400), outline=fill, width=line_w)
                draw.polygon([(160, 180), (190, 80), (240, 170)], outline=fill)
                draw.polygon([(272, 170), (322, 80), (352, 180)], outline=fill)
            else:
                draw.ellipse((140, 160, 372, 400), fill=fill)
                ear = soft if motif == "bunny" else accent
                draw.polygon([(160, 180), (190, 70), (245, 175)], fill=ear)
                draw.polygon([(267, 175), (322, 70), (352, 180)], fill=ear)
                draw.ellipse((195, 250, 235, 290), fill=(40, 40, 50, 255))
                draw.ellipse((277, 250, 317, 290), fill=(40, 40, 50, 255))
        return

    if motif in ("cloud", "snow", "wave", "fire", "balloon", "candy", "pencil", "frame", "hex", "square", "triangle", "donut", "fruit", "cake", "bean", "ribbon", "hat"):
        if motif == "cloud":
            parts = [(150, 220, 270, 340), (210, 170, 360, 310), (280, 210, 400, 340)]
            for b in parts:
                if style in ("outline", "doodle", "minimal", "stamp"):
                    draw.ellipse(b, outline=fill, width=line_w - 2)
                else:
                    draw.ellipse(b, fill=fill)
        elif motif == "snow":
            for i in range(6):
                a = math.radians(i * 60)
                draw.line((cx + math.cos(a) * 20, cy + math.sin(a) * 20, cx + math.cos(a) * 170, cy + math.sin(a) * 170), fill=fill, width=line_w or 12)
            draw.ellipse((cx - 28, cy - 28, cx + 28, cy + 28), fill=fill if style not in ("outline", "minimal") else None, outline=fill, width=line_w or 0)
        elif motif == "wave":
            for yi, y in enumerate((200, 260, 320)):
                pts = []
                for x in range(80, 440, 8):
                    pts.append((x, y + math.sin((x + yi * 40) / 28) * 22))
                draw.line(pts, fill=(fill, accent, soft)[yi], width=line_w or 14, joint="curve")
        elif motif == "balloon":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.ellipse((176, 80, 336, 280), outline=fill, width=line_w)
                draw.line((256, 280, 256, 420), fill=fill, width=line_w - 2)
            else:
                draw.ellipse((176, 80, 336, 280), fill=fill)
                draw.polygon([(240, 275), (272, 275), (256, 310)], fill=accent)
                draw.line((256, 310, 256, 420), fill=accent, width=8)
        elif motif in ("square", "frame"):
            box = (130, 130, 382, 382)
            if style in ("outline", "doodle", "minimal", "stamp") or motif == "frame":
                draw.rounded_rectangle(box, radius=28, outline=fill, width=line_w or 16)
                if motif == "frame":
                    draw.rounded_rectangle((170, 170, 342, 342), radius=18, outline=accent, width=10)
            else:
                draw.rounded_rectangle(box, radius=28, fill=fill)
        elif motif == "triangle":
            pts = [(256, 90), (100, 400), (412, 400)]
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.polygon(pts, outline=fill)
            else:
                draw.polygon(pts, fill=fill)
        elif motif == "hex":
            pts = []
            for i in range(6):
                a = math.radians(i * 60 - 30)
                pts.append((cx + 170 * math.cos(a), cy + 170 * math.sin(a)))
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.polygon(pts, outline=fill)
            else:
                draw.polygon(pts, fill=fill)
                draw.polygon([(cx + 90 * math.cos(math.radians(i * 60 - 30)), cy + 90 * math.sin(math.radians(i * 60 - 30))) for i in range(6)], fill=accent)
        elif motif == "donut":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.ellipse((120, 120, 392, 392), outline=fill, width=line_w)
                draw.ellipse((210, 210, 302, 302), outline=fill, width=line_w - 2)
            else:
                draw.ellipse((120, 120, 392, 392), fill=fill)
                draw.ellipse((210, 210, 302, 302), fill=(0, 0, 0, 0))
                # punch hole using destination-out later — draw soft center for now then clear
        elif motif == "bean":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.ellipse((170, 120, 300, 390), outline=fill, width=line_w)
                draw.arc((200, 160, 280, 350), 200, 340, fill=fill, width=line_w - 2)
            else:
                draw.ellipse((170, 120, 300, 390), fill=fill)
                draw.arc((200, 160, 280, 350), 200, 340, fill=soft, width=12)
        elif motif == "ribbon":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.ellipse((150, 130, 250, 230), outline=fill, width=line_w)
                draw.ellipse((262, 130, 362, 230), outline=fill, width=line_w)
                draw.polygon([(200, 210), (312, 210), (256, 290)], outline=fill)
            else:
                draw.ellipse((150, 130, 250, 230), fill=fill)
                draw.ellipse((262, 130, 362, 230), fill=accent)
                draw.polygon([(200, 210), (312, 210), (256, 290)], fill=soft)
                draw.polygon([(210, 290), (256, 250), (230, 410)], fill=fill)
                draw.polygon([(302, 290), (256, 250), (282, 410)], fill=accent)
        elif motif == "hat":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.polygon([(256, 90), (160, 250), (352, 250)], outline=fill)
                draw.ellipse((120, 240, 392, 320), outline=fill, width=line_w)
            else:
                draw.polygon([(256, 90), (160, 250), (352, 250)], fill=fill)
                draw.ellipse((120, 240, 392, 320), fill=accent)
        elif motif == "pencil":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.polygon([(330, 90), (390, 150), (200, 370), (140, 310)], outline=fill)
                draw.polygon([(140, 310), (200, 370), (120, 400)], outline=fill)
            else:
                draw.polygon([(330, 90), (390, 150), (200, 370), (140, 310)], fill=fill)
                draw.polygon([(140, 310), (200, 370), (120, 400)], fill=accent)
        elif motif == "candy":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.ellipse((180, 180, 332, 332), outline=fill, width=line_w)
                draw.polygon([(100, 200), (180, 240), (180, 280), (100, 320)], outline=fill)
                draw.polygon([(412, 200), (332, 240), (332, 280), (412, 320)], outline=fill)
            else:
                draw.ellipse((180, 180, 332, 332), fill=fill)
                draw.polygon([(100, 200), (180, 240), (180, 280), (100, 320)], fill=accent)
                draw.polygon([(412, 200), (332, 240), (332, 280), (412, 320)], fill=accent)
        elif motif == "cake":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.rounded_rectangle((140, 220, 372, 390), radius=20, outline=fill, width=line_w)
                draw.ellipse((140, 190, 372, 260), outline=fill, width=line_w)
                draw.line((256, 120, 256, 200), fill=fill, width=line_w)
            else:
                draw.rounded_rectangle((140, 220, 372, 390), radius=20, fill=fill)
                draw.ellipse((140, 190, 372, 260), fill=accent)
                draw.line((256, 120, 256, 205), fill=soft, width=10)
                draw.ellipse((240, 100, 272, 132), fill=(250, 180, 60, 255))
        elif motif == "fruit":
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.ellipse((150, 150, 370, 390), outline=fill, width=line_w)
                draw.arc((220, 100, 300, 180), 200, 340, fill=fill, width=line_w)
            else:
                draw.ellipse((150, 150, 370, 390), fill=fill)
                draw.arc((220, 105, 300, 175), 200, 340, fill=(40, 120, 60, 255), width=14)
                draw.ellipse((210, 200, 250, 240), fill=(255, 255, 255, 100))
        elif motif == "fire":
            pts = [(256, 80), (340, 220), (300, 220), (360, 340), (256, 430), (152, 340), (212, 220), (172, 220)]
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.polygon(pts, outline=fill)
            else:
                draw.polygon(pts, fill=fill)
                draw.polygon([(256, 180), (300, 280), (256, 360), (212, 280)], fill=accent)
        else:
            # default circle badge
            if style in ("outline", "doodle", "minimal", "stamp"):
                draw.ellipse((120, 120, 392, 392), outline=fill, width=line_w)
            else:
                draw.ellipse((120, 120, 392, 392), fill=fill)
        return

    # fallback
    draw.ellipse((140, 140, 372, 372), fill=fill if style not in ("outline", "doodle", "minimal", "stamp") else None, outline=fill, width=line_w or 0)


def punch_center_hole(img: Image.Image, box):
    """Make elliptical area fully transparent (for donut)."""
    mask = Image.new("L", img.size, 0)
    md = ImageDraw.Draw(mask)
    md.ellipse(box, fill=255)
    r, g, b, a = img.split()
    a = Image.composite(Image.new("L", img.size, 0), a, mask)
    return Image.merge("RGBA", (r, g, b, a))


def make_image(seed: int, motif: str, style: str) -> Image.Image:
    rng = random.Random(seed)
    img = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img, "RGBA")
    c1, c2, c3 = rng.choice(PALETTES)

    # style-specific underlay accents (still transparent outside)
    if style == "sticker":
        # soft translucent plate behind motif only
        plate = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
        pd = ImageDraw.Draw(plate, "RGBA")
        pd.ellipse((70, 70, 442, 442), fill=(255, 255, 255, 55))
        img = Image.alpha_composite(img, plate)
        draw = ImageDraw.Draw(img, "RGBA")
    elif style == "stamp":
        # faint outer ring
        draw.ellipse((55, 55, 457, 457), outline=rgba(c1, 90), width=6)
        draw.ellipse((75, 75, 437, 437), outline=rgba(c2, 70), width=3)
    elif style == "geometric":
        for i in range(3):
            a = rng.uniform(0, math.pi)
            x = 256 + int(math.cos(a) * (80 + i * 30))
            y = 256 + int(math.sin(a) * (80 + i * 30))
            draw.rectangle((x - 20, y - 20, x + 20, y + 20), fill=rgba(c3, 70))

    draw_motif(draw, motif, style, c1, c2, c3, rng)

    if motif == "donut" and style not in ("outline", "doodle", "minimal", "stamp"):
        img = punch_center_hole(img, (210, 210, 302, 302))

    if style == "doodle":
        # slight wobble dots
        d2 = ImageDraw.Draw(img, "RGBA")
        for _ in range(14):
            x, y = rng.randint(80, 430), rng.randint(80, 430)
            r = rng.randint(2, 5)
            d2.ellipse((x - r, y - r, x + r, y + r), fill=rgba(c2, 160))

    if style == "gradient":
        # soft glow blotches with alpha
        glow = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
        gd = ImageDraw.Draw(glow, "RGBA")
        gd.ellipse((90, 90, 280, 280), fill=rgba(c3, 50))
        gd.ellipse((240, 220, 430, 410), fill=rgba(c2, 40))
        glow = glow.filter(ImageFilter.GaussianBlur(18))
        img = Image.alpha_composite(glow, img)

    # ensure fully transparent corners (no accidental near-white bg)
    return img


def assert_transparent(img: Image.Image) -> bool:
    """True if corners are transparent and image has alpha variation."""
    if img.mode != "RGBA":
        return False
    corners = [(0, 0), (SIZE - 1, 0), (0, SIZE - 1), (SIZE - 1, SIZE - 1), (10, 10), (SIZE - 11, SIZE - 11)]
    for x, y in corners:
        if img.getpixel((x, y))[3] > 8:
            return False
    # must have some opaque content
    alpha = img.split()[-1]
    extrema = alpha.getextrema()
    return extrema[1] > 200 and extrema[0] < 10


def main():
    if OUT_DIR.exists():
        for old in OUT_DIR.glob("seed_*.png"):
            old.unlink()
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    MANIFEST.parent.mkdir(parents=True, exist_ok=True)

    cat_keys = list(CATEGORIES.keys())
    items = []
    per_cat = TARGET // len(cat_keys)
    remainder = TARGET % len(cat_keys)
    n = 0
    regenerated = 0

    for ci, key in enumerate(cat_keys):
        meta = CATEGORIES[key]
        count = per_cat + (1 if ci < remainder else 0)
        titles = meta["titles"]
        motifs = meta["motifs"]
        for i in range(count):
            n += 1
            title_base = titles[i % len(titles)]
            variant = (i // len(titles)) + 1
            title = title_base if variant == 1 else f"{title_base} {variant}"
            style = STYLES[(n + ci * 3) % len(STYLES)]
            motif = motifs[i % len(motifs)]
            # rotate style extra for variety within same motif
            if i % 3 == 0:
                style = STYLES[(n * 7) % len(STYLES)]

            fname = f"seed_{key}_{n:04d}.png"
            path = OUT_DIR / fname
            seed = 2000 + n * 97 + ci * 131

            img = make_image(seed, motif, style)
            tries = 0
            while (not assert_transparent(img) or img.getbbox() is None) and tries < 5:
                tries += 1
                regenerated += 1
                img = make_image(seed + tries * 17, motif, STYLES[(n + tries) % len(STYLES)])

            # final safety: force corner clear
            px = img.load()
            for x, y in [(0, 0), (1, 0), (0, 1), (SIZE - 1, 0), (0, SIZE - 1), (SIZE - 1, SIZE - 1)]:
                r, g, b, a = px[x, y]
                if a > 0 and r > 245 and g > 245 and b > 245:
                    px[x, y] = (0, 0, 0, 0)

            img.save(path, "PNG", optimize=True)

            tags = list(dict.fromkeys(meta["tags"] + [title_base, key, style, "클립아트", "라벨", "투명"]))
            hashtags = " ".join(f"#{t}" for t in tags)
            items.append({
                "title": f"{title} · {style}",
                "category_slug": key,
                "image_path": f"/assets/cliparts/{fname}",
                "hashtags": hashtags,
                "description": f"{meta['name']}용 {title_base} 클립아트 ({style})",
                "sort_order": n,
            })

    manifest = {"count": len(items), "items": items, "regenerated_retries": regenerated}
    MANIFEST.write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Generated {len(items)} transparent PNGs -> {OUT_DIR}")
    print(f"Styles: {', '.join(STYLES)}")
    print(f"Manifest -> {MANIFEST}")


if __name__ == "__main__":
    main()
