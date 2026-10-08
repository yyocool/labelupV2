#!/usr/bin/env python3
"""Publish LabelUp Blazor WASM editor into public/editor."""
from __future__ import annotations

import hashlib
import re
import shutil
import subprocess
import sys
import time
from pathlib import Path

BOOT_SENTINEL = "BOOTSTAMP"

# index.html 이 ?v= 를 달고 부르는, public/editor 안에 함께 실리는 파일들.
# 이 목록에 있는 것은 퍼블리시할 때마다 내용 해시로 토큰을 새로 찍는다.
# /js/home-ai-chat.js 처럼 사이트 뿌리에서 오는 것은 여기서 해시를 뜰 수 없어 뺀다.
LOCAL_ASSETS = (
    "css/app.css",
    "css/editor.css",
    "css/tutorial.css",
    "js/editor.js",
    "js/panel-dock.js",
    "js/tutorial.js",
    "img/labi-wink.png",
)

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


def stamp_local_assets(out: Path) -> int:
    """index.html 이 부르는 css·js·이미지의 ?v= 를 내용 해시로 바꾼다.

    이 토큰들은 여태 손으로 적었고, 그래서 멈춰 있었다. 2026-10-08 에 editor.js 에
    단축키를 넣어 올렸는데 토큰은 20261006svg 그대로여서, 그 파일을 캐시에 들고 있던
    브라우저는 새 코드를 받지 못했다. 서버가 /editor 응답에 Cache-Control 을 주지 않아
    브라우저가 제 나름대로 오래 들고 있기 때문에 더 그렇다.

    해시로 찍으면 내용이 바뀔 때만 토큰이 움직이고 안 바뀌면 그대로라, 받을 것만 받는다.
    """
    index = out / "index.html"
    if not index.exists():
        print("index.html missing; cannot stamp asset versions", file=sys.stderr)
        return 1

    text = index.read_text(encoding="utf-8")
    stamped = []
    for rel in LOCAL_ASSETS:
        if f'"{rel}' not in text and f'"/{rel}' not in text:
            continue  # 더 이상 부르지 않는 파일. 캐시 걱정도 없다.
        target = out / rel
        if not target.exists():
            print(f"index.html references {rel} but it was not published", file=sys.stderr)
            return 1

        stamp = hashlib.sha256(target.read_bytes()).hexdigest()[:12]
        text, hits = re.subn(
            r'("/?' + re.escape(rel) + r'\?v=)[^"]*"',
            lambda m: m.group(1) + stamp + '"',
            text,
        )
        if hits == 0:
            print(
                f"{rel} is referenced without a ?v= token, so browsers can keep serving an "
                "old copy after deploy. Add ?v= to it in "
                "editor-src/LabelUp.Editor/wwwroot/index.html.",
                file=sys.stderr,
            )
            return 1
        stamped.append(f"{rel}={stamp}")

    index.write_text(text, encoding="utf-8")
    print("Stamped asset versions: " + ", ".join(stamped))
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


def clean_intermediates() -> None:
    """Force the native relink, because MSBuild will happily skip it.

    WasmBuildNative=true means the P/Invoke table is generated from the managed
    assemblies and compiled into dotnet.native.wasm at publish time. MSBuild
    decides whether to redo that native link from file timestamps, and it gets
    the call wrong when only the set of P/Invokes changes: adding a new
    SkiaSharp call does not make any input look newer than dotnet.native.wasm,
    so the old module is reused and the new entry is simply absent from its
    table.

    Nothing warns about it. The publish succeeds, every file verifies, and the
    app dies at runtime the first time that call is reached - Mono cannot find
    the interp-to-native trampoline and aborts the whole runtime with
    aot-runtime-wasm.c "<disabled>" and exit(1). That shipped once already: the
    deployed dotnet.native.wasm was 125 bytes smaller than a clean build's
    because it was missing sk_paint_get_fill_path and friends.

    A clean relink costs about a minute and a half. A native module that does
    not match the assemblies costs a dead editor in production.
    """
    for name in ("obj", "bin"):
        target = PROJECT / name
        # Windows hands out transient directory locks (search indexer, antivirus,
        # a just-closed MSBuild node), so one failed rmtree means "wait", not
        # "give up" - giving up here would silently reuse the stale native build.
        for attempt in range(1, 6):
            if not target.exists():
                break
            try:
                shutil.rmtree(target)
            except PermissionError as ex:
                if attempt == 5:
                    raise
                print(f"{name}/ locked ({ex.strerror}); retrying in 3s")
                time.sleep(3)
    print("Cleared obj/ and bin/ to force a fresh native relink")


def main() -> int:
    if not PROJECT.exists():
        print(f"Missing project: {PROJECT}", file=sys.stderr)
        return 1

    clean_intermediates()

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

    if stamp_local_assets(OUT) != 0:
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
