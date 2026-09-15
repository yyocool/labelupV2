#!/usr/bin/env python3
"""Generate 100 high-quality business/office phrase cliparts (transparent PNG).

Outputs:
  public/assets/cliparts/hq_workphrase_{NN}_{id}.png
  storage/imports/clipart_workphrase_manifest.json
"""
from __future__ import annotations

import json
import math
import re
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "cliparts"
MANIFEST = ROOT / "storage" / "imports" / "clipart_workphrase_manifest.json"
SIZE = 768
TARGET = 100
CATEGORY = "work-phrase"

FONT_BOLD = Path(r"C:\Windows\Fonts\malgunbd.ttf")
FONT_REG = Path(r"C:\Windows\Fonts\malgun.ttf")
FONT_FALLBACK = ROOT / "public" / "editor" / "fonts" / "Pretendard-Bold.otf"

# (fill, accent, text, name)
PALETTES = [
    ((166, 27, 55), (124, 16, 39), (255, 250, 251), "burgundy"),
    ((28, 70, 130), (18, 48, 95), (240, 248, 255), "navy"),
    ((35, 110, 80), (22, 75, 55), (240, 252, 245), "forest"),
    ((50, 52, 60), (30, 32, 38), (248, 248, 250), "charcoal"),
    ((180, 95, 30), (130, 65, 15), (255, 248, 235), "amber"),
    ((15, 125, 135), (10, 90, 98), (240, 252, 252), "teal"),
    ((95, 45, 145), (65, 28, 105), (248, 242, 255), "violet"),
    ((145, 40, 45), (100, 25, 30), (255, 244, 242), "crimson"),
]

