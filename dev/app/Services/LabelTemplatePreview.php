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
        foreach ($objects as $obj) {
            if (!is_array($obj)) {
                continue;
            }
            if (array_key_exists('visible', $obj) && empty($obj['visible'])) {
                continue;
            }
            $drawn = self::objectSvg($obj);
            if ($drawn !== '') {
                $parts[] = $drawn;
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . self::n($w) . ' ' . self::n($h) . '" preserveAspectRatio="xMidYMid meet" role="img">'
            . implode('', $parts)
            . '</svg>';
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
        return is_array($objects) ? $objects : [];
    }

    private static function shapeFill(string $kind, float $w, float $h, float $radius, string $fill): string
    {
        $attr = ' fill="' . self::color($fill) . '"';
        if ($kind === 'ellipse') {
            return '<ellipse cx="' . self::n($w / 2) . '" cy="' . self::n($h / 2) . '" rx="' . self::n($w / 2) . '" ry="' . self::n($h / 2) . '"' . $attr . '/>';
        }
        $r = max(0.0, min($radius, min($w, $h) / 2));
        return '<rect x="0" y="0" width="' . self::n($w) . '" height="' . self::n($h) . '" rx="' . self::n($r) . '" ry="' . self::n($r) . '"' . $attr . '/>';
    }

    /** @param array<string, mixed> $obj */
    private static function objectSvg(array $obj): string
    {
        $type = strtolower((string) ($obj['type'] ?? 'rect'));
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
            'text' => self::textSvg($obj, $x, $y, $w, $h, $fill),
            'barcode' => self::barcodeSvg($x, $y, $w, $h, $fill),
            'qr' => self::qrSvg($x, $y, $w, $h, $fill),
            'table' => self::tableSvg($obj, $x, $y, $w, $h, $fill, $stroke),
            default => '<rect x="' . self::n($x) . '" y="' . self::n($y) . '" width="' . self::n($w) . '" height="' . self::n($h) . '" fill="' . $fill . '"' . $strokeAttr . '/>',
        };
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
        return htmlspecialchars(mb_substr($text, 0, 40), ENT_QUOTES, 'UTF-8');
    }
}
