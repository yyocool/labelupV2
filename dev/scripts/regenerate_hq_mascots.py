#!/usr/bin/env python3
"""Regenerate all character + friends cliparts with high-quality renderer."""
from __future__ import annotations

import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(Path(__file__).resolve().parent))

from lib_mascot_hq import (  # noqa: E402
    CHAR_POSES,
    CHARS_V1,
    FRIEND_POSES,
    FRIENDS,
    render_mascot,
)

OUT_DIR = ROOT / "public" / "assets" / "cliparts"
IMPORTS = ROOT / "storage" / "imports"


def regen_pack(
    chars: dict,
    poses: list[tuple[str, str]],
    prefix: str,
    manifest_name: str,
    sort_base: int,
) -> dict:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    IMPORTS.mkdir(parents=True, exist_ok=True)
    items = []
    n = 0
    written = 0
    for key, meta in chars.items():
        for pose_id, pose_name in poses:
            n += 1
            fname = f"{prefix}_{n:03d}_{key}_{pose_id}.png"
            path = OUT_DIR / fname
            rel = f"/assets/cliparts/{fname}"
            img = render_mascot(meta, pose_id, 768)
            img.save(path, "PNG", optimize=True)
            written += 1
            print(f"[ok] {fname}", flush=True)
            items.append({
                "title": f"{meta['name']} · {pose_name}",
                "category_slug": "character",
                "image_path": rel,
                "hashtags": f"#캐릭터 #프렌즈 #귀여운 #스티커 #라벨 #클립아트 #{meta['name']} #{pose_id}",
                "description": f"{meta['name']} 고품질 · {pose_name}",
                "sort_order": sort_base + n,
            })
    manifest_path = IMPORTS / manifest_name
    manifest_path.write_text(
        json.dumps({"count": len(items), "items": items}, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    return {"written": written, "total": len(items), "manifest": str(manifest_path)}


def main() -> int:
    # Keep existing filename schemes so DB image_path rows update in place
    r1 = regen_pack(CHARS_V1, CHAR_POSES, "hq_char", "clipart_character_manifest.json", 3000)
    r2 = regen_pack(FRIENDS, FRIEND_POSES, "hq_friend", "clipart_friends_manifest.json", 4000)
    print(json.dumps({"chars": r1, "friends": r2}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
