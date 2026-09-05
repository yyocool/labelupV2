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

    # Keep Apache helper for MIME / SPA
    htaccess = OUT / ".htaccess"
    if not htaccess.exists():
        htaccess.write_text(
            """DirectoryIndex index.html
<IfModule mod_mime.c>
  AddType application/wasm .wasm
  AddType application/octet-stream .dll
</IfModule>
<IfModule mod_headers.c>
  <FilesMatch "\\.(wasm|dll|json)$">
    Header set Cache-Control "no-cache, must-revalidate"
  </FilesMatch>
</IfModule>
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /editor/
  RewriteCond %{REQUEST_FILENAME} -f [OR]
  RewriteCond %{REQUEST_FILENAME} -d
  RewriteRule ^ - [L]
  RewriteRule ^ index.html [L]
</IfModule>
""",
            encoding="utf-8",
        )

    print(f"Published to {OUT}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
