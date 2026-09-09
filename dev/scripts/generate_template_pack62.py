#!/usr/bin/env python3
"""Generate pack-62 sticker template arts + manifest (third reference sheet)."""
from __future__ import annotations

import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(Path(__file__).resolve().parent))

from lib_pack60_arts import render_art  # noqa: E402

OUT_DIR = ROOT / "public" / "assets" / "templates" / "pack62"
MANIFEST = ROOT / "storage" / "imports" / "template_pack62_manifest.json"
SIZE = 768

# layout: product | stack | art_bottom | deco_top | center | icon | badge | special_*
CATALOG = [
    # Row1
    dict(slug="p62-thank-you-wreath", name="Thank you Small Business", cat="gift", tags="감사,리스", tone="#5B7A3A", bg="#F7F3EA", art="wreath", layout="center",
         texts=[("Thank you", "title", "#3F5A28"), ("SMALL BUSINESS BIG HAPPINESS", "sub", "#6B8F4E")], desc="Thank you 리스"),
    dict(slug="p62-handmade-love", name="HANDMADE with love", cat="gift", tags="핸드메이드", tone="#8B5E3C", bg="#E8D5B5", art="kraft_circle", layout="center",
         texts=[("HANDMADE with love", "title", "#5C3A1E"), ("♥", "sub", "#C45C6E")], desc="핸드메이드 with love"),
    dict(slug="p62-handmade-jam", name="수제잼 HANDMADE JAM", cat="food", tags="잼,딸기", tone="#C23B4A", bg="#FFF5F7", art="strawberry", layout="product",
         texts=[("수제잼", "title", "#B83246"), ("HANDMADE JAM", "mid", "#C45C6E"), ("100% NATURAL", "sub", "#D46A7A")], desc="수제잼"),
    dict(slug="p62-natural-cosmetic", name="NATURAL COSMETIC", cat="beauty", tags="화장품,내추럴", tone="#5B7A3A", bg="#F1F7F3", art="leaves_sprig", layout="product",
         texts=[("NATURAL COSMETIC", "title", "#2F5A40"), ("PURE & MILD", "sub", "#5B8A6A")], desc="내추럴 코스메틱"),
    dict(slug="p62-sugoheosseoyo", name="오늘도 수고했어요", cat="gift", tags="응원,스마일", tone="#F5C518", bg="#FFF9E8", art="smile", layout="stack",
         texts=[("오늘도 수고했어요", "title", "#2E2A27"), ("You're Amazing!", "sub", "#5B5560")], desc="오늘도 수고했어요"),
    dict(slug="p62-premium-coffee", name="PREMIUM COFFEE", cat="cafe", tags="커피,원두", tone="#5C3A1E", bg="#3E2723", art="coffee", layout="product",
         texts=[("PREMIUM COFFEE", "title", "#FFF8E7"), ("100% ARABICA", "sub", "#D7CCC8")], desc="프리미엄 커피"),
    dict(slug="p62-especially-floral", name="Especially for you", cat="gift", tags="선물,플라워", tone="#C45C7A", bg="#FFF0F5", art="floral", layout="center",
         texts=[("Especially for you", "title", "#9B3A5A")], desc="Especially for you"),
    dict(slug="p62-teddy-good-day", name="좋은 하루 되세요", cat="gift", tags="곰돌이,응원", tone="#8B5E3C", bg="#FFE8F0", art="bear", layout="stack",
         texts=[("좋은 하루 되세요", "title", "#5C3A1E")], desc="좋은 하루 되세요"),
    dict(slug="p62-plant-based", name="PLANT BASED", cat="warning", tags="비건,에코", tone="#43A047", bg="#FFFFFF", art="leaf", layout="icon",
         texts=[("PLANT BASED", "title", "#2E7D32"), ("ECO FRIENDLY", "sub", "#66BB6A")], desc="플랜트 베이스드"),
    dict(slug="p62-big-sale", name="BIG SALE 70%", cat="price", tags="세일,할인", tone="#C62828", bg="#FFFFFF", art="cart", layout="stack",
         texts=[("BIG SALE", "title", "#C62828"), ("UP TO 70% OFF", "sub", "#E57373")], desc="빅세일"),
    # Row2
    dict(slug="p62-happy-day", name="행복한 하루 되세요", cat="gift", tags="응원,꽃", tone="#F9A825", bg="#FFFDE7", art="yellow_flowers", layout="art_bottom",
         texts=[("행복한 하루 되세요", "title", "#F57F17"), ("Have a Nice Day", "sub", "#FFB300")], desc="행복한 하루"),
    dict(slug="p62-bakery", name="BAKERY Fresh", cat="cafe", tags="베이커리,빵", tone="#C4873A", bg="#FFF8EE", art="croissant", layout="product",
         texts=[("BAKERY", "title", "#8B5A20"), ("FRESHLY BAKED EVERYDAY", "sub", "#B07A40")], desc="베이커리"),
    dict(slug="p62-cookie", name="수제 쿠키", cat="food", tags="쿠키,수제", tone="#A1887F", bg="#FFF8F0", art="cookie", layout="product",
         texts=[("수제 쿠키", "title", "#6D4C41"), ("HANDMADE COOKIE", "mid", "#8D6E63"), ("달콤한 하루", "sub", "#A1887F")], desc="수제 쿠키"),
    dict(slug="p62-vegan", name="VEGAN", cat="warning", tags="비건", tone="#66BB6A", bg="#FFFFFF", art="leaf", layout="icon",
         texts=[("VEGAN", "title", "#388E3C"), ("GOOD FOR YOU GOOD FOR EARTH", "sub", "#81C784")], desc="비건"),
    dict(slug="p62-love-yourself", name="LOVE YOURSELF", cat="gift", tags="하트,셀프", tone="#E85A7A", bg="#FFF0F4", art="heart", layout="badge",
         texts=[("LOVE YOURSELF", "title", "#FFFFFF")], desc="러브 유어셀프"),
    dict(slug="p62-fresh-food", name="신선식품 FRESH", cat="food", tags="신선,채소", tone="#558B2F", bg="#FFFFFF", art="veggies_mix", layout="product",
         texts=[("신선식품", "title", "#33691E"), ("FRESH FOOD", "mid", "#558B2F"), ("맛있는 오늘", "sub", "#7CB342")], desc="신선식품"),
    dict(slug="p62-baby-on-board", name="BABY ON BOARD", cat="warning", tags="아기,주의", tone="#EC407A", bg="#FFF5F8", art="baby", layout="stack",
         texts=[("BABY ON BOARD", "title", "#AD1457")], desc="베이비 온 보드"),
    dict(slug="p62-caution-fragile", name="주의 파손주의", cat="shipping", tags="주의,파손", tone="#C62828", bg="#FFFFFF", art="caution", layout="icon",
         texts=[("주의", "title", "#B71C1C"), ("파손주의", "mid", "#E57373"), ("취급을 주의해 주세요", "sub", "#EF9A9A")], desc="파손주의"),
    dict(slug="p62-parfum", name="EAU DE PARFUM", cat="beauty", tags="향수,플라워", tone="#C45C7A", bg="#FFFFFF", art="floral", layout="art_bottom",
         texts=[("EAU DE PARFUM", "title", "#9B3A5A"), ("NATURAL SCENT", "sub", "#C45C7A")], desc="오드퍼퓸"),
    dict(slug="p62-love-pets", name="LOVE PETS", cat="gift", tags="반려견,펫", tone="#8B5E3C", bg="#FFF8F0", art="dog", layout="product",
         texts=[("LOVE PETS", "title", "#5C3A1E"), ("♥ ♥", "sub", "#A08060")], desc="러브 펫츠"),
    # Row3
    dict(slug="p62-organic-apple", name="ORGANIC APPLE", cat="food", tags="사과,유기농", tone="#C62828", bg="#FFFFFF", art="apple", layout="product",
         texts=[("ORGANIC APPLE", "title", "#B71C1C"), ("NATURAL & FRESH", "sub", "#E57373")], desc="오가닉 애플"),
    dict(slug="p62-fast-delivery", name="FAST DELIVERY", cat="shipping", tags="배송,트럭", tone="#1976D2", bg="#FFFFFF", art="truck", layout="icon",
         texts=[("FAST DELIVERY", "title", "#1565C0"), ("빠른배송", "mid", "#42A5F5"), ("안전하게 도착합니다", "sub", "#64B5F6")], desc="빠른배송"),
    dict(slug="p62-gamsahamnida", name="감사합니다", cat="gift", tags="감사,카네이션", tone="#C45C7A", bg="#FFFFFF", art="carnation", layout="art_bottom",
         texts=[("감사합니다", "title", "#9B3A5A"), ("항상 고맙습니다", "sub", "#C45C7A")], desc="감사합니다"),
    dict(slug="p62-home-sweet", name="HOME SWEET HOME", cat="gift", tags="집,홈", tone="#8B5E3C", bg="#FFF8F0", art="house", layout="stack",
         texts=[("HOME SWEET HOME", "title", "#5C3A1E")], desc="홈 스위트 홈"),
    dict(slug="p62-lavender", name="LAVENDER Relax", cat="beauty", tags="라벤더", tone="#7E57C2", bg="#F8F5FC", art="lavender", layout="product",
         texts=[("LAVENDER", "title", "#5E35B1"), ("RELAXING", "sub", "#9575CD")], desc="라벤더"),
    dict(slug="p62-clean-beauty", name="CLEAN BEAUTY", cat="beauty", tags="뷰티,클린", tone="#43A047", bg="#F1F8F2", art="leaf", layout="product",
         texts=[("CLEAN BEAUTY", "title", "#2E7D32"), ("FOR A BETTER TOMORROW", "sub", "#66BB6A")], desc="클린 뷰티"),
    dict(slug="p62-best-quality", name="BEST QUALITY", cat="event", tags="베스트,왕관", tone="#F9A825", bg="#FFFDE7", art="crown", layout="badge",
         texts=[("BEST QUALITY", "title", "#F57F17"), ("PREMIUM PRODUCT", "sub", "#FFB300")], desc="베스트 퀄리티"),
    dict(slug="p62-expiry", name="유통기한", cat="warehouse", tags="유통기한", tone="#212121", bg="#FFFFFF", art="", layout="special_dates",
         texts=[("유통기한", "title", "#212121"), ("개봉 후에는 빠른 시일 내에 드세요", "sub", "#616161")], desc="유통기한"),
    dict(slug="p62-barcode", name="PRODUCT 바코드", cat="price", tags="바코드", tone="#212121", bg="#FFFFFF", art="barcode", layout="special_barcode",
         texts=[("PRODUCT", "title", "#212121"), ("8801234567890", "sub", "#616161")], desc="바코드"),
    dict(slug="p62-scan-me", name="SCAN ME QR", cat="price", tags="QR", tone="#212121", bg="#FFFFFF", art="qr", layout="special_qr",
         texts=[("SCAN ME", "title", "#212121"), ("더 많은 이야기를 만나보세요", "sub", "#757575")], desc="SCAN ME"),
    # Row4 safety
    dict(slug="p62-chilled", name="냉장보관", cat="shipping", tags="냉장", tone="#0288D1", bg="#E1F5FE", art="snowflake", layout="icon",
         texts=[("냉장보관", "title", "#0277BD"), ("0~10°C", "sub", "#4FC3F7")], desc="냉장보관"),
    dict(slug="p62-frozen", name="냉동보관", cat="shipping", tags="냉동", tone="#0277BD", bg="#E3F2FD", art="thermometer", layout="icon",
         texts=[("냉동보관", "title", "#01579B"), ("-18°C 이하", "sub", "#4FC3F7")], desc="냉동보관"),
    dict(slug="p62-microwave", name="전자레인지 사용 가능", cat="warning", tags="전자레인지", tone="#455A64", bg="#FFFFFF", art="microwave", layout="icon",
         texts=[("전자레인지 사용 가능", "title", "#37474F")], desc="전자레인지"),
    dict(slug="p62-oven", name="오븐 사용 가능", cat="warning", tags="오븐", tone="#5D4037", bg="#FFFFFF", art="oven", layout="icon",
         texts=[("오븐 사용 가능", "title", "#3E2723"), ("180°C 이하", "sub", "#8D6E63")], desc="오븐"),
    dict(slug="p62-dishwasher", name="식기세척기 사용 가능", cat="warning", tags="식기세척기", tone="#0277BD", bg="#FFFFFF", art="dishwasher", layout="icon",
         texts=[("식기세척기 사용 가능", "title", "#01579B")], desc="식기세척기"),
    dict(slug="p62-bpa-free", name="BPA FREE", cat="warning", tags="BPA,안전", tone="#43A047", bg="#FFFFFF", art="bpa_free", layout="icon",
         texts=[("BPA FREE", "title", "#2E7D32"), ("안전한 소재", "sub", "#66BB6A")], desc="BPA FREE"),
    dict(slug="p62-recycle", name="RECYCLE", cat="warning", tags="재활용", tone="#43A047", bg="#FFFFFF", art="recycle", layout="icon",
         texts=[("RECYCLE", "title", "#2E7D32"), ("지구를 지켜요", "sub", "#66BB6A")], desc="리사이클"),
    dict(slug="p62-gluten-free", name="GLUTEN FREE", cat="warning", tags="글루텐프리", tone="#8D6E63", bg="#FFFFFF", art="gluten_free", layout="icon",
         texts=[("GLUTEN FREE", "title", "#5D4037"), ("글루텐 프리", "sub", "#A1887F")], desc="글루텐프리"),
    dict(slug="p62-non-gmo", name="NON GMO", cat="warning", tags="논지엠오", tone="#558B2F", bg="#FFFFFF", art="non_gmo", layout="icon",
         texts=[("NON GMO", "title", "#33691E"), ("자연 그대로", "sub", "#7CB342")], desc="논지엠오"),
    dict(slug="p62-kids-safe", name="KIDS SAFE", cat="warning", tags="어린이,안전", tone="#EC407A", bg="#FFF5F8", art="kids", layout="icon",
         texts=[("어린이 보호제품", "title", "#AD1457"), ("KIDS SAFE", "sub", "#F48FB1")], desc="키즈 세이프"),
    # Row5 seasonal/events
    dict(slug="p62-just-for-you", name="Just For You", cat="gift", tags="리본,선물", tone="#E07A9A", bg="#FFFFFF", art="bow", layout="deco_top",
         texts=[("Just For You", "title", "#C45C7A")], desc="Just For You"),
    dict(slug="p62-limited", name="한정판 LIMITED", cat="event", tags="한정판", tone="#F9A825", bg="#FFFDE7", art="starburst", layout="badge",
         texts=[("한정판", "title", "#F57F17"), ("LIMITED EDITION", "sub", "#FFB300")], desc="한정판"),
    dict(slug="p62-new-arrival", name="NEW ARRIVAL", cat="event", tags="신상품", tone="#C62828", bg="#FFF8F0", art="starburst", layout="badge",
         texts=[("NEW ARRIVAL", "title", "#FFFFFF")], desc="뉴 어라이벌"),
    dict(slug="p62-skin-care", name="SKIN CARE", cat="beauty", tags="스킨케어", tone="#8D6E63", bg="#FFF8F0", art="skin_profile", layout="product",
         texts=[("SKIN CARE", "title", "#5D4037"), ("BEAUTY LIFE", "sub", "#A1887F")], desc="스킨케어"),
    dict(slug="p62-thank-you-lily", name="Thank You 백합", cat="gift", tags="감사,꽃", tone="#5B7A3A", bg="#FFFFFF", art="lily_bouquet", layout="art_bottom",
         texts=[("Thank You", "title", "#3F5A28")], desc="Thank You 백합"),
    dict(slug="p62-wedding", name="결혼을 축하합니다", cat="event", tags="결혼,반지", tone="#C9A227", bg="#FFFDE7", art="wedding_rings", layout="stack",
         texts=[("결혼을 축하합니다", "title", "#8A6A10")], desc="결혼 축하"),
    dict(slug="p62-christmas", name="Merry Christmas", cat="event", tags="크리스마스", tone="#1B4D3E", bg="#0F3D2E", art="tree_xmas", layout="product",
         texts=[("Merry Christmas", "title", "#F5F7FA")], desc="메리 크리스마스"),
    dict(slug="p62-halloween", name="Happy Halloween", cat="event", tags="할로윈", tone="#E65100", bg="#FF6D00", art="pumpkin", layout="product",
         texts=[("Happy Halloween", "title", "#1A1208")], desc="해피 할로윈"),
    dict(slug="p62-new-year", name="Happy New Year", cat="event", tags="새해", tone="#1A237E", bg="#0D1B4C", art="fireworks", layout="product",
         texts=[("Happy New Year", "title", "#E8EEFF")], desc="해피 뉴이어"),
    dict(slug="p62-spring", name="봄이 왔어요", cat="event", tags="봄,벚꽃", tone="#EC407A", bg="#FFF5F8", art="cherry_blossom", layout="art_bottom",
         texts=[("봄이 왔어요", "title", "#AD1457")], desc="봄이 왔어요"),
    # Row6 seasons/lifestyle
    dict(slug="p62-summer", name="SUMMER VACATION", cat="event", tags="여름,휴가", tone="#0288D1", bg="#E1F5FE", art="summer_beach", layout="product",
         texts=[("SUMMER VACATION", "title", "#01579B")], desc="서머 베케이션"),
    dict(slug="p62-autumn", name="Hello Autumn", cat="event", tags="가을,단풍", tone="#EF6C00", bg="#FFF3E0", art="maple_leaves", layout="product",
         texts=[("Hello Autumn", "title", "#E65100")], desc="헬로 오텀"),
    dict(slug="p62-winter", name="WINTER SEASON", cat="event", tags="겨울,눈사람", tone="#0277BD", bg="#E3F2FD", art="snowman", layout="product",
         texts=[("WINTER SEASON", "title", "#01579B")], desc="윈터 시즌"),
    dict(slug="p62-hello-spring", name="Hello Spring", cat="event", tags="봄,튤립", tone="#EC407A", bg="#FFF5F8", art="tulips", layout="product",
         texts=[("Hello Spring", "title", "#AD1457")], desc="헬로 스프링"),
    dict(slug="p62-coffee-time", name="Coffee Time", cat="cafe", tags="커피,컵", tone="#5C3A1E", bg="#FFF8F0", art="coffee_cup", layout="stack",
         texts=[("Coffee Time", "title", "#3E2723"), ("GOOD DAY", "sub", "#8D6E63")], desc="커피 타임"),
    dict(slug="p62-tea-time", name="TEA TIME", cat="cafe", tags="차,티팟", tone="#5B7A3A", bg="#F1F7F3", art="teapot", layout="stack",
         texts=[("TEA TIME", "title", "#2F5A40"), ("Relax & Enjoy", "sub", "#5B8A6A")], desc="티 타임"),
    dict(slug="p62-enjoy-meal", name="맛있게 드세요", cat="food", tags="식사,하트", tone="#C62828", bg="#FFFFFF", art="utensils", layout="stack",
         texts=[("맛있게 드세요", "title", "#B71C1C")], desc="맛있게 드세요"),
    dict(slug="p62-healthy-salad", name="다이어트 식단", cat="food", tags="샐러드,건강", tone="#689F38", bg="#FFFFFF", art="salad", layout="product",
         texts=[("다이어트 식단", "title", "#558B2F"), ("HEALTHY FOOD", "mid", "#7CB342"), ("가벼운 하루", "sub", "#AED581")], desc="다이어트 식단"),
    dict(slug="p62-handmade-soap", name="수제비누", cat="beauty", tags="비누,수제", tone="#43A047", bg="#F1F8F2", art="soap_bar", layout="product",
         texts=[("수제비누", "title", "#2E7D32"), ("NATURAL SOAP", "sub", "#66BB6A")], desc="수제비누"),
    dict(slug="p62-interior", name="INTERIOR DECOR", cat="gift", tags="인테리어,화분", tone="#5B7A3A", bg="#F7FAF2", art="pot_plant", layout="product",
         texts=[("INTERIOR DECOR", "title", "#3F5A28"), ("SIMPLE LIFE", "sub", "#6B8F4E")], desc="인테리어 데코"),
]


def main() -> None:
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
            art_file = f"/assets/templates/pack62/{art_name}"
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
            "sort_order": 300 + i,
            "paper_w_mm": 50,
            "paper_h_mm": 50,
            "paper_shape": "roundrect",
            "paper_no": f"LU-P62-{i:02d}",
        })

    assert len(items) == 60, len(items)
    MANIFEST.write_text(
        json.dumps({"version": 1, "count": len(items), "items": items}, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    print(f"Wrote {len(items)} arts → {OUT_DIR}")
    print(f"Manifest → {MANIFEST}")


if __name__ == "__main__":
    main()