PHRASES: list[tuple[str, str]] = [
    ("확인바랍니다", "confirm"),
    ("긴급", "urgent"),
    ("중요", "important"),
    ("필수", "required"),
    ("참고", "reference"),
    ("회람", "circulate"),
    ("결재", "approve"),
    ("검토요청", "review"),
    ("전달", "forward"),
    ("공지", "notice"),
    ("완료", "done"),
    ("대기", "pending"),
    ("승인", "ok"),
    ("반려", "reject"),
    ("마감", "deadline"),
    ("우선", "priority"),
    ("내부용", "internal"),
    ("대외비", "confidential"),
    ("초안", "draft"),
    ("최종본", "final"),
    ("첨부", "attach"),
    ("회신요망", "reply"),
    ("즉시처리", "asap"),
    ("보류", "hold"),
    ("취소", "cancel"),
    ("변경", "change"),
    ("신규", "new"),
    ("수정", "revise"),
    ("요청", "request"),
    ("안내", "info"),
    ("주의", "caution"),
    ("필수확인", "mustcheck"),
    ("금일마감", "today"),
    ("내일까지", "tomorrow"),
    ("금주마감", "thisweek"),
    ("월간보고", "monthly"),
    ("주간보고", "weekly"),
    ("회의자료", "meeting"),
    ("회의록", "minutes"),
    ("업무연락", "memo"),
    ("지시사항", "directive"),
    ("협조요청", "cooperate"),
    ("검토완료", "reviewed"),
    ("결재완료", "approved"),
    ("수신", "inbox"),
    ("발신", "outbox"),
    ("사본", "copy"),
    ("원본", "original"),
    ("보관", "archive"),
    ("폐기", "dispose"),
    ("보안", "security"),
    ("비밀번호", "password"),
    ("로그인", "login"),
    ("로그아웃", "logout"),
    ("열람전용", "readonly"),
    ("편집가능", "editable"),
    ("공유", "share"),
    ("비공개", "private"),
    ("공개", "public"),
    ("임시저장", "tempsave"),
    ("제출", "submit"),
    ("접수", "receive"),
    ("처리중", "processing"),
    ("완료보고", "donereport"),
    ("이슈", "issue"),
    ("리스크", "risk"),
    ("체크", "check"),
    ("OK", "okeng"),
    ("NG", "ng"),
    ("PASS", "pass"),
    ("FAIL", "fail"),
    ("TODO", "todo"),
    ("DONE", "doneeng"),
    ("ASAP", "asapeng"),
    ("FYI", "fyi"),
    ("NOTE", "note"),
    ("CONFIDENTIAL", "confeng"),
    ("INTERNAL", "inteng"),
    ("DRAFT", "drafteng"),
    ("FINAL", "finaleng"),
    ("URGENT", "urgenteng"),
    ("IMPORTANT", "importeng"),
    ("REQUIRED", "reqeng"),
    ("APPROVED", "approveng"),
    ("REJECTED", "rejecteng"),
    ("PENDING", "pendeng"),
    ("CLOSED", "closed"),
    ("OPEN", "open"),
    ("UPDATE", "updateeng"),
    ("NEW", "neweng"),
    ("COPY", "copyeng"),
    ("SAMPLE", "sample"),
    ("TEMPLATE", "template"),
    ("VERSION", "version"),
    ("PAGE", "page"),
    ("FILE", "file"),
    ("REPORT", "report"),
    ("PLAN", "plan"),
    ("GOAL", "goal"),
    ("FOCUS", "focus"),
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
    size = max_size
    while size >= 16:
        font = load_font(size, bold)
        tw, th = text_size(draw, text, font)
        if tw <= cw * 0.9 and th <= ch * 0.78:
            draw.text((x0 + (cw - tw) / 2, y0 + (ch - th) / 2 - th * 0.06), text, font=font, fill=fill)
            return
        size -= 3
    font = load_font(16, bold)
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


# --- painters ---

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


def paint_underline(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    fit_text(d, (60, 250, 708, 450), label, rgba(fill), 100)
    d.rounded_rectangle((160, 470, 608, 500), radius=12, fill=rgba(accent))
    compose(img, layer)


def paint_speech(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    round_rect(d, (90, 160, 678, 500), 42, fill=rgba(fill), outline=rgba(accent), width=8)
    d.polygon([(300, 490), (370, 490), (280, 620)], fill=rgba(fill))
    d.line([(300, 490), (280, 620), (370, 490)], fill=rgba(accent), width=7)
    fit_text(d, (130, 220, 638, 450), label, rgba(textc), 78)
    compose(img, layer)


def paint_stamp(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.ellipse([110, 110, 658, 658], outline=rgba(fill), width=18)
    d.ellipse([145, 145, 623, 623], outline=rgba(fill, 180), width=6)
    # rotate-ish look via angled rectangle
    round_rect(d, (170, 310, 598, 460), 12, outline=rgba(fill), width=8)
    fit_text(d, (190, 325, 578, 445), label, rgba(fill), 70)
    compose(img, layer)


def paint_ribbon(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    pts = [(60, 280), (708, 280), (668, 384), (708, 488), (60, 488), (100, 384)]
    d.polygon(pts, fill=rgba(fill))
    d.line(pts + [pts[0]], fill=rgba(accent), width=6)
    fit_text(d, (130, 310, 640, 460), label, rgba(textc), 80)
    compose(img, layer)


def paint_tag(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    pts = [(120, 220), (620, 220), (660, 384), (620, 548), (120, 548), (80, 384)]
    d.polygon(pts, fill=rgba(fill))
    d.line(pts + [pts[0]], fill=rgba(accent), width=7)
    d.ellipse([340, 250, 420, 330], fill=rgba(textc), outline=rgba(accent), width=4)
    d.ellipse([360, 270, 400, 310], fill=rgba(fill))
    fit_text(d, (150, 340, 610, 520), label, rgba(textc), 72)
    compose(img, layer)


def paint_ticket(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    round_rect(d, (70, 250, 698, 520), 28, fill=rgba(fill), outline=rgba(accent), width=8)
    for y in range(280, 500, 28):
        d.ellipse([58, y, 82, y + 24], fill=(0, 0, 0, 0))
        d.ellipse([686, y, 710, y + 24], fill=(0, 0, 0, 0))
    # notch cut by transparent circles already; draw dashed divider
    for x in range(200, 560, 24):
        d.rectangle([x, 380, x + 12, 388], fill=rgba(textc, 140))
    fit_text(d, (120, 270, 650, 370), label, rgba(textc), 70)
    compose(img, layer)


def paint_outline(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    # hollow rounded frame, text in brand color
    round_rect(d, (90, 260, 678, 510), 40, outline=rgba(fill), width=14)
    fit_text(d, (130, 300, 638, 470), label, rgba(fill), 84)
    compose(img, layer)


def paint_bar_left(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    round_rect(d, (90, 260, 688, 510), 28, fill=rgba(textc), outline=rgba(accent), width=4)
    d.rectangle([90, 260, 150, 510], fill=rgba(fill))
    # round left corners visually
    fit_text(d, (170, 300, 660, 470), label, rgba(fill), 78)
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
    fit_text(d, (200, 310, 568, 460), label, rgba(textc), 64)
    compose(img, layer)


def paint_shield(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    pts = [(384, 90), (640, 180), (620, 420), (384, 670), (148, 420), (128, 180)]
    d.polygon(pts, fill=rgba(fill))
    d.line(pts + [pts[0]], fill=rgba(accent), width=8)
    fit_text(d, (190, 280, 578, 470), label, rgba(textc), 62)
    compose(img, layer)


def paint_double_line(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.rectangle([100, 240, 668, 258], fill=rgba(fill))
    d.rectangle([100, 510, 668, 528], fill=rgba(fill))
    fit_text(d, (110, 290, 658, 470), label, rgba(fill), 90)
    compose(img, layer)


def paint_pill_soft(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    # soft pastel body using lightened fill
    soft = tuple(min(255, int(c + (255 - c) * 0.82)) for c in fill)
    round_rect(d, (70, 280, 698, 490), 100, fill=rgba(soft), outline=rgba(fill), width=7)
    fit_text(d, (110, 310, 658, 460), label, rgba(fill), 82)
    compose(img, layer)


def paint_corner_fold(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    round_rect(d, (100, 220, 668, 560), 24, fill=rgba(fill), outline=rgba(accent), width=6)
    d.polygon([(560, 220), (668, 220), (668, 328)], fill=rgba(accent))
    fit_text(d, (140, 300, 620, 500), label, rgba(textc), 76)
    compose(img, layer)


def paint_badge_round(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.ellipse([120, 120, 648, 648], fill=rgba(fill), outline=rgba(accent), width=12)
    d.ellipse([155, 155, 613, 613], outline=rgba(textc, 140), width=4)
    fit_text(d, (180, 300, 588, 470), label, rgba(textc), 68)
    compose(img, layer)


def paint_arrow_banner(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    pts = [(60, 260), (560, 260), (700, 384), (560, 508), (60, 508)]
    d.polygon(pts, fill=rgba(fill))
    d.line(pts + [pts[0]], fill=rgba(accent), width=7)
    fit_text(d, (100, 300, 540, 470), label, rgba(textc), 74)
    compose(img, layer)


def paint_notepad(img, fill, accent, textc, label: str) -> None:
    layer = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    paper = (255, 252, 245)
    round_rect(d, (140, 120, 628, 660), 18, fill=rgba(paper), outline=rgba(accent), width=6)
    d.rectangle([140, 120, 628, 200], fill=rgba(fill))
    for y in (280, 360, 440, 520, 600):
        d.rectangle([190, y, 580, y + 4], fill=rgba(accent, 90))
    fit_text(d, (170, 130, 600, 195), label, rgba(textc), 48)
    compose(img, layer)


STYLES: list[tuple[str, str, object]] = [
    ("capsule", "캡슐 배너", paint_capsule),
    ("banner", "라운드 배너", paint_rounded_banner),
    ("underline", "언더라인 문구", paint_underline),
    ("speech", "말풍선", paint_speech),
    ("stamp", "스탬프", paint_stamp),
    ("ribbon", "리본 문구", paint_ribbon),
    ("tag", "태그", paint_tag),
    ("ticket", "티켓", paint_ticket),
    ("outline", "아웃라인", paint_outline),
    ("barleft", "사이드바", paint_bar_left),
    ("hex", "헥사", paint_hex),
    ("shield", "실드", paint_shield),
    ("double", "더블라인", paint_double_line),
    ("softpill", "소프트필", paint_pill_soft),
    ("fold", "폴드카드", paint_corner_fold),
    ("round", "원형뱃지", paint_badge_round),
    ("arrow", "화살배너", paint_arrow_banner),
    ("notepad", "노트헤더", paint_notepad),
]


def build_catalog() -> list[dict]:
    items: list[dict] = []
    n = 0
    # cycle styles × phrases until 100
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
            "tags": f"#업무용문구 #오피스 #{style_id} #{pname} #{phrase}",
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
        fname = f"hq_workphrase_{n:03d}_{slugify(item['id'])[:40]}.png"
        path = OUT_DIR / fname
        render_item(item).save(path, "PNG", optimize=True)
        # verify transparency
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
            "description": f"업무용 문구 클립아트 — {item['label']} ({item['style_name']})",
            "sort_order": 9000 + n,
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
