"""Recolor Labi character assets from blue/cyan to LabelUp burgundy palette."""
from __future__ import annotations

import colorsys
import shutil
import sys
from pathlib import Path

from PIL import Image

# LabelUp brand (brand.css)
BRAND = {
    "burgundy": (123, 40, 64),
    "burgundy_dark": (94, 31, 48),
    "burgundy_light": (154, 58, 85),
    "accent_soft": (243, 232, 236),
    "accent_soft_2": (232, 212, 219),
}

ASSETS = [
    "labi-icon.png",
    "labi-ai-design.png",
    "labi-paper.png",
    "labi-spec.png",
    "labi-print.png",
    "labi-cs.png",
    "labi-shop.png",
]


def rgb_to_hsl(r: int, g: int, b: int) -> tuple[float, float, float]:
    h, l, s = colorsys.rgb_to_hls(r / 255, g / 255, b / 255)
    return h * 360, s, l


def hsl_to_rgb(h: float, s: float, l: float) -> tuple[int, int, int]:
    r, g, b = colorsys.hls_to_rgb(h / 360, l, s)
    return int(round(r * 255)), int(round(g * 255)), int(round(b * 255))


def lerp(a: float, b: float, t: float) -> float:
    return a + (b - a) * t


def is_blue_family(r: int, g: int, b: int, h: float, s: float, l: float) -> bool:
    if s < 0.05 and l > 0.82:
        return False
    if s < 0.05:
        return False

    # Yellow / warm accents
    if 35 <= h <= 75 and s >= 0.25:
        return False
    # Pink mouth
    if (h <= 25 or h >= 330) and r > g + 20 and s >= 0.15:
        return False
    # Already burgundy / maroon
    if 310 <= h <= 360 and s >= 0.12 and r > b + 10:
        return False

    # Primary saturated blues
    if 165 <= h <= 265 and s >= 0.08:
        return True

    # Cyan glow (eyes, rings)
    if 165 <= h <= 210 and s >= 0.05 and l >= 0.55:
        return True

    # Blue-tinted light grays on edges
    if b > r + 8 and b > g + 2 and l >= 0.72 and s <= 0.22:
        return True

    return b > r + 25 and b > 90


def remap_blue(h: float, s: float, l: float) -> tuple[float, float, float]:
    """Map blue/cyan HSL to burgundy family while preserving shading."""
    # 200° (cyan) .. 230° (royal blue) -> 350° .. 338°
    src_h = max(165.0, min(265.0, h))
    t = (src_h - 165.0) / 100.0
    new_h = lerp(352.0, 336.0, t)

    if l >= 0.78:
        # Highlights / glow -> soft burgundy tint
        new_s = max(0.08, s * 0.55)
        new_l = min(0.94, l * 0.96)
        new_h = 350.0
    elif l >= 0.55:
        new_s = min(0.72, s * 0.78 + 0.12)
        new_l = l * 0.82 + 0.06
        new_h = lerp(355.0, 342.0, t)
    elif l >= 0.32:
        new_s = min(0.78, s * 0.9 + 0.08)
        new_l = l * 0.88 + 0.02
    else:
        new_s = min(0.85, s * 0.95 + 0.05)
        new_l = max(0.12, l * 0.92)

    return new_h, new_s, new_l


def recolor_pixel(r: int, g: int, b: int, a: int) -> tuple[int, int, int, int]:
    if a < 8:
        return r, g, b, a

    h, s, l = rgb_to_hsl(r, g, b)
    if not is_blue_family(r, g, b, h, s, l):
        return r, g, b, a

    nh, ns, nl = remap_blue(h, s, l)
    nr, ng, nb = hsl_to_rgb(nh, ns, nl)
    return nr, ng, nb, a


def recolor_image(src: Path, dst: Path) -> None:
    im = Image.open(src).convert("RGBA")
    px = im.load()
    w, h = im.size
    for y in range(h):
        for x in range(w):
            px[x, y] = recolor_pixel(*px[x, y])
    im.save(dst, "PNG", optimize=True)


def main() -> None:
    assets_dir = Path(__file__).resolve().parents[1] / "public" / "assets"
    backup_dir = assets_dir / "_labi_backup_blue"
    backup_dir.mkdir(exist_ok=True)

    for name in ASSETS:
        src = assets_dir / name
        if not src.exists():
            print(f"skip missing: {name}")
            continue
        bak = backup_dir / name
        if not bak.exists():
            shutil.copy2(src, bak)
        recolor_image(bak if bak.exists() else src, src)
        print(f"recolored {name} ({src.stat().st_size} bytes)")


if __name__ == "__main__":
    main()
