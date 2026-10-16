#!/usr/bin/env python3
"""
publicdomainvectors.org SVG 클립아트를 카테고리별로 받아
public/assets/cliparts + clipart_pdv_manifest.json 으로 만든다.

사용:
  python scripts/import_pdv_cliparts.py                  # 전 카테고리
  python scripts/import_pdv_cliparts.py --cat animals    # 동물만
  python scripts/import_pdv_cliparts.py --max 200        # 테스트용 제한
  python scripts/import_pdv_cliparts.py --workers 6
"""
from __future__ import annotations

import argparse
import json
import re
import ssl
import sys
import threading
import time
import urllib.error
import urllib.request
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path

try:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    sys.stderr.reconfigure(encoding="utf-8", errors="replace")
except Exception:
    pass

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "cliparts"
MANIFEST = ROOT / "storage" / "imports" / "clipart_pdv_manifest.json"
PROGRESS = ROOT / "storage" / "imports" / "pdv_progress.json"

BASE = "https://publicdomainvectors.org"
UA = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
)
CTX = ssl.create_default_context()
LOCK = threading.Lock()

# PDV 카테고리 slug → LabelUp category_slug
CATEGORY_MAP = {
    "animals": "animal",
    "nature": "nature",
    "food-and-drink": "food",
    "business": "business",
    "signs-symbols": "shipping",
    "transportation": "traffic",
    "people": "character",
    "sports": "character",
    "backgrounds": "pattern",
    "architecture": "shape",
    "education": "kids",
    "flags": "shape",
    "health-medical": "beauty",
    "objects": "shape",
}

DEFAULT_CATS = list(CATEGORY_MAP.keys())


def fetch(url: str, referer: str | None = None, retries: int = 3) -> bytes:
    headers = {
        "User-Agent": UA,
        "Accept": "*/*",
        "Accept-Language": "en-US,en;q=0.9",
    }
    if referer:
        headers["Referer"] = referer
    last: Exception | None = None
    for attempt in range(retries):
        try:
            req = urllib.request.Request(url, headers=headers)
            with urllib.request.urlopen(req, context=CTX, timeout=90) as res:
                return res.read()
        except Exception as ex:  # noqa: BLE001
            last = ex
            time.sleep(1.2 * (attempt + 1))
    assert last is not None
    raise last


def fetch_text(url: str, referer: str | None = None) -> str:
    return fetch(url, referer=referer).decode("utf-8", "replace")


def load_json(path: Path, default):
    if not path.exists():
        return default
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except Exception:  # noqa: BLE001
        return default


def save_json(path: Path, data) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    tmp = path.with_suffix(path.suffix + ".tmp")
    payload = json.dumps(data, ensure_ascii=False, indent=2)
    tmp.write_text(payload, encoding="utf-8")
    last: Exception | None = None
    for attempt in range(8):
        try:
            tmp.replace(path)
            return
        except PermissionError as ex:
            last = ex
            time.sleep(0.4 * (attempt + 1))
        except OSError as ex:
            last = ex
            time.sleep(0.4 * (attempt + 1))
    # 마지막 수단: 직접 덮어쓰기 (다른 프로세스가 tmp→본문 replace를 잠근 경우)
    try:
        path.write_text(payload, encoding="utf-8")
        try:
            tmp.unlink(missing_ok=True)
        except OSError:
            pass
        return
    except Exception as ex:  # noqa: BLE001
        if last is not None:
            raise last from ex
        raise


def title_from_slug(slug: str) -> str:
    return re.sub(r"[-_]+", " ", slug).strip()


def list_page_items(cat: str, page: int) -> tuple[list[tuple[str, str]], int | None]:
    url = f"{BASE}/en/free-clipart/{cat}?type=svg&page={page}"
    html = fetch_text(url)
    items = sorted(set(re.findall(r"/en/free-clipart/([^\"'/]+)/(\d+)\.html", html)))
    total = None
    m = re.search(r"([\d,]+)\s*Free Results", html)
    if m:
        total = int(m.group(1).replace(",", ""))
    return items, total


