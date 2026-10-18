<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 편집기와 같은 Font Awesome Free 카탈로그에서 아이콘 path 를 찾는다.
 * 미리보기 SVG가 편집기 실제 아이콘과 같게 보이도록 쓴다.
 */
final class FontAwesomeIconCatalog
{
    /** @var array<string, array{d:string,w:int,h:int}>|null */
    private static ?array $paths = null;

    /** @return array{d:string,w:int,h:int}|null */
    public static function resolve(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        self::load();
        if (self::$paths === []) {
            return null;
        }
        foreach (self::candidateKeys($name) as $key) {
            if (isset(self::$paths[$key])) {
                return self::$paths[$key];
            }
        }
        return null;
    }

    private static function load(): void
    {
        if (self::$paths !== null) {
            return;
        }
        self::$paths = [];
        $file = self::catalogPath();
        if ($file === null) {
            return;
        }
        $raw = @file_get_contents($file);
        if (!is_string($raw) || $raw === '') {
            return;
        }
        $text = @gzdecode($raw);
        if (!is_string($text) || $text === '') {
            return;
        }

        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $parts = explode("\t", $line);
            if (count($parts) >= 4 && ($parts[1] ?? '') === '>') {
                $alias = self::normalize($parts[0] ?? '');
                $target = self::normalize($parts[2] ?? '');
                if ($alias !== '' && $target !== '' && isset(self::$paths[$target])) {
                    self::$paths[$alias] = self::$paths[$target];
                }
                continue;
            }
            if (count($parts) < 5) {
                continue;
            }
            $slug = self::normalize($parts[0]);
            if ($slug === '') {
                continue;
            }
            $style = match ($parts[1] ?? '') {
                'b' => 'brands',
                'r' => 'regular',
                default => 'solid',
            };
            $w = (int) ($parts[2] ?? 512);
            $h = (int) ($parts[3] ?? 512);
            $d = str_replace("\u{001e}", ' ', (string) $parts[4]);
            if ($d === '') {
                continue;
            }
            $glyph = [
                'd' => $d,
                'w' => $w > 0 ? $w : 512,
                'h' => $h > 0 ? $h : 512,
            ];
            $styled = $slug . '-' . $style;
            if (!isset(self::$paths[$styled])) {
                self::$paths[$styled] = $glyph;
            }
            // solid 를 기본 슬러그로 쓴다. 이미 있으면 덮지 않는다.
            if (!isset(self::$paths[$slug])) {
                self::$paths[$slug] = $glyph;
            }
            $compact = str_replace('-', '', $slug);
            if ($compact !== $slug && !isset(self::$paths[$compact])) {
                self::$paths[$compact] = $glyph;
            }
        }
    }

    private static function catalogPath(): ?string
    {
        $rel = 'editor/assets/fontawesome/fa-free-6.5.2.catalog.gz';
        $candidates = [
            public_path($rel),
            base_path('public/' . $rel),
            base_path('www/' . $rel),
            base_path('editor-src/LabelUp.Editor/wwwroot/assets/fontawesome/fa-free-6.5.2.catalog.gz'),
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    /** @return array<int, string> */
    private static function candidateKeys(string $name): array
    {
        $keys = [];
        $kebab = self::normalize($name);
        if ($kebab !== '') {
            $keys[] = $kebab;
        }
        if (str_ends_with($kebab, '-solid')) {
            $keys[] = substr($kebab, 0, -6);
        }
        $compact = str_replace('-', '', $kebab);
        if ($compact !== '' && $compact !== $kebab) {
            $keys[] = $compact;
        }
        return $keys;
    }

    private static function normalize(string $name): string
    {
        $name = strtolower(trim($name));
        $out = '';
        $len = strlen($name);
        for ($i = 0; $i < $len; $i++) {
            $ch = $name[$i];
            if (ctype_alnum($ch)) {
                $out .= $ch;
            } elseif ($ch === '-' || $ch === '_' || $ch === ' ' || $ch === '.') {
                if ($out !== '' && !str_ends_with($out, '-')) {
                    $out .= '-';
                }
            }
        }
        return trim($out, '-');
    }
}
