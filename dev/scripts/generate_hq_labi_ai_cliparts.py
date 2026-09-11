#!/usr/bin/env python3
"""Generate 100 high-quality LABI cliparts via OpenAI gpt-image-1 edits.

Uses the official character sheet as reference (input_fidelity=high) so glossy
3D style stays consistent. Transparent PNG output.

Outputs:
  public/assets/cliparts/hq_labi_{NNN}_{id}.png
  storage/imports/clipart_labi_manifest.json
"""
from __future__ import annotations

import base64
import json
import os
import time
import urllib3
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path

import requests
from PIL import Image

urllib3.disable_warnings()

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "public" / "assets" / "cliparts"
MANIFEST = ROOT / "storage" / "imports" / "clipart_labi_manifest.json"
SHEET = ROOT / "storage" / "imports" / "labi_character_sheet.png"
HERO = ROOT / "storage" / "imports" / "labi_ai_test_happy.png"
CATEGORY = "labi"
MODEL = "gpt-image-1"
QUALITY = os.environ.get("LABI_IMAGE_QUALITY", "high")  # low|medium|high
WORKERS = int(os.environ.get("LABI_WORKERS", "3"))
SIZE_OUT = 768

STYLE = (
    "Using LABI from the reference images EXACTLY, create ONE isolated sticker clipart "
    "on a fully transparent background. LABI is a glossy 3D thick rounded-rectangle label mascot: "
    "deep maroon/burgundy #873448 frame, white recessed face screen, bottom-right corner peeled "
    "like a sticker revealing cream/white underside, small maroon upward chevron antenna on top. "
    "CRITICAL: NO mouth ever — expressions ONLY through eye shapes. Soft studio lighting, "
    "plastic gloss highlights, premium 3D render matching references. Centered, no text, "
    "no watermark, no extra unrelated characters."
)