def download_one(cat: str, slug: str, item_id: str, sort_order: int) -> dict | None:
    label_cat = CATEGORY_MAP.get(cat, "shape")
    out_name = f"pdv_{cat.replace('-', '_')}_{item_id}.svg"
    out_path = OUT_DIR / out_name
    public_path = f"/assets/cliparts/{out_name}"
    detail = f"{BASE}/en/free-clipart/{slug}/{item_id}.html"
    title = title_from_slug(slug)

    if out_path.exists() and out_path.stat().st_size > 200:
        return {
            "title": title,
            "category_slug": label_cat,
            "image_path": public_path,
            "hashtags": f"#동물 #클립아트 #퍼블릭도메인 #pdv #{cat} #{title.replace(' ', '')}",
            "description": f"Public Domain Vectors · {cat} · {title} (CC0/Public Domain)",
            "sort_order": sort_order,
            "source_url": detail,
            "pdv_id": item_id,
            "pdv_category": cat,
        }

    try:
        body = fetch(f"{BASE}/download/{item_id}", referer=detail)
    except urllib.error.HTTPError as ex:
        if ex.code in (403, 404, 429):
            return None
        raise

    head = body[:120].lstrip().lower()
    if body[:2] == b"PK":
        # zip — AI/EPS 묶음은 스킵 (type=svg 필터면 거의 없음)
        return None
    if b"<html" in head:
        return None
    if not (head.startswith(b"<?xml") or head.startswith(b"<svg") or b"<svg" in body[:800].lower()):
        return None
    # 비정상 대용량 SVG(임베드 비트맵 등)는 원격 할당량을 잠식하므로 제외
    if len(body) > 5 * 1024 * 1024:
        return None

    OUT_DIR.mkdir(parents=True, exist_ok=True)
    out_path.write_bytes(body)

    tags = [
        "#클립아트",
        "#퍼블릭도메인",
        "#pdv",
        f"#{cat}",
        f"#{title.replace(' ', '')[:24]}",
    ]
    if label_cat == "animal":
        tags.insert(0, "#동물")
    elif label_cat == "food":
        tags.insert(0, "#식품")
    elif label_cat == "nature":
        tags.insert(0, "#자연")

    return {
        "title": title,
        "category_slug": label_cat,
        "image_path": public_path,
        "hashtags": " ".join(tags),
        "description": f"Public Domain Vectors · {cat} · {title} (CC0/Public Domain)",
        "sort_order": sort_order,
        "source_url": detail,
        "pdv_id": item_id,
        "pdv_category": cat,
    }


def crawl_category(cat: str, max_items: int | None, workers: int, progress: dict, manifest_items: dict) -> int:
    added = 0
    page = 1
    total_reported = None
    seen_ids: set[str] = set(progress.get("done_ids", {}).get(cat, []))

    while True:
        try:
            items, total = list_page_items(cat, page)
        except Exception as ex:  # noqa: BLE001
            print(f"[{cat}] page {page} list fail: {ex}")
            break
        if total_reported is None and total is not None:
            total_reported = total
            print(f"[{cat}] Free SVG results ~ {total}")
        if not items:
            break

        todo = [(slug, iid) for slug, iid in items if iid not in seen_ids]
        if max_items is not None:
            remain = max_items - added
            if remain <= 0:
                break
            todo = todo[:remain]

        if todo:
            with ThreadPoolExecutor(max_workers=workers) as pool:
                futs = {
                    pool.submit(download_one, cat, slug, iid, 100000 + int(iid)): iid
                    for slug, iid in todo
                }
                for fut in as_completed(futs):
                    iid = futs[fut]
                    try:
                        row = fut.result()
                    except Exception as ex:  # noqa: BLE001
                        print(f"[{cat}] id={iid} error: {ex}")
                        continue
                    if not row:
                        continue
                    with LOCK:
                        manifest_items[row["image_path"]] = row
                        seen_ids.add(iid)
                        added += 1
                        if added % 25 == 0:
                            progress.setdefault("done_ids", {})[cat] = sorted(seen_ids)
                            save_json(PROGRESS, progress)
                            save_json(
                                MANIFEST,
                                {
                                    "count": len(manifest_items),
                                    "source": "https://publicdomainvectors.org/",
                                    "items": list(manifest_items.values()),
                                },
                            )
                            print(f"[{cat}] saved {added} (+page {page})", flush=True)

        # stop conditions
        if max_items is not None and added >= max_items:
            break
        if len(items) < 40:
            break
        page += 1
        if page > 500:
            break
        time.sleep(0.35)

    progress.setdefault("done_ids", {})[cat] = sorted(seen_ids)
    save_json(PROGRESS, progress)
    save_json(
        MANIFEST,
        {
            "count": len(manifest_items),
            "source": "https://publicdomainvectors.org/",
            "items": list(manifest_items.values()),
        },
    )
    print(f"[{cat}] done +{added} (unique files tracked for cat: {len(seen_ids)})")
    return added


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--cat", action="append", dest="cats", help="PDV category slug (repeatable)")
    ap.add_argument("--max", type=int, default=None, help="Max new downloads per category")
    ap.add_argument("--workers", type=int, default=6)
    args = ap.parse_args()

    cats = args.cats or DEFAULT_CATS
    progress = load_json(PROGRESS, {"done_ids": {}})
    manifest = load_json(MANIFEST, {"items": []})
    manifest_items = {
        str(it.get("image_path")): it
        for it in (manifest.get("items") or [])
        if isinstance(it, dict) and it.get("image_path")
    }

    print(f"categories={cats} workers={args.workers} existing_manifest={len(manifest_items)}")
    total_new = 0
    for cat in cats:
        if cat not in CATEGORY_MAP:
            print(f"skip unknown cat mapping: {cat}")
            continue
        total_new += crawl_category(cat, args.max, args.workers, progress, manifest_items)

    print(f"ALL DONE new_downloads≈{total_new} manifest={len(manifest_items)} -> {MANIFEST}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
