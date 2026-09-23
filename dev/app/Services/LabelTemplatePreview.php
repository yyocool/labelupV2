<?php

declare(strict_types=1);

namespace App\Services;

final class LabelTemplatePreview
{
    /** @param array<string, mixed> $row */
    public static function svgFromRow(array $row, array $document = []): string
    {
        $paper = is_array($document['paper'] ?? null) ? $document['paper'] : [];
        $w = max(8.0, (float) ($paper['labelWidthMm'] ?? $row['paper_w_mm'] ?? $row['widthMm'] ?? 70));
        $h = max(8.0, (float) ($paper['labelHeightMm'] ?? $row['paper_h_mm'] ?? $row['heightMm'] ?? 36));
        $bg = (string) ($document['background'] ?? $paper['labelColor'] ?? '#FFFFFF');
        $shape = is_array($paper['shape'] ?? null) ? $paper['shape'] : [];
        $kind = (string) ($shape['kind'] ?? $row['paper_shape'] ?? $row['shape'] ?? 'rect');
        $radius = (float) ($shape['cornerRadiusMm'] ?? 2);
        $objects = self::firstObjects($document);

        $parts = [self::shapeFill($kind, $w, $h, $radius, $bg)];
        $clipId = 'lp' . preg_replace('/[^a-zA-Z0-9]/', '', (string) ($row['id'] ?? ''))
            . substr(md5($w . 'x' . $h . $kind . ($row['title'] ?? '') . ($row['updated_at'] ?? '')), 0, 8);
        $inner = [];
        foreach ($objects as $obj) {
            if (!is_array($obj)) {
                continue;
            }
            if (array_key_exists('visible', $obj) && empty($obj['visible'])) {
                continue;
            }
            $drawn = self::objectSvg($obj);
            if ($drawn !== '') {
                $inner[] = $drawn;
            }
        }

        $clipDef = '<defs><clipPath id="' . $clipId . '">' . self::shapeFill($kind, $w, $h, $radius, '#fff') . '</clipPath></defs>';
        $body = $inner === []
            ? ''
            : '<g clip-path="url(#' . $clipId . ')">' . implode('', $inner) . '</g>';

        return '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="' . self::n($w) . '" height="' . self::n($h) . '" viewBox="0 0 ' . self::n($w) . ' ' . self::n($h) . '" preserveAspectRatio="xMidYMid meet" role="img">'
            . $clipDef
            . implode('', $parts)
            . $body
            . '</svg>';
    }