# (id, Korean title, pose detail for prompt)
ITEMS: list[tuple[str, str, str]] = [
    ("normal", "기본 표정", "Front view, calm vertical oval eyes."),
    ("happy", "방긋 미소", "Front view, happy curved closed eyes."),
    ("wink", "윙크", "Front view, left eye oval, right eye happy curve wink."),
    ("surprise", "깜짝", "Front view, large round surprised eyes."),
    ("think", "생각중", "Slightly tilted, thinking upward oval eyes, small thought dots nearby."),
    ("sleepy", "졸린", "Front view, sleepy horizontal line eyes, soft mood."),
    ("love", "하트눈", "Front view, heart-shaped maroon eyes, tiny floating hearts nearby."),
    ("cry", "울먹", "Front view, oval eyes with small blue tear drops."),
    ("angry", "화남", "Front view, angry slanted oval eyes, tiny frustration marks."),
    ("shy", "수줍", "Slight tilt, shy small oval eyes with soft pink blush cheeks."),
    ("dizzy", "어질어질", "Front view, spiral dizzy eyes, slight wobble."),
    ("sparkle", "반짝", "Happy curved eyes with gold sparkles around head."),
    ("hello", "안녕", "Tilted left, happy eyes, friendly greeting pose."),
    ("wave", "손인사", "Tilted right with motion lines, wink eyes greeting."),
    ("cheer", "만세", "Happy eyes, celebratory floating stars."),
    ("jump", "폴짝", "Slightly airborne with motion lines, happy eyes."),
    ("dance", "댄스", "Strongly tilted with motion lines, happy eyes dancing."),
    ("tilt_left", "기울기 왼쪽", "Body tilted left ~15°, calm oval eyes."),
    ("tilt_right", "기울기 오른쪽", "Body tilted right ~15°, calm oval eyes."),
    ("back", "뒷모습", "Back view: solid glossy maroon rounded rectangle with chevron on top, no face."),
    ("laptop", "노트북", "Beside a sleek gray laptop, calm oval eyes focusing."),
    ("coding", "코딩중", "With laptop, thinking upward eyes."),
    ("headset", "헤드셋", "Wearing maroon/dark headset with mic, calm oval eyes."),
    ("cs", "상담중", "Headset + speech bubble with dots, happy curved eyes."),
    ("bulb", "아이디어", "Next to glowing yellow lightbulb, surprised round eyes."),
    ("idea_happy", "번쩍 아이디어", "Glowing lightbulb, happy eyes, sparkles."),
    ("search", "검색", "Holding magnifying glass, thinking eyes."),
    ("inspect", "꼼꼼 확인", "Magnifying glass over label sheets, focused oval eyes."),
    ("labels", "라벨 뭉치", "Holding stacked circular/rectangle label sheets, happy eyes."),
    ("print", "출력중", "Standing next to small white desktop label printer, calm eyes."),
    ("print_happy", "출력 완료", "Printer outputting labels, happy eyes."),
    ("coffee", "커피타임", "Small coffee cup nearby, happy eyes."),
    ("phone", "전화중", "Holding smartphone, calm oval eyes."),
    ("call_wink", "전화 윙크", "Phone + wink eyes."),
    ("bag", "쇼핑백", "Holding shopping bag, happy eyes."),
    ("shop", "쇼핑", "Shopping bag with motion lines, wink eyes."),
    ("star", "별점", "Golden star prop, happy eyes."),
    ("star_love", "별사랑", "Star + heart eyes."),
    ("paint", "페인팅", "Paintbrush prop, happy eyes."),
    ("design", "디자인중", "Paintbrush, thinking eyes."),
    ("scissors", "가위질", "Scissors prop, calm eyes."),
    ("cut", "재단", "Scissors, wink eyes."),
    ("barcode", "바코드", "Barcode label card, calm eyes."),
    ("scan", "스캔", "Barcode card, thinking eyes."),
    ("gift", "선물", "Gift box with ribbon, love heart eyes."),
    ("gift_surprise", "깜짝선물", "Gift box, surprised eyes."),
    ("book", "독서", "Open book, thinking eyes."),
    ("study", "공부", "Book, calm focused eyes."),
    ("camera", "촬영", "Camera prop, happy eyes."),
    ("selfie", "셀카", "Camera, wink eyes, motion lines."),
    ("ok", "OK", "Green check badge nearby, happy eyes."),
    ("done", "완료", "Check badge, wink eyes."),
    ("speech", "말풍선", "White speech bubble with dots, calm eyes."),
    ("chat", "수다", "Speech bubble, happy eyes."),
    ("sleep", "쿨쿨", "Zzz marks, sleepy line eyes, tilted."),
    ("nap", "낮잠", "Zzz marks, sleepy eyes."),
    ("heart", "하트", "Floating maroon heart, happy eyes."),
    ("heart_shy", "두근", "Heart + shy blush eyes."),
    ("blush", "발그레", "Soft blush, shy small oval eyes, slight tilt."),
    ("proud", "뿌듯", "Proud happy eyes, slight tilt, tiny sparkles."),
    ("curious", "궁금", "Curious thinking eyes, tilt."),
    ("focus", "집중", "Magnifier, intense thinking eyes."),
    ("ai_work", "AI 작업", "Laptop with motion lines, calm working eyes."),
    ("support", "고객지원", "Headset + speech bubble, happy helpful eyes."),
    ("inspire", "영감", "Lightbulb + sparkles, sparkle eyes."),
    ("ship_label", "배송라벨", "Stack of shipping labels, calm eyes, slight tilt."),
    ("pack", "포장", "Gift box packaging, happy eyes."),
    ("celebrate", "축하", "Stars and confetti, happy eyes, tilted."),
    ("party", "파티", "Party sparkles and stars, sparkle eyes."),
    ("coffee_think", "커피 생각", "Coffee cup, thinking eyes."),
    ("phone_surprise", "전화 놀람", "Phone, surprised eyes."),
    ("bag_love", "쇼핑 러브", "Shopping bag, heart eyes."),
    ("paint_shy", "그림 수줍", "Paintbrush, shy blush eyes."),
    ("book_sleep", "책보다 졸림", "Book + sleepy eyes."),
    ("camera_surprise", "찰칵 놀람", "Camera, surprised eyes."),
    ("check_proud", "검수 OK", "Green check, proud happy eyes."),
    ("speech_think", "음…", "Speech bubble, thinking eyes."),
    ("back_motion", "뒤돌아 신남", "Back view maroon body + chevron with motion lines."),
    ("side_tilt", "살짝 옆", "Strong 3/4 side angle, wink eyes, peel corner visible."),
    ("float", "둥실", "Floating with soft motion lines, calm oval eyes."),
    ("cry_comfort", "위로", "Tearful eyes with comforting heart nearby."),
    ("angry_work", "화난 작업", "Angry eyes next to laptop."),
    ("love_gift", "러브 선물", "Gift + heart eyes."),
    ("labels_wink", "라벨 윙크", "Label sheets, wink eyes."),
    ("print_think", "출력 고민", "Printer, thinking eyes."),
    ("scissors_happy", "가위 신남", "Scissors, happy eyes."),
    ("barcode_ok", "바코드 OK", "Barcode + check badge, happy eyes."),
    ("star_wink", "별 윙크", "Star, wink eyes."),
    ("bulb_think", "아이디어 생각", "Lightbulb, thinking eyes."),
    ("headset_sleepy", "상담 졸림", "Headset, sleepy eyes."),
    ("laptop_happy", "노트북 신남", "Laptop, happy eyes."),
    ("bag_surprise", "쇼핑 놀람", "Shopping bag, surprised eyes."),
    ("phone_love", "전화 러브", "Phone, heart eyes."),
    ("magnifier_surprise", "돋보기 놀람", "Magnifier, surprised eyes."),
    ("coffee_wink", "커피 윙크", "Coffee, wink eyes."),
    ("book_happy", "독서 신남", "Book, happy eyes."),
    ("camera_love", "촬영 러브", "Camera, heart eyes."),
    ("check_wink", "체크 윙크", "Check badge, wink eyes."),
    ("speech_surprise", "말풍선 놀람", "Speech bubble, surprised eyes."),
    ("zzz_tilt", "기우뚱 졸림", "Zzz, sleepy eyes, strong tilt."),
]


