#!/usr/bin/env python3
"""Post-process AI traffic cliparts → transparent 768 PNG + manifest."""
from __future__ import annotations

import json
from pathlib import Path

from PIL import Image, ImageFilter

ROOT = Path(__file__).resolve().parents[1]
SRC = Path(r"C:\Users\PW1234\.cursor\projects\c-phpstudy-pro-WWW-labelup\assets")
OUT = ROOT / "public" / "assets" / "cliparts"
MANIFEST = ROOT / "storage" / "imports" / "clipart_traffic_manifest.json"
SIZE = 768

ITEMS = [
    ("car_blue", "파란 승용차", "cute blue passenger car side view"),
    ("car_red", "빨간 승용차", "cute red passenger car side view"),
    ("car_green", "초록 승용차", "cute green passenger car side view"),
    ("taxi", "택시", "cute yellow taxi cab with taxi sign side view"),
    ("bus_green", "시내버스", "cute green city bus side view"),
    ("bus_blue", "광역버스", "cute blue coach bus side view"),
    ("truck", "트럭", "cute delivery truck side view"),
    ("van", "밴", "cute white cargo van side view"),
    ("motorcycle", "오토바이", "cute scooter motorcycle side view"),
    ("bicycle", "자전거", "cute bicycle side view"),
    ("scooter", "킥보드", "cute electric kick scooter"),
    ("ambulance", "앰뷸런스", "cute ambulance emergency vehicle side view"),
    ("police", "경찰차", "cute police car side view"),
    ("firetruck", "소방차", "cute red fire truck side view"),
    ("train", "기차", "cute passenger train locomotive side view"),
    ("subway", "지하철", "cute subway metro train side view"),
    ("airplane", "비행기", "cute small airplane side view"),
    ("helicopter", "헬리콥터", "cute helicopter side view"),
    ("ship", "배", "cute cargo ship side view"),
    ("sailboat", "요트", "cute sailboat side view"),
    ("traffic_light", "신호등", "cute traffic light with red yellow green lamps"),
    ("stop", "정지 표지", "cute red octagon stop road sign"),
    ("yield", "양보 표지", "cute yield give-way triangular road sign"),
    ("speed30", "제한속도 30", "cute circular speed limit 30 road sign"),
    ("speed50", "제한속도 50", "cute circular speed limit 50 road sign"),
    ("speed80", "제한속도 80", "cute circular speed limit 80 road sign"),
    ("no_entry", "진입금지", "cute no entry road sign"),
    ("parking", "주차 표지", "cute blue parking P road sign"),
    ("pedestrian", "보행자 표지", "cute pedestrian crossing road sign"),
    ("bikelane", "자전거도로", "cute bicycle lane road sign"),
    ("oneway", "일방통행", "cute one way arrow road sign"),
    ("crosswalk", "횡단보도", "cute zebra crosswalk road marking icon"),
    ("cone", "안전삼각콘", "cute orange traffic safety cone"),
    ("barrier", "공사 바리케이드", "cute construction road barrier"),
    ("gas", "주유소", "cute gas fuel pump station icon"),
    ("evcharge", "전기차 충전", "cute electric vehicle charging station"),
    ("meter", "주차미터기", "cute parking meter"),
    ("toll", "톨게이트", "cute highway toll gate booth"),
    ("bridge", "다리", "cute bridge icon"),
    ("tunnel", "터널", "cute road tunnel entrance icon"),
    ("camera", "교통카메라", "cute traffic speed camera"),
    ("pin", "위치핀", "cute map location pin marker"),
    ("compass", "나침반", "cute navigation compass"),
    ("steering", "핸들", "cute car steering wheel"),
    ("keyfob", "스마트키", "cute car smart key fob"),
    ("tire", "타이어", "cute car tire wheel"),
    ("helmet", "헬멧", "cute motorcycle helmet"),
    ("seatbelt", "안전벨트", "cute seatbelt safety icon"),
    ("airbag", "에어백", "cute car airbag icon"),
    ("map", "경로지도", "cute GPS route map icon"),
    ("lot", "주차장", "cute parking lot icon"),
    ("jam", "교통체증", "cute traffic jam cars icon"),
    ("roundabout", "로터리", "cute roundabout traffic circle icon"),
    ("overpass", "고가도로", "cute highway overpass icon"),
    ("railcross", "건널목", "cute railway crossing barrier icon"),
    ("lighthouse", "등대", "cute lighthouse icon"),
    ("balloon", "열기구", "cute hot air balloon"),
    ("skateboard", "스케이트보드", "cute skateboard"),
    ("rollerskate", "롤러스케이트", "cute roller skate"),
    ("cablecar", "케이블카", "cute cable car gondola"),
    ("monorail", "모노레일", "cute monorail train"),
    ("ferry", "페리", "cute ferry boat"),
    ("rocket", "로켓", "cute rocket ship"),
    ("ufo", "UFO", "cute UFO flying saucer"),
    ("delivery", "배달스쿠터", "cute food delivery scooter with box"),
    ("containership", "컨테이너선", "cute container cargo ship"),
    ("tow", "견인차", "cute tow truck"),
    ("forklift", "지게차", "cute forklift"),
    ("tractor", "트랙터", "cute farm tractor"),
    ("bulldozer", "불도저", "cute bulldozer"),
    ("crane", "크레인", "cute construction crane"),
    ("speedbump", "과속방지턱", "cute road speed bump"),
    ("manhole", "맨홀", "cute manhole cover"),
    ("busstop", "버스정류장", "cute bus stop sign shelter"),
    ("taxistand", "택시승강장", "cute taxi stand sign"),
    ("car_orange", "주황 승용차", "cute orange passenger car side view"),
    ("car_purple", "보라 승용차", "cute purple passenger car side view"),
    ("car_pink", "분홍 승용차", "cute pink passenger car side view"),
    ("car_gray", "회색 승용차", "cute gray passenger car side view"),
    ("truck_blue", "파란 트럭", "cute blue delivery truck side view"),
    ("truck_green", "초록 트럭", "cute green delivery truck side view"),
    ("van_red", "빨간 밴", "cute red cargo van side view"),
    ("van_yellow", "노란 밴", "cute yellow cargo van side view"),
    ("bike_red", "빨간 자전거", "cute red bicycle side view"),
    ("bike_orange", "주황 자전거", "cute orange bicycle side view"),
    ("scooter_blue", "파란 킥보드", "cute blue electric kick scooter"),
    ("scooter_green", "초록 킥보드", "cute green electric kick scooter"),
    ("moto_red", "빨간 오토바이", "cute red motorcycle side view"),
    ("bus_yellow", "노란 버스", "cute yellow school bus side view"),
    ("bus_red", "빨간 버스", "cute red city bus side view"),
    ("plane_blue", "파란 비행기", "cute blue airplane side view"),
    ("heli_red", "빨간 헬기", "cute red helicopter side view"),
    ("ship_green", "초록 배", "cute green ship side view"),
    ("train_red", "빨간 기차", "cute red train locomotive side view"),
    ("train_green", "초록 기차", "cute green train locomotive side view"),
    ("warning", "경고 표지", "cute yellow warning triangle road sign"),
    ("school", "스쿨존", "cute school zone road sign"),
    ("wheelchair", "장애인주차", "cute wheelchair accessible parking sign"),
    ("restarea", "휴게소", "cute highway rest area sign"),
    ("snowplow", "제설차", "cute snow plow truck side view"),
]

