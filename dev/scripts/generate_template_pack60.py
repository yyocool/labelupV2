#!/usr/bin/env python3
"""Generate pack-60 sticker template arts + manifest for LabelUp templates admin."""
from __future__ import annotations

import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(Path(__file__).resolve().parent))

from lib_pack60_arts import render_art  # noqa: E402

OUT_DIR = ROOT / "public" / "assets" / "templates" / "pack60"
MANIFEST = ROOT / "storage" / "imports" / "template_pack60_manifest.json"
SIZE = 768

# layout: art_top | art_center | art_icon | text_only | special
# texts: list of {text, role: title|sub|kicker, color}
CATALOG = [
    # Row 1
    dict(slug="sticker-handmade", name="HAND MADE", cat="gift", tags="핸드메이드,수제,원형", tone="#8B5E3C", bg="#E8D5B5", art="handmade", layout="art_center",
         texts=[("HAND MADE", "title", "#5C3A1E"), ("with love", "sub", "#A05050")], desc="크라프트 핸드메이드 스티커"),
    dict(slug="sticker-thankyou-smile", name="THANK YOU 스마일", cat="gift", tags="감사,스마일", tone="#F5C518", bg="#FFF9E8", art="smile", layout="art_top",
         texts=[("THANK YOU", "title", "#2E2A27"), ("항상 감사합니다", "sub", "#5B5560")], desc="스마일 Thank You"),
    dict(slug="sticker-olive-oil", name="Natural Olive Oil", cat="food", tags="올리브,오일,유기농", tone="#5B7A3A", bg="#F7FAF2", art="olive", layout="art_top",
         texts=[("Natural Olive Oil", "title", "#3F5A28"), ("EXTRA VIRGIN · 100% ORGANIC", "sub", "#6B8F4E")], desc="올리브오일 라벨"),
    dict(slug="sticker-coffee", name="COFFEE SPECIALTY", cat="cafe", tags="커피,카페", tone="#5C3A1E", bg="#3E2723", art="coffee", layout="art_top",
         texts=[("COFFEE", "title", "#FFF8E7"), ("SPECIALTY BLEND", "sub", "#D7CCC8")], desc="스페셜티 커피"),
    dict(slug="sticker-honey", name="HONEY Pure", cat="food", tags="꿀,식품", tone="#C9A227", bg="#FFF8E0", art="bee", layout="art_top",
         texts=[("HONEY", "title", "#8A6A10"), ("PURE NATURAL 100%", "sub", "#A08030")], desc="천연 꿀 라벨"),
    dict(slug="sticker-strawberry-jam", name="딸기잼 Homemade", cat="food", tags="잼,딸기,수제", tone="#C23B4A", bg="#FFF5F7", art="strawberry", layout="art_top",
         texts=[("딸기잼", "title", "#B83246"), ("Strawberry Jam · HOMEMADE", "sub", "#D46A7A")], desc="수제 딸기잼"),
    dict(slug="sticker-bakery", name="BAKERY Fresh", cat="cafe", tags="베이커리,빵", tone="#C4873A", bg="#FFF8EE", art="croissant", layout="art_top",
         texts=[("BAKERY", "title", "#8B5A20"), ("FRESH & DELICIOUS", "sub", "#B07A40")], desc="베이커리 스티커"),
    dict(slug="sticker-hand-soap", name="HAND SOAP", cat="beauty", tags="핸드솝,화장품", tone="#5B8A6A", bg="#F1F7F3", art="leaf", layout="art_top",
         texts=[("HAND SOAP", "title", "#2F5A40"), ("NATURAL CARE · 300ml", "sub", "#5B8A6A")], desc="핸드솝 라벨"),
    dict(slug="sticker-shampoo", name="SHAMPOO Mild", cat="beauty", tags="샴푸,헤어", tone="#3D6B5A", bg="#F5FAF7", art="leaf", layout="art_top",
         texts=[("SHAMPOO", "title", "#2A4F42"), ("MILD & PURE · 500ml", "sub", "#5A8A78")], desc="마일드 샴푸"),
    dict(slug="sticker-thankyou-script", name="Thank you 플라워", cat="gift", tags="감사,플라워", tone="#D46A8A", bg="#FFF0F5", art="floral", layout="art_center",
         texts=[("Thank you", "title", "#B8325A"), ("with love", "sub", "#D48AA0")], desc="스크립트 Thank you"),
    # Row 2
    dict(slug="sticker-thanks-happy", name="감사합니다 행복하세요", cat="gift", tags="감사,한글", tone="#C45C7A", bg="#FFFFFF", art="floral", layout="art_center",
         texts=[("감사합니다", "title", "#9B3A5A"), ("늘 행복하세요", "sub", "#C45C7A")], desc="한글 감사 메시지"),
    dict(slug="sticker-birthday-cake", name="생일 축하해요", cat="event", tags="생일,케이크", tone="#E891B0", bg="#FFF5F8", art="cake", layout="art_top",
         texts=[("생일 축하해요", "title", "#C45C7A"), ("Happy Birthday", "sub", "#E891B0")], desc="생일 케이크"),
    dict(slug="sticker-love-for-you", name="LOVE For You", cat="gift", tags="사랑,하트", tone="#E85A7A", bg="#FFF0F4", art="heart", layout="art_center",
         texts=[("LOVE", "title", "#FFFFFF"), ("For You", "sub", "#C43A5A")], desc="러브 하트"),
    dict(slug="sticker-congrats", name="축하합니다", cat="event", tags="축하,리본", tone="#E07A9A", bg="#FFF5F8", art="bow", layout="art_top",
         texts=[("축하합니다", "title", "#C45C7A"), ("CONGRATULATIONS", "sub", "#E07A9A")], desc="축하 리본"),
    dict(slug="sticker-precious", name="소중한 당신께", cat="gift", tags="선물,리스", tone="#D48A5A", bg="#FFF8F0", art="wreath", layout="art_center",
         texts=[("소중한 당신께", "title", "#8B5A3A"), ("For Someone Special", "sub", "#C48A60")], desc="소중한 당신께"),
    dict(slug="sticker-good-luck", name="GOOD LUCK", cat="gift", tags="행운,클로버", tone="#4CAF50", bg="#F1F8F2", art="clover", layout="art_top",
         texts=[("GOOD LUCK", "title", "#2E7D32"), ("행운을 빌어요", "sub", "#66BB6A")], desc="행운 클로버"),
    dict(slug="sticker-happy-birthday", name="Happy Birthday", cat="event", tags="생일,영문", tone="#7B2840", bg="#FFFFFF", art="floral", layout="art_center",
         texts=[("Happy Birthday", "title", "#7B2840"), ("Celebrate!", "sub", "#A05070")], desc="해피 버스데이"),
    dict(slug="sticker-merry-christmas", name="Merry Christmas", cat="event", tags="크리스마스", tone="#1B4D3E", bg="#0F3D2E", art="tree_xmas", layout="art_top",
         texts=[("Merry Christmas", "title", "#F5F7FA"), ("Season's Greetings", "sub", "#A8D5C4")], desc="메리 크리스마스"),
    dict(slug="sticker-happy-new-year", name="Happy New Year", cat="event", tags="새해,겨울", tone="#1A237E", bg="#0D1B4C", art="snow", layout="art_top",
         texts=[("Happy New Year", "title", "#E8EEFF"), ("새해 복 많이 받으세요", "sub", "#9FB4E8")], desc="해피 뉴이어"),
    dict(slug="sticker-halloween", name="Halloween", cat="event", tags="할로윈,호박", tone="#E65100", bg="#FF6D00", art="pumpkin", layout="art_top",
         texts=[("Halloween", "title", "#1A1208"), ("Trick or Treat", "sub", "#3E2723")], desc="할로윈"),
    # Row 3
    dict(slug="sticker-apple", name="사과 국내산", cat="food", tags="사과,과일", tone="#C62828", bg="#FFFFFF", art="apple", layout="art_top",
         texts=[("사과 Apple", "title", "#B71C1C"), ("국내산 100%", "sub", "#E57373")], desc="사과 라벨"),
    dict(slug="sticker-tomato", name="토마토 Fresh", cat="food", tags="토마토,채소", tone="#D32F2F", bg="#FFFFFF", art="tomato", layout="art_top",
         texts=[("토마토 TOMATO", "title", "#C62828"), ("FRESH VEGETABLE", "sub", "#EF9A9A")], desc="토마토 라벨"),
    dict(slug="sticker-organic-veg", name="유기농 채소", cat="food", tags="유기농,채소", tone="#558B2F", bg="#FFFFFF", art="veggies", layout="art_top",
         texts=[("유기농 채소", "title", "#33691E"), ("ORGANIC · FARM FRESH", "sub", "#7CB342")], desc="유기농 채소"),
    dict(slug="sticker-hanwoo", name="한우 Premium", cat="food", tags="한우,육류", tone="#5D4037", bg="#FBF6F0", art="cow", layout="art_top",
         texts=[("한우 PREMIUM BEEF", "title", "#3E2723"), ("신선한 우리한우", "sub", "#8D6E63")], desc="한우 라벨"),
    dict(slug="sticker-cookie", name="수제 쿠키", cat="food", tags="쿠키,수제", tone="#A1887F", bg="#FFF8F0", art="cookie", layout="art_top",
         texts=[("수제 쿠키", "title", "#6D4C41"), ("HANDMADE COOKIE · 맛있는 하루", "sub", "#A1887F")], desc="수제 쿠키"),
    dict(slug="sticker-macaron", name="마카롱", cat="cafe", tags="마카롱,디저트", tone="#EC407A", bg="#FFF5F8", art="macaron", layout="art_top",
         texts=[("마카롱 MACARON", "title", "#AD1457"), ("SWEET DESSERT", "sub", "#F48FB1")], desc="마카롱"),
    dict(slug="sticker-salad", name="샐러드", cat="food", tags="샐러드,건강", tone="#689F38", bg="#FFFFFF", art="salad", layout="art_top",
         texts=[("샐러드 FRESH SALAD", "title", "#558B2F"), ("HEALTHY LIFE", "sub", "#AED581")], desc="프레시 샐러드"),
    dict(slug="sticker-banchan", name="반찬 Homemade", cat="food", tags="반찬,집밥", tone="#8D6E63", bg="#FFF8F0", art="utensils", layout="art_top",
         texts=[("반찬 HOMEMADE", "title", "#5D4037"), ("맛있는 집밥", "sub", "#A1887F")], desc="반찬 라벨"),
    dict(slug="sticker-kimchi", name="김치", cat="food", tags="김치,발효", tone="#C62828", bg="#FFFFFF", art="kimchi", layout="art_top",
         texts=[("김치 KIMCHI", "title", "#B71C1C"), ("국내산 재료", "sub", "#E57373")], desc="김치 라벨"),
    dict(slug="sticker-pure-honey", name="벌꿀 Pure Honey", cat="food", tags="벌꿀,천연", tone="#F9A825", bg="#FFFDE7", art="honey_dipper", layout="art_top",
         texts=[("벌꿀 PURE HONEY", "title", "#F57F17"), ("자연 그대로", "sub", "#FFB300")], desc="벌꿀 딥퍼"),
    # Row 4
    dict(slug="sticker-organic", name="ORGANIC Eco", cat="warning", tags="유기농,에코", tone="#43A047", bg="#FFFFFF", art="leaf", layout="art_icon",
         texts=[("ORGANIC", "title", "#2E7D32"), ("ECO FRIENDLY", "sub", "#66BB6A")], desc="오가닉 마크"),
    dict(slug="sticker-vegan", name="VEGAN Plant", cat="warning", tags="비건,식물", tone="#66BB6A", bg="#FFFFFF", art="leaf", layout="art_icon",
         texts=[("VEGAN", "title", "#388E3C"), ("PLANT BASED", "sub", "#81C784")], desc="비건 마크"),
    dict(slug="sticker-gluten-free", name="GLUTEN FREE", cat="warning", tags="글루텐프리", tone="#8D6E63", bg="#FFFFFF", art="leaf", layout="art_icon",
         texts=[("GLUTEN FREE", "title", "#5D4037"), ("No Wheat", "sub", "#A1887F")], desc="글루텐프리"),
    dict(slug="sticker-non-gmo", name="NON GMO", cat="warning", tags="논지엠오", tone="#558B2F", bg="#FFFFFF", art="leaf", layout="art_icon",
         texts=[("NON GMO", "title", "#33691E"), ("Natural Choice", "sub", "#7CB342")], desc="논지엠오"),
    dict(slug="sticker-recycle", name="RECYCLE", cat="warning", tags="재활용,환경", tone="#43A047", bg="#FFFFFF", art="recycle", layout="art_icon",
         texts=[("RECYCLE", "title", "#2E7D32"), ("SAVE OUR PLANET", "sub", "#66BB6A")], desc="리사이클"),
    dict(slug="sticker-fragile", name="FRAGILE 취급주의", cat="shipping", tags="파손,취급주의", tone="#C62828", bg="#FFFFFF", art="fragile", layout="art_icon",
         texts=[("FRAGILE", "title", "#B71C1C"), ("취급주의", "sub", "#E57373")], desc="파손주의"),
    dict(slug="sticker-this-side-up", name="THIS SIDE UP", cat="shipping", tags="방향,배송", tone="#212121", bg="#FFFFFF", art="arrows_up", layout="art_icon",
         texts=[("THIS SIDE UP", "title", "#212121"), ("위로 가게", "sub", "#757575")], desc="이 면이 위로"),
    dict(slug="sticker-keep-dry", name="KEEP DRY", cat="shipping", tags="습기,배송", tone="#1976D2", bg="#FFFFFF", art="umbrella", layout="art_icon",
         texts=[("KEEP DRY", "title", "#1565C0"), ("습기주의", "sub", "#64B5F6")], desc="습기주의"),
    dict(slug="sticker-caution", name="CAUTION 주의", cat="warning", tags="주의,경고", tone="#F9A825", bg="#FFFDE7", art="caution", layout="art_icon",
         texts=[("CAUTION", "title", "#212121"), ("주의", "sub", "#F57F17")], desc="주의 표시"),
    dict(slug="sticker-handle-care", name="HANDLE WITH CARE", cat="shipping", tags="취급,배송", tone="#5D4037", bg="#FFFFFF", art="hands", layout="art_icon",
         texts=[("HANDLE WITH CARE", "title", "#3E2723"), ("조심히 다뤄주세요", "sub", "#8D6E63")], desc="취급주의"),
    # Row 5
    dict(slug="sticker-order-thanks", name="주문 감사합니다", cat="gift", tags="주문,감사", tone="#7B2840", bg="#FFFFFF", art="smile", layout="art_top",
         texts=[("주문해 주셔서 감사합니다", "title", "#5A1E30"), ("좋은 하루 되세요", "sub", "#9A6070")], desc="주문 감사"),
    dict(slug="sticker-fast-ship", name="빠른 배송 감사", cat="shipping", tags="배송,감사", tone="#1976D2", bg="#FFFFFF", art="truck", layout="art_top",
         texts=[("빠른 배송 감사합니다!", "title", "#1565C0"), ("Thank you", "sub", "#64B5F6")], desc="빠른 배송"),
    dict(slug="sticker-gift-for-you", name="선물입니다", cat="gift", tags="선물,리본", tone="#C62828", bg="#FFF5F5", art="bow", layout="art_top",
         texts=[("선물입니다", "title", "#B71C1C"), ("FOR YOU", "sub", "#E57373")], desc="선물 메시지"),
    dict(slug="sticker-open-me", name="OPEN ME", cat="gift", tags="편지,하트", tone="#E91E63", bg="#FFF0F5", art="envelope", layout="art_top",
         texts=[("OPEN ME", "title", "#AD1457"), ("A little surprise", "sub", "#F48FB1")], desc="Open Me"),
    dict(slug="sticker-special-gift", name="특별한 선물", cat="gift", tags="선물,벚꽃", tone="#EC407A", bg="#FFFFFF", art="floral", layout="art_center",
         texts=[("특별한 날, 특별한 선물", "title", "#AD1457"), ("Special Gift", "sub", "#F48FB1")], desc="특별한 선물"),
    dict(slug="sticker-just-for-you", name="JUST FOR YOU", cat="gift", tags="하트,선물", tone="#E53935", bg="#FFF5F5", art="heart", layout="art_top",
         texts=[("JUST FOR YOU", "title", "#C62828"), ("with love", "sub", "#EF9A9A")], desc="Just For You"),
    dict(slug="sticker-love-yourself", name="LOVE YOURSELF", cat="gift", tags="셀프케어,하트", tone="#C62828", bg="#FFF8F0", art="laurel_heart", layout="art_center",
         texts=[("LOVE YOURSELF", "title", "#8B1E1E"), ("You are enough", "sub", "#C62828")], desc="Love Yourself"),
    dict(slug="sticker-good-day", name="오늘도 좋은 하루", cat="gift", tags="응원,스마일", tone="#FBC02D", bg="#FFFFFF", art="smile", layout="art_top",
         texts=[("오늘도 좋은 하루", "title", "#F57F17"), ("Have a nice day", "sub", "#FFB300")], desc="좋은 하루"),
    dict(slug="sticker-cheer", name="빛나는 내일을 응원", cat="gift", tags="응원,메시지", tone="#C2185B", bg="#FFFFFF", art="floral", layout="art_center",
         texts=[("당신의 빛나는 내일을", "title", "#AD1457"), ("응원합니다", "sub", "#EC407A")], desc="응원 메시지"),
    dict(slug="sticker-good-things", name="좋은 일만 가득하길", cat="gift", tags="행운,클로버", tone="#43A047", bg="#FFFFFF", art="clover", layout="art_top",
         texts=[("좋은 일만 가득하길", "title", "#2E7D32"), ("Good things ahead", "sub", "#66BB6A")], desc="좋은 일 가득"),
    # Row 6
    dict(slug="sticker-barcode", name="바코드 PRODUCT", cat="price", tags="바코드,상품", tone="#212121", bg="#FFFFFF", art="barcode", layout="special_barcode",
         texts=[("PRODUCT", "title", "#212121"), ("8801234567890", "sub", "#616161")], desc="상품 바코드"),
    dict(slug="sticker-price", name="PRICE 가격표", cat="price", tags="가격,원", tone="#C62828", bg="#FFFFFF", art="", layout="special_price",
         texts=[("PRICE", "title", "#C62828"), ("₩ 3,900", "sub", "#B71C1C")], desc="가격표"),
    dict(slug="sticker-sale", name="SALE 50% OFF", cat="price", tags="세일,할인", tone="#C62828", bg="#C62828", art="", layout="special_sale",
         texts=[("SALE", "title", "#FFFFFF"), ("UP TO 50% OFF", "sub", "#FFCDD2")], desc="세일"),
    dict(slug="sticker-new", name="NEW 신상품", cat="event", tags="신상품,NEW", tone="#EF6C00", bg="#FFF8F0", art="starburst", layout="art_center",
         texts=[("NEW", "title", "#FFFFFF"), ("신상품", "sub", "#E65100")], desc="신상품"),
    dict(slug="sticker-best", name="BEST 인기상품", cat="event", tags="베스트,인기", tone="#F9A825", bg="#FFFDE7", art="crown", layout="art_center",
         texts=[("BEST", "title", "#F57F17"), ("인기상품", "sub", "#FFB300")], desc="베스트"),
    dict(slug="sticker-one-plus-one", name="1+1 SPECIAL", cat="event", tags="이벤트,1+1", tone="#C62828", bg="#FFFFFF", art="", layout="special_event",
         texts=[("1+1", "title", "#C62828"), ("SPECIAL EVENT", "sub", "#E57373")], desc="1+1 이벤트"),
    dict(slug="sticker-coupon-20", name="20% 할인쿠폰", cat="price", tags="쿠폰,할인", tone="#2E7D32", bg="#FFFFFF", art="", layout="special_coupon",
         texts=[("20%", "title", "#2E7D32"), ("할인쿠폰", "sub", "#66BB6A")], desc="20% 쿠폰"),
    dict(slug="sticker-sample", name="SAMPLE 샘플", cat="warehouse", tags="샘플,재고", tone="#757575", bg="#F5F5F5", art="", layout="special_sample",
         texts=[("SAMPLE", "title", "#424242"), ("샘플상품 · NOT FOR SALE", "sub", "#9E9E9E")], desc="샘플 상품"),
    dict(slug="sticker-lot-exp", name="LOT / EXP", cat="warehouse", tags="유통기한,로트", tone="#212121", bg="#FFFFFF", art="", layout="special_lot",
         texts=[("LOT NO.", "title", "#212121"), ("EXP. DATE", "sub", "#616161")], desc="LOT/유통기한"),
    dict(slug="sticker-qr-scan", name="SCAN ME QR", cat="price", tags="QR,스캔", tone="#212121", bg="#FFFFFF", art="qr", layout="special_qr",
         texts=[("SCAN ME", "title", "#212121"), ("www.labelup.co.kr", "sub", "#757575")], desc="QR 스캔"),
]