def load_api_key() -> str:
    env = ROOT / ".env"
    if env.is_file():
        for line in env.read_text(encoding="utf-8").splitlines():
            if line.startswith("OPENAI_API_KEY="):
                return line.split("=", 1)[1].strip().strip('"').strip("'")
    key = os.environ.get("OPENAI_API_KEY", "").strip()
    if not key:
        raise SystemExit("OPENAI_API_KEY missing")
    return key


def prompt_for(detail: str) -> str:
    return f"{STYLE} Pose/expression for this sticker: {detail}"


def generate_one(key: str, sheet_bytes: bytes, hero_bytes: bytes, detail: str) -> bytes:
    files = [
        ("image[]", ("sheet.png", sheet_bytes, "image/png")),
        ("image[]", ("hero.png", hero_bytes, "image/png")),
    ]
    data = {
        "model": MODEL,
        "prompt": prompt_for(detail),
        "size": "1024x1024",
        "quality": QUALITY,
        "background": "transparent",
        "output_format": "png",
        "input_fidelity": "high",
        "n": "1",
    }
    last_err = None
    for attempt in range(4):
        try:
            r = requests.post(
                "https://api.openai.com/v1/images/edits",
                headers={"Authorization": f"Bearer {key}"},
                files=files,
                data=data,
                timeout=300,
                verify=False,
            )
            if r.status_code == 200:
                return base64.b64decode(r.json()["data"][0]["b64_json"])
            last_err = f"{r.status_code}: {r.text[:400]}"
            if r.status_code in (429, 500, 502, 503):
                time.sleep(5 * (attempt + 1))
                continue
            break
        except Exception as e:
            last_err = str(e)
            time.sleep(3 * (attempt + 1))
    raise RuntimeError(last_err or "generate failed")