STYLE = (
    "Premium soft 3D sticker clipart icon, glossy plastic toy style like modern app icons, "
    "rounded bubbly forms, soft studio lighting, subtle drop shadow, "
    "isolated on plain light gray background, no text, no watermark, high-end polished 3D render. Subject: "
)


def remove_bg(im: Image.Image) -> Image.Image:
    im = im.convert("RGBA")
    px = im.load()
    w, h = im.size
    # sample corners
    corners = [px[0, 0], px[w - 1, 0], px[0, h - 1], px[w - 1, h - 1]]
    # flood from edges for near-bg colors
    from collections import deque

    visited = [[False] * w for _ in range(h)]
    q = deque()
    for x in range(w):
        q.append((x, 0))
        q.append((x, h - 1))
    for y in range(h):
        q.append((0, y))
        q.append((w - 1, y))

    def near_bg(c):
        r, g, b, a = c
        # light gray / white / checker-ish
        if min(r, g, b) > 210 and abs(r - g) < 25 and abs(g - b) < 25:
            return True
        # mid gray checker
        if 140 < r < 200 and abs(r - g) < 18 and abs(g - b) < 18:
            return True
        return False

    while q:
        x, y = q.popleft()
        if x < 0 or y < 0 or x >= w or y >= h or visited[y][x]:
            continue
        visited[y][x] = True
        c = px[x, y]
        if not near_bg(c):
            continue
        px[x, y] = (c[0], c[1], c[2], 0)
        q.extend([(x + 1, y), (x - 1, y), (x, y + 1), (x, y - 1)])
    return im


def fit_square(im: Image.Image) -> Image.Image:
    a = im.getchannel("A")
    bbox = a.getbbox()
    if bbox:
        im = im.crop(bbox)
    w, h = im.size
    side = max(w, h)
    pad = int(side * 0.06)
    canvas = Image.new("RGBA", (side + pad * 2, side + pad * 2), (0, 0, 0, 0))
    canvas.paste(im, ((canvas.size[0] - w) // 2, (canvas.size[1] - h) // 2), im)
    # soft shadow
    sh = Image.new("RGBA", canvas.size, (0, 0, 0, 0))
    alpha = canvas.split()[-1]
    sh.paste((25, 28, 35, 50), (6, 10), alpha)
    sh = sh.filter(ImageFilter.GaussianBlur(12))
    out = Image.alpha_composite(sh, canvas)
    return out.resize((SIZE, SIZE), Image.LANCZOS)


def main() -> int:
    assert len(ITEMS) == 100
    OUT.mkdir(parents=True, exist_ok=True)
    missing = []
    manifest = []
    for i, (pid, title, _subj) in enumerate(ITEMS, 1):
        src = SRC / f"traffic_ai_{i:03d}_{pid}.png"
        # also accept without zero pad variants
        if not src.is_file():
            alts = list(SRC.glob(f"traffic_ai_{i:03d}_*.png")) + list(SRC.glob(f"traffic_ai_{pid}.png"))
            src = alts[0] if alts else src
        dest = OUT / f"hq_traffic_{i:03d}_{pid}.png"
        if not src.is_file():
            missing.append(str(src.name))
            continue
        im = remove_bg(Image.open(src))
        im = fit_square(im)
        im.save(dest, "PNG", optimize=True)
        amin, _ = im.getchannel("A").getextrema()
        print(f"[{i}/100] {dest.name} amin={amin} bytes={dest.stat().st_size}")
        manifest.append({
            "title": title,
            "category_slug": "traffic",
            "image_path": f"/assets/cliparts/{dest.name}",
            "hashtags": f"#교통 #탈것 #3D스티커 #{pid}",
            "description": f"고품질 교통 클립아트 — {title}",
            "sort_order": 9400 + i,
        })
    MANIFEST.write_text(json.dumps({"items": manifest}, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"manifest {len(manifest)} missing {len(missing)}")
    if missing:
        print("MISSING:", ", ".join(missing[:20]))
    return 0 if not missing else 1


if __name__ == "__main__":
    raise SystemExit(main())
