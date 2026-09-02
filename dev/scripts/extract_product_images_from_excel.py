#!/usr/bin/env python3
"""Extract product spec images from Excel column B and save import manifest."""
from __future__ import annotations

import json
import re
import sys
from pathlib import Path

try:
    import openpyxl
except ImportError:
    import subprocess

    subprocess.check_call([sys.executable, "-m", "pip", "install", "openpyxl", "-q"])
    import openpyxl

SKU_COL = 3
DATA_START_ROW = 4


def clean_sku(value) -> str:
    if value is None:
        return ""
    if isinstance(value, float) and value.is_integer():
        value = int(value)
    return str(value).strip()


def image_anchor_row(img) -> int | None:
    anchor = img.anchor
    if hasattr(anchor, "_from"):
        return int(anchor._from.row) + 1
    return None


def detect_ext(data: bytes) -> str:
    if data[:3] == b"\xff\xd8\xff":
        return "jpg"
    if data[:4] == b"\x89PNG":
        return "png"
    if data[:6] in (b"GIF87a", b"GIF89a"):
        return "gif"
    if len(data) >= 12 and data[:4] == b"RIFF" and data[8:12] == b"WEBP":
        return "webp"
    return "png"


def main() -> int:
    root = Path(__file__).resolve().parents[1]
    default_xlsx = root / "storage" / "imports" / "labelup-products-schedule.xlsx"
    xlsx = Path(sys.argv[1]) if len(sys.argv) > 1 else default_xlsx
    out_dir = root / "public" / "assets" / "products"
    manifest_path = root / "storage" / "imports" / "product_images_manifest.json"

    if not xlsx.exists():
        print(f"Excel not found: {xlsx}", file=sys.stderr)
        return 1

    out_dir.mkdir(parents=True, exist_ok=True)
    wb = openpyxl.load_workbook(xlsx)
    ws = wb.active

    manifest: list[dict] = []
    for img in ws._images:
        row = image_anchor_row(img)
        if row is None or row < DATA_START_ROW:
            continue
        sku = clean_sku(ws.cell(row, SKU_COL).value)
        if not sku:
            continue

        data = img._data()
        ext = detect_ext(data)
        safe_sku = re.sub(r"[^\w\-]+", "_", sku)[:80]
        filename = f"spec_{safe_sku}.{ext}"
        dest = out_dir / filename
        dest.write_bytes(data)

        manifest.append(
            {
                "row": row,
                "sku": sku,
                "image_path": f"/assets/products/{filename}",
            }
        )

    manifest.sort(key=lambda item: item["row"])
    manifest_path.write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Extracted {len(manifest)} images -> {out_dir}")
    print(f"Manifest -> {manifest_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