def main():
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    MANIFEST.parent.mkdir(parents=True, exist_ok=True)
    items = []
    for i, row in enumerate(CATALOG, start=1):
        slug = row["slug"]
        art_kind = row.get("art") or ""
        art_file = ""
        if art_kind:
            art = render_art(art_kind, SIZE)
            art_name = f"{slug}_art.png"
            art.save(OUT_DIR / art_name, "PNG", optimize=True)
            art_file = f"/assets/templates/pack60/{art_name}"
            print(f"[{i:02d}/60] art {slug} ({art_kind})")
        else:
            print(f"[{i:02d}/60] shape-only {slug}")
        items.append({
            "slug": slug,
            "name": row["name"],
            "category": row["cat"],
            "tags": row["tags"],
            "description": row["desc"],
            "tone": row["tone"],
            "bg": row["bg"],
            "layout": row["layout"],
            "art_url": art_file,
            "texts": [{"text": t[0], "role": t[1], "color": t[2]} for t in row["texts"]],
            "sort_order": 100 + i,
            "paper_w_mm": 50,
            "paper_h_mm": 50,
            "paper_shape": "roundrect",
            "paper_no": f"LU-P60-{i:02d}",
        })
    MANIFEST.write_text(json.dumps({"version": 1, "count": len(items), "items": items}, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Wrote {len(items)} arts → {OUT_DIR}")
    print(f"Manifest → {MANIFEST}")


if __name__ == "__main__":
    main()