def postprocess(png_bytes: bytes, dest: Path) -> None:
    im = Image.open(io_bytes := __import__("io").BytesIO(png_bytes)).convert("RGBA")
    # trim near-empty margins lightly then fit to SIZE_OUT square
    alpha = im.getchannel("A")
    bbox = alpha.getbbox()
    if bbox:
        im = im.crop(bbox)
    # pad to square
    w, h = im.size
    side = max(w, h)
    canvas = Image.new("RGBA", (side, side), (0, 0, 0, 0))
    canvas.paste(im, ((side - w) // 2, (side - h) // 2), im)
    out = canvas.resize((SIZE_OUT, SIZE_OUT), Image.LANCZOS)
    # ensure some transparency exists
    amin, _ = out.getchannel("A").getextrema()
    if amin == 255:
        # force corner transparent if model failed transparency
        px = out.load()
        for y in range(8):
            for x in range(8):
                px[x, y] = (0, 0, 0, 0)
    out.save(dest, "PNG", optimize=True)


def main() -> int:
    assert SHEET.is_file(), f"missing {SHEET}"
    assert len(ITEMS) == 100, f"need 100 items, got {len(ITEMS)}"
    if not HERO.is_file():
        raise SystemExit(f"missing hero ref {HERO} — run a single test first")

    key = load_api_key()
    sheet_bytes = SHEET.read_bytes()
    hero_bytes = HERO.read_bytes()
    OUT_DIR.mkdir(parents=True, exist_ok=True)

    jobs = []
    for i, (pid, title, detail) in enumerate(ITEMS, 1):
        fname = f"hq_labi_{i:03d}_{pid}.png"
        path = OUT_DIR / fname
        # skip only if already AI-quality size (~>80KB typically) AND env SKIP set
        if path.is_file() and path.stat().st_size > 90000 and os.environ.get("LABI_RESUME") == "1":
            print(f"[{i}/100] skip {fname}")
            continue
        jobs.append((i, pid, title, detail, fname, path))

    print(f"Generating {len(jobs)} / 100  quality={QUALITY} workers={WORKERS}")

    def work(job):
        i, pid, title, detail, fname, path = job
        raw = generate_one(key, sheet_bytes, hero_bytes, detail)
        postprocess(raw, path)
        return i, fname, title, path.stat().st_size

    errors = []
    with ThreadPoolExecutor(max_workers=WORKERS) as ex:
        futs = {ex.submit(work, j): j for j in jobs}
        for fut in as_completed(futs):
            j = futs[fut]
            try:
                i, fname, title, sz = fut.result()
                print(f"[{i}/100] {fname} · {title} ({sz} bytes)")
            except Exception as e:
                errors.append((j[4], str(e)))
                print(f"FAIL {j[4]}: {e}")

    if errors:
        print(f"{len(errors)} failures — re-run with LABI_RESUME=1")

    manifest_items = []
    for i, (pid, title, _detail) in enumerate(ITEMS, 1):
        fname = f"hq_labi_{i:03d}_{pid}.png"
        path = OUT_DIR / fname
        if not path.is_file():
            continue
        manifest_items.append({
            "title": f"라비 · {title}",
            "category_slug": CATEGORY,
            "image_path": f"/assets/cliparts/{fname}",
            "hashtags": f"#라비 #LABI #캐릭터 #마스코트 #라벨업 #클립아트 #{pid}",
            "description": f"라벨업 AI 마스코트 라비 — {title}",
            "sort_order": 9600 + i,
        })
    MANIFEST.write_text(json.dumps({"items": manifest_items}, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Wrote manifest {len(manifest_items)} → {MANIFEST}")
    return 0 if not errors else 1


if __name__ == "__main__":
    raise SystemExit(main())
