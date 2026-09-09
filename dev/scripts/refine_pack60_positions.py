#!/usr/bin/env python3
"""Sync pack60 manifest text roles + layouts to match reference sheet positions."""
from __future__ import annotations

import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
MANIFEST = ROOT / "storage" / "imports" / "template_pack60_manifest.json"

# slug -> (layout, texts[(text, role, color), ...])
# layout: product | stack | center | icon | badge | special_*
REFINE = {
    # Row1 — 제품형: 상단 제목 / 중앙 이미지 / 하단 보조
    "sticker-handmade": ("center", [
        ("HAND MADE", "title", "#5C3A1E"),
        ("with love", "sub", "#A05050"),
    ]),
    "sticker-thankyou-smile": ("stack", [
        ("THANK YOU", "title", "#2E2A27"),
        ("항상 감사합니다", "sub", "#5B5560"),
    ]),
    "sticker-olive-oil": ("product", [
        ("Natural Olive Oil", "title", "#3F5A28"),
        ("EXTRA VIRGIN", "mid", "#5B7A3A"),
        ("100% ORGANIC", "sub", "#6B8F4E"),
    ]),
    "sticker-coffee": ("product", [
        ("COFFEE", "title", "#FFF8E7"),
        ("SPECIALTY BLEND", "mid", "#D7CCC8"),
        ("Better Days", "sub", "#BCAAA4"),
    ]),
    "sticker-honey": ("product", [
        ("HONEY", "title", "#8A6A10"),
        ("PURE NATURAL 100%", "sub", "#A08030"),
    ]),
    "sticker-strawberry-jam": ("product", [
        ("딸기잼", "title", "#B83246"),
        ("Strawberry Jam", "mid", "#C45C6E"),
        ("HOMEMADE", "sub", "#D46A7A"),
    ]),
    "sticker-bakery": ("product", [
        ("BAKERY", "title", "#8B5A20"),
        ("FRESH & DELICIOUS", "sub", "#B07A40"),
    ]),
    "sticker-hand-soap": ("product", [
        ("HAND SOAP", "title", "#2F5A40"),
        ("NATURAL CARE", "mid", "#5B8A6A"),
        ("300ml", "sub", "#7A9E8A"),
    ]),
    "sticker-shampoo": ("product", [
        ("SHAMPOO", "title", "#2A4F42"),
        ("MILD & PURE", "mid", "#5A8A78"),
        ("500ml", "sub", "#7AA090"),
    ]),
    "sticker-thankyou-script": ("center", [
        ("Thank you", "title", "#B8325A"),
        ("with love", "sub", "#D48AA0"),
    ]),
    # Row2
    "sticker-thanks-happy": ("center", [
        ("감사합니다", "title", "#9B3A5A"),
        ("늘 행복하세요", "sub", "#C45C7A"),
    ]),
    "sticker-birthday-cake": ("stack", [
        ("생일 축하해요", "title", "#C45C7A"),
        ("Happy Birthday", "sub", "#E891B0"),
    ]),
    "sticker-love-for-you": ("badge", [
        ("LOVE", "title", "#FFFFFF"),
        ("For You", "sub", "#C43A5A"),
    ]),
    "sticker-congrats": ("stack", [
        ("축하합니다", "title", "#C45C7A"),
        ("CONGRATULATIONS", "sub", "#E07A9A"),
    ]),
    "sticker-precious": ("center", [
        ("소중한 당신께", "title", "#8B5A3A"),
        ("For Someone Special", "sub", "#C48A60"),
    ]),
    "sticker-good-luck": ("stack", [
        ("GOOD LUCK", "title", "#2E7D32"),
        ("행운을 빌어요", "sub", "#66BB6A"),
    ]),
    "sticker-happy-birthday": ("center", [
        ("Happy Birthday", "title", "#7B2840"),
        ("Celebrate!", "sub", "#A05070"),
    ]),
    "sticker-merry-christmas": ("product", [
        ("Merry Christmas", "title", "#F5F7FA"),
        ("Season's Greetings", "sub", "#A8D5C4"),
    ]),
    "sticker-happy-new-year": ("product", [
        ("Happy New Year", "title", "#E8EEFF"),
        ("새해 복 많이 받으세요", "sub", "#9FB4E8"),
    ]),
    "sticker-halloween": ("product", [
        ("Halloween", "title", "#1A1208"),
        ("Trick or Treat", "sub", "#3E2723"),
    ]),
    # Row3 food
    "sticker-apple": ("product", [
        ("사과", "title", "#B71C1C"),
        ("Apple", "mid", "#C62828"),
        ("국내산 100%", "sub", "#E57373"),
    ]),
    "sticker-tomato": ("product", [
        ("토마토", "title", "#C62828"),
        ("TOMATO", "mid", "#D32F2F"),
        ("FRESH VEGETABLE", "sub", "#EF9A9A"),
    ]),
    "sticker-organic-veg": ("product", [
        ("유기농 채소", "title", "#33691E"),
        ("ORGANIC", "mid", "#558B2F"),
        ("FARM FRESH", "sub", "#7CB342"),
    ]),
    "sticker-hanwoo": ("product", [
        ("한우", "title", "#3E2723"),
        ("PREMIUM BEEF", "mid", "#5D4037"),
        ("신선한 우리한우", "sub", "#8D6E63"),
    ]),
    "sticker-cookie": ("product", [
        ("수제 쿠키", "title", "#6D4C41"),
        ("HANDMADE COOKIE", "mid", "#8D6E63"),
        ("맛있는 하루", "sub", "#A1887F"),
    ]),
    "sticker-macaron": ("product", [
        ("마카롱", "title", "#AD1457"),
        ("MACARON", "mid", "#EC407A"),
        ("SWEET DESSERT", "sub", "#F48FB1"),
    ]),
    "sticker-salad": ("product", [
        ("샐러드", "title", "#558B2F"),
        ("FRESH SALAD", "mid", "#689F38"),
        ("HEALTHY LIFE", "sub", "#AED581"),
    ]),
    "sticker-banchan": ("product", [
        ("반찬", "title", "#5D4037"),
        ("HOMEMADE", "mid", "#8D6E63"),
        ("맛있는 집밥", "sub", "#A1887F"),
    ]),
    "sticker-kimchi": ("product", [
        ("김치", "title", "#B71C1C"),
        ("KIMCHI", "mid", "#C62828"),
        ("국내산 재료", "sub", "#E57373"),
    ]),
    "sticker-pure-honey": ("product", [
        ("벌꿀", "title", "#F57F17"),
        ("PURE HONEY", "mid", "#F9A825"),
        ("자연 그대로", "sub", "#FFB300"),
    ]),
    # Row4 icons
    "sticker-organic": ("icon", [("ORGANIC", "title", "#2E7D32"), ("ECO FRIENDLY", "sub", "#66BB6A")]),
    "sticker-vegan": ("icon", [("VEGAN", "title", "#388E3C"), ("PLANT BASED", "sub", "#81C784")]),
    "sticker-gluten-free": ("icon", [("GLUTEN FREE", "title", "#5D4037"), ("No Wheat", "sub", "#A1887F")]),
    "sticker-non-gmo": ("icon", [("NON GMO", "title", "#33691E"), ("Natural Choice", "sub", "#7CB342")]),
    "sticker-recycle": ("icon", [("RECYCLE", "title", "#2E7D32"), ("SAVE OUR PLANET", "sub", "#66BB6A")]),
    "sticker-fragile": ("icon", [("FRAGILE", "title", "#B71C1C"), ("취급주의", "sub", "#E57373")]),
    "sticker-this-side-up": ("icon", [("THIS SIDE UP", "title", "#212121"), ("위로 가게", "sub", "#757575")]),
    "sticker-keep-dry": ("icon", [("KEEP DRY", "title", "#1565C0"), ("습기주의", "sub", "#64B5F6")]),
    "sticker-caution": ("icon", [("CAUTION", "title", "#212121"), ("주의", "sub", "#F57F17")]),
    "sticker-handle-care": ("icon", [("HANDLE WITH CARE", "title", "#3E2723"), ("조심히 다뤄주세요", "sub", "#8D6E63")]),
    # Row5 messages
    "sticker-order-thanks": ("center", [
        ("주문해 주셔서 감사합니다", "title", "#5A1E30"),
        ("좋은 하루 되세요", "sub", "#9A6070"),
    ]),
    "sticker-fast-ship": ("stack", [
        ("빠른 배송 감사합니다!", "title", "#1565C0"),
        ("Thank you", "sub", "#64B5F6"),
    ]),
    "sticker-gift-for-you": ("stack", [
        ("선물입니다", "title", "#B71C1C"),
        ("FOR YOU", "sub", "#E57373"),
    ]),
    "sticker-open-me": ("stack", [
        ("OPEN ME", "title", "#AD1457"),
        ("A little surprise", "sub", "#F48FB1"),
    ]),
    "sticker-special-gift": ("center", [
        ("특별한 날, 특별한 선물", "title", "#AD1457"),
        ("Special Gift", "sub", "#F48FB1"),
    ]),
    "sticker-just-for-you": ("stack", [
        ("JUST FOR YOU", "title", "#C62828"),
        ("with love", "sub", "#EF9A9A"),
    ]),
    "sticker-love-yourself": ("center", [
        ("LOVE YOURSELF", "title", "#8B1E1E"),
        ("You are enough", "sub", "#C62828"),
    ]),
    "sticker-good-day": ("stack", [
        ("오늘도 좋은 하루", "title", "#F57F17"),
        ("Have a nice day", "sub", "#FFB300"),
    ]),
    "sticker-cheer": ("center", [
        ("당신의 빛나는 내일을", "title", "#AD1457"),
        ("응원합니다", "sub", "#EC407A"),
    ]),
    "sticker-good-things": ("stack", [
        ("좋은 일만 가득하길", "title", "#2E7D32"),
        ("Good things ahead", "sub", "#66BB6A"),
    ]),
    # Row6 retail — keep special layouts; refine badge
    "sticker-new": ("badge", [("NEW", "title", "#FFFFFF"), ("신상품", "sub", "#E65100")]),
    "sticker-best": ("badge", [("BEST", "title", "#F57F17"), ("인기상품", "sub", "#FFB300")]),
}


def main() -> None:
    data = json.loads(MANIFEST.read_text(encoding="utf-8"))
    for item in data["items"]:
        slug = item["slug"]
        if slug not in REFINE:
            continue
        layout, texts = REFINE[slug]
        item["layout"] = layout
        item["texts"] = [{"text": t, "role": r, "color": c} for t, r, c in texts]
    MANIFEST.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    print("updated", len(REFINE), "position maps")


if __name__ == "__main__":
    main()
