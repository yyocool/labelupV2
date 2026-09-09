#!/usr/bin/env python3
import json
import shutil
from pathlib import Path

OUT = Path(__file__).resolve().parents[1] / "public" / "assets" / "templates" / "pack60"
MAN = Path(__file__).resolve().parents[1] / "storage" / "imports" / "template_pack60_manifest.json"
data = json.loads(MAN.read_text(encoding="utf-8"))
for i, item in enumerate(data["items"], 1):
    src = OUT / f"{item['slug']}_full.png"
    name = f"p{i:02d}.png"
    dst = OUT / name
    if src.is_file():
        shutil.copyfile(src, dst)
    item["art_url"] = f"/assets/templates/pack60/{name}"
    item["layout"] = "full_sticker"
MAN.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
print("ok", len(data["items"]), data["items"][46]["art_url"])
