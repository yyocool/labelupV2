#!/usr/bin/env python3
"""Publish LabelUp Blazor WASM editor into public/editor."""
from __future__ import annotations

import shutil
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PROJECT = ROOT / "editor-src" / "LabelUp.Editor"
OUT = ROOT / "public" / "editor"


def apply_icu_bin_workaround(out: Path) -> None:
    """Some hosts block .dat downloads, which stops the editor from booting.

    Ship the ICU table as icudt.bin as well and point the boot files at it.
    Publishing wipes public/editor, so this has to run on every publish or the
    fix silently disappears.
    """
    framework = out / "_framework"
    source = framework / "icudt.dat"
    if not source.exists():
        print("icudt.dat not found; skipped ICU .bin workaround", file=sys.stderr)
        return

    shutil.copyfile(source, framework / "icudt.bin")

    for name in ("blazor.boot.json", "dotnet.js"):
        target = framework / name
        if not target.exists():
            print(f"{name} not found; ICU reference left alone", file=sys.stderr)
            continue
        text = target.read_text(encoding="utf-8")
        if "icudt.dat" not in text:
            continue
        target.write_text(text.replace("icudt.dat", "icudt.bin"), encoding="utf-8")

    print("Applied ICU .bin workaround")


def main() -> int:
    if not PROJECT.exists():
        print(f"Missing project: {PROJECT}", file=sys.stderr)
        return 1

    if OUT.exists():
        shutil.rmtree(OUT)
    OUT.mkdir(parents=True, exist_ok=True)

    cmd = [
        "dotnet",
        "publish",
        str(PROJECT / "LabelUp.Editor.csproj"),
        "-c",
        "Release",
        "-o",
        str(OUT),
    ]
    print(" ".join(cmd))
    proc = subprocess.run(cmd, check=False)
    if proc.returncode != 0:
        return proc.returncode

    www = OUT / "wwwroot"
    if www.is_dir():
        for child in www.iterdir():
            dest = OUT / child.name
            if dest.exists():
                if dest.is_dir():
                    shutil.rmtree(dest)
                else:
                    dest.unlink()
            shutil.move(str(child), str(dest))
        www.rmdir()

    for name in (
        "emcc-props.json",
        "LabelUp.Editor.staticwebassets.endpoints.json",
        "web.config",
    ):
        p = OUT / name
        if p.exists():
            p.unlink()

    apply_icu_bin_workaround(OUT)

    # PHPS(www.labelup.co.kr) 500s if any .htaccess exists under /editor/.
    # MIME/SPA rules live in the hosting www/.htaccess instead.
    htaccess = OUT / ".htaccess"
    if htaccess.exists():
        htaccess.unlink()

    print(f"Published to {OUT}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
