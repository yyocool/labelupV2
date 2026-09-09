#!/usr/bin/env python3
"""Apply HQ AI illustration arts onto pack60 templates (art only, editable text stays in JSON)."""
from __future__ import annotations

import json
import shutil
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "public" / "assets" / "templates" / "pack60"
MANIFEST = ROOT / "storage" / "imports" / "template_pack60_manifest.json"
ASSETS = Path(r"C:\Users\PW1234\.cursor\projects\c-phpstudy-pro-WWW-labelup\assets")

# art kind -> generated file
HQ = {
    "smile": "pack60_art_smile.png",
    "olive": "pack60_art_olive.png",
    "coffee": "pack60_art_coffee.png",
    "bee": "pack60_art_bee.png",
    "strawberry": "pack60_art_strawberry.png",
    "croissant": "pack60_art_croissant.png",
    "heart": "pack60_art_heart.png",
    "cake": "pack60_art_cake.png",
    "leaf": "pack60_art_leaf.png",
    "floral": "pack60_art_floral.png",
    "bow": "pack60_art_bow.png",
    "wreath": "pack60_art_wreath.png",
    "clover": "pack60_art_clover.png",
    "tree_xmas": "pack60_art_tree_xmas.png",
    "snow": "pack60_art_snow.png",
    "pumpkin": "pack60_art_pumpkin.png",
    "apple": "pack60_art_apple.png",
    "tomato": "pack60_art_tomato.png",
    "veggies": "pack60_art_veggies.png",
    "cow": "pack60_art_cow.png",
    "cookie": "pack60_art_cookie.png",
    "macaron": "pack60_art_macaron.png",
    "salad": "pack60_art_salad.png",
    "utensils": "pack60_art_utensils.png",
    "kimchi": "pack60_art_kimchi.png",
    "honey_dipper": "pack60_art_honey_dipper.png",
    "recycle": "pack60_art_recycle.png",
    "fragile": "pack60_art_fragile.png",
    "arrows_up": "pack60_art_arrows_up.png",
    "umbrella": "pack60_art_umbrella.png",
    "caution": "pack60_art_caution.png",
    "hands": "pack60_art_hands.png",
    "truck": "pack60_art_truck.png",
    "envelope": "pack60_art_envelope.png",
    "crown": "pack60_art_crown.png",
    "starburst": "pack60_art_starburst.png",
    "handmade": "pack60_art_handmade.png",
    "laurel_heart": "pack60_art_laurel_heart.png",
}

# slug -> art kind (from catalog)
from generate_template_pack60 import CATALOG  # noqa: E402


def normalize_square(src: Path, dest: Path, size: int = 1024) -> None:
    im = Image.open(src).convert("RGBA")
    # fit into square with transparent/cream pad
    side = max(im.width, im.height)
    canvas = Image.new("RGBA", (side, side), (255, 255, 255, 0))
    canvas.paste(im, ((side - im.width) // 2, (side - im.height) // 2), im)
    out = canvas.resize((size, size), Image.LANCZOS)
    dest.parent.mkdir(parents=True, exist_ok=True)
    out.save(dest, "PNG", optimize=True)


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    kind_by_slug = {row["slug"]: (row.get("art") or "") for row in CATALOG}
    data = json.loads(MANIFEST.read_text(encoding="utf-8"))
    applied = 0
    for item in data["items"]:
        slug = item["slug"]
        kind = kind_by_slug.get(slug, "")
        item.pop("full_image", None)
        # restore layout from catalog if still full_sticker
        for row in CATALOG:
            if row["slug"] == slug:
                item["layout"] = row["layout"]
                item["bg"] = row["bg"]
                item["tone"] = row["tone"]
                item["texts"] = [{"text": t[0], "role": t[1], "color": t[2]} for t in row["texts"]]
                break
        if not kind:
            item["art_url"] = ""
            continue
        hq_name = HQ.get(kind)
        art_name = f"{slug}_art.png"
        dest = OUT / art_name
        if hq_name:
            src = ASSETS / hq_name
            if src.is_file():
                normalize_square(src, dest, 1024)
                applied += 1
            elif not dest.is_file():
                # keep existing PIL art if present
                pil = OUT / art_name
                if not pil.is_file():
                    print("missing", slug, kind)
        item["art_url"] = f"/assets/templates/pack60/{art_name}"
    MANIFEST.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"applied HQ arts: {applied}")
    print("manifest layouts restored")


if __name__ == "__main__":
    main()
