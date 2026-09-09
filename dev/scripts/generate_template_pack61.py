#!/usr/bin/env python3
"""Generate pack-61 sticker template arts + manifest (second reference sheet)."""
from __future__ import annotations

import json
import shutil
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(Path(__file__).resolve().parent))

from lib_pack60_arts import render_art  # noqa: E402

OUT_DIR = ROOT / "public" / "assets" / "templates" / "pack61"
MANIFEST = ROOT / "storage" / "imports" / "template_pack61_manifest.json"
REF_SRC = Path(
    r"C:\Users\PW1234\.cursor\projects\c-phpstudy-pro-WWW-labelup\assets"
    r"\c__Users_PW1234_AppData_Roaming_Cursor_User_workspaceStorage_6e6a9ae665954e1a85919f8f8f840eb1_images_image-b77941f8-85f1-41ef-bf66-e10bee265b0d.png"
)
REF_DST = ROOT / "storage" / "imports" / "pack61_reference.png"
SIZE = 768

# layout: product | stack | art_bottom | deco_top | center | icon | badge | special_*
CATALOG = [
    # Row1
    dict(slug="p61-for-you", name="For You 리스", cat="gift", tags="선물,포유,리스", tone="#8B5E3C", bg="#F3E6D4", art="wreath", layout="center",
         texts=[("For You", "title", "#5C3A1E"), ("♥", "sub", "#C45C6E")], desc="포유 플라워 리스"),
    dict(slug="p61-handmade", name="HANDMADE with love", cat="gift", tags="핸드메이드,크라프트", tone="#8B5E3C", bg="#E8D5B5", art="kraft_circle", layout="center",
         texts=[("HANDMADE", "title", "#5C3A1E"), ("with love", "sub", "#A05050")], desc="크라프트 핸드메이드"),
    dict(slug="p61-thank-you", name="Thank You 리프", cat="gift", tags="감사,리프", tone="#5B7A3A", bg="#F7F3EA", art="leaves_sprig", layout="art_bottom",
         texts=[("Thank You", "title", "#3F5A28")], desc="Thank You 리프"),
    dict(slug="p61-special-gift", name="SPECIAL GIFT", cat="gift", tags="선물,리본", tone="#E07A9A", bg="#FFFFFF", art="bow", layout="deco_top",
         texts=[("SPECIAL GIFT", "title", "#C45C7A")], desc="스페셜 기프트"),
    dict(slug="p61-gamsahamnida", name="감사합니다 행복하세요", cat="gift", tags="감사,한글", tone="#C45C7A", bg="#FFFFFF", art="floral", layout="art_bottom",
         texts=[("감사합니다", "title", "#9B3A5A"), ("항상 행복하세요", "sub", "#C45C7A")], desc="한글 감사"),
    dict(slug="p61-happy-birthday", name="HAPPY BIRTHDAY", cat="event", tags="생일,콘페티", tone="#2E2A27", bg="#FFFFFF", art="confetti", layout="center",
         texts=[("HAPPY BIRTHDAY", "title", "#2E2A27")], desc="해피 버스데이 콘페티"),
    dict(slug="p61-especially", name="Especially for you", cat="gift", tags="하트,스크립트", tone="#C45C7A", bg="#FFF0F4", art="heart", layout="art_bottom",
         texts=[("Especially for you", "title", "#9B3A5A")], desc="Especially for you"),
    dict(slug="p61-just-for-you", name="Just for you", cat="gift", tags="리프,스크립트", tone="#5B7A3A", bg="#FFFFFF", art="leaves_sprig", layout="art_bottom",
         texts=[("Just for you", "title", "#3F5A28")], desc="Just for you"),
    dict(slug="p61-love", name="LOVE 하트", cat="gift", tags="사랑,하트", tone="#E85A7A", bg="#FFF0F4", art="heart", layout="badge",
         texts=[("LOVE", "title", "#FFFFFF")], desc="LOVE 하트"),
    dict(slug="p61-congrats", name="축하합니다", cat="event", tags="축하,꽃다발", tone="#C45C7A", bg="#FFFFFF", art="bouquet", layout="art_bottom",
         texts=[("축하합니다", "title", "#9B3A5A")], desc="축하합니다"),
    # Row2
    dict(slug="p61-birthday-cake", name="생일 축하해요", cat="event", tags="생일,케이크", tone="#E891B0", bg="#FFF5F8", art="cake", layout="art_bottom",
         texts=[("생일 축하해요", "title", "#C45C7A")], desc="생일 케이크"),
    dict(slug="p61-happy-wedding", name="HAPPY WEDDING", cat="event", tags="웨딩,꽃", tone="#C45C7A", bg="#FFFFFF", art="wedding_flowers", layout="art_bottom",
         texts=[("HAPPY WEDDING", "title", "#9B3A5A")], desc="해피 웨딩"),
    dict(slug="p61-saranghamnida", name="사랑합니다", cat="gift", tags="사랑,카네이션", tone="#C62828", bg="#FFFFFF", art="carnation", layout="art_bottom",
         texts=[("사랑합니다", "title", "#B71C1C")], desc="사랑합니다"),
    dict(slug="p61-grandma", name="할머니 사랑해요", cat="gift", tags="가족,하트", tone="#C45C7A", bg="#FFF5F8", art="heart", layout="art_bottom",
         texts=[("할머니 사랑해요", "title", "#9B3A5A")], desc="할머니 사랑해요"),
    dict(slug="p61-teacher", name="Thank you Teacher", cat="gift", tags="선생님,감사", tone="#C45C7A", bg="#FFFFFF", art="floral", layout="art_bottom",
         texts=[("Thank you Teacher", "title", "#9B3A5A")], desc="선생님 감사합니다"),
    dict(slug="p61-good-job", name="수고했어요 Good Job", cat="gift", tags="응원,스마일", tone="#F5C518", bg="#FFF9E8", art="smile", layout="stack",
         texts=[("수고했어요", "title", "#2E2A27"), ("Good Job!", "sub", "#5B5560")], desc="수고했어요"),
    dict(slug="p61-cheer-up", name="Cheer Up!", cat="gift", tags="응원,곰돌이", tone="#8B5E3C", bg="#FFF8F0", art="bear", layout="stack",
         texts=[("Cheer Up!", "title", "#5C3A1E")], desc="Cheer Up 곰돌이"),
    dict(slug="p61-good-day", name="좋은 하루 되세요", cat="gift", tags="응원,태양", tone="#5B7A3A", bg="#FFFFFF", art="sun_leaves", layout="art_bottom",
         texts=[("좋은 하루 되세요", "title", "#3F5A28")], desc="좋은 하루"),
    dict(slug="p61-welcome", name="WELCOME 환영", cat="gift", tags="환영,집", tone="#8B5E3C", bg="#FFF8F0", art="house", layout="stack",
         texts=[("WELCOME", "title", "#5C3A1E"), ("우리집에 오신 것을 환영합니다", "sub", "#A08060")], desc="환영합니다"),
    dict(slug="p61-good-luck", name="행운을 빕니다", cat="gift", tags="행운,클로버", tone="#43A047", bg="#F1F8F2", art="clover", layout="art_bottom",
         texts=[("행운을 빕니다", "title", "#2E7D32")], desc="행운을 빕니다"),
    # Row3 food
    dict(slug="p61-strawberry", name="FRESH STRAWBERRY", cat="food", tags="딸기,과일", tone="#C23B4A", bg="#FFFFFF", art="strawberry", layout="product",
         texts=[("FRESH STRAWBERRY", "title", "#B83246"), ("딸기", "mid", "#C45C6E"), ("국내산 100%", "sub", "#D46A7A")], desc="딸기 라벨"),
    dict(slug="p61-orange", name="ORANGE Fresh", cat="food", tags="오렌지,과일", tone="#EF6C00", bg="#FFFFFF", art="orange", layout="product",
         texts=[("ORANGE", "title", "#E65100"), ("신선한 오렌지", "sub", "#FF9800")], desc="오렌지 라벨"),
    dict(slug="p61-apple", name="APPLE 국내산", cat="food", tags="사과,과일", tone="#C62828", bg="#FFFFFF", art="apple", layout="product",
         texts=[("APPLE", "title", "#B71C1C"), ("국내산 사과", "sub", "#E57373")], desc="사과 라벨"),
    dict(slug="p61-tomato", name="TOMATO Farm", cat="food", tags="토마토,채소", tone="#D32F2F", bg="#FFFFFF", art="tomato", layout="product",
         texts=[("TOMATO", "title", "#C62828"), ("FARM FRESH", "sub", "#EF9A9A")], desc="토마토 라벨"),
    dict(slug="p61-lemon", name="LEMON Natural", cat="food", tags="레몬,과일", tone="#F9A825", bg="#FFFDE7", art="lemon", layout="product",
         texts=[("LEMON", "title", "#F57F17"), ("100% NATURAL", "sub", "#FFB300")], desc="레몬 라벨"),
    dict(slug="p61-organic-veg", name="유기농 채소", cat="food", tags="유기농,채소", tone="#558B2F", bg="#E8F5E9", art="veggies", layout="product",
         texts=[("유기농 채소", "title", "#33691E"), ("ORGANIC", "sub", "#7CB342")], desc="유기농 채소"),
    dict(slug="p61-honey", name="HONEY Pure", cat="food", tags="꿀,식품", tone="#C9A227", bg="#FFF8E0", art="honey_dipper", layout="product",
         texts=[("HONEY", "title", "#8A6A10"), ("PURE NATURAL", "sub", "#A08030")], desc="천연 꿀"),
    dict(slug="p61-coffee", name="PREMIUM COFFEE", cat="cafe", tags="커피,원두", tone="#5C3A1E", bg="#FFF8F0", art="coffee", layout="product",
         texts=[("PREMIUM COFFEE", "title", "#3E2723"), ("100% ARABICA", "sub", "#8D6E63")], desc="프리미엄 커피"),
    dict(slug="p61-bakery", name="BAKERY Fresh", cat="cafe", tags="베이커리,빵", tone="#C4873A", bg="#FFF8EE", art="croissant", layout="product",
         texts=[("BAKERY", "title", "#8B5A20"), ("FRESH & DELICIOUS", "sub", "#B07A40")], desc="베이커리"),
    dict(slug="p61-hand-soap", name="HAND SOAP", cat="beauty", tags="핸드솝,리프", tone="#5B8A6A", bg="#F1F7F3", art="leaves_sprig", layout="product",
         texts=[("HAND SOAP", "title", "#2F5A40"), ("NATURAL CARE", "sub", "#5B8A6A")], desc="핸드솝"),
    # Row4 beauty/home/pet
    dict(slug="p61-shampoo", name="SHAMPOO Mild", cat="beauty", tags="샴푸,헤어", tone="#3D6B5A", bg="#F5FAF7", art="leaf", layout="product",
         texts=[("SHAMPOO", "title", "#2A4F42"), ("MILD & PURE", "mid", "#5A8A78"), ("500ml", "sub", "#7AA090")], desc="샴푸"),
    dict(slug="p61-body-wash", name="BODY WASH", cat="beauty", tags="바디워시,라벤더", tone="#7E57C2", bg="#F8F5FC", art="lavender", layout="product",
         texts=[("BODY WASH", "title", "#5E35B1"), ("RELAXING", "mid", "#9575CD"), ("500ml", "sub", "#B39DDB")], desc="바디워시"),
    dict(slug="p61-skin-lotion", name="SKIN LOTION", cat="beauty", tags="로션,스킨", tone="#EC407A", bg="#FFF5F8", art="flower_pink", layout="product",
         texts=[("SKIN LOTION", "title", "#AD1457"), ("MOISTURE", "mid", "#EC407A"), ("200ml", "sub", "#F48FB1")], desc="스킨로션"),
    dict(slug="p61-olive-oil", name="NATURAL OLIVE OIL", cat="food", tags="올리브,오일", tone="#5B7A3A", bg="#F7FAF2", art="olive", layout="product",
         texts=[("NATURAL OLIVE OIL", "title", "#3F5A28"), ("EXTRA VIRGIN 100%", "sub", "#6B8F4E")], desc="올리브오일"),
    dict(slug="p61-hand-cream", name="HAND CREAM", cat="beauty", tags="핸드크림", tone="#8D6E63", bg="#FFF8F0", art="flower_white", layout="product",
         texts=[("HAND CREAM", "title", "#5D4037"), ("MOISTURIZING", "mid", "#8D6E63"), ("50ml", "sub", "#A1887F")], desc="핸드크림"),
    dict(slug="p61-aroma-candle", name="AROMA CANDLE", cat="beauty", tags="캔들,아로마", tone="#8B5E3C", bg="#FFF8F0", art="candle", layout="product",
         texts=[("AROMA CANDLE", "title", "#5C3A1E"), ("Sweet Life", "sub", "#A08060")], desc="아로마 캔들"),
    dict(slug="p61-diffuser", name="DIFFUSER Relax", cat="beauty", tags="디퓨저", tone="#5C6BC0", bg="#F5F7FF", art="diffuser", layout="product",
         texts=[("DIFFUSER", "title", "#3949AB"), ("RELAX", "sub", "#7986CB")], desc="디퓨저"),
    dict(slug="p61-natural-soap", name="NATURAL SOAP", cat="beauty", tags="비누,에코", tone="#43A047", bg="#F1F8F2", art="leaf", layout="product",
         texts=[("NATURAL SOAP", "title", "#2E7D32"), ("ECO FRIENDLY", "sub", "#66BB6A")], desc="내추럴 솝"),
    dict(slug="p61-pet-food", name="PET FOOD", cat="food", tags="반려견,펫", tone="#8B5E3C", bg="#FFF8F0", art="dog", layout="product",
         texts=[("PET FOOD", "title", "#5C3A1E"), ("FOR YOUR PET", "sub", "#A08060")], desc="펫푸드"),
    dict(slug="p61-cat-food", name="CAT FOOD", cat="food", tags="고양이,펫", tone="#6D4C41", bg="#FFF8F0", art="cat", layout="product",
         texts=[("CAT FOOD", "title", "#4E342E"), ("HEALTHY LIFE", "sub", "#8D6E63")], desc="캣푸드"),
    # Row5 shipping
    dict(slug="p61-barcode", name="PRODUCT 바코드", cat="price", tags="바코드,상품", tone="#212121", bg="#FFFFFF", art="barcode", layout="special_barcode",
         texts=[("PRODUCT", "title", "#212121"), ("8801234567890", "sub", "#616161")], desc="상품 바코드"),
    dict(slug="p61-scan-me", name="SCAN ME QR", cat="price", tags="QR,스캔", tone="#212121", bg="#FFFFFF", art="qr", layout="special_qr",
         texts=[("SCAN ME", "title", "#212121"), ("www.labelup.co.kr", "sub", "#757575")], desc="SCAN ME"),
    dict(slug="p61-follow-us", name="Follow Us! QR", cat="price", tags="SNS,QR", tone="#C2185B", bg="#FFFFFF", art="qr", layout="special_qr",
         texts=[("Follow Us!", "title", "#AD1457"), ("@labelup", "sub", "#EC407A")], desc="Follow Us"),
    dict(slug="p61-fragile", name="FRAGILE 취급주의", cat="shipping", tags="파손,취급주의", tone="#C62828", bg="#FFFFFF", art="fragile", layout="icon",
         texts=[("FRAGILE", "title", "#B71C1C"), ("취급주의", "sub", "#E57373")], desc="파손주의"),
    dict(slug="p61-this-side-up", name="THIS SIDE UP", cat="shipping", tags="방향,배송", tone="#212121", bg="#FFFFFF", art="arrows_up", layout="icon",
         texts=[("THIS SIDE UP", "title", "#212121"), ("위로 가세요", "sub", "#757575")], desc="이 면이 위로"),
    dict(slug="p61-keep-dry", name="KEEP DRY", cat="shipping", tags="습기,배송", tone="#1976D2", bg="#FFFFFF", art="umbrella", layout="icon",
         texts=[("KEEP DRY", "title", "#1565C0"), ("습기주의", "sub", "#64B5F6")], desc="습기주의"),
    dict(slug="p61-caution", name="CAUTION 주의", cat="warning", tags="주의,경고", tone="#F9A825", bg="#FFFDE7", art="caution", layout="icon",
         texts=[("CAUTION", "title", "#212121"), ("주의", "sub", "#F57F17")], desc="주의 표시"),
    dict(slug="p61-fast-ship", name="빠른배송", cat="shipping", tags="배송,트럭", tone="#1976D2", bg="#FFFFFF", art="truck", layout="icon",
         texts=[("빠른배송", "title", "#1565C0"), ("안전하게 배송해드립니다", "sub", "#64B5F6")], desc="빠른배송"),
    dict(slug="p61-chilled", name="냉장보관", cat="shipping", tags="냉장,보관", tone="#0288D1", bg="#E1F5FE", art="snowflake", layout="icon",
         texts=[("냉장보관", "title", "#0277BD"), ("0~10°C", "sub", "#4FC3F7")], desc="냉장보관"),
    dict(slug="p61-frozen", name="냉동보관", cat="shipping", tags="냉동,보관", tone="#0277BD", bg="#E3F2FD", art="thermometer", layout="icon",
         texts=[("냉동보관", "title", "#01579B"), ("-18°C 이하", "sub", "#4FC3F7")], desc="냉동보관"),
    # Row6 retail
    dict(slug="p61-best-seller", name="BEST SELLER", cat="event", tags="베스트,왕관", tone="#F9A825", bg="#FFFDE7", art="crown", layout="badge",
         texts=[("BEST SELLER", "title", "#F57F17")], desc="베스트셀러"),
    dict(slug="p61-new", name="NEW 신상품", cat="event", tags="신상품,NEW", tone="#EF6C00", bg="#FFF8F0", art="starburst", layout="badge",
         texts=[("NEW", "title", "#FFFFFF"), ("신상품", "sub", "#E65100")], desc="신상품"),
    dict(slug="p61-sale", name="SALE 50% OFF", cat="price", tags="세일,할인", tone="#C62828", bg="#C62828", art="", layout="special_sale",
         texts=[("SALE", "title", "#FFFFFF"), ("UP TO 50% OFF", "sub", "#FFCDD2")], desc="세일"),
    dict(slug="p61-coupon-20", name="20% 할인쿠폰", cat="price", tags="쿠폰,할인", tone="#2E7D32", bg="#FFFFFF", art="scissors", layout="special_coupon",
         texts=[("20%", "title", "#2E7D32"), ("할인쿠폰", "sub", "#66BB6A")], desc="20% 쿠폰"),
    dict(slug="p61-sample", name="SAMPLE 샘플", cat="warehouse", tags="샘플,재고", tone="#757575", bg="#F5F5F5", art="", layout="special_sample",
         texts=[("SAMPLE", "title", "#424242"), ("샘플상품 · NOT FOR SALE", "sub", "#9E9E9E")], desc="샘플"),
    dict(slug="p61-lot-exp", name="LOT / EXP", cat="warehouse", tags="유통기한,로트", tone="#212121", bg="#FFFFFF", art="", layout="special_lot",
         texts=[("LOT NO.", "title", "#212121"), ("EXP. DATE", "sub", "#616161")], desc="LOT/유통기한"),
    dict(slug="p61-mfg-exp", name="제조일자 / 유통기한", cat="warehouse", tags="제조일자,유통기한", tone="#212121", bg="#FFFFFF", art="", layout="special_dates",
         texts=[("제조일자", "title", "#212121"), ("유통기한", "sub", "#616161")], desc="제조/유통기한"),
    dict(slug="p61-premium", name="PREMIUM QUALITY", cat="event", tags="프리미엄,품질", tone="#C9A227", bg="#FFFDE7", art="premium_seal", layout="center",
         texts=[("PREMIUM QUALITY", "title", "#8A6A10")], desc="프리미엄 퀄리티"),
    dict(slug="p61-eco", name="ECO FRIENDLY", cat="warning", tags="에코,친환경", tone="#43A047", bg="#FFFFFF", art="leaf", layout="icon",
         texts=[("ECO FRIENDLY", "title", "#2E7D32")], desc="에코 프렌들리"),
    dict(slug="p61-recycle", name="재활용", cat="warning", tags="재활용,환경", tone="#43A047", bg="#FFFFFF", art="recycle", layout="icon",
         texts=[("재활용", "title", "#2E7D32"), ("지구를 지켜요", "sub", "#66BB6A")], desc="재활용"),
]


def main() -> None:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    MANIFEST.parent.mkdir(parents=True, exist_ok=True)
    if REF_SRC.is_file():
        shutil.copy2(REF_SRC, REF_DST)
        print(f"reference → {REF_DST}")

    items = []
    for i, row in enumerate(CATALOG, start=1):
        slug = row["slug"]
        art_kind = row.get("art") or ""
        art_file = ""
        if art_kind:
            art = render_art(art_kind, SIZE)
            art_name = f"{slug}_art.png"
            art.save(OUT_DIR / art_name, "PNG", optimize=True)
            art_file = f"/assets/templates/pack61/{art_name}"
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
            "sort_order": 200 + i,
            "paper_w_mm": 50,
            "paper_h_mm": 50,
            "paper_shape": "roundrect",
            "paper_no": f"LU-P61-{i:02d}",
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