    /** img src로 쓸 수 있게 로컬 이미지를 data URI로 넣는다. */
    public static function withInlinedImages(string $svg): string
    {
        $out = preg_replace_callback(
            '#((?:href|xlink:href)=")(/assets/[^"]+)(")#',
            static function (array $m): string {
                $full = public_path(ltrim($m[2], '/'));
                if (!is_file($full)) {
                    return $m[1] . $m[2] . $m[3];
                }
                $ext = strtolower((string) pathinfo($full, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                    default => 'image/png',
                };
                $bin = @file_get_contents($full);
                if (!is_string($bin) || $bin === '') {
                    return $m[1] . $m[2] . $m[3];
                }
                return $m[1] . 'data:' . $mime . ';base64,' . base64_encode($bin) . $m[3];
            },
            $svg
        );

        return is_string($out) ? $out : $svg;
    }

    /** @param array<string, mixed> $document */
    /** @return array<int, mixed> */
    private static function firstObjects(array $document): array
    {
        $pages = $document['pages'] ?? [];
        if (!is_array($pages) || $pages === []) {
            return [];
        }
        $cells = $pages[0]['cells'] ?? [];
        if (!is_array($cells) || $cells === []) {
            return [];
        }
        $objects = $cells[0]['objects'] ?? [];
        if (!is_array($objects)) {
            return [];
        }
        usort($objects, static function ($a, $b): int {
            $za = (int) (is_array($a) ? ($a['zIndex'] ?? 0) : 0);
            $zb = (int) (is_array($b) ? ($b['zIndex'] ?? 0) : 0);
            return $za <=> $zb;
        });
        return $objects;
    }

    private static function shapeFill(string $kind, float $w, float $h, float $radius, string $fill): string
    {
        $attr = ' fill="' . self::color($fill) . '" stroke="#e4ddd6" stroke-width="' . self::n(max(0.18, min($w, $h) * 0.012)) . '"';
        if ($kind === 'ellipse') {
            return '<ellipse cx="' . self::n($w / 2) . '" cy="' . self::n($h / 2) . '" rx="' . self::n($w / 2) . '" ry="' . self::n($h / 2) . '"' . $attr . '/>';
        }
        $r = max(0.0, min($radius, min($w, $h) / 2));
        return '<rect x="0" y="0" width="' . self::n($w) . '" height="' . self::n($h) . '" rx="' . self::n($r) . '" ry="' . self::n($r) . '"' . $attr . '/>';
    }

    /** @param array<string, mixed> $obj */
    private static function objectSvg(array $obj): string
    {
        $type = self::typeName($obj['type'] ?? 'rect');
        $x = (float) ($obj['x'] ?? 0);
        $y = (float) ($obj['y'] ?? 0);
        $w = max(0.2, (float) ($obj['width'] ?? 1));
        $h = max(0.2, (float) ($obj['height'] ?? 1));
        $fill = self::color((string) ($obj['fill'] ?? '#7B2840'));
        $stroke = self::color((string) ($obj['stroke'] ?? 'transparent'));
        $sw = (float) ($obj['strokeWidth'] ?? 0);
        $strokeAttr = ($stroke !== 'none' && $sw > 0) ? ' stroke="' . $stroke . '" stroke-width="' . self::n($sw) . '"' : '';

        return match ($type) {
            'ellipse' => '<ellipse cx="' . self::n($x + $w / 2) . '" cy="' . self::n($y + $h / 2) . '" rx="' . self::n($w / 2) . '" ry="' . self::n($h / 2) . '" fill="' . $fill . '"' . $strokeAttr . '/>',
            'line' => '<line x1="' . self::n($x) . '" y1="' . self::n($y) . '" x2="' . self::n($x + $w) . '" y2="' . self::n($y + $h) . '" stroke="' . ($stroke === 'none' ? $fill : $stroke) . '" stroke-width="' . self::n(max(0.2, $sw)) . '"/>',
            'shape', 'gradient' => self::shapeSvg($obj, $x, $y, $w, $h, $fill, $strokeAttr),
            'text' => self::textSvg($obj, $x, $y, $w, $h, $fill),
            'barcode' => self::barcodeSvg($x, $y, $w, $h, $fill),
            'qr' => self::qrSvg($x, $y, $w, $h, $fill),
            'table' => self::tableSvg($obj, $x, $y, $w, $h, $fill, $stroke),
            'icon' => self::iconSvg($obj, $x, $y, $w, $h, $fill),
            'image', 'clipart' => self::imageSvg($obj, $x, $y, $w, $h),
            default => '<rect x="' . self::n($x) . '" y="' . self::n($y) . '" width="' . self::n($w) . '" height="' . self::n($h) . '" fill="' . $fill . '"' . $strokeAttr . '/>',
        };
    }

    private static function typeName(mixed $type): string
    {
        if (is_int($type) || (is_string($type) && is_numeric($type))) {
            return match ((int) $type) {
                0 => 'text',
                1 => 'rect',
                2 => 'ellipse',
                3 => 'line',
                4 => 'shape',
                5 => 'image',
                6 => 'barcode',
                7 => 'qr',
                8 => 'table',
                9 => 'clipart',
                10 => 'icon',
                11 => 'gradient',
                default => 'rect',
            };
        }

        return strtolower(trim((string) $type));
    }

    /** @param array<string, mixed> $obj */
    private static function shapeSvg(array $obj, float $x, float $y, float $w, float $h, string $fill, string $strokeAttr): string
    {
        $kind = strtolower((string) ($obj['shapeKind'] ?? 'rect'));
        if ($kind === 'triangle') {
            $points = self::n($x + $w / 2) . ',' . self::n($y)
                . ' ' . self::n($x + $w) . ',' . self::n($y + $h)
                . ' ' . self::n($x) . ',' . self::n($y + $h);
            return '<polygon points="' . $points . '" fill="' . $fill . '"' . $strokeAttr . '/>';
        }
        if ($kind === 'ellipse' || $kind === 'circle') {
            return '<ellipse cx="' . self::n($x + $w / 2) . '" cy="' . self::n($y + $h / 2) . '" rx="' . self::n($w / 2) . '" ry="' . self::n($h / 2) . '" fill="' . $fill . '"' . $strokeAttr . '/>';
        }
        return '<rect x="' . self::n($x) . '" y="' . self::n($y) . '" width="' . self::n($w) . '" height="' . self::n($h) . '" fill="' . $fill . '"' . $strokeAttr . '/>';
    }

    /** @param array<string, mixed> $obj */
    private static function iconSvg(array $obj, float $x, float $y, float $w, float $h, string $fill): string
    {
        $cx = $x + $w / 2;
        $cy = $y + $h / 2;
        $r = min($w, $h) / 2;
        $name = strtolower((string) ($obj['iconName'] ?? ''));
        $mark = match (true) {
            str_contains($name, 'check') => '✓',
            str_contains($name, 'xmark') || $name === 'ban' || $name === 'xmark' => '✕',
            str_contains($name, 'arrow-up') => '↑',
            str_contains($name, 'truck') => '🚚',
            str_contains($name, 'lock') => '🔒',
            default => '',
        };
        $circle = '<ellipse cx="' . self::n($cx) . '" cy="' . self::n($cy) . '" rx="' . self::n($r) . '" ry="' . self::n($r) . '" fill="' . $fill . '"/>';
        if ($mark === '') {
            return $circle;
        }
        return '<g>' . $circle . '<text x="' . self::n($cx) . '" y="' . self::n($cy) . '" fill="#FFFFFF" font-size="' . self::n(max(2.4, $r * 0.9)) . '" font-weight="700" text-anchor="middle" dominant-baseline="middle">' . self::xml($mark) . '</text></g>';
    }

    /** @param array<string, mixed> $obj */
    private static function imageSvg(array $obj, float $x, float $y, float $w, float $h): string
    {
        $src = trim((string) ($obj['imageData'] ?? $obj['svg'] ?? ''));
        if ($src === '' || str_starts_with($src, 'data:')) {
            // data URL은 미리보기에서 생략하고 플레이스홀더
            return '<rect x="' . self::n($x) . '" y="' . self::n($y) . '" width="' . self::n($w) . '" height="' . self::n($h) . '" fill="#f3f1ef" rx="1"/>';
        }
        if (str_starts_with($src, '//')) {
            $src = 'https:' . $src;
        } elseif (!str_starts_with($src, 'http') && !str_starts_with($src, '/')) {
            $src = '/' . ltrim($src, '/');
        }
        if (str_starts_with($src, '/') && !str_starts_with($src, '//')) {
            $full = public_path(ltrim($src, '/'));
            if (!is_file($full)) {
                return '';
            }
        }
        $href = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
        $fit = strtolower((string) ($obj['imageFit'] ?? 'contain'));
        $par = $fit === 'cover' ? 'xMidYMid slice' : 'xMidYMid meet';
        return '<image href="' . $href . '" xlink:href="' . $href . '" x="' . self::n($x) . '" y="' . self::n($y) . '" width="' . self::n($w) . '" height="' . self::n($h) . '" preserveAspectRatio="' . $par . '"/>';
    }

    /** @param array<string, mixed> $obj */
    private static function textSvg(array $obj, float $x, float $y, float $w, float $h, string $fill): string
    {
        $text = trim((string) ($obj['text'] ?? ''));
        if ($text === '') {
            return '';
        }
        $size = max(1.6, (float) ($obj['fontSize'] ?? 3.2));
        $align = (string) ($obj['textAlign'] ?? 'center');
        $anchor = $align === 'left' ? 'start' : ($align === 'right' ? 'end' : 'middle');
        $tx = $align === 'left' ? $x + 0.4 : ($align === 'right' ? $x + $w - 0.4 : $x + $w / 2);
        $weight = !empty($obj['bold']) ? '700' : '600';
        return '<text x="' . self::n($tx) . '" y="' . self::n($y + $h / 2) . '" fill="' . $fill . '" font-size="' . self::n($size) . '" font-weight="' . $weight . '" text-anchor="' . $anchor . '" dominant-baseline="middle" font-family="Pretendard,Malgun Gothic,sans-serif">'
            . self::xml($text)
            . '</text>';
    }

    private static function barcodeSvg(float $x, float $y, float $w, float $h, string $fill): string
    {
        $bars = '';
        $count = 18;
        $gap = $w / $count;
        for ($i = 0; $i < $count; $i++) {
            if ($i % 3 === 2) {
                continue;
            }
            $bw = $gap * (($i % 2 === 0) ? 0.7 : 0.4);
            $bars .= '<rect x="' . self::n($x + $i * $gap) . '" y="' . self::n($y) . '" width="' . self::n($bw) . '" height="' . self::n($h * 0.78) . '" fill="' . $fill . '"/>';
        }
        return '<g>' . $bars . '</g>';
    }

    private static function qrSvg(float $x, float $y, float $w, float $h, string $fill): string
    {
        $n = 7;
        $cw = $w / $n;
        $ch = $h / $n;
        $cells = '';
        for ($r = 0; $r < $n; $r++) {
            for ($c = 0; $c < $n; $c++) {
                $on = ($r + $c) % 2 === 0 || ($r < 2 && $c < 2) || ($r < 2 && $c > $n - 3) || ($r > $n - 3 && $c < 2);
                if (!$on) {
                    continue;
                }
                $cells .= '<rect x="' . self::n($x + $c * $cw) . '" y="' . self::n($y + $r * $ch) . '" width="' . self::n($cw) . '" height="' . self::n($ch) . '" fill="' . $fill . '"/>';
            }
        }
        return '<g>' . $cells . '</g>';
    }

    /** @param array<string, mixed> $obj */
    private static function tableSvg(array $obj, float $x, float $y, float $w, float $h, string $fill, string $stroke): string
    {
        $rows = max(1, (int) ($obj['tableRows'] ?? 2));
        $cols = max(1, (int) ($obj['tableCols'] ?? 2));
        $cells = is_array($obj['tableCells'] ?? null) ? $obj['tableCells'] : [];
        $cw = $w / $cols;
        $ch = $h / $rows;
        $out = '<g>';
        $out .= '<rect x="' . self::n($x) . '" y="' . self::n($y) . '" width="' . self::n($w) . '" height="' . self::n($h) . '" fill="#fff" stroke="' . ($stroke === 'none' ? $fill : $stroke) . '" stroke-width="0.2"/>';
        $i = 0;
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                $cx = $x + $c * $cw;
                $cy = $y + $r * $ch;
                $out .= '<rect x="' . self::n($cx) . '" y="' . self::n($cy) . '" width="' . self::n($cw) . '" height="' . self::n($ch) . '" fill="none" stroke="' . ($stroke === 'none' ? $fill : $stroke) . '" stroke-width="0.15"/>';
                $txt = trim((string) ($cells[$i] ?? ''));
                $i++;
                if ($txt !== '') {
                    $out .= '<text x="' . self::n($cx + $cw / 2) . '" y="' . self::n($cy + $ch / 2) . '" fill="' . $fill . '" font-size="' . self::n(max(1.6, min($ch * 0.45, 2.6))) . '" text-anchor="middle" dominant-baseline="middle" font-family="Pretendard,sans-serif">' . self::xml($txt) . '</text>';
                }
            }
        }
        return $out . '</g>';
    }

    private static function color(string $value): string
    {
        $value = trim($value);
        if ($value === '' || strcasecmp($value, 'transparent') === 0) {
            return 'none';
        }
        if (preg_match('/^#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value) || preg_match('/^rgba?\(/', $value)) {
            return str_starts_with($value, '#') || str_starts_with($value, 'rgb') ? $value : '#' . $value;
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private static function n(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private static function xml(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return htmlspecialchars(mb_substr($text, 0, 80), ENT_QUOTES, 'UTF-8');
    }
}
