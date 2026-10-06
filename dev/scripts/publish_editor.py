#!/usr/bin/env python3
"""Publish LabelUp Blazor WASM editor into public/editor."""
from __future__ import annotations

import hashlib
import shutil
import subprocess
import sys
from pathlib import Path

BOOT_SENTINEL = "BOOTSTAMP"

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


def stamp_boot_version(out: Path) -> int:
    """Give the _framework boot resources a cache token that follows the build.

    LabelUp.Editor.wasm, dotnet.native.wasm and blazor.boot.json all keep fixed
    file names on .NET 8, so index.html's ?v= token is the only thing that can
    invalidate them. index.html also overrides loadBootResource, which turns off
    Blazor's integrity checking - so if the token ever stands still, a browser is
    free to pair a cached old dotnet.native.wasm with freshly deployed assemblies.
    The app then dies on the first native call that the old module never knew
    about (aot-runtime-wasm.c "<disabled>", exit(1)).

    Hashing blazor.boot.json is enough: it already lists the hash of every
    assembly and of the native module, so the token moves exactly when a boot
    resource moves and stays put when nothing changed.
    """
    boot = out / "_framework" / "blazor.boot.json"
    index = out / "index.html"
    if not boot.exists() or not index.exists():
        print("blazor.boot.json or index.html missing; cannot stamp boot version", file=sys.stderr)
        return 1

    stamp = hashlib.sha256(boot.read_bytes()).hexdigest()[:12]
    text = index.read_text(encoding="utf-8")
    hits = text.count(BOOT_SENTINEL)
    if hits == 0:
        print(
            f"index.html has no {BOOT_SENTINEL} placeholder. The boot cache token would be "
            "frozen, which lets browsers mix stale _framework files. Restore it in "
            "editor-src/LabelUp.Editor/wwwroot/index.html.",
            file=sys.stderr,
        )
        return 1

    index.write_text(text.replace(BOOT_SENTINEL, stamp), encoding="utf-8")
    print(f"Stamped boot cache token v={stamp} ({hits} spots)")
    return 0


def write_charset_shim(out: Path) -> None:
    """Serve index.html through PHP so the charset header is right.

    The host sends .html without a charset and we cannot use .htaccess here,
    so DirectoryIndex lands on this file instead. Publishing wipes
    public/editor, so it has to be written again every time.
    """
    (out / "index.php").write_text(
        "<?php\n"
        "header('Content-Type: text/html; charset=utf-8');\n"
        "readfile(__DIR__ . '/index.html');\n",
        encoding="utf-8",
    )
    print("Wrote index.php charset shim")


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

    # ICU 손질이 blazor.boot.json 을 고치므로 그 뒤에 해시를 떠야 한다.
    if stamp_boot_version(OUT) != 0:
        return 1

    # PHPS(www.labelup.co.kr) 500s if any .htaccess exists under /editor/.
    # MIME/SPA rules live in the hosting www/.htaccess instead.
    htaccess = OUT / ".htaccess"
    if htaccess.exists():
        htaccess.unlink()

    write_charset_shim(OUT)

    print(f"Published to {OUT}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
